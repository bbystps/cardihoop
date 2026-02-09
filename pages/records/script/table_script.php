<script>
  $(function() {
    const table = $('#scanRecordsTable').DataTable({
      ajax: {
        url: 'api/table_data.php',
        dataSrc: 'data'
      },

      columns: [{
          data: 'ID'
        }, // hidden
        {
          data: 'RecordID'
        },
        {
          data: 'AthleteID'
        },
        {
          data: 'AthleteName'
        },
        {
          data: 'Timestamp'
        },
        {
          data: 'Status',
          render: function(data, type) {
            if (type !== 'display') return data;
            return data === 'Normal' ?
              '<span class="badge badge-green">Normal</span>' :
              '<span class="badge badge-red">Abnormal</span>';
          }
        }
      ],

      columnDefs: [{
          targets: 0,
          visible: false
        }, // hide ID
        {
          targets: '_all',
          className: 'dt-left'
        }
      ],

      scrollX: true,
      scrollCollapse: true,
      responsive: false,
      autoWidth: true, // IMPORTANT: allow recalculation

      ordering: true,
      order: [
        [0, 'desc']
      ],
      paging: true,
      searching: true,
    });

    /* 🔑 THIS FIXES THE “STUCK WIDTH” ISSUE */
    function adjustTable() {
      table.columns.adjust().draw(false);
    }

    // On resize
    window.addEventListener('resize', adjustTable);

    // On sidebar/layout changes (just in case)
    setTimeout(adjustTable, 200);
  });
</script>