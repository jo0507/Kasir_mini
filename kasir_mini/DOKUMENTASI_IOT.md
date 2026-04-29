# DOKUMENTASI IoT ABSENSI
## ESP32/ESP8266 + RFID/Fingerprint

---

## 📡 ARSITEKTUR SISTEM

```
[RFID Reader / Fingerprint Sensor]
            ↓
    [ESP32 / ESP8266]
            ↓
      [WiFi Network]
            ↓
   [Server PHP - api_absensi.php]
            ↓
    [MySQL Database]
```

---

## 🔧 HARDWARE YANG DIBUTUHKAN

### Opsi 1: RFID System
- ESP32 atau ESP8266 (NodeMCU)
- RFID RC522 Module
- Kartu RFID / Tag RFID
- Buzzer (optional)
- LED indicator (optional)
- Kabel jumper
- Power supply 5V

### Opsi 2: Fingerprint System
- ESP32 atau ESP8266
- Fingerprint Sensor (R307/R503)
- Buzzer (optional)
- LED indicator (optional)
- Kabel jumper
- Power supply 5V

### Opsi 3: Kombinasi (Recommended)
- ESP32 (memiliki lebih banyak pin)
- RFID RC522 Module
- Fingerprint Sensor
- LCD Display 16x2 (optional)
- Buzzer & LED
- Kabel jumper
- Power supply 5V

---

## 🔌 WIRING DIAGRAM

### RFID RC522 ke ESP8266/ESP32

**RFID RC522 → ESP8266 (NodeMCU)**
```
SDA  → D4 (GPIO2)
SCK  → D5 (GPIO14)
MOSI → D7 (GPIO13)
MISO → D6 (GPIO12)
GND  → GND
RST  → D3 (GPIO0)
3.3V → 3.3V
```

**RFID RC522 → ESP32**
```
SDA  → GPIO5
SCK  → GPIO18
MOSI → GPIO23
MISO → GPIO19
GND  → GND
RST  → GPIO22
3.3V → 3.3V
```

### Fingerprint Sensor → ESP32/ESP8266
```
VCC → 5V
GND → GND
TX  → RX (GPIO16 ESP8266 / GPIO16 ESP32)
RX  → TX (GPIO17 ESP8266 / GPIO17 ESP32)
```

---

## 💻 CODE ARDUINO (ESP8266 - RFID)

```cpp
#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <SPI.h>
#include <MFRC522.h>

// WiFi Credentials
const char* ssid = "YOUR_WIFI_SSID";
const char* password = "YOUR_WIFI_PASSWORD";

// Server URL
const char* serverUrl = "http://192.168.1.100/sistem_kasir/api_absensi.php";

// RFID Pins
#define RST_PIN D3
#define SS_PIN D4

// Optional: Buzzer & LED
#define BUZZER_PIN D8
#define LED_GREEN D1
#define LED_RED D2

MFRC522 rfid(SS_PIN, RST_PIN);
WiFiClient client;

void setup() {
  Serial.begin(115200);
  
  // Initialize pins
  pinMode(BUZZER_PIN, OUTPUT);
  pinMode(LED_GREEN, OUTPUT);
  pinMode(LED_RED, OUTPUT);
  
  // Initialize RFID
  SPI.begin();
  rfid.PCD_Init();
  
  // Connect to WiFi
  Serial.println("Connecting to WiFi...");
  WiFi.begin(ssid, password);
  
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  
  Serial.println("\nWiFi Connected!");
  Serial.println("IP: " + WiFi.localIP().toString());
  Serial.println("Ready to scan...");
  
  // Blink LED to indicate ready
  blinkLED(LED_GREEN, 3);
}

void loop() {
  // Check if new card is present
  if (!rfid.PICC_IsNewCardPresent())
    return;
  
  // Verify if the NUID has been read
  if (!rfid.PICC_ReadCardSerial())
    return;
  
  // Read UID
  String rfidUID = "";
  for (byte i = 0; i < rfid.uid.size; i++) {
    rfidUID += String(rfid.uid.uidByte[i] < 0x10 ? "0" : "");
    rfidUID += String(rfid.uid.uidByte[i], HEX);
  }
  rfidUID.toUpperCase();
  
  Serial.println("RFID UID: " + rfidUID);
  
  // Send to server
  sendAbsensi(rfidUID);
  
  // Halt PICC
  rfid.PICC_HaltA();
  rfid.PCD_StopCrypto1();
  
  delay(2000); // Prevent multiple scans
}

void sendAbsensi(String rfidUID) {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    
    http.begin(client, serverUrl);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");
    
    // Prepare POST data
    String postData = "rfid_uid=" + rfidUID + "&device_id=ESP8266_01";
    
    Serial.println("Sending to server...");
    int httpCode = http.POST(postData);
    
    if (httpCode > 0) {
      String response = http.getString();
      Serial.println("Response: " + response);
      
      // Parse JSON response (simple parsing)
      if (response.indexOf("\"success\":true") > 0) {
        // Success
        digitalWrite(LED_GREEN, HIGH);
        beep(2, 100);
        delay(1000);
        digitalWrite(LED_GREEN, LOW);
      } else {
        // Failed
        digitalWrite(LED_RED, HIGH);
        beep(1, 500);
        delay(1000);
        digitalWrite(LED_RED, LOW);
      }
    } else {
      Serial.println("Error: " + String(httpCode));
      digitalWrite(LED_RED, HIGH);
      beep(3, 200);
      delay(1000);
      digitalWrite(LED_RED, LOW);
    }
    
    http.end();
  } else {
    Serial.println("WiFi Disconnected!");
    blinkLED(LED_RED, 5);
  }
}

void beep(int times, int duration) {
  for (int i = 0; i < times; i++) {
    digitalWrite(BUZZER_PIN, HIGH);
    delay(duration);
    digitalWrite(BUZZER_PIN, LOW);
    delay(duration);
  }
}

void blinkLED(int pin, int times) {
  for (int i = 0; i < times; i++) {
    digitalWrite(pin, HIGH);
    delay(200);
    digitalWrite(pin, LOW);
    delay(200);
  }
}
```

