#include <MFRC522.h>
#include <SPI.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <Preferences.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <WebServer.h>
#include <DNSServer.h>

#define SS_PIN          21
#define RST_PIN         22
#define LED_VERDE       2
#define LED_VERMELHO    15
#define BOTAO_RESET     4
#define SDA_PIN 16
#define SCL_PIN 17

MFRC522 mfrc522(SS_PIN, RST_PIN);
Preferences preferences;
LiquidCrystal_I2C lcd(0x27, 16, 2);
WebServer server(80);

bool modoConfiguracao = false;

String ssid = "";
String password = "";
String serverIP = "";
String laboratorio = "";

String serverUrl = "";

void setup() {

  Serial.begin(115200);
  delay(1000);

  Serial.println("\n\n=== SISTEMA RFID SiLab ===");

  Wire.begin(SDA_PIN, SCL_PIN);

  lcd.init();

  lcd.backlight();

  lcd.clear();

  lcd.setCursor(0, 0);
  lcd.print("SiLab RFID");

  lcd.setCursor(0, 1);
  lcd.print("Inicializando");

  SPI.begin();
  mfrc522.PCD_Init();

  pinMode(LED_VERDE, OUTPUT);
  pinMode(LED_VERMELHO, OUTPUT);

  pinMode(BOTAO_RESET, INPUT_PULLUP);

  if (digitalRead(BOTAO_RESET) == LOW) {

    Serial.println("\n RESET DE FABRICA");

    lcd.clear();

    lcd.setCursor(0, 0);
    lcd.print("RESET FABRICA");

    lcd.setCursor(0, 1);
    lcd.print("Apagando...");

    preferences.begin("silab", false);

    preferences.clear();

    preferences.end();

    delay(2000);

    ESP.restart();
  }

  digitalWrite(LED_VERDE, HIGH);
  delay(300);
  digitalWrite(LED_VERDE, LOW);

  digitalWrite(LED_VERMELHO, HIGH);
  delay(300);
  digitalWrite(LED_VERMELHO, LOW);

  preferences.begin("silab", false);

  ssid        = preferences.getString("ssid", "");
  password    = preferences.getString("password", "");
  serverIP    = preferences.getString("serverip", "");
  laboratorio = preferences.getString("lab", "");

  preferences.end();

  if (
      ssid == "" ||
      password == "" ||
      serverIP == "" ||
      laboratorio == ""
  ) {

    configurarSistema();
  }

  serverUrl =
      "http://" +
      serverIP +
      "/api_rfid.php";

  Serial.println("\n=== CONFIGURAÇÃO ===");

  Serial.print("LAB: ");
  Serial.println(laboratorio);

  Serial.print("WIFI: ");
  Serial.println(ssid);

  Serial.print("SERVIDOR: ");
  Serial.println(serverIP);

  conectarWiFi();

  telaPadrao();

  Serial.println("\nSistema pronto!");
  Serial.println("Aproxime o cartão...");
  Serial.println("================================");
}

void loop() {

  if (digitalRead(BOTAO_RESET) == LOW) {

    Serial.println("\nBOTÃO RESET PRESSIONADO");

    unsigned long tempoInicial = millis();

    while (digitalRead(BOTAO_RESET) == LOW) {

      if (millis() - tempoInicial >= 10000) {

        Serial.println("🗑️ APAGANDO CONFIGURAÇÕES...");

        preferences.begin("silab", false);

        preferences.clear();

        preferences.end();

        Serial.println("RESET CONCLUÍDO");

        delay(2000);

        ESP.restart();
      }

      delay(100);
    }

    Serial.println("⏹️ RESET CANCELADO");
  }

  /*if (WiFi.status() != WL_CONNECTED) {

    Serial.println("\nWIFI DESCONECTADO");

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
  }*/

  if (!mfrc522.PICC_IsNewCardPresent()) {

    delay(100);
    return;
  }

  if (!mfrc522.PICC_ReadCardSerial()) {
    return;
  }

  Serial.println("\n>>> CARTÃO DETECTADO <<<");

  lcd.clear();

  lcd.setCursor(0, 0);
  lcd.print("Cartao Lido");

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
  lcd.setCursor(0, 1);

  lcd.print(rfidCode);

  if (WiFi.status() == WL_CONNECTED) {

    enviarParaServidor(rfidCode);

  } else {

    Serial.println("SEM WIFI");

    digitalWrite(LED_VERMELHO, HIGH);
    delay(1000);
    digitalWrite(LED_VERMELHO, LOW);
  }

  mfrc522.PICC_HaltA();

  delay(1500);
}

