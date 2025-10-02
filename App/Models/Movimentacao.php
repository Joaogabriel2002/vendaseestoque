<?php
// Ficheiro: App/Models/Movimentacao.php

class Movimentacao
{
    /**
     * Lista todas as movimentações de estoque, com filtro de período.
     * A consulta foi atualizada para obter os dados a partir das variações.
     * @param PDO $pdo A conexão com o banco de dados.
     * @param string $periodo O filtro de período ('hoje', 'semana', 'mes', 'sempre').
     * @return array A lista de todas as movimentações.
     */
    public static function listarTodasMovimentacoes(PDO $pdo, string $periodo = 'sempre'): array
    {
        $sql = "SELECT 
                    m.data_hora,
                    m.tipo_movimentacao,
                    m.quantidade,
                    m.observacao,
                    p.nome as nome_produto, 
                    vp.tamanho
                FROM movimentacao_estoque m
                JOIN variacoes_produto vp ON m.id_variacao = vp.id
                JOIN produtos p ON vp.id_produto = p.id";

        $whereClause = '';
        switch ($periodo) {
            case 'hoje':
                $whereClause = " WHERE DATE(m.data_hora) = CURDATE()";
                break;
            case 'semana':
                $whereClause = " WHERE YEARWEEK(m.data_hora, 1) = YEARWEEK(CURDATE(), 1)";
                break;
            case 'mes':
                $whereClause = " WHERE MONTH(m.data_hora) = MONTH(CURDATE()) AND YEAR(m.data_hora) = YEAR(CURDATE())";
                break;
        }

        $sql .= $whereClause . " ORDER BY m.data_hora DESC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

