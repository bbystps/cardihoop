<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pond Monitoring System</title>

  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/login.css">
  <link rel="stylesheet" href="css/modal.css">
</head>

<body>
  <!-- LOGIN HEADER -->
  <header class="login-header">
    <div class="login-brand">
      <div class="login-logo">
        <img src="assets/img/logo2.png" alt="Cardihoop Logo">
      </div>
      <div class="login-brand-text">
        <div class="login-brand-title">Cardihoop</div>
        <div class="login-brand-subtitle">
          Play with intensity, monitor for safety
        </div>
      </div>
    </div>
  </header>

  <div class="container-center">
    <div class="login-card">
      <div class="login-title">
        Please Login to Your Account
      </div>
      <div class="login-form">
        <form id="loginform" enctype="multipart/form-data">
          <div class="login-field">
            <label for="username">Username</label>
            <input autocomplete="false" type="text" name="username" id="username" required>
            <label class="mt-5" for="password">Password</label>
            <input type="password" name="password" id="password" required>
            <button id="login_button" class="mt-10">Login</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <?php include("login/modal.php"); ?>

</body>

<script src="plugins/js/jquery.min.js"></script>
<script>
  // ===== Modal helpers =====
  const loginModal = document.getElementById("loginResultModal");
  const loginBackdrop = document.getElementById("loginModalBackdrop");
  const closeLoginModalBtn = document.getElementById("closeLoginModal");
  const okLoginModalBtn = document.getElementById("okLoginModal");

  const iconEl = document.getElementById("loginResultIcon");
  const headlineEl = document.getElementById("loginResultHeadline");
  const textEl = document.getElementById("loginResultText");
  const hintEl = document.getElementById("loginResultHint");

  let lastFocusEl = null;

  function openLoginModal(type, headline, text, hint) {
    lastFocusEl = document.activeElement;

    // reset classes
    iconEl.classList.remove("success", "error");
    iconEl.classList.add(type === "success" ? "success" : "error");

    iconEl.textContent = type === "success" ? "✓" : "!";
    headlineEl.textContent = headline || "Notice";
    textEl.textContent = text || "—";
    hintEl.textContent = hint || "";

    loginModal.classList.add("show");
    loginModal.inert = false;
    loginModal.setAttribute("aria-hidden", "false");
    okLoginModalBtn.focus();
  }

  function closeLoginModal() {
    loginModal.classList.remove("show");
    loginModal.inert = true;
    loginModal.setAttribute("aria-hidden", "true");
    if (lastFocusEl) lastFocusEl.focus();
  }

  closeLoginModalBtn.addEventListener("click", closeLoginModal);
  okLoginModalBtn.addEventListener("click", closeLoginModal);
  loginBackdrop.addEventListener("click", closeLoginModal);

  // ===== Login AJAX =====
  $("#login_button").click(function(e) {
    e.preventDefault();

    const btn = document.getElementById("login_button");
    btn.disabled = true;
    btn.textContent = "Logging in...";

    const form = $('#loginform')[0];
    const data = new FormData(form);

    $.ajax({
      type: "POST",
      enctype: 'multipart/form-data',
      url: "login/authenticate.php",
      data: data,
      processData: false,
      contentType: false,
      cache: false,
      success: function(resp) {
        let json;
        try {
          json = (typeof resp === "string") ? JSON.parse(resp) : resp;
        } catch (err) {
          openLoginModal(
            "error",
            "Unexpected Response",
            "Server did not return valid JSON.",
            "Check authenticate.php output (no extra echo/HTML)."
          );
          return;
        }

        const loginState = (json.login || "").toUpperCase();

        if (loginState === "SUCCESS") {
          openLoginModal("success", "Login Successful", "Redirecting to dashboard…", "");
          setTimeout(() => {
            window.location.href = "pages/dashboard/index.php";
          }, 600);
        } else if (loginState === "EMPTY FIELD") {
          openLoginModal("error", "Missing Fields", "Please enter username and password.", "");
        } else if (loginState === "NO USER") {
          openLoginModal("error", "User Not Found", "No account matches that username.", "Double-check your username.");
        } else if (loginState === "FAIL") {
          openLoginModal("error", "Invalid Password", "Incorrect password. Please try again.", "");
        } else {
          openLoginModal("error", "Login Failed", "Unknown status returned: " + loginState, "");
        }
      },
      error: function(xhr) {
        openLoginModal(
          "error",
          "Network / Server Error",
          "Request failed (" + xhr.status + "). Please try again.",
          ""
        );
      },
      complete: function() {
        btn.disabled = false;
        btn.textContent = "Login";
      }
    });

    return false;
  });
</script>

</html>