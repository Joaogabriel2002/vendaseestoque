<?php
session_start();

// Protege a página: se não estiver logado, redireciona
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit();
}

// 1. Incluímos os ficheiros essenciais
require_once __DIR__ . '/../../App/Config/conexao.php';
require_once __DIR__ . '/../../App/Models/Produtos.php';

// 2. Criamos a conexão com o banco
$conexao = new Conexao();
$pdo = $conexao->getConn();

// 3. Verificamos o filtro de estoque selecionado
$filtro_estoque = $_GET['filtro_estoque'] ?? 'todos';

// 4. Buscamos a lista de produtos já com os totais calculados
$produtos = [];
try {
    $todosOsProdutos = Produto::listarTodos($pdo);

    // Aplicamos o filtro de estoque com base no 'estoque_total'
    if ($filtro_estoque === 'zerado') {
        $produtos = array_filter($todosOsProdutos, fn($p) => !isset($p['estoque_total']) || $p['estoque_total'] <= 0);
    } elseif ($filtro_estoque === 'em_estoque') {
        $produtos = array_filter($todosOsProdutos, fn($p) => isset($p['estoque_total']) && $p['estoque_total'] > 0);
    } else {
        $produtos = $todosOsProdutos;
    }

} catch (Exception $e) {
    $erro = "Não foi possível carregar os produtos. Tente novamente mais tarde.";
    error_log($e->getMessage());
}

// Lógica para exibir mensagens de feedback (sucesso/erro)
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
                <div class="relative flex-grow">
                    <input type="text" id="searchInput" placeholder="Buscar produtos..." class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <div class="absolute top-0 left-0 inline-flex items-center p-2 mt-1 ml-1 text-gray-400"><i class="fas fa-search"></i></div>
                </div>
                <a href="CadastrarProdutos.php" class="bg-blue-600 text-white font-bold py-2 px-6 rounded-lg hover:bg-blue-700 transition duration-300 flex items-center justify-center shrink-0"><i class="fas fa-plus mr-2"></i> Novo</a>
            </div>
        </header>

        <div class="mb-8 flex flex-wrap gap-2">
            <a href="?filtro_estoque=todos" class="px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200 <?php echo ($filtro_estoque === 'todos' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100 border'); ?>">Todos</a>
            <a href="?filtro_estoque=em_estoque" class="px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200 <?php echo ($filtro_estoque === 'em_estoque' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100 border'); ?>">Em Estoque</a>
            <a href="?filtro_estoque=zerado" class="px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200 <?php echo ($filtro_estoque === 'zerado' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100 border'); ?>">Estoque Zerado</a>
        </div>
        
        <?php if (!empty($mensagem)): ?>
        <div class="<?php echo $classeAlerta; ?>" role="alert"><?php echo htmlspecialchars($mensagem); ?></div>
        <?php endif; ?>

        <div id="productGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php if (empty($produtos)): ?>
                <div class="col-span-full text-center py-16 bg-white rounded-lg shadow-md border"><i class="fas fa-box-open fa-3x text-gray-400 mb-4"></i><h3 class="text-xl font-semibold text-gray-700">Nenhum produto encontrado</h3></div>
            <?php else: ?>
                <?php foreach ($produtos as $produto): ?>
                    <div class="product-card bg-white rounded-lg shadow-md overflow-hidden flex flex-col">
                        <div class="h-48 bg-gray-200 flex items-center justify-center relative">
                            <?php if (!empty($produto['imagem1'])): ?>
                                <img src="<?php echo $caminhoBaseImagem . htmlspecialchars($produto['imagem1']); ?>" alt="<?php echo htmlspecialchars($produto['nome']); ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <i class="fas fa-image fa-3x text-gray-400"></i>
                            <?php endif; ?>
                            
                            <?php
                                $estoqueTotal = $produto['estoque_total'] ?? 0;
                                $corEstoque = $estoqueTotal > 0 ? 'bg-green-500' : 'bg-red-500';
                            ?>
                            <span class="absolute top-2 right-2 text-xs text-white <?php echo $corEstoque; ?> px-2 py-1 rounded-full font-semibold">Estoque Total: <?php echo $estoqueTotal; ?></span>
                        </div>
                        <div class="p-4 flex-grow flex flex-col justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 truncate" title="<?php echo htmlspecialchars($produto['nome']); ?>"><?php echo htmlspecialchars($produto['nome']); ?></h3>
                                <p class="text-2xl font-bold text-gray-900 mt-2">
                                     <?php if (isset($produto['preco_minimo'])): ?>
                                        <span class="text-sm font-normal text-gray-500">A partir de</span>
                                        R$ <?php echo number_format($produto['preco_minimo'], 2, ',', '.'); ?>
                                    <?php else: ?>
                                        <span class="text-lg font-normal text-gray-500">Sem variações</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="mt-4 flex justify-end gap-2">
                                <a href="EditarProdutos.php?id=<?php echo $produto['id']; ?>" class="text-gray-500 hover:text-blue-600 p-2 rounded-full" title="Editar Produto e Gerir Variações"><i class="fas fa-pencil-alt fa-fw"></i></a>
                                <a href="../../App/Controller/excluir_produto.php?id=<?php echo $produto['id']; ?>" class="text-gray-500 hover:text-red-600 p-2 rounded-full" title="Excluir" onclick="return confirm('Tem a certeza de que deseja excluir o produto \'<?php echo htmlspecialchars(addslashes($produto['nome'])); ?>\'? Esta ação não pode ser desfeita.');"><i class="fas fa-trash-alt fa-fw"></i></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div id="noResultsMessage" class="hidden col-span-full text-center py-16 bg-white rounded-lg shadow-md border"><i class="fas fa-search fa-3x text-gray-400 mb-4"></i><h3 class="text-xl font-semibold text-gray-700">Nenhum resultado encontrado</h3></div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('searchInput');
        const productGrid = document.getElementById('productGrid');
        const productCards = productGrid.querySelectorAll('.product-card');
        const noResultsMessage = document.getElementById('noResultsMessage');
        searchInput.addEventListener('keyup', function () {
            const searchTerm = searchInput.value.toLowerCase();
            let visibleCards = 0;
            productCards.forEach(card => {
                const productName = card.querySelector('h3').textContent.toLowerCase();
                if (productName.includes(searchTerm)) {
                    card.style.display = 'flex';
                    visibleCards++;
                } else {
                    card.style.display = 'none';
                }
            });
            noResultsMessage.style.display = visibleCards === 0 ? 'block' : 'none';
        });
    });
    </script>
</body>
</html>

