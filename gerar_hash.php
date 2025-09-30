<?php
// Ficheiro: gerar_hash.php
// Este script ajuda-o a criar uma senha encriptada (hash) para guardar na sua base de dados.

// --- INSTRUÇÕES ---
// 1. Altere a senha na variável $senhaParaEncriptar abaixo.
// 2. Guarde o ficheiro.
// 3. Aceda a este ficheiro no seu navegador (ex: http://localhost/loja/gerar_hash.php).
// 4. Copie o hash gerado que aparece no ecrã.
// 5. Cole esse hash na coluna 'senha' da sua tabela 'usuarios' no phpMyAdmin.

$senhaParaEncriptar = '123'; // <-- Altere para a senha que deseja usar

// Gera o hash da senha usando o algoritmo BCRYPT, que é o padrão e muito seguro.
$hash = password_hash($senhaParaEncriptar, PASSWORD_DEFAULT);

// Exibe o hash gerado no ecrã.
echo "<h2>Hash Gerado:</h2>";
echo "<p>Copie a linha abaixo e cole na coluna 'senha' da sua tabela 'usuarios':</p>";
echo "<textarea rows='3' style='width: 100%; font-family: monospace; font-size: 16px;'>" . htmlspecialchars($hash) . "</textarea>";

?>
