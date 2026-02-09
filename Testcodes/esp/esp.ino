#include <WiFi.h>
#include <PubSubClient.h>

#define ECG_PIN   36   // ADC1_CH0 (GPIO36) - input only
#define LO_PLUS   12
#define LO_MINUS  13

#define ECG_FS_HZ        250
#define ECG_SCAN_SEC     10
#define ECG_SAMPLES_PER_SCAN (ECG_FS_HZ * ECG_SCAN_SEC)

static const uint32_t sample_delay_ms_v = 4;   // ~250 Hz

// ------------------- WiFi/MQTT CONFIG -------------------
static const char *wifi_ssid_v     = "PLDTinnov";
static const char *wifi_pass_v     = "Password12345!";

static const char *mqtt_host_v     = "13.214.212.87";
static const uint16_t mqtt_port_v  = 1883;
static const char *mqtt_user_v     = "mqtt";
static const char *mqtt_pass_v     = "ICPHmqtt!";

static const char *mqtt_topic_cmd_v    = "ecg/cmd";
static const char *mqtt_topic_status_v = "ecg/status";
static const char *mqtt_topic_data_v   = "ecg/data";

// Binary chunk size (samples per MQTT message)
static const uint16_t mqtt_chunk_samples_v = 200; // 200 samples => ~412 bytes payload
// --------------------------------------------------------

WiFiClient wifi_client;
PubSubClient mqtt_client(wifi_client);

uint16_t ecg_buffer[ECG_SAMPLES_PER_SCAN];
uint16_t ecg_index = 0;

static uint32_t scan_id_v = 0;

typedef enum {
  ECG_IDLE,
  ECG_SCANNING,
  ECG_DONE
} ECG_State_e;

ECG_State_e ecg_state = ECG_IDLE;

// Forward declaration (needed because mqttPublishChecked uses mqttConnect)
static void mqttConnect(void);

// ---------- Helpers ----------
static void mqttPublishStatus(const char *msg_v) {
  Serial.println(msg_v);
  if (mqtt_client.connected()) {
    mqtt_client.publish(mqtt_topic_status_v, msg_v, true);
  }
}

static bool mqttPublishChecked(const char *topic_v, const uint8_t *data_v, uint16_t len_v) {
  if (!mqtt_client.connected()) {
    mqttConnect();
  }

  bool ok_v = mqtt_client.publish(topic_v, data_v, len_v);
  if (!ok_v) {
    Serial.print("MQTT publish failed, state=");
    Serial.println(mqtt_client.state());
  }
  return ok_v;
}

static bool mqttPublishCheckedText(const char *topic_v, const char *text_v) {
  return mqttPublishChecked(topic_v, (const uint8_t *)text_v, (uint16_t)strlen(text_v));
}

// ---------- ECG ----------
static void startScan(void) {
  if (ecg_state != ECG_IDLE) {
    mqttPublishStatus("ECG_BUSY");
    return;
  }

  ecg_index = 0;
  ecg_state = ECG_SCANNING;
  mqttPublishStatus("SCAN_STARTED");
  Serial.println("ECG SCAN STARTED (10 seconds)");
}

static void publishEcgScanBinary(void) {
  // Ensure connected before starting burst
  if (!mqtt_client.connected()) {
    mqttConnect();
  }

  // 1) start message (text)
  scan_id_v++;

  char start_msg_v[96];
  snprintf(start_msg_v, sizeof(start_msg_v),
           "SCAN_START,%lu,%u,%u",
           (unsigned long)scan_id_v,
           (unsigned)ECG_FS_HZ,
           (unsigned)ECG_SAMPLES_PER_SCAN);

  mqttPublishCheckedText(mqtt_topic_data_v, start_msg_v);
  mqtt_client.loop();
  delay(30);

  // 2) binary chunks: 12-byte header + (count * 2 bytes)
  static uint8_t bin_buf_v[12 + (mqtt_chunk_samples_v * 2)];

  for (uint16_t i = 0; i < ECG_SAMPLES_PER_SCAN; i += mqtt_chunk_samples_v) {
    uint16_t count_v = mqtt_chunk_samples_v;
    if ((uint32_t)i + (uint32_t)count_v > (uint32_t)ECG_SAMPLES_PER_SCAN) {
      count_v = (uint16_t)(ECG_SAMPLES_PER_SCAN - i);
    }

    // Header
    bin_buf_v[0] = 'E';
    bin_buf_v[1] = 'C';
    bin_buf_v[2] = 0x01;  // type: data
    bin_buf_v[3] = 0x00;  // reserved

    // scan_id (uint32 little-endian)
    uint32_t sid_v = scan_id_v;
    bin_buf_v[4] = (uint8_t)(sid_v & 0xFF);
    bin_buf_v[5] = (uint8_t)((sid_v >> 8) & 0xFF);
    bin_buf_v[6] = (uint8_t)((sid_v >> 16) & 0xFF);
    bin_buf_v[7] = (uint8_t)((sid_v >> 24) & 0xFF);

    // start_index (uint16 little-endian)
    bin_buf_v[8]  = (uint8_t)(i & 0xFF);
    bin_buf_v[9]  = (uint8_t)((i >> 8) & 0xFF);

    // sample_count (uint16 little-endian)
    bin_buf_v[10] = (uint8_t)(count_v & 0xFF);
    bin_buf_v[11] = (uint8_t)((count_v >> 8) & 0xFF);

    // Samples
    uint16_t off_v = 12;
    for (uint16_t k = 0; k < count_v; k++) {
      uint16_t s_v = ecg_buffer[i + k];
      bin_buf_v[off_v++] = (uint8_t)(s_v & 0xFF);
      bin_buf_v[off_v++] = (uint8_t)((s_v >> 8) & 0xFF);
    }

    // Publish
    bool ok_v = mqttPublishChecked(mqtt_topic_data_v, bin_buf_v, off_v);
    if (!ok_v) {
      // Back off and try to recover
      delay(200);
      mqttConnect();
    }

    // Throttle to avoid flooding and keep connection alive
    mqtt_client.loop();
    delay(30);
  }

  // 3) end message (text)
  char end_msg_v[48];
  snprintf(end_msg_v, sizeof(end_msg_v), "SCAN_END,%lu", (unsigned long)scan_id_v);
  mqttPublishCheckedText(mqtt_topic_data_v, end_msg_v);
  mqtt_client.loop();
  delay(30);
}

