<?php
// api/dashboard_data.php
header('Content-Type: application/json');
require_once '../config.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['perfil'] !== 'root') {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit();
}

$data = ['success' => true];

// Total de professores
$stmt = $pdo->query("SELECT COUNT(*) as total FROM usuario WHERE perfil = 'professor' AND status = 'aprovado'");
$data['total_professores'] = $stmt->fetch()['total'];

// Total de laboratórios
$stmt = $pdo->query("SELECT COUNT(*) as total FROM laboratorio");
$data['total_laboratorios'] = $stmt->fetch()['total'];

// Acessos hoje
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM log_sistema WHERE entidade = 'acesso_laboratorio' AND DATE(data_hora) = CURDATE()");
$stmt->execute();
$data['acessos_hoje'] = $stmt->fetch()['total'];

// Últimos RFIDs cadastrados
$stmt = $pdo->query("SELECT nome_completo, matricula, rfid FROM usuario WHERE perfil = 'professor' AND rfid IS NOT NULL ORDER BY data_cadastro DESC LIMIT 5");
$data['ultimos_rfids'] = $stmt->fetchAll();

// Últimos acessos
$stmt = $pdo->prepare("SELECT l.detalhes, l.data_hora, u.nome_completo FROM log_sistema l LEFT JOIN usuario u ON l.usuario_id = u.id WHERE l.entidade = 'acesso_laboratorio' ORDER BY l.data_hora DESC LIMIT 5");
$stmt->execute();
$data['ultimos_acessos'] = $stmt->fetchAll();

echo json_encode($data);
?>