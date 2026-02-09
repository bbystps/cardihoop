<article class="card profile">
  <div class="profile-head">
    <div class="profile-meta">
      <div class="profile-name" id="profileName">Select an athlete</div>
      <div class="muted" id="profileId">Athlete ID: —</div>
      <div class="muted small" id="profileEmail">—</div>
    </div>
  </div>

  <!-- QUICK INFO -->
  <div class="profile-grid">
    <div class="info">
      <div class="info-label">Gender</div>
      <div class="info-value" id="profileGender">—</div>
    </div>
    <div class="info">
      <div class="info-label">Age</div>
      <div class="info-value" id="profileAge">—</div>
    </div>
    <div class="info">
      <div class="info-label">Birthdate</div>
      <div class="info-value" id="profileBirthdate">—</div>
    </div>
    <div class="info">
      <div class="info-label">Contact</div>
      <div class="info-value" id="profileContact">—</div>
    </div>
  </div>

  <div class="divider"></div>

  <!-- PERSONAL DETAILS -->
  <div class="profile-section">
    <div class="section-title">Personal Details</div>

    <div class="detail-grid">
      <div class="detail">
        <div class="detail-label">Address</div>
        <div class="detail-value" id="profileAddress">—</div>
      </div>

      <div class="detail">
        <div class="detail-label">Birthplace</div>
        <div class="detail-value" id="profileBirthplace">—</div>
      </div>

      <div class="detail">
        <div class="detail-label">Civil Status</div>
        <div class="detail-value" id="profileCivilStatus">—</div>
      </div>

      <div class="detail">
        <div class="detail-label">Citizenship</div>
        <div class="detail-value" id="profileCitizenship">—</div>
      </div>

      <div class="detail">
        <div class="detail-label">Religion</div>
        <div class="detail-value" id="profileReligion">—</div>
      </div>

      <div class="detail">
        <div class="detail-label">Registered</div>
        <div class="detail-value" id="profileTimestamp">—</div>
      </div>
    </div>
  </div>

  <div class="divider"></div>

  <!-- BODY METRICS -->
  <div class="profile-section">
    <div class="section-title">Body Metrics</div>

    <div class="mini-kpis">
      <div class="kpi">
        <div class="kpi-label">Height</div>
        <div class="kpi-value" id="profileHeight">—</div>
      </div>
      <div class="kpi">
        <div class="kpi-label">Weight</div>
        <div class="kpi-value" id="profileWeight">—</div>
      </div>
    </div>
  </div>

  <div class="divider"></div>

  <!-- LATEST ECG SUMMARY -->
  <div class="profile-section">
    <div class="card-title">Latest ECG Summary</div>

    <div class="mini-kpis">
      <div class="kpi">
        <div class="kpi-label">Last Scan</div>
        <div class="kpi-value" id="profileLastScan">—</div>
      </div>
      <div class="kpi">
        <div class="kpi-label">Result</div>
        <div class="kpi-value" id="profileResult"><span class="badge">—</span></div>
      </div>
    </div>

    <div class="note" style="margin-top:12px;">
      <div class="note-title">Action</div>
      <div class="note-text" id="profileActionText">
        Select an athlete to view details.
      </div>
    </div>

    <div class="profile-actions">
      <button class="btn btn-primary" type="button" id="btnViewRecords" disabled>View Records</button>
      <button class="btn btn-ghost" type="button" id="btnEditProfile" disabled>Edit Profile</button>
    </div>
  </div>

  <div class="divider"></div>

  <!-- EMERGENCY CONTACT -->
  <div class="profile-section">
    <div class="section-title">Emergency Contact</div>

    <div class="detail-grid">
      <div class="detail">
        <div class="detail-label">Name</div>
        <div class="detail-value" id="profileEmName">—</div>
      </div>

      <div class="detail">
        <div class="detail-label">Number</div>
        <div class="detail-value" id="profileEmNumber">—</div>
      </div>

      <div class="detail detail-full">
        <div class="detail-label">Address</div>
        <div class="detail-value" id="profileEmAddress">—</div>
      </div>
    </div>
  </div>
</article>