<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit();
}

require_once __DIR__ . '/../../App/Config/conexao.php';
require_once __DIR__ . '/../../App/Models/Produtos.php';

$conexao = new Conexao();
$pdo = $conexao->getConn();

$filtro_estoque = $_GET['filtro_estoque'] ?? 'todos';
$produtos = [];

try {
    $todosOsProdutos = Produto::listarTodos($pdo);
    if ($filtro_estoque === 'zerado') {
        $produtos = array_filter($todosOsProdutos, fn($p) => !isset($p['estoque_total']) || $p['estoque_total'] <= 0);
    } elseif ($filtro_estoque === 'em_estoque') {
        $produtos = array_filter($todosOsProdutos, fn($p) => isset($p['estoque_total']) && $p['estoque_total'] > 0);
    } else {
        $produtos = $todosOsProdutos;
    }
} catch (Exception $e) {
    $erro = "Não foi possível carregar os produtos.";
    error_log($e->getMessage());
}

$mensagem = '';
$classeAlerta = '';
if (isset($_GET['status']) && isset($_GET['msg'])) {
    $status = $_GET['status'];
    $msg = urldecode($_GET['msg']);
    $classeAlerta = $status === 'sucesso' ? 'p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg' : 'p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg';
    $mensagem = $msg;
}

