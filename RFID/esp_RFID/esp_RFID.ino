#include <MFRC522.h>
#include <SPI.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <Preferences.h>

#define SS_PIN          21
#define RST_PIN         22
#define LED_VERDE       2
#define LED_VERMELHO    15
#define BOTAO_RESET     4

MFRC522 mfrc522(SS_PIN, RST_PIN);
Preferences preferences;

// ===============================
// CONFIGURAÇÕES
// ===============================

String ssid = "";
String password = "";
String serverIP = "";
String laboratorio = "";

String serverUrl = "";

// ===============================
// SETUP
// ===============================

void setup() {

  Serial.begin(115200);
  delay(1000);

  Serial.println("\n\n=== SISTEMA RFID SiLab ===");

  SPI.begin();
  mfrc522.PCD_Init();

  pinMode(LED_VERDE, OUTPUT);
  pinMode(LED_VERMELHO, OUTPUT);

  pinMode(BOTAO_RESET, INPUT_PULLUP);

  // ===============================
  // RESET DE FABRICA
  // ===============================

  if (digitalRead(BOTAO_RESET) == LOW) {

    Serial.println("\n⚠️ RESET DE FABRICA");

    preferences.begin("silab", false);

    preferences.clear();

    preferences.end();

    delay(2000);

    ESP.restart();
  }

  // ===============================
  // TESTE DOS LEDs
  // ===============================

  digitalWrite(LED_VERDE, HIGH);
  delay(300);
  digitalWrite(LED_VERDE, LOW);

  digitalWrite(LED_VERMELHO, HIGH);
  delay(300);
  digitalWrite(LED_VERMELHO, LOW);

  // ===============================
  // CARREGAR CONFIGURAÇÕES
  // ===============================

  preferences.begin("silab", false);

  ssid        = preferences.getString("ssid", "");
  password    = preferences.getString("password", "");
  serverIP    = preferences.getString("serverip", "");
  laboratorio = preferences.getString("lab", "");

  preferences.end();

  // ===============================
  // PRIMEIRA CONFIGURAÇÃO
  // ===============================

  if (
      ssid == "" ||
      password == "" ||
      serverIP == "" ||
      laboratorio == ""
  ) {

    configurarSistema();
  }

  // ===============================
  // MONTAR URL
  // ===============================

  serverUrl =
      "http://" +
      serverIP +
      "/api_rfid.php";

  // ===============================
  // MOSTRAR CONFIG
  // ===============================

  Serial.println("\n=== CONFIGURAÇÃO ===");

  Serial.print("LAB: ");
  Serial.println(laboratorio);

  Serial.print("WIFI: ");
  Serial.println(ssid);

  Serial.print("SERVIDOR: ");
  Serial.println(serverIP);

  conectarWiFi();

  Serial.println("\n✅ Sistema pronto!");
  Serial.println("Aproxime o cartão...");
  Serial.println("================================");
}

// ===============================
// LOOP
// ===============================

void loop() {

  // ===============================
  // BOTÃO RESET
  // ===============================

  if (digitalRead(BOTAO_RESET) == LOW) {

    Serial.println("\n⚠️ BOTÃO RESET PRESSIONADO");

    unsigned long tempoInicial = millis();

    // Espera segurando por 5 segundos
    while (digitalRead(BOTAO_RESET) == LOW) {

      if (millis() - tempoInicial >= 10000) {

        Serial.println("🗑️ APAGANDO CONFIGURAÇÕES...");

        preferences.begin("silab", false);

        preferences.clear();

        preferences.end();

        Serial.println("✅ RESET CONCLUÍDO");

        delay(2000);

        ESP.restart();
      }

      delay(100);
    }

    Serial.println("⏹️ RESET CANCELADO");
  }

  // ===============================
  // WIFI DESCONECTADO
  // ===============================

  if (WiFi.status() != WL_CONNECTED) {

    Serial.println("\n❌ WIFI DESCONECTADO");

    Serial.println("Deseja configurar nova rede?");
    Serial.println("Digite S para SIM");

    unsigned long inicio = millis();

    while (millis() - inicio < 10000) {

      if (Serial.available()) {

        String resposta =
            Serial.readStringUntil('\n');

        resposta.trim();
        resposta.toUpperCase();

        if (resposta == "S") {

          configurarSistema();

          serverUrl =
              "http://" +
              serverIP +
              "/api_rfid.php";

          conectarWiFi();
        }

        break;
      }

      delay(100);
    }

    conectarWiFi();
  }

  // ===============================
  // AGUARDAR CARTÃO
  // ===============================

  if (!mfrc522.PICC_IsNewCardPresent()) {

    delay(100);
    return;
  }

  if (!mfrc522.PICC_ReadCardSerial()) {
    return;
  }

  Serial.println("\n>>> CARTÃO DETECTADO <<<");

  String rfidCode = "";

  for (
      byte i = 0;
      i < mfrc522.uid.size;
      i++
  ) {

    if (mfrc522.uid.uidByte[i] < 0x10) {
      rfidCode += "0";
    }

    rfidCode +=
        String(
            mfrc522.uid.uidByte[i],
            HEX
        );
  }

  rfidCode.toUpperCase();

  Serial.print("RFID: ");
  Serial.println(rfidCode);

  // ===============================
  // ENVIAR PARA SERVIDOR
  // ===============================

  if (WiFi.status() == WL_CONNECTED) {

    enviarParaServidor(rfidCode);

  } else {

    Serial.println("⚠️ SEM WIFI");

    digitalWrite(LED_VERMELHO, HIGH);
    delay(1000);
    digitalWrite(LED_VERMELHO, LOW);
  }

  mfrc522.PICC_HaltA();

  delay(1500);
}

