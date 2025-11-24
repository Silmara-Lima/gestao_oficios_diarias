<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

// Carregar configurações existentes
$stmt = $db->query("SELECT * FROM configuracoes LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);

$cabecalho = $config['cabecalho'] ?? '';
$rodape = $config['rodape'] ?? '';

// Salvar alterações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $novo_cabecalho = $_POST['cabecalho'] ?? '';
    $novo_rodape = $_POST['rodape'] ?? '';

    $stmt = $db->prepare("UPDATE configuracoes SET cabecalho = ?, rodape = ? WHERE id = 1");
    $stmt->execute([$novo_cabecalho, $novo_rodape]);

    $salvo = true;

    // recarregar
    $cabecalho = $novo_cabecalho;
    $rodape = $novo_rodape;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações do Sistema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body { background-color: #f8f9fa; }
    </style>
</head>
<body>

<div class="container mt-5">

    <!-- TÍTULO E BOTÕES -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Configurações do Sistema</h1>
        <a href="index.php" class="btn btn-secondary">Voltar ao Dashboard</a>
    </div>

    <?php if (!empty($salvo)): ?>
        <div class="alert alert-success">Configurações salvas com sucesso.</div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">

            <form method="post">

                <div class="mb-3">
                    <label class="form-label"><strong>Cabeçalho do Documento</strong></label>
                    <textarea name="cabecalho" class="form-control" rows="5" required><?php echo htmlspecialchars($cabecalho); ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label"><strong>Rodapé do Documento</strong></label>
                    <textarea name="rodape" class="form-control" rows="5" required><?php echo htmlspecialchars($rodape); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary w-100">Salvar Configurações</button>
            </form>

        </div>
    </div>

</div>

</body>
</html>
