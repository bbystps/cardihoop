#!/usr/bin/env python3
import os
import json
import struct
import time
import glob
import sys
from dataclasses import dataclass, field
from typing import Dict, Optional, List, Tuple

import numpy as np
import paho.mqtt.client as mqtt
from edge_impulse_linux.runner import ImpulseRunner

# =========================================================
# CONFIG
# =========================================================
MQTT_HOST = os.getenv("MQTT_HOST", "18.138.249.117")
MQTT_PORT = int(os.getenv("MQTT_PORT", "1883"))
MQTT_USER = os.getenv("MQTT_USER", "mqttuser")
MQTT_PASS = os.getenv("MQTT_PASS", "mqttpass")
MQTT_TOPIC_IN = os.getenv("MQTT_TOPIC_IN", "ecg/data")

OUT_DIR = os.getenv("OUT_DIR", "ecg_out")
os.makedirs(OUT_DIR, exist_ok=True)

MODEL_PATH = os.getenv(
    "MODEL_PATH",
    "/var/www/html/CardiHoop/service/EImodels/cardihoopml-linux-x86_64-v1"
)

MQTT_TOPIC_SAVED  = os.getenv("MQTT_TOPIC_SAVED", "Cardihoop/ScanSaved")
MQTT_TOPIC_RESULT = os.getenv("MQTT_TOPIC_RESULT", "Cardihoop/ScanResult")

DEVICE_NAME = os.getenv("DEVICE_NAME", "cardihoop-ecg")
DEVICE_TYPE = os.getenv("DEVICE_TYPE", "ESP32_AD8232")

# =========================================================
# TRAINING / INFERENCE MATCH SETTINGS
# These should match your dataset generation
# =========================================================
TARGET_FS = int(os.getenv("TARGET_FS", "128"))
WINDOW_SECONDS = int(os.getenv("WINDOW_SECONDS", "60"))
EXPECTED_TOTAL_SAMPLES = TARGET_FS * WINDOW_SECONDS   # 7680
USE_OVERLAP = os.getenv("USE_OVERLAP", "0") == "1"
NORMALIZE_EACH_WINDOW = os.getenv("NORMALIZE_EACH_WINDOW", "1") == "1"

DEFAULT_STRIDE_MODE = os.getenv("STRIDE_MODE", "nonoverlap")      # nonoverlap | overlap_1s
PAD_MODE = os.getenv("PAD_MODE", "edge")                         # edge | zero
INCOMPLETE_LAST_WINDOW = os.getenv("LAST_WINDOW", "pad")         # pad | drop

# =========================================================
# OPTIONAL EXTRA FILTERS
# IMPORTANT:
# Keep disabled unless your training data used the same filtering.
# =========================================================
FILTER_ENABLE = os.getenv("FILTER_ENABLE", "0") == "1"
HP_ALPHA = float(os.getenv("HP_ALPHA", "0.995"))
MA_WIN = int(os.getenv("MA_WIN", "5"))

# If your EI model was trained on int16-scaled input, set USE_INT16=1.
# Otherwise keep 0.
USE_INT16 = os.getenv("USE_INT16", "0") == "1"

# Protocol constants
MAGIC0 = ord("E")
MAGIC1 = ord("C")
PKT_TYPE_DATA = 0x01


# =========================================================
# SCAN STATE
# =========================================================
@dataclass
class ScanState:
    scan_id: int
    fs_hz: int
    total_samples: int
    samples: List[Optional[int]] = field(default_factory=list)
    received_count: int = 0
    started_at: float = field(default_factory=time.time)

    def __post_init__(self):
        self.samples = [None] * self.total_samples

    def put_chunk(self, start_index: int, chunk: List[int]) -> None:
        end = start_index + len(chunk)
        if start_index < 0 or end > self.total_samples:
            raise ValueError(
                f"Chunk out of range: start={start_index}, len={len(chunk)}, total={self.total_samples}"
            )

        for i, v in enumerate(chunk):
            idx = start_index + i
            if self.samples[idx] is None:
                self.received_count += 1
            self.samples[idx] = v

    def missing(self) -> int:
        return self.total_samples - self.received_count


scans: Dict[int, ScanState] = {}

