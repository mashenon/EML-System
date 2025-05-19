#include <SPI.h>
#include <MFRC522.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <EEPROM.h>
#include <TimeLib.h>

// Pins
#define RST_PIN     22
#define SS_PIN      4
#define RELAY_PIN   27
#define OVERRIDE_BTN 25
#define RED_LED     32
#define GREEN_LED   33
#define BUZZER_PIN  26

MFRC522 rfid(SS_PIN, RST_PIN);

// WiFi credentials
const char* ssid = "WOOOOOT";
const char* password = "Kurt0412";

// Server URLs
String serverUrl = "http://192.168.83.155/EMS/api/insert_uid_log.php";
String userSyncUrl = "http://192.168.83.155/EMS/api/get_users_json.php";
String lockStatusUrl = "http://192.168.83.155/EMS/api/update_lock_status.php";
String timeSyncUrl = "http://192.168.83.155/EMS/api/get_current_time.php";

// System state
bool isUnlocked = false;
unsigned long unlockTime = 0;
unsigned long lastSyncTime = 0;
const unsigned long syncInterval = 3600000; // 1 hour sync interval

// User data structure
struct User {
  String uid;
  String name;
  String schedule_start;
  String schedule_end;
};

User users[50];
int userCount = 0;
time_t currentTime = 0;

// WiFi connection function (ADDED THIS)
void connectToWiFi() {
  Serial.print("Connecting to WiFi");
  WiFi.begin(ssid, password);
  
  unsigned long startTime = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - startTime < 10000) {
    delay(500);
    Serial.print(".");
  }
  
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\nWiFi connected!");
    Serial.print("IP Address: ");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println("\nFailed to connect to WiFi. Using offline mode.");
  }
}

void setup() {
  Serial.begin(115200);
  SPI.begin();
  rfid.PCD_Init();

  // Initialize GPIO - relay first!
  pinMode(RELAY_PIN, OUTPUT);
  digitalWrite(RELAY_PIN, HIGH); // Start locked
  pinMode(OVERRIDE_BTN, INPUT_PULLUP);
  pinMode(RED_LED, OUTPUT);
  pinMode(GREEN_LED, OUTPUT);
  pinMode(BUZZER_PIN, OUTPUT);

  // Initialize EEPROM
  EEPROM.begin(4096);

  // Connect to WiFi
  connectToWiFi(); // Now this function exists

  // Load users from EEPROM
  loadUsersFromEEPROM();

  // Initial sync if connected
  if (WiFi.status() == WL_CONNECTED) {
    syncTimeWithServer();
    syncUsersWithServer();
  }

  Serial.println("System initialized - Door locked");
}

void loop() {
  // Handle override button
  if (digitalRead(OVERRIDE_BTN) == LOW) {
    Serial.println("Override Button Pressed!");
    unlockDoor();
    delay(500); // Debounce
  }

  // Handle periodic sync
  if (WiFi.status() == WL_CONNECTED && millis() - lastSyncTime > syncInterval) {
    syncTimeWithServer();
    syncUsersWithServer();
    lastSyncTime = millis();
  }

  // Update current time (simple implementation)
  currentTime += 1; // Increment by 1 second (you might want a better timekeeping method)

  // Handle RFID card scan
  if (rfid.PICC_IsNewCardPresent() && rfid.PICC_ReadCardSerial()) {
    handleRFIDScan();
    rfid.PICC_HaltA();
    rfid.PCD_StopCrypto1();
  }

  // Auto-lock after 5 seconds
  if (isUnlocked && millis() - unlockTime > 5000) {
    lockDoor();
  }

  delay(1000); // Delay for timekeeping (crude implementation)
}

// Time sync function
void syncTimeWithServer() {
  if (WiFi.status() != WL_CONNECTED) return;

  HTTPClient http;
  http.begin(timeSyncUrl);
  int httpCode = http.GET();
  
  if (httpCode == HTTP_CODE_OK) {
    String payload = http.getString();
    DynamicJsonDocument doc(200);
    DeserializationError error = deserializeJson(doc, payload);
    
    if (!error) {
      currentTime = doc["timestamp"];
      Serial.println("Time synced with server: " + String(currentTime));
    }
  }
  http.end();
}

void handleRFIDScan() {
  Serial.println("Card detected!");
  
  String uid = "";
  for (byte i = 0; i < rfid.uid.size; i++) {
    if (rfid.uid.uidByte[i] < 0x10) uid += "0";
    uid += String(rfid.uid.uidByte[i], HEX);
  }
  uid.toUpperCase();

  Serial.print("Card UID: ");
  Serial.println(uid);

  // First try online verification
  if (WiFi.status() == WL_CONNECTED) {
    bool access = checkServerAccess(uid);
    if (access) {
      unlockDoor();
      return;
    }
  }
  
  // Fallback to offline check
  if (checkLocalAccess(uid)) {
    unlockDoor();
  } else {
    accessDenied();
  }
}

bool checkServerAccess(String uid) {
  HTTPClient http;
  http.begin(serverUrl);
  http.addHeader("Content-Type", "application/json");

  String jsonPayload = "{\"uid\": \"" + uid + "\", \"location\": \"classroom-01\"}";
  int httpResponseCode = http.POST(jsonPayload);

  if (httpResponseCode > 0) {
    String response = http.getString();
    Serial.println("Server response: " + response);

    StaticJsonDocument<200> doc;
    DeserializationError error = deserializeJson(doc, response);

    if (!error) {
      String access = doc["access"];
      String reason = doc["reason"] | "";
      Serial.println("Access: " + access + ", Reason: " + reason);
      
      if (access == "granted") {
        http.end();
        return true;
      }
    }
  }
  http.end();
  return false;
}

