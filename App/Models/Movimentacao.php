<?php
// Ficheiro: App/Models/Movimentacao.php

class Movimentacao
{
    /**
     * Lista todas as movimentações de estoque, com filtro de período e de produto.
     * @param PDO $pdo A conexão com o banco de dados.
     * @param string $periodo O filtro de período.
     * @param int|null $id_produto O ID do produto para filtrar (opcional).
     * @return array A lista de todas as movimentações.
     */
    public static function listarTodasMovimentacoes(PDO $pdo, string $periodo = 'sempre', ?int $id_produto = null): array
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

        $conditions = [];
        $params = [];

        // Adiciona filtro de período
        switch ($periodo) {
            case 'hoje':
                $conditions[] = "DATE(m.data_hora) = CURDATE()";
                break;
            case 'semana':
                $conditions[] = "YEARWEEK(m.data_hora, 1) = YEARWEEK(CURDATE(), 1)";
                break;
            case 'mes':
                $conditions[] = "MONTH(m.data_hora) = MONTH(CURDATE()) AND YEAR(m.data_hora) = YEAR(CURDATE())";
                break;
        }

        // Adiciona filtro de produto, se fornecido
        if ($id_produto) {
            $conditions[] = "p.id = :id_produto";
            $params[':id_produto'] = $id_produto;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY m.data_hora DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

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

