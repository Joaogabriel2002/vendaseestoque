<?php
session_start();

// Protege a página
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit();
}

// 1. Incluímos os ficheiros essenciais
require_once __DIR__ . '/../../App/Config/Conexao.php';
require_once __DIR__ . '/../../App/Models/Categoria.php';

// 2. Buscamos as categorias para popular o <select>
$conexao = new Conexao();
$pdo = $conexao->getConn();
$categorias = Categoria::listarTodas($pdo);

// 3. Lógica para exibir mensagens de feedback
$mensagem = '';
$classeAlerta = '';
if (isset($_GET['status']) && isset($_GET['msg'])) {
    $status = $_GET['status'];
    $msg = urldecode($_GET['msg']);
    if ($status === 'sucesso') {
        $mensagem = $msg;
        $classeAlerta = 'p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg';
    } elseif ($status === 'erro') {
        $mensagem = $msg;
        $classeAlerta = 'p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Produtos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center font-sans py-12">

    <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-4xl">
        <h2 class="text-3xl font-bold mb-8 text-center text-gray-800">Cadastrar Novo Produto</h2>
        
        <?php if (!empty($mensagem)): ?>
        <div class="<?php echo $classeAlerta; ?>" role="alert"><?php echo htmlspecialchars($mensagem); ?></div>
        <?php endif; ?>

        <form action="..\..\App\Controller\cadastrar_produto.php" method="POST" enctype="multipart/form-data">
            
            <!-- Secção: Informações Gerais do Produto -->
            <div class="mb-8 border-b pb-6">
                <h3 class="text-xl font-semibold text-gray-700 mb-4">1. Informações da Peça</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="nome" class="block text-gray-600 font-medium mb-2">Nome do Produto</label>
                        <input type="text" id="nome" name="nome" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ex: Camiseta Gola V Lisa" required>
                    </div>
                    <div>
                        <label for="id_categoria" class="block text-gray-600 font-medium mb-2">Categoria</label>
                        <select id="id_categoria" name="id_categoria" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                            <option value="">Selecione uma categoria</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo htmlspecialchars($categoria['id']); ?>">
                                    <?php echo htmlspecialchars($categoria['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label for="descricao" class="block text-gray-600 font-medium mb-2">Descrição</label>
                        <textarea id="descricao" name="descricao" rows="3" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Detalhes do produto..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Secção: Imagens do Produto -->
            <div class="mb-8 border-b pb-6">
                 <h3 class="text-xl font-semibold text-gray-700 mb-4">2. Imagens</h3>
                 <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="imagem1" class="block text-gray-600 font-medium mb-2">Imagem Principal</label>
                        <input type="file" id="imagem1" name="imagem1" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                    <div>
                        <label for="imagem2" class="block text-gray-600 font-medium mb-2">Imagem 2</label>
                        <input type="file" id="imagem2" name="imagem2" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                    <div>
                        <label for="imagem3" class="block text-gray-600 font-medium mb-2">Imagem 3</label>
                        <input type="file" id="imagem3" name="imagem3" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                </div>
            </div>

            <!-- Secção: Variações de Tamanho -->
            <div class="mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-semibold text-gray-700">3. Variações (Tamanhos, Preços e Estoque)</h3>
                    <button type="button" id="addVariacaoBtn" class="bg-green-500 text-white font-bold py-2 px-4 rounded-lg hover:bg-green-600 transition duration-300 flex items-center">
                        <i class="fas fa-plus mr-2"></i> Adicionar Tamanho
                    </button>
                </div>
                <div id="variacoesContainer" class="space-y-4">
                    <!-- Linha de Variação (template) -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center bg-gray-50 p-3 rounded-lg variacao-item">
                        <input type="text" name="variacoes[tamanho][]" class="px-3 py-2 border rounded-lg focus:outline-none" placeholder="Tamanho (Ex: P, M, 42)" required>
                        <input type="number" step="0.01" name="variacoes[preco_custo][]" class="px-3 py-2 border rounded-lg focus:outline-none" placeholder="Preço Custo">
                        <input type="number" step="0.01" name="variacoes[preco_venda][]" class="px-3 py-2 border rounded-lg focus:outline-none" placeholder="Preço Venda *" required>
                        <div class="flex items-center gap-2">
                            <input type="number" name="variacoes[quantidade_estoque][]" class="w-full px-3 py-2 border rounded-lg focus:outline-none" placeholder="Estoque *" required>
                            <button type="button" class="text-red-500 hover:text-red-700 removeVariacaoBtn" title="Remover Variação">
                                <i class="fas fa-trash-alt fa-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-center gap-4 mt-8">
                <a href="ListarProdutos.php" class="w-full md:w-1/3 bg-gray-400 text-white font-bold py-3 px-6 rounded-lg hover:bg-gray-500 transition duration-300 text-center">
                    Cancelar
                </a>
                <button type="submit" class="w-full md:w-1/3 bg-blue-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-blue-700 transition duration-300">
                    Salvar Produto
                </button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('variacoesContainer');
            const addButton = document.getElementById('addVariacaoBtn');

            function addRow() {
                const template = container.querySelector('.variacao-item');
                const newItem = template.cloneNode(true);
                newItem.querySelectorAll('input').forEach(input => input.value = '');
                container.appendChild(newItem);
                updateRemoveButtons();
            }

            addButton.addEventListener('click', addRow);

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
                    removeBtn.style.display = items.length > 1 ? 'inline-block' : 'none';
                });
            }
            updateRemoveButtons();
        });
    </script>

</body>
</html>

