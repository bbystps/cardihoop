<script>
  $(function() {
    const $form = $("#registerForm");
    const $status = $("#registerStatus");

    $form.on("submit", function(e) {
      e.preventDefault();

      $status.text("Saving...");

      $.ajax({
        type: "POST",
        url: this.action, // ✅ as you requested
        data: new FormData(this),
        processData: false, // ✅ required for FormData
        contentType: false, // ✅ required for FormData
        dataType: "json",
        success: function(out) {
          if (!out || out.ok !== true) {
            const msg = (out && out.error) ? out.error : "Failed to save.";
            $status.text(msg);

            // show error modal
            openResultModal("error", "Registration failed", msg);
            return;
          }

          $status.text("Saved!");
          $form[0].reset();

          setCreateMode();

          // close register modal
          closeModal();

          // refresh datatable
          if (window.athletesDt) {
            window.athletesDt.ajax.reload(null, false);
          } else if ($.fn.dataTable.isDataTable("#athletesTable")) {
            $("#athletesTable").DataTable().ajax.reload(null, false);
          }

          // show success modal
          openResultModal("success", "Operation successful", "The athlete profile was saved successfully.");
        },
        error: function(xhr) {
          // try to read JSON error if server returned one
          let msg = "Network / server error.";
          try {
            const out = xhr.responseJSON;
            if (out && out.error) msg = out.error;
          } catch (e) {}

          $status.text(msg);
          openResultModal("error", "Server error", msg);
        }
      });
    });
  });
</script>