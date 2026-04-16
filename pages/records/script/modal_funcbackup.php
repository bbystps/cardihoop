<!-- =========================================================
     FULL JS (SCAN MODAL + SAVE RECORD MODAL + DELETE ON CANCEL)
     ========================================================= -->

<script>
  // ===============================
  // SCAN MODAL
  // ===============================
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

<script>
  // ===============================
  // SAVE RECORD MODAL
  // ===============================
  const saveRecordModal = document.getElementById("saveRecordModal");
  const saveRecordBackdrop = document.getElementById("closeSaveRecordBackdrop");
  const saveRecordCloseBtn = document.getElementById("closeSaveRecord");
  const saveRecordCancelBtn = document.getElementById("srCancel");

  let lastFocusElSave = null;

  // init inert
  saveRecordModal.inert = true;

  // global flags
  window.lastScanResult = null;
  window.lastScanSaved = false;

  function endAndCloseScanModal() {
    stopScanTimer();

    scanActive = false;

    scanCancelBtn.disabled = true;
    scanCloseBtn.disabled = false;

    scanStatusText.textContent = "Scan completed";
    scanHintText.textContent = "Preparing result…";

    closeScanModal();
  }

  function openSaveRecordModal(data = null) {
    lastFocusElSave = document.activeElement;

    // Only populate if data is explicitly provided
    if (data !== null) {
      document.getElementById("srRecordId").textContent = data.record_id || "-";
      document.getElementById("srLabel").textContent = data.label || "-";
      document.getElementById("srConfidence").textContent =
        (typeof data.confidence === "number") ? (data.confidence * 100).toFixed(2) + "%" : "-";
      document.getElementById("srSavedPath").textContent =
        data.saved_path ? "Saved file: " + data.saved_path : "";
      loadAthletesForSaveModal(data?.athlete_id || "");
    } else {
      // still load athletes if you want fresh list every open
      loadAthletesForSaveModal("");
    }

    saveRecordModal.classList.add("show");
    saveRecordModal.inert = false;
    saveRecordModal.setAttribute("aria-hidden", "false");
    saveRecordCloseBtn.focus();
  }

  function closeSaveRecordModal() {
    saveRecordModal.classList.remove("show");
    saveRecordModal.inert = true;
    saveRecordModal.setAttribute("aria-hidden", "true");

    if (lastFocusElSave) lastFocusElSave.focus();
  }

  // ===============================
  // DELETE JSON WHEN USER CANCELS SAVE
  // ===============================
  function deleteLastScanFileIfNotSaved() {
    const p = window.lastScanResult?.saved_path;
    if (!p) return;

    // If already saved to DB, do not delete
    if (window.lastScanSaved) return;

    $.ajax({
      type: "POST",
      url: "api/delete_scan_file.php",
      dataType: "json",
      data: {
        saved_path: p
      },
      success: function(res) {
        if (res && res.ok) {
          toastr.info("Scan file discarded.");
        } else {
          toastr.warning(res?.error || "Could not delete scan file.");
        }
      },
      error: function() {
        toastr.warning("Server error while deleting scan file.");
      },
      complete: function() {
        // Clear local references so we don't delete twice
        window.lastScanResult = null;
        window.lastScanSaved = false;
      }
    });
  }

  function cancelSaveRecordAndDeleteFile() {
    closeSaveRecordModal();
    deleteLastScanFileIfNotSaved();
  }

  // ===============================
  // EVENTS (REPLACED to include delete)
  // ===============================
  saveRecordCloseBtn.addEventListener("click", cancelSaveRecordAndDeleteFile);
  saveRecordCancelBtn.addEventListener("click", cancelSaveRecordAndDeleteFile);
  saveRecordBackdrop.addEventListener("click", cancelSaveRecordAndDeleteFile);

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && saveRecordModal.classList.contains("show")) {
      cancelSaveRecordAndDeleteFile();
    }
  });
</script>

