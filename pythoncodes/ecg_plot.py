import argparse
import threading
import time
from collections import deque

import serial
import matplotlib.pyplot as plt
from matplotlib.animation import FuncAnimation
from matplotlib.ticker import MultipleLocator


def serial_thread(port, baud, timeout, q, stop):
    try:
        ser = serial.Serial(port, baud, timeout=timeout)
    except Exception as e:
        q.append(("__ERR__", f"Open failed: {e}"))
        return

    time.sleep(1.0)
    try:
        ser.reset_input_buffer()
    except Exception:
        pass

    q.append(("__INFO__", f"Opened {port} @ {baud}"))

    while not stop.is_set():
        try:
            b = ser.readline()
            if not b:
                continue
            s = b.decode(errors="ignore").strip()
            if not s:
                continue
            q.append(("__RAW__", s))
        except Exception as e:
            q.append(("__ERR__", f"Read error: {e}"))
            break

    try:
        ser.close()
    except Exception:
        pass


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--port", required=True)
    ap.add_argument("--baud", type=int, default=115200)
    ap.add_argument("--timeout", type=float, default=0.2)
    ap.add_argument("--fs", type=float, default=250.0, help="Your sampling rate (Hz)")
    ap.add_argument("--seconds", type=float, default=5.0, help="Seconds visible")
    ap.add_argument("--plot_fs", type=float, default=80.0, help="Display rate (Hz). Lower = cleaner line")
    args = ap.parse_args()

    q = deque(maxlen=200000)
    stop = threading.Event()
    t = threading.Thread(target=serial_thread, args=(args.port, args.baud, args.timeout, q, stop), daemon=True)
    t.start()

    n = int(args.fs * args.seconds)
    ybuf = deque([None] * n, maxlen=n)

    fig, ax = plt.subplots()

    # Thin line (still may look dense without downsampling)
    line, = ax.plot(
        [], [],
        color="#0b5cff",
        linewidth=1.0,
        antialiased=True,
        solid_capstyle="round",
        solid_joinstyle="round",
    )

    ax.set_title("AD8232 Live ECG")
    ax.set_xlabel("Time (s)")
    ax.set_ylabel("ADC")
    ax.set_xlim(-args.seconds, 0)
    ax.set_facecolor("white")

    # ECG-style grid
    ax.xaxis.set_major_locator(MultipleLocator(0.2))
    ax.xaxis.set_minor_locator(MultipleLocator(0.04))
    ax.yaxis.set_major_locator(MultipleLocator(50))
    ax.yaxis.set_minor_locator(MultipleLocator(10))
    ax.grid(which="major", linewidth=0.8, alpha=0.6)
    ax.grid(which="minor", linewidth=0.3, alpha=0.35)

    # Reduce x tick label clutter
    ax.tick_params(axis="x", labelrotation=0)
    ax.xaxis.set_major_formatter(lambda x, pos: f"{x:.1f}")

    status = ax.text(0.02, 0.98, "Starting...", transform=ax.transAxes, va="top", ha="left")

    # Debug counters
    lines_rx = 0
    parsed = 0
    skipped = 0
    last_val = None
    last_info = ""
    last_err = ""
    leads_off = False

    # Compute downsample step for display
    step = max(1, int(round(args.fs / max(1e-6, args.plot_fs))))

    def update(_):
        nonlocal lines_rx, parsed, skipped, last_val, last_info, last_err, leads_off

        while q:
            tag, payload = q.popleft()

            if tag == "__INFO__":
                last_info = payload
                continue
            if tag == "__ERR__":
                last_err = payload
                continue
            if tag != "__RAW__":
                continue

            s = payload
            lines_rx += 1

            if s == "!":
                ybuf.append(None)
                parsed += 1
                last_val = None
                leads_off = True
                continue

            try:
                v = float(s)
                ybuf.append(v)
                parsed += 1
                last_val = v
                leads_off = False
            except ValueError:
                skipped += 1

        y_full = list(ybuf)

        # ---- Downsample for display ----
        # Keep None breaks (leads-off) by sampling them too.
        y = y_full[::step]
        x = [(-len(y) + 1 + i) * (step / args.fs) for i in range(len(y))]  # time step matches downsample

        line.set_data(x, y)

        # Auto-scale using last ~1 second of FULL data (better scaling)
        tail_n = max(10, int(args.fs * 1.0))
        tail = [v for v in y_full[-tail_n:] if v is not None]
        if tail:
            lo = min(tail)
            hi = max(tail)
            span = max(1e-6, hi - lo)
            pad = 0.35 * span
            ax.set_ylim(lo - pad, hi + pad)
        else:
            ax.set_ylim(0, 4095)

        text_lines = []
        if last_err:
            text_lines.append(f"ERROR: {last_err}")
        if last_info:
            text_lines.append(last_info)
        text_lines.append("LEADS OFF" if leads_off else "LEADS OK")
        text_lines.append(f"RX={lines_rx} parsed={parsed} skipped={skipped}")
        text_lines.append(f"Last={last_val}")
        text_lines.append(f"Plot step={step} (plot_fs≈{args.fs/step:.1f} Hz)")
        status.set_text("\n".join(text_lines))

        return (line, status)

    ani = FuncAnimation(fig, update, interval=40, blit=False, cache_frame_data=False)

    try:
        plt.show()
    finally:
        stop.set()


if __name__ == "__main__":
    main()
