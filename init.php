<?php
// init.php - Script de inicialização do banco de dados
require_once 'config.php';

function inicializarBanco($pdo) {
    $mensagens = [];
    
    try {
        // Verificar se a tabela usuario existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'usuario'");
        if ($stmt->rowCount() == 0) {
            $mensagens[] = "⚠️ Tabelas não encontradas. Execute o script SQL primeiro.";
            return $mensagens;
        }
        
        // Verificar se existe usuário root
        $stmt = $pdo->prepare("SELECT id FROM usuario WHERE perfil = 'root'");
        $stmt->execute();
        $root = $stmt->fetch();
        
        if (!$root) {
            // Criar usuário root
            $senha_hash = password_hash('Root@00', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuario (nome_completo, matricula, email, senha_hash, perfil, status) 
                                   VALUES ('Root Administrator', '20251SI0000', 'root@silab.com', ?, 'root', 'aprovado')");
            $stmt->execute([$senha_hash]);
            $root_id = $pdo->lastInsertId();
            $mensagens[] = "✅ Usuário root criado com sucesso! (ID: $root_id)";
        } else {
            $mensagens[] = "✅ Usuário root já existe (ID: {$root['id']})";
        }
        
        // Verificar se existem laboratórios
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM laboratorio");
        $totalLab = $stmt->fetch()['total'];
        
        if ($totalLab == 0) {
            // Buscar ID do root para criar os laboratórios
            $stmt = $pdo->prepare("SELECT id FROM usuario WHERE perfil = 'root' LIMIT 1");
            $stmt->execute();
            $root = $stmt->fetch();
            $root_id = $root['id'];
            
            // Criar laboratórios padrão
            $stmt = $pdo->prepare("INSERT INTO laboratorio (nome, capacidade, criado_por) VALUES 
                                   ('Lab 24', 30, ?),
                                   ('Lab 25', 30, ?),
                                   ('Lab 27', 30, ?)");
            $stmt->execute([$root_id, $root_id, $root_id]);
            $mensagens[] = "✅ Laboratórios padrão criados (Lab 24, Lab 25, Lab 27)";
        } else {
            $mensagens[] = "✅ Laboratórios já existem ($totalLab laboratórios)";
        }
        
    } catch (PDOException $e) {
        $mensagens[] = "❌ Erro: " . $e->getMessage();
    }
    
    return $mensagens;
}

// Se executado diretamente, mostrar mensagens
if (basename($_SERVER['PHP_SELF']) == 'init.php') {
    echo "<h1>Inicialização do SiLab</h1>";
    echo "<pre>";
    $mensagens = inicializarBanco($pdo);
    echo implode("\n", $mensagens);
    echo "\n\n✅ Sistema pronto para uso!";
    echo "\n🔑 Login: matrícula 20251SI0000 | senha Root@00";
    echo "</pre>";
    echo "<a href='login.php'>Ir para o login →</a>";
}
?>