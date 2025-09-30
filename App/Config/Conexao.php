<?php
// Ficheiro: App/Config/Conexao.php

class Conexao
{
    // --- Detalhes da Conexão ---
    // Altere estes valores para corresponderem à sua configuração local.
    private $host = "localhost";
    private $dbname = "loja";
    private $user = "root";
    private $password = "";
    // ---------------------------

    /** @var PDO A instância da conexão PDO. */
    protected $conn;

    /**
     * O construtor é chamado automaticamente quando um novo objeto Conexao é criado.
     * Ele estabelece a conexão com a base de dados.
     */
    public function __construct()
    {
        try {
            // Cria uma nova instância do PDO para se conectar à base de dados MySQL.
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                $this->user,
                $this->password
            );

            // Define o modo de erro do PDO para exceções.
            // Isto significa que, se ocorrer um erro de SQL, o PDO irá lançar uma exceção,
            // o que nos permite capturá-la no bloco catch.
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch (PDOException $e) {
            // Se a conexão falhar, interrompe a execução e exibe uma mensagem de erro clara.
            // Numa aplicação em produção, seria ideal registar este erro num ficheiro de log
            // em vez de o exibir diretamente no ecrã.
            die("Erro de conexão com a base de dados: " . $e->getMessage());
        }
    }

    /**
     * Método público para obter a instância da conexão PDO.
     * Os outros ficheiros irão usar este método para poderem executar queries.
     * @return PDO A instância da conexão ativa.
     */
    public function getConn(): PDO
    {
        return $this->conn;
    }
}
