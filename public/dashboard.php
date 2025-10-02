<?php
session_start();

// Protege a página: se o utilizador não estiver logado, redireciona para a página de login.
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

// Obtém o e-mail do utilizador da sessão para uma mensagem de boas-vindas.
$email_usuario = htmlspecialchars($_SESSION['usuario_email'] ?? 'Utilizador');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Sistema de Gestão</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">

    <div class="min-h-screen flex flex-col">
        <!-- Cabeçalho -->
        <header class="bg-white shadow-md p-4">
            <div class="container mx-auto flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-store text-blue-600"></i>
                    Painel de Gestão
                </h1>
                <div class="flex items-center">
                    <span class="text-gray-600 mr-4 hidden sm:block">Olá, <?php echo $email_usuario; ?></span>
                    <!-- Botão Sair que aponta para o controlador de logout -->
                    <a href="../App/Controller/logout_controller.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors duration-300" title="Sair">
                        <i class="fas fa-sign-out-alt mr-2"></i>Sair
                    </a>
                </div>
            </div>
        </header>

        <!-- Corpo do Dashboard -->
        <main class="flex-grow container mx-auto p-4 md:p-8">
            <div class="text-left mb-10">
                <h2 class="text-3xl font-semibold text-gray-700">Bem-vindo(a) de volta!</h2>
                <p class="text-gray-500 mt-2">Selecione uma das opções abaixo para começar a gerir o seu negócio.</p>
            </div>

            <!-- Grid de Cards de Ação -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">

                <!-- Card: Frente de Caixa (PDV) -->
                <a href="Vendas/FrenteCaixa.php" class="group bg-white rounded-lg shadow-lg hover:shadow-2xl transition-shadow duration-300 transform hover:-translate-y-2 block">
                    <div class="p-8"><div class="flex items-center justify-center h-16 w-16 rounded-full bg-purple-100 text-purple-600 mb-6 group-hover:bg-purple-600 group-hover:text-white transition-colors duration-300"><i class="fas fa-cash-register fa-2x"></i></div><h3 class="text-2xl font-bold text-gray-800 mb-2">Frente de Caixa</h3><p class="text-gray-600">Inicie uma nova venda.</p></div>
                </a>
                
                <!-- Card: Gerir Produtos -->
                <a href="Produtos/ListarProdutos.php" class="group bg-white rounded-lg shadow-lg hover:shadow-2xl transition-shadow duration-300 transform hover:-translate-y-2 block">
                    <div class="p-8"><div class="flex items-center justify-center h-16 w-16 rounded-full bg-blue-100 text-blue-600 mb-6 group-hover:bg-blue-600 group-hover:text-white transition-colors duration-300"><i class="fas fa-box-open fa-2x"></i></div><h3 class="text-2xl font-bold text-gray-800 mb-2">Gerir Produtos</h3><p class="text-gray-600">Adicione, edite e gira o inventário.</p></div>
                </a>

                <!-- Card: Histórico de Pedidos -->
                <a href="Relatorios/HistoricoPedidos.php" class="group bg-white rounded-lg shadow-lg hover:shadow-2xl transition-shadow duration-300 transform hover:-translate-y-2 block">
                    <div class="p-8"><div class="flex items-center justify-center h-16 w-16 rounded-full bg-indigo-100 text-indigo-600 mb-6 group-hover:bg-indigo-600 group-hover:text-white transition-colors duration-300"><i class="fas fa-receipt fa-2x"></i></div><h3 class="text-2xl font-bold text-gray-800 mb-2">Histórico de Pedidos</h3><p class="text-gray-600">Consulte todas as vendas e os seus itens.</p></div>
                </a>

                <!-- Card: Relatório de Lucro -->
                <a href="Relatorios/HistoricoVendas.php" class="group bg-white rounded-lg shadow-lg hover:shadow-2xl transition-shadow duration-300 transform hover:-translate-y-2 block">
                    <div class="p-8"><div class="flex items-center justify-center h-16 w-16 rounded-full bg-green-100 text-green-600 mb-6 group-hover:bg-green-600 group-hover:text-white transition-colors duration-300"><i class="fas fa-chart-line fa-2x"></i></div><h3 class="text-2xl font-bold text-gray-800 mb-2">Relatório de Lucro</h3><p class="text-gray-600">Analise o lucro de cada venda.</p></div>
                </a>
                
                <!-- Card: Movimentação de Estoque -->
                <a href="Relatorios/MovimentacaoEstoque.php" class="group bg-white rounded-lg shadow-lg hover:shadow-2xl transition-shadow duration-300 transform hover:-translate-y-2 block">
                    <div class="p-8"><div class="flex items-center justify-center h-16 w-16 rounded-full bg-yellow-100 text-yellow-600 mb-6 group-hover:bg-yellow-600 group-hover:text-white transition-colors duration-300"><i class="fas fa-exchange-alt fa-2x"></i></div><h3 class="text-2xl font-bold text-gray-800 mb-2">Movimentação de Estoque</h3><p class="text-gray-600">Acompanhe todas as entradas e saídas.</p></div>
                </a>

                <!-- Card: Fechamento de Caixa -->
                <a href="Relatorios/FechamentoCaixa.php" class="group bg-white rounded-lg shadow-lg hover:shadow-2xl transition-shadow duration-300 transform hover:-translate-y-2 block">
                    <div class="p-8">
                        <div class="flex items-center justify-center h-16 w-16 rounded-full bg-teal-100 text-teal-600 mb-6 group-hover:bg-teal-600 group-hover:text-white transition-colors duration-300">
                            <i class="fas fa-calculator fa-2x"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-2">Fechamento de Caixa</h3>
                        <p class="text-gray-600">Consulte o resumo de vendas diário por forma de pagamento.</p>
                    </div>
                </a>

            </div>
        </main>
    </div>
</body>
</html>