EI_RUNNER: Optional[ImpulseRunner] = None
EI_MODEL_INFO: Optional[dict] = None


# =========================================================
# OPTIONAL FILTERS
# =========================================================
def high_pass_1st(x: np.ndarray, alpha: float = 0.995) -> np.ndarray:
    x = x.astype(np.float32, copy=False)
    if x.size < 2:
        return x.copy()

    y = np.zeros_like(x, dtype=np.float32)
    y_prev = 0.0
    x_prev = float(x[0])

    for i in range(1, len(x)):
        y_prev = alpha * (y_prev + float(x[i]) - x_prev)
        y[i] = y_prev
        x_prev = float(x[i])

    return y


def moving_average(x: np.ndarray, win: int = 5) -> np.ndarray:
    x = x.astype(np.float32, copy=False)
    win = int(win)
    if win <= 1 or x.size == 0:
        return x.copy()

    k = np.ones(win, dtype=np.float32) / float(win)
    return np.convolve(x, k, mode="same").astype(np.float32)


def optional_filter_ecg(x_raw: np.ndarray) -> np.ndarray:
    x = x_raw.astype(np.float32, copy=False)
    if x.size == 0:
        return x.copy()

    x0 = x - np.mean(x, dtype=np.float32)
    hp = high_pass_1st(x0, alpha=HP_ALPHA)
    sm = moving_average(hp, win=MA_WIN)
    return sm.astype(np.float32)


def normalize_window_zscore(window: np.ndarray, eps: float = 1e-8) -> np.ndarray:
    """
    Matches NORMALIZE_EACH_WINDOW=True more closely than max-abs scaling.
    """
    x = window.astype(np.float32, copy=False)
    if x.size == 0:
        return x.copy()

    mean_v = float(np.mean(x))
    std_v = float(np.std(x))

    if std_v < eps:
        return (x - mean_v).astype(np.float32)

    return ((x - mean_v) / std_v).astype(np.float32)


def to_int16_scaled(x_norm: np.ndarray) -> np.ndarray:
    x = np.clip(x_norm, -1.0, 1.0).astype(np.float32, copy=False)
    return (x * 32767.0).astype(np.int16)


# =========================================================
# EI / MODEL HELPERS
# =========================================================
def pick_best_label(res: dict):
    cls = res.get("result", {}).get("classification", None)
    if not cls:
        return None, None
    best = max(cls, key=cls.get)
    return best, float(cls[best])


def resolve_model_path(path: str) -> str:
    if os.path.isfile(path):
        return path
    if os.path.isfile(path + ".eim"):
        return path + ".eim"
    if os.path.isdir(path):
        candidates = sorted(glob.glob(os.path.join(path, "*.eim")))
        if candidates:
            return candidates[0]
    candidates = sorted(glob.glob(path + "*.eim"))
    if candidates:
        return candidates[0]
    raise FileNotFoundError(
        f"Model file does not exist: {path}\n"
        f"Also tried: {path}.eim, directory scan, and prefix*.eim"
    )


def load_ei_json(path: str):
    with open(path, "r", encoding="utf-8") as f:
        obj = json.load(f)
    payload = obj.get("payload", {})
    interval_ms = float(payload.get("interval_ms", 0))
    values = payload.get("values", [])
    if not values:
        raise ValueError("payload.values is empty or missing")
    samples = np.asarray([v[0] for v in values], dtype=np.float32)
    return samples, interval_ms


def resample_to_fs(samples: np.ndarray, src_fs: float, dst_fs: float) -> np.ndarray:
    if src_fs <= 0 or dst_fs <= 0:
        return samples
    if abs(src_fs - dst_fs) < 1e-6:
        return samples

    n_src = len(samples)
    if n_src < 2:
        return samples

    duration_s = (n_src - 1) / src_fs
    n_dst = int(round(duration_s * dst_fs)) + 1
    if n_dst < 2:
        n_dst = 2

    t_src = np.linspace(0.0, duration_s, n_src, dtype=np.float64)
    t_dst = np.linspace(0.0, duration_s, n_dst, dtype=np.float64)
    out = np.interp(t_dst, t_src, samples.astype(np.float64)).astype(np.float32)
    return out


