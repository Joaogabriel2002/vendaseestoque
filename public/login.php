<?php
session_start();

// Se o utilizador já estiver logado, redireciona para o dashboard
if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Verifica se há uma mensagem de erro na URL
$erro_login = isset($_GET['erro']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Gestão</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">

    <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-8">
        <div class="text-center mb-8">
            <i class="fas fa-store fa-3x text-blue-600"></i>
            <h1 class="text-3xl font-bold text-gray-800 mt-4">Bem-vindo(a)</h1>
            <p class="text-gray-500">Faça login para aceder ao seu painel.</p>
        </div>

        <?php if ($erro_login): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
                <strong class="font-bold">Erro!</strong>
                <span class="block sm:inline">E-mail ou senha inválidos.</span>
            </div>
        <?php endif; ?>

        <form action="../App/Controller/auth_controller.php" method="POST">
            <div class="mb-4">
                <label for="email" class="block text-gray-700 text-sm font-bold mb-2">E-mail</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-envelope text-gray-400"></i>
                    </div>
                    <input type="email" id="email" name="email" class="shadow-sm appearance-none border rounded-lg w-full py-3 px-4 pl-10 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="seuemail@exemplo.com" required>
                </div>
            </div>
            <div class="mb-6">
                <label for="senha" class="block text-gray-700 text-sm font-bold mb-2">Senha</label>
                 <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-lock text-gray-400"></i>
                    </div>
                    <input type="password" id="senha" name="senha" class="shadow-sm appearance-none border rounded-lg w-full py-3 px-4 pl-10 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="************" required>
                </div>
            </div>
            <div class="flex items-center justify-between">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                    Entrar
                </button>
            </div>
        </form>
    </div>

</body>
</html>