// ===============================
// CONFIGURAÇÃO
// ===============================

void configurarSistema() {

  Serial.println("\n=== CONFIGURAÇÃO INICIAL ===");

  Serial.println("Digite o nome da rede WiFi:");

  while (!Serial.available()) {
    delay(100);
  }

  ssid = Serial.readStringUntil('\n');
  ssid.trim();

  Serial.println("Digite a senha:");

  while (!Serial.available()) {
    delay(100);
  }

  password =
      Serial.readStringUntil('\n');

  password.trim();

  Serial.println("Digite o IP do servidor:");

  while (!Serial.available()) {
    delay(100);
  }

  serverIP =
      Serial.readStringUntil('\n');

  serverIP.trim();

  Serial.println("Digite o laboratório:");
  Serial.println("Exemplo: Lab 24");

  while (!Serial.available()) {
    delay(100);
  }

  laboratorio =
      Serial.readStringUntil('\n');

  laboratorio.trim();

  // ===============================
  // SALVAR
  // ===============================

  preferences.begin("silab", false);

  preferences.putString("ssid", ssid);
  preferences.putString("password", password);
  preferences.putString("serverip", serverIP);
  preferences.putString("lab", laboratorio);

  preferences.end();

  Serial.println("\n✅ CONFIGURAÇÃO SALVA");
}

// ===============================
// WIFI
// ===============================

void conectarWiFi() {

  Serial.println("\n📡 CONECTANDO WIFI...");

  WiFi.mode(WIFI_STA);

  WiFi.disconnect(true, true);

  WiFi.setSleep(false);

  delay(2000);

  WiFi.begin(
      ssid.c_str(),
      password.c_str()
  );

  int tentativas = 0;

  while (
      WiFi.status() != WL_CONNECTED &&
      tentativas < 40
  ) {

    delay(500);

    Serial.print(".");

    tentativas++;
  }

  Serial.println();

  if (WiFi.status() == WL_CONNECTED) {

    Serial.println("✅ WIFI CONECTADO");

    Serial.print("IP ESP: ");
    Serial.println(WiFi.localIP());

    digitalWrite(LED_VERDE, HIGH);
    delay(500);
    digitalWrite(LED_VERDE, LOW);

  } else {

    Serial.println("❌ FALHA WIFI");

    Serial.print("CÓDIGO: ");
    Serial.println(WiFi.status());

    digitalWrite(LED_VERMELHO, HIGH);
    delay(1000);
    digitalWrite(LED_VERMELHO, LOW);
  }
}

// ===============================
// SERVIDOR
// ===============================

void enviarParaServidor(String rfid) {

  HTTPClient http;

  String laboratorioURL = laboratorio;
  laboratorioURL.replace(" ", "%20");

  String url =
      serverUrl +
      "?rfid=" +
      rfid +
      "&laboratorio=" +
      laboratorioURL +
      "&action=check";

  Serial.println("\n📡 URL:");
  Serial.println(url);

  http.begin(url);

  http.setTimeout(5000);

  int httpCode = http.GET();

  Serial.print("📡 HTTP CODE: ");
  Serial.println(httpCode);

  if (httpCode == 200) {

    String resposta =
        http.getString();

    Serial.println("📡 RESPOSTA:");
    Serial.println(resposta);

    if (
        resposta.indexOf("\"success\":true")
        >= 0
    ) {

      Serial.println("✅ ACESSO LIBERADO");

      digitalWrite(LED_VERDE, HIGH);
      delay(1000);
      digitalWrite(LED_VERDE, LOW);

    } else {

      Serial.println("❌ ACESSO NEGADO");

      digitalWrite(LED_VERMELHO, HIGH);
      delay(1000);
      digitalWrite(LED_VERMELHO, LOW);
    }

  } else {

    Serial.println("❌ ERRO HTTP");

    digitalWrite(LED_VERMELHO, HIGH);
    delay(1000);
    digitalWrite(LED_VERMELHO, LOW);
  }

  http.end();
}