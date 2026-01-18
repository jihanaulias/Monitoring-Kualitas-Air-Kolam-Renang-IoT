#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClient.h>
#include <SoftwareSerial.h>
//NodeMCU 0.9
// RX, TX (ESP side)
SoftwareSerial megaSerial(D5, D6); // D5 = RX, D6 = TX

// ================= WIFI =================
const char* WIFI_SSID = "alpha beta charlie";
const char* WIFI_PASS = "192837465";
//WIFI SSID dan PASS diubah sesuai dengan nama WIFI dan Passwordnya
// ================= SERVER ===============
const char* HOST = "10.188.43.14";
//HOST diubah sesuai dengan IP dari device yang akan menerima data dari ESP
const char* API_KEY = "RAHASIA123";
//diubah sesuai dengan API_KEY yang digunakan di ingest.php
const char* PATH_INGEST = "/iot/ingest.php";
const char* PATH_ACTIVE = "/iot/get_active_kolam.php";
// =======================================

WiFiClient client;

int getActiveKolam() {
  HTTPClient http;
  String url = String("http://") + HOST + PATH_ACTIVE;

  http.begin(client, url);
  int code = http.GET();
  String body = http.getString();
  http.end();

  Serial.print("GET ACTIVE ");
  Serial.print(code);
  Serial.print(" | ");
  Serial.println(body);

  body.trim();
  int id = body.toInt();
  return (id > 0) ? id : -1;
}

bool sendMeasurement(int idKolam, float ph, int tds, float temp, const String& statusAir) {
  //100126 add "const String& statusAir"
  HTTPClient http;

  String url = String("http://") + HOST + PATH_INGEST +
    "?key=" + API_KEY +
    "&id_kolam=" + String(idKolam) +
    "&ph=" + String(ph, 2) +
    "&tds=" + String(tds) +
    "&temp=" + String(temp, 2) +
    "&status=" + urlEncode(statusAir);

  Serial.println("SEND -> " + url);

  http.begin(client, url);
  int code = http.GET();
  String resp = http.getString();
  http.end();

  Serial.print("HTTP ");
  Serial.print(code);
  Serial.print(" | ");
  Serial.println(resp);

  return (code == 200 && resp.indexOf("OK") >= 0);
}

//100126 added url encoder | start
String urlEncode(const String& s) {
  String out;
  const char *hex = "0123456789ABCDEF";
  for (size_t i = 0; i < s.length(); i++) {
    uint8_t c = (uint8_t)s[i];
    // RFC3986 unreserved: A-Z a-z 0-9 - _ . ~
    if ((c >= 'A' && c <= 'Z') ||
        (c >= 'a' && c <= 'z') ||
        (c >= '0' && c <= '9') ||
        c == '-' || c == '_' || c == '.' || c == '~') {
      out += (char)c;
    } else {
      out += '%';
      out += hex[(c >> 4) & 0x0F];
      out += hex[c & 0x0F];
    }
  }
  return out;
}
//end

void setup() {
  Serial.begin(115200);      // DEBUG ke laptop
  megaSerial.begin(9600);    // DATA dari Arduino Mega

  WiFi.begin(WIFI_SSID, WIFI_PASS);
  Serial.print("Connecting WiFi");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  Serial.println("\nWiFi Connected");
  Serial.print("ESP IP: ");
  Serial.println(WiFi.localIP());
  Serial.println("Waiting CSV from Mega...");
}

void loop() {
  if (!megaSerial.available()) return;

  String line = megaSerial.readStringUntil('\n');
  line.trim();
  if (line.length() == 0) return;

  Serial.println("RX: " + line);

  int p1 = line.indexOf(',');
  int p2 = line.indexOf(',', p1 + 1);
  //100126 added p3
  int p3 = line.indexOf(',', p2 + 1);
  //100126 added p3 < 0
  if (p1 < 0 || p2 < 0 || p3 < 0) {
    Serial.println("FORMAT SALAH");
    return;
  }

  float ph   = line.substring(0, p1).toFloat();
  int   tds  = line.substring(p1 + 1, p2).toInt();
  //float temp = line.substring(p2 + 1).toFloat();
  float temp = line.substring(p2 + 1, p3).toFloat();
  //100126 added status air
  String statusAir = line.substring(p3 + 1);
  statusAir.trim();
  if(statusAir.length() == 0) statusAir = "UNKNOWN_FROM_ESP";

  int idKolam = getActiveKolam();
  if (idKolam <= 0) {
    Serial.println("Kolam aktif invalid");
    return;
  }

  sendMeasurement(idKolam, ph, tds, temp, statusAir);
  delay(300);
}