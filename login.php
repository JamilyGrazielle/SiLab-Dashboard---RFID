<?php
require_once 'config.php';

verificarNaoLogado();

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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #4361ee;
            --primary-dark: #3a0ca3;
            --success: #06d6a0;
            --danger: #ef476f;
            --white: #ffffff;
            --dark: #2b2d42;
            --gray: #8d99ae;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            top: -150px;
            right: -150px;
        }

        body::after {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
            bottom: -250px;
            left: -250px;
        }

        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 20px;
            position: relative;
            z-index: 1;
        }

        .login-card {
            background: var(--white);
            border-radius: 20px;
            box-shadow: 0 20px 35px -10px rgba(0,0,0,0.2);
            padding: 40px;
            animation: fadeInUp 0.5s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .logo-icon i {
            font-size: 32px;
            color: var(--white);
        }

        .login-header h1 {
            font-size: 28px;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .login-header p {
            color: var(--gray);
            font-size: 14px;
        }

        .login-info {
            background: #e8f0fe;
            border-radius: 10px;
            padding: 12px;
            text-align: center;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .login-info i {
            color: var(--primary);
            font-size: 18px;
        }

        .login-info span {
            color: var(--primary-dark);
            font-size: 13px;
            font-weight: 500;
        }

        .input-group {
            display: flex;
            align-items: center;
            background: #f5f7fb;
            border-radius: 10px;
            margin-bottom: 20px;
            padding: 0 15px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
        }

        .input-group:focus-within {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .input-group i {
            color: var(--gray);
            font-size: 18px;
        }

        .input-group input {
            flex: 1;
            padding: 15px 12px;
            border: none;
            background: transparent;
            font-size: 15px;
            outline: none;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--white);
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .login-footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }

        .login-footer small {
            color: var(--gray);
            font-size: 12px;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
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
                    <?php echo htmlspecialchars($erro); ?>
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
    <script>
        document.querySelector('input[name="senha"]').focus();
    </script>
</body>
</html>