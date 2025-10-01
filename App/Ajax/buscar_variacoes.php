<?php
// Ficheiro: App/Ajax/buscar_variacoes.php
header('Content-Type: application/json');
require_once __DIR__ . '/../Config/Conexao.php';
require_once __DIR__ . '/../Models/Produtos.php';

$idProduto = $_GET['id'] ?? null;
if (!$idProduto) {
    http_response_code(400);
    echo json_encode(['erro' => 'ID do produto não fornecido.']);
    exit();
}

try {
    $conexao = new Conexao();
    $pdo = $conexao->getConn();
    $variacoes = Produto::listarVariacoesPorProdutoId($pdo, (int)$idProduto);
    echo json_encode($variacoes);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao buscar variações.']);
}
