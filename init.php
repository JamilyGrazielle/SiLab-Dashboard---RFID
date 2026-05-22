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
        
        // Verificar se já existem professores cadastrados
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuario WHERE perfil = 'professor'");
        $totalProf = $stmt->fetch()['total'];

        if ($totalProf < 6) {

            $senha_padrao = password_hash('Professor@123', PASSWORD_DEFAULT);

            $professores = [
                ['Gentil Junior', '20251SI0001'],
                ['Abraao Azevedo', '20251SI0002'],
                ['Alex Martins', '20251SI0003'],
                ['Antonio Luis', '20251SI0004'],
                ['Carla Faria', '20251SI0005'],
                ['Eveline Sa', '20251SI0006'],
                ['Fernanda Gomes', '20251SI0007'],
                ['Flavio Barros', '20251SI0008'],
                ['Fabiano Tavares', '20251SI0009'],
                ['Gilson Rodrigues', '20251SI0010'],
                ['Helder Borges', '20251SI0011'],
                ['Jane Ewerton', '20251SI0012'],
                ['Jeane Ferreira', '20251SI0013'],
                ['Joao Carlos', '20251SI0014'],
                ['Josenildo Silva', '20251SI0015'],
                ['Marcio Campos', '20251SI0016'],
                ['Mauro Silva', '20251SI0017'],
                ['Omar Carmona', '20251SI0018'],
                ['Paulo Pacini', '20251SI0019'],
                ['Salete Farias', '20251SI0020'],
                ['Silvana Brito', '20251SI0021'],
                ['Ulibiran Chaves', '20251SI0022']
            ];

            $stmt = $pdo->prepare("
                INSERT INTO usuario 
                (nome_completo, matricula, email, senha_hash, perfil, status)
                VALUES (?, ?, ?, ?, 'professor', 'aprovado')
            ");

            foreach ($professores as $professor) {

                $nome = $professor[0];
                $matricula = $professor[1];

                $email = strtolower(str_replace(' ', '.', $nome)) . '@silab.com';

                $stmt->execute([
                    $nome,
                    $matricula,
                    $email,
                    $senha_padrao
                ]);
            }

            $mensagens[] = "✅ Professores cadastrados com sucesso!";
    } else {
        $mensagens[] = "✅ Professores já cadastrados ($totalProf professores)";
    }

        // Verificar se já existem professores cadastrados
$stmt = $pdo->query("SELECT COUNT(*) as total FROM usuario WHERE perfil = 'professor'");
$totalProf = $stmt->fetch()['total'];

if ($totalProf == 0) {

    $senha_padrao = password_hash('Professor@123', PASSWORD_DEFAULT);

    $professores = [
        ['Gentil Junior', '20251SI0001'],
        ['Abraao Azevedo', '20251SI0002'],
        ['Alex Martins', '20251SI0003'],
        ['Antonio Luis', '20251SI0004'],
        ['Carla Faria', '20251SI0005'],
        ['Eveline Sa', '20251SI0006'],
        ['Fernanda Gomes', '20251SI0007'],
        ['Flavio Barros', '20251SI0008'],
        ['Fabiano Tavares', '20251SI0009'],
        ['Gilson Rodrigues', '20251SI0010'],
        ['Helder Borges', '20251SI0011'],
        ['Jane Ewerton', '20251SI0012'],
        ['Jeane Ferreira', '20251SI0013'],
        ['Joao Carlos', '20251SI0014'],
        ['Josenildo Silva', '20251SI0015'],
        ['Marcio Campos', '20251SI0016'],
        ['Mauro Silva', '20251SI0017'],
        ['Omar Carmona', '20251SI0018'],
        ['Paulo Pacini', '20251SI0019'],
        ['Salete Farias', '20251SI0020'],
        ['Silvana Brito', '20251SI0021'],
        ['Ulibiran Chaves', '20251SI0022']
    ];

    $stmtProfessor = $pdo->prepare("
        INSERT INTO usuario 
        (
            nome_completo,
            matricula,
            email,
            senha_hash,
            perfil,
            status
        )
        VALUES (?, ?, ?, ?, 'professor', 'aprovado')
    ");

    foreach ($professores as $professor) {

        $nome = $professor[0];
        $matricula = $professor[1];

        $email = strtolower(
            str_replace(' ', '.', $nome)
        ) . '@silab.com';

        $stmtProfessor->execute([
            $nome,
            $matricula,
            $email,
            $senha_padrao
        ]);
    }

    $mensagens[] = "✅ Professores cadastrados!";
} else {
    $mensagens[] = "✅ Professores já cadastrados!";
}


    // ===============================
    // DISCIPLINAS E RESERVAS
    // ===============================

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM disciplina");
    $totalDisc = $stmt->fetch()['total'];

    if ($totalDisc == 0) {

        $disciplinas = [

            ['Gentil Junior', 'Lógica e Matemática Computacional', 'SUP.04865'],

            ['Abraao Azevedo', 'Gestão e Organização', 'SUP.04869'],
            ['Abraao Azevedo', 'Tópicos em Gestão', 'SUP.05372'],
            ['Abraao Azevedo', 'Empreendedorismo e Inovação', 'SUP.05379'],
            ['Abraao Azevedo', 'Gestão de Tecnologia da Informação', 'SUP.05385'],
            ['Abraao Azevedo', 'Empreendedorismo em Informática', 'SUP.01279'],

            ['Alex Martins', 'Segurança e Auditoria de Informação', 'SUP.05387'],

            ['Antonio Luis', 'Matemática Discreta', 'SUP.05361'],
            ['Antonio Luis', 'Inteligência Artificial', 'SUP.05384'],

            ['Carla Faria', 'Banco de Dados', 'SUP.05362'],
            ['Carla Faria', 'Engenharia de Software', 'SUP.05374'],
            ['Carla Faria', 'Análise e Projeto de Sistemas', 'SUP.05381'],

            ['Eveline Sa', 'Interação Humano Computador', 'SUP.05377'],
            ['Eveline Sa', 'Laboratório de Desenvolvimento de Software', 'SUP.05388'],

            ['Fernanda Gomes', 'Probabilidade e Estatística', 'SUP.05360'],

            ['Flavio Barros', 'Programação Orientada a Objetos', 'SUP.05364'],
            ['Flavio Barros', 'Desenvolvimento Web II', 'SUP.05378'],

            ['Fabiano Tavares', 'Cálculo Diferencial e Integral', 'SUP.04864'],

            ['Gentil Junior', 'Fundamentos de Sistemas de Informação', 'SUP.05365'],
            ['Gentil Junior', 'Computação, Sociedade e Ética', 'SUP.05382'],

            ['Gilson Rodrigues', 'Metodologia da Pesquisa', 'SUP.05366'],

            ['Helder Borges', 'Trabalho de Conclusão de Curso I', 'SUP.05393'],
            ['Helder Borges', 'Introdução a Robótica', 'SUP.07812'],
            ['Helder Borges', 'Monografia II', 'SUP.01309'],

            ['Jane Ewerton', 'Inglês Instrumental', 'SUP.07788'],

            ['Jeane Ferreira', 'Atividade Curricular de Extensão I', 'SUP.05373'],
            ['Jeane Ferreira', 'Atividade Curricular de Extensão II', 'SUP.08120'],
            ['Jeane Ferreira', 'Atividade Curricular de Extensão III', 'SUP.05386'],
            ['Jeane Ferreira', 'Atividade Curricular de Extensão IV', 'SUP.05391'],

            ['Joao Carlos', 'Laboratório de Banco de Dados', 'SUP.05369'],
            ['Joao Carlos', 'Desenvolvimento Web I', 'SUP.05371'],
            ['Joao Carlos', 'Padrões de Software e Refatoração', 'SUP.05389'],

            ['Josenildo Silva', 'Algoritmos e Estruturas de Dados I', 'SUP.05363'],
            ['Josenildo Silva', 'Administração e Gerenciamento de Banco de Dados', 'SUP.05376'],
            ['Josenildo Silva', 'Aprendizagem de Máquina', 'SUP.07798'],

            ['Marcio Campos', 'Sistemas Operacionais', 'SUP.05368'],
            ['Marcio Campos', 'Redes de Computadores I', 'SUP.05375'],

            ['Mauro Silva', 'Programação para Dispositivos Móveis', 'SUP.05383'],
            ['Mauro Silva', 'Sistemas Distribuídos', 'SUP.07824'],
            ['Mauro Silva', 'Sistemas Distribuídos', 'SUP.01284'],

            ['Omar Carmona', 'Algoritmos e Estruturas de Dados II', 'SUP.05370'],
            ['Omar Carmona', 'Computação Paralela', 'SUP.01319'],

            ['Paulo Pacini', 'Direito Digital', 'SUP.07793'],

            ['Salete Farias', 'Gerenciamento de Projetos em TI', 'SUP.05390'],
            ['Salete Farias', 'Internet das Coisas', 'SUP.07807'],

            ['Silvana Brito', 'Língua Brasileira de Sinais (Libras)', 'SUP.07789'],

            ['Ulibiran Chaves', 'Álgebra Linear e Geometria Analítica', 'SUP.05367']
        ];

        $stmtDisciplina = $pdo->prepare("
            INSERT INTO disciplina (codigo, nome)
            VALUES (?, ?)
        ");

        $stmtBuscarProfessor = $pdo->prepare("
            SELECT id FROM usuario
            WHERE nome_completo = ?
            LIMIT 1
        ");

        $stmtBuscarDisciplina = $pdo->prepare("
            SELECT id FROM disciplina
            WHERE codigo = ?
            LIMIT 1
        ");

        $stmtVinculo = $pdo->prepare("
            INSERT INTO professor_disciplina
            (professor_id, disciplina_id)
            VALUES (?, ?)
        ");

        $stmtReserva = $pdo->prepare("
            INSERT INTO reserva_laboratorio
            (
                professor_id,
                disciplina_id,
                laboratorio_id,
                dia_semana,
                horario_inicio,
                horario_fim,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, 'ativa')
        ");

        $dias = [
            'segunda',
            'terca',
            'quarta',
            'quinta',
            'sexta'
        ];

        foreach ($disciplinas as $index => $disciplina) {

            $professorNome = $disciplina[0];
            $nomeDisciplina = $disciplina[1];
            $codigo = $disciplina[2];

            // Inserir disciplina
            $stmtDisciplina->execute([
                $codigo,
                $nomeDisciplina
            ]);

            // Buscar professor
            $stmtBuscarProfessor->execute([$professorNome]);
            $professor = $stmtBuscarProfessor->fetch();

            // Buscar disciplina
            $stmtBuscarDisciplina->execute([$codigo]);
            $disciplinaBanco = $stmtBuscarDisciplina->fetch();

            if ($professor && $disciplinaBanco) {

                // Vincular professor e disciplina
                $stmtVinculo->execute([
                    $professor['id'],
                    $disciplinaBanco['id']
                ]);

                // Distribuição automática dos laboratórios
                $laboratorioId = ($index % 3) + 1;

                // Distribuição automática dos dias
                $dia = $dias[$index % 5];

                // Horários automáticos
                $horaInicio = sprintf(
                    '%02d:00:00',
                    8 + ($index % 8)
                );

                $horaFim = sprintf(
                    '%02d:00:00',
                    10 + ($index % 8)
                );

                // Criar reserva
                $stmtReserva->execute([
                    $professor['id'],
                    $disciplinaBanco['id'],
                    $laboratorioId,
                    $dia,
                    $horaInicio,
                    $horaFim
                ]);
            }
        }

        $mensagens[] = "✅ Disciplinas, vínculos e reservas criadas!";
    } else {
        $mensagens[] = "✅ Disciplinas já cadastradas!";
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