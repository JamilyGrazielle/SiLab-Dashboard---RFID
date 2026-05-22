-- =====================================================
-- SCRIPT COMPLETO PARA CONFIGURAR O SiLab
-- Execute TODOS os comandos em ordem
-- =====================================================

-- 1. DROP e CREATE do banco
DROP DATABASE IF EXISTS silab;
CREATE DATABASE IF NOT EXISTS silab;
USE silab;

-- =====================================================
-- 2. CRIAR TABELAS (sem constraints de FK que dependem de usuario)
-- =====================================================

-- Tabela usuario (primeiro, pois é referenciada por outras)
CREATE TABLE usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_completo VARCHAR(200) NOT NULL,
    matricula VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    perfil ENUM('professor', 'administrador', 'root') NOT NULL DEFAULT 'professor',
    status ENUM('aprovado', 'pendente', 'inativo') NOT NULL DEFAULT 'pendente',
    rfid VARCHAR(50) NULL UNIQUE,
    data_cadastro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_ultimo_login DATETIME NULL,
    criado_por INT NULL,
    INDEX idx_matricula (matricula),
    INDEX idx_status (status),
    INDEX idx_perfil (perfil),
    INDEX idx_rfid (rfid),
    FOREIGN KEY (criado_por) REFERENCES usuario(id) ON DELETE SET NULL
);

-- Tabela laboratorio (agora pode referenciar usuario)
CREATE TABLE laboratorio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    capacidade INT NOT NULL CHECK (capacidade > 0),
    status_laboratorio ENUM('ativo', 'em_manutencao') NOT NULL DEFAULT 'ativo',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    criado_por INT NOT NULL,
    FOREIGN KEY (criado_por) REFERENCES usuario(id)
);

-- Tabela equipamento
CREATE TABLE equipamento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    laboratorio_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    quantidade INT NOT NULL CHECK (quantidade > 0),
    FOREIGN KEY (laboratorio_id) REFERENCES laboratorio(id) ON DELETE CASCADE,
    INDEX idx_laboratorio (laboratorio_id)
);

-- Tabela reserva
CREATE TABLE reserva (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    laboratorio_id INT NOT NULL,
    data_reserva DATE NOT NULL,
    horario_inicio TIME NOT NULL,
    horario_fim TIME NOT NULL,
    finalidade TEXT NOT NULL,
    numero_alunos INT NULL,
    protocolo VARCHAR(50) NOT NULL UNIQUE,
    criada_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_professor (professor_id),
    INDEX idx_laboratorio (laboratorio_id),
    INDEX idx_data (data_reserva),
    INDEX idx_protocolo (protocolo),
    INDEX idx_agenda (laboratorio_id, data_reserva, horario_inicio),
    FOREIGN KEY (professor_id) REFERENCES usuario(id),
    FOREIGN KEY (laboratorio_id) REFERENCES laboratorio(id),
    CONSTRAINT chk_horario CHECK (horario_inicio < horario_fim),
    CONSTRAINT chk_nao_conflito UNIQUE KEY (laboratorio_id, data_reserva, horario_inicio, horario_fim)
);

-- Tabela reserva_cancelada
CREATE TABLE reserva_cancelada (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reserva_original_id INT NOT NULL,
    professor_id INT NOT NULL,
    laboratorio_id INT NOT NULL,
    data_reserva DATE NOT NULL,
    horario_inicio TIME NOT NULL,
    horario_fim TIME NOT NULL,
    finalidade TEXT NOT NULL,
    numero_alunos INT NULL,
    protocolo VARCHAR(50) NOT NULL,
    criada_em DATETIME NOT NULL,
    cancelada_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    cancelado_por INT NOT NULL,
    motivo_cancelamento TEXT NOT NULL,
    INDEX idx_professor (professor_id),
    INDEX idx_laboratorio (laboratorio_id),
    INDEX idx_data_reserva (data_reserva),
    INDEX idx_cancelada_em (cancelada_em),
    INDEX idx_protocolo (protocolo),
    FOREIGN KEY (professor_id) REFERENCES usuario(id),
    FOREIGN KEY (laboratorio_id) REFERENCES laboratorio(id),
    FOREIGN KEY (cancelado_por) REFERENCES usuario(id)
);

