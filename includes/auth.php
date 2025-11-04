<?php
// Verifica se o usuário está logado e, caso contrário, redireciona para a tela de login.

// 1. Inicia a sessão, se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
?>
