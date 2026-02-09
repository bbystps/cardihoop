<!-- RESULT MODAL (Success / Error) -->
<div class="modal" id="resultModal" aria-hidden="true">
  <div class="modal-backdrop" id="closeResultBackdrop"></div>

  <div class="modal-card modal-card-sm" role="dialog" aria-modal="true" aria-label="Result">
    <div class="modal-head">
      <div class="result-head">
        <div class="result-icon" id="resultIcon">✓</div>
        <div>
          <div class="modal-title" id="resultTitle">Success</div>
          <div class="muted small" id="resultMessage">Saved.</div>
        </div>
      </div>

      <button class="icon-btn" type="button" id="closeResult" aria-label="Close">✕</button>
    </div>

    <div class="modal-actions" style="border-top:none; padding-top:10px;">
      <div></div>
      <div class="modal-actions-right">
        <button class="btn btn-primary" type="button" id="okResult">OK</button>
      </div>
    </div>
  </div>
</div>

<!-- REGISTER MODAL -->
<div class="modal" id="registerModal" aria-hidden="true">
  <div class="modal-backdrop" id="closeRegisterBackdrop"></div>

  <div class="modal-card" role="dialog" aria-modal="true" aria-label="Register Athlete">
    <div class="modal-head">
      <div>
        <div class="modal-title">Register Athlete</div>
        <div class="muted small">Complete the profile fields below.</div>
      </div>
      <button class="icon-btn" type="button" id="closeRegister" aria-label="Close">✕</button>
    </div>

    <form class="form" id="registerForm" action="api/athlete_create.php" method="post" autocomplete="off">
      <div class="form-grid">
        <input type="hidden" name="id" id="athleteDbId" value="">

        <!-- athlete_id -->
        <div class="field">
          <label>Athlete ID <span class="req">*</span></label>
          <input name="athlete_id" type="text" placeholder="e.g., A-006" required maxlength="16" />
        </div>

        <!-- name -->
        <div class="field">
          <label>Full Name <span class="req">*</span></label>
          <input name="name" type="text" placeholder="e.g., Juan Dela Cruz" required maxlength="255" />
        </div>

        <!-- sex -->
        <div class="field">
          <label>Gender <span class="req">*</span></label>
          <select name="sex" required>
            <option value="" selected disabled>Select</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
          </select>
        </div>

        <!-- age -->
        <div class="field">
          <label>Age <span class="req">*</span></label>
          <input name="age" type="text" placeholder="e.g., 21" required maxlength="4" />
        </div>

        <!-- birthdate -->
        <div class="field">
          <label>Birthdate <span class="req">*</span></label>
          <input name="birthdate" type="date" required />
        </div>

        <!-- birthplace -->
        <div class="field">
          <label>Birthplace <span class="req">*</span></label>
          <input name="birthplace" type="text" placeholder="e.g., Bacolor, Pampanga" required maxlength="64" />
        </div>

        <!-- address -->
        <div class="field field-full">
          <label>Address <span class="req">*</span></label>
          <input name="address" type="text" placeholder="Complete address" required maxlength="255" />
        </div>

        <!-- civil_status -->
        <div class="field">
          <label>Civil Status <span class="req">*</span></label>
          <select name="civil_status" required>
            <option value="" selected disabled>Select</option>
            <option>Single</option>
            <option>Married</option>
            <option>Widowed</option>
            <option>Separated</option>
          </select>
        </div>

        <!-- citizenship -->
        <div class="field">
          <label>Citizenship <span class="req">*</span></label>
          <input name="citizenship" type="text" placeholder="e.g., Filipino" required maxlength="16" />
        </div>

        <!-- religion -->
        <div class="field">
          <label>Religion <span class="req">*</span></label>
          <input name="religion" type="text" placeholder="e.g., Roman Catholic" required maxlength="16" />
        </div>

        <!-- contact_number -->
        <div class="field">
          <label>Contact Number <span class="req">*</span></label>
          <input name="contact_number" type="text" placeholder="+63 9XX XXX XXXX" required maxlength="16" />
        </div>

        <!-- email -->
        <div class="field">
          <label>Email <span class="req">*</span></label>
          <input name="email" type="email" placeholder="e.g., athlete@email.com" required maxlength="255" />
        </div>

        <!-- height -->
        <div class="field">
          <label>Height <span class="req">*</span></label>
          <input name="height" type="text" placeholder="e.g., 170 cm" required maxlength="8" />
        </div>

        <!-- weight -->
        <div class="field">
          <label>Weight <span class="req">*</span></label>
          <input name="weight" type="text" placeholder="e.g., 65 kg" required maxlength="8" />
        </div>

        <div class="divider field-full"></div>

        <!-- emergency_contact -->
        <div class="field">
          <label>Emergency Contact Name <span class="req">*</span></label>
          <input name="emergency_contact" type="text" placeholder="e.g., Maria Dela Cruz" required maxlength="16" />
        </div>

        <!-- em_contact_number -->
        <div class="field">
          <label>Emergency Contact Number <span class="req">*</span></label>
          <input name="em_contact_number" type="text" placeholder="+63 9XX XXX XXXX" required maxlength="16" />
        </div>

        <!-- em_contact_address -->
        <div class="field field-full">
          <label>Emergency Contact Address <span class="req">*</span></label>
          <input name="em_contact_address" type="text" placeholder="Address of emergency contact" required maxlength="255" />
        </div>
      </div>

      <div class="modal-actions">
        <div class="modal-status muted small" id="registerStatus"></div>
        <div class="modal-actions-right">
          <button class="btn btn-ghost" type="button" id="cancelRegister">Cancel</button>
          <button class="btn btn-primary" type="submit">Save Athlete</button>
        </div>
      </div>
    </form>
  </div>
</div>