-- Tabela bloqueio_manutencao
CREATE TABLE bloqueio_manutencao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    laboratorio_id INT NOT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE NOT NULL,
    motivo TEXT NOT NULL,
    status ENUM('ativo', 'encerrado') NOT NULL DEFAULT 'ativo',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    criado_por INT NOT NULL,
    INDEX idx_laboratorio (laboratorio_id),
    INDEX idx_periodo (data_inicio, data_fim),
    INDEX idx_status (status),
    FOREIGN KEY (laboratorio_id) REFERENCES laboratorio(id),
    FOREIGN KEY (criado_por) REFERENCES usuario(id),
    CONSTRAINT chk_bloqueio_periodo CHECK (data_inicio <= data_fim),
    CONSTRAINT chk_bloqueio_30dias CHECK (DATEDIFF(data_fim, data_inicio) <= 30)
);

-- Tabela solicitacao_cadastro
CREATE TABLE solicitacao_cadastro (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_completo VARCHAR(200) NOT NULL,
    matricula VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    status ENUM('pendente', 'aprovada', 'rejeitada') NOT NULL DEFAULT 'pendente',
    justificativa_rejeicao TEXT NULL,
    data_solicitacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processada_em DATETIME NULL,
    processada_por INT NULL,
    INDEX idx_status (status),
    INDEX idx_matricula (matricula),
    INDEX idx_email (email),
    FOREIGN KEY (processada_por) REFERENCES usuario(id) ON DELETE SET NULL
);

-- Tabela log_sistema
CREATE TABLE log_sistema (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    acao VARCHAR(100) NOT NULL,
    entidade VARCHAR(50) NOT NULL,
    entidade_id INT NULL,
    detalhes TEXT NULL,
    ip_origem VARCHAR(45) NULL,
    data_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario_id),
    INDEX idx_entidade (entidade, entidade_id),
    INDEX idx_data_hora (data_hora),
    FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE SET NULL
);

-- Tabela sessao
CREATE TABLE sessao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    token_sessao VARCHAR(255) NOT NULL UNIQUE,
    data_inicio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_ultima_atividade DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_expiracao DATETIME NOT NULL,
    ip_origem VARCHAR(45) NULL,
    user_agent TEXT NULL,
    ativa BOOLEAN NOT NULL DEFAULT TRUE,
    INDEX idx_usuario (usuario_id),
    INDEX idx_token (token_sessao),
    INDEX idx_expiracao (data_expiracao),
    INDEX idx_ativa (ativa),
    FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE CASCADE
);

-- Tabela notificacao
CREATE TABLE notificacao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo ENUM('reserva_confirmada', 'reserva_cancelada', 'cadastro_aprovado', 
              'cadastro_rejeitado', 'manutencao_criada', 'conta_alterada') NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    mensagem TEXT NOT NULL,
    lida BOOLEAN NOT NULL DEFAULT FALSE,
    data_envio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    email_enviado BOOLEAN NOT NULL DEFAULT FALSE,
    INDEX idx_usuario (usuario_id),
    INDEX idx_lida (lida),
    INDEX idx_data_envio (data_envio),
    FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE CASCADE
);

CREATE TABLE disciplina (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) UNIQUE NOT NULL,
    nome VARCHAR(255) NOT NULL
);

CREATE TABLE professor_disciplina (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    disciplina_id INT NOT NULL,

    FOREIGN KEY (professor_id) REFERENCES usuario(id),
    FOREIGN KEY (disciplina_id) REFERENCES disciplina(id)
);

