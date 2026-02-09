<script>
  $(function() {
    $.ajax({
      type: "POST",
      url: "api/stats_data.php", // adjust path if needed (see note below)
      dataType: "json",
      success: function(res) {
        if (!res || !res.ok) {
          console.error(res?.error || "Failed to load stats");
          return;
        }

        $("#stat-athletes").text(res.data.athletes_total);
        $("#stat-scans-today").text(res.data.scans_today);
        $("#stat-abnormal").text(res.data.abnormal_total);
      },
      error: function(xhr) {
        console.error("Stats API error:", xhr.status, xhr.responseText);
      }
    });
  });
</script>