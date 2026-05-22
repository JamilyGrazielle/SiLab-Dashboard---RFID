#include <MFRC522.h>
#include <SPI.h>
#include <WiFi.h>
#include <HTTPClient.h>

#define SS_PIN    21
#define RST_PIN   22
#define LED_VERDE 12
#define LED_VERMELHO 32

// Configurações do WiFi
const char* ssid = "IFMA";
const char* password = "ifma1234";

// URL do servidor
const char* serverUrl = "http://192.168.137.44/api_rfid.php";

MFRC522 mfrc522(SS_PIN, RST_PIN);

void setup() {
  Serial.begin(115200);
  delay(1000);
  
  Serial.println("\n\n=== SISTEMA RFID SiLab ===");
  
  SPI.begin();
  mfrc522.PCD_Init();
  
  pinMode(LED_VERDE, OUTPUT);
  pinMode(LED_VERMELHO, OUTPUT);
  
  digitalWrite(LED_VERDE, HIGH);
  delay(300);
  digitalWrite(LED_VERDE, LOW);
  digitalWrite(LED_VERMELHO, HIGH);
  delay(300);
  digitalWrite(LED_VERMELHO, LOW);
  
  conectarWiFi();
  
  Serial.println("\n✅ Sistema pronto!");
  Serial.println("Aproxime o cartão do leitor...");
  Serial.println("================================\n");
}

void conectarWiFi() {

  Serial.println("\nConectando WiFi...");

  WiFi.mode(WIFI_STA);

  WiFi.disconnect(true, true);

  WiFi.setSleep(false);

  delay(2000);

  WiFi.begin(ssid, password);

  int tentativas = 0;

  while (WiFi.status() != WL_CONNECTED && tentativas < 40) {

    delay(500);

    Serial.print(".");

    tentativas++;
  }

  Serial.println();

  if (WiFi.status() == WL_CONNECTED) {

    Serial.println("✅ WIFI CONECTADO");
    Serial.print("IP: ");
    Serial.println(WiFi.localIP());

  } else {

    Serial.println("❌ FALHA WIFI");
    Serial.print("Código: ");
    Serial.println(WiFi.status());
  }
}

void loop() {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("📡 WiFi desconectado! Tentando reconectar...");
    conectarWiFi();
  }
  
  if (!mfrc522.PICC_IsNewCardPresent()) {
    delay(100);
    return;
  }
  
  if (!mfrc522.PICC_ReadCardSerial()) {
    return;
  }
  
  Serial.println("\n>>> Cartão detectado! <<<");
  
  String rfidCode = "";
  for (byte i = 0; i < mfrc522.uid.size; i++) {
    if (mfrc522.uid.uidByte[i] < 0x10) {
      rfidCode += "0";
    }
    rfidCode += String(mfrc522.uid.uidByte[i], HEX);
  }
  rfidCode.toUpperCase();
  
  Serial.print("📌 CÓDIGO RFID: ");
  Serial.println(rfidCode);
  
  if (WiFi.status() == WL_CONNECTED) {
    enviarParaServidor(rfidCode);
  } else {
    Serial.println("⚠️ Sem WiFi - Acesso não registrado!");
    digitalWrite(LED_VERMELHO, HIGH);
    delay(1000);
    digitalWrite(LED_VERMELHO, LOW);
  }
  
  mfrc522.PICC_HaltA();
  delay(1500);
}

void enviarParaServidor(String rfid) {
  HTTPClient http;
  
  // CORREÇÃO: Espaço codificado como %20
  String url = String(serverUrl) + "?rfid=" + rfid + "&laboratorio=Lab%2024&action=check";
  
  Serial.print("📡 Enviando para: ");
  Serial.println(url);
  
  http.begin(url);
  http.setTimeout(5000);
  
  int httpCode = http.GET();
  
  Serial.print("📡 Resposta HTTP: ");
  Serial.println(httpCode);
  
  if (httpCode == 200) {
    String resposta = http.getString();
    Serial.print("📡 Resposta: ");
    Serial.println(resposta);
    
    if (resposta.indexOf("\"success\":true") > 0) {
      Serial.println("✅ ACESSO LIBERADO!");
      digitalWrite(LED_VERDE, HIGH);
      delay(1000);
      digitalWrite(LED_VERDE, LOW);
    } else {
      Serial.println("❌ ACESSO NEGADO!");
      digitalWrite(LED_VERMELHO, HIGH);
      delay(1000);
      digitalWrite(LED_VERMELHO, LOW);
    }
  } else {
    Serial.println("❌ Erro na comunicação com o servidor!");
    digitalWrite(LED_VERMELHO, HIGH);
    delay(1000);
    digitalWrite(LED_VERMELHO, LOW);
  }
  
  http.end();
}