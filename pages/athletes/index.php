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

        <!-- <button class="btn btn-danger" type="button">
          <span class="btn-icon">⏻</span>
          <span>Logout</span>
        </button> -->
      </div>
    </aside>

    <!-- MAIN -->
    <main class="main">
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
          <!-- <div class="search">
            <span class="search-icon">🔎</span>
            <input id="athleteSearch" type="text" placeholder="Search name, ID, team..." />
          </div> -->

          <button class="btn btn-primary" type="button" id="openRegister">
            <span class="btn-icon">＋</span>
            <span>Register Athlete</span>
          </button>

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

      if (!app) {
        console.warn('[Sidebar] .app not found');
        return;
      }
      if (!btn) {
        console.warn('[Sidebar] #sidebarToggle not found');
        return;
      }

      // restore state
      const saved = localStorage.getItem('sidebarCollapsed');
      if (saved === '1') app.classList.add('is-collapsed');

      btn.addEventListener('click', function() {
        app.classList.toggle('is-collapsed');
        localStorage.setItem('sidebarCollapsed', app.classList.contains('is-collapsed') ? '1' : '0');
      });
    });
  </script>

  <?php include("script/table_load.php"); ?>
  <?php include("script/modal_func.php"); ?>
  <?php include("script/athlete_create.php"); ?>
  <?php include("script/athlete_edit.php"); ?>

</body>

</html>