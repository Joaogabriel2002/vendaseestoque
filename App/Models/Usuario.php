<?php
// Ficheiro: App/Models/Usuario.php

class Usuario
{
    /**
     * Autentica um utilizador com base no e-mail e senha.
     * @param PDO $pdo A conexão com o banco de dados.
     * @param string $email O e-mail do utilizador.
     * @param string $senha A senha do utilizador.
     * @return array|null Retorna os dados do utilizador se a autenticação for bem-sucedida, caso contrário, null.
     */
    public static function autenticar(PDO $pdo, string $email, string $senha): ?array
    {
        try {
            // 1. Busca o utilizador pelo e-mail
            $sql = "SELECT id, email, senha FROM usuarios WHERE email = :email";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // 2. Se o utilizador não for encontrado, a autenticação falha
            if (!$user) {
                return null;
            }

            // 3. Verifica se a senha fornecida corresponde à senha hashada no banco de dados
            // NOTA: Isto assume que a sua senha está guardada usando password_hash()
            if (password_verify($senha, $user['senha'])) {
                // Senha correta, retorna os dados do utilizador
                return $user;
            }

            // Senha incorreta
            return null;

        } catch (PDOException $e) {
            // Regista o erro para depuração
            error_log("Erro de autenticação: " . $e->getMessage());
            return null;
        }
    }
}
