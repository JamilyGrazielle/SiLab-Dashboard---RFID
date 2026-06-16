# 🔐 Sistema RFID para Controle de Acesso dos Laboratórios SiLab

> Solução IoT para controle de acesso aos laboratórios gerenciados pelo SiLab utilizando tecnologia RFID e ESP32.

## 📌 Sobre o Projeto

O Sistema RFID para Controle de Acesso dos Laboratórios SiLab é uma solução desenvolvida para automatizar o processo de identificação e autorização de usuários nos laboratórios de informática do IFMA.

O sistema utiliza um leitor RFID conectado a um ESP32 para identificar cartões cadastrados e consultar suas permissões através do sistema SiLab. Após a validação, o dispositivo informa ao usuário se o acesso foi autorizado ou negado.

## 🎯 Objetivo

Implementar uma solução de controle de acesso baseada em RFID capaz de:

* Identificar usuários através de cartões RFID;
* Registrar acessos aos laboratórios;
* Integrar-se ao sistema SiLab;
* Aumentar a segurança dos ambientes;
* Automatizar o gerenciamento de entrada de usuários.

---

## ⚙️ Funcionamento

```text
Cartão RFID
      ↓
Leitor MFRC522
      ↓
ESP32
      ↓
Servidor SiLab
      ↓
Validação de Permissão
      ↓
Acesso Liberado ou Negado
```

---

## 🛠️ Tecnologias Utilizadas

### Hardware

* ESP32
* Módulo RFID MFRC522
* Display LCD I2C 16x2
* LED RGB
* Modulo Botão
* Protoboard
* Jumpers

### Software

* Arduino IDE
* C++
* PHP
* MySQL
* Git
* GitHub

---

## 📦 Bibliotecas Utilizadas

```cpp
MFRC522
SPI
WiFi
HTTPClient
Preferences
Wire
LiquidCrystal_I2C
WebServer
DNSServer
```

---

## ✅ Funcionalidades Implementadas

* [x] Leitura de cartões RFID
* [x] Conexão Wi-Fi utilizando ESP32
* [x] Comunicação HTTP com servidor
* [x] Display LCD para feedback ao usuário
* [x] LEDs de acesso autorizado e negado
* [x] Armazenamento persistente de configurações
* [x] Portal Web para configuração inicial
* [x] Configuração de laboratório associada ao dispositivo
* [x] Reset de fábrica do dispositivo

---

## 🚧 Funcionalidades em Desenvolvimento - Integração com funcionalidades do SILAB

* [ ] Registro de histórico de acessos
* [ ] Dashboard de monitoramento
* [ ] Controle de permissões por laboratório
* [ ] Controle de horários de acesso
* [ ] Cadastro de usuários via interface web

---

## 🔌 Componentes do Circuito

| Componente   | Função                            |
| ------------ | --------------------------------- |
| ESP32        | Controle principal do sistema     |
| MFRC522      | Leitura dos cartões RFID          |
| LCD I2C      | Exibição de mensagens             |
| LED Verde    | Indicação de acesso autorizado    |
| LED Vermelho | Indicação de acesso negado        |
| Botão Reset  | Reinicialização das configurações |

---

## 📂 Estrutura do Projeto

```text
RFID/
│
├── esp_RFID/
│   ├── esp_RFID.ino
│   └── Leitura_esp_RFID/
│
├── API PHP
│
├── Banco de Dados MySQL
│
└── Documentação
```

---

## 📸 Protótipo

Adicione aqui uma foto do protótipo em funcionamento.

```md
![Protótipo](imagens/prototipo.jpg)
```

---

## 📊 Status do Projeto

🚧 Projeto em desenvolvimento (Etapa 2)

Atualmente o sistema já realiza a leitura de cartões RFID, comunicação com servidor e validação de acesso. As próximas etapas envolvem a integração completa com o SiLab e o armazenamento do histórico de acessos.

---

## 👨‍💻 Autor

**Jamily Grazielle Sousa Maciel**

Bacharelado em Sistemas de Informação - IFMA

Projeto desenvolvido para a disciplina de Internet das Coisas (IoT).

---

## 📄 Licença

Projeto desenvolvido para fins acadêmicos.
