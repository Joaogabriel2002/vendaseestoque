<?php
// Ficheiro: App/Models/Produtos.php
// Versão completa e correta para trabalhar com Produtos e Variações de Tamanho.

class Produto
{
    // Atributos do "Produto Pai" (a peça de roupa em si)
    private $id;
    private $nome;
    private $descricao;
    private $id_categoria;
    private $imagem1;
    private $imagem2;
    private $imagem3;

    // --- Getters e Setters para o Produto Pai ---
    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; }
    public function getNome() { return $this->nome; }
    public function setNome($nome) { $this->nome = $nome; }
    public function getDescricao() { return $this->descricao; }
    public function setDescricao($descricao) { $this->descricao = $descricao; }
    public function getIdCategoria() { return $this->id_categoria; }
    public function setIdCategoria($id_categoria) { $this->id_categoria = $id_categoria; }
    public function getImagem1() { return $this->imagem1; }
    public function setImagem1($imagem1) { $this->imagem1 = $imagem1; }
    public function getImagem2() { return $this->imagem2; }
    public function setImagem2($imagem2) { $this->imagem2 = $imagem2; }
    public function getImagem3() { return $this->imagem3; }
    public function setImagem3($imagem3) { $this->imagem3 = $imagem3; }

    /**
     * Salva o produto pai e todas as suas variações de tamanho numa única transação.
     * @param PDO $pdo A conexão com o banco de dados.
     * @param array $variacoes Os dados das variações (tamanho, preço, estoque) vindos do formulário.
     * @return int O ID do produto pai que foi criado.
     * @throws Exception Em caso de erro.
     */
    public function salvarComVariacoes(PDO $pdo, array $variacoes): int
    {
        try {
            $pdo->beginTransaction();

            // 1. Salvar o Produto Pai
            $sqlProduto = "INSERT INTO produtos (nome, descricao, id_categoria, imagem1, imagem2, imagem3) 
                           VALUES (:nome, :descricao, :id_categoria, :imagem1, :imagem2, :imagem3)";
            $stmtProduto = $pdo->prepare($sqlProduto);
            $stmtProduto->execute([
                ':nome' => $this->getNome(),
                ':descricao' => $this->getDescricao(),
                ':id_categoria' => $this->getIdCategoria(),
                ':imagem1' => $this->getImagem1(),
                ':imagem2' => $this->getImagem2(),
                ':imagem3' => $this->getImagem3()
            ]);
            $idProdutoPai = $pdo->lastInsertId();

            // 2. Preparar para inserir as variações e a movimentação
            $sqlVariacao = "INSERT INTO variacoes_produto (id_produto, tamanho, preco_custo, preco_venda, quantidade_estoque) 
                            VALUES (:id_produto, :tamanho, :preco_custo, :preco_venda, :quantidade_estoque)";
            $stmtVariacao = $pdo->prepare($sqlVariacao);

            $sqlMovimentacao = "INSERT INTO movimentacao_estoque (id_variacao, tipo_movimentacao, quantidade, observacao) 
                                VALUES (:id_variacao, 'ENTRADA', :quantidade, 'Cadastro Inicial do Produto')";
            $stmtMovimentacao = $pdo->prepare($sqlMovimentacao);

            // 3. Loop para salvar cada variação e registar a sua movimentação inicial
            foreach ($variacoes as $var) {
                $stmtVariacao->execute([
                    ':id_produto' => $idProdutoPai,
                    ':tamanho' => $var['tamanho'],
                    ':preco_custo' => !empty($var['preco_custo']) ? $var['preco_custo'] : null,
                    ':preco_venda' => $var['preco_venda'],
                    ':quantidade_estoque' => $var['quantidade_estoque']
                ]);
                $idVariacaoInserida = $pdo->lastInsertId();

                if ($var['quantidade_estoque'] > 0) {
                    $stmtMovimentacao->execute([
                        ':id_variacao' => $idVariacaoInserida,
                        ':quantidade' => $var['quantidade_estoque']
                    ]);
                }
            }

            $pdo->commit();
            return $idProdutoPai;

        } catch (Exception $e) {
            $pdo->rollBack();
            throw new Exception("Erro ao salvar o produto e suas variações: " . $e->getMessage());
        }
    }
    
    /**
     * Atualiza o produto pai e sincroniza as suas variações (adiciona, atualiza, remove).
     */
    public function atualizarComVariacoes(PDO $pdo, array $variacoes)
    {
        try {
            $pdo->beginTransaction();

            $sqlProduto = "UPDATE produtos SET nome = :nome, descricao = :descricao, id_categoria = :id_categoria, imagem1 = :imagem1, imagem2 = :imagem2, imagem3 = :imagem3 WHERE id = :id";
            $stmtProduto = $pdo->prepare($sqlProduto);
            $stmtProduto->execute([
                ':nome' => $this->getNome(), ':descricao' => $this->getDescricao(), ':id_categoria' => $this->getIdCategoria(),
                ':imagem1' => $this->getImagem1(), ':imagem2' => $this->getImagem2(), ':imagem3' => $this->getImagem3(), ':id' => $this->getId()
            ]);

            $idsVariacoesDoFormulario = array_filter(array_column($variacoes, 'id'));
            
            if (!empty($idsVariacoesDoFormulario)) {
                $placeholders = implode(',', array_fill(0, count($idsVariacoesDoFormulario), '?'));
                $sqlDelete = "DELETE FROM variacoes_produto WHERE id_produto = ? AND id NOT IN ($placeholders)";
                $stmtDelete = $pdo->prepare($sqlDelete);
                $params = array_merge([$this->getId()], $idsVariacoesDoFormulario);
                $stmtDelete->execute($params);
            } else {
                $sqlDelete = "DELETE FROM variacoes_produto WHERE id_produto = ?";
                $stmtDelete = $pdo->prepare($sqlDelete);
                $stmtDelete->execute([$this->getId()]);
            }

            $sqlInsert = "INSERT INTO variacoes_produto (id_produto, tamanho, preco_custo, preco_venda, quantidade_estoque) VALUES (?, ?, ?, ?, ?)";
            $stmtInsert = $pdo->prepare($sqlInsert);
            $sqlUpdate = "UPDATE variacoes_produto SET tamanho = ?, preco_custo = ?, preco_venda = ?, quantidade_estoque = ? WHERE id = ?";
            $stmtUpdate = $pdo->prepare($sqlUpdate);

            foreach ($variacoes as $var) {
                $precoCusto = !empty($var['preco_custo']) ? $var['preco_custo'] : null;
                if (empty($var['id'])) {
                    $stmtInsert->execute([$this->getId(), $var['tamanho'], $precoCusto, $var['preco_venda'], $var['quantidade_estoque']]);
                } else {
                    $stmtUpdate->execute([$var['tamanho'], $precoCusto, $var['preco_venda'], $var['quantidade_estoque'], $var['id']]);
                }
            }

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw new Exception("Erro ao atualizar o produto: " . $e->getMessage());
        }
    }

    // --- MÉTODOS ESTÁTICOS ---

    /**
     * Lista todos os produtos PAI, agregando o estoque e o preço mínimo.
     */
    public static function listarTodos(PDO $pdo): array
    {
        $sql = "SELECT p.*, c.nome AS nome_categoria, SUM(vp.quantidade_estoque) AS estoque_total, MIN(vp.preco_venda) AS preco_minimo
                FROM produtos AS p
                LEFT JOIN categorias AS c ON p.id_categoria = c.id
                LEFT JOIN variacoes_produto AS vp ON p.id = vp.id_produto
                GROUP BY p.id
                ORDER BY p.nome ASC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lista CADA VARIAÇÃO como uma entrada individual.
     */
    public static function listarTodasVariacoes(PDO $pdo): array
    {
        $sql = "SELECT
                    vp.id AS id_variacao,
                    vp.tamanho,
                    vp.preco_venda,
                    vp.quantidade_estoque,
                    p.id AS id_produto,
                    p.nome AS nome_produto,
                    p.imagem1
                FROM variacoes_produto AS vp
                JOIN produtos AS p ON vp.id_produto = p.id
                ORDER BY p.nome, vp.tamanho ASC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca um produto "pai" pelo ID e carrega todas as suas variações associadas.
     */
    public static function findById(PDO $pdo, int $id): ?array
    {
        $produto = [];
        $sqlProduto = "SELECT * FROM produtos WHERE id = :id";
        $stmtProduto = $pdo->prepare($sqlProduto);
        $stmtProduto->execute([':id' => $id]);
        $produto['info'] = $stmtProduto->fetch(PDO::FETCH_ASSOC);

        if (!$produto['info']) {
            return null;
        }

        $sqlVariacoes = "SELECT * FROM variacoes_produto WHERE id_produto = :id_produto ORDER BY id ASC";
        $stmtVariacoes = $pdo->prepare($sqlVariacoes);
        $stmtVariacoes->execute([':id_produto' => $id]);
        $produto['variacoes'] = $stmtVariacoes->fetchAll(PDO::FETCH_ASSOC);

        return $produto;
    }
    
    /**
     * Busca produtos por um termo para a Frente de Caixa.
     */
    public static function buscarPorTermo(PDO $pdo, string $termo): array
    {
        $termoBusca = '%' . $termo . '%';
        $sqlProdutos = "SELECT id, nome FROM produtos WHERE nome LIKE :termo LIMIT 10";
        $stmtProdutos = $pdo->prepare($sqlProdutos);
        $stmtProdutos->execute([':termo' => $termoBusca]);
        $produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);

        if (empty($produtos)) return [];

        $idsProdutos = array_column($produtos, 'id');
        $placeholders = implode(',', array_fill(0, count($idsProdutos), '?'));

        $sqlVariacoes = "SELECT id, id_produto, tamanho, preco_venda, quantidade_estoque 
                         FROM variacoes_produto 
                         WHERE id_produto IN ($placeholders) AND quantidade_estoque > 0";
        $stmtVariacoes = $pdo->prepare($sqlVariacoes);
        $stmtVariacoes->execute($idsProdutos);
        $variacoes = $stmtVariacoes->fetchAll(PDO::FETCH_ASSOC);

        $produtosComVariacoes = [];
        foreach ($produtos as $produto) {
            $produto['variacoes'] = array_values(array_filter($variacoes, fn($v) => $v['id_produto'] == $produto['id']));
            if (!empty($produto['variacoes'])) {
                $produtosComVariacoes[] = $produto;
            }
        }
        return $produtosComVariacoes;
    }

    /**
     * Adiciona estoque a uma VARIAÇÃO específica de produto.
     */
    public static function adicionarEstoque(PDO $pdo, int $id_variacao, int $quantidade, string $observacao)
    {
        if ($quantidade <= 0) {
            throw new Exception("A quantidade deve ser positiva.");
        }
        try {
            $pdo->beginTransaction();

            $sqlEstoque = "UPDATE variacoes_produto SET quantidade_estoque = quantidade_estoque + :quantidade WHERE id = :id_variacao";
            $stmtEstoque = $pdo->prepare($sqlEstoque);
            $stmtEstoque->execute([':quantidade' => $quantidade, ':id_variacao' => $id_variacao]);

            $sqlMov = "INSERT INTO movimentacao_estoque (id_variacao, tipo_movimentacao, quantidade, observacao) 
                       VALUES (:id_variacao, 'ENTRADA', :quantidade, :observacao)";
            $stmtMov = $pdo->prepare($sqlMov);
            $stmtMov->execute([':id_variacao' => $id_variacao, ':quantidade' => $quantidade, ':observacao' => $observacao]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw new Exception("Erro ao adicionar estoque: " . $e->getMessage());
        }
    }
    
    /**
     * Lista apenas as variações de um produto específico.
     */
    public static function listarVariacoesPorProdutoId(PDO $pdo, int $id_produto): array
    {
        $sql = "SELECT id, tamanho, quantidade_estoque FROM variacoes_produto WHERE id_produto = :id_produto ORDER BY id ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id_produto' => $id_produto]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

