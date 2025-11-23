<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function formato_data($d){ if (!$d) return ''; $ts = strtotime($d); return $ts ? date('d/m/Y', $ts) : $d; }
function is_date_ok($d){ if ($d === null || $d === '') return true; return (bool)strtotime($d); }
function next_num($db){
    $st = $db->query("SELECT COALESCE(MAX(numero),0)+1 AS prox FROM diarias");
    $r = $st->fetch(PDO::FETCH_ASSOC);
    return (int)($r['prox'] ?? 1);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];

    if ($acao === 'add_funcionario') {
        $nome = strtoupper(trim($_POST['nome'] ?? ''));
        $cargo = strtoupper(trim($_POST['cargo'] ?? ''));
        $matricula = trim($_POST['matricula'] ?? '');

        if ($nome === '') { echo 'ERROR: Nome obrigatório'; exit; }
        if(!preg_match('/^[A-Z\s]+$/', $nome)){ echo 'ERROR: Nome inválido'; exit; }
        if($cargo && !preg_match('/^[A-Z\s]+$/', $cargo)){ echo 'ERROR: Cargo inválido'; exit; }

        try {
            $stmt = $db->prepare("INSERT INTO funcionarios (nome, matricula, cargo) VALUES (:nome,:matricula,:cargo)");
            $stmt->execute([
                ':nome'=>$nome,
                ':matricula'=>$matricula===''?null:$matricula,
                ':cargo'=>$cargo===''?null:$cargo
            ]);
            echo (int)$db->lastInsertId();
        } catch(PDOException $e){
            echo 'ERROR: '.$e->getMessage();
        }
        exit;
    }

    if ($acao === 'table') {
        try {
            $sql = "SELECT d.*, f.nome AS funcionario_nome, f.matricula AS funcionario_matricula, f.cargo
                    FROM diarias d
                    LEFT JOIN funcionarios f ON f.id = d.funcionario_id
                    ORDER BY d.numero DESC, d.id DESC";
            $res = $db->query($sql);
            $rows = $res->fetchAll(PDO::FETCH_ASSOC);

            echo '<table class="table table-bordered"><thead><tr>
                    <th>Nº</th><th>Servidor</th><th>Matrícula</th><th>Destino</th><th>Período</th><th>Objetivo</th><th>Ações</th>
                  </tr></thead><tbody>';
            if (!$rows) {
                echo '<tr><td colspan="7">Nenhuma diária cadastrada.</td></tr>';
            } else {
                foreach($rows as $r){
                    $periodo = formato_data($r['data_inicio']) . ($r['data_inicio'] && $r['data_fim'] ? ' até ' . formato_data($r['data_fim']) : '');
                    echo '<tr>';
                    echo '<td>'.esc($r['numero']).'</td>';
                    echo '<td>'.esc($r['funcionario_nome'] ?? '—').'</td>';
                    echo '<td>'.esc($r['funcionario_matricula'] ?? '').'</td>';
                    echo '<td>'.esc($r['destino']).'</td>';
                    echo '<td>'.esc($periodo).'</td>';
                    echo '<td>'.nl2br(esc($r['objetivo'])).'</td>';

                    $json = json_encode($r, JSON_UNESCAPED_UNICODE);
                    $json_b64 = base64_encode($json);

                    echo '<td>
                            <button class="btn btn-sm btn-primary" onclick="editarDiariaFromB64('.$r['id'].',\''.$json_b64.'\')">Editar</button>
                            <button class="btn btn-sm btn-danger" onclick="excluirDiaria('.$r['id'].')">Excluir</button>
                            <button class="btn btn-sm btn-secondary" onclick="visualizarDiaria('.$r['numero'].')">Visualizar</button>
                          </td>';
                    echo '</tr>';
                }
            }
            echo '</tbody></table>';
        } catch(PDOException $e){
            echo '<div class="alert alert-warning">Erro ao listar diárias: '.esc($e->getMessage()).'</div>';
        }
        exit;
    }

    if ($acao === 'add' || $acao === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $fid = intval($_POST['funcionario_id'] ?? 0);
        $destino = trim($_POST['destino'] ?? '');
        $di = $_POST['data_inicio'] ?? null;
        $df = $_POST['data_fim'] ?? null;
        $obj = trim($_POST['objetivo'] ?? '');
        $numero_forcado = isset($_POST['numero']) && $_POST['numero'] !== '' ? intval($_POST['numero']) : null;

        if($fid<=0){ echo '<div class="alert alert-danger">Selecione um funcionário.</div>'; exit; }
        if($destino==='' || $obj===''){ echo '<div class="alert alert-danger">Preencha destino e objetivo.</div>'; exit; }
        if(!is_date_ok($di) || !is_date_ok($df)){ echo '<div class="alert alert-danger">Datas inválidas.</div>'; exit; }
        if($di && $df && strtotime($di)>strtotime($df)){ echo '<div class="alert alert-danger">Data de início não pode ser depois da data de fim.</div>'; exit; }

        try {
            if ($acao==='add') {
                if ($numero_forcado) {
                    $st = $db->prepare("SELECT COUNT(*) as c FROM diarias WHERE numero=:n");
                    $st->execute([':n'=>$numero_forcado]);
                    $r = $st->fetch(PDO::FETCH_ASSOC);
                    if($r['c']>0){
                        echo '<div class="alert alert-danger">Número já existente.</div>'; exit;
                    }
                    $numero = $numero_forcado;
                } else { $numero = next_num($db); }

                $stmt = $db->prepare("INSERT INTO diarias (numero, funcionario_id, destino, data_inicio, data_fim, objetivo)
                                      VALUES (:numero,:fid,:dest,:di,:df,:obj)");
                $stmt->execute([
                    ':numero'=>$numero,
                    ':fid'=>$fid,
                    ':dest'=>$destino,
                    ':di'=>$di,
                    ':df'=>$df,
                    ':obj'=>$obj
                ]);
                echo '<div class="alert alert-success">Diária criada (Nº '.esc($numero).').</div>';
            } else {
                $stmt = $db->prepare("UPDATE diarias SET funcionario_id=:fid,destino=:dest,data_inicio=:di,data_fim=:df,objetivo=:obj WHERE id=:id");
                $stmt->execute([
                    ':fid'=>$fid,
                    ':dest'=>$destino,
                    ':di'=>$di,
                    ':df'=>$df,
                    ':obj'=>$obj,
                    ':id'=>$id
                ]);
                echo '<div class="alert alert-success">Diária atualizada com sucesso.</div>';
            }
        } catch(PDOException $e){
            echo '<div class="alert alert-danger">Erro: '.esc($e->getMessage()).'</div>';
        }
        exit;
    }

    if($acao==='delete'){
        $id=intval($_POST['id']??0);
        if($id<=0){ echo '<div class="alert alert-danger">ID inválido.</div>'; exit; }
        try{
            $stmt=$db->prepare("DELETE FROM diarias WHERE id=:id");
            $stmt->execute([':id'=>$id]);
            echo '<div class="alert alert-success">Diária excluída.</div>';
        } catch(PDOException $e){
            echo '<div class="alert alert-danger">Erro ao excluir: '.esc($e->getMessage()).'</div>';
        }
        exit;
    }

    if($acao==='preview'){
        $numero = isset($_POST['numero']) && $_POST['numero'] !== '' ? intval($_POST['numero']) : null;
        if ($numero) {
            $st = $db->prepare("SELECT d.*, f.nome AS funcionario_nome, f.matricula AS funcionario_matricula
                                FROM diarias d LEFT JOIN funcionarios f ON f.id=d.funcionario_id
                                WHERE d.numero=:n LIMIT 1");
            $st->execute([':n'=>$numero]);
            $d = $st->fetch(PDO::FETCH_ASSOC);
            if($d){
                $servidor = $d['funcionario_nome'] ?? '';
                $destino = $d['destino'] ?? '';
                $objetivo = $d['objetivo'] ?? '';
                $num_text = $d['numero'];
            } else { echo '<div class="alert alert-warning">Diária não encontrada.</div>'; exit; }
        } else {
            $servidor = esc($_POST['servidor'] ?? '');
            $destino = esc($_POST['destino'] ?? '');
            $objetivo = esc($_POST['objetivo'] ?? '');
            $num_text = '____';
        }

        $data = date('d/m/Y');
        $html = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;">';
        $html .= '<div style="display:flex;justify-content:space-between;align-items:center;">';
        $html .= '<strong>OFÍCIO Nº '.esc($num_text).'/'.date('Y').'</strong>';
        $html .= '</div>';
        $html .= '<p>João Pessoa, '.$data.'.</p>';
        $html .= '<p>À Senhora<br>__________________________________</p>';
        $html .= '<p><strong>Assunto:</strong> Solicitação de Diária.</p>';
        $html .= '<p>Prezada Senhora,</p>';
        $html .= '<p>Solicitamos diária administrativa em favor do(a) servidor(a) <strong>'. esc($servidor) .'</strong>, com destino a <strong>'. esc($destino) .'</strong>, a fim de realizar:</p>';
        $html .= '<p>'. nl2br(esc($objetivo)) .'</p>';
        $html .= '<p>Sem mais para o momento.</p>';
        $html .= '<p>__________________________________<br>Assinatura</p>';
        $html .= '</div>';
        echo $html;
        exit;
    }

    echo '<div class="alert alert-warning">Ação inválida.</div>';
    exit;
}

try{ $funcionarios = $db->query("SELECT id,nome,matricula,cargo FROM funcionarios ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e){ $funcionarios = []; }
try{ $municipios = $db->query("SELECT municipio FROM municipios ORDER BY municipio")->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e){ $municipios = []; }

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Diárias - CRUD</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.container{max-width:1100px;margin:20px auto}
.modal-bg{ display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); align-items:center; justify-content:center; }
.modal-box{ background:#fff; padding:20px; width:800px; max-height:80%; overflow:auto; border-radius:6px; }
.close{ float:right; cursor:pointer; font-size:20px; }
</style>
</head>
<body class="bg-light">
<div class="container bg-white p-4 rounded shadow-sm">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <h3>Gerenciar Diárias</h3>
        <div><a href="dashboard.php" class="btn btn-sm btn-outline-secondary">← Dashboard</a></div>
    </div>
    <div id="mensagem"></div>

    <form id="formDiaria" class="row g-3 mb-3">
        <input type="hidden" id="diariaId" value="">
        <div class="col-md-4">
            <label class="form-label">Funcionário</label>
            <select id="funcionario_id" class="form-select" required>
                <option value="">-- selecione --</option>
                <?php foreach($funcionarios as $f): ?>
                    <option value="<?= esc($f['id']) ?>"><?= esc($f['nome']) ?> — <?= esc($f['matricula']) ?> (<?= esc($f['cargo']) ?>)</option>
                <?php endforeach; ?>
                <option value="__add_new__">+ Cadastrar novo funcionário</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Data Início</label>
            <input type="date" id="data_inicio" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Data Fim</label>
            <input type="date" id="data_fim" class="form-control">
        </div>
        <div class="col-md-2">
            <label class="form-label">Nº (opcional)</label>
            <input type="text" id="numero" class="form-control" placeholder="auto">
        </div>
        <div class="col-12">
            <label class="form-label">Destino</label>
            <select id="destino" class="form-select" required>
                <option value="">-- selecione município --</option>
                <?php foreach($municipios as $m): ?>
                    <option value="<?= esc($m['municipio']) ?>"><?= esc($m['municipio']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12">
            <label class="form-label">Objetivo</label>
            <textarea id="objetivo" class="form-control" rows="3" required></textarea>
        </div>
        <div class="col-md-2">
            <button type="button" id="btnSalvar" class="btn btn-success w-100">Salvar</button>
        </div>
        <div class="col-md-2">
            <button type="button" id="btnLimpar" class="btn btn-secondary w-100">Limpar</button>
        </div>
        <div class="col-md-2">
            <button type="button" id="btnPreview" class="btn btn-outline-primary w-100">Pré-visualizar</button>
        </div>
    </form>
    <div id="listaDiarias"></div>
</div>

<div class="modal-bg" id="modalPreview">
    <div class="modal-box">
        <span class="close" onclick="document.getElementById('modalPreview').style.display='none'">×</span>
        <div id="conteudoPreview" style="white-space:pre-wrap;"></div>
    </div>
</div>

<div class="modal-bg" id="modalCadastroFunc">
    <div class="modal-box">
        <span class="close" onclick="document.getElementById('modalCadastroFunc').style.display='none'">×</span>
        <h5>Cadastrar novo funcionário</h5>
        <div class="mb-2"><label class="form-label">Nome</label><input type="text" id="novo_nome" class="form-control"></div>
        <div class="mb-2"><label class="form-label">Matrícula</label><input type="text" id="novo_matricula" class="form-control"></div>
        <div class="mb-2"><label class="form-label">Cargo</label><input type="text" id="novo_cargo" class="form-control"></div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary" id="btnSalvarFunc">Salvar</button>
            <button class="btn btn-secondary" onclick="document.getElementById('modalCadastroFunc').style.display='none'">Cancelar</button>
        </div>
        <div id="mensagemFunc" class="mt-2"></div>
    </div>
</div>

<script>
function excluirDiaria(id){
    if(confirm('Deseja realmente excluir esta diária?')){
        const params = new URLSearchParams();
        params.append('acao','delete'); params.append('id',id);
        fetch('diarias.php',{method:'POST',body:params})
        .then(r=>r.text())
        .then(resp=>{
            document.getElementById('mensagem').innerHTML=resp;
            listarDiarias();
        });
    }
}

function editarDiariaFromB64(id,b64){
    try{
        const obj = JSON.parse(atob(b64));
        document.getElementById('diariaId').value=id;
        document.getElementById('funcionario_id').value=obj.funcionario_id||'';
        document.getElementById('data_inicio').value=obj.data_inicio||'';
        document.getElementById('data_fim').value=obj.data_fim||'';
        document.getElementById('destino').value=obj.destino||'';
        document.getElementById('objetivo').value=obj.objetivo||'';
        document.getElementById('numero').value=obj.numero||'';
        window.scrollTo({top:0,behavior:'smooth'});
    }catch(e){ console.error(e); document.getElementById('mensagem').innerHTML='<div class="alert alert-danger">Não foi possível carregar a diária.</div>'; }
}

function visualizarDiaria(numero){
    const params = new URLSearchParams();
    params.append('acao','preview'); params.append('numero',numero);
    fetch('diarias.php',{method:'POST',body:params})
    .then(r=>r.text())
    .then(html=>{
        document.getElementById('conteudoPreview').innerHTML=html;
        document.getElementById('modalPreview').style.display='flex';
    });
}

function listarDiarias(){
    const params = new URLSearchParams(); params.append('acao','table');
    fetch('diarias.php',{method:'POST',body:params})
    .then(r=>r.text())
    .then(html=>{ document.getElementById('listaDiarias').innerHTML=html; });
}

document.getElementById('btnSalvar').addEventListener('click',()=>{
    const params = new URLSearchParams();
    params.append('acao',document.getElementById('diariaId').value?'edit':'add');
    params.append('id',document.getElementById('diariaId').value);
    params.append('funcionario_id',document.getElementById('funcionario_id').value);
    params.append('data_inicio',document.getElementById('data_inicio').value);
    params.append('data_fim',document.getElementById('data_fim').value);
    params.append('destino',document.getElementById('destino').value);
    params.append('objetivo',document.getElementById('objetivo').value);
    params.append('numero',document.getElementById('numero').value);

    fetch('diarias.php',{method:'POST',body:params})
    .then(r=>r.text())
    .then(resp=>{
        document.getElementById('mensagem').innerHTML=resp;
        listarDiarias();
        document.getElementById('formDiaria').reset();
        document.getElementById('diariaId').value='';
    });
});

document.getElementById('btnLimpar').addEventListener('click',()=>{
    document.getElementById('formDiaria').reset();
    document.getElementById('diariaId').value='';
});

document.getElementById('btnPreview').addEventListener('click',()=>{
    const params = new URLSearchParams();
    params.append('acao','preview');
    params.append('servidor',document.getElementById('funcionario_id').selectedOptions[0].text);
    params.append('destino',document.getElementById('destino').value);
    params.append('objetivo',document.getElementById('objetivo').value);
    fetch('diarias.php',{method:'POST',body:params})
    .then(r=>r.text())
    .then(html=>{
        document.getElementById('conteudoPreview').innerHTML=html;
        document.getElementById('modalPreview').style.display='flex';
    });
});

document.getElementById('funcionario_id').addEventListener('change',()=>{
    if(document.getElementById('funcionario_id').value==='__add_new__'){
        document.getElementById('modalCadastroFunc').style.display='flex';
    }
});

document.getElementById('btnSalvarFunc').addEventListener('click',()=>{
    const nome=document.getElementById('novo_nome').value.trim();
    const matricula=document.getElementById('novo_matricula').value.trim();
    const cargo=document.getElementById('novo_cargo').value.trim();
    if(!nome){ document.getElementById('mensagemFunc').innerHTML='<div class="alert alert-danger">Nome obrigatório</div>'; return; }
    const params=new URLSearchParams();
    params.append('acao','add_funcionario');
    params.append('nome',nome);
    params.append('matricula',matricula);
    params.append('cargo',cargo);
    fetch('diarias.php',{method:'POST',body:params})
    .then(r=>r.text())
    .then(resp=>{
        if(resp.startsWith('ERROR')){ document.getElementById('mensagemFunc').innerHTML='<div class="alert alert-danger">'+resp+'</div>'; return; }
        const opt=document.createElement('option'); opt.value=resp; opt.text=nome+' — '+matricula+' ('+cargo+')';
        const sel=document.getElementById('funcionario_id'); sel.insertBefore(opt,sel.querySelector('option[value="__add_new__"]'));
        sel.value=resp;
        document.getElementById('modalCadastroFunc').style.display='none';
        document.getElementById('novo_nome').value=''; document.getElementById('novo_matricula').value=''; document.getElementById('novo_cargo').value='';
    });
});

listarDiarias();
</script>
</body>
</html>