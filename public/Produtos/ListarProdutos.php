<?php
session_start();

// Protege a página: se não estiver logado, redireciona
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit();
}

require_once __DIR__ . '/../../App/Config/conexao.php';
require_once __DIR__ . '/../../App/Models/Produtos.php';

$conexao = new Conexao();
$pdo = $conexao->getConn();

// 1. Verificamos o filtro de estoque selecionado
$filtro_estoque = $_GET['filtro_estoque'] ?? 'todos';
$variacoes = [];

try {
    // 2. AGORA CHAMA O NOVO MÉTODO que lista cada variação individualmente
    $todasAsVariacoes = Produto::listarTodasVariacoes($pdo);

    // 3. Aplica o filtro com base no estoque de CADA variação
    if ($filtro_estoque === 'zerado') {
        $variacoes = array_filter($todasAsVariacoes, fn($v) => $v['quantidade_estoque'] <= 0);
    } elseif ($filtro_estoque === 'em_estoque') {
        $variacoes = array_filter($todasAsVariacoes, fn($v) => $v['quantidade_estoque'] > 0);
    } else {
        $variacoes = $todasAsVariacoes;
    }

} catch (Exception $e) {
    $erro = "Não foi possível carregar as variações dos produtos.";
    error_log($e->getMessage());
}

