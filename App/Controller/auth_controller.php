<?php
// Ficheiro: App/Controller/auth_controller.php

// Inicia a sessão em todas as requisições para este controlador
session_start();

// Inclui os ficheiros necessários
require_once __DIR__ . '/../Config/Conexao.php';
require_once __DIR__ . '/../Models/Usuario.php';

// Verifica se o formulário foi submetido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';

    // Validação básica
    if (empty($email) || empty($senha)) {
        // Redireciona de volta com uma mensagem de erro genérica
        header('Location: ../../public/login.php?erro=1');
        exit();
    }

    // Tenta autenticar
    $conexao = new Conexao();
    $pdo = $conexao->getConn();
    $usuario = Usuario::autenticar($pdo, $email, $senha);

    if ($usuario) {
        // Sucesso na autenticação: guarda os dados na sessão
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_email'] = $usuario['email'];

        // Redireciona para o dashboard
        header('Location: ../../public/dashboard.php');
        exit();
    } else {
        // Falha na autenticação: redireciona de volta com erro
        header('Location: ../../public/login.php?erro=1');
        exit();
    }
} else {
    // Se alguém tentar aceder a este ficheiro diretamente, redireciona
    header('Location: ../../public/login.php');
    exit();
}
