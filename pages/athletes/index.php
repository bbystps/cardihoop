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
  <title>Athletes • ECG Monitoring Admin</title>
  <link rel="stylesheet" href="../../css/style.css" />
  <link rel="stylesheet" href="../../css/table.css" />
  <link rel="stylesheet" href="../../css/modal.css" />
  <link rel="stylesheet" href="../../plugins/datatables/datatables.css" />
</head>

<body>
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
        <a class="nav-item active" href="../athletes/index.php">
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
          <div class="helper-title">Tip</div>
          <div class="helper-text">
            Keep athlete profiles complete (team, birthdate, emergency contact) for better screening context.
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
          <h1 class="page-title">Athletes</h1>
          <div class="page-subtitle">Manage athlete profiles and registrations.</div>
        </div>

        <div class="topbar-right">
          <!-- <div class="search">
            <span class="search-icon">🔎</span>
            <input id="athleteSearch" type="text" placeholder="Search name, ID, team..." />
          </div> -->

          <button class="btn btn-primary" type="button" id="openRegister">
            <span class="btn-icon">＋</span>
            <span>Register Athlete</span>
          </button>
        </div>
      </header>

      <!-- PAGE CONTENT -->
      <section class="grid content">
        <!-- LEFT: ATHLETES TABLE -->
        <article class="card">
          <div class="card-title-row">
            <div>
              <div class="card-title">Athlete List</div>
              <div class="muted">Click a row to view profile details.</div>
            </div>
          </div>

          <div class="table-wrap">

            <table id="athletesTable" class="display nowrap" style="width:100%">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Athlete ID</th>
                  <th>Name</th>
                  <th>Gender</th>
                  <th>Scanned Date</th>
                  <th>Latest Status</th>
                </tr>
              </thead>
              <tbody>
              </tbody>
            </table>

          </div>

        </article>

        <?php include("profile.php"); ?>

      </section>

      <footer class="footer">
        <div class="muted small">© 2026 ECG Monitoring System • Athletes</div>
      </footer>
    </main>
  </div>

  <!-- REGISTER MODAL -->
  <?php include("modal.php"); ?>

  <script src="../../plugins/js/jquery.min.js"></script>
  <script src="../../plugins/datatables/datatables.js"></script>

  <?php include("script/table_load.php"); ?>
  <?php include("script/modal_func.php"); ?>
  <?php include("script/athlete_create.php"); ?>
  <?php include("script/athlete_edit.php"); ?>

</body>

</html>