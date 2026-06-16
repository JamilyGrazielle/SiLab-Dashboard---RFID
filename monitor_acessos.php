<?php
require_once 'config.php';
verificarLogin();

$stmt = $pdo->prepare("
    SELECT 
        l.data_hora,
        l.detalhes,
        u.nome_completo,
        u.matricula
    FROM log_sistema l
    LEFT JOIN usuario u ON l.usuario_id = u.id
    WHERE l.entidade = 'acesso_laboratorio'
    ORDER BY l.data_hora DESC
    LIMIT 50
");
$stmt->execute();
$acessos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor de Acessos - SiLab</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fb;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            background: linear-gradient(135deg, #4361ee, #3a0ca3);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            background: #f8f9fc;
            font-weight: 600;
            color: #2b2d42;
        }
        .badge-success {
            background: #d4edda;
            color: #155724;
            padding: 4px 8px;
            border-radius: 5px;
            font-size: 12px;
        }
        .refresh-btn {
            background: #4361ee;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
        }
        .refresh-btn:hover {
            background: #3a0ca3;
        }
        .rfid-card {
            background: #e8f0fe;
            border-left: 4px solid #4361ee;
        }
        .laboratorio {
            display: inline-block;
            padding: 4px 10px;
            background: #eef2f6;
            border-radius: 20px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-door-open"></i> Monitor de Acessos - SiLab</h1>
            <p>Registros de entrada nos laboratórios via RFID</p>
        </div>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2><i class="fas fa-history"></i> Últimos Acessos</h2>
                <button class="refresh-btn" onclick="location.reload()">
                    <i class="fas fa-sync-alt"></i> Atualizar
                </button>
            </div>
            
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Professor</th>
                            <th>Matrícula</th>
                            <th>Laboratório</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($acessos) > 0): ?>
                            <?php foreach ($acessos as $acesso): 
                                $detalhes = json_decode($acesso['detalhes'], true);
                            ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i:s', strtotime($acesso['data_hora'])); ?></td>
                                    <td><strong><?php echo htmlspecialchars($acesso['nome_completo'] ?? 'Desconhecido'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($acesso['matricula'] ?? 'N/A'); ?></td>
                                    <td><span class="laboratorio"><?php echo htmlspecialchars($detalhes['laboratorio'] ?? 'N/A'); ?></span></td>
                                    <td><span class="badge-success">Liberado</span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">Nenhum acesso registrado ainda</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <h2><i class="fas fa-chart-line"></i> Estatísticas de Hoje</h2>
            <?php
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total,
                    COUNT(DISTINCT usuario_id) as professores
                FROM log_sistema 
                WHERE entidade = 'acesso_laboratorio' 
                AND DATE(data_hora) = CURDATE()
            ");
            $stmt->execute();
            $stats = $stmt->fetch();
            
            $stmt = $pdo->prepare("
                SELECT laboratorio, COUNT(*) as total
                FROM (
                    SELECT JSON_EXTRACT(detalhes, '$.laboratorio') as laboratorio
                    FROM log_sistema
                    WHERE entidade = 'acesso_laboratorio' 
                    AND DATE(data_hora) = CURDATE()
                ) as a
                GROUP BY laboratorio
            ");
            $stmt->execute();
            $porLab = $stmt->fetchAll();
            ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
                <div style="text-align: center; padding: 20px; background: #f8f9fc; border-radius: 10px;">
                    <h3 style="font-size: 32px; color: #4361ee;"><?php echo $stats['total'] ?? 0; ?></h3>
                    <p>Acessos hoje</p>
                </div>
                <div style="text-align: center; padding: 20px; background: #f8f9fc; border-radius: 10px;">
                    <h3 style="font-size: 32px; color: #4361ee;"><?php echo $stats['professores'] ?? 0; ?></h3>
                    <p>Professores diferentes</p>
                </div>
            </div>
            
            <?php if (count($porLab) > 0): ?>
                <h3 style="margin-top: 20px;">Acessos por Laboratório</h3>
                <ul>
                    <?php foreach ($porLab as $lab): ?>
                        <li><?php echo htmlspecialchars(trim($lab['laboratorio'], '"')); ?>: <?php echo $lab['total']; ?> acessos</li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <script>
        setTimeout(function() {
            location.reload();
        }, 10000);
    </script>
</body>
</html>