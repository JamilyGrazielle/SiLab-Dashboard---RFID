<?php
require_once 'config.php';
verificarLogin();

$filtro_laboratorio = $_GET['laboratorio'] ?? '';
$filtro_data = $_GET['data'] ?? date('Y-m-d');
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$query = "SELECT l.id, l.detalhes, l.data_hora, u.nome_completo, u.matricula 
          FROM log_sistema l
          LEFT JOIN usuario u ON l.usuario_id = u.id
          WHERE l.entidade = 'acesso_laboratorio'";

$params = [];

if ($filtro_laboratorio) {
    $query .= " AND JSON_EXTRACT(l.detalhes, '$.laboratorio') = ?";
    $params[] = $filtro_laboratorio;
}

if ($filtro_data) {
    $query .= " AND DATE(l.data_hora) = ?";
    $params[] = $filtro_data;
}

$countQuery = str_replace("l.id, l.detalhes, l.data_hora, u.nome_completo, u.matricula", "COUNT(*) as total", $query);
$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$total = $stmt->fetch()['total'];
$totalPages = ceil($total / $limit);

$query .= " ORDER BY l.data_hora DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$acessos = $stmt->fetchAll();

$laboratorios = ['Lab 24', 'Lab 25', 'Lab 27'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiLab - Lista de Acesso</title>
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
                <a href="cadastrar.php" class="nav-item">
                    <i class="fas fa-user-plus"></i>
                    <span>Cadastrar Professor</span>
                </a>
                <a href="cadastrar.php?tab=rfid" class="nav-item">
                    <i class="fas fa-id-card"></i>
                    <span>Cadastrar RFID</span>
                </a>
                <a href="lista_acesso.php" class="nav-item active">
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
                <h1><i class="fas fa-door-open"></i> Lista de Acesso aos Laboratórios</h1>
            </div>

            <div class="card">
                <div class="card-body">
                    <form method="GET" class="filter-form">
                        <div class="filter-group">
                            <label><i class="fas fa-flask"></i> Laboratório</label>
                            <select name="laboratorio" class="form-control">
                                <option value="">Todos</option>
                                <?php foreach ($laboratorios as $lab): ?>
                                    <option value="<?php echo $lab; ?>" <?php echo $filtro_laboratorio === $lab ? 'selected' : ''; ?>>
                                        <?php echo $lab; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label><i class="fas fa-calendar"></i> Data</label>
                            <input type="date" name="data" class="form-control" value="<?php echo $filtro_data; ?>">
                        </div>
                        <div class="filter-group filter-actions">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                            <a href="lista_acesso.php" class="btn btn-secondary">
                                <i class="fas fa-undo"></i> Limpar
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Registros de Acesso</h3>
                    <span class="badge">Total: <?php echo $total; ?></span>
                </div>
                <div class="card-body table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Data/Hora</th>
                                <th>Professor</th>
                                <th>Matrícula</th>
                                <th>Laboratório</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($acessos) > 0): ?>
                                <?php foreach ($acessos as $acesso): 
                                    $detalhes = json_decode($acesso['detalhes'], true);
                                ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i:s', strtotime($acesso['data_hora'])); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($acesso['nome_completo'] ?? 'Desconhecido'); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($acesso['matricula'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="lab-badge">
                                                <i class="fas fa-flask"></i>
                                                <?php echo htmlspecialchars($detalhes['laboratorio'] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">
                                        <div class="empty-state">
                                            <i class="fas fa-door-open"></i>
                                            <p>Nenhum registro de acesso encontrado</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="?page=<?php echo $i; ?>&laboratorio=<?php echo urlencode($filtro_laboratorio); ?>&data=<?php echo $filtro_data; ?>" 
                                   class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    <script src="script.js"></script>
</body>
</html>