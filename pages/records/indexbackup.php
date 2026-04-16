<!DOCTYPE html>
<html lang="en">

<?php
session_start();
if ($_SESSION['loggedin'] !== true) {
  header("Location: ../../index.php");
  exit();
}
?>

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Records • ECG Monitoring Admin</title>
  <link rel="stylesheet" href="../../css/style.css" />
  <link rel="stylesheet" href="../../css/table.css" />
  <link rel="stylesheet" href="../../css/modal.css" />
  <link rel="stylesheet" href="../../plugins/datatables/datatables.css" />
  <link rel="stylesheet" href="../../plugins/toastr/toastr.min.css">

  <style>
    .records-page .content {
      grid-template-columns: 1fr;
      align-items: start;
    }

    .records-page .record-detail {
      position: relative;
      top: auto;
      align-self: stretch;
      width: 100%;
    }

    .records-page .wavebox {
      width: 100%;
      height: 360px;
    }

    .records-page .wavebox.zoom-box {
      height: 380px;
    }

    .wavebox {
      width: 100%;
      height: 300px;
      border-radius: 12px;
      overflow: hidden;
      background: #fff;
      border: 1px solid rgba(0, 0, 0, 0.06);
    }

    .wavebox.zoom-box {
      height: 320px;
      margin-top: 12px;
    }

    .wave-controls {
      display: flex;
      gap: 10px;
      align-items: center;
      flex-wrap: wrap;
      margin-top: 12px;
      margin-bottom: 10px;
    }

    .wave-controls label {
      font-size: 13px;
      color: #475569;
      font-weight: 600;
    }

    .wave-controls select,
    .wave-controls button {
      padding: 8px 10px;
      border-radius: 8px;
      border: 1px solid #cbd5e1;
      background: #fff;
      cursor: pointer;
    }

    .wave-info-row {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      align-items: center;
      margin-top: 10px;
    }

    .row-selected {
      background: rgba(59, 130, 246, 0.08) !important;
    }

    .section-mini-title {
      font-size: 13px;
      font-weight: 700;
      color: #334155;
      margin-top: 12px;
      margin-bottom: 8px;
    }
  </style>
</head>

<script src="../../plugins/mqtt/mqttws31.js"></script>
<?php include("script/mqtt.php"); ?>

