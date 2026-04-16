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

<!-- SAVE RECORD MODAL -->
<div class="modal" id="saveRecordModal" aria-hidden="true">
  <div class="modal-backdrop" id="closeSaveRecordBackdrop"></div>
  <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="saveRecordTitle">
    <div class="modal-header">
      <div>
        <div class="modal-title" id="saveRecordTitle">Save ECG Scan Result?</div>
        <div class="muted small" id="saveRecordSubtitle">Select an athlete and confirm saving.</div>
      </div>
      <button class="btn btn-ghost" type="button" id="closeSaveRecord">✕</button>
    </div>

    <div class="modal-body">
      <div class="detail-grid" style="gap:12px;">
        <div class="info">
          <div class="info-label">Record ID</div>
          <div class="info-value mono" id="srRecordId">-</div>
        </div>
        <div class="info">
          <div class="info-label">Result</div>
          <div class="info-value" id="srLabel">-</div>
        </div>
        <div class="info">
          <div class="info-label">Confidence</div>
          <div class="info-value" id="srConfidence">-</div>
        </div>
      </div>

      <div class="divider"></div>

      <label class="muted small" style="display:block; margin-bottom:6px;">Assign to Athlete</label>
      <select id="srAthleteSelect" class="input" style="width:100%; padding:10px; border-radius:10px;">
        <option value="">Loading athletes…</option>
      </select>
      <div class="muted small" style="margin-top:8px;" id="srAthletePreview"></div>
      <div class="muted small" style="margin-top:10px;" id="srSavedPath"> </div>
    </div>

    <div class="modal-footer" style="display:flex; gap:10px; justify-content:flex-end;">
      <button class="btn btn-ghost" type="button" id="srCancel">Cancel</button>
      <button class="btn btn-primary" type="button" id="srSaveBtn">Save to Database</button>
    </div>
  </div>
</div>