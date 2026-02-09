<!-- SCAN MODAL -->
<div class="modal" id="scanModal" aria-hidden="true">
  <div class="modal-backdrop" id="closeScanBackdrop"></div>

  <div class="modal-card modal-card-sm" role="dialog" aria-modal="true" aria-labelledby="scanTitle">
    <div class="modal-head">
      <div>
        <div class="modal-title" id="scanTitle">Scanning…</div>
        <div class="muted small" id="scanSubtitle">Requesting new ECG scan. Please hold still.</div>
      </div>

      <button class="icon-btn" type="button" id="closeScan" aria-label="Close" disabled>✕</button>
    </div>

    <div class="scan-body">
      <div class="scan-row">
        <div class="scan-spinner" aria-hidden="true"></div>
        <div class="scan-meta">
          <div class="row-strong" id="scanStatusText">Waiting for device…</div>
          <div class="muted small" id="scanHintText">You can cancel anytime.</div>
        </div>
      </div>
    </div>

    <div class="modal-actions">
      <div class="muted small" id="scanTimerText">Elapsed: 0s</div>
      <div class="modal-actions-right">
        <button class="btn btn-danger" type="button" id="cancelScan">Cancel Scan</button>
      </div>
    </div>
  </div>
</div>