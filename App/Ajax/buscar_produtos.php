<?php
// Ficheiro: App/Ajax/buscar_produtos.php
header('Content-Type: application/json');

require_once __DIR__ . '/../Config/Conexao.php';
require_once __DIR__ . '/../Models/Produtos.php';

$termo = $_GET['term'] ?? '';

// Evita buscas desnecessárias com termos muito curtos
if (strlen($termo) < 2) {
    echo json_encode([]);
    exit();
}

try {
    $conexao = new Conexao();
    $pdo = $conexao->getConn();
    
    // Chama o método estático que busca os produtos e as suas variações
    $produtos = Produto::buscarPorTermo($pdo, $termo);
    
    echo json_encode($produtos);

} catch (Exception $e) {
    http_response_code(500); // Erro Interno do Servidor
    echo json_encode(['erro' => 'Erro ao buscar produtos.']);
    error_log($e->getMessage()); // Regista o erro para depuração
}