def make_windows(samples: np.ndarray, window_size: int, stride: int):
    total = len(samples)
    if total == 0:
        return

    start = 0
    while start < total:
        end = start + window_size
        if end <= total:
            yield start, samples[start:end]
        else:
            if INCOMPLETE_LAST_WINDOW == "drop":
                break

            need = end - total
            if PAD_MODE == "zero":
                pad = np.zeros(need, dtype=np.float32)
            else:
                pad = np.full(need, float(samples[-1]), dtype=np.float32)

            win = np.concatenate([samples[start:total], pad], axis=0)
            yield start, win
            break

        start += stride


def preprocess_window_for_model(window: np.ndarray) -> np.ndarray:
    """
    Recommended order:
    1) optional filter (OFF by default)
    2) per-window normalization (ON by default)
    """
    x = window.astype(np.float32, copy=False)

    if FILTER_ENABLE:
        x = optional_filter_ecg(x)

    if NORMALIZE_EACH_WINDOW:
        x = normalize_window_zscore(x)

    return x.astype(np.float32)


def infer_json_file(json_path: str) -> dict:
    """
    Runs EI classification on windows of the saved JSON file.
    Returns final decision + per-window details.
    """
    if EI_RUNNER is None or EI_MODEL_INFO is None:
        raise RuntimeError("EI runner not initialized")

    mp = EI_MODEL_INFO.get("model_parameters", {})
    model_window_size = int(mp.get("input_features_count", 0))
    model_fs = float(mp.get("frequency", 0))

    if model_window_size <= 0:
        raise RuntimeError("Could not read input_features_count from model_info")

    samples, interval_ms = load_ei_json(json_path)
    src_fs = (1000.0 / interval_ms) if interval_ms > 0 else 0.0

    effective_fs = TARGET_FS
    if model_fs > 0:
        effective_fs = model_fs

    effective_window_size = EXPECTED_TOTAL_SAMPLES
    if model_window_size > 0:
        effective_window_size = model_window_size

    if src_fs > 0 and effective_fs > 0 and abs(src_fs - effective_fs) > 1e-3:
        before = len(samples)
        samples = resample_to_fs(samples, src_fs, effective_fs)
        print(f"[ML] Resampled: {before} -> {len(samples)} samples ({src_fs:.3f} Hz -> {effective_fs:.3f} Hz)")

    if USE_OVERLAP and DEFAULT_STRIDE_MODE == "overlap_1s" and effective_fs > 0:
        stride = int(round(effective_fs * 1.0))
    else:
        stride = effective_window_size

    window_best = []
    vote = {}

    win_idx = 0
    for start, window in make_windows(samples, effective_window_size, stride):
        window_p = preprocess_window_for_model(window)

        if USE_INT16:
            window_i16 = to_int16_scaled(window_p)
            features = window_i16.astype(np.float32).tolist()
        else:
            features = window_p.tolist()

        res = EI_RUNNER.classify(features)
        label, conf = pick_best_label(res)

        if label is not None:
            vote[label] = vote.get(label, 0) + 1
            window_best.append({
                "win": win_idx,
                "start": int(start),
                "label": label,
                "conf": float(conf),
            })

        win_idx += 1

    final_label = None
    final_conf = None
    if vote:
        max_count = max(vote.values())
        top = [k for k, v in vote.items() if v == max_count]
        window_best_sorted = sorted(window_best, key=lambda x: x["conf"], reverse=True)

        if len(top) == 1:
            final_label = top[0]
            final_conf = max(
                (w["conf"] for w in window_best if w["label"] == final_label),
                default=window_best_sorted[0]["conf"],
            )
        else:
            final_label = window_best_sorted[0]["label"]
            final_conf = window_best_sorted[0]["conf"]

        window_best = window_best_sorted[:5]

    return {
        "final_label": final_label,
        "final_confidence": final_conf,
        "window_votes": vote,
        "window_best": window_best,
        "preprocess": {
            "filter_enabled": FILTER_ENABLE,
            "hp_alpha": HP_ALPHA,
            "ma_win": MA_WIN,
            "normalize_each_window": NORMALIZE_EACH_WINDOW,
            "use_int16": USE_INT16,
            "target_fs": TARGET_FS,
            "window_seconds": WINDOW_SECONDS,
            "expected_total_samples": EXPECTED_TOTAL_SAMPLES,
        }
    }


