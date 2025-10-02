<?php
// Ficheiro: App/Models/Venda.php

class Venda
{
    private $id;
    private $id_usuario;
    private $valor_total;
    private $numero_documento; // Novo atributo
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
    public function getDataHora() { return $this->data_hora; }
    public function setDataHora($data_hora) { $this->data_hora = $data_hora; }

    /**
     * Cria a venda, incluindo o número do documento, e atualiza o estoque das variações.
     */
    public function criar(PDO $pdo, array $carrinho)
    {
        try {
            $pdo->beginTransaction();

            // 1. Inserir a venda principal com o novo campo
            $sqlVenda = "INSERT INTO vendas (id_usuario, valor_total, numero_documento) 
                         VALUES (:id_usuario, :valor_total, :numero_documento)";
            $stmtVenda = $pdo->prepare($sqlVenda);
            $stmtVenda->execute([
                ':id_usuario' => $this->getIdUsuario(),
                ':valor_total' => $this->getValorTotal(),
                ':numero_documento' => $this->getNumeroDocumento()
            ]);
            $idVendaInserida = $pdo->lastInsertId();

            // Preparar queries para o loop
            $sqlItem = "INSERT INTO itens_venda (id_venda, id_variacao, quantidade, preco_unitario_momento) 
                        VALUES (:id_venda, :id_variacao, :quantidade, :preco)";
            $stmtItem = $pdo->prepare($sqlItem);

            $sqlEstoque = "UPDATE variacoes_produto SET quantidade_estoque = quantidade_estoque - :quantidade 
                           WHERE id = :id_variacao";
            $stmtEstoque = $pdo->prepare($sqlEstoque);

            $sqlMov = "INSERT INTO movimentacao_estoque (id_variacao, tipo_movimentacao, quantidade, observacao) 
                       VALUES (:id_variacao, 'SAIDA', :quantidade, :observacao)";
            $stmtMov = $pdo->prepare($sqlMov);

            // 2. Processar cada item do carrinho
            foreach ($carrinho as $item) {
                if (!isset($item['id_variacao'], $item['quantidade'], $item['preco_venda'])) {
                    throw new Exception('Dados de um item no carrinho estão incompletos.');
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
        } catch (Exception $e) {
            $pdo->rollBack();
            throw new Exception("Erro ao processar a venda: " . $e->getMessage());
        }
    }

    // --- Outros Métodos Estáticos ---
}

