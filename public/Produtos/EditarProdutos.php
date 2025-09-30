<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit();
}

require_once __DIR__ . '/../../App/Config/Conexao.php';
require_once __DIR__ . '/../../App/Models/Produtos.php';
require_once __DIR__ . '/../../App/Models/Categoria.php';

$conexao = new Conexao();
$pdo = $conexao->getConn();

// 1. Valida o ID do produto vindo da URL
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header("Location: ListarProdutos.php?status=erro&msg=" . urlencode("ID de produto inválido!"));
    exit();
}

// 2. Busca o produto e as suas variações
$produtoDados = Produto::findById($pdo, $id);
if (!$produtoDados) {
    header("Location: ListarProdutos.php?status=erro&msg=" . urlencode("Produto não encontrado!"));
    exit();
}

// 3. Busca todas as categorias para o dropdown
$categorias = Categoria::listarTodas($pdo);
$caminhoBaseImagem = '../uploads/produtos/';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Produto</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center font-sans py-12">

    <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-4xl">
        <h2 class="text-3xl font-bold mb-8 text-center text-gray-800">Editar Produto</h2>
        
        <form action="..\..\App\Controller\editar_produto.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id_produto" value="<?php echo $produtoDados['info']['id']; ?>">
            
            <!-- Secção: Informações Gerais -->
            <div class="mb-8 border-b pb-6">
                <h3 class="text-xl font-semibold text-gray-700 mb-4">1. Informações da Peça</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="nome" class="block text-gray-600 font-medium mb-2">Nome do Produto</label>
                        <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($produtoDados['info']['nome']); ?>" class="w-full px-4 py-2 border rounded-lg" required>
                    </div>
                    <div>
                        <label for="id_categoria" class="block text-gray-600 font-medium mb-2">Categoria</label>
                        <select id="id_categoria" name="id_categoria" class="w-full px-4 py-2 border rounded-lg bg-white">
                            <option value="">Selecione</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo $categoria['id']; ?>" <?php echo ($produtoDados['info']['id_categoria'] == $categoria['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($categoria['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label for="descricao" class="block text-gray-600 font-medium mb-2">Descrição</label>
                        <textarea id="descricao" name="descricao" rows="3" class="w-full px-4 py-2 border rounded-lg"><?php echo htmlspecialchars($produtoDados['info']['descricao']); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Secção: Imagens -->
            <div class="mb-8 border-b pb-6">
                 <h3 class="text-xl font-semibold text-gray-700 mb-4">2. Imagens</h3>
                 <p class="text-sm text-gray-500 mb-4">Envie um novo ficheiro apenas para substituir a imagem atual.</p>
                 <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php for ($i = 1; $i <= 3; $i++): $imagem = $produtoDados['info']['imagem' . $i]; ?>
                    <div>
                        <label for="imagem<?php echo $i; ?>" class="block text-gray-600 font-medium mb-2">Imagem <?php echo $i; ?></label>
                        <?php if ($imagem): ?>
                            <img src="<?php echo $caminhoBaseImagem . htmlspecialchars($imagem); ?>" class="w-full h-24 object-cover rounded-md mb-2 border">
                        <?php endif; ?>
                        <input type="file" name="imagem<?php echo $i; ?>" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Secção: Variações -->
            <div class="mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-semibold text-gray-700">3. Variações (Tamanhos, Preços e Estoque)</h3>
                    <button type="button" id="addVariacaoBtn" class="bg-green-500 text-white font-bold py-2 px-4 rounded-lg hover:bg-green-600"><i class="fas fa-plus mr-2"></i> Adicionar Tamanho</button>
                </div>
                <div id="variacoesContainer" class="space-y-4">
                    <?php if (empty($produtoDados['variacoes'])): ?>
                        <!-- Se não houver variações, mostra uma linha vazia -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center bg-gray-50 p-3 rounded-lg variacao-item">
                            <input type="hidden" name="variacoes[id][]" value="">
                            <input type="text" name="variacoes[tamanho][]" class="px-3 py-2 border rounded-lg" placeholder="Tamanho *" required>
                            <input type="number" step="0.01" name="variacoes[preco_custo][]" class="px-3 py-2 border rounded-lg" placeholder="Preço Custo">
                            <input type="number" step="0.01" name="variacoes[preco_venda][]" class="px-3 py-2 border rounded-lg" placeholder="Preço Venda *" required>
                            <div class="flex items-center gap-2">
                                <input type="number" name="variacoes[quantidade_estoque][]" class="w-full px-3 py-2 border rounded-lg" placeholder="Estoque *" required>
                                <button type="button" class="text-red-500 hover:text-red-700 removeVariacaoBtn" title="Remover"><i class="fas fa-trash-alt fa-lg"></i></button>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($produtoDados['variacoes'] as $var): ?>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center bg-gray-50 p-3 rounded-lg variacao-item">
                            <input type="hidden" name="variacoes[id][]" value="<?php echo $var['id']; ?>">
                            <input type="text" name="variacoes[tamanho][]" value="<?php echo htmlspecialchars($var['tamanho']); ?>" class="px-3 py-2 border rounded-lg" placeholder="Tamanho *" required>
                            <input type="number" step="0.01" name="variacoes[preco_custo][]" value="<?php echo htmlspecialchars($var['preco_custo']); ?>" class="px-3 py-2 border rounded-lg" placeholder="Preço Custo">
                            <input type="number" step="0.01" name="variacoes[preco_venda][]" value="<?php echo htmlspecialchars($var['preco_venda']); ?>" class="px-3 py-2 border rounded-lg" placeholder="Preço Venda *" required>
                            <div class="flex items-center gap-2">
                                <input type="number" name="variacoes[quantidade_estoque][]" value="<?php echo htmlspecialchars($var['quantidade_estoque']); ?>" class="w-full px-3 py-2 border rounded-lg" placeholder="Estoque *" required>
                                <button type="button" class="text-red-500 hover:text-red-700 removeVariacaoBtn" title="Remover"><i class="fas fa-trash-alt fa-lg"></i></button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex justify-center gap-4 mt-8">
                <a href="ListarProdutos.php" class="w-full md:w-1/3 bg-gray-400 text-white font-bold py-3 px-6 rounded-lg text-center">Cancelar</a>
                <button type="submit" class="w-full md:w-1/3 bg-blue-600 text-white font-bold py-3 px-6 rounded-lg">Salvar Alterações</button>
            </div>
        </form>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('variacoesContainer');
        const addButton = document.getElementById('addVariacaoBtn');

        // Template para uma nova linha de variação
        const template = `
            <input type="hidden" name="variacoes[id][]" value="">
            <input type="text" name="variacoes[tamanho][]" class="px-3 py-2 border rounded-lg" placeholder="Tamanho *" required>
            <input type="number" step="0.01" name="variacoes[preco_custo][]" class="px-3 py-2 border rounded-lg" placeholder="Preço Custo">
            <input type="number" step="0.01" name="variacoes[preco_venda][]" class="px-3 py-2 border rounded-lg" placeholder="Preço Venda *" required>
            <div class="flex items-center gap-2">
                <input type="number" name="variacoes[quantidade_estoque][]" class="w-full px-3 py-2 border rounded-lg" placeholder="Estoque *" required>
                <button type="button" class="text-red-500 hover:text-red-700 removeVariacaoBtn" title="Remover"><i class="fas fa-trash-alt fa-lg"></i></button>
            </div>
        `;

        addButton.addEventListener('click', function() {
            const newItem = document.createElement('div');
            newItem.className = 'grid grid-cols-1 md:grid-cols-4 gap-4 items-center bg-gray-50 p-3 rounded-lg variacao-item';
            newItem.innerHTML = template;
            container.appendChild(newItem);
            updateRemoveButtons();
        });

        container.addEventListener('click', function(e) {
            const removeButton = e.target.closest('.removeVariacaoBtn');
            if (removeButton) {
                const itemToRemove = removeButton.closest('.variacao-item');
                if (container.children.length > 1) {
                    itemToRemove.remove();
                } else {
                    alert('É necessário ter pelo menos uma variação.');
                }
                updateRemoveButtons();
            }
        });

        function updateRemoveButtons() {
            const items = container.querySelectorAll('.variacao-item');
            items.forEach(item => {
                const removeBtn = item.querySelector('.removeVariacaoBtn');
                removeBtn.style.display = items.length > 1 ? 'block' : 'none';
            });
        }
        updateRemoveButtons();
    });
    </script>
</body>
</html>

