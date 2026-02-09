<script>
  // REGISTER MODAL
  const modal = document.getElementById("registerModal");
  const openBtn = document.getElementById("openRegister");
  const closeBtn = document.getElementById("closeRegister");
  const cancelBtn = document.getElementById("cancelRegister");
  const backdrop = document.getElementById("closeRegisterBackdrop");

  let lastFocusEl = null;

  // start hidden/inactive
  modal.inert = true;

  function openModal() {
    lastFocusEl = document.activeElement;

    modal.classList.add("show");
    modal.inert = false;
    modal.setAttribute("aria-hidden", "false");

    // move focus inside for accessibility
    closeBtn.focus();
  }

  function closeModal() {
    modal.classList.remove("show");
    modal.inert = true;
    modal.setAttribute("aria-hidden", "true");

    // return focus back to opener
    if (lastFocusEl) lastFocusEl.focus();
  }

  openBtn.addEventListener("click", openModal);
  closeBtn.addEventListener("click", closeModal);
  cancelBtn.addEventListener("click", closeModal);
  backdrop.addEventListener("click", closeModal);

  // optional: ESC closes modal
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && modal.classList.contains("show")) {
      closeModal();
    }
  });


  // RESULT MODAL
  const resultModal = document.getElementById("resultModal");
  const closeResultBtn = document.getElementById("closeResult");
  const okResultBtn = document.getElementById("okResult");
  const resultBackdrop = document.getElementById("closeResultBackdrop");

  const resultIcon = document.getElementById("resultIcon");
  const resultTitle = document.getElementById("resultTitle");
  const resultMessage = document.getElementById("resultMessage");

  let lastFocusElResult = null;

  // init inert
  resultModal.inert = true;

  function openResultModal(type, title, message) {
    lastFocusElResult = document.activeElement;

    // set content
    resultTitle.textContent = title || (type === "success" ? "Success" : "Error");
    resultMessage.textContent = message || "";

    // set icon style
    resultIcon.classList.remove("success", "error");
    if (type === "success") {
      resultIcon.textContent = "✓";
      resultIcon.classList.add("success");
    } else {
      resultIcon.textContent = "!";
      resultIcon.classList.add("error");
    }

    // show
    resultModal.classList.add("show");
    resultModal.inert = false;
    resultModal.setAttribute("aria-hidden", "false");

    okResultBtn.focus();
  }

  function closeResultModal() {
    resultModal.classList.remove("show");
    resultModal.inert = true;
    resultModal.setAttribute("aria-hidden", "true");

    if (lastFocusElResult) lastFocusElResult.focus();
  }

  closeResultBtn.addEventListener("click", closeResultModal);
  okResultBtn.addEventListener("click", closeResultModal);
  resultBackdrop.addEventListener("click", closeResultModal);

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && resultModal.classList.contains("show")) {
      closeResultModal();
    }
  });
</script>