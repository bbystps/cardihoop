<script>
  jQuery(function($) {

    // ============================
    // DataTable init
    // ============================
    const table = $('#athletesTable').DataTable({
      ajax: {
        url: 'api/table_data.php',
        dataSrc: 'data'
      },
      columns: [{
          data: 'ID'
        },
        {
          data: 'AthleteID'
        },
        {
          data: 'Name'
        },
        {
          data: 'Gender'
        },
        {
          data: 'ScannedDate'
        },
        {
          data: 'Status',
          render: function(data, type) {
            if (type !== 'display') return data;
            if (data === 'N/A') return '<span class="badge">N/A</span>';
            return String(data).trim().toLowerCase() === 'normal' ?
              '<span class="badge badge-green">Normal</span>' :
              '<span class="badge badge-red">Abnormal</span>';
          }
        },
        {
          data: null,
          orderable: false,
          searchable: false,
          render: () => '<button class="btn btn-ghost btn-sm js-view" type="button">View</button>'
        }
      ],
      columnDefs: [{
          targets: [0, 1],
          visible: false
        },
        {
          targets: '_all',
          className: 'dt-left'
        }
      ],
      scrollX: true,
      scrollCollapse: true,
      responsive: false,
      autoWidth: true,
      ordering: true,
      order: [
        [0, 'desc']
      ],
      paging: true,
      searching: false
    });

    function adjustTable() {
      table.columns.adjust().draw(false);
    }
    window.addEventListener('resize', adjustTable);
    setTimeout(adjustTable, 200);

    // ============================
    // Helpers
    // ============================
    const dash = '—';

    function setText(id, val) {
      const el = document.getElementById(id);
      if (!el) return;
      el.textContent = (val === null || val === undefined || val === '') ? dash : String(val);
    }

    function setHtml(id, html) {
      const el = document.getElementById(id);
      if (!el) return;
      el.innerHTML = html;
    }

    function normalizeStatus(s) {
      return (s ?? '').toString().trim().toLowerCase();
    }

    function setBadgeResult(status) {
      const s = normalizeStatus(status);
      if (!s) return setHtml('profileResult', `<span class="badge">${dash}</span>`);

      setHtml(
        'profileResult',
        (s === 'normal') ?
        '<span class="badge badge-green">Normal</span>' :
        '<span class="badge badge-red">Abnormal</span>'
      );
    }

    function setActionText(status) {
      const s = normalizeStatus(status);
      setText(
        'profileActionText',
        (!s) ?
        'No ECG records found for this athlete.' :
        (s === 'normal' ?
          'No immediate action required. Continue routine screening.' :
          'Recommended: repeat scan and refer to medical staff for evaluation.')
      );
    }

    function setButtonsEnabled(enabled, athleteId) {
      const viewBtn = document.getElementById('btnViewRecords');
      const editBtn = document.getElementById('btnEditProfile');
      if (!viewBtn || !editBtn) return;

      viewBtn.disabled = !enabled;
      editBtn.disabled = !enabled;

      viewBtn.dataset.athleteId = enabled ? (athleteId || '') : '';
      editBtn.dataset.athleteId = enabled ? (athleteId || '') : '';
    }

    // Map DOM ids to API fields
    const FIELD_MAP = {
      profileName: 'name',
      profileEmail: 'email',

      profileGender: 'sex',
      profileAge: 'age',
      profileBirthdate: 'birthdate',
      profileContact: 'contact_number',

      profileAddress: 'address',
      profileBirthplace: 'birthplace',
      profileCivilStatus: 'civil_status',
      profileCitizenship: 'citizenship',
      profileReligion: 'religion',
      profileTimestamp: 'timestamp',

      profileHeight: 'height',
      profileWeight: 'weight',

      profileEmName: 'emergency_contact',
      profileEmNumber: 'em_contact_number',
      profileEmAddress: 'em_contact_address'
    };

    function clearProfileUI() {
      setText('profileName', 'Select an athlete');
      setText('profileId', 'Athlete ID: —');
      setText('profileEmail', dash);

      Object.keys(FIELD_MAP).forEach(id => {
        if (id !== 'profileName' && id !== 'profileEmail') setText(id, dash);
      });

      setText('profileLastScan', dash);
      setBadgeResult('');
      setActionText('');
      setButtonsEnabled(false);
    }

    // ============================
    // Race protection + AJAX
    // ============================
    let currentXhr = null;
    let requestToken = 0;

    function fillProfile(data) {
      // Identity
      setText('profileName', data?.name);
      setText('profileId', data?.athlete_id ? `Athlete ID: ${data.athlete_id}` : 'Athlete ID: —');

      // Bulk fields
      for (const [domId, apiKey] of Object.entries(FIELD_MAP)) {
        setText(domId, data?.[apiKey]);
      }

      // Latest record fields (exact keys from your PHP: last_scan, last_status)
      const lastScan = data?.last_scan ?? '';
      const lastStatus = data?.last_status ?? '';

      setText('profileLastScan', lastScan);
      setBadgeResult(lastStatus);
      setActionText(lastStatus);

      setButtonsEnabled(true, data?.athlete_id);
    }

    function loadProfileByAthleteId(athleteId) {
      if (!athleteId) return;

      // Abort in-flight request so older response can't overwrite newer UI
      if (currentXhr && currentXhr.readyState !== 4) {
        currentXhr.abort();
      }

      const myToken = ++requestToken;

      console.log('[profile] request start', {
        athleteIdRequested: athleteId,
        token: myToken
      });

      setButtonsEnabled(false);
      setText('profileName', 'Loading...');
      setText('profileId', 'Athlete ID: —');
      setText('profileLastScan', dash);
      setBadgeResult('');
      setActionText('');

      currentXhr = $.ajax({
          type: "POST",
          url: "api/get_athlete_profile.php",
          dataType: "json",
          data: {
            athlete_id: athleteId
          }
        })
        .done(function(res) {
          // Ignore if this is not the latest request
          if (myToken !== requestToken) {
            console.log('[profile] stale response ignored', {
              token: myToken,
              latestToken: requestToken
            });
            return;
          }

          console.log('[profile] response', {
            athleteIdRequested: athleteId,
            token: myToken,
            ok: res?.ok,
            last_scan: res?.data?.last_scan,
            last_status: res?.data?.last_status,
            debug: res?.debug
          });

          if (!res || !res.ok || !res.data) {
            alert(res && res.error ? res.error : 'Failed to load profile.');
            clearProfileUI();
            return;
          }

          fillProfile(res.data);
        })
        .fail(function(xhr, status) {
          if (status === 'abort') {
            console.log('[profile] request aborted', {
              token: myToken
            });
            return;
          }
          if (myToken !== requestToken) return;

          console.log('[profile] request failed', {
            token: myToken,
            status,
            http: xhr?.status,
            responseText: xhr?.responseText
          });

          alert('Server error while loading profile.');
          clearProfileUI();
        });
    }

    // ============================
    // View button click
    // ============================
    $('#athletesTable tbody').on('click', '.js-view', function(e) {
      e.preventDefault();
      e.stopPropagation();

      const rowData = table.row($(this).closest('tr')).data();
      if (!rowData?.AthleteID) return;

      // Store BOTH ids:
      const editBtn = document.getElementById('btnEditProfile');
      if (editBtn) {
        editBtn.dataset.dbId = rowData.ID || ''; // DB primary key
        editBtn.dataset.athleteId = rowData.AthleteID || ''; // public AthleteID
      }

      if (!rowData.AthleteID) return;

      loadProfileByAthleteId(rowData.AthleteID);
    });

    // Block row click (prevents profile loading on row body click)
    $('#athletesTable tbody').on('click', 'tr', function(e) {
      if (!$(e.target).closest('.js-view').length) {
        e.preventDefault();
        e.stopImmediatePropagation();
        return false;
      }
    });

    // Initial state
    clearProfileUI();
  });
</script>