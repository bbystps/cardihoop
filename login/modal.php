<!-- LOGIN RESULT MODAL -->
<div class="modal" id="loginResultModal" aria-hidden="true" inert>
  <div class="modal-backdrop" id="loginModalBackdrop" aria-hidden="true"></div>

  <div class="modal-card modal-card-sm" role="dialog" aria-modal="true" aria-labelledby="loginModalTitle">
    <div class="modal-head">
      <div class="modal-title" id="loginModalTitle">Login Status</div>
      <button class="icon-btn" type="button" id="closeLoginModal" aria-label="Close">✕</button>
    </div>

    <div class="scan-body">
      <div class="result-head">
        <div class="result-icon" id="loginResultIcon">!</div>
        <div class="scan-meta">
          <div class="row-strong" id="loginResultHeadline">Message</div>
          <div class="muted small" id="loginResultText">—</div>
        </div>
      </div>

      <div class="divider" style="margin:12px 0;"></div>

      <div class="modal-actions" style="padding-top:0; border-top:none;">
        <div class="muted small" id="loginResultHint">Please try again.</div>
        <div class="modal-actions-right">
          <button class="btn btn-primary" type="button" id="okLoginModal">OK</button>
        </div>
      </div>
    </div>
  </div>
</div>