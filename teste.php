<?php
session_start();
echo "<h1>Debug da Sessão</h1>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Data:\n";
var_dump($_SESSION);
echo "</pre>";

if (isset($_SESSION['usuario_id'])) {
    echo "<p style='color:red'>⚠️ Você está logado! Sessão ID: " . $_SESSION['usuario_id'] . "</p>";
    echo '<a href="logout.php">Clique aqui para fazer logout</a>';
} else {
    echo "<p style='color:green'>✅ Nenhuma sessão ativa. Você precisa fazer login.</p>";
    echo '<a href="login.php">Ir para o login</a>';
}
?>