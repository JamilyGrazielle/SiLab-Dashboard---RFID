<?php
require_once 'config.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricula = $_POST['matricula'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM usuario WHERE matricula = ? AND perfil = 'root' AND status = 'aprovado'");
    $stmt->execute([$matricula]);
    $usuario = $stmt->fetch();
    
    if ($usuario && password_verify($senha, $usuario['senha_hash'])) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome_completo'];
        $_SESSION['perfil'] = $usuario['perfil'];
        $_SESSION['matricula'] = $usuario['matricula'];
        
        $pdo->prepare("UPDATE usuario SET data_ultimo_login = NOW() WHERE id = ?")->execute([$usuario['id']]);
        
        header('Location: index.php');
        exit();
    } else {
        $erro = 'Matrícula ou senha incorretos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiLab - Login Root</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo-icon">
                    <i class="fas fa-flask"></i>
                </div>
                <h1>SiLab</h1>
                <p>Sistema Integrado de Laboratórios</p>
            </div>
            <div class="login-info">
                <i class="fas fa-shield-alt"></i>
                <span>Acesso Root - Administração Master</span>
            </div>
            <?php if ($erro): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $erro; ?>
                </div>
            <?php endif; ?>
            <form method="POST" class="login-form">
                <div class="input-group">
                    <i class="fas fa-id-card"></i>
                    <input type="text" name="matricula" placeholder="Matrícula" required value="20251SI0000">
                </div>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="senha" placeholder="Senha" required>
                </div>
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    Entrar
                </button>
            </form>
            <div class="login-footer">
                <small>Matrícula: 20251SI0000 | Senha: Root@00</small>
            </div>
        </div>
    </div>
</body>
</html>