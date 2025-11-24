<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

// Configurações de Debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$stmtCfg = $db->query("SELECT cabecalho, rodape FROM configuracoes WHERE id = 1 LIMIT 1");
$cfg = $stmtCfg->fetch(PDO::FETCH_ASSOC);

$cabecalho_oficio = $cfg['cabecalho'] ?? '';
$rodape_oficio = $cfg['rodape'] ?? '';

function formatarDataPorExtenso($data_iso) {
    if (empty($data_iso)) {
        return 'Data Inválida';
    }
        
    date_default_timezone_set('America/Sao_Paulo');     
    
    try {
        $dateObj = new DateTime($data_iso . ' 12:00:00');
    } catch (Exception $e) {
        return 'Data Inválida';
    }
    
    if (!extension_loaded('intl')) {
        $timestamp = $dateObj->getTimestamp();
        return ucfirst(date('d \d\e M \d\e Y', $timestamp)); 
    }
    
    $formatter = new IntlDateFormatter(
        'pt_BR',
        IntlDateFormatter::LONG,
        IntlDateFormatter::NONE,
        'America/Sao_Paulo',
        IntlDateFormatter::GREGORIAN,
        'dd \'de\' LLLL \'de\' yyyy'
    );
    
    $data_extenso = $formatter->format($dateObj);
    return ucfirst($data_extenso);
}

function validarNome($nome) {
    return preg_match('/^[A-ZÀ-Ÿa-zà-ÿ ]+$/', $nome);
}

function validarCargo($cargo) {
    return preg_match('/^[A-ZÀ-Ÿa-zà-ÿ ]+$/', $cargo);
}

function validarMatricula($mat) {
    return preg_match('/^\d{4}-\d$/', $mat);
}

