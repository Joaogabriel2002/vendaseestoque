<?php
// Ficheiro: App/Controller/logout_controller.php

// 1. Inicia a sessão para poder aceder às suas variáveis.
session_start();

// 2. Limpa todas as variáveis da sessão.
$_SESSION = array();

// 3. Destrói a sessão.
session_destroy();

// 4. Redireciona o utilizador para a página de login.
// O caminho assume que o login.php está dentro da pasta public.
header("Location: ../../public/login.php");
exit();
?>
