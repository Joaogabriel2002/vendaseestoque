<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit();
}

require_once __DIR__ . '/../../App/Config/Conexao.php';
require_once __DIR__ . '/../../App/Models/Venda.php';

$conexao = new Conexao();
$pdo = $conexao->getConn();

// Pega a data da URL, ou usa a data de hoje como padrão
$data_filtro = $_GET['data'] ?? date('Y-m-d');
$dados_caixa = [];
$total_geral = 0;
$total_vendas = 0;

try {
    $dados_caixa = Venda::gerarFechamentoDeCaixa($pdo, $data_filtro);
    // Calcula os totais
    foreach ($dados_caixa as $dado) {
        $total_geral += $dado['valor_apurado'];
        $total_vendas += $dado['total_vendas'];
    }
} catch (Exception $e) {
    $erro = "Não foi possível gerar o fechamento de caixa.";
    error_log($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Fechamento de Caixa</title>
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

        <header class="mb-4">
            <h1 class="text-3xl font-bold text-gray-800">Fechamento de Caixa</h1>
        </header>

        <form method="GET" class="mb-8 p-4 bg-white rounded-lg shadow-sm border">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
                <div>
                    <label for="data" class="block text-sm font-medium text-gray-700">Selecione a Data:</label>
                    <input type="date" name="data" id="data" value="<?php echo htmlspecialchars($data_filtro); ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>
                <div><button type="submit" class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded-lg"><i class="fas fa-search mr-2"></i>Consultar</button></div>
            </div>
        </form>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-green-500 text-white p-6 rounded-lg shadow-lg">
                <h3 class="text-lg font-semibold">Valor Total Apurado</h3>
                <p class="text-4xl font-bold mt-2">R$ <?php echo number_format($total_geral, 2, ',', '.'); ?></p>
            </div>
            <div class="bg-blue-500 text-white p-6 rounded-lg shadow-lg">
                <h3 class="text-lg font-semibold">Total de Vendas</h3>
                <p class="text-4xl font-bold mt-2"><?php echo $total_vendas; ?></p>
            </div>
        </div>

        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <h3 class="text-xl font-bold text-gray-800 p-4 border-b">Resumo por Forma de Pagamento</h3>
            <table class="min-w-full">
                <tbody>
                    <?php if (empty($dados_caixa)): ?>
                        <tr><td class="text-center py-10 px-5">Nenhuma venda encontrada para esta data.</td></tr>
                    <?php else: ?>
                        <?php foreach($dados_caixa as $dado): ?>
                            <tr class="border-b">
                                <td class="px-5 py-4 font-semibold"><?php echo htmlspecialchars($dado['forma_pagamento']); ?></td>
                                <td class="px-5 py-4 text-center"><?php echo $dado['total_vendas']; ?> venda(s)</td>
                                <td class="px-5 py-4 text-right font-bold text-gray-700">R$ <?php echo number_format($dado['valor_apurado'], 2, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
