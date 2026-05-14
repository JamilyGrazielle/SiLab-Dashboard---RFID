<?php
// config.php
session_start();

$host = 'localhost';
$dbname = 'silab';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

// Verificar se usuário está logado
function verificarLogin() {
    if (!isset($_SESSION['usuario_id']) || $_SESSION['perfil'] !== 'root') {
        header('Location: login.php');
        exit();
    }
}

// Função para registrar logs
function registrarLog($pdo, $usuario_id, $acao, $entidade, $entidade_id, $detalhes = null) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $stmt = $pdo->prepare("INSERT INTO log_sistema (usuario_id, acao, entidade, entidade_id, detalhes, ip_origem) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$usuario_id, $acao, $entidade, $entidade_id, $detalhes, $ip]);
}
?>