// ---------- MQTT callback ----------
static void mqttCallback(char *topic, byte *payload, unsigned int length) {
  char msg_v[64];
  unsigned int n_v = (length < (sizeof(msg_v) - 1)) ? length : (sizeof(msg_v) - 1);
  memcpy(msg_v, payload, n_v);
  msg_v[n_v] = '\0';

  String cmd = String(msg_v);
  cmd.trim();

  if (String(topic) == mqtt_topic_cmd_v) {
    if (cmd.equalsIgnoreCase("START")) {
      startScan();
    } else {
      mqttPublishStatus("UNKNOWN_CMD");
    }
  }
}

// ---------- WiFi/MQTT connect ----------
static void wifiConnect(void) {
  WiFi.mode(WIFI_STA);
  WiFi.begin(wifi_ssid_v, wifi_pass_v);

  Serial.print("WiFi connecting");
  while (WiFi.status() != WL_CONNECTED) {
    delay(300);
    Serial.print(".");
  }
  Serial.println();
  Serial.print("WiFi connected: ");
  Serial.println(WiFi.localIP());
}

static void mqttConnect(void) {
  mqtt_client.setServer(mqtt_host_v, mqtt_port_v);
  mqtt_client.setCallback(mqttCallback);

  while (!mqtt_client.connected()) {
    String client_id_v = "ESP32_ECG_" + String((uint32_t)ESP.getEfuseMac(), HEX);

    Serial.print("MQTT connecting as ");
    Serial.println(client_id_v);

    bool ok_v = mqtt_client.connect(client_id_v.c_str(), mqtt_user_v, mqtt_pass_v);

    if (ok_v) {
      Serial.println("MQTT connected");
      mqtt_client.subscribe(mqtt_topic_cmd_v);
      mqttPublishStatus("MQTT_READY");
      mqtt_client.loop();
      delay(50);
    } else {
      Serial.print("MQTT connect failed, rc=");
      Serial.println(mqtt_client.state());
      delay(1000);
    }
  }
}

// ---------- Serial command ----------
static void handleSerialCommand(void) {
  if (!Serial.available()) return;

  String cmd = Serial.readStringUntil('\n');
  cmd.trim();

  if (cmd.equalsIgnoreCase("START")) {
    startScan();
  }
}

// ---------- Setup/Loop ----------
void setup() {
  Serial.begin(115200);

  pinMode(LO_PLUS, INPUT);
  pinMode(LO_MINUS, INPUT);

  analogReadResolution(12);
  analogSetPinAttenuation(ECG_PIN, ADC_11db);

  wifiConnect();

  // Increase PubSubClient buffer (if your version supports it)
  // 1024 is enough for 200-sample binary chunks (~412 bytes).
  mqtt_client.setBufferSize(1024);

  mqttConnect();

  Serial.println("ECG READY. Send 'START' over Serial or publish 'START' to ecg/cmd.");
}

void loop() {
  // keep MQTT alive
  if (!mqtt_client.connected()) {
    mqttConnect();
  }
  mqtt_client.loop();

  // allow serial trigger too
  handleSerialCommand();

  if (ecg_state != ECG_SCANNING) {
    return;
  }

  // Leads off → abort scan
  if (digitalRead(LO_PLUS) || digitalRead(LO_MINUS)) {
    mqttPublishStatus("LEADS_OFF_ABORT");
    Serial.println("LEADS OFF - SCAN ABORTED");
    ecg_index = 0;
    ecg_state = ECG_IDLE;
    return;
  }

  ecg_buffer[ecg_index++] = analogRead(ECG_PIN);

  if (ecg_index >= ECG_SAMPLES_PER_SCAN) {
    ecg_state = ECG_DONE;
    mqttPublishStatus("SCAN_DONE");

    Serial.println("ECG SCAN COMPLETE");

    // Publish through MQTT (binary)
    publishEcgScanBinary();

    ecg_state = ECG_IDLE;
    mqttPublishStatus("READY");
    return;
  }

  delay(sample_delay_ms_v);
}
