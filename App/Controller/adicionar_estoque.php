<?php
// Ficheiro: App/Controller/adicionar_estoque.php
session_start();

// Protege o acesso ao script
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../public/login.php');
    exit();
}

require_once __DIR__ . '/../Config/conexao.php';
require_once __DIR__ . '/../Models/Produtos.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Obtém e valida os dados do formulário do modal
    $id_variacao = $_POST['id_variacao'] ?? null;
    $quantidade = $_POST['quantidade'] ?? null;
    $observacao = $_POST['observacao'] ?? 'Entrada manual de estoque';

    if (!$id_variacao || !$quantidade || !is_numeric($quantidade) || $quantidade <= 0) {
        $msg = "Dados inválidos. Por favor, selecione um tamanho e insira uma quantidade válida.";
        header("Location: ../../public/Produtos/ListarProdutos.php?status=erro&msg=" . urlencode($msg));
        exit();
    }

    try {
        // 2. Tenta adicionar o estoque chamando o método estático na classe Produto
        $conexao = new Conexao();
        $pdo = $conexao->getConn();
        Produto::adicionarEstoque($pdo, (int)$id_variacao, (int)$quantidade, $observacao);
        
        // 3. Redireciona com mensagem de sucesso
        $msg = "Estoque adicionado com sucesso!";
        header("Location: ../../public/Produtos/ListarProdutos.php?status=sucesso&msg=" . urlencode($msg));
        exit();
        
    } catch (Exception $e) {
        // 4. Em caso de erro, redireciona com a mensagem de erro
        $msg = "Erro ao adicionar estoque: " . $e->getMessage();
        header("Location: ../../public/Produtos/ListarProdutos.php?status=erro&msg=" . urlencode($msg));
        exit();
    }
} else {
    // Se o acesso não for via POST, apenas redireciona
    header("Location: ../../public/Produtos/ListarProdutos.php");
    exit();
}