<script>
  // ===============================
  // HELPERS + POPULATE MODAL
  // ===============================
  function recordIdFromSavedPath(saved_path) {
    if (!saved_path) return "-";
    const filename = String(saved_path).replace(/^ecg_out\//, "");
    return filename; // display only
  }

  function numericRecordId(saved_path) {
    if (!saved_path) return null;
    const filename = String(saved_path).replace(/^ecg_out\//, "");
    const numeric = filename.replace(/\D+/g, "");
    return numeric ? parseInt(numeric, 10) : null;
  }

  function populateSaveRecordModalFromScanResult(payload) {
    const rid = numericRecordId(payload.saved_path);

    document.getElementById("srRecordId").textContent = (rid !== null) ? String(rid) : "-";
    document.getElementById("srLabel").textContent = payload.label || "-";
    document.getElementById("srConfidence").textContent =
      typeof payload.confidence === "number" ? (payload.confidence * 100).toFixed(2) + "%" : "-";
    document.getElementById("srSavedPath").textContent =
      payload.saved_path ? "Saved file: " + payload.saved_path : "";

    // store for save/cancel flow
    window.lastScanResult = payload;
    window.lastScanSaved = false; // reset for new scan

    // close scan modal first
    if (scanModal.classList.contains("show")) {
      endAndCloseScanModal();
    }

    // open save record modal without overwriting fields
    openSaveRecordModal(null);

    // load athletes
    loadAthletesForSaveModal("");
  }
</script>

<script>
  // ===============================
  // ATHLETES DROPDOWN
  // ===============================
  const srAthleteSelect = document.getElementById("srAthleteSelect");
  const srAthletePreview = document.getElementById("srAthletePreview");

  function loadAthletesForSaveModal(selectedAthleteId = "") {
    srAthleteSelect.innerHTML = `<option value="">Loading athletes…</option>`;
    srAthleteSelect.disabled = true;
    if (srAthletePreview) srAthletePreview.textContent = "";

    $.ajax({
      type: "GET",
      url: "api/athletes_list.php",
      dataType: "json",
      success: function(res) {
        const rows = (res && res.data) ? res.data : [];
        let html = `<option value="">Select athlete…</option>`;

        rows.forEach(r => {
          const id = String(r.AthleteID || "").trim();
          const name = String(r.AthleteName || "").trim();
          const selected = (selectedAthleteId && id === selectedAthleteId) ? "selected" : "";
          html += `<option value="${escapeHtml(id)}" ${selected}>${escapeHtml(name)} (${escapeHtml(id)})</option>`;
        });

        srAthleteSelect.innerHTML = html;
        srAthleteSelect.disabled = false;
        updateAthletePreview();
      },
      error: function() {
        srAthleteSelect.innerHTML = `<option value="">Failed to load athletes</option>`;
        srAthleteSelect.disabled = false;
      }
    });
  }

  function updateAthletePreview() {
    if (!srAthletePreview) return;

    const opt = srAthleteSelect.options[srAthleteSelect.selectedIndex];
    if (!opt || !srAthleteSelect.value) {
      srAthletePreview.textContent = "";
      return;
    }
    srAthletePreview.textContent = `Selected: ${opt.text}`;
  }

  srAthleteSelect.addEventListener("change", updateAthletePreview);

  function escapeHtml(str) {
    return String(str)
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }
</script>

<script>
  // ===============================
  // SAVE TO DATABASE
  // ===============================
  const srSaveBtn = document.getElementById("srSaveBtn");
  const srAthleteSelectToSave = document.getElementById("srAthleteSelect");

  function statusFromLabel(label) {
    const x = String(label || "").toLowerCase();
    return (x === "abnormal") ? "Abnormal" : "Normal";
  }

  srSaveBtn.addEventListener("click", function() {
    if (!window.lastScanResult) {
      toastr.error("No scan result loaded.");
      return;
    }

    const athlete_id = srAthleteSelectToSave.value;
    if (!athlete_id) {
      toastr.warning("Please select an athlete.");
      return;
    }

    const record_id = numericRecordId(window.lastScanResult.saved_path);
    const status = statusFromLabel(window.lastScanResult.label);

    if (!record_id) {
      toastr.error("Invalid record ID.");
      return;
    }

    srSaveBtn.disabled = true;
    srSaveBtn.textContent = "Saving...";

    $.ajax({
      type: "POST",
      url: "api/save_record.php",
      dataType: "json",
      data: {
        record_id: record_id,
        athlete_id: athlete_id,
        status: status
      },
      success: function(res) {
        if (res && res.ok) {
          window.lastScanSaved = true; // ✅ prevent deletion on cancel
          toastr.success("Record saved successfully.");

          closeSaveRecordModal();
          reloadScanRecordsTable();

          // optional: clear scan result after saving
          window.lastScanResult = null;
        } else {
          toastr.error(res?.error || "Save failed.");
        }
      },
      error: function() {
        toastr.error("Server error while saving.");
      },
      complete: function() {
        srSaveBtn.disabled = false;
        srSaveBtn.textContent = "Save to Database";
      }
    });
  });
</script>