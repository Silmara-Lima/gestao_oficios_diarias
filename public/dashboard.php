<?php

// 1. Inclui o script de autenticação (garante login ativo).
require_once __DIR__ . '/../includes/auth.php';

// 2. Incluir o header padrão:
// require_once __DIR__ . '/../includes/header.php';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gestor de Ofícios e Diárias</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Bem-vindo, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
            <a href="logout.php" class="btn btn-danger">Sair</a>
        </div>

        <p>Seu nível de acesso é: <strong><?php echo htmlspecialchars($_SESSION['user_nivel_acesso']); ?></strong>.</p>

        <hr>

        <h2>Módulos do Sistema</h2>

        <div class="row g-4 mt-3">

            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <h5 class="card-title">📝 Gerenciar Ofícios</h5>
                            <p class="card-text">Criação, edição e controle sequencial de ofícios.</p>
                        </div>
                        <a href="oficios.php" class="btn btn-primary w-100 mt-3">Acessar Ofícios</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <h5 class="card-title">💰 Gerenciar Diárias</h5>
                            <p class="card-text">Criação e controle de registros de diárias.</p>
                        </div>
                        <a href="diarias.php" class="btn btn-primary w-100 mt-3">Acessar Diárias</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <h5 class="card-title">👥 Cadastro de Funcionários</h5>
                            <p class="card-text">Manutenção do cadastro de funcionários (Nome e Matrícula).</p>
                        </div>
                        <a href="funcionarios.php" class="btn btn-primary w-100 mt-3">Acessar Funcionários</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <h5 class="card-title">⚙️ Configurações do Documento</h5>
                            <p class="card-text">Edição de cabeçalho e rodapé dos documentos.</p>
                        </div>
                        <a href="configuracoes.php" class="btn btn-secondary w-100 mt-3">Configurações</a>
                    </div>
                </div>
            </div>

        </div>

    </div>
</body>

</html>