# =========================================================
# MQTT / PROTOCOL
# =========================================================
def parse_scan_start(payload: str) -> Optional[ScanState]:
    parts = payload.strip().split(",")
    if len(parts) != 4 or parts[0] != "SCAN_START":
        return None
    return ScanState(scan_id=int(parts[1]), fs_hz=int(parts[2]), total_samples=int(parts[3]))


def parse_scan_end(payload: str) -> Optional[int]:
    parts = payload.strip().split(",")
    if len(parts) != 2 or parts[0] != "SCAN_END":
        return None
    return int(parts[1])


def decode_binary_chunk(b: bytes) -> Tuple[int, int, List[int]]:
    if len(b) < 12:
        raise ValueError("Binary packet too short")
    if b[0] != MAGIC0 or b[1] != MAGIC1:
        raise ValueError("Bad magic")
    if b[2] != PKT_TYPE_DATA:
        raise ValueError(f"Unknown pkt type: {b[2]}")

    scan_id = struct.unpack_from("<I", b, 4)[0]
    start_index = struct.unpack_from("<H", b, 8)[0]
    sample_count = struct.unpack_from("<H", b, 10)[0]
    expected_len = 12 + sample_count * 2
    if len(b) < expected_len:
        raise ValueError(f"Truncated chunk: got={len(b)} expected={expected_len}")

    samples = list(struct.unpack_from(f"<{sample_count}H", b, 12))
    return scan_id, start_index, samples


def save_scan_to_json(scan: ScanState) -> str:
    ts_ms = int(time.time() * 1000)
    filename = os.path.join(OUT_DIR, f"{ts_ms}.json")

    values = [[0 if v is None else int(v)] for v in scan.samples]
    interval_ms = 1000.0 / float(scan.fs_hz) if scan.fs_hz > 0 else (1000.0 / TARGET_FS)

    doc = {
        "protected": {"ver": "v1", "alg": "none"},
        "signature": "00",
        "payload": {
            "device_name": DEVICE_NAME,
            "device_type": DEVICE_TYPE,
            "interval_ms": float(interval_ms),
            "sensors": [{"name": "ecg", "units": "adc"}],
            "values": values,
        },
    }

    with open(filename, "w", encoding="utf-8") as f:
        json.dump(doc, f)

    return filename


def publish_json(client: mqtt.Client, topic: str, obj: dict):
    client.publish(topic, json.dumps(obj), qos=0, retain=False)


def on_connect(client, userdata, flags, reason_code, properties):
    print(f"[MQTT] Connected rc={reason_code}")
    client.subscribe(MQTT_TOPIC_IN)
    print(f"[MQTT] Subscribed to {MQTT_TOPIC_IN}")


