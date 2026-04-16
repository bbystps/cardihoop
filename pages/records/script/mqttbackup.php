<script>
    var client = new Messaging.Client("18.138.249.117", 9001, "myclientid_" + parseInt(Math.random() * 100, 10));

    client.onConnectionLost = function(responseObject) {
        toastr.error('Trying to reconnect...', 'Server not responding.', {
            closeButton: true,
            timeOut: 3000,
            progressBar: true
        });
        MQTTreconnect();
    };

    function MQTTreconnect() {
        if (client.connected) return;
        setTimeout(function() {
            client.connect(options);
        }, 5000);
    }

    var options = {
        timeout: 3,
        keepAliveInterval: 60,
        userName: 'mqttuser',
        password: 'mqttpass',
        onSuccess: function() {
            // Subscribe to BOTH request and result topics
            client.subscribe('Cardihoop/EcgScan', {
                qos: 0
            });
            client.subscribe('Cardihoop/ScanResult', {
                qos: 0
            });

            toastr.success('', 'Server OK!', {
                closeButton: true,
                timeOut: 500,
                progressBar: true
            });
        },
        onFailure: function() {
            toastr.error('Trying to reconnect...', 'Server not responding.', {
                closeButton: true,
                timeOut: 3000,
                progressBar: true
            });
            MQTTreconnect();
        }
    };

    // helper publish if needed
    function publish(payload, topic, qos) {
        var message = new Messaging.Message(payload);
        message.destinationName = topic;
        message.qos = qos || 0;
        client.send(message);
    }

    // ---- NEW: ScanResult handler hook ----
    window.lastScanResult = null; // for debugging / reuse

    client.onMessageArrived = function(message) {
        var topic = message.destinationName;
        var x = message.payloadString;
        console.log("MQTT RX:", topic, x);

        if (topic === "Cardihoop/ScanResult") {
            console.log("Received ScanResult:", x);
            try {
                var obj = JSON.parse(x); // ✅ parse JSON
                window.lastScanResult = obj; // ✅ store for debugging
                console.log("Parsed ScanResult:", obj);

                // ✅ populate + open modal
                populateSaveRecordModalFromScanResult(obj);

            } catch (e) {
                console.error("Invalid ScanResult JSON:", e, x);
                toastr.error("Received invalid ScanResult payload.", "Parse error", {
                    closeButton: true,
                    timeOut: 4000,
                    progressBar: true
                });
            }
            return;
        }

    };
</script>