// Lógica de feedback (inalterada)
$mensagem = '';
$classeAlerta = '';
if (isset($_GET['status'], $_GET['msg'])) {
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
    <title>Inventário Detalhado por Tamanho</title>
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
            <h1 class="text-3xl font-bold text-gray-800 mb-4 md:mb-0">Inventário por Tamanho</h1>
            <div class="flex gap-4 w-full md:w-auto">
                <div class="relative flex-grow"><input type="text" id="searchInput" placeholder="Buscar produtos ou tamanhos..." class="w-full pl-10 pr-4 py-2 border rounded-lg"><div class="absolute top-0 left-0 p-2 mt-1 ml-1 text-gray-400"><i class="fas fa-search"></i></div></div>
                <a href="CadastrarProdutos.php" class="bg-blue-600 text-white font-bold py-2 px-6 rounded-lg hover:bg-blue-700 flex items-center shrink-0"><i class="fas fa-plus mr-2"></i> Novo Produto</a>
            </div>
        </header>

        <div class="mb-8 flex flex-wrap gap-2">
            <a href="?filtro_estoque=todos" class="px-4 py-2 text-sm font-medium rounded-lg <?php echo ($filtro_estoque === 'todos' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border'); ?>">Todos</a>
            <a href="?filtro_estoque=em_estoque" class="px-4 py-2 text-sm font-medium rounded-lg <?php echo ($filtro_estoque === 'em_estoque' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border'); ?>">Em Estoque</a>
            <a href="?filtro_estoque=zerado" class="px-4 py-2 text-sm font-medium rounded-lg <?php echo ($filtro_estoque === 'zerado' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border'); ?>">Estoque Zerado</a>
        </div>
        
        <?php if (!empty($mensagem)): ?><div class="<?php echo $classeAlerta; ?>"><?php echo htmlspecialchars($mensagem); ?></div><?php endif; ?>

        <div id="productGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php if (empty($variacoes)): ?>
                <div class="col-span-full text-center py-16 bg-white rounded-lg shadow-md border"><i class="fas fa-box-open fa-3x text-gray-400 mb-4"></i><h3 class="text-xl font-semibold text-gray-700">Nenhuma variação encontrada para este filtro.</h3></div>
            <?php else: ?>
                <?php foreach ($variacoes as $var): ?>
                    <div class="product-card bg-white rounded-lg shadow-md flex flex-col">
                        <div class="h-48 bg-gray-200 flex items-center justify-center relative">
                            <img src="<?php echo $caminhoBaseImagem . ($var['imagem1'] ?? 'placeholder.png'); ?>" alt="<?php echo htmlspecialchars($var['nome_produto']); ?>" class="w-full h-full object-cover">
                            <?php $estoque = $var['quantidade_estoque']; $corEstoque = $estoque > 0 ? 'bg-green-500' : 'bg-red-500'; ?>
                            <span class="absolute top-2 right-2 text-xs text-white <?php echo $corEstoque; ?> px-2 py-1 rounded-full font-semibold">Estoque: <?php echo $estoque; ?></span>
                        </div>
                        <div class="p-4 flex-grow flex flex-col justify-between">
                            <div>
                                <h3 class="text-lg font-semibold truncate" title="<?php echo htmlspecialchars($var['nome_produto']); ?>"><?php echo htmlspecialchars($var['nome_produto']); ?></h3>
                                <p class="text-gray-500">Tamanho: <span class="font-bold text-gray-800"><?php echo htmlspecialchars($var['tamanho']); ?></span></p>
                                <p class="text-2xl font-bold text-gray-900 mt-2">R$ <?php echo number_format($var['preco_venda'], 2, ',', '.'); ?></p>
                            </div>
                            <div class="mt-4 flex justify-end gap-2">
                                <button type="button" class="open-modal-estoque text-gray-500 hover:text-green-600 p-2 rounded-full" title="Adicionar Estoque" data-id-variacao="<?php echo $var['id_variacao']; ?>" data-nome-variacao="<?php echo htmlspecialchars($var['nome_produto'] . ' - ' . $var['tamanho']); ?>"><i class="fas fa-plus-circle fa-fw"></i></button>
                                <a href="EditarProdutos.php?id=<?php echo $var['id_produto']; ?>" class="text-gray-500 hover:text-blue-600 p-2 rounded-full" title="Editar Produto Pai"><i class="fas fa-pencil-alt fa-fw"></i></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div id="noResultsMessage" class="hidden col-span-full text-center py-16 bg-white rounded-lg shadow-md border"><i class="fas fa-search fa-3x text-gray-400 mb-4"></i><h3 class="text-xl font-semibold text-gray-700">Nenhum resultado encontrado.</h3></div>
    </div>

    <!-- Modal Adicionar Estoque (Simplificado) -->
    <div id="modalEstoque" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
            <div class="flex justify-between items-center mb-4 border-b pb-3">
                <h3 class="text-xl font-semibold" id="modalEstoqueTitle"></h3>
                <button id="closeModalEstoque" class="text-2xl">&times;</button>
            </div>
            <form action="../../App/Controller/adicionar_estoque.php" method="POST">
                <input type="hidden" name="id_variacao" id="id_variacao_estoque">
                <div class="mb-4"><label class="block text-sm font-bold mb-2">Quantidade a Adicionar:</label><input type="number" name="quantidade" class="shadow border rounded w-full py-2 px-3" min="1" required></div>
                <div class="mb-6"><label class="block text-sm font-bold mb-2">Motivo/Observação:</label><input type="text" name="observacao" value="Entrada manual de estoque" class="shadow border rounded w-full py-2 px-3" required></div>
                <div class="flex justify-end gap-4">
                    <button type="button" id="cancelModalEstoque" class="bg-gray-500 text-white font-bold py-2 px-4 rounded">Cancelar</button>
                    <button type="submit" class="bg-blue-600 text-white font-bold py-2 px-4 rounded">Salvar Entrada</button>
                </div>
            </form>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const productCards = document.querySelectorAll('.product-card');
    const noResultsMessage = document.getElementById('noResultsMessage');
    
    searchInput.addEventListener('keyup', () => {
        const term = searchInput.value.toLowerCase();
        let visibleCount = 0;
        productCards.forEach(card => {
            const cardText = card.textContent.toLowerCase();
            const isVisible = cardText.includes(term);
            card.style.display = isVisible ? 'flex' : 'none';
            if (isVisible) visibleCount++;
        });
        noResultsMessage.style.display = visibleCount === 0 ? 'block' : 'none';
    });

    const modal = document.getElementById('modalEstoque');
    const openModalButtons = document.querySelectorAll('.open-modal-estoque');
    const closeModalButton = document.getElementById('closeModalEstoque');
    const cancelModalButton = document.getElementById('cancelModalEstoque');
    const modalTitle = document.getElementById('modalEstoqueTitle');
    const idVariacaoInput = document.getElementById('id_variacao_estoque');
    
    function openModal(idVariacao, nomeVariacao) {
        modalTitle.textContent = `Adicionar Estoque para: ${nomeVariacao}`;
        idVariacaoInput.value = idVariacao;
        modal.classList.remove('hidden');
    }
    function closeModal() { modal.classList.add('hidden'); }

    openModalButtons.forEach(btn => btn.addEventListener('click', function() { openModal(this.dataset.idVariacao, this.dataset.nomeVariacao); }));
    closeModalButton.addEventListener('click', closeModal);
    cancelModalButton.addEventListener('click', closeModal);
    modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
});
</script>
</body>
</html>

