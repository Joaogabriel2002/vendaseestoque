<?php
session_start();

// Protege a página: se o utilizador não estiver logado, redireciona.
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit();
}

require_once __DIR__ . '/../../App/Config/Conexao.php';
require_once __DIR__ . '/../../App/Models/Movimentacao.php';

$conexao = new Conexao();
$pdo = $conexao->getConn();

// 1. Verifica o filtro de período selecionado na URL
$periodo = $_GET['periodo'] ?? 'sempre';
$tituloPeriodo = 'do Período';
switch ($periodo) {
    case 'hoje': $tituloPeriodo = 'de Hoje'; break;
    case 'semana': $tituloPeriodo = 'desta Semana'; break;
    case 'mes': $tituloPeriodo = 'deste Mês'; break;
}

// 2. Busca os dados de vendas, já aplicando o filtro
$vendas = [];
$lucroTotalGeral = 0;
try {
    $vendas = Movimentacao::listarVendasComLucro($pdo, $periodo);
} catch (Exception $e) {
    $erro = "Não foi possível carregar o histórico de vendas.";
    error_log($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Lucro por Venda</title>
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
            <h1 class="text-3xl font-bold text-gray-800 mb-4 md:mb-0">Relatório de Lucro por Venda</h1>
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
                <div><button type="submit" class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-700"><i class="fas fa-filter mr-2"></i>Aplicar Filtro</button></div>
            </div>
        </form>

        <?php if (isset($erro)): ?>
            <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <!-- Tabela de Vendas -->
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full leading-normal">
                    <thead>
                        <tr class="border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            <th class="px-5 py-3">Data</th>
                            <th class="px-5 py-3">Produto (Tamanho)</th>
                            <th class="px-5 py-3 text-center">Qtd.</th>
                            <th class="px-5 py-3 text-right">P. Custo</th>
                            <th class="px-5 py-3 text-right">P. Venda</th>
                            <th class="px-5 py-3 text-right">Lucro (R$)</th>
                            <th class="px-5 py-3 text-right">Margem (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($vendas)): ?>
                            <tr><td colspan="7" class="text-center py-10 px-5"><div class="flex flex-col items-center"><i class="fas fa-chart-line fa-3x text-gray-400 mb-4"></i><p class="text-gray-700 font-semibold">Nenhuma venda encontrada para este período.</p></div></td></tr>
                        <?php else: ?>
                            <?php foreach ($vendas as $venda):
                                $precoCusto = $venda['preco_custo'] ?? 0;
                                $precoVenda = $venda['preco_venda'] ?? 0;
                                $quantidade = $venda['quantidade'];
                                $lucroPorItem = $precoVenda - $precoCusto;
                                $lucroTotalLinha = $lucroPorItem * $quantidade;
                                // Calcula a margem sobre o preço de venda para evitar divisão por zero
                                $margem = ($precoVenda > 0) ? ($lucroPorItem / $precoVenda) * 100 : 0;
                                $lucroTotalGeral += $lucroTotalLinha;
                            ?>
                                <tr class="border-b border-gray-200 hover:bg-gray-50">
                                    <td class="px-5 py-4 text-sm"><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($venda['data_hora']))); ?></td>
                                    <td class="px-5 py-4 text-sm"><p class="font-medium"><?php echo htmlspecialchars($venda['nome_produto']); ?></p><p class="text-xs text-gray-600">Tamanho: <?php echo htmlspecialchars($venda['tamanho']); ?></p></td>
                                    <td class="px-5 py-4 text-sm text-center"><?php echo $quantidade; ?></td>
                                    <td class="px-5 py-4 text-sm text-right text-red-600">R$ <?php echo number_format($precoCusto, 2, ',', '.'); ?></td>
                                    <td class="px-5 py-4 text-sm text-right text-green-600">R$ <?php echo number_format($precoVenda, 2, ',', '.'); ?></td>
                                    <td class="px-5 py-4 text-sm text-right font-bold text-blue-600">R$ <?php echo number_format($lucroTotalLinha, 2, ',', '.'); ?></td>
                                    <td class="px-5 py-4 text-sm text-right font-semibold"><?php echo number_format($margem, 2, ',', '.'); ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <?php if (!empty($vendas)): ?>
                    <tfoot class="bg-gray-100">
                        <tr class="font-bold text-gray-800">
                            <td colspan="5" class="px-5 py-4 text-right text-lg">Lucro Total <?php echo htmlspecialchars($tituloPeriodo); ?>:</td>
                            <td colspan="2" class="px-5 py-4 text-left text-xl text-blue-700">R$ <?php echo number_format($lucroTotalGeral, 2, ',', '.'); ?></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

