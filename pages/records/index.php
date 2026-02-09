<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Records • ECG Monitoring Admin</title>
  <link rel="stylesheet" href="../../css/style.css" />
  <link rel="stylesheet" href="../../css/table.css" />
  <link rel="stylesheet" href="../../css/modal.css" />
  <link rel="stylesheet" href="../../plugins/datatables/datatables.css" />
  <link rel="stylesheet" href="../../plugins/toastr/toastr.min.css">
</head>

<script src="../../plugins/mqtt/mqttws31.js"></script>
<?php include("script/mqtt.php"); ?>

<body onload="client.connect(options);">
  <div class="app">
    <!-- SIDEBAR -->
    <aside class="sidebar">
      <div class="brand">
        <div class="brand-logo">ECG</div>
        <div class="brand-text">
          <div class="brand-title">ECG Admin</div>
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


        <button class="btn btn-danger" type="button">
          <span class="btn-icon">⏻</span>
          <span>Logout</span>
        </button>
      </div>
    </aside>

    <!-- MAIN -->
    <main class="main">

      <!-- TOP BAR -->
      <header class="topbar">
        <div class="topbar-left">
          <h1 class="page-title">Records</h1>
          <div class="page-subtitle">View scanned ECG data and classification results.</div>
        </div>

        <div class="topbar-right">

          <button class="btn btn-ghost" type="button" id="scanBtn">New ECG Scan</button>
        </div>
      </header>

      <!-- CONTENT -->
      <section class="grid content">
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
              <tbody>
              </tbody>
            </table>

          </div>

        </article>

        <!-- RIGHT: RECORD DETAIL -->
        <article class="card record-detail">
          <div class="card-title-row">
            <div>
              <div class="card-title">Record Detail</div>
              <div class="muted">Waveform preview & classification info</div>
            </div>
            <button class="btn btn-primary" type="button">Open Full Waveform</button>
          </div>

          <div class="detail-grid">
            <div class="info">
              <div class="info-label">Record ID</div>
              <div class="info-value mono" id="dRecord">REC-10421</div>
            </div>
            <div class="info">
              <div class="info-label">Athlete</div>
              <div class="info-value" id="dAthlete">D. Santos</div>
            </div>
            <div class="info">
              <div class="info-label">Date/Time</div>
              <div class="info-value" id="dDateTime">Jan 22, 2026 • 09:42 AM</div>
            </div>
          </div>

          <div class="divider"></div>

          <div class="wave-card">
            <div class="wave-head">
              <div class="wave-title">Waveform Preview</div>
              <div id="dStatusBadge"><span class="badge badge-red">Abnormal</span></div>
            </div>

            <!-- Placeholder waveform area -->
            <div class="wavebox" aria-label="ECG waveform preview">
              <div class="wavebox-inner">
                <div class="wave-legend">
                  <span class="dot"></span>
                  <span class="muted small">Chart Preview here</span>
                </div>
                <div class="wave-grid">
                  <div class="wave-line"></div>
                </div>
              </div>
            </div>

            <div class="muted small" style="margin-top:10px;">
              Tip: later, replace the placeholder with a canvas/chart (Chart.js / Plotly) and plot your filtered ECG samples.
            </div>
          </div>

          <div class="note" style="margin-top:12px;">
            <div class="note-title">MIT-BIH Reference</div>
            <div class="note-text">
              Classification is guided by patterns learned from reference arrhythmia signals (MIT-BIH). For screening support only.
            </div>
          </div>

          <div class="detail-notes">
            <div class="card-title">Notes</div>
            <div class="muted" id="dNotes">
              Irregular rhythm pattern detected. Verify signal quality.
            </div>
          </div>

          <div class="detail-actions">
            <button class="btn btn-ghost" type="button">Download Raw</button>
          </div>
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

  <?php include("script/modal_func.php"); ?>
  <?php include("script/table_script.php"); ?>

</body>

</html>