<?php
// Ficheiro: App/Models/Movimentacao.php

class Movimentacao
{
    /**
     * Lista os itens vendidos com detalhes para cálculo de lucro, com filtro de período.
     * @param PDO $pdo A conexão com o banco de dados.
     * @param string $periodo O filtro de período ('hoje', 'semana', 'mes', 'sempre').
     * @return array A lista de itens vendidos com detalhes financeiros.
     */
    public static function listarVendasComLucro(PDO $pdo, string $periodo = 'sempre'): array
    {
        $sql = "SELECT
                    v.data_hora, 
                    p.nome AS nome_produto, 
                    vp.tamanho,
                    iv.quantidade, 
                    vp.preco_custo, 
                    iv.preco_unitario_momento AS preco_venda
                FROM itens_venda AS iv
                JOIN vendas AS v ON iv.id_venda = v.id
                JOIN variacoes_produto AS vp ON iv.id_variacao = vp.id
                JOIN produtos AS p ON vp.id_produto = p.id";

        $whereClause = '';
        switch ($periodo) {
            case 'hoje': 
                $whereClause = " WHERE DATE(v.data_hora) = CURDATE()"; 
                break;
            case 'semana': 
                $whereClause = " WHERE YEARWEEK(v.data_hora, 1) = YEARWEEK(CURDATE(), 1)"; 
                break;
            case 'mes': 
                $whereClause = " WHERE MONTH(v.data_hora) = MONTH(CURDATE()) AND YEAR(v.data_hora) = YEAR(CURDATE())"; 
                break;
        }
        $sql .= $whereClause . " ORDER BY v.data_hora DESC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

