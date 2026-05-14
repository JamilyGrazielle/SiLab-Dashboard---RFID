<?php
require_once 'config.php';
verificarLogin();

$tab = $_GET['tab'] ?? 'professor';
$mensagem = '';
$tipoMensagem = '';

// Processar cadastro de professor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_professor'])) {
    $nome = trim($_POST['nome_completo']);
    $matricula = trim($_POST['matricula']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    
    $erros = [];
    
    if (strlen($senha) < 6) {
        $erros[] = "Senha deve ter no mínimo 6 caracteres.";
    }
    
    $stmt = $pdo->prepare("SELECT id FROM usuario WHERE matricula = ?");
    $stmt->execute([$matricula]);
    if ($stmt->fetch()) {
        $erros[] = "Matrícula já cadastrada.";
    }
    
    $stmt = $pdo->prepare("SELECT id FROM usuario WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $erros[] = "E-mail já cadastrado.";
    }
    
    if (empty($erros)) {
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO usuario (nome_completo, matricula, email, senha_hash, perfil, status, criado_por) 
                               VALUES (?, ?, ?, ?, 'professor', 'aprovado', ?)");
        
        if ($stmt->execute([$nome, $matricula, $email, $senha_hash, $_SESSION['usuario_id']])) {
            $usuario_id = $pdo->lastInsertId();
            registrarLog($pdo, $_SESSION['usuario_id'], 'CADASTRAR_PROFESSOR', 'usuario', $usuario_id, "Professor {$nome} cadastrado");
            $mensagem = "Professor cadastrado com sucesso!";
            $tipoMensagem = "success";
        } else {
            $mensagem = "Erro ao cadastrar professor.";
            $tipoMensagem = "error";
        }
    } else {
        $mensagem = implode("<br>", $erros);
        $tipoMensagem = "error";
    }
}

// Processar cadastro de RFID
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_rfid'])) {
    $professor_id = $_POST['professor_id'];
    $rfid = trim($_POST['rfid']);
    
    if (empty($rfid)) {
        $mensagem = "Código RFID é obrigatório.";
        $tipoMensagem = "error";
    } else {
        $stmt = $pdo->prepare("UPDATE usuario SET rfid = ? WHERE id = ? AND perfil = 'professor'");
        if ($stmt->execute([$rfid, $professor_id])) {
            registrarLog($pdo, $_SESSION['usuario_id'], 'CADASTRAR_RFID', 'usuario', $professor_id, "RFID cadastrado: {$rfid}");
            $mensagem = "RFID cadastrado com sucesso!";
            $tipoMensagem = "success";
        } else {
            $mensagem = "Erro ao cadastrar RFID.";
            $tipoMensagem = "error";
        }
    }
}

// Buscar professores para o select
$professores = $pdo->query("SELECT id, nome_completo, matricula, rfid FROM usuario WHERE perfil = 'professor' AND status = 'aprovado' ORDER BY nome_completo")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiLab - Cadastrar</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-flask"></i>
                    <span>SiLab</span>
                </div>
                <div class="user-badge">
                    <i class="fas fa-user-shield"></i>
                    <div>
                        <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong>
                        <small>Root Administrator</small>
                    </div>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="cadastrar.php" class="nav-item <?php echo $tab === 'professor' ? 'active' : ''; ?>">
                    <i class="fas fa-user-plus"></i>
                    <span>Cadastrar Professor</span>
                </a>
                <a href="cadastrar.php?tab=rfid" class="nav-item <?php echo $tab === 'rfid' ? 'active' : ''; ?>">
                    <i class="fas fa-id-card"></i>
                    <span>Cadastrar RFID</span>
                </a>
                <a href="lista_acesso.php" class="nav-item">
                    <i class="fas fa-door-open"></i>
                    <span>Lista de Acesso</span>
                </a>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Sair</span>
                </a>
            </div>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <h1><i class="fas fa-<?php echo $tab === 'professor' ? 'user-plus' : 'id-card'; ?>"></i> 
                    <?php echo $tab === 'professor' ? 'Cadastrar Professor' : 'Cadastrar RFID'; ?>
                </h1>
            </div>

            <?php if ($mensagem): ?>
                <div class="alert alert-<?php echo $tipoMensagem; ?>">
                    <i class="fas fa-<?php echo $tipoMensagem === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo $mensagem; ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-<?php echo $tab === 'professor' ? 'user-graduate' : 'microchip'; ?>"></i>
                        <?php echo $tab === 'professor' ? 'Novo Professor' : 'Vincular Cartão RFID'; ?>
                    </h3>
                </div>
                <div class="card-body">
                    <?php if ($tab === 'professor'): ?>
                        <form method="POST" class="form-cadastro">
                            <div class="form-group">
                                <label><i class="fas fa-user"></i> Nome Completo *</label>
                                <input type="text" name="nome_completo" class="form-control" required>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label><i class="fas fa-id-card"></i> Matrícula *</label>
                                    <input type="text" name="matricula" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label><i class="fas fa-envelope"></i> E-mail *</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label><i class="fas fa-lock"></i> Senha *</label>
                                    <input type="password" name="senha" class="form-control" required minlength="6">
                                    <small>Mínimo 6 caracteres</small>
                                </div>
                                <div class="form-group">
                                    <label><i class="fas fa-lock"></i> Confirmar Senha *</label>
                                    <input type="password" id="confirmar_senha" class="form-control" required>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="cadastrar_professor" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Cadastrar Professor
                                </button>
                                <button type="reset" class="btn btn-secondary">
                                    <i class="fas fa-eraser"></i> Limpar
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <form method="POST" class="form-cadastro">
                            <div class="form-group">
                                <label><i class="fas fa-chalkboard-user"></i> Selecionar Professor *</label>
                                <select name="professor_id" class="form-control" required>
                                    <option value="">Selecione um professor...</option>
                                    <?php foreach ($professores as $prof): ?>
                                        <option value="<?php echo $prof['id']; ?>">
                                            <?php echo htmlspecialchars($prof['nome_completo']); ?> 
                                            (<?php echo htmlspecialchars($prof['matricula']); ?>)
                                            <?php echo $prof['rfid'] ? ' - ✅ RFID cadastrado' : ''; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-id-card"></i> Código RFID *</label>
                                <input type="text" name="rfid" class="form-control" placeholder="Digite ou leia o código do cartão" required>
                                <small>Leia o cartão RFID na leitora para preencher automaticamente</small>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="cadastrar_rfid" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Vincular RFID
                                </button>
                            </div>
                        </form>

                        <hr>

                        <h4><i class="fas fa-list"></i> Professores com RFID Cadastrado</h4>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Matrícula</th>
                                    <th>RFID</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $stmt = $pdo->query("SELECT nome_completo, matricula, rfid FROM usuario WHERE perfil = 'professor' AND rfid IS NOT NULL ORDER BY nome_completo");
                                $rfidProfessores = $stmt->fetchAll();
                                foreach ($rfidProfessores as $prof): 
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($prof['nome_completo']); ?></td>
                                        <td><?php echo htmlspecialchars($prof['matricula']); ?></td>
                                        <td><code><?php echo htmlspecialchars($prof['rfid']); ?></code></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (count($rfidProfessores) === 0): ?>
                                    <tr>
                                        <td colspan="3" class="text-center">Nenhum RFID cadastrado ainda</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    <script src="script.js"></script>
</body>
</html>