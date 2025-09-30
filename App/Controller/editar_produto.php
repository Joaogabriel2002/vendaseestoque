<?php
// Ficheiro: App/Controller/editar_produto.php

session_start();
// Protege o acesso ao script
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../public/login.php');
    exit();
}

require_once __DIR__ . '/../Config/conexao.php';
require_once __DIR__ . '/../Models/Produtos.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conexao = new Conexao();
    $pdo = $conexao->getConn();

    $idProduto = $_POST['id_produto'] ?? null;
    if (!$idProduto) {
        header("Location: ../../public/Produtos/ListarProdutos.php?status=erro&msg=" . urlencode("ID do produto não fornecido."));
        exit();
    }

    try {
        // 1. Busca os dados existentes do produto para ter as informações atuais (como nomes de imagens antigas)
        $produtoDadosAntigos = Produto::findById($pdo, $idProduto);
        if (!$produtoDadosAntigos) {
            throw new Exception("Produto não encontrado para atualização.");
        }
        
        // 2. Cria um objeto Produto para guardar os novos dados
        $produto = new Produto();
        $produto->setId($idProduto);
        $produto->setNome($_POST['nome'] ?? '');
        $produto->setDescricao($_POST['descricao'] ?? null);
        $produto->setIdCategoria(!empty($_POST['id_categoria']) ? $_POST['id_categoria'] : null);

        // 3. Processa as imagens (substitui apenas se um novo ficheiro for enviado)
        $pastaUpload = __DIR__ . '/../../public/uploads/produtos/';
        $nomesImagens = [
            'imagem1' => $produtoDadosAntigos['info']['imagem1'], 
            'imagem2' => $produtoDadosAntigos['info']['imagem2'], 
            'imagem3' => $produtoDadosAntigos['info']['imagem3']
        ];

        for ($i = 1; $i <= 3; $i++) {
            $campo = 'imagem' . $i;
            if (isset($_FILES[$campo]) && $_FILES[$campo]['error'] === UPLOAD_ERR_OK) {
                // Apaga a imagem antiga se existir
                if ($nomesImagens[$campo] && file_exists($pastaUpload . $nomesImagens[$campo])) {
                    unlink($pastaUpload . $nomesImagens[$campo]);
                }
                // Move a nova imagem
                $nomeArquivo = uniqid() . '-' . basename($_FILES[$campo]['name']);
                move_uploaded_file($_FILES[$campo]['tmp_name'], $pastaUpload . $nomeArquivo);
                $nomesImagens[$campo] = $nomeArquivo;
            }
        }
        $produto->setImagem1($nomesImagens['imagem1']);
        $produto->setImagem2($nomesImagens['imagem2']);
        $produto->setImagem3($nomesImagens['imagem3']);
        
        // 4. Organiza os dados das Variações que vieram do formulário
        $variacoesPost = $_POST['variacoes'] ?? [];
        $variacoesParaAtualizar = [];
        if (!empty($variacoesPost['tamanho'])) {
            foreach ($variacoesPost['tamanho'] as $index => $tamanho) {
                 if (!empty($tamanho) && !empty($variacoesPost['preco_venda'][$index]) && isset($variacoesPost['quantidade_estoque'][$index])) {
                    $variacoesParaAtualizar[] = [
                        'id' => $variacoesPost['id'][$index] ?? null, // ID da variação existente ou nulo se for nova
                        'tamanho' => $tamanho,
                        'preco_custo' => $variacoesPost['preco_custo'][$index],
                        'preco_venda' => $variacoesPost['preco_venda'][$index],
                        'quantidade_estoque' => $variacoesPost['quantidade_estoque'][$index]
                    ];
                }
            }
        }

        if (empty($variacoesParaAtualizar)) {
            throw new Exception("O produto deve ter pelo menos uma variação válida com tamanho, preço e estoque.");
        }
        
        // 5. Chama o método para atualizar o produto e sincronizar as variações
        $produto->atualizarComVariacoes($pdo, $variacoesParaAtualizar);

        $msg = "Produto '" . htmlspecialchars($produto->getNome()) . "' atualizado com sucesso!";
        header("Location: ../../public/Produtos/ListarProdutos.php?status=sucesso&msg=" . urlencode($msg));
        exit();

    } catch (Exception $e) {
        $msg = "Erro ao atualizar o produto: " . $e->getMessage();
        // Redireciona de volta para a página de edição com o erro
        header("Location: ../../public/Produtos/EditarProdutos.php?id={$idProduto}&status=erro&msg=" . urlencode($msg));
        exit();
    }
}

