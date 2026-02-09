#!/usr/bin/env python3
"""
ECG MQTT receiver (binary chunks) for the ESP32 sketch.

- Subscribes to ecg/data
- Handles:
  - "SCAN_START,<scan_id>,<fs_hz>,<total_samples>" (text)
  - Binary packets starting with b"EC" and type 0x01
  - "SCAN_END,<scan_id>" (text)
- Reassembles the scan into a numpy array (optional) or python list
- Saves to CSV on SCAN_END

Install:
  pip install paho-mqtt

Run:
  python ecg_receiver.py

Optional env vars:
  MQTT_HOST, MQTT_PORT, MQTT_USER, MQTT_PASS, MQTT_TOPIC
"""

import os
import csv
import struct
import time
from dataclasses import dataclass, field
from typing import Dict, Optional, List

import paho.mqtt.client as mqtt


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
            raise ValueError(f"Chunk out of range: start={start_index}, len={len(chunk)}, total={self.total_samples}")

        # Count only newly filled samples
        for i, v in enumerate(chunk):
            idx = start_index + i
            if self.samples[idx] is None:
                self.received_count += 1
            self.samples[idx] = v

    def is_complete(self) -> bool:
        return self.received_count >= self.total_samples

    def missing(self) -> int:
        return self.total_samples - self.received_count


# ----------------- Config -----------------
MQTT_HOST = os.getenv("MQTT_HOST", "13.214.212.87")
MQTT_PORT = int(os.getenv("MQTT_PORT", "1883"))
MQTT_USER = os.getenv("MQTT_USER", "mqtt")
MQTT_PASS = os.getenv("MQTT_PASS", "ICPHmqtt!")
MQTT_TOPIC = os.getenv("MQTT_TOPIC", "ecg/data")

OUT_DIR = os.getenv("OUT_DIR", "ecg_out")
os.makedirs(OUT_DIR, exist_ok=True)

# Protocol constants (must match ESP32)
MAGIC0 = ord("E")  # 0x45
MAGIC1 = ord("C")  # 0x43
PKT_TYPE_DATA = 0x01

# Active scans by scan_id
scans: Dict[int, ScanState] = {}


def parse_scan_start(payload: str) -> Optional[ScanState]:
    # Expected: SCAN_START,<scan_id>,<fs_hz>,<total_samples>
    parts = payload.strip().split(",")
    if len(parts) != 4:
        return None
    if parts[0] != "SCAN_START":
        return None
    scan_id = int(parts[1])
    fs_hz = int(parts[2])
    total = int(parts[3])
    return ScanState(scan_id=scan_id, fs_hz=fs_hz, total_samples=total)


def parse_scan_end(payload: str) -> Optional[int]:
    # Expected: SCAN_END,<scan_id>
    parts = payload.strip().split(",")
    if len(parts) != 2:
        return None
    if parts[0] != "SCAN_END":
        return None
    return int(parts[1])


def decode_binary_chunk(b: bytes):
    """
    Binary layout (little-endian):
      [0]   'E'
      [1]   'C'
      [2]   type (0x01)
      [3]   reserved
      [4:8] scan_id (uint32 LE)
      [8:10] start_index (uint16 LE)
      [10:12] sample_count (uint16 LE)
      [12:] samples (uint16 LE) * sample_count
    """
    if len(b) < 12:
        raise ValueError("Binary packet too short")

    if b[0] != MAGIC0 or b[1] != MAGIC1:
        raise ValueError("Bad magic")

    pkt_type = b[2]
    if pkt_type != PKT_TYPE_DATA:
        raise ValueError(f"Unknown pkt type: {pkt_type}")

    scan_id = struct.unpack_from("<I", b, 4)[0]
    start_index = struct.unpack_from("<H", b, 8)[0]
    sample_count = struct.unpack_from("<H", b, 10)[0]

    expected_len = 12 + sample_count * 2
    if len(b) != expected_len:
        # Some clients/brokers may deliver exact; if not exact, still try best-effort
        if len(b) < expected_len:
            raise ValueError(f"Truncated chunk: got={len(b)} expected={expected_len}")
        # If longer, ignore trailing bytes.

    samples = list(struct.unpack_from(f"<{sample_count}H", b, 12))
    return scan_id, start_index, samples


def save_scan_to_csv(scan: ScanState) -> str:
    ts = time.strftime("%Y%m%d_%H%M%S")
    filename = os.path.join(OUT_DIR, f"ecg_scan_{scan.scan_id}_{ts}.csv")

    # Replace None with empty or 0; better to keep empty for debugging
    row = [("" if v is None else v) for v in scan.samples]

    with open(filename, "w", newline="") as f:
        w = csv.writer(f)
        w.writerow(["scan_id", scan.scan_id, "fs_hz", scan.fs_hz, "total_samples", scan.total_samples])
        w.writerow(row)

    return filename


def on_connect(client, userdata, flags, rc):
    print(f"[MQTT] Connected rc={rc}")
    client.subscribe(MQTT_TOPIC)
    print(f"[MQTT] Subscribed to {MQTT_TOPIC}")


def on_message(client, userdata, msg):
    payload_bytes: bytes = msg.payload

    # Try text first (SCAN_START/SCAN_END)
    try:
        payload_text = payload_bytes.decode("utf-8", errors="strict")
        # If it decodes cleanly, check commands
        if payload_text.startswith("SCAN_START"):
            scan = parse_scan_start(payload_text)
            if scan:
                scans[scan.scan_id] = scan
                print(f"[SCAN] START id={scan.scan_id} fs={scan.fs_hz} total={scan.total_samples}")
            return

        if payload_text.startswith("SCAN_END"):
            sid = parse_scan_end(payload_text)
            if sid is None:
                return
            scan = scans.get(sid)
            if not scan:
                print(f"[SCAN] END id={sid} but no state found")
                return

            elapsed = time.time() - scan.started_at
            print(f"[SCAN] END id={sid} received={scan.received_count}/{scan.total_samples} missing={scan.missing()} elapsed={elapsed:.2f}s")

            out = save_scan_to_csv(scan)
            print(f"[SCAN] Saved CSV: {out}")

            # Cleanup
            scans.pop(sid, None)
            return

        # Other text: ignore or log
        return

    except UnicodeDecodeError:
        # Not valid UTF-8 => assume binary
        pass

    # Binary chunk
    try:
        sid, start_idx, samples = decode_binary_chunk(payload_bytes)
    except Exception as e:
        print(f"[WARN] Failed to parse binary packet: {e} (len={len(payload_bytes)})")
        return

    scan = scans.get(sid)
    if not scan:
        # If chunks arrive before SCAN_START, you can create a placeholder or ignore.
        print(f"[WARN] Chunk for scan_id={sid} but no SCAN_START yet. Ignoring.")
        return

    try:
        scan.put_chunk(start_idx, samples)
    except Exception as e:
        print(f"[WARN] Failed to store chunk sid={sid}: {e}")
        return

    # Print progress occasionally
    if scan.received_count % 500 == 0 or scan.received_count == scan.total_samples:
        print(f"[SCAN] id={sid} progress {scan.received_count}/{scan.total_samples} (missing {scan.missing()})")


def main():
    client = mqtt.Client()
    client.username_pw_set(MQTT_USER, MQTT_PASS)

    client.on_connect = on_connect
    client.on_message = on_message

    print(f"[MQTT] Connecting to {MQTT_HOST}:{MQTT_PORT} topic={MQTT_TOPIC}")
    client.connect(MQTT_HOST, MQTT_PORT, keepalive=60)

    client.loop_forever()


if __name__ == "__main__":
    main()
