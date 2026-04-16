<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Admin Account - Cardihoop</title>

  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/login.css">
  <link rel="stylesheet" href="css/modal.css">
</head>

<body>
  <!-- HEADER -->
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
        Create Admin Account
      </div>
      <div class="login-subtitle">
        Add a new administrator account for system access.
      </div>

      <div class="login-form">
        <form id="createAdminForm" enctype="multipart/form-data">
          <div class="login-field">
            <label for="admin_username">Username</label>
            <input type="text" name="username" id="admin_username" autocomplete="off" required>

            <label class="mt-5" for="admin_password">Password</label>
            <input type="password" name="password" id="admin_password" required>

            <label class="mt-5" for="admin_confirm_password">Confirm Password</label>
            <input type="password" name="confirm_password" id="admin_confirm_password" required>

            <button id="create_admin_button" class="mt-10" type="submit">Create Admin Account</button>
          </div>
        </form>
      </div>

      <div class="login-foot">
        <a href="index.php" class="login-link">Back to Login</a>
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

  // ===== Create Admin AJAX =====
  $("#createAdminForm").on("submit", function(e) {
    e.preventDefault();

    const username = $("#admin_username").val().trim();
    const password = $("#admin_password").val();
    const confirmPassword = $("#admin_confirm_password").val();
    const btn = document.getElementById("create_admin_button");

    if (username === "" || password === "" || confirmPassword === "") {
      openLoginModal("error", "Missing Fields", "Please complete all required fields.", "");
      return false;
    }

    if (password !== confirmPassword) {
      openLoginModal("error", "Password Mismatch", "Password and confirm password do not match.", "");
      return false;
    }

    btn.disabled = true;
    btn.textContent = "Creating...";

    const form = $('#createAdminForm')[0];
    const data = new FormData(form);

    $.ajax({
      type: "POST",
      enctype: 'multipart/form-data',
      url: "login/create_admin.php",
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
            "Check create_admin.php output."
          );
          return;
        }

        const createState = (json.status || "").toUpperCase();

        if (createState === "SUCCESS") {
          openLoginModal(
            "success",
            "Admin Created",
            "New admin account has been created successfully.",
            ""
          );

          document.getElementById("createAdminForm").reset();

        } else if (createState === "EMPTY FIELD") {
          openLoginModal("error", "Missing Fields", "Please enter username and password.", "");
        } else if (createState === "USER EXISTS") {
          openLoginModal("error", "Username Exists", "That username is already taken.", "Please choose a different username.");
        } else if (createState === "FAIL") {
          openLoginModal("error", "Creation Failed", "Unable to create admin account.", "");
        } else if (createState === "PASSWORD MISMATCH") {
          openLoginModal("error", "Password Mismatch", "Password and confirm password do not match.", "");
        } else {
          openLoginModal("error", "Unknown Response", "Server returned: " + createState, "");
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
        btn.textContent = "Create Admin Account";
      }
    });

    return false;
  });
</script>

</html>