---

## 💻 CODE ARDUINO (ESP32 - RFID + Fingerprint)

```cpp
#include <WiFi.h>
#include <HTTPClient.h>
#include <SPI.h>
#include <MFRC522.h>
#include <Adafruit_Fingerprint.h>

// WiFi Credentials
const char* ssid = "YOUR_WIFI_SSID";
const char* password = "YOUR_WIFI_PASSWORD";

// Server URL
const char* serverUrl = "http://192.168.1.100/sistem_kasir/api_absensi.php";

// RFID Pins
#define RST_PIN 22
#define SS_PIN 5

// Fingerprint Serial
HardwareSerial mySerial(2); // Use Serial2 on ESP32
Adafruit_Fingerprint finger = Adafruit_Fingerprint(&mySerial);

MFRC522 rfid(SS_PIN, RST_PIN);

void setup() {
  Serial.begin(115200);
  
  // Initialize RFID
  SPI.begin();
  rfid.PCD_Init();
  
  // Initialize Fingerprint
  mySerial.begin(57600, SERIAL_8N1, 16, 17); // RX=16, TX=17
  if (finger.verifyPassword()) {
    Serial.println("Fingerprint sensor found!");
  } else {
    Serial.println("Fingerprint sensor not found!");
  }
  
  // Connect to WiFi
  Serial.println("Connecting to WiFi...");
  WiFi.begin(ssid, password);
  
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  
  Serial.println("\nWiFi Connected!");
  Serial.println("Ready to scan RFID or Fingerprint...");
}

void loop() {
  // Check RFID
  if (rfid.PICC_IsNewCardPresent() && rfid.PICC_ReadCardSerial()) {
    String rfidUID = "";
    for (byte i = 0; i < rfid.uid.size; i++) {
      rfidUID += String(rfid.uid.uidByte[i] < 0x10 ? "0" : "");
      rfidUID += String(rfid.uid.uidByte[i], HEX);
    }
    rfidUID.toUpperCase();
    
    Serial.println("RFID: " + rfidUID);
    sendAbsensiRFID(rfidUID);
    
    rfid.PICC_HaltA();
    rfid.PCD_StopCrypto1();
    delay(2000);
  }
  
  // Check Fingerprint
  int fingerprintID = getFingerprintID();
  if (fingerprintID > 0) {
    Serial.println("Fingerprint ID: " + String(fingerprintID));
    sendAbsensiFingerprint(fingerprintID);
    delay(2000);
  }
  
  delay(50);
}

int getFingerprintID() {
  uint8_t p = finger.getImage();
  if (p != FINGERPRINT_OK) return -1;
  
  p = finger.image2Tz();
  if (p != FINGERPRINT_OK) return -1;
  
  p = finger.fingerFastSearch();
  if (p != FINGERPRINT_OK) return -1;
  
  return finger.fingerID;
}

void sendAbsensiRFID(String rfidUID) {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(serverUrl);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");
    
    String postData = "rfid_uid=" + rfidUID + "&device_id=ESP32_RFID_01";
    int httpCode = http.POST(postData);
    
    if (httpCode > 0) {
      Serial.println("Server Response: " + http.getString());
    }
    
    http.end();
  }
}

void sendAbsensiFingerprint(int fingerprintID) {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(serverUrl);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");
    
    String postData = "fingerprint_id=" + String(fingerprintID) + "&device_id=ESP32_FP_01";
    int httpCode = http.POST(postData);
    
    if (httpCode > 0) {
      Serial.println("Server Response: " + http.getString());
    }
    
    http.end();
  }
}
```