void configurarSistema() {

  modoConfiguracao = true;

  WiFi.mode(WIFI_AP);

  WiFi.softAP(
      "SiLab_Config",
      "12345678"
  );

  IPAddress IP = WiFi.softAPIP();

  Serial.println("\n=== MODO CONFIG ===");

  Serial.print("IP: ");
  Serial.println(IP);

  lcd.clear();

  lcd.setCursor(0, 0);
  lcd.print("MODO CONFIG");

  lcd.setCursor(0, 1);
  lcd.print(IP.toString());

  server.on("/", HTTP_GET, []() {

    String pagina = R"rawliteral(

      <!DOCTYPE html>
      <html>

      <head>
        <meta charset="UTF-8">
        <title>SiLab Config</title>

        <style>

          body{
            font-family: Arial;
            margin:40px;
          }

          input{
            width:100%;
            padding:10px;
            margin-bottom:10px;
          }

          button{
            padding:10px;
            width:100%;
          }

        </style>

      </head>

      <body>

        <h2>Configuração SiLab</h2>

        <form action="/salvar">

          <label>WiFi</label>
          <input name="ssid">

          <label>Senha</label>
          <input name="password">

          <label>IP Servidor</label>
          <input name="serverip">

          <label>Laboratório</label>
          <input name="lab">

          <button type="submit">
            Salvar
          </button>

        </form>

      </body>

      </html>

    )rawliteral";

    server.send(200, "text/html", pagina);
  });

  server.on("/salvar", HTTP_GET, []() {

    ssid =
      server.arg("ssid");

    password =
      server.arg("password");

    serverIP =
      server.arg("serverip");

    laboratorio =
      server.arg("lab");

    preferences.begin("silab", false);

    preferences.putString("ssid", ssid);

    preferences.putString(
        "password",
        password
    );

    preferences.putString(
        "serverip",
        serverIP
    );

    preferences.putString(
        "lab",
        laboratorio
    );

    preferences.end();

    lcd.clear();

    lcd.setCursor(0, 0);
    lcd.print("CONFIG SALVA");

    lcd.setCursor(0, 1);
    lcd.print(laboratorio);

    server.send(
        200,
        "text/html",
        "<h1>Configuracao salva!</h1><h2>Reiniciando ESP...</h2>"
    );

    delay(3000);

    ESP.restart();
  });

  server.begin();

  Serial.println("Servidor iniciado");

  while (modoConfiguracao) {

    server.handleClient();

    delay(10);
  }
}

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

    Serial.println("WIFI CONECTADO");

    Serial.print("IP ESP: ");
    Serial.println(WiFi.localIP());

    lcd.clear();

    lcd.setCursor(0, 0);
    lcd.print(laboratorio);

    lcd.setCursor(0, 1);
    lcd.print("WiFi OK");

    digitalWrite(LED_VERDE, HIGH);
    delay(500);
    digitalWrite(LED_VERDE, LOW);

  } else {

    Serial.println("❌FALHA WIFI");

    Serial.print("CÓDIGO: ");
    Serial.println(WiFi.status());

    lcd.clear();

    lcd.setCursor(0, 0);
    lcd.print("ERRO WIFI");

    lcd.setCursor(0, 1);
    lcd.print(WiFi.status());

    digitalWrite(LED_VERMELHO, HIGH);
    delay(1000);
    digitalWrite(LED_VERMELHO, LOW);
  }
}

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

    if (resposta.indexOf("\"success\":true") >= 0) {
      lcd.clear();

      lcd.setCursor(0, 0);
      lcd.print("ACESSO OK");

      lcd.setCursor(0, 1);
      lcd.print(laboratorio);

      Serial.println("ACESSO LIBERADO");

      digitalWrite(LED_VERDE, HIGH);
      delay(1000);
      digitalWrite(LED_VERDE, LOW);

    } else {

      lcd.clear();

      lcd.setCursor(0, 0);
      lcd.print("ACESSO");

      lcd.setCursor(0, 1);
      lcd.print("NEGADO");

      Serial.println("ACESSO NEGADO");

      digitalWrite(LED_VERMELHO, HIGH);
      delay(1000);
      digitalWrite(LED_VERMELHO, LOW);
    }

  } else {

    lcd.clear();

    lcd.setCursor(0, 0);
    lcd.print("ERRO HTTP");

    lcd.setCursor(0, 1);
    lcd.print(httpCode);

    Serial.println("ERRO HTTP");

    digitalWrite(LED_VERMELHO, HIGH);
    delay(1000);
    digitalWrite(LED_VERMELHO, LOW);
  }

  delay(3000);

  lcd.clear();

  lcd.setCursor(0, 0);
  lcd.print(laboratorio);

  lcd.setCursor(0, 1);
  lcd.print("Aproxime RFID");

  http.end();
}

void telaPadrao() {

  lcd.clear();

  lcd.setCursor(0, 0);

  lcd.print(laboratorio);

  lcd.setCursor(0, 1);

  lcd.print("Aproxime RFID");
}