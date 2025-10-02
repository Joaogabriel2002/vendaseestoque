<?php
session_start();

// Protege a página: se o utilizador não estiver logado, redireciona para a página de login.
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit();
}

// 1. Incluímos os ficheiros essenciais
require_once __DIR__ . '/../../App/Config/Conexao.php';
require_once __DIR__ . '/../../App/Models/Movimentacao.php';

// 2. Criamos a conexão com o banco
$conexao = new Conexao();
$pdo = $conexao->getConn();

// 3. Verificamos o filtro de período selecionado na URL
$periodo = $_GET['periodo'] ?? 'sempre';

// 4. Buscamos a lista de movimentações aplicando o filtro
$movimentacoes = [];
try {
    $movimentacoes = Movimentacao::listarTodasMovimentacoes($pdo, $periodo);
} catch (Exception $e) {
    $erro = "Não foi possível carregar as movimentações de estoque.";
    error_log($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movimentação de Estoque</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 font-sans">

    <!-- Navbar -->
    <nav class="bg-white shadow-md">
        <div class="container mx-auto px-6 py-3 flex justify-between items-center">
            <div class="text-xl font-bold text-gray-800">Meu Estoque</div>
            <div>
                <a href="../dashboard.php" class="text-gray-600 hover:text-blue-600 px-3 py-2 rounded">Dashboard</a>
            </div>
        </div>
    </nav>

    <!-- Conteúdo Principal -->
    <div class="container mx-auto p-4 md:p-8">

        <!-- Botão Voltar -->
        <div class="mb-6">
            <a href="../dashboard.php" class="inline-flex items-center text-gray-600 hover:text-gray-900 font-semibold transition-colors duration-300">
                <i class="fas fa-arrow-left mr-2"></i>
                Voltar
            </a>
        </div>

        <header class="flex flex-col md:flex-row justify-between items-center mb-4">
            <h1 class="text-3xl font-bold text-gray-800 mb-4 md:mb-0">Movimentação de Estoque</h1>
        </header>

        <!-- Barra de Filtros de Período -->
        <div class="mb-8 flex flex-wrap gap-2">
            <a href="?periodo=hoje" class="px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200 <?php echo ($periodo === 'hoje' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100 border'); ?>">Hoje</a>
            <a href="?periodo=semana" class="px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200 <?php echo ($periodo === 'semana' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100 border'); ?>">Esta Semana</a>
            <a href="?periodo=mes" class="px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200 <?php echo ($periodo === 'mes' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100 border'); ?>">Este Mês</a>
            <a href="?periodo=sempre" class="px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200 <?php echo ($periodo === 'sempre' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100 border'); ?>">Sempre</a>
        </div>

        <?php if (isset($erro)): ?>
            <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg" role="alert">
                <?php echo htmlspecialchars($erro); ?>
            </div>
        <?php endif; ?>

        <!-- Tabela de Movimentações -->
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full leading-normal">
                    <thead>
                        <tr class="border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            <th class="px-5 py-3">Data e Hora</th>
                            <th class="px-5 py-3">Produto (Tamanho)</th>
                            <th class="px-5 py-3 text-center">Tipo</th>
                            <th class="px-5 py-3 text-center">Quantidade</th>
                            <th class="px-5 py-3">Observação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($movimentacoes)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-10 px-5">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-history fa-3x text-gray-400 mb-4"></i>
                                        <p class="text-gray-700 font-semibold">Nenhuma movimentação encontrada para este período.</p>
                                        <p class="text-gray-500 text-sm">Entradas e saídas de estoque aparecerão aqui.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($movimentacoes as $mov): ?>
                                <tr class="border-b border-gray-200 hover:bg-gray-50">
                                    <td class="px-5 py-4 text-sm">
                                        <?php echo htmlspecialchars(date('d/m/Y H:i:s', strtotime($mov['data_hora']))); ?>
                                    </td>
                                    <td class="px-5 py-4 text-sm">
                                        <p class="text-gray-900 font-semibold whitespace-no-wrap"><?php echo htmlspecialchars($mov['nome_produto']); ?></p>
                                        <p class="text-gray-600 text-xs whitespace-no-wrap">
                                            Tamanho: <?php echo htmlspecialchars($mov['tamanho']); ?>
                                        </p>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-center">
                                        <?php
                                            $tipo = htmlspecialchars($mov['tipo_movimentacao']);
                                            $corTipo = $tipo === 'ENTRADA' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                                            echo "<span class='px-2 py-1 font-semibold leading-tight text-xs rounded-full {$corTipo}'>{$tipo}</span>";
                                        ?>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-center">
                                        <?php
                                            $quantidade = htmlspecialchars($mov['quantidade']);
                                            $sinal = $mov['tipo_movimentacao'] === 'ENTRADA' ? '+' : '-';
                                            $corQuantidade = $mov['tipo_movimentacao'] === 'ENTRADA' ? 'text-green-600' : 'text-red-600';
                                            echo "<span class='font-bold {$corQuantidade}'>{$sinal} {$quantidade}</span>";
                                        ?>
                                    </td>
                                    <td class="px-5 py-4 text-sm">
                                        <?php echo htmlspecialchars($mov['observacao']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>