CREATE TABLE reserva_laboratorio (
    id INT AUTO_INCREMENT PRIMARY KEY,

    professor_id INT NOT NULL,
    disciplina_id INT NOT NULL,
    laboratorio_id INT NOT NULL,

    dia_semana ENUM(
        'segunda',
        'terca',
        'quarta',
        'quinta',
        'sexta',
        'sabado'
    ) NOT NULL,

    horario_inicio TIME NOT NULL,
    horario_fim TIME NOT NULL,

    status ENUM('ativa', 'cancelada') DEFAULT 'ativa',

    FOREIGN KEY (professor_id) REFERENCES usuario(id),
    FOREIGN KEY (disciplina_id) REFERENCES disciplina(id),
    FOREIGN KEY (laboratorio_id) REFERENCES laboratorio(id)
);

/*-- =====================================================
-- 4. INSERIR LABORATÓRIOS (agora com criado_por = 1 que existe)
-- =====================================================
INSERT INTO laboratorio (nome, capacidade, criado_por) VALUES
('Lab 24', 30, 1),
('Lab 25', 30, 1),
('Lab 27', 30, 1);
*/

-- =====================================================
-- 5. TRIGGERS
-- =====================================================
DELIMITER //

CREATE TRIGGER gerar_protocolo_reserva
BEFORE INSERT ON reserva
FOR EACH ROW
BEGIN
    IF NEW.protocolo IS NULL OR NEW.protocolo = '' THEN
        SET NEW.protocolo = CONCAT('RES-', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'), '-', LPAD(FLOOR(RAND() * 10000), 4, '0'));
    END IF;
END//

CREATE TRIGGER mover_reserva_para_cancelada
BEFORE DELETE ON reserva
FOR EACH ROW
BEGIN
    INSERT INTO reserva_cancelada (
        reserva_original_id, professor_id, laboratorio_id, 
        data_reserva, horario_inicio, horario_fim, 
        finalidade, numero_alunos, protocolo, criada_em,
        cancelada_em, cancelado_por, motivo_cancelamento
    ) VALUES (
        OLD.id, OLD.professor_id, OLD.laboratorio_id,
        OLD.data_reserva, OLD.horario_inicio, OLD.horario_fim,
        OLD.finalidade, OLD.numero_alunos, OLD.protocolo, OLD.criada_em,
        NOW(), @cancelado_por, @motivo_cancelamento
    );
END//

DELIMITER ;

-- =====================================================
-- 6. VIEWS
-- =====================================================
CREATE VIEW view_agenda_semanal AS
SELECT 
    r.id AS reserva_id,
    l.nome AS laboratorio,
    l.id AS laboratorio_id,
    u.nome_completo AS professor,
    r.data_reserva,
    r.horario_inicio,
    r.horario_fim,
    r.finalidade,
    'confirmada' AS status
FROM reserva r
JOIN laboratorio l ON r.laboratorio_id = l.id
JOIN usuario u ON r.professor_id = u.id
WHERE l.status_laboratorio = 'ativo'
  AND r.data_reserva >= CURDATE();

CREATE VIEW view_historico_cancelamentos AS
SELECT 
    rc.id,
    rc.protocolo,
    u.nome_completo AS professor,
    l.nome AS laboratorio,
    rc.data_reserva,
    rc.horario_inicio,
    rc.horario_fim,
    rc.finalidade,
    rc.cancelada_em,
    rc.motivo_cancelamento,
    c.nome_completo AS cancelado_por
FROM reserva_cancelada rc
JOIN usuario u ON rc.professor_id = u.id
JOIN laboratorio l ON rc.laboratorio_id = l.id
JOIN usuario c ON rc.cancelado_por = c.id
ORDER BY rc.cancelada_em DESC;

CREATE VIEW view_laboratorios_disponiveis AS
SELECT 
    l.id,
    l.nome,
    l.capacidade
FROM laboratorio l
WHERE l.status_laboratorio = 'ativo'
  AND NOT EXISTS (
      SELECT 1 FROM reserva r 
      WHERE r.laboratorio_id = l.id 
        AND r.data_reserva >= CURDATE()
  )
  AND NOT EXISTS (
      SELECT 1 FROM bloqueio_manutencao b 
      WHERE b.laboratorio_id = l.id 
        AND b.status = 'ativo'
        AND b.data_fim >= CURDATE()
  );

-- =====================================================
-- 7. PROCEDURES
-- =====================================================
DELIMITER //

CREATE PROCEDURE cancelar_reserva(
    IN p_reserva_id INT,
    IN p_cancelado_por INT,
    IN p_motivo TEXT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    SET @cancelado_por = p_cancelado_por;
    SET @motivo_cancelamento = p_motivo;
    
    DELETE FROM reserva WHERE id = p_reserva_id;
    
    INSERT INTO log_sistema (usuario_id, acao, entidade, entidade_id, detalhes)
    VALUES (p_cancelado_por, 'CANCELAR_RESERVA', 'reserva', p_reserva_id, p_motivo);
    
    COMMIT;
END//

CREATE PROCEDURE cancelar_reservas_usuario(
    IN p_usuario_id INT,
    IN p_cancelado_por INT,
    IN p_motivo TEXT
)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_reserva_id INT;
    DECLARE cur CURSOR FOR 
        SELECT id FROM reserva 
        WHERE professor_id = p_usuario_id AND data_reserva >= CURDATE();
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    START TRANSACTION;
    
    SET @cancelado_por = p_cancelado_por;
    SET @motivo_cancelamento = p_motivo;
    
    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO v_reserva_id;
        IF done THEN
            LEAVE read_loop;
        END IF;
        DELETE FROM reserva WHERE id = v_reserva_id;
    END LOOP;
    CLOSE cur;
    
    INSERT INTO log_sistema (usuario_id, acao, entidade, entidade_id, detalhes)
    VALUES (p_cancelado_por, 'CANCELAR_RESERVAS_USUARIO', 'usuario', p_usuario_id, p_motivo);
    
    COMMIT;
END//

CREATE PROCEDURE expirar_sessoes_inativas()
BEGIN
    UPDATE sessao 
    SET ativa = FALSE 
    WHERE ativa = TRUE 
      AND data_expiracao < NOW();
END//

DELIMITER ;

/*-- =====================================================
-- 8. INSERIR UM PROFESSOR DE EXEMPLO (opcional)
-- =====================================================
INSERT INTO usuario (nome_completo, matricula, email, senha_hash, perfil, status, criado_por) VALUES
('Professor Exemplo', '20251SI1111', 'professor@acad.ifma.edu.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'professor', 'aprovado', 1);

-- =====================================================
-- 9. INSERIR LOGS DE ACESSO DE EXEMPLO (opcional)
-- =====================================================
INSERT INTO log_sistema (usuario_id, acao, entidade, entidade_id, detalhes, ip_origem, data_hora) VALUES
(1, 'ACESSO_LABORATORIO', 'acesso_laboratorio', 1, '{"laboratorio":"Lab 24"}', '192.168.1.100', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(1, 'ACESSO_LABORATORIO', 'acesso_laboratorio', 2, '{"laboratorio":"Lab 25"}', '192.168.1.101', DATE_SUB(NOW(), INTERVAL 5 HOUR)),
(1, 'ACESSO_LABORATORIO', 'acesso_laboratorio', 3, '{"laboratorio":"Lab 27"}', '192.168.1.102', DATE_SUB(NOW(), INTERVAL 1 DAY));
*/

-- =====================================================
-- 10. VERIFICAR DADOS INSERIDOS
-- =====================================================
SELECT '=== USUÁRIOS ===' as '';
SELECT id, nome_completo, matricula, perfil, status FROM usuario;

SELECT '=== LABORATÓRIOS ===' as '';
SELECT id, nome, capacidade, status_laboratorio FROM laboratorio;

SELECT '=== LOGS ===' as '';
SELECT id, acao, entidade, data_hora FROM log_sistema;