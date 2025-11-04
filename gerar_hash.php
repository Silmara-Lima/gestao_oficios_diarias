<?php
// 1. Defina a NOVA SENHA
$nova_senha_clara = 'admin'; 

// 2. Gere o hash
$novo_hash = password_hash($nova_senha_clara, PASSWORD_DEFAULT);

echo $novo_hash; // Copie APENAS esta saída.
?>