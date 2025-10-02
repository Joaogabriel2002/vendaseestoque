<?php
// Ficheiro: App/Ajax/buscar_detalhes_venda.php

// Define o tipo de conteúdo da resposta como JSON
header('Content-Type: application/json');

// Inclui os ficheiros essenciais
require_once __DIR__ . '/../Config/Conexao.php';
require_once __DIR__ . '/../Models/Venda.php';

// 1. Obtém o ID do pedido a partir da URL (ex: ?id=123)
$idVenda = $_GET['id'] ?? null;

// 2. Valida o ID recebido
if (!$idVenda || !is_numeric($idVenda)) {
    http_response_code(400); // Resposta de "Bad Request"
    echo json_encode(['erro' => 'ID do pedido inválido ou não fornecido.']);
    exit();
}

try {
    // 3. Conecta-se à base de dados
    $conexao = new Conexao();
    $pdo = $conexao->getConn();
    
    // 4. Chama o método estático da classe Venda para buscar os itens
    $itens = Venda::buscarItensPorVendaId($pdo, (int)$idVenda);
    
    // 5. Devolve os itens encontrados em formato JSON
    echo json_encode($itens);

} catch (Exception $e) {
    // 6. Em caso de erro, devolve uma resposta de erro do servidor
    http_response_code(500); // Resposta de "Internal Server Error"
    echo json_encode(['erro' => 'Erro interno ao buscar os detalhes do pedido.']);
    // Regista o erro real no log do servidor para fins de depuração
    error_log("Erro em buscar_detalhes_venda.php: " . $e->getMessage());
}