<body onload="client.connect(options);">
  <div class="app">
    <!-- SIDEBAR -->
    <aside class="sidebar">
      <div class="brand">
        <div class="brand-logo"><img src="../../assets/img/logo2.png" alt="Cardihoop Logo" /></div>
        <div class="brand-text">
          <div class="brand-title">Cardihoop</div>
          <div class="brand-subtitle">Cloud-Based ECG Monitoring System</div>
        </div>
      </div>

      <nav class="nav">
        <a class="nav-item" href="../dashboard/index.php">
          <span class="nav-icon">🏠</span>
          <span>Dashboard</span>
        </a>
        <a class="nav-item" href="../athletes/index.php">
          <span class="nav-icon">👤</span>
          <span>Athletes</span>
        </a>
        <a class="nav-item active" href="../records/index.php">
          <span class="nav-icon">📄</span>
          <span>Records</span>
        </a>
      </nav>

      <div class="sidebar-footer">
        <div class="helper-card">
          <div class="helper-title">Reminder</div>
          <div class="helper-text">
            “Abnormal” means the scan is flagged for review. Check electrode placement and motion artifacts.
          </div>
        </div>

        <!-- <button class="btn btn-danger" type="button">
          <span class="btn-icon">⏻</span>
          <span>Logout</span>
        </button> -->
      </div>
    </aside>

    <!-- MAIN -->
    <main class="main records-page">
      <!-- TOP BAR -->
      <header class="topbar">
        <div class="topbar-left">
          <button class="sidebar-toggle" type="button" id="sidebarToggle" aria-label="Toggle sidebar">
            ☰
          </button>

          <div>
            <h1 class="page-title">Dashboard</h1>
            <div class="page-subtitle">Overview of today’s ECG activity and detected abnormalities.</div>
          </div>
        </div>

        <div class="topbar-right">
          <button class="btn btn-ghost" type="button" id="scanBtn">New ECG Scan</button>

          <div class="topbar-right">
            <div class="user-dropdown" id="userDropdown">
              <button class="user-chip user-chip-btn" type="button" id="userDropdownBtn" aria-expanded="false">
                <div class="user-avatar">
                  <svg viewBox="0 0 24 24" class="avatar-icon">
                    <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8V22h19.2v-2.8c0-3.2-6.4-4.8-9.6-4.8z" />
                  </svg>
                </div>
                <div class="user-meta">
                  <div class="user-name">Admin</div>
                  <div class="user-role"><?php echo $_SESSION['username']; ?></div>
                </div>
                <div class="user-caret">▾</div>
              </button>

              <div class="user-menu" id="userMenu">
                <!-- <a href="profile.php" class="user-menu-item">Profile</a>
              <a href="settings.php" class="user-menu-item">Settings</a> -->
                <a href="../includes/logout.php" class="user-menu-item user-menu-item-danger">Logout</a>
              </div>
            </div>
          </div>
        </div>
      </header>

      <!-- CONTENT -->
      <section class="grid content records-layout">
        <!-- LEFT: RECORDS TABLE -->
        <article class="card">
          <div class="card-title-row">
            <div>
              <div class="card-title">ECG Scan Records</div>
              <div class="muted">Select a record to view waveform details.</div>
            </div>
          </div>

          <div class="table-wrap">
            <table id="scanRecordsTable" class="display nowrap" style="width:100%">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Record ID</th>
                  <th>Athlete ID</th>
                  <th>Athlete Name</th>
                  <th>Time</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </article>

        <!-- RIGHT: RECORD DETAIL -->
        <article class="card record-detail">
          <div class="wave-card">
            <div class="wave-head">
              <div class="wave-title">Waveform Preview</div>
            </div>

            <div id="selectedRecordBar" class="selected-record-bar">
              <div class="selected-record-left">
                <div class="selected-record-title">No record selected</div>
                <div class="selected-record-sub muted small">Click a row on the left table to preview waveform.</div>
              </div>

              <div class="selected-record-right">
                <span id="hrBadge" class="badge badge-gray">HR: -- bpm</span>
              </div>
            </div>

            <div class="wave-info-row">
              <span id="fsBadge" class="badge badge-gray">FS: -- Hz</span>
              <span id="durationBadge" class="badge badge-gray">Duration: -- s</span>
              <span id="samplesBadge" class="badge badge-gray">Samples: --</span>
            </div>

            <div class="section-mini-title">Overview (Full Record)</div>
            <div class="wavebox" aria-label="ECG waveform overview">
              <canvas id="waveCanvas"></canvas>
            </div>

            <div class="wave-controls">
              <label for="zoomWindowSelect">Zoom Window</label>
              <select id="zoomWindowSelect">
                <option value="10">10 seconds</option>
                <option value="5">5 seconds</option>
                <option value="15">15 seconds</option>
              </select>

              <label for="segmentSelect">Segment</label>
              <select id="segmentSelect">
                <option value="">Select segment</option>
              </select>

              <button type="button" id="prevSegmentBtn">◀ Prev</button>
              <button type="button" id="nextSegmentBtn">Next ▶</button>
            </div>

            <div class="section-mini-title">Zoom View</div>
            <div class="wavebox zoom-box" aria-label="ECG waveform zoom">
              <canvas id="zoomWaveCanvas"></canvas>
            </div>

            <div class="muted small" style="margin-top:10px;">
              Full record is shown above for rhythm overview. Use the zoom panel for clearer waveform inspection.
            </div>
          </div>

          <div class="note" style="margin-top:12px;">
            <div class="note-title">MIT-BIH Reference</div>
            <div class="note-text">
              Classification is guided by patterns learned from reference arrhythmia signals (MIT-BIH). For screening support only.
            </div>
          </div>

          <div class="note" style="margin-top:12px;">
            <div class="note-title">Notes</div>
            <div class="note-text">
              If irregular rhythm pattern detected, verify signal quality and electrode placement.
            </div>
          </div>

          <!-- <div class="detail-actions" style="margin-top:12px;">
            <button class="btn btn-ghost" type="button">Download Raw</button>
          </div> -->
        </article>
      </section>

      <footer class="footer">
        <div class="muted small">© 2026 ECG Monitoring System • Records</div>
      </footer>
    </main>
  </div>

  <?php include("modal.php"); ?>

  <script src="../../plugins/js/jquery.min.js"></script>
  <script src="../../plugins/datatables/datatables.js"></script>
  <script src="../../plugins/toastr/toastr.min.js"></script>

  <script>
    const userDropdown = document.getElementById('userDropdown');
    const userDropdownBtn = document.getElementById('userDropdownBtn');

    userDropdownBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      userDropdown.classList.toggle('open');

      const isOpen = userDropdown.classList.contains('open');
      userDropdownBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    document.addEventListener('click', function(e) {
      if (!userDropdown.contains(e.target)) {
        userDropdown.classList.remove('open');
        userDropdownBtn.setAttribute('aria-expanded', 'false');
      }
    });
  </script>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const app = document.querySelector('.app');
      const btn = document.getElementById('sidebarToggle');

      if (!app || !btn) return;

      const saved = localStorage.getItem('sidebarCollapsed');
      if (saved === '1') app.classList.add('is-collapsed');

      btn.addEventListener('click', function() {
        app.classList.toggle('is-collapsed');
        localStorage.setItem('sidebarCollapsed', app.classList.contains('is-collapsed') ? '1' : '0');
      });
    });
  </script>

  <?php include("script/modal_func.php"); ?>
  <?php include("script/table_script.php"); ?>

  <script>
    const ECG_OUT_DIR = '../../service/ecg_out';

    const ecgState = {
      recordId: null,
      rowMeta: null,
      samples: [],
      fs: null,
      durationSec: 0,
      zoomWindowSec: 10,
      segmentIndex: 0
    };

    function estimateHeartRateBpm(samples, fs) {
      if (!samples || samples.length < fs * 2) return {
        bpm: null,
        peaks: []
      };

      const winHp = Math.max(1, Math.floor(fs * 0.6));
      const hp = new Array(samples.length);
      let sum = 0;

      for (let i = 0; i < samples.length; i++) {
        sum += samples[i];
        if (i >= winHp) sum -= samples[i - winHp];
        const mean = sum / Math.min(i + 1, winHp);
        hp[i] = samples[i] - mean;
      }

      const winLp = Math.max(1, Math.floor(fs * 0.05));
      const lp = new Array(hp.length);
      sum = 0;

      for (let i = 0; i < hp.length; i++) {
        sum += hp[i];
        if (i >= winLp) sum -= hp[i - winLp];
        lp[i] = sum / Math.min(i + 1, winLp);
      }

      const energy = new Array(lp.length).fill(0);
      for (let i = 1; i < lp.length; i++) {
        const d = lp[i] - lp[i - 1];
        energy[i] = d * d;
      }

      const winEnv = Math.max(1, Math.floor(fs * 0.12));
      const env = new Array(energy.length);
      sum = 0;

      for (let i = 0; i < energy.length; i++) {
        sum += energy[i];
        if (i >= winEnv) sum -= energy[i - winEnv];
        env[i] = sum / Math.min(i + 1, winEnv);
      }

      let meanEnv = 0;
      for (const v of env) meanEnv += v;
      meanEnv /= env.length;

      let varEnv = 0;
      for (const v of env) varEnv += (v - meanEnv) * (v - meanEnv);
      varEnv /= env.length;

      const stdEnv = Math.sqrt(varEnv);
      const thresh = meanEnv + 2.0 * stdEnv;
      const refractory = Math.floor(fs * 0.28);

      const peaks = [];
      let lastPeak = -refractory;

      for (let i = 1; i < env.length - 1; i++) {
        if (i - lastPeak < refractory) continue;

        const isLocalMax = env[i] > env[i - 1] && env[i] >= env[i + 1];
        if (!isLocalMax) continue;
        if (env[i] < thresh) continue;

        const refineWin = Math.floor(fs * 0.06);
        let bestIdx = i;
        let bestVal = -Infinity;
        const start = Math.max(0, i - refineWin);
        const end = Math.min(lp.length - 1, i + refineWin);

        for (let j = start; j <= end; j++) {
          if (lp[j] > bestVal) {
            bestVal = lp[j];
            bestIdx = j;
          }
        }

        peaks.push(bestIdx);
        lastPeak = bestIdx;
      }

      if (peaks.length < 2) return {
        bpm: null,
        peaks
      };

      const rr = [];
      for (let i = 1; i < peaks.length; i++) {
        rr.push((peaks[i] - peaks[i - 1]) / fs);
      }

      rr.sort((a, b) => a - b);
      const mid = Math.floor(rr.length / 2);
      const rrMed = rr.length % 2 ? rr[mid] : (rr[mid - 1] + rr[mid]) / 2;

      if (!rrMed || rrMed <= 0) return {
        bpm: null,
        peaks
      };

      const bpm = 60 / rrMed;
      const bpmClamped = Math.round(Math.max(30, Math.min(220, bpm)));

      return {
        bpm: bpmClamped,
        peaks
      };
    }

    function drawEcgPaperGrid(ctx, w, h, pxPerMm, opts = {}) {
      const minor = 1 * pxPerMm;
      const major = 5 * pxPerMm;

      ctx.fillStyle = opts.bg ?? "#fff7f7";
      ctx.fillRect(0, 0, w, h);

      ctx.strokeStyle = opts.minorColor ?? "rgba(220, 38, 38, 0.12)";
      ctx.lineWidth = 1;

      for (let x = 0; x <= w; x += minor) {
        ctx.beginPath();
        ctx.moveTo(x + 0.5, 0);
        ctx.lineTo(x + 0.5, h);
        ctx.stroke();
      }

      for (let y = 0; y <= h; y += minor) {
        ctx.beginPath();
        ctx.moveTo(0, y + 0.5);
        ctx.lineTo(w, y + 0.5);
        ctx.stroke();
      }

      ctx.strokeStyle = opts.majorColor ?? "rgba(220, 38, 38, 0.28)";
      ctx.lineWidth = 1.2;

      for (let x = 0; x <= w; x += major) {
        ctx.beginPath();
        ctx.moveTo(x + 0.5, 0);
        ctx.lineTo(x + 0.5, h);
        ctx.stroke();
      }

      for (let y = 0; y <= h; y += major) {
        ctx.beginPath();
        ctx.moveTo(0, y + 0.5);
        ctx.lineTo(w, y + 0.5);
        ctx.stroke();
      }
    }

    function drawWaveformOnCanvas(canvasId, samples, opts = {}) {
      const canvas = document.getElementById(canvasId);
      if (!canvas) return;

      const ctx = canvas.getContext('2d');
      const cssW = canvas.clientWidth || 900;
      const box = canvas.parentElement;
      const cssH = (box ? box.clientHeight : canvas.clientHeight) || 300;
      const dpr = window.devicePixelRatio || 1;

      canvas.style.height = cssH + "px";
      canvas.width = Math.floor(cssW * dpr);
      canvas.height = Math.floor(cssH * dpr);
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

      const w = cssW;
      const h = cssH;

      const padTop = 10;
      const padBottom = 26;
      const padLeft = 46;
      const padRight = 10;

      const plotW = w - padLeft - padRight;
      const plotH = h - padTop - padBottom;

      const pxPerMm = opts.pxPerMm ?? 8;
      const mmPerSec = opts.mmPerSec ?? 25;
      const mmPerMv = opts.mmPerMv ?? 10;
      const adcToMv = opts.adcToMv ?? null;
      const fs = opts.fs_hz ?? null;
      const labelStartSec = opts.labelStartSec ?? 0;
      const compressToFit = opts.compressToFit ?? true;
      const titleText = opts.titleText ?? "";

      ctx.save();
      ctx.translate(padLeft, padTop);
      drawEcgPaperGrid(ctx, plotW, plotH, pxPerMm);
      ctx.restore();

      ctx.fillStyle = "rgba(120, 0, 0, 0.85)";
      ctx.font = "12px Arial";
      ctx.fillText(`${mmPerSec} mm/s`, padLeft + 8, padTop + 14);
      ctx.fillText(`${mmPerMv} mm/mV`, padLeft + 86, padTop + 14);

      if (titleText) {
        ctx.fillStyle = "rgba(20,20,20,0.75)";
        ctx.fillText(titleText, padLeft + 180, padTop + 14);
      }

      ctx.fillStyle = "rgba(60, 0, 0, 0.7)";
      ctx.fillText("Time (s)", padLeft + plotW - 56, h - 8);

      ctx.save();
      ctx.translate(14, padTop + plotH / 2);
      ctx.rotate(-Math.PI / 2);
      ctx.fillText(adcToMv ? "Amplitude (mV)" : "Amplitude (ADC units)", 0, 0);
      ctx.restore();

      if (!samples || samples.length < 2) {
        ctx.fillStyle = "rgba(15, 23, 42, 0.6)";
        ctx.fillText("No waveform data", padLeft + 10, padTop + 18);
        return;
      }

      const maxPoints = opts.maxPoints ?? 2500;
      let data = samples;

      if (samples.length > maxPoints) {
        const step = Math.ceil(samples.length / maxPoints);
        const ds = [];
        for (let i = 0; i < samples.length; i += step) ds.push(samples[i]);
        data = ds;
      }

      const pxPerSec = mmPerSec * pxPerMm;
      const pxPerSample = (fs && fs > 0) ? (pxPerSec / fs) : (plotW / Math.max(1, data.length - 1));
      const naturalW = (fs && fs > 0) ? ((data.length - 1) * pxPerSample) : plotW;
      const xScale = (compressToFit && naturalW > plotW) ? (plotW / naturalW) : 1;

      const pxPerMv = mmPerMv * pxPerMm;
      const yVals = data.map(v => {
        const n = Number(v);
        return adcToMv ? adcToMv(n) : n;
      });

      const midY = padTop + plotH / 2;
      let mapY;

      if (adcToMv) {
        mapY = (mv) => midY - (mv * pxPerMv);
      } else {
        let min = Infinity;
        let max = -Infinity;

        for (const v of yVals) {
          if (v < min) min = v;
          if (v > max) max = v;
        }

        if (max === min) max = min + 1;

        const margin = 0.12;
        mapY = (v) => {
          const t = (v - min) / (max - min);
          const y = padTop + (1 - t) * plotH;
          return padTop + plotH * margin + (y - padTop) * (1 - 2 * margin);
        };
      }

      // thinner ECG trace here
      ctx.strokeStyle = "rgba(16, 24, 40, 0.95)";
      ctx.lineWidth = opts.traceWidth ?? 1.1;
      ctx.beginPath();

      for (let i = 0; i < yVals.length; i++) {
        const x = padLeft + (i * pxPerSample * xScale);
        const y = mapY(yVals[i]);
        if (i === 0) ctx.moveTo(x, y);
        else ctx.lineTo(x, y);
      }
      ctx.stroke();

      if (fs && fs > 0) {
        const durationSec = samples.length / fs;
        const maxSec = Math.floor(durationSec);

        let labelStepSec = 1;
        if (maxSec > 40) labelStepSec = 10;
        else if (maxSec > 20) labelStepSec = 5;

        ctx.fillStyle = "rgba(120, 0, 0, 0.55)";
        ctx.font = "11px Arial";

        for (let s = 0; s <= maxSec; s += labelStepSec) {
          const x = padLeft + (s * pxPerSec * xScale);
          if (x <= padLeft + plotW + 1) {
            ctx.fillText(String(labelStartSec + s), x - 5, padTop + plotH + 16);
          }
        }
      }
    }

    function updateMetaBadges(samples, fs) {
      const fsBadge = document.getElementById('fsBadge');
      const durationBadge = document.getElementById('durationBadge');
      const samplesBadge = document.getElementById('samplesBadge');

      if (fsBadge) {
        fsBadge.textContent = `FS: ${fs ? Number(fs).toFixed(3).replace(/\.?0+$/, '') : '--'} Hz`;
      }

      if (durationBadge) {
        durationBadge.textContent = `Duration: ${fs ? (samples.length / fs).toFixed(2).replace(/\.?0+$/, '') : '--'} s`;
      }

      if (samplesBadge) {
        samplesBadge.textContent = `Samples: ${samples.length || '--'}`;
      }
    }

    function updateSelectedBar(recordId, rowMeta) {
      const barTitle = document.querySelector('#selectedRecordBar .selected-record-title');
      const barSub = document.querySelector('#selectedRecordBar .selected-record-sub');

      if (barTitle) {
        const athlete = rowMeta?.AthleteName ?? 'Unknown athlete';
        const ts = rowMeta?.Timestamp ?? rowMeta?.Time ?? '';
        const status = rowMeta?.Status ?? '';
        barTitle.textContent = `${athlete} • ${status}`;

        if (barSub) {
          barSub.textContent = `RecordID: ${recordId}${ts ? ` • Time: ${ts}` : ''}`;
        }
      }
    }

    function getFsFromJson(json) {
      const sampleRateHz = Number(json?.payload?.sample_rate_hz);
      const intervalUs = Number(json?.payload?.interval_us);
      const intervalMs = Number(json?.payload?.interval_ms);

      if (Number.isFinite(sampleRateHz) && sampleRateHz > 0) return sampleRateHz;
      if (Number.isFinite(intervalUs) && intervalUs > 0) return 1000000 / intervalUs;
      if (Number.isFinite(intervalMs) && intervalMs > 0) return 1000 / intervalMs;
      return null;
    }

    function populateSegmentSelect() {
      const segmentSelect = document.getElementById('segmentSelect');
      if (!segmentSelect) return;

      segmentSelect.innerHTML = '';

      if (!ecgState.samples.length || !ecgState.fs) {
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = 'Select segment';
        segmentSelect.appendChild(opt);
        return;
      }

      const segmentSamples = Math.max(1, Math.floor(ecgState.zoomWindowSec * ecgState.fs));
      const totalSegments = Math.ceil(ecgState.samples.length / segmentSamples);

      for (let i = 0; i < totalSegments; i++) {
        const startSec = i * ecgState.zoomWindowSec;
        const endSec = Math.min((i + 1) * ecgState.zoomWindowSec, ecgState.durationSec);

        const opt = document.createElement('option');
        opt.value = String(i);
        opt.textContent = `${startSec}s - ${endSec}s`;
        segmentSelect.appendChild(opt);
      }

      if (ecgState.segmentIndex >= totalSegments) ecgState.segmentIndex = 0;
      segmentSelect.value = String(ecgState.segmentIndex);
    }

    function renderOverview() {
      drawWaveformOnCanvas('waveCanvas', ecgState.samples, {
        maxPoints: 4000,
        fs_hz: ecgState.fs,
        pxPerMm: 8,
        mmPerSec: 25,
        compressToFit: true,
        titleText: 'Full record overview',
        traceWidth: 1.0
      });
    }

    function renderZoomSegment() {
      if (!ecgState.samples.length || !ecgState.fs) {
        drawWaveformOnCanvas('zoomWaveCanvas', [], {});
        return;
      }

      const segmentSamples = Math.max(1, Math.floor(ecgState.zoomWindowSec * ecgState.fs));
      const startIdx = ecgState.segmentIndex * segmentSamples;
      const endIdx = Math.min(startIdx + segmentSamples, ecgState.samples.length);

      const segment = ecgState.samples.slice(startIdx, endIdx);
      const labelStartSec = Math.floor(startIdx / ecgState.fs);

      drawWaveformOnCanvas('zoomWaveCanvas', segment, {
        maxPoints: segment.length,
        fs_hz: ecgState.fs,
        pxPerMm: 8,
        mmPerSec: 25,
        compressToFit: true,
        labelStartSec: labelStartSec,
        titleText: `Zoomed segment (${labelStartSec}s onward)`,
        traceWidth: 1.0
      });

      const segmentSelect = document.getElementById('segmentSelect');
      if (segmentSelect) segmentSelect.value = String(ecgState.segmentIndex);
    }

    function refreshZoomUi() {
      populateSegmentSelect();
      renderZoomSegment();
    }

    async function loadAndPlotRecord(recordId, rowMeta = null) {
      const url = `${ECG_OUT_DIR}/${encodeURIComponent(recordId)}.json`;

      drawWaveformOnCanvas('waveCanvas', [0, 1], {
        maxPoints: 2,
        traceWidth: 1.0
      });
      drawWaveformOnCanvas('zoomWaveCanvas', [0, 1], {
        maxPoints: 2,
        traceWidth: 1.0
      });

      const res = await fetch(url, {
        cache: 'no-store'
      });
      if (!res.ok) throw new Error(`File not found: ${url}`);

      const json = await res.json();
      const fs = getFsFromJson(json);

      const values = json?.payload?.values;
      if (!Array.isArray(values)) throw new Error('Invalid JSON: payload.values missing');

      const samples = values
        .map(v => Array.isArray(v) ? Number(v[0]) : Number(v))
        .filter(n => Number.isFinite(n));

      if (samples.length < 2) throw new Error('No samples in file');

      console.log('Record debug:', {
        recordId: recordId,
        sampleRateHz: json?.payload?.sample_rate_hz,
        intervalUs: json?.payload?.interval_us,
        intervalMs: json?.payload?.interval_ms,
        sampleCount: samples.length,
        fs: fs,
        durationSec: fs ? (samples.length / fs) : null
      });

      ecgState.recordId = recordId;
      ecgState.rowMeta = rowMeta;
      ecgState.samples = samples;
      ecgState.fs = fs;
      ecgState.durationSec = fs ? (samples.length / fs) : 0;
      ecgState.segmentIndex = 0;

      updateSelectedBar(recordId, rowMeta);
      updateMetaBadges(samples, fs);

      renderOverview();
      refreshZoomUi();

      let hrText = 'HR: -- bpm';
      const hrBadge = document.getElementById('hrBadge');

      if (fs) {
        const hr = estimateHeartRateBpm(samples, fs);
        if (hr?.bpm) {
          hrText = `HR: ${hr.bpm} bpm`;
          if (hrBadge) {
            hrBadge.classList.remove('badge-gray', 'badge-green', 'badge-red');
            hrBadge.textContent = hrText;
            hrBadge.classList.add(hr.bpm > 100 ? 'badge-red' : 'badge-green');
          }
        } else {
          if (hrBadge) {
            hrBadge.classList.remove('badge-green', 'badge-red');
            hrBadge.classList.add('badge-gray');
            hrBadge.textContent = hrText;
          }
        }
      } else {
        if (hrBadge) {
          hrBadge.classList.remove('badge-green', 'badge-red');
          hrBadge.classList.add('badge-gray');
          hrBadge.textContent = hrText;
        }
      }
    }

    function bindZoomControls() {
      const zoomWindowSelect = document.getElementById('zoomWindowSelect');
      const segmentSelect = document.getElementById('segmentSelect');
      const prevSegmentBtn = document.getElementById('prevSegmentBtn');
      const nextSegmentBtn = document.getElementById('nextSegmentBtn');

      if (zoomWindowSelect) {
        zoomWindowSelect.addEventListener('change', function() {
          ecgState.zoomWindowSec = Number(this.value) || 10;
          ecgState.segmentIndex = 0;
          refreshZoomUi();
        });
      }

      if (segmentSelect) {
        segmentSelect.addEventListener('change', function() {
          ecgState.segmentIndex = Number(this.value) || 0;
          renderZoomSegment();
        });
      }

      if (prevSegmentBtn) {
        prevSegmentBtn.addEventListener('click', function() {
          if (!ecgState.samples.length || !ecgState.fs) return;
          ecgState.segmentIndex = Math.max(0, ecgState.segmentIndex - 1);
          renderZoomSegment();
        });
      }

      if (nextSegmentBtn) {
        nextSegmentBtn.addEventListener('click', function() {
          if (!ecgState.samples.length || !ecgState.fs) return;

          const segmentSamples = Math.max(1, Math.floor(ecgState.zoomWindowSec * ecgState.fs));
          const totalSegments = Math.ceil(ecgState.samples.length / segmentSamples);

          ecgState.segmentIndex = Math.min(totalSegments - 1, ecgState.segmentIndex + 1);
          renderZoomSegment();
        });
      }
    }

    $(function() {
      bindZoomControls();

      $('#scanRecordsTable tbody').on('click', 'tr', async function() {
        const table = window.scanRecordsTable;
        if (!table) return;

        const row = table.row(this);
        const data = row.data();
        if (!data) return;

        $('#scanRecordsTable tbody tr').removeClass('row-selected');
        $(this).addClass('row-selected');

        const recordId = data.RecordID;
        if (!recordId) return;

        try {
          await loadAndPlotRecord(recordId, data);
        } catch (e) {
          console.error(e);
          toastr.error(e.message || 'Failed to load waveform');

          drawWaveformOnCanvas('waveCanvas', [], {
            traceWidth: 1.0
          });
          drawWaveformOnCanvas('zoomWaveCanvas', [], {
            traceWidth: 1.0
          });

          const hrBadge = document.getElementById('hrBadge');
          if (hrBadge) {
            hrBadge.textContent = 'HR: -- bpm';
            hrBadge.classList.remove('badge-green', 'badge-red');
            hrBadge.classList.add('badge-gray');
          }

          updateMetaBadges([], null);
        }
      });

      window.addEventListener('resize', function() {
        if (ecgState.samples.length) {
          renderOverview();
          renderZoomSegment();
        }
      });
    });
  </script>

</body>

</html>