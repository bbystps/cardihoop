<script>
  $(function() {
    // Store selected athlete DB id here
    window.selectedAthleteId = 0;

    function setProfileEmpty() {
      $("#profileName").text("Select an athlete");
      $("#profileId").text("Athlete ID: —");
      $("#profileEmail").text("—");
      $("#profileGender").text("—");
      $("#profileAge").text("—");
      $("#profileBirthdate").text("—");
      $("#profileContact").text("—");
      $("#profileAddress").text("—");
      $("#profileBirthplace").text("—");
      $("#profileCivilStatus").text("—");
      $("#profileCitizenship").text("—");
      $("#profileReligion").text("—");
      $("#profileTimestamp").text("—");
      $("#profileHeight").text("—");
      $("#profileWeight").text("—");
      $("#profileLastScan").text("—");
      $("#profileResult").html('<span class="badge">—</span>');
      $("#profileActionText").text("Select an athlete to view details.");

      $("#btnEditProfile").prop("disabled", true);
      $("#btnViewRecords").prop("disabled", true);
    }

    function fillProfile(a) {
      $("#profileName").text(a.name || "—");
      $("#profileId").text("Athlete ID: " + (a.athlete_id || "—"));
      $("#profileEmail").text(a.email || "—");
      $("#profileGender").text(a.sex || "—");
      $("#profileAge").text(a.age || "—");
      $("#profileBirthdate").text(a.birthdate || "—");
      $("#profileContact").text(a.contact_number || "—");
      $("#profileAddress").text(a.address || "—");
      $("#profileBirthplace").text(a.birthplace || "—");
      $("#profileCivilStatus").text(a.civil_status || "—");
      $("#profileCitizenship").text(a.citizenship || "—");
      $("#profileReligion").text(a.religion || "—");
      $("#profileTimestamp").text(a.timestamp || "—");
      $("#profileHeight").text(a.height || "—");
      $("#profileWeight").text(a.weight || "—");

      // If you have these in your table/api later:
      $("#profileLastScan").text(a.last_scan || "—");
      $("#profileResult").html('<span class="badge">' + (a.latest_status || "—") + '</span>');

      $("#profileActionText").text("You may edit profile or view records.");

      $("#btnEditProfile").prop("disabled", false);
      $("#btnViewRecords").prop("disabled", false);
    }

    function loadAthleteAndUpdateUI(id) {
      window.selectedAthleteId = id;

      $.ajax({
        type: "GET",
        url: "api/athlete_get.php",
        data: {
          id
        },
        dataType: "json",
        success: function(out) {
          if (!out || out.ok !== true || !out.data) {
            openResultModal("error", "Load failed", (out && out.error) ? out.error : "Failed to load athlete.");
            return;
          }
          fillProfile(out.data);
        },
        error: function() {
          openResultModal("error", "Server error", "Failed to load athlete profile.");
        }
      });
    }

    // Start empty
    setProfileEmpty();

    // ----- ROW CLICK -----
    $("#athletesTable tbody").on("click", "tr", function() {
      // Use your global dt if you have it; else grab DataTable instance
      const dt = window.athletesDt || $("#athletesTable").DataTable();

      const row = dt.row(this).data();
      if (!row) return;

      // IMPORTANT:
      // If your ajax returns array rows -> ID is row[0]
      // If it returns object rows -> ID is row.id
      const id = (typeof row === "object") ? (row.id || row.ID) : row[0];
      const athleteId = parseInt(id, 10);

      if (!athleteId) return;

      // Highlight selected
      $("#athletesTable tbody tr").removeClass("selected");
      $(this).addClass("selected");

      loadAthleteAndUpdateUI(athleteId);
    });

    // ----- EDIT CLICK -----
    $("#btnEditProfile").on("click", function() {
      if (!window.selectedAthleteId) return;

      $.ajax({
        type: "GET",
        url: "api/athlete_get.php",
        data: {
          id: window.selectedAthleteId
        },
        dataType: "json",
        success: function(out) {
          if (!out || out.ok !== true || !out.data) {
            openResultModal("error", "Load failed", (out && out.error) ? out.error : "Failed to load athlete.");
            return;
          }

          const a = out.data;

          // Switch modal form to UPDATE endpoint
          const $form = $("#registerForm");
          $form.attr("action", "api/athlete_update.php");

          // Make sure your modal has this hidden input:
          // <input type="hidden" name="id" id="athleteDbId">
          $("#athleteDbId").val(a.id);

          // Fill form fields by "name"
          $form.find('[name="athlete_id"]').val(a.athlete_id);
          $form.find('[name="name"]').val(a.name);
          $form.find('[name="age"]').val(a.age);
          $form.find('[name="sex"]').val(a.sex);
          $form.find('[name="address"]').val(a.address);
          $form.find('[name="birthdate"]').val(a.birthdate);
          $form.find('[name="birthplace"]').val(a.birthplace);
          $form.find('[name="civil_status"]').val(a.civil_status);
          $form.find('[name="citizenship"]').val(a.citizenship);
          $form.find('[name="religion"]').val(a.religion);
          $form.find('[name="contact_number"]').val(a.contact_number);
          $form.find('[name="email"]').val(a.email);
          $form.find('[name="height"]').val(a.height);
          $form.find('[name="weight"]').val(a.weight);
          $form.find('[name="emergency_contact"]').val(a.emergency_contact);
          $form.find('[name="em_contact_address"]').val(a.em_contact_address);
          $form.find('[name="em_contact_number"]').val(a.em_contact_number);

          // Open the modal using your existing function
          openModal();
        },
        error: function() {
          openResultModal("error", "Server error", "Failed to load athlete for editing.");
        }
      });
    });

  });
</script>