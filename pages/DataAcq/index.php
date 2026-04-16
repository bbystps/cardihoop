<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="../../plugins/toastr/toastr.min.css">
</head>

<script src="../../plugins/mqtt/mqttws31.js"></script>
<?php include("script/mqtt.php"); ?>

<body onload="client.connect(options);">

  <h1>Data Acquisition Site Using MQTT</h1>

  <button onclick="sendStartSittingScan()">Send Start Sitting Scan</button>
  <button onclick="sendStartStandingScan()">Send Start Standing Scan</button>
  <button onclick="sendStartWalkingScan()">Send Start Walking Scan</button>
  <button onclick="sendStartRunningScan()">Send Start Running Scan</button>

  <h3 id="ScanningStatus">--</h3>

  <script src="../../plugins/js/jquery.min.js"></script>
  <script src="../../plugins/toastr/toastr.min.js"></script>

  <script>
    function sendStartSittingScan() {
      publish('start_sitting_scan', 'Cardihoop/Command', 0);
      $('#ScanningStatus').text('Sitting Scan Started');
    }

    function sendStartStandingScan() {
      publish('start_standing_scan', 'Cardihoop/Command', 0);
      $('#ScanningStatus').text('Standing Scan Started');
    }

    function sendStartWalkingScan() {
      publish('start_walking_scan', 'Cardihoop/Command', 0);
      $('#ScanningStatus').text('Walking Scan Started');
    }

    function sendStartRunningScan() {
      publish('start_running_scan', 'Cardihoop/Command', 0);
      $('#ScanningStatus').text('Running Scan Started');
    }
  </script>


</body>

</html>