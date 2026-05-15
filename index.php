<?php
require_once 'config.php';
verificarLogin();

$usuario_nome = $_SESSION['usuario_nome'];

// Contar total de professores
$stmt = $pdo->query("SELECT COUNT(*) as total FROM usuario WHERE perfil = 'professor' AND status = 'aprovado'");
$totalProfessores = $stmt->fetch()['total'];

// Contar total de laboratórios
$stmt = $pdo->query("SELECT COUNT(*) as total FROM laboratorio");
$totalLaboratorios = $stmt->fetch()['total'];

// Contar acessos hoje
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM log_sistema WHERE entidade = 'acesso_laboratorio' AND DATE(data_hora) = CURDATE()");
$stmt->execute();
$acessosHoje = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiLab - Dashboard Root</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-flask"></i>
                    <span>SiLab</span>
                </div>
                <div class="user-badge">
                    <i class="fas fa-user-shield"></i>
                    <div>
                        <strong><?php echo htmlspecialchars($usuario_nome); ?></strong>
                        <small>Root Administrator</small>
                    </div>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="cadastrar.php" class="nav-item">
                    <i class="fas fa-user-plus"></i>
                    <span>Cadastrar Professor</span>
                </a>
                <a href="cadastrar.php?tab=rfid" class="nav-item">
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
                    <span>Sair do Sistema</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                <div class="header-actions">
                    <div class="date-time">
                        <i class="far fa-calendar-alt"></i>
                        <span id="currentDate"></span>
                    </div>
                    <a href="logout.php" class="btn-logout-header" title="Sair do Sistema">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Sair</span>
                    </a>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chalkboard-user"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $totalProfessores; ?></h3>
                        <p>Professores Cadastrados</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-flask"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $totalLaboratorios; ?></h3>
                        <p>Laboratórios</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $acessosHoje; ?></h3>
                        <p>Acessos Hoje</p>
                    </div>
                </div>
            </div>

            <div class="content-grid">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-id-card"></i> Últimos RFID Cadastrados</h3>
                        <a href="cadastrar.php?tab=rfid" class="btn-link">Ver todos</a>
                    </div>
                    <div class="card-body">
                        <div class="rfid-list" id="ultimosRfid">
                            <?php
                            $stmt = $pdo->query("SELECT u.nome_completo, u.matricula, u.rfid, u.data_cadastro 
                                                  FROM usuario u 
                                                  WHERE u.perfil = 'professor' AND u.rfid IS NOT NULL 
                                                  ORDER BY u.data_cadastro DESC LIMIT 5");
                            $rfids = $stmt->fetchAll();
                            if (count($rfids) > 0):
                                foreach ($rfids as $rfid):
                            ?>
                                <div class="rfid-item">
                                    <div class="rfid-info">
                                        <strong><?php echo htmlspecialchars($rfid['nome_completo']); ?></strong>
                                        <span>Matrícula: <?php echo htmlspecialchars($rfid['matricula']); ?></span>
                                    </div>
                                    <div class="rfid-code">
                                        <i class="fas fa-id-card"></i>
                                        <?php echo htmlspecialchars(substr($rfid['rfid'], -8)); ?>
                                    </div>
                                </div>
                            <?php 
                                endforeach;
                            else:
                            ?>
                                <div class="empty-state">
                                    <i class="fas fa-id-card"></i>
                                    <p>Nenhum RFID cadastrado ainda</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-door-open"></i> Últimos Acessos</h3>
                        <a href="lista_acesso.php" class="btn-link">Ver todos</a>
                    </div>
                    <div class="card-body">
                        <div class="access-list" id="ultimosAcessos">
                            <?php
                            $stmt = $pdo->prepare("SELECT l.detalhes, l.data_hora, u.nome_completo 
                                                   FROM log_sistema l
                                                   LEFT JOIN usuario u ON l.usuario_id = u.id
                                                   WHERE l.entidade = 'acesso_laboratorio'
                                                   ORDER BY l.data_hora DESC LIMIT 5");
                            $stmt->execute();
                            $acessos = $stmt->fetchAll();
                            if (count($acessos) > 0):
                                foreach ($acessos as $acesso):
                                    $detalhes = json_decode($acesso['detalhes'], true);
                            ?>
                                <div class="access-item">
                                    <div class="access-icon">
                                        <i class="fas fa-door-open"></i>
                                    </div>
                                    <div class="access-info">
                                        <strong><?php echo htmlspecialchars($acesso['nome_completo'] ?? 'Desconhecido'); ?></strong>
                                        <span>Laboratório: <?php echo htmlspecialchars($detalhes['laboratorio'] ?? 'N/A'); ?></span>
                                    </div>
                                    <div class="access-time">
                                        <?php echo date('d/m H:i', strtotime($acesso['data_hora'])); ?>
                                    </div>
                                </div>
                            <?php 
                                endforeach;
                            else:
                            ?>
                                <div class="empty-state">
                                    <i class="fas fa-door-open"></i>
                                    <p>Nenhum acesso registrado ainda</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="script.js"></script>
    <script>
        function updateDateTime() {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const dateElement = document.getElementById('currentDate');
            if (dateElement) {
                dateElement.innerHTML = now.toLocaleDateString('pt-BR', options);
            }
        }
        updateDateTime();
        
        // Atualizar a cada minuto
        setInterval(updateDateTime, 60000);
    </script>
</body>
</html>