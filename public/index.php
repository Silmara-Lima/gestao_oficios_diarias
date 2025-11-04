<?php
session_start();

require_once __DIR__ . '/../config/db.php';

// Verifica se o usuário já está logado
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$mensagem_erro = '';

// Processa a submissão do formulário de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $senha_digitada = $_POST['senha'] ?? '';

    if (empty($username) || empty($senha_digitada)) {
        $mensagem_erro = '<div class="alert alert-danger">Preencha todos os campos.</div>';
    } else {
        try {
            // Busca usuário e hash da senha
            $stmt = $db->prepare("
                SELECT id, username, senha, nivel_acesso
                FROM users
                WHERE username = :username
            ");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verifica se encontrou usuário e senha está correta
            if ($user && password_verify($senha_digitada, $user['senha'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_nivel_acesso'] = $user['nivel_acesso'];

                header('Location: dashboard.php');
                exit;
            } else {
                $mensagem_erro = '<div class="alert alert-danger">Nome de usuário ou senha inválidos.</div>';
            }
        } catch (PDOException $e) {
            $mensagem_erro = '<div class="alert alert-danger">Erro de banco de dados: ' . $e->getMessage() . '</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso ao Sistema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 25px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
    </style>
</head>

<body>
<div class="login-container">
    <h2 class="text-center mb-4">Sistema de Gestão de Ofícios e Diárias</h2>
    <h4 class="text-center mb-4">Login</h4>

    <?php echo $mensagem_erro; ?>

    <form method="POST" action="index.php">
        <div class="mb-3">
            <label for="username" class="form-label">Usuário</label>
            <input type="text" class="form-control" id="username" name="username" required>
        </div>

        <div class="mb-3">
            <label for="senha" class="form-label">Senha</label>
            <input type="password" class="form-control" id="senha" name="senha" required>
        </div>

        <button type="submit" class="btn btn-primary w-100 mt-3">Entrar</button>
    </form>
</div>
</body>
</html>