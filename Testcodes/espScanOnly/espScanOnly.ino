#define ECG_PIN   36   // ADC1_CH0 (GPIO36) - input only
#define LO_PLUS   12
#define LO_MINUS  13

// Optional: ESP32 ADC is 12-bit by default (0..4095)
static const uint32_t sample_delay_ms_v = 4;   // ~250 samples/sec

void setup() {
  Serial.begin(115200);

  pinMode(LO_PLUS, INPUT);
  pinMode(LO_MINUS, INPUT);

  // ADC config (safe defaults)
  analogReadResolution(12);                // 0..4095
  analogSetPinAttenuation(ECG_PIN, ADC_11db); // better input range vs default
}

void loop() {
  if (digitalRead(LO_PLUS) || digitalRead(LO_MINUS)) {
    Serial.println("!");        // leads-off marker
  } else {
    int raw = analogRead(ECG_PIN);
    Serial.println(raw);        // one value per line
  }

  delay(sample_delay_ms_v);
}
