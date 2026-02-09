#define ECG_PIN   36   // ADC1_CH0 (GPIO36) - input only
#define LO_PLUS   12
#define LO_MINUS  13

#define ECG_FS_HZ        250
#define ECG_SCAN_SEC     10
#define ECG_SAMPLES_PER_SCAN (ECG_FS_HZ * ECG_SCAN_SEC)

static const uint32_t sample_delay_ms_v = 4;   // ~250 Hz

uint16_t ecg_buffer[ECG_SAMPLES_PER_SCAN];
uint16_t ecg_index = 0;

typedef enum {
  ECG_IDLE,
  ECG_SCANNING,
  ECG_DONE
} ECG_State_e;

ECG_State_e ecg_state = ECG_IDLE;

void setup() {
  Serial.begin(115200);

  pinMode(LO_PLUS, INPUT);
  pinMode(LO_MINUS, INPUT);

  analogReadResolution(12);
  analogSetPinAttenuation(ECG_PIN, ADC_11db);

  Serial.println("ECG READY. Send 'START' to begin scan.");
}

void printEcgScan(void) {
  Serial.println("ECG_CSV_START");

  for (uint16_t i = 0; i < ECG_SAMPLES_PER_SCAN; i++) {
    Serial.print(ecg_buffer[i]);
    if (i < ECG_SAMPLES_PER_SCAN - 1) {
      Serial.print(",");   // CSV separator
    }
  }

  Serial.println();  // newline at end
  Serial.println("ECG_CSV_END");
}

void handleSerialCommand(void) {
  if (!Serial.available()) return;

  String cmd = Serial.readStringUntil('\n');
  cmd.trim();

  if (cmd.equalsIgnoreCase("START")) {
    if (ecg_state == ECG_IDLE) {
      ecg_index = 0;
      ecg_state = ECG_SCANNING;
      Serial.println("ECG SCAN STARTED (10 seconds)");
    } else {
      Serial.println("ECG BUSY");
    }
  }
}

void loop() {
  handleSerialCommand();

  if (ecg_state != ECG_SCANNING) {
    return;   // idle or done
  }

  // Leads off → abort scan
  if (digitalRead(LO_PLUS) || digitalRead(LO_MINUS)) {
    Serial.println("LEADS OFF - SCAN ABORTED");
    ecg_index = 0;
    ecg_state = ECG_IDLE;
    return;
  }

  ecg_buffer[ecg_index++] = analogRead(ECG_PIN);

  if (ecg_index >= ECG_SAMPLES_PER_SCAN) {
    ecg_state = ECG_DONE;
    Serial.println("ECG SCAN COMPLETE");
    printEcgScan();
    ecg_state = ECG_IDLE;   // ready for next START
    return;
  }

  delay(sample_delay_ms_v);
}