bool checkLocalAccess(String uid) {
  for (int i = 0; i < userCount; i++) {
    if (users[i].uid == uid) {
      // Check if current time is within schedule
      int currentHour = hour(currentTime);
      int currentMinute = minute(currentTime);
      
      // Parse schedule start time (format HH:MM:SS)
      int startHour = users[i].schedule_start.substring(0, 2).toInt();
      int startMinute = users[i].schedule_start.substring(3, 5).toInt();
      
      // Parse schedule end time
      int endHour = users[i].schedule_end.substring(0, 2).toInt();
      int endMinute = users[i].schedule_end.substring(3, 5).toInt();
      
      // Convert to minutes since midnight for comparison
      int currentTotal = currentHour * 60 + currentMinute;
      int startTotal = startHour * 60 + startMinute;
      int endTotal = endHour * 60 + endMinute;
      
      if (currentTotal >= startTotal && currentTotal < endTotal) {
        Serial.println("Access granted - within schedule");
        return true;
      } else {
        Serial.println("Access denied - outside schedule");
        return false;
      }
    }
  }
  Serial.println("Access denied - UID not found");
  return false;
}

// Keep all your original door control functions exactly the same
void unlockDoor() {
  digitalWrite(RELAY_PIN, LOW);
  digitalWrite(GREEN_LED, HIGH);
  digitalWrite(RED_LED, LOW);
  buzz(2, 100);
  isUnlocked = true;
  unlockTime = millis();
  updateLockStatus("unlocked");
}

void lockDoor() {
  digitalWrite(RELAY_PIN, HIGH);
  digitalWrite(GREEN_LED, LOW);
  digitalWrite(RED_LED, LOW);
  isUnlocked = false;
  Serial.println("Door Locked.");
  updateLockStatus("locked");
}

void buzz(int times, int duration) {
  for (int i = 0; i < times; i++) {
    digitalWrite(BUZZER_PIN, HIGH);
    delay(duration);
    digitalWrite(BUZZER_PIN, LOW);
    delay(100);
  }
}

void accessDenied() {
  digitalWrite(RED_LED, HIGH);
  digitalWrite(GREEN_LED, LOW);
  buzz(3, 200);
  delay(500);
  digitalWrite(RED_LED, LOW);
}

void updateLockStatus(String status) {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(lockStatusUrl);
    http.addHeader("Content-Type", "application/json");

    String jsonPayload = "{\"location\":\"classroom-01\",\"status\":\"" + status + "\"}";
    int httpResponseCode = http.POST(jsonPayload);

    if (httpResponseCode > 0) {
      String response = http.getString();
      Serial.println("Lock status response: " + response);
    }
    http.end();
  }
}

// EEPROM functions (same as before)
void loadUsersFromEEPROM() {
  String input;
  int address = 0;
  char c;
  
  while((c = EEPROM.read(address)) != '\0' && address < 4096) {
    input += c;
    address++;
  }
  
  if (input.length() > 0) {
    DynamicJsonDocument doc(2048);
    DeserializationError error = deserializeJson(doc, input);
    
    if (!error) {
      userCount = 0;
      for (JsonObject user : doc.as<JsonArray>()) {
        if (userCount >= 50) break;
        users[userCount].uid = user["uid"].as<String>();
        users[userCount].name = user["name"].as<String>();
        users[userCount].schedule_start = user["schedule_start"].as<String>();
        users[userCount].schedule_end = user["schedule_end"].as<String>();
        userCount++;
      }
    }
  }
}

void syncUsersWithServer() {
  if (WiFi.status() != WL_CONNECTED) return;

  HTTPClient http;
  http.begin(userSyncUrl);
  int httpCode = http.GET();
  
  if (httpCode == HTTP_CODE_OK) {
    String payload = http.getString();
    DynamicJsonDocument doc(2048);
    DeserializationError error = deserializeJson(doc, payload);
    
    if (!error) {
      userCount = 0;
      for (JsonObject user : doc.as<JsonArray>()) {
        if (userCount >= 50) break;
        users[userCount].uid = user["uid"].as<String>();
        users[userCount].name = user["name"].as<String>();
        users[userCount].schedule_start = user["schedule_start"].as<String>();
        users[userCount].schedule_end = user["schedule_end"].as<String>();
        userCount++;
      }
      saveUsersToEEPROM();
    }
  }
  http.end();
}

void saveUsersToEEPROM() {
  DynamicJsonDocument doc(2048);
  for(int i = 0; i < userCount; i++) {
    JsonObject user = doc.createNestedObject();
    user["uid"] = users[i].uid;
    user["name"] = users[i].name;
    user["schedule_start"] = users[i].schedule_start;
    user["schedule_end"] = users[i].schedule_end;
  }
  
  String output;
  serializeJson(doc, output);
  
  for(size_t i = 0; i < output.length(); i++) {
    EEPROM.write(i, output[i]);
  }
  EEPROM.write(output.length(), '\0');
  EEPROM.commit();
}