function getFuncionarios($db) {
    try {
        $stmt = $db->query("SELECT id, nome, matricula, cargo FROM funcionarios ORDER BY nome");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

function getFuncionarioById($db, $id) {
    try {
        $stmt = $db->prepare("SELECT nome, cargo, matricula FROM funcionarios WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return null;
    }
}

function getOficioById($db, $id) {
    try {
        $stmt = $db->prepare("SELECT * FROM oficios WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return null;
    }
}

function getProximoNumeroSequencial($db) {
    $ano_atual = date('Y');

    try {
        $stmt = $db->prepare("
            SELECT MAX(numero_sequencial) AS ultimo_numero
            FROM oficios
            WHERE ano = :ano
        ");
        $stmt->bindParam(':ano', $ano_atual);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        $proximo_numero = (int)$resultado['ultimo_numero'] + 1;

        return [
            'ano' => $ano_atual,
            'numero' => $proximo_numero,
            'numero_completo' => str_pad($proximo_numero, 4, '0', STR_PAD_LEFT) . '/' . $ano_atual
        ];

    } catch (PDOException $e) {
        return ['ano' => $ano_atual, 'numero' => 1, 'numero_completo' => '0001/' . $ano_atual];
    }
}

function gerarTabela($oficios) {
    $html = '
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Número</th>
                <th>Assunto</th>
                <th>Destinatário</th>
                <th>Elaborador</th>
                <th>Data</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>';

    foreach ($oficios as $o) {
        $numero = htmlspecialchars($o['numero_completo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $assunto = htmlspecialchars($o['assunto'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $destinatario = htmlspecialchars($o['destinatario'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $elaborador = htmlspecialchars($o['nome_elaborador'] ?? 'N/D', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $data_formatada = date('d/m/Y', strtotime($o['data_emissao']));

        $assuntoJs = addslashes($assunto);
        $destinatarioJs = addslashes($destinatario);
        $corpoJs = addslashes($o['corpo']);        
        
        $dataEmissaoJs = $o['data_emissao'];
        $pronomeTratamentoJs = addslashes($o['pronome_tratamento'] ?? 'Ao Sr.');
        $saudacaoJs = addslashes($o['saudacao'] ?? 'Prezado Senhor,');
        
        $html .= "
        <tr>
            <td data-id='{$o['id']}'>{$numero}</td>
            <td>{$assunto}</td>
            <td>{$destinatario}</td>
            <td>{$elaborador}</td>
            <td>{$data_formatada}</td>
            <td>
                <button class='btn btn-sm btn-info' onclick=\"visualizarOficio({$o['id']})\">Ver</button>
                <button class='btn btn-sm btn-primary' onclick=\"editarOficio({$o['id']}, '{$assuntoJs}', '{$destinatarioJs}', '{$corpoJs}', '{$o['funcionario_id']}', '{$dataEmissaoJs}', '{$pronomeTratamentoJs}', '{$saudacaoJs}')\">Editar</button>
                <button class='btn btn-sm btn-danger' onclick=\"excluirOficio({$o['id']})\">Excluir</button>
            </td>
        </tr>";
    }

    $html .= '</tbody></table>';
    return $html;
}

// Bloco AJAX
if (isset($_POST['acao'])) {
    $acao = $_POST['acao'];

    if ($acao == 'table') {
        try {
            $stmt = $db->query("
                SELECT o.*, f.nome AS nome_elaborador
                FROM oficios o
                LEFT JOIN funcionarios f ON o.funcionario_id = f.id
                ORDER BY o.ano DESC, o.numero_sequencial DESC
            ");
            echo gerarTabela($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {
            echo '<div class="alert alert-warning">Erro ao carregar dados: ' . $e->getMessage() . '</div>';
        }
        exit;
    }

    if ($acao == 'get_elaborador_data') {
        header('Content-Type: application/json');
        $id = intval($_POST['id'] ?? 0);
        $data = getFuncionarioById($db, $id);
        if ($data) {
            echo json_encode(['nome' => $data['nome'], 'cargo' => $data['cargo'], 'matricula' => $data['matricula']]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Funcionário não encontrado.']);
        }
        exit;
    }
    
    if ($acao == 'get_oficio_data') {
        header('Content-Type: application/json');
        $id = intval($_POST['id'] ?? 0);
        $oficio = getOficioById($db, $id);
        if ($oficio) {
            echo json_encode($oficio);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Ofício não encontrado.']);
        }
        exit;
    }    
    
    if ($acao == 'format_date') {
        echo formatarDataPorExtenso($_POST['data']);
        exit;
    }

    if ($acao == 'get_next_number') {
        header('Content-Type: application/json');
        echo json_encode(['numero' => getProximoNumeroSequencial($db)['numero_completo']]);
        exit;
    }

    if ($acao == 'add') {
        $assunto = trim($_POST['assunto']);
        $destinatario = trim($_POST['destinatario']);
        $corpo = trim($_POST['corpo']);
        $elaborado_por_id = intval($_POST['elaborado_por_id'] ?? 0);
        $novo_elaborador_nome = trim($_POST['novo_elaborador_nome'] ?? '');
        
        $nova_matricula = trim($_POST['nova_matricula'] ?? ''); 
        $novo_cargo = trim($_POST['novo_cargo'] ?? ''); 
        
        $data_emissao = trim($_POST['data_emissao'] ?? date('Y-m-d'));
        $pronome_tratamento = trim($_POST['pronome_tratamento'] ?? '');
        $saudacao = trim($_POST['saudacao'] ?? '');
        

        if (!$assunto || !$destinatario || !$corpo || !$data_emissao) {
            echo '<div class="alert alert-danger">Preencha todos os campos obrigatórios, incluindo a Data de Emissão.</div>';
            exit;
        }

        $funcionario_id = null;

        if ($elaborado_por_id > 0) {
            $funcionario_id = $elaborado_por_id;
        } elseif (!empty($novo_elaborador_nome)) {
            
            if (!validarNome($novo_elaborador_nome)) {
                echo '<div class="alert alert-danger">Nome do novo elaborador inválido. Use apenas letras e espaços.</div>';
                exit;
            }

            if (!validarCargo($novo_elaborador_cargo)) {
                echo '<div class="alert alert-danger">Cargo do novo elaborador inválido. Use apenas letras e espaços.</div>';
                exit;
            }
            
            if (!validarMatricula($nova_matricula)) {
                echo '<div class="alert alert-danger">Matrícula do novo elaborador inválida. Formato esperado: 0000-0.</div>';
                exit;
            }
            
            if (!validarCargo($novo_cargo)) {
                echo '<div class="alert alert-danger">Cargo do novo elaborador inválido.</div>';
                exit;
            }

            try {
                $nome_upper = strtoupper($novo_elaborador_nome);
                $cargo_upper = strtoupper($novo_cargo);                
                
                $stmt = $db->prepare("INSERT INTO funcionarios (nome, matricula, cargo) VALUES (:nome, :matricula, :cargo) RETURNING id");
                $stmt->bindParam(':nome', $nome_upper);
                $stmt->bindParam(':matricula', $nova_matricula);
                $stmt->bindParam(':cargo', $cargo_upper);
                $stmt->execute();
                
                $novo_func = $stmt->fetch(PDO::FETCH_ASSOC);
                $funcionario_id = $novo_func['id'];
                
                echo '<div class="alert alert-success">Novo Elaborador **' . $nome_upper . '** cadastrado e selecionado.</div>';

            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'unique constraint') !== false) {
                    echo '<div class="alert alert-danger">Erro: A matrícula informada já existe na base de dados.</div>';
                } else {
                    echo '<div class="alert alert-danger">Erro ao criar novo elaborador: ' . $e->getMessage() . '</div>';
                }
                exit;
            }
        } else {
            echo '<div class="alert alert-danger">Selecione ou informe um elaborador.</div>';
            exit;
        }

        try {
            $seq = getProximoNumeroSequencial($db);
            $stmt = $db->prepare("
                INSERT INTO oficios (ano, numero_sequencial, numero_completo, assunto, destinatario, corpo, criado_por_user_id, funcionario_id, data_emissao, pronome_tratamento, saudacao)
                VALUES (:ano, :num, :numcomp, :ass, :dest, :corpo, :user, :func, :dataemissao, :pronome, :saudacao)
            ");
            $stmt->execute([
                ':ano' => $seq['ano'],
                ':num' => $seq['numero'],
                ':numcomp' => $seq['numero_completo'],
                ':ass' => $assunto,
                ':dest' => $destinatario,
                ':corpo' => $corpo,
                ':user' => $_SESSION['user_id'],
                ':func' => $funcionario_id,
                ':dataemissao' => $data_emissao,
                ':pronome' => $pronome_tratamento,
                ':saudacao' => $saudacao
            ]);

            echo '<div class="alert alert-success">Ofício **' . $seq['numero_completo'] . '** registrado com sucesso!</div>';

        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'unique constraint') !== false) {
                echo '<div class="alert alert-danger">Erro: Conflito de numeração. Tente recarregar a página para obter um novo número.</div>';
            } else {
                echo '<div class="alert alert-danger">Erro ao registrar ofício: ' . $e->getMessage() . '</div>';
            }
        }
        exit;
    }

    if ($acao == 'edit') {
        try {
            $stmt = $db->prepare("
                UPDATE oficios SET 
                    assunto=:ass, 
                    destinatario=:dest, 
                    corpo=:corpo, 
                    funcionario_id=:func, 
                    data_emissao=:dataemissao,
                    pronome_tratamento=:pronome,
                    saudacao=:saudacao
                WHERE id=:id
            ");
            $stmt->execute([
                ':ass' => $_POST['assunto'],
                ':dest' => $_POST['destinatario'],
                ':corpo' => $_POST['corpo'],
                ':func' => $_POST['elaborado_por_id'],
                ':dataemissao' => $_POST['data_emissao'],
                ':pronome' => $_POST['pronome_tratamento'],
                ':saudacao' => $_POST['saudacao'],
                ':id' => $_POST['id']
            ]);
            echo '<div class="alert alert-success">Ofício atualizado com sucesso!</div>';
        } catch (PDOException $e) {
            echo '<div class="alert alert-danger">Erro ao atualizar: ' . $e->getMessage() . '</div>';
        }
        exit;
    }

    if ($acao == 'delete') {
        try {
            $stmt = $db->prepare("DELETE FROM oficios WHERE id=:id");
            $stmt->execute([':id' => $_POST['id']]);
            echo '<div class="alert alert-success">Ofício excluído com sucesso!</div>';
        } catch (PDOException $e) {
            echo '<div class="alert alert-danger">Erro ao excluir: ' . $e->getMessage() . '</div>';
        }
        exit;
    }
}

$funcionarios_list = getFuncionarios($db);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Gestor de Ofícios - CRUD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    .oficio-modal-content {
        font-family: 'Times New Roman', Times, serif;
        font-size: 12pt;
        padding: 30px;
    }

    .oficio-header {
        text-align: right;
        margin-bottom: 20pt;
    }

    .oficio-dest {
        margin-bottom: 20pt;
    }

    .oficio-assunto {
        font-weight: bold;
        margin-bottom: 15pt;
    }

    .oficio-corpo {
        white-space: pre-wrap;
        text-align: justify;
        line-height: 1.5;
        margin-bottom: 30pt;
    }

    .oficio-assinatura {
        font-weight: bold;
        text-align: center;
        margin-top: 60pt;
    }

    .oficio-assinatura-nome {
        font-weight: bold;
        margin-top: 5pt;
        border-top: 1px solid #000;
        display: inline-block;
        padding-top: 3px;
    }

    .oficio-assinatura-cargo {
        display: block;
        font-size: 10pt;
        margin-top: 2px;
    }
    </style>
</head>

<body>
    <div class="container mt-4">
        <h2>Cadastro e Controle de Ofícios</h2>
        <a href="dashboard.php" class="btn btn-sm btn-outline-secondary mb-3">← Voltar ao Dashboard</a>

        <div id="mensagem"></div>

        <form id="formOficio" class="p-4 border rounded bg-light mb-4">
            <input type="hidden" id="oficioId" name="id" value="">

            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="numero" class="form-label">Número do Ofício</label>
                    <input type="text" id="numero" class="form-control" readonly required>
                    <small class="text-muted">Gerado automaticamente.</small>
                </div>

                <div class="col-md-3">
                    <label for="data_emissao" class="form-label">Data de Emissão</label>
                    <input type="date" id="data_emissao" name="data_emissao" class="form-control"
                        value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="assunto" class="form-label">Assunto</label>
                    <input type="text" id="assunto" name="assunto" class="form-control" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="destinatario" class="form-label">Destinatário</label>
                <input type="text" id="destinatario" name="destinatario" class="form-control" required>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="pronome_tratamento" class="form-label">Pronome de Tratamento (Ex: Ao Sr.)</label>
                    <input type="text" id="pronome_tratamento" name="pronome_tratamento" class="form-control"
                        value="Ao Sr.">
                </div>
                <div class="col-md-8">
                    <label for="saudacao" class="form-label">Saudação (Ex: Prezado Senhor,)</label>
                    <input type="text" id="saudacao" name="saudacao" class="form-control" value="Prezado Senhor,"
                        required>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="elaborado_por_id" class="form-label">Elaborado por (Funcionário)</label>
                    <select id="elaborado_por_id" name="elaborado_por_id" class="form-select" required>
                        <option value="" selected disabled>Selecione o funcionário</option>
                        <?php 
                    foreach ($funcionarios_list as $func): 
                        $info = htmlspecialchars($func['nome'] . (!empty($func['matricula']) ? ' (' . $func['matricula'] . ')' : ''));
                    ?>
                        <option value="<?php echo $func['id']; ?>"
                            data-cargo="<?php echo htmlspecialchars($func['cargo'] ?? ''); ?>"
                            data-matricula="<?php echo htmlspecialchars($func['matricula'] ?? ''); ?>">
                            <?php echo $info; ?>
                        </option>
                        <?php endforeach; ?>
                        <option value="0">--- Adicionar Outro Nome ---</option>
                    </select>
                </div>

                <div class="col-md-6" id="novoElaboradorContainer" style="display: none;">
                    <div class="row">
                        <div class="col-md-4 mb-2 mb-md-0">
                            <label for="novo_elaborador_nome" class="form-label">Nome (Novo)</label>
                            <input type="text" id="novo_elaborador_nome" name="novo_elaborador_nome"
                                class="form-control" disabled>
                        </div>
                        <div class="col-md-4 mb-2 mb-md-0">
                            <label for="novo_cargo" class="form-label">Cargo (Novo)</label>
                            <input type="text" id="novo_cargo" name="novo_cargo" class="form-control" disabled>
                        </div>
                        <div class="col-md-4">
                            <label for="nova_matricula" class="form-label">Matrícula</label>
                            <input type="text" id="nova_matricula" name="nova_matricula" class="form-control" disabled
                                maxlength="6">
                            <small class="text-muted">Formato: 0000-0</small>
                        </div>
                    </div>
                    <small class="text-muted">Será salvo na tabela de Funcionários.</small>
                </div>
            </div>

            <div class="mb-3">
                <label for="corpo" class="form-label">Corpo do Ofício / Conteúdo</label>
                <textarea id="corpo" name="corpo" class="form-control" rows="6" required></textarea>
            </div>

            <div class="mt-4">
                <button type="button" id="btnPreview" class="btn btn-info me-2">Pré-Visualizar Ofício</button>
                <button type="submit" id="btnSubmit" class="btn btn-success">Registrar Ofício Definitivo</button>
                <button type="button" id="btnCancel" class="btn btn-secondary d-none" onclick="resetForm()">Cancelar
                    Edição</button>
            </div>
        </form>

        <h3 class="mt-5">Ofícios Registrados</h3>
        <div id="tabelaOficios"></div>
    </div>

    <div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="previewModalLabel">Pré-Visualização do Ofício</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="oficioContent" class="oficio-modal-content">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar
                        Pré-Visualização</button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    const oficioId = document.getElementById('oficioId');
    const numeroInput = document.getElementById('numero');
    const assuntoInput = document.getElementById('assunto');
    const destinatarioInput = document.getElementById('destinatario');
    const corpoInput = document.getElementById('corpo');
    const formOficio = document.getElementById('formOficio');
    const btnSubmit = document.getElementById('btnSubmit');
    const btnCancel = document.getElementById('btnCancel');
    const tabelaDiv = document.getElementById('tabelaOficios');
    const mensagemDiv = document.getElementById('mensagem');

    const selectElaborador = document.getElementById('elaborado_por_id');
    const novoElaboradorContainer = document.getElementById('novoElaboradorContainer');
    const novoElaboradorInput = document.getElementById('novo_elaborador_nome');

    const novoCargoInput = document.getElementById('novo_cargo');
    const novaMatriculaInput = document.getElementById('nova_matricula');

    const btnPreview = document.getElementById('btnPreview');
    const oficioContentDiv = document.getElementById('oficioContent');

    const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));

    const dataEmissaoInput = document.getElementById('data_emissao');
    const pronomeTratamentoInput = document.getElementById('pronome_tratamento');
    const saudacaoInput = document.getElementById('saudacao');

    function exibirMensagem(html, delay = 3000) {
        mensagemDiv.innerHTML = html;
        setTimeout(() => {
            mensagemDiv.innerHTML = '';
        }, delay);
    }

    novaMatriculaInput.addEventListener('input', () => {
        let valor = novaMatriculaInput.value.replace(/\D/g, '');
        if (valor.length > 4) valor = valor.slice(0, 4) + '-' + valor.slice(4, 5);
        novaMatriculaInput.value = valor;
    });

    novoElaboradorInput.addEventListener('input', () => {
        novoElaboradorInput.value = novoElaboradorInput.value.toUpperCase().replace(/[^A-ZÀ-Ÿ ]/g, '');
    });

    novoCargoInput.addEventListener('input', () => {
        novoCargoInput.value = novoCargoInput.value.toUpperCase();
    });

    function resetForm() {
        formOficio.reset();
        oficioId.value = '';
        btnSubmit.textContent = 'Registrar Ofício Definitivo';
        btnCancel.classList.add('d-none');

        novoElaboradorContainer.style.display = 'none';
        novoElaboradorInput.disabled = true;
        novoCargoInput.disabled = true;
        novaMatriculaInput.disabled = true;

        novoElaboradorInput.required = false;
        novoCargoInput.required = false;
        novaMatriculaInput.required = false;

        dataEmissaoInput.value = new Date().toISOString().substring(0, 10);

        pronomeTratamentoInput.value = 'Ao Sr.';
        saudacaoInput.value = 'Prezado Senhor,';

        carregarProximoNumero();
        assuntoInput.focus();
    }

    function carregarProximoNumero() {
        fetch('oficios.php', {
                method: 'POST',
                body: new URLSearchParams({
                    acao: 'get_next_number'
                })
            })
            .then(resp => resp.json())
            .then(data => {
                numeroInput.value = data.numero || 'Erro ao carregar';
            })
            .catch(err => {
                console.error("Erro ao carregar número:", err);
                numeroInput.value = 'Erro de rede';
            });
    }

    function carregarOficios() {
        fetch('oficios.php', {
                method: 'POST',
                body: new URLSearchParams({
                    acao: 'table'
                })
            })
            .then(resp => resp.text())
            .then(html => tabelaDiv.innerHTML = html);
    }

    selectElaborador.addEventListener('change', () => {
        const isNew = selectElaborador.value === '0';
        novoElaboradorContainer.style.display = isNew ? 'block' : 'none';

        novoElaboradorInput.disabled = !isNew;
        novoCargoInput.disabled = !isNew;
        novaMatriculaInput.disabled = !isNew;

        novoElaboradorInput.required = isNew && !oficioId.value;
        novoCargoInput.required = isNew && !oficioId.value;
        novaMatriculaInput.required = isNew && !oficioId.value;

        if (isNew) novoElaboradorInput.focus();
    });


    function getElaboradorData() {
        const selectedOption = selectElaborador.options[selectElaborador.selectedIndex];

        let nome = '';
        let cargo = '';
        let matricula = '';

        if (selectElaborador.value === '0' && novoElaboradorInput.value.trim() !== '') {
            nome = novoElaboradorInput.value.trim().toUpperCase();
            cargo = novoCargoInput.value.trim().toUpperCase();
            matricula = novaMatriculaInput.value.trim();

            if (!nome || !cargo || !matricula) {
                return null;
            }
            return {
                nome: nome,
                cargo: cargo,
                matricula: matricula
            };

        } else if (selectElaborador.value !== '' && selectElaborador.value !== '0') {
            nome = selectedOption.textContent.trim().replace(/\s+\(.*\)$/, '');
            cargo = selectedOption.getAttribute('data-cargo');
            matricula = selectedOption.getAttribute('data-matricula');
            return {
                nome: nome,
                cargo: cargo,
                matricula: matricula
            };
        }
        return null;
    }

    btnPreview.addEventListener('click', async (e) => {
        e.preventDefault();

        if (!formOficio.checkValidity()) {
            formOficio.reportValidity();
            exibirMensagem('<div class="alert alert-warning">Preencha todos os campos obrigatórios.</div>',
                5000);
            return;
        }

        const elaboradorData = getElaboradorData();

        if (!elaboradorData) {
            exibirMensagem(
                '<div class="alert alert-warning">Selecione ou informe Nome, Matrícula e Cargo do Elaborador.</div>'
            );
            return;
        }

        const nomeElaborador = elaboradorData.nome;
        const cargoElaborador = elaboradorData.cargo;
        const matriculaElaborador = elaboradorData.matricula;

        const destinatario = destinatarioInput.value;
        const assunto = assuntoInput.value;
        const corpo = corpoInput.value.replace(/\n/g, '<br>');
        const pronomeTratamento = pronomeTratamentoInput.value;
        const saudacao = saudacaoInput.value;
        const numero = numeroInput.value;
        const dataIso = dataEmissaoInput.value;

        let dataPorExtenso = 'Data Inválida';
        try {
            const resp = await fetch('oficios.php', {
                method: 'POST',
                body: new URLSearchParams({
                    acao: 'format_date',
                    data: dataIso
                })
            });
            dataPorExtenso = await resp.text();
        } catch (err) {
            console.error("Erro ao formatar data:", err);
        }

        const local = 'João Pessoa';

        const oficioHtml = `
        <div class="oficio-header">
            <div class="oficio-cabecalho" style="text-align:center; margin-bottom:20px; font-size:14px;">
                <?= nl2br($cabecalho_oficio) ?>
            </div>
            OFÍCIO Nº ${numero}
            <br>
            ${local}, ${dataPorExtenso}
        </div>

        <div class="oficio-dest">
            ${pronomeTratamento}
            <br>
            ${destinatario}
        </div>

        <div class="oficio-assunto">
            Assunto: ${assunto}
        </div>

        <div style="margin-bottom: 10pt;">
            ${saudacao}
        </div>

        <div class="oficio-corpo">
            ${corpo}
        </div>

        <div style="font-weight: bold; margin-top: 15pt;">
            Atenciosamente,
        </div>

        <div class="oficio-assinatura">
            <br>
            <div class="oficio-assinatura-nome">
                ${nomeElaborador}
                ${matriculaElaborador ? `<span class="oficio-assinatura-cargo" style="font-size: 11pt;">Matrícula: ${matriculaElaborador}</span>` : ''} 
                <span class="oficio-assinatura-cargo">${cargoElaborador}</span>
            </div>
            <div class="oficio-rodape" style="text-align:center; margin-top:40px; font-size:13px; border-top:1px solid #ccc; padding-top:10px;">
                <?= nl2br($rodape_oficio) ?>
            </div>
            <br>
        </div>
    `;

        oficioContentDiv.innerHTML = oficioHtml;
        previewModal.show();
    });

    window.visualizarOficio = (id) => {
        fetch('oficios.php', {
                method: 'POST',
                body: new URLSearchParams({
                    acao: 'get_oficio_data',
                    id: id
                })
            })
            .then(resp => {
                if (!resp.ok) throw new Error("Erro ao buscar ofício.");
                return resp.json();
            })
            .then(async (oficioData) => {
                const corpo = oficioData.corpo.replace(/\n/g, '<br>');
                const dataIso = oficioData.data_emissao;
                const funcId = oficioData.funcionario_id;
                const pronomeTratamento = oficioData.pronome_tratamento;
                const saudacao = oficioData.saudacao;

                let elaboradorData = {
                    nome: 'NOME N/D',
                    cargo: 'CARGO N/D',
                    matricula: ''
                };
                try {
                    const funcResp = await fetch('oficios.php', {
                        method: 'POST',
                        body: new URLSearchParams({
                            acao: 'get_elaborador_data',
                            id: funcId
                        })
                    });
                    if (funcResp.ok) {
                        elaboradorData = await funcResp.json();
                    }
                } catch (e) {
                    console.error("Erro ao buscar dados do elaborador:", e);
                }

                const nomeElaborador = elaboradorData.nome;
                const cargoElaborador = elaboradorData.cargo;
                const matriculaElaborador = elaboradorData.matricula ?? '';

                let dataPorExtenso = 'Data Inválida';
                try {
                    const dateResp = await fetch('oficios.php', {
                        method: 'POST',
                        body: new URLSearchParams({
                            acao: 'format_date',
                            data: dataIso
                        })
                    });
                    dataPorExtenso = await dateResp.text();
                } catch (err) {
                    console.error("Erro ao formatar data:", err);
                }

                const local = 'João Pessoa';

                const oficioHtml = `
            <div class="oficio-header">
                OFÍCIO Nº ${oficioData.numero_completo}
                <br>
                ${local}, ${dataPorExtenso}
            </div>

            <div class="oficio-dest">
                ${pronomeTratamento}
                <br>
                ${oficioData.destinatario}
            </div>

            <div class="oficio-assunto">
                Assunto: ${oficioData.assunto}
            </div>

            <div style="margin-bottom: 10pt;">
                ${saudacao}
            </div>

            <div class="oficio-corpo">
                ${corpo}
            </div>

            <div style="font-weight: bold; margin-top: 15pt;">
                Atenciosamente,
            </div>

            <div class="oficio-assinatura">
                <br>
                <div class="oficio-assinatura-nome">
                    ${nomeElaborador}
                    ${matriculaElaborador ? `<span class="oficio-assinatura-cargo" style="font-size: 11pt;">Matrícula: ${matriculaElaborador}</span>` : ''} 
                    <span class="oficio-assinatura-cargo">${cargoElaborador}</span>
                </div>
                <br>
            </div>
        `;

                oficioContentDiv.innerHTML = oficioHtml;
                previewModal.show();

            })
            .catch(error => {
                console.error("Erro na visualização do ofício:", error);
                exibirMensagem(`<div class="alert alert-danger">${error.message}</div>`);
            });
    }


    function editarOficio(id, assunto, destinatario, corpo, funcId, dataEmissao, pronomeTratamento, saudacao) {
        oficioId.value = id;
        assuntoInput.value = assunto;
        destinatarioInput.value = destinatario;
        corpoInput.value = corpo;
        dataEmissaoInput.value = dataEmissao;
        pronomeTratamentoInput.value = pronomeTratamento;
        saudacaoInput.value = saudacao;

        selectElaborador.value = funcId;
        if (funcId === '0') {
            novoElaboradorContainer.style.display = 'block';
        } else {
            novoElaboradorContainer.style.display = 'none';
            novoElaboradorInput.disabled = true;
            novoCargoInput.disabled = true;
            novaMatriculaInput.disabled = true;
        }

        numeroInput.value = 'Editando...';

        btnSubmit.textContent = 'Salvar Alterações';
        btnCancel.classList.remove('d-none');
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    function excluirOficio(id) {
        if (confirm("Tem certeza que deseja excluir este Ofício? Esta ação não pode ser desfeita.")) {
            fetch('oficios.php', {
                    method: 'POST',
                    body: new URLSearchParams({
                        acao: 'delete',
                        id: id
                    })
                })
                .then(resp => resp.text())
                .then(msg => {
                    exibirMensagem(msg);
                    carregarOficios();
                    resetForm();
                })
                .catch(err => {
                    exibirMensagem('<div class="alert alert-danger">Erro de rede ao excluir.</div>');
                    console.error(err);
                });
        }
    }

    formOficio.addEventListener('submit', (e) => {
        e.preventDefault();

        const elaboradorData = getElaboradorData();
        if (!elaboradorData) {
            exibirMensagem(
                '<div class="alert alert-danger">Selecione ou informe Nome, Matrícula e Cargo do Elaborador.</div>'
            );
            return;
        }

        const acao = oficioId.value ? 'edit' : 'add';
        const dados = new URLSearchParams(new FormData(formOficio));
        dados.append('acao', acao);

        if (acao === 'edit') {
            if (selectElaborador.value !== '0') {
                dados.delete('novo_elaborador_nome');
                dados.delete('novo_cargo');
                dados.delete('nova_matricula');
            }
        }


        fetch('oficios.php', {
                method: 'POST',
                body: dados
            })
            .then(resp => resp.text())
            .then(msg => {
                exibirMensagem(msg);
                carregarOficios();
                resetForm();
            })
            .catch(err => {
                exibirMensagem('<div class="alert alert-danger">Erro de rede. Verifique o console.</div>');
                console.error(err);
            });
    });

    document.addEventListener('DOMContentLoaded', () => {
        carregarProximoNumero();
        carregarOficios();
    });
    </script>
</body>

</html>