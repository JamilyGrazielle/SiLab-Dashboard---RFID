<?php

require_once 'config.php';

$sql = file_get_contents('sql/banco.sql');

$pdo->exec($sql);

echo "Banco criado com sucesso!";