$caminhoBaseImagem = '../uploads/produtos/';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estoque de Produtos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 font-sans">

    <nav class="bg-white shadow-md">
        <div class="container mx-auto px-6 py-3 flex justify-between items-center">
            <div class="text-xl font-bold text-gray-800">Meu Estoque</div>
            <div><a href="../dashboard.php" class="text-gray-600 hover:text-blue-600 px-3 py-2 rounded">Dashboard</a></div>
        </div>
    </nav>

    <div class="container mx-auto p-4 md:p-8">
        <div class="mb-6"><a href="../dashboard.php" class="inline-flex items-center text-gray-600 hover:text-gray-900 font-semibold"><i class="fas fa-arrow-left mr-2"></i>Voltar</a></div>
        <header class="flex flex-col md:flex-row justify-between items-center mb-4">
            <h1 class="text-3xl font-bold text-gray-800 mb-4 md:mb-0">Produtos Cadastrados</h1>
            <div class="flex gap-4 w-full md:w-auto">
                <div class="relative flex-grow"><input type="text" id="searchInput" placeholder="Buscar produtos..." class="w-full pl-10 pr-4 py-2 border rounded-lg"><div class="absolute top-0 left-0 p-2 mt-1 ml-1 text-gray-400"><i class="fas fa-search"></i></div></div>
                <a href="CadastrarProdutos.php" class="bg-blue-600 text-white font-bold py-2 px-6 rounded-lg hover:bg-blue-700 flex items-center shrink-0"><i class="fas fa-plus mr-2"></i> Novo</a>
            </div>
        </header>
        <div class="mb-8 flex flex-wrap gap-2">
            <a href="?filtro_estoque=todos" class="px-4 py-2 text-sm font-medium rounded-lg <?php echo ($filtro_estoque === 'todos' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border'); ?>">Todos</a>
            <a href="?filtro_estoque=em_estoque" class="px-4 py-2 text-sm font-medium rounded-lg <?php echo ($filtro_estoque === 'em_estoque' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border'); ?>">Em Estoque</a>
            <a href="?filtro_estoque=zerado" class="px-4 py-2 text-sm font-medium rounded-lg <?php echo ($filtro_estoque === 'zerado' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border'); ?>">Estoque Zerado</a>
        </div>
        <?php if (!empty($mensagem)): ?><div class="<?php echo $classeAlerta; ?>"><?php echo htmlspecialchars($mensagem); ?></div><?php endif; ?>
        <div id="productGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php if (empty($produtos)): ?>
                <div class="col-span-full text-center py-16 bg-white rounded-lg shadow-md border"><i class="fas fa-box-open fa-3x text-gray-400 mb-4"></i><h3 class="text-xl font-semibold text-gray-700">Nenhum produto encontrado</h3></div>
            <?php else: ?>
                <?php foreach ($produtos as $produto): ?>
                    <div class="product-card bg-white rounded-lg shadow-md flex flex-col">
                        <div class="h-48 bg-gray-200 flex items-center justify-center relative">
                            <img src="<?php echo $caminhoBaseImagem . ($produto['imagem1'] ?? 'placeholder.png'); ?>" class="w-full h-full object-cover">
                            <?php $estoqueTotal = $produto['estoque_total'] ?? 0; $corEstoque = $estoqueTotal > 0 ? 'bg-green-500' : 'bg-red-500'; ?>
                            <span class="absolute top-2 right-2 text-xs text-white <?php echo $corEstoque; ?> px-2 py-1 rounded-full">Estoque: <?php echo $estoqueTotal; ?></span>
                        </div>
                        <div class="p-4 flex-grow flex flex-col justify-between">
                            <div>
                                <h3 class="text-lg font-semibold truncate"><?php echo htmlspecialchars($produto['nome']); ?></h3>
                                <p class="text-2xl font-bold mt-2">
                                     <?php if (isset($produto['preco_minimo'])): ?>
                                        <span class="text-sm font-normal">A partir de</span> R$ <?php echo number_format($produto['preco_minimo'], 2, ',', '.'); ?>
                                    <?php else: ?>
                                        <span class="text-lg font-normal">Sem variações</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="mt-4 flex justify-end gap-2">
                                <button type="button" class="open-modal-estoque text-gray-500 hover:text-green-600 p-2 rounded-full" title="Adicionar Estoque" data-id="<?php echo $produto['id']; ?>" data-nome="<?php echo htmlspecialchars($produto['nome']); ?>"><i class="fas fa-plus-circle fa-fw"></i></button>
                                <a href="EditarProdutos.php?id=<?php echo $produto['id']; ?>" class="text-gray-500 hover:text-blue-600 p-2 rounded-full" title="Editar"><i class="fas fa-pencil-alt fa-fw"></i></a>
                                <a href="../../App/Controller/excluir_produto.php?id=<?php echo $produto['id']; ?>" class="text-gray-500 hover:text-red-600 p-2 rounded-full" title="Excluir" onclick="return confirm('Tem a certeza?');"><i class="fas fa-trash-alt fa-fw"></i></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div id="noResultsMessage" class="hidden"></div>
    </div>

    <!-- Modal Adicionar Estoque -->
    <div id="modalEstoque" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
            <div class="flex justify-between items-center mb-4 border-b pb-3">
                <h3 class="text-xl font-semibold" id="modalEstoqueTitle"></h3>
                <button id="closeModalEstoque" class="text-2xl">&times;</button>
            </div>
            <div id="step1-variacao"><p class="mb-4">Selecione o tamanho:</p><div id="listaVariacoes" class="space-y-2 max-h-60 overflow-y-auto"></div></div>
            <form id="formAdicionarEstoque" action="../../App/Controller/adicionar_estoque.php" method="POST" class="hidden">
                <input type="hidden" name="id_variacao" id="id_variacao_estoque">
                <p class="mb-4">Adicionando a: <strong id="variacaoSelecionadaNome"></strong></p>
                <div class="mb-4"><label class="block text-sm font-bold mb-2">Quantidade:</label><input type="number" name="quantidade" class="shadow border rounded w-full py-2 px-3" min="1" required></div>
                <div class="mb-6"><label class="block text-sm font-bold mb-2">Motivo:</label><input type="text" name="observacao" value="Entrada manual" class="shadow border rounded w-full py-2 px-3" required></div>
                <div class="flex justify-end gap-4">
                    <button type="button" id="voltarParaVariacoes" class="bg-gray-500 text-white font-bold py-2 px-4 rounded">Voltar</button>
                    <button type="submit" class="bg-blue-600 text-white font-bold py-2 px-4 rounded">Salvar</button>
                </div>
            </form>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const productGrid = document.getElementById('productGrid');
    const productCards = productGrid.querySelectorAll('.product-card');
    const noResultsMessage = document.getElementById('noResultsMessage');
    const modalEstoque = document.getElementById('modalEstoque');
    const openModalButtons = document.querySelectorAll('.open-modal-estoque');
    const closeModalButton = document.getElementById('closeModalEstoque');
    const modalTitle = document.getElementById('modalEstoqueTitle');
    const step1 = document.getElementById('step1-variacao');
    const step2 = document.getElementById('formAdicionarEstoque');
    const listaVariacoesContainer = document.getElementById('listaVariacoes');
    const idVariacaoInput = document.getElementById('id_variacao_estoque');
    const variacaoNomeSpan = document.getElementById('variacaoSelecionadaNome');
    const voltarBtn = document.getElementById('voltarParaVariacoes');

    function showStep1() { step1.classList.remove('hidden'); step2.classList.add('hidden'); }
    function showStep2(varId, varNome) {
        idVariacaoInput.value = varId;
        variacaoNomeSpan.textContent = varNome;
        step1.classList.add('hidden');
        step2.classList.remove('hidden');
    }

    async function openModal(produtoId, produtoNome) {
        modalTitle.textContent = `Adicionar Estoque para: ${produtoNome}`;
        showStep1();
        listaVariacoesContainer.innerHTML = '<p>A carregar...</p>';
        modalEstoque.classList.remove('hidden');

        try {
            const response = await fetch(`../../App/Ajax/buscar_variacoes.php?id=${produtoId}`);
            if (!response.ok) throw new Error('Falha ao buscar variações.');
            const variacoes = await response.json();
            listaVariacoesContainer.innerHTML = '';

            if (variacoes.length === 0) {
                listaVariacoesContainer.innerHTML = '<p>Produto sem variações.</p>';
                return;
            }
            variacoes.forEach(v => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'w-full text-left p-3 rounded-lg hover:bg-gray-100 flex justify-between';
                btn.innerHTML = `<span>Tamanho: <strong class="text-blue-600">${v.tamanho}</strong></span><span class="text-sm text-gray-500">Estoque: ${v.quantidade_estoque}</span>`;
                btn.addEventListener('click', () => showStep2(v.id, `Tamanho ${v.tamanho}`));
                listaVariacoesContainer.appendChild(btn);
            });
        } catch (error) {
            listaVariacoesContainer.innerHTML = `<p class="text-red-500">${error.message}</p>`;
        }
    }
    function closeModal() { modalEstoque.classList.add('hidden'); }

    openModalButtons.forEach(btn => btn.addEventListener('click', function() { openModal(this.dataset.id, this.dataset.nome); }));
    closeModalButton.addEventListener('click', closeModal);
    voltarBtn.addEventListener('click', showStep1);
    modalEstoque.addEventListener('click', e => { if (e.target === modalEstoque) closeModal(); });

    searchInput.addEventListener('keyup', () => {
        const term = searchInput.value.toLowerCase();
        let visible = 0;
        productCards.forEach(card => {
            const name = card.querySelector('h3').textContent.toLowerCase();
            const isVisible = name.includes(term);
            card.style.display = isVisible ? 'flex' : 'none';
            if(isVisible) visible++;
        });
        noResultsMessage.style.display = visible === 0 ? 'block' : 'none';
    });
});
</script>
</body>
</html>

