<?php
// api_rfid.php - API para receber leituras do RFID
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

// Responder OPTIONS (pré-requisição CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Aceitar tanto GET quanto POST
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $rfid = $_GET['rfid'] ?? null;
    $laboratorio = $_GET['laboratorio'] ?? null;
    $action = $_GET['action'] ?? 'check';
} else {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $rfid = $_POST['rfid'] ?? null;
        $laboratorio = $_POST['laboratorio'] ?? null;
        $action = $_POST['action'] ?? 'check';
    } else {
        $rfid = $input['rfid'] ?? null;
        $laboratorio = $input['laboratorio'] ?? null;
        $action = $input['action'] ?? 'check';
    }
}

// Se for GET de professores
if ($action === 'get_professores' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("SELECT id, nome_completo, matricula, rfid FROM usuario WHERE perfil = 'professor' AND (rfid IS NULL OR rfid = '') ORDER BY nome_completo");
    $stmt->execute();
    $professores = $stmt->fetchAll();
    echo json_encode(['success' => true, 'professores' => $professores]);
    exit;
}

if (!$rfid) {
    echo json_encode(['success' => false, 'message' => 'RFID não informado']);
    exit;
}

// Registrar log da leitura
function registrarLogRFID($pdo, $rfid, $laboratorio, $status, $mensagem, $professor_id = null) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $detalhes = json_encode([
        'rfid' => $rfid,
        'laboratorio' => $laboratorio,
        'status' => $status,
        'mensagem' => $mensagem
    ]);
    
    $stmt = $pdo->prepare("INSERT INTO log_sistema (usuario_id, acao, entidade, entidade_id, detalhes, ip_origem) 
                           VALUES (?, 'leitura_rfid', 'rfid', NULL, ?, ?)");
    $stmt->execute([$professor_id, $detalhes, $ip]);
}

// Lista de laboratórios disponíveis
$laboratorios_validos = ['Lab 24', 'Lab 25', 'Lab 27'];

// Validar laboratório
if (!$laboratorio || !in_array($laboratorio, $laboratorios_validos)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Laboratório inválido. Use: Lab 24, Lab 25 ou Lab 27',
        'laboratorios' => $laboratorios_validos
    ]);
    registrarLogRFID($pdo, $rfid, $laboratorio, 'error', 'Laboratório inválido');
    exit;
}

// Buscar professor pelo RFID
$stmt = $pdo->prepare("SELECT id, nome_completo, matricula, status FROM usuario WHERE rfid = ? AND perfil = 'professor'");
$stmt->execute([$rfid]);
$professor = $stmt->fetch();

if (!$professor) {
    echo json_encode([
        'success' => false, 
        'message' => 'RFID não cadastrado. Procure o administrador.',
        'rfid_lido' => $rfid
    ]);
    registrarLogRFID($pdo, $rfid, $laboratorio, 'error', 'RFID não cadastrado');
    exit;
}

// Verificar se professor está ativo
if ($professor['status'] !== 'aprovado') {
    echo json_encode([
        'success' => false, 
        'message' => 'Professor não está ativo no sistema.'
    ]);
    registrarLogRFID($pdo, $rfid, $laboratorio, 'error', 'Professor inativo', $professor['id']);
    exit;
}

// Verificar se o laboratório está disponível
$stmt = $pdo->prepare("SELECT status_laboratorio FROM laboratorio WHERE nome = ?");
$stmt->execute([$laboratorio]);
$lab = $stmt->fetch();

if (!$lab || $lab['status_laboratorio'] === 'em_manutencao') {
    echo json_encode([
        'success' => false, 
        'message' => 'Laboratório em manutenção. Acesso negado.'
    ]);
    registrarLogRFID($pdo, $rfid, $laboratorio, 'error', 'Laboratório em manutenção', $professor['id']);
    exit;
}

// Registrar acesso
$detalhes_acesso = json_encode([
    'laboratorio' => $laboratorio,
    'professor_id' => $professor['id'],
    'professor_nome' => $professor['nome_completo'],
    'matricula' => $professor['matricula']
]);

$stmt = $pdo->prepare("INSERT INTO log_sistema (usuario_id, acao, entidade, entidade_id, detalhes, ip_origem) 
                       VALUES (?, 'acesso_laboratorio', 'acesso_laboratorio', NULL, ?, ?)");
$stmt->execute([$professor['id'], $detalhes_acesso, $_SERVER['REMOTE_ADDR'] ?? null]);

echo json_encode([
    'success' => true, 
    'message' => 'Acesso liberado!',
    'professor' => [
        'nome' => $professor['nome_completo'],
        'matricula' => $professor['matricula'],
        'laboratorio' => $laboratorio,
        'data_hora' => date('d/m/Y H:i:s')
    ]
]);

registrarLogRFID($pdo, $rfid, $laboratorio, 'success', 'Acesso liberado', $professor['id']);
?>