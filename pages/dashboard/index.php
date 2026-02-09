<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ECG Monitoring Admin Dashboard</title>
  <link rel="stylesheet" href="../../css/style.css" />
  <link rel="stylesheet" href="../../css/table.css" />
  <link rel="stylesheet" href="../../plugins/datatables/datatables.css" />
</head>

<body>
  <div class="app">
    <!-- SIDEBAR -->
    <aside class="sidebar">
      <div class="brand">
        <div class="brand-logo"><img src="../../assets/img/logo.png" alt="Cardihoop Logo" /></div>
        <div class="brand-text">
          <div class="brand-title">Cardihoop</div>
          <div class="brand-subtitle">Cloud-Based ECG Monitoring System</div>
        </div>
      </div>

      <nav class="nav">
        <a class="nav-item active" href="../dashboard/index.php">
          <span class="nav-icon">🏠</span>
          <span>Dashboard</span>
        </a>
        <a class="nav-item" href="../athletes/index.php">
          <span class="nav-icon">👤</span>
          <span>Athletes</span>
        </a>
        <a class="nav-item" href="../records/index.php">
          <span class="nav-icon">📄</span>
          <span>Records</span>
        </a>
      </nav>

      <div class="sidebar-footer">
        <div class="helper-card">
          <div class="helper-title">Reference</div>
          <div class="helper-text">
            Uses MIT-BIH Arrhythmia Database as a reference dataset for rhythm classification.
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
          <h1 class="page-title">Dashboard</h1>
          <div class="page-subtitle">Overview of today’s ECG activity and detected abnormalities.</div>
        </div>

        <div class="topbar-right">

          <div class="user-chip">
            <div class="user-avatar">A</div>
            <div class="user-meta">
              <div class="user-name">Admin</div>
              <div class="user-role">Administrator</div>
            </div>
          </div>
        </div>
      </header>

      <!-- STATS -->
      <section class="grid stats">
        <article class="card stat">
          <div class="card-head">
            <div>
              <div class="card-label">Registered Athletes</div>
              <div class="card-value" id="stat-athletes">128</div>
            </div>
            <div class="pill pill-blue">Total</div>
          </div>
          <div class="card-foot">
            <div class="muted">All athletes in the system</div>
          </div>
        </article>

        <article class="card stat">
          <div class="card-head">
            <div>
              <div class="card-label">ECG Scans Today</div>
              <div class="card-value" id="stat-scans-today">24</div>
            </div>
            <div class="pill pill-green">Today</div>
          </div>
          <div class="card-foot">
            <div class="muted">Based on today’s date</div>
          </div>
        </article>

        <article class="card stat">
          <div class="card-head">
            <div>
              <div class="card-label">Abnormal Readings</div>
              <div class="card-value danger" id="stat-abnormal">3</div>
            </div>
            <div class="pill pill-red">Alert</div>
          </div>
          <div class="card-foot">
            <div class="muted">Flagged for review</div>
          </div>
        </article>
      </section>

      <!-- CONTENT ROW -->
      <section class="grid content">
        <!-- Recent Abnormal -->
        <article class="card">
          <div class="card-title-row">
            <div>
              <div class="card-title">Recent Abnormal Readings</div>
              <div class="muted">Latest records flagged as abnormal</div>
            </div>
            <button class="btn btn-primary" type="button" onclick="window.location.href='../records/index.php'">View Records</button>
          </div>

          <div class="table-wrap">

            <table id="recentReadingsTable" class="display nowrap" style="width:100%">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Record ID</th>
                  <th>Athlete</th>
                  <th>Time</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
              </tbody>
            </table>

          </div>
        </article>

        <!-- Today Summary -->
        <article class="card">
          <div class="card-title-row">
            <div>
              <div class="card-title">Today’s Summary</div>
              <div class="muted">Quick glance health screening stats</div>
            </div>
            <button class="btn btn-ghost" type="button">Export</button>
          </div>

          <div class="summary">
            <div class="summary-item">
              <div class="summary-label">Normal</div>
              <div class="summary-value" id="stat-normal">21</div>
              <div class="progress">
                <div class="progress-bar" id="bar-normal" style="width: 88%"></div>
              </div>
              <div class="muted small">Most scans fall within normal rhythm</div>
            </div>

            <div class="summary-item">
              <div class="summary-label">Needs Review</div>
              <div class="summary-value danger" id="stat-review">3</div>
              <div class="progress">
                <div class="progress-bar danger" id="bar-review" style="width: 12%"></div>
              </div>
              <div class="muted small">Flagged for abnormal pattern</div>
            </div>

            <div class="divider"></div>

            <div class="mini-actions">
              <button class="btn btn-primary" type="button" onclick="window.location.href='../athletes/index.php'">Register Athlete</button>
              <button class="btn btn-ghost" type="button">New ECG Scan</button>
            </div>

            <div class="note">
              <div class="note-title">Classification Note</div>
              <div class="note-text">
                Rhythm labels here are for screening support only. Any “Abnormal” should be reviewed by a qualified professional.
              </div>
            </div>
          </div>
        </article>
      </section>

      <!-- FOOTER -->
      <footer class="footer">
        <div class="muted small">© 2026 ECG Monitoring System • Admin Dashboard (Light Theme)</div>
      </footer>
    </main>
  </div>

  <script src="../../plugins/js/jquery.min.js"></script>
  <script src="../../plugins/datatables/datatables.js"></script>

  <?php include("script/stats_script.php"); ?>
  <?php include("script/table_script.php"); ?>
  <?php include("script/summary_script.php"); ?>

</body>

</html>