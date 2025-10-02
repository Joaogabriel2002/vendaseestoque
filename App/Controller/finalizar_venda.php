<?php
// Ficheiro: App/Controller/finalizar_venda.php

// Estas linhas são importantes para garantir que apenas a nossa resposta JSON seja enviada
error_reporting(0);
ini_set('display_errors', 0);

session_start();
header('Content-Type: application/json');

// Ficheiros necessários
require_once __DIR__ . '/../Config/conexao.php';
require_once __DIR__ . '/../Models/Venda.php';

// Obtém os dados que o JavaScript enviou (em formato JSON)
$dados = json_decode(file_get_contents('php://input'), true);

// Extrai os dados
$carrinho = $dados['carrinho'] ?? [];
$totalVenda = $dados['total'] ?? 0;
$numeroDocumento = $dados['numero_documento'] ?? null;
$formaPagamento = $dados['forma_pagamento'] ?? 'Não informado';

// Validação inicial
if (empty($carrinho) || $totalVenda <= 0) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'O carrinho está vazio ou o total é inválido.']);
    exit();
}

try {
    // Inicia a conexão com a base de dados
    $conexao = new Conexao();
    $pdo = $conexao->getConn();

    // Obtém o ID do utilizador da sessão, ou 1 como fallback para desenvolvimento
    $id_usuario = $_SESSION['usuario_id'] ?? 1;
    if (!$id_usuario) {
        throw new Exception("Sessão de utilizador inválida. Não foi possível registar a venda.");
    }

    // Cria um novo objeto de Venda e preenche os seus dados
    $venda = new Venda();
    $venda->setIdUsuario($id_usuario);
    $venda->setValorTotal($totalVenda);
    $venda->setNumeroDocumento($numeroDocumento);
    $venda->setFormaPagamento($formaPagamento);

    // O método 'criar' fará todo o trabalho pesado
    $venda->criar($pdo, $carrinho);

    // Se tudo correu bem, envia uma resposta de sucesso
    echo json_encode(['sucesso' => true, 'mensagem' => 'Venda finalizada com sucesso!']);

} catch (Exception $e) {
    // Se ocorrer um erro, regista o erro para depuração
    error_log("Erro em finalizar_venda.php: " . $e->getMessage());
    // E envia uma mensagem de erro clara para o utilizador
    echo json_encode(['sucesso' => false, 'mensagem' => $e->getMessage()]);
}

