-- Ficheiro: loja_database_schema.sql
-- Script completo para criar a base de dados 'loja' com a estrutura final.

-- Cria a base de dados apenas se ela não existir.
CREATE DATABASE IF NOT EXISTS `loja` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Seleciona a base de dados para usar nos comandos seguintes.
USE `loja`;

-- --------------------------------------------------------
-- Tabela `usuarios`
-- --------------------------------------------------------
CREATE TABLE `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `senha` VARCHAR(255) NOT NULL
) COMMENT='Armazena os utilizadores que podem operar o sistema.';

-- --------------------------------------------------------
-- Tabela `categorias`
-- --------------------------------------------------------
CREATE TABLE `categorias` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(240) NOT NULL UNIQUE
) COMMENT='Categoriza as peças de roupa (Ex: Camisetas, Calças, Casacos).';

-- --------------------------------------------------------
-- Tabela `produtos` (O Produto "Pai")
-- --------------------------------------------------------
CREATE TABLE `produtos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(240) NOT NULL,
  `descricao` TEXT,
  `id_categoria` INT,
  `imagem1` VARCHAR(255) NULL COMMENT 'Nome do ficheiro da imagem principal',
  `imagem2` VARCHAR(255) NULL COMMENT 'Nome do ficheiro da imagem secundária',
  `imagem3` VARCHAR(255) NULL COMMENT 'Nome do ficheiro da imagem terciária',
  FOREIGN KEY (`id_categoria`) REFERENCES `categorias`(`id`) ON DELETE SET NULL
) COMMENT='Guarda as informações genéricas de um produto.';

-- --------------------------------------------------------
-- Tabela `variacoes_produto` (Os Itens Físicos/SKUs)
-- --------------------------------------------------------
CREATE TABLE `variacoes_produto` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_produto` INT NOT NULL,
  `tamanho` VARCHAR(50) NOT NULL COMMENT 'Ex: P, M, G, 40, 42, Único',
  `preco_custo` DECIMAL(10, 2),
  `preco_venda` DECIMAL(10, 2) NOT NULL,
  `quantidade_estoque` INT NOT NULL DEFAULT 0,
  FOREIGN KEY (`id_produto`) REFERENCES `produtos`(`id`) ON DELETE CASCADE
) COMMENT='Controla o preço e o estoque de cada tamanho de um produto.';

-- --------------------------------------------------------
-- Tabela `vendas` (ATUALIZADA)
-- --------------------------------------------------------
CREATE TABLE `vendas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `data_hora` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `valor_total` DECIMAL(10, 2) NOT NULL,
  `numero_documento` VARCHAR(100) NULL COMMENT 'Número manual do documento ou da transação.',
  `forma_pagamento` VARCHAR(50) NULL,
  `id_usuario` INT NOT NULL,
  FOREIGN KEY (`id_usuario`) REFERENCES `usuarios`(`id`)
) COMMENT='Registra o cabeçalho de cada transação de venda.';

-- --------------------------------------------------------
-- Tabela `itens_venda`
-- --------------------------------------------------------
CREATE TABLE `itens_venda` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_venda` INT NOT NULL,
  `id_variacao` INT NOT NULL COMMENT 'Refere-se ao tamanho específico que foi vendido.',
  `quantidade` INT NOT NULL,
  `preco_unitario_momento` DECIMAL(10, 2) NOT NULL,
  FOREIGN KEY (`id_venda`) REFERENCES `vendas`(`id`),
  FOREIGN KEY (`id_variacao`) REFERENCES `variacoes_produto`(`id`)
) COMMENT='Detalha os tamanhos/variações de cada venda.';

-- --------------------------------------------------------
-- Tabela `movimentacao_estoque`
-- --------------------------------------------------------
CREATE TABLE `movimentacao_estoque` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_variacao` INT NOT NULL COMMENT 'Refere-se ao tamanho que foi movimentado.',
  `data_hora` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tipo_movimentacao` VARCHAR(50) NOT NULL COMMENT 'ENTRADA, SAIDA_VENDA, AJUSTE',
  `quantidade` INT NOT NULL,
  `observacao` TEXT,
  FOREIGN KEY (`id_variacao`) REFERENCES `variacoes_produto`(`id`)
) COMMENT='Histórico de todas as movimentações de estoque por tamanho.';

-- --------------------------------------------------------
-- Inserir dados iniciais para teste
-- --------------------------------------------------------
INSERT INTO `usuarios` (`id`, `email`, `senha`) VALUES (1, 'admin@loja.com', '$2y$10$3g.6i/Q9.0t3jS5.6hJ.QeUqB4p/2U.w0sS3.Y4t/5p.I5eP9zUu6'); -- Senha: senha123
INSERT INTO `categorias` (`id`, `nome`) VALUES (1, 'Camisetas'), (2, 'Calças');

