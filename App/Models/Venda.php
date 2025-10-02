<?php
// Ficheiro: App/Models/Venda.php

class Venda
{
    private $id;
    private $id_usuario;
    private $valor_total;
    private $numero_documento;
    private $forma_pagamento;
    private $data_hora;

    // --- Getters e Setters ---
    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; }
    public function getIdUsuario() { return $this->id_usuario; }
    public function setIdUsuario($id_usuario) { $this->id_usuario = $id_usuario; }
    public function getValorTotal() { return $this->valor_total; }
    public function setValorTotal($valor_total) { $this->valor_total = $valor_total; }
    public function getNumeroDocumento() { return $this->numero_documento; }
    public function setNumeroDocumento($numero_documento) { $this->numero_documento = $numero_documento; }
    public function getFormaPagamento() { return $this->forma_pagamento; }
    public function setFormaPagamento($forma_pagamento) { $this->forma_pagamento = $forma_pagamento; }
    public function getDataHora() { return $this->data_hora; }
    public function setDataHora($data_hora) { $this->data_hora = $data_hora; }

    /**
     * Cria a venda, os seus itens, atualiza o estoque e regista a movimentação.
     * @param PDO $pdo A conexão com o banco de dados.
     * @param array $carrinho O array de itens do carrinho.
     * @throws Exception Se ocorrer um erro.
     */
    public function criar(PDO $pdo, array $carrinho)
    {
        try {
            $pdo->beginTransaction();

            // 1. Inserir a venda principal
            $sqlVenda = "INSERT INTO vendas (id_usuario, valor_total, numero_documento, forma_pagamento) VALUES (:id_usuario, :valor_total, :numero_documento, :forma_pagamento)";
            $stmtVenda = $pdo->prepare($sqlVenda);
            $stmtVenda->execute([
                ':id_usuario' => $this->getIdUsuario(),
                ':valor_total' => $this->getValorTotal(),
                ':numero_documento' => $this->getNumeroDocumento(),
                ':forma_pagamento' => $this->getFormaPagamento()
            ]);
            $idVendaInserida = $pdo->lastInsertId();

            // Preparar queries para o loop
            $sqlCheckEstoque = "SELECT p.nome, vp.tamanho, vp.quantidade_estoque FROM variacoes_produto vp JOIN produtos p ON vp.id_produto = p.id WHERE vp.id = :id_variacao FOR UPDATE";
            $stmtCheckEstoque = $pdo->prepare($sqlCheckEstoque);
            
            $sqlItem = "INSERT INTO itens_venda (id_venda, id_variacao, quantidade, preco_unitario_momento) VALUES (:id_venda, :id_variacao, :quantidade, :preco)";
            $stmtItem = $pdo->prepare($sqlItem);

            $sqlEstoque = "UPDATE variacoes_produto SET quantidade_estoque = quantidade_estoque - :quantidade WHERE id = :id_variacao";
            $stmtEstoque = $pdo->prepare($sqlEstoque);

            $sqlMov = "INSERT INTO movimentacao_estoque (id_variacao, tipo_movimentacao, quantidade, observacao) VALUES (:id_variacao, 'SAIDA_VENDA', :quantidade, :observacao)";
            $stmtMov = $pdo->prepare($sqlMov);

            // 2. Processar cada item do carrinho
            foreach ($carrinho as $item) {
                if (!isset($item['id_variacao'], $item['quantidade'], $item['preco_venda'])) {
                    throw new Exception('Dados de um item no carrinho estão incompletos.');
                }
                
                $stmtCheckEstoque->execute([':id_variacao' => $item['id_variacao']]);
                $variacao = $stmtCheckEstoque->fetch(PDO::FETCH_ASSOC);

                if (!$variacao || $variacao['quantidade_estoque'] < $item['quantidade']) {
                    $nomeItem = $variacao ? "{$variacao['nome']} (Tamanho: {$variacao['tamanho']})" : "Item ID {$item['id_variacao']}";
                    throw new Exception("Estoque insuficiente para o produto: " . $nomeItem);
                }

                $stmtItem->execute([
                    ':id_venda' => $idVendaInserida,
                    ':id_variacao' => $item['id_variacao'],
                    ':quantidade' => $item['quantidade'],
                    ':preco' => $item['preco_venda']
                ]);
                $stmtEstoque->execute([':quantidade' => $item['quantidade'], ':id_variacao' => $item['id_variacao']]);
                $stmtMov->execute([':id_variacao' => $item['id_variacao'], ':quantidade' => $item['quantidade'], ':observacao' => 'Venda ID: ' . $idVendaInserida]);
            }

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw new Exception("Erro ao processar a venda: " . $e->getMessage());
        }
    }

    // --- MÉTODOS ESTÁTICOS PARA RELATÓRIOS ---
    public static function listarTodas(PDO $pdo, string $periodo = 'sempre'): array
    {
        $sql = "SELECT v.id, v.data_hora, v.valor_total, v.numero_documento, u.email AS nome_usuario
                FROM vendas AS v
                JOIN usuarios AS u ON v.id_usuario = u.id";
        
        $whereClause = '';
        switch ($periodo) {
            case 'hoje': $whereClause = " WHERE DATE(v.data_hora) = CURDATE()"; break;
            case 'semana': $whereClause = " WHERE YEARWEEK(v.data_hora, 1) = YEARWEEK(CURDATE(), 1)"; break;
            case 'mes': $whereClause = " WHERE MONTH(v.data_hora) = MONTH(CURDATE()) AND YEAR(v.data_hora) = YEAR(CURDATE())"; break;
        }
        $sql .= $whereClause . " ORDER BY v.data_hora DESC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function buscarItensPorVendaId(PDO $pdo, int $idVenda): array
    {
        $sql = "SELECT p.nome AS nome_produto, vp.tamanho, iv.quantidade, iv.preco_unitario_momento
                FROM itens_venda AS iv
                JOIN variacoes_produto AS vp ON iv.id_variacao = vp.id
                JOIN produtos AS p ON vp.id_produto = p.id
                WHERE iv.id_venda = :id_venda";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id_venda' => $idVenda]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public static function gerarFechamentoDeCaixa(PDO $pdo, string $data): array
    {
        $sql = "SELECT 
                    forma_pagamento, 
                    COUNT(id) as total_vendas, 
                    SUM(valor_total) as valor_apurado
                FROM vendas
                WHERE DATE(data_hora) = :data_venda
                GROUP BY forma_pagamento";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':data_venda' => $data]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

