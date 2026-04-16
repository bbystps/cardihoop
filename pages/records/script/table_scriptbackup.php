<script>
  let scanRecordsTable = null;
  $(function() {
    window.scanRecordsTable = $('#scanRecordsTable').DataTable({
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
          targets: [0], // Hide ID and RecordID columns
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
      pageLength: 5,
      lengthMenu: [5, 10, 25, 50],
      searching: true,
    });

    function adjustTable() {
      window.scanRecordsTable.columns.adjust().draw(false);
    }

    window.addEventListener('resize', adjustTable);
    setTimeout(adjustTable, 200);
  });

  function reloadScanRecordsTable() {
    if (window.scanRecordsTable) {
      window.scanRecordsTable.ajax.reload(null, false);
    } else {
      console.warn("scanRecordsTable not initialized yet.");
    }
  }
</script>