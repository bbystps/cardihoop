void setup() {
  Serial.begin(115200);          // better for fast streaming
  pinMode(10, INPUT);
  pinMode(11, INPUT);
}

void loop() {
  if (digitalRead(10) || digitalRead(11)) {
    Serial.println("!");         // leads off marker (plotter will drop it)
  } else {
    int raw = analogRead(A0);
    Serial.println(raw);         // one value per line
  }

  delay(4); // 2ms = 500 samples/sec
}
