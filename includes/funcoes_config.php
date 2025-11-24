<?php
require_once __DIR__ . 'includes../config/db.php'; // usa sua conexão PDO como no resto do sistema

function lerConfig() {
    global $db;

    $sql = "SELECT cabecalho, rodape FROM configuracoes WHERE id = 1 LIMIT 1";
    $stmt = $db->query($sql);

    if ($stmt && $stmt->rowCount() > 0) {
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Se não existir, cria padrão
    $db->query("INSERT INTO configuracoes (id, cabecalho, rodape) VALUES (1, '', '')");

    return ['cabecalho' => '', 'rodape' => ''];
}

function salvarCabecalhoBanco($conteudo) {
    global $db;

    $sql = "UPDATE configuracoes SET cabecalho = :c WHERE id = 1";
    $stmt = $db->prepare($sql);
    return $stmt->execute([':c' => $conteudo]);
}

function salvarRodapeBanco($conteudo) {
    global $db;

    $sql = "UPDATE configuracoes SET rodape = :r WHERE id = 1";
    $stmt = $db->prepare($sql);
    return $stmt->execute([':r' => $conteudo]);
}
