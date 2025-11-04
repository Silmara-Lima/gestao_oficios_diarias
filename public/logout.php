<?php
// 1. Inicia a sessão para garantir que podemos manipulá-la.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Destrói todas as variáveis de sessão.
$_SESSION = [];

// 3. Se estiver usando cookies de sessão, apaga o cookie.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 4. Finalmente, destrói a sessão no servidor.
session_destroy();

// 5. Redireciona para a página de login.
header('Location: index.php');
exit;
?>
