<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

// Configurações de Debug (Manter apenas durante o desenvolvimento)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Funções de validação e helpers
 */
function validarNome($nome) {
    return preg_match('/^[A-ZÀ-Ÿ ]+$/', $nome);
}

function validarMatricula($mat) {
    return preg_match('/^\d{4}-\d$/', $mat);
}

/**
 * Função para gerar tabela HTML dos funcionários
 */
function gerarTabela($funcionarios) {
    $html = '<table class="table table-bordered"><thead><tr><th>ID</th><th>Nome</th><th>Matrícula</th><th>Ações</th></tr></thead><tbody>';

    foreach ($funcionarios as $f) {
        $nomeEsc = htmlspecialchars($f['nome'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $matEsc = htmlspecialchars($f['matricula'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $nomeJs = addslashes($nomeEsc);
        $matJs = addslashes($matEsc);

        $html .= "
        <tr>
            <td>{$f['id']}</td>
            <td>{$nomeEsc}</td>
            <td>{$matEsc}</td>
            <td>
                <button class='btn btn-sm btn-primary' onclick=\"editarFuncionario({$f['id']},'{$nomeJs}','{$matJs}')\">Editar</button>
                <button class='btn btn-sm btn-danger' onclick=\"excluirFuncionario({$f['id']})\">Excluir</button>
            </td>
        </tr>";
    }

    $html .= '</tbody></table>';
    return $html;
}

/**
 * Bloco de ações AJAX
 */
if (isset($_POST['acao'])) {
    $acao = $_POST['acao'];

    if ($acao == 'table') {
        try {
            $stmt = $db->query("SELECT * FROM funcionarios ORDER BY id");
            $funcionarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo gerarTabela($funcionarios);
        } catch (PDOException $e) {
            echo '<div class="alert alert-warning">Erro ao carregar dados: ' . $e->getMessage() . '</div>';
        }
        exit;
    }

    if ($acao == 'add') {
        $nome = strtoupper(trim($_POST['nome']));
        $matricula = trim($_POST['matricula']);

        if (!validarNome($nome)) { echo '<div class="alert alert-danger">Nome inválido! Apenas letras e espaços permitidos.</div>'; exit; }
        if (!validarMatricula($matricula)) { echo '<div class="alert alert-danger">Matrícula inválida! Formato esperado: 0000-0.</div>'; exit; }

        try {
            $stmt = $db->prepare("INSERT INTO funcionarios (nome, matricula) VALUES (:nome, :matricula)");
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':matricula', $matricula);
            $stmt->execute();
            echo '<div class="alert alert-success">Funcionário adicionado com sucesso!</div>';
        } catch (PDOException $e) {
            echo '<div class="alert alert-danger">Erro ao adicionar: ' . $e->getMessage() . '</div>';
        }
        exit;
    }

    if ($acao == 'edit') {
        $id = intval($_POST['id']);
        $nome = strtoupper(trim($_POST['nome']));
        $matricula = trim($_POST['matricula']);

        if (!validarNome($nome)) { echo '<div class="alert alert-danger">Nome inválido!</div>'; exit; }
        if (!validarMatricula($matricula)) { echo '<div class="alert alert-danger">Matrícula inválida!</div>'; exit; }

        try {
            $stmt = $db->prepare("UPDATE funcionarios SET nome=:nome, matricula=:matricula WHERE id=:id");
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':matricula', $matricula);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            echo '<div class="alert alert-success">Funcionário atualizado com sucesso!</div>';
        } catch (PDOException $e) {
            echo '<div class="alert alert-danger">Erro ao atualizar: ' . $e->getMessage() . '</div>';
        }
        exit;
    }

    if ($acao == 'delete') {
        $id = intval($_POST['id']);
        try {
            $stmt = $db->prepare("DELETE FROM funcionarios WHERE id=:id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            echo '<div class="alert alert-success">Funcionário excluído com sucesso!</div>';
        } catch (PDOException $e) {
            echo '<div class="alert alert-danger">Erro ao excluir: ' . $e->getMessage() . '</div>';
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gestor de Ofícios e Diárias - Funcionários</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/template_funcionarios.css" rel="stylesheet">
</head>
<body>

<div class="container mt-4">
    <h2>Cadastro de Funcionários</h2>

    <div id="mensagem"></div>

    <form id="formFuncionario" class="row g-3 mb-4">
        <input type="hidden" id="funcId" value="">
        <div class="col-md-5">
            <input type="text" id="nome" class="form-control" placeholder="Nome" required>
        </div>
        <div class="col-md-5">
            <input type="text" id="matricula" class="form-control" placeholder="Matrícula (0000-0)" required maxlength="6">
        </div>
        <div class="col-md-2">
            <button type="submit" id="btnSubmit" class="btn btn-success w-100">Adicionar</button>
        </div>
    </form>

    <div id="tabelaFuncionarios"></div>
</div>

<script>
const nomeInput = document.getElementById('nome');
const matriculaInput = document.getElementById('matricula');
const formFuncionario = document.getElementById('formFuncionario');
const btnSubmit = document.getElementById('btnSubmit');
const funcId = document.getElementById('funcId');
const tabelaDiv = document.getElementById('tabelaFuncionarios');
const mensagemDiv = document.getElementById('mensagem');

nomeInput.addEventListener('input', () => {
    nomeInput.value = nomeInput.value.toUpperCase().replace(/[^A-ZÀ-Ÿ ]/g,'');
});

matriculaInput.addEventListener('input', () => {
    let valor = matriculaInput.value.replace(/\D/g,'');
    if (valor.length > 4) valor = valor.slice(0,4)+'-'+valor.slice(4,5);
    matriculaInput.value = valor;
});

function validarNome(nome) { return /^[A-ZÀ-Ÿ ]+$/.test(nome.trim()); }
function validarMatricula(mat) { return /^\d{4}-\d$/.test(mat.trim()); }

function carregarFuncionarios() {
    fetch('funcionarios.php', {
        method: 'POST',
        body: new URLSearchParams({acao: 'table'})
    })
    .then(resp => resp.text())
    .then(html => tabelaDiv.innerHTML = html);
}

formFuncionario.addEventListener('submit', e => {
    e.preventDefault();
    const nome = nomeInput.value.trim();
    const matricula = matriculaInput.value.trim();
    const id = funcId.value;

    if (!validarNome(nome)) { mensagemDiv.innerHTML = '<div class="alert alert-danger">Nome inválido!</div>'; return; }
    if (!validarMatricula(matricula)) { mensagemDiv.innerHTML = '<div class="alert alert-danger">Matrícula inválida!</div>'; return; }

    const data = new URLSearchParams();
    data.append('acao', id ? 'edit' : 'add');
    data.append('nome', nome);
    data.append('matricula', matricula);
    if (id) data.append('id', id);

    fetch('funcionarios.php', { method: 'POST', body: data })
        .then(resp => resp.text())
        .then(html => {
            mensagemDiv.innerHTML = html;
            formFuncionario.reset();
            btnSubmit.textContent = 'Adicionar';
            funcId.value = '';
            nomeInput.focus();
            setTimeout(()=>{mensagemDiv.innerHTML='';},3000);
            carregarFuncionarios();
        });
});

function editarFuncionario(id, nome, matricula) {
    funcId.value = id;
    nomeInput.value = nome;
    matriculaInput.value = matricula;
    btnSubmit.textContent = 'Salvar';
    nomeInput.focus();
}

function excluirFuncionario(id) {
    if (!confirm('Deseja excluir este funcionário?')) return;

    const data = new URLSearchParams();
    data.append('acao','delete');
    data.append('id', id);

    fetch('funcionarios.php', { method: 'POST', body: data })
        .then(resp => resp.text())
        .then(html => {
            mensagemDiv.innerHTML = html;
            formFuncionario.reset();
            btnSubmit.textContent = 'Adicionar';
            funcId.value = '';
            setTimeout(()=>{mensagemDiv.innerHTML='';},3000);
            carregarFuncionarios();
        });
}

carregarFuncionarios();
</script>

</body>
</html>