def on_message(client, userdata, msg):
    payload_bytes: bytes = msg.payload

    # Text packets
    try:
        payload_text = payload_bytes.decode("utf-8", errors="strict").strip()

        if payload_text.startswith("SCAN_START"):
            scan = parse_scan_start(payload_text)
            if scan:
                scans[scan.scan_id] = scan
                print(f"[SCAN] START id={scan.scan_id} fs={scan.fs_hz} total={scan.total_samples}")

                if scan.fs_hz != TARGET_FS:
                    print(f"[WARN] Incoming fs_hz={scan.fs_hz}, expected training fs={TARGET_FS}. Resampling may occur.")

                if scan.total_samples != EXPECTED_TOTAL_SAMPLES:
                    print(
                        f"[WARN] Incoming total_samples={scan.total_samples}, "
                        f"expected={EXPECTED_TOTAL_SAMPLES} for {WINDOW_SECONDS}s @ {TARGET_FS}Hz"
                    )
            else:
                print(f"[WARN] Bad SCAN_START: {payload_text}")
            return

        if payload_text.startswith("SCAN_END"):
            sid = parse_scan_end(payload_text)
            if sid is None:
                print(f"[WARN] Bad SCAN_END: {payload_text}")
                return

            scan = scans.get(sid)
            if not scan:
                print(f"[SCAN] END id={sid} but no state found")
                return

            elapsed = time.time() - scan.started_at
            print(
                f"[SCAN] END id={sid} received={scan.received_count}/{scan.total_samples} "
                f"missing={scan.missing()} elapsed={elapsed:.2f}s"
            )

            saved_path = save_scan_to_json(scan)
            print(f"[SCAN] Saved JSON: {saved_path}")

            publish_json(client, MQTT_TOPIC_SAVED, {
                "saved_path": saved_path,
                "scan_id": scan.scan_id,
                "received": scan.received_count,
                "total_samples": scan.total_samples,
                "timestamp_ms": int(time.time() * 1000),
            })

            try:
                infer = infer_json_file(saved_path)
                print(
                    f"[ML] {infer.get('final_label')} ({infer.get('final_confidence')}) "
                    f"votes={infer.get('window_votes')} preprocess={infer.get('preprocess')}"
                )

                publish_json(client, MQTT_TOPIC_RESULT, {
                    "saved_path": saved_path,
                    "scan_id": scan.scan_id,
                    "label": infer.get("final_label"),
                    "confidence": infer.get("final_confidence"),
                    "votes": infer.get("window_votes", {}),
                    "top_windows": infer.get("window_best", []),
                    "preprocess": infer.get("preprocess", {}),
                    "timestamp_ms": int(time.time() * 1000),
                })
                print(f"[ML] Published: {MQTT_TOPIC_RESULT}")

            except Exception as e:
                publish_json(client, MQTT_TOPIC_RESULT, {
                    "saved_path": saved_path,
                    "scan_id": scan.scan_id,
                    "error": str(e),
                    "timestamp_ms": int(time.time() * 1000),
                })
                print(f"[ML] ERROR: {e}")

            scans.pop(sid, None)
            return

        return

    except UnicodeDecodeError:
        pass

    # Binary chunk
    try:
        sid, start_idx, samples = decode_binary_chunk(payload_bytes)
    except Exception as e:
        print(f"[WARN] Failed to parse binary packet: {e} (len={len(payload_bytes)})")
        return

    scan = scans.get(sid)
    if not scan:
        print(f"[WARN] Chunk for scan_id={sid} but no SCAN_START yet. Ignoring.")
        return

    try:
        scan.put_chunk(start_idx, samples)
    except Exception as e:
        print(f"[WARN] Failed to store chunk sid={sid}: {e}")
        return

    if scan.received_count % 512 == 0 or scan.received_count == scan.total_samples:
        print(f"[SCAN] id={sid} progress {scan.received_count}/{scan.total_samples} (missing {scan.missing()})")


def main():
    global EI_RUNNER, EI_MODEL_INFO

    model_path = resolve_model_path(MODEL_PATH)
    EI_RUNNER = ImpulseRunner(model_path)
    EI_MODEL_INFO = EI_RUNNER.init()

    print("Model loaded OK")
    print("Model info:", EI_MODEL_INFO)
    print(
        f"[PREPROCESS] filter_enabled={FILTER_ENABLE} hp_alpha={HP_ALPHA} ma_win={MA_WIN} "
        f"normalize_each_window={NORMALIZE_EACH_WINDOW} use_int16={USE_INT16}"
    )
    print(
        f"[EXPECTED] target_fs={TARGET_FS} window_seconds={WINDOW_SECONDS} "
        f"expected_total_samples={EXPECTED_TOTAL_SAMPLES}"
    )

    client = mqtt.Client(mqtt.CallbackAPIVersion.VERSION2)
    client.username_pw_set(MQTT_USER, MQTT_PASS)
    client.on_connect = on_connect
    client.on_message = on_message

    print(f"[MQTT] Connecting to {MQTT_HOST}:{MQTT_PORT} topic={MQTT_TOPIC_IN}")
    client.connect(MQTT_HOST, MQTT_PORT, keepalive=60)

    try:
        client.loop_forever()
    finally:
        try:
            EI_RUNNER.stop()
        except Exception:
            pass


if __name__ == "__main__":
    try:
        main()
    except Exception as e:
        print("ERROR:", e, file=sys.stderr)
        sys.exit(1)