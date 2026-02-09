<script>
  $(function() {
    $.ajax({
      type: "POST",
      url: "api/summary_data.php", // adjust path if inside /api/ folder
      dataType: "json",
      success: function(res) {
        if (!res || !res.ok) {
          console.error(res?.error || "Failed to load summary");
          return;
        }

        $("#stat-normal").text(res.data.normal);
        $("#stat-review").text(res.data.review);

        $("#bar-normal").css("width", res.data.normal_pct + "%");
        $("#bar-review").css("width", res.data.review_pct + "%");
      },
      error: function(xhr) {
        console.error("Summary API error:", xhr.status, xhr.responseText);
      }
    });
  });
</script>