<?php
// Ficheiro: App/Models/Categoria.php

class Categoria
{
    /**
     * Lista todas as categorias do banco de dados, ordenadas por nome.
     * @param PDO $pdo A conexão com o banco de dados.
     * @return array A lista de categorias, cada uma contendo 'id' e 'nome'.
     */
    public static function listarTodas(PDO $pdo): array
    {
        try {
            $sql = "SELECT id, nome FROM categorias ORDER BY nome ASC";
            $stmt = $pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Em caso de erro, regista-o e retorna um array vazio para não quebrar a página.
            error_log("Erro ao buscar categorias: " . $e->getMessage());
            return [];
        }
    }
}

