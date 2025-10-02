<?php
session_start();

// Protege a página: se o utilizador não estiver logado, redireciona.
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit();
}

require_once __DIR__ . '/../../App/Config/Conexao.php';
require_once __DIR__ . '/../../App/Models/Venda.php';

$conexao = new Conexao();
$pdo = $conexao->getConn();

// 1. Verifica o filtro de período selecionado na URL
$periodo = $_GET['periodo'] ?? 'sempre';
$pedidos = [];
try {
    // 2. Busca os pedidos aplicando o filtro
    $pedidos = Venda::listarTodas($pdo, $periodo);
} catch (Exception $e) {
    $erro = "Não foi possível carregar o histórico de pedidos.";
    error_log($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Pedidos</title>
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
        <div class="mb-6">
            <a href="../dashboard.php" class="inline-flex items-center text-gray-600 hover:text-gray-900 font-semibold">
                <i class="fas fa-arrow-left mr-2"></i>Voltar
            </a>
        </div>
        <header class="flex flex-col md:flex-row justify-between items-center mb-4">
            <h1 class="text-3xl font-bold text-gray-800">Histórico de Pedidos</h1>
        </header>

        <!-- Formulário de Filtros -->
        <form action="" method="GET" class="mb-8 p-4 bg-white rounded-lg shadow-sm border">
             <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
                <div>
                    <label for="periodo" class="block text-sm font-medium text-gray-700">Filtrar por Período:</label>
                    <select name="periodo" id="periodo" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                        <option value="sempre" <?php echo ($periodo === 'sempre' ? 'selected' : ''); ?>>Sempre</option>
                        <option value="hoje" <?php echo ($periodo === 'hoje' ? 'selected' : ''); ?>>Hoje</option>
                        <option value="semana" <?php echo ($periodo === 'semana' ? 'selected' : ''); ?>>Esta Semana</option>
                        <option value="mes" <?php echo ($periodo === 'mes' ? 'selected' : ''); ?>>Este Mês</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-700 transition duration-300 flex items-center justify-center">
                        <i class="fas fa-filter mr-2"></i> Aplicar Filtro
                    </button>
                </div>
            </div>
        </form>

        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full leading-normal">
                    <thead>
                        <tr class="border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            <th class="px-5 py-3">ID Pedido</th>
                            <th class="px-5 py-3">Data</th>
                            <th class="px-5 py-3">Utilizador</th>
                            <th class="px-5 py-3">Nº Documento</th>
                            <th class="px-5 py-3 text-right">Valor Total</th>
                            <th class="px-5 py-3 text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pedidos)): ?>
                            <tr><td colspan="6" class="text-center py-10">Nenhum pedido encontrado para este período.</td></tr>
                        <?php else: ?>
                            <?php foreach ($pedidos as $pedido): ?>
                                <tr class="border-b border-gray-200 hover:bg-gray-50">
                                    <td class="px-5 py-4 text-sm font-bold">#<?php echo $pedido['id']; ?></td>
                                    <td class="px-5 py-4 text-sm"><?php echo date('d/m/Y H:i', strtotime($pedido['data_hora'])); ?></td>
                                    <td class="px-5 py-4 text-sm"><?php echo htmlspecialchars($pedido['nome_usuario']); ?></td>
                                    <td class="px-5 py-4 text-sm"><?php echo htmlspecialchars($pedido['numero_documento']); ?></td>
                                    <td class="px-5 py-4 text-sm text-right font-semibold">R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?></td>
                                    <td class="px-5 py-4 text-sm text-center">
                                        <button onclick="verDetalhes(<?php echo $pedido['id']; ?>)" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 text-xs">
                                            <i class="fas fa-eye mr-1"></i>Ver Itens
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal para Detalhes do Pedido -->
    <div id="detalhesModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-6 shadow-xl max-w-2xl w-full">
            <div class="flex justify-between items-center border-b pb-3 mb-4">
                <h3 id="modalTitle" class="text-2xl font-bold"></h3>
                <button onclick="fecharModal()" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
            </div>
            <div id="modalBody" class="max-h-96 overflow-y-auto"></div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('detalhesModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalBody = document.getElementById('modalBody');

        async function verDetalhes(pedidoId) {
            modalTitle.innerText = `Detalhes do Pedido #${pedidoId}`;
            modalBody.innerHTML = '<p class="text-center py-8">A carregar...</p>';
            modal.classList.remove('hidden');

            try {
                // Chama o ficheiro Ajax que está no seu Canvas
                const response = await fetch(`../../App/Ajax/buscar_detalhes_venda.php?id=${pedidoId}`);
                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.erro || 'Erro na resposta do servidor.');
                }
                
                const itens = await response.json();
                
                let content = '<table class="min-w-full">';
                content += '<thead class="bg-gray-100"><tr><th class="p-2 text-left">Produto</th><th class="p-2 text-center">Qtd.</th><th class="p-2 text-right">Preço Unit.</th><th class="p-2 text-right">Subtotal</th></tr></thead><tbody>';
                let total = 0;
                itens.forEach(item => {
                    const subtotal = item.quantidade * item.preco_unitario_momento;
                    total += subtotal;
                    const nomeCompleto = `${item.nome_produto} (Tamanho: ${item.tamanho})`;
                    content += `<tr class="border-b"><td class="p-2">${nomeCompleto}</td><td class="p-2 text-center">${item.quantidade}</td><td class="p-2 text-right">R$ ${parseFloat(item.preco_unitario_momento).toFixed(2)}</td><td class="p-2 text-right font-semibold">R$ ${subtotal.toFixed(2)}</td></tr>`;
                });
                content += `</tbody><tfoot class="font-bold bg-gray-100"><tr class="border-t-2"><td colspan="3" class="p-2 text-right">Total do Pedido:</td><td class="p-2 text-right">R$ ${total.toFixed(2)}</td></tr></tfoot></table>`;
                modalBody.innerHTML = content;

            } catch (error) {
                modalBody.innerHTML = `<p class="text-center py-8 text-red-500">${error.message}</p>`;
            }
        }

        function fecharModal() {
            modal.classList.add('hidden');
        }
    </script>
</body>
</html>

