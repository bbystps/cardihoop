<script>
  // SCAN MODAL (same pattern as your other modals)
  const scanModal = document.getElementById("scanModal");
  const scanOpenBtn = document.getElementById("scanBtn");
  const scanCloseBtn = document.getElementById("closeScan");
  const scanCancelBtn = document.getElementById("cancelScan");
  const scanBackdrop = document.getElementById("closeScanBackdrop");

  const scanStatusText = document.getElementById("scanStatusText");
  const scanHintText = document.getElementById("scanHintText");
  const scanTimerText = document.getElementById("scanTimerText");

  let lastFocusElScan = null;
  let scanActive = false;
  let scanTimerHandle = null;
  let scanStartMs = 0;

  // init inert
  scanModal.inert = true;

  function mqttSend(topic, payload) {
    const message = new Messaging.Message(String(payload));
    message.destinationName = topic;
    message.qos = 0;
    client.send(message);
  }

  function startScanTimer() {
    scanStartMs = Date.now();
    scanTimerText.textContent = "Elapsed: 0s";
    scanTimerHandle = setInterval(() => {
      const sec = Math.floor((Date.now() - scanStartMs) / 1000);
      scanTimerText.textContent = `Elapsed: ${sec}s`;
    }, 500);
  }

  function stopScanTimer() {
    if (scanTimerHandle) clearInterval(scanTimerHandle);
    scanTimerHandle = null;
  }

  function openScanModal() {
    lastFocusElScan = document.activeElement;

    scanModal.classList.add("show");
    scanModal.inert = false;
    scanModal.setAttribute("aria-hidden", "false");

    scanCloseBtn.focus();
  }

  function closeScanModal() {
    scanModal.classList.remove("show");
    scanModal.inert = true;
    scanModal.setAttribute("aria-hidden", "true");

    if (lastFocusElScan) lastFocusElScan.focus();
  }

  function beginScan() {
    scanActive = true;

    scanCloseBtn.disabled = true; // locked while scanning
    scanCancelBtn.disabled = false;

    scanStatusText.textContent = "Scanning in progress…";
    scanHintText.textContent = "Please keep still. You can cancel anytime.";

    openScanModal();
    startScanTimer();

    // request scan
    mqttSend("Cardihoop/EcgScan", "Requesting new ECG scan");
  }

  function cancelScan() {
    if (!scanActive) return;
    scanActive = false;

    mqttSend("Cardihoop/EcgScanCancel", "Cancel ECG scan");

    scanStatusText.textContent = "Scan cancelled";
    scanHintText.textContent = "You can start a new scan anytime.";

    scanCancelBtn.disabled = true;
    scanCloseBtn.disabled = false;

    stopScanTimer();

    setTimeout(() => closeScanModal(), 600);
  }

  // events
  scanOpenBtn.addEventListener("click", beginScan);
  scanCancelBtn.addEventListener("click", cancelScan);

  // backdrop click: only close if not active
  scanBackdrop.addEventListener("click", () => {
    if (!scanActive) closeScanModal();
  });

  // close button: only close if not active
  scanCloseBtn.addEventListener("click", () => {
    if (!scanActive) closeScanModal();
  });

  // ESC: only close if not active
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && scanModal.classList.contains("show")) {
      if (!scanActive) closeScanModal();
    }
  });
</script>