---

## 🔧 INSTALASI & KONFIGURASI

### 1. Install Library Arduino IDE
```
- ESP8266WiFi / WiFi (ESP32)
- ESP8266HTTPClient / HTTPClient (ESP32)
- SPI
- MFRC522 (by GithubCommunity)
- Adafruit Fingerprint Sensor Library
```

### 2. Konfigurasi WiFi
Ganti pada code:
```cpp
const char* ssid = "YOUR_WIFI_SSID";
const char* password = "YOUR_WIFI_PASSWORD";
```

### 3. Konfigurasi Server URL
Ganti IP dengan IP komputer server Anda:
```cpp
const char* serverUrl = "http://192.168.1.100/sistem_kasir/api_absensi.php";
```

Cara cek IP Windows:
```cmd
ipconfig
```

Cara cek IP Linux/Mac:
```bash
ifconfig
```

### 4. Upload Code
1. Pilih board ESP8266/ESP32
2. Pilih port COM
3. Upload code

---

## 📝 REGISTRASI RFID/FINGERPRINT

### Registrasi RFID:
1. Login sebagai admin
2. Buka menu "Pegawai"
3. Tambah/edit pegawai
4. Scan kartu RFID dengan reader
5. Copy UID yang muncul di Serial Monitor
6. Paste ke field "RFID UID" di form pegawai
7. Simpan

### Registrasi Fingerprint:
1. Upload code enrollment fingerprint ke ESP32
2. Ikuti instruksi di Serial Monitor
3. Catat ID fingerprint yang berhasil
4. Input ID tersebut ke field "Fingerprint ID" di form pegawai

---

## 🧪 TESTING

### Test API Langsung:
Buka Postman atau browser:
```
POST: http://localhost/sistem_kasir/api_absensi.php
Body: rfid_uid=A1B2C3D4&device_id=TEST_DEVICE
```

Response sukses:
```json
{
  "success": true,
  "message": "Absen MASUK berhasil! Selamat bekerja, Nama Pegawai",
  "data": {
    "nama": "Nama Pegawai",
    "nip": "NIP001",
    "action": "MASUK",
    "jam": "08:15:00",
    "status": "HADIR"
  },
  "timestamp": "2025-01-19 08:15:00"
}
```

---

## 🐛 TROUBLESHOOTING

### Device tidak connect ke WiFi:
- Cek SSID dan password
- Pastikan WiFi 2.4GHz (ESP tidak support 5GHz)
- Cek jarak ke router

### RFID tidak terbaca:
- Cek wiring
- Pastikan power supply cukup
- Jarak kartu ke reader max 3cm

### Server tidak merespon:
- Cek IP server benar
- Pastikan server dan device di network yang sama
- Cek firewall
- Pastikan XAMPP Apache dan MySQL running

### Absensi tidak masuk database:
- Cek RFID UID terdaftar di database
- Cek log_absensi_iot untuk error
- Pastikan pegawai status aktif

---

## ✅ CHECKLIST DEPLOYMENT

- [ ] Hardware sudah dirakit
- [ ] Code sudah diupload
- [ ] Device connect ke WiFi
- [ ] API endpoint bisa diakses
- [ ] RFID/Fingerprint terdaftar di database
- [ ] Test scan berhasil
- [ ] Data masuk ke database
- [ ] Buzzer dan LED berfungsi

---

## 📊 MONITORING

Log IoT Device tersimpan di tabel `log_absensi_iot`:
```sql
SELECT * FROM log_absensi_iot ORDER BY id DESC LIMIT 50;
```

Cek absensi hari ini:
```sql
SELECT * FROM absensi WHERE DATE(tanggal) = CURDATE();
```

---

**Sistem IoT Siap Digunakan!** 🚀
