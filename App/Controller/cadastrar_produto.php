<?php
// Ficheiro: App/Controller/cadastrar_produto.php

session_start();

// Protege o acesso ao script
if (!isset($_SESSION['usuario_id'])) {
    // Se não estiver logado, não pode cadastrar
    header('Location: ../../public/login.php');
    exit();
}

require_once __DIR__ . '/../Config/conexao.php';
require_once __DIR__ . '/../Models/Produtos.php';

// Define a pasta de destino para os uploads
$pastaUpload = __DIR__ . '/../../public/uploads/produtos/';
if (!file_exists($pastaUpload)) {
    // Cria a pasta se ela não existir
    mkdir($pastaUpload, 0777, true);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conexao = new Conexao();
    $pdo = $conexao->getConn();

    // 1. Processa as informações do "Produto Pai"
    $produto = new Produto();
    $produto->setNome($_POST['nome'] ?? '');
    $produto->setDescricao($_POST['descricao'] ?? null);
    $produto->setIdCategoria(!empty($_POST['id_categoria']) ? $_POST['id_categoria'] : null);

    // 2. Processa as Imagens
    $nomesImagens = [];
    for ($i = 1; $i <= 3; $i++) {
        $campoImagem = 'imagem' . $i;
        if (isset($_FILES[$campoImagem]) && $_FILES[$campoImagem]['error'] === UPLOAD_ERR_OK) {
            $nomeArquivo = uniqid() . '-' . basename($_FILES[$campoImagem]['name']);
            move_uploaded_file($_FILES[$campoImagem]['tmp_name'], $pastaUpload . $nomeArquivo);
            $nomesImagens[$i] = $nomeArquivo;
        } else {
            $nomesImagens[$i] = null;
        }
    }
    $produto->setImagem1($nomesImagens[1]);
    $produto->setImagem2($nomesImagens[2]);
    $produto->setImagem3($nomesImagens[3]);

    // 3. Organiza os dados das Variações de Tamanho
    $variacoesPost = $_POST['variacoes'] ?? [];
    $variacoesParaSalvar = [];
    if (!empty($variacoesPost['tamanho'])) {
        foreach ($variacoesPost['tamanho'] as $index => $tamanho) {
            // Só adiciona a variação se os campos obrigatórios (tamanho, preço, estoque) não estiverem vazios
            if (!empty($tamanho) && !empty($variacoesPost['preco_venda'][$index]) && isset($variacoesPost['quantidade_estoque'][$index])) {
                $variacoesParaSalvar[] = [
                    'tamanho' => $tamanho,
                    'preco_custo' => $variacoesPost['preco_custo'][$index],
                    'preco_venda' => $variacoesPost['preco_venda'][$index],
                    'quantidade_estoque' => $variacoesPost['quantidade_estoque'][$index]
                ];
            }
        }
    }

    // 4. Validação
    if (empty($produto->getNome()) || empty($variacoesParaSalvar)) {
        $msg = "Erro: O nome do produto é obrigatório e deve haver pelo menos uma variação com tamanho, preço e estoque preenchidos.";
        header("Location: ../../public/Produtos/CadastrarProdutos.php?status=erro&msg=" . urlencode($msg));
        exit();
    }

    // 5. Salva no banco de dados
    try {
        $produto->salvarComVariacoes($pdo, $variacoesParaSalvar);
        $msg = "Produto '" . htmlspecialchars($produto->getNome()) . "' e suas variações foram cadastrados com sucesso!";
        header("Location: ../../public/Produtos/ListarProdutos.php?status=sucesso&msg=" . urlencode($msg));
        exit();
    } catch (Exception $e) {
        $msg = "Erro ao cadastrar o produto: " . $e->getMessage();
        header("Location: ../../public/Produtos/CadastrarProdutos.php?status=erro&msg=" . urlencode($msg));
        exit();
    }

} else {
    // Redireciona se o acesso não for via POST
    header("Location: ../../public/Produtos/CadastrarProdutos.php");
    exit();
}

