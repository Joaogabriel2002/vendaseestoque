<?php
session_start();

// Protege a página: se o utilizador não estiver logado, redireciona.
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Frente de Caixa (PDV)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">

    <div class="flex h-screen">
        <!-- Área Principal da Venda -->
        <main class="w-2/3 p-6 flex flex-col">
            <header class="mb-6 flex justify-between items-center">
                <div><h1 class="text-3xl font-bold text-gray-800">Frente de Caixa</h1></div>
                <a href="../dashboard.php" class="bg-white text-gray-700 px-4 py-2 rounded-lg shadow hover:bg-gray-50 transition-colors"><i class="fas fa-arrow-left mr-2"></i> Voltar</a>
            </header>

            <div class="relative mb-6">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" id="searchProduto" placeholder="Digite o nome do produto..." class="w-full pl-12 pr-4 py-3 border rounded-lg text-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <div id="searchResults" class="absolute z-20 w-full bg-white border rounded-lg mt-1 shadow-lg hidden max-h-60 overflow-y-auto"></div>
            </div>

            <div class="flex-grow bg-white rounded-lg shadow overflow-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/2">Produto</th>
                            <th class="py-3 px-6 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Qtd.</th>
                            <th class="py-3 px-6 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Preço Unit.</th>
                            <th class="py-3 px-6 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                            <th class="py-3 px-6 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Ação</th>
                        </tr>
                    </thead>
                    <tbody id="cartItems">
                        <tr id="emptyCartMessage"><td colspan="5" class="text-center py-10 text-gray-500"><i class="fas fa-shopping-cart fa-2x mb-2"></i><p>O carrinho está vazio</p></td></tr>
                    </tbody>
                </table>
            </div>
        </main>

        <!-- Barra Lateral do Resumo -->
        <aside class="w-1/3 bg-white p-6 shadow-lg flex flex-col">
            <h2 class="text-2xl font-bold text-gray-800 border-b pb-4 mb-6">Resumo da Venda</h2>
            <div class="flex-grow space-y-4 text-lg">
                <div class="flex justify-between"><span class="text-gray-600">Subtotal</span><span id="subtotal" class="font-semibold">R$ 0,00</span></div>
                
                <div class="border-t pt-4">
                    <label for="numero_documento" class="block text-sm font-medium text-gray-700">Nº do Documento (Opcional)</label>
                    <input type="text" id="numero_documento" class="mt-1 w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
                 <div>
                    <label for="forma_pagamento" class="block text-sm font-medium text-gray-700">Forma de Pagamento</label>
                    <select id="forma_pagamento" class="mt-1 w-full px-3 py-2 border rounded-md bg-white focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option>Dinheiro</option>
                        <option>Cartão de Crédito</option>
                        <option>Cartão de Débito</option>
                        <option>PIX</option>
                    </select>
                </div>
            </div>
            <div class="border-t pt-4">
                <div class="flex justify-between text-3xl font-bold text-gray-900 mb-6">
                    <span>Total</span>
                    <span id="total">R$ 0,00</span>
                </div>
                <button id="finalizeVendaBtn" class="w-full bg-green-600 text-white font-bold py-4 rounded-lg text-lg hover:bg-green-700 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed">
                    <i class="fas fa-check-circle mr-2"></i> Finalizar Venda
                </button>
            </div>
        </aside>
    </div>

    <!-- Modal de Seleção de Variação -->
    <div id="variationModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden"></div>
    
    <!-- Modal de Feedback -->
    <div id="feedbackModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden"></div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchProduto');
        const searchResults = document.getElementById('searchResults');
        const cartItems = document.getElementById('cartItems');
        const emptyCartMessage = document.getElementById('emptyCartMessage');
        const finalizeVendaBtn = document.getElementById('finalizeVendaBtn');
        const variationModal = document.getElementById('variationModal');
        const feedbackModal = document.getElementById('feedbackModal');

        let cart = [];
        let productsCache = {};

        searchInput.addEventListener('keyup', async (e) => {
            const term = e.target.value;
            if (term.length < 2) {
                searchResults.classList.add('hidden');
                return;
            }
            try {
                const response = await fetch(`../../App/Ajax/buscar_produtos.php?term=${encodeURIComponent(term)}`);
                if (!response.ok) throw new Error('Erro na rede.');
                
                const products = await response.json();
                productsCache = {};
                searchResults.innerHTML = '';
                
                if (products.length > 0) {
                    products.forEach(product => {
                        productsCache[product.id] = product;
                        const div = document.createElement('div');
                        div.className = 'p-4 hover:bg-gray-100 cursor-pointer border-b';
                        div.innerHTML = `<p class="font-semibold">${product.nome}</p>`;
                        div.addEventListener('click', () => openVariationModal(product.id));
                        searchResults.appendChild(div);
                    });
                    searchResults.classList.remove('hidden');
                } else {
                    searchResults.classList.add('hidden');
                }
            } catch (error) {
                console.error('Erro ao buscar produtos:', error);
            }
        });

        function openVariationModal(productId) {
            const product = productsCache[productId];
            if (!product || !product.variacoes || product.variacoes.length === 0) {
                showFeedbackModal('error', 'Erro', 'Produto sem variações disponíveis.');
                return;
            }

            let variationsHtml = product.variacoes.map(v => `
                <button type="button" data-variation-id="${v.id}" class="variation-btn w-full text-left p-3 rounded-lg hover:bg-gray-100 flex justify-between items-center ${v.quantidade_estoque <= 0 ? 'opacity-50 cursor-not-allowed' : ''}" ${v.quantidade_estoque <= 0 ? 'disabled' : ''}>
                    <span class="font-semibold">Tamanho: ${v.tamanho}</span>
                    <span class="text-sm">Estoque: ${v.quantidade_estoque} | R$ ${parseFloat(v.preco_venda).toFixed(2)}</span>
                </button>
            `).join('');

            variationModal.innerHTML = `
                <div class="bg-white rounded-lg p-6 shadow-xl max-w-lg w-full">
                    <div class="flex justify-between items-center border-b pb-3 mb-4">
                        <h3 class="text-2xl font-bold">Selecionar Tamanho para ${product.nome}</h3>
                        <button onclick="closeVariationModal()" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
                    </div>
                    <div class="space-y-2 max-h-60 overflow-y-auto">${variationsHtml}</div>
                </div>`;
            
            variationModal.querySelectorAll('.variation-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const variationId = btn.dataset.variationId;
                    const selectedVariation = product.variacoes.find(v => v.id == variationId);
                    addProductToCart(product, selectedVariation);
                });
            });

            variationModal.classList.remove('hidden');
        }

        window.closeVariationModal = () => variationModal.classList.add('hidden');

        function addProductToCart(product, variation) {
            closeVariationModal();
            searchInput.value = '';
            searchResults.classList.add('hidden');

            const existingItem = cart.find(item => item.id_variacao === variation.id);
            if (existingItem) {
                if (existingItem.quantidade < variation.quantidade_estoque) {
                    existingItem.quantidade++;
                } else {
                    showFeedbackModal('error', 'Estoque', 'Quantidade máxima em estoque atingida.');
                }
            } else {
                cart.push({ 
                    id_produto: product.id,
                    id_variacao: variation.id,
                    nome: `${product.nome} (Tamanho: ${variation.tamanho})`,
                    quantidade: 1,
                    preco_venda: variation.preco_venda,
                    estoque_disponivel: variation.quantidade_estoque
                });
            }
            renderCart();
        }
        
        function renderCart() {
            if (cart.length === 0) {
                cartItems.innerHTML = '';
                cartItems.appendChild(emptyCartMessage);
            } else {
                cartItems.innerHTML = cart.map((item, index) => {
                    const subtotal = item.quantidade * item.preco_venda;
                    return `
                        <tr class="border-b">
                            <td class="py-4 px-6">${item.nome}</td>
                            <td class="py-4 px-6 text-center">
                                <div class="flex items-center justify-center">
                                    <button onclick="updateQuantity(${index}, -1)" class="px-2 py-1 bg-gray-200 rounded-l">-</button>
                                    <span class="w-16 text-center font-semibold">${item.quantidade}</span>
                                    <button onclick="updateQuantity(${index}, 1)" class="px-2 py-1 bg-gray-200 rounded-r">+</button>
                                </div>
                            </td>
                            <td class="py-4 px-6 text-right cursor-pointer hover:bg-yellow-100" onclick="editPrice(${index})">
                                R$ ${parseFloat(item.preco_venda).toFixed(2).replace('.', ',')}
                                <i class="fas fa-pencil-alt text-xs text-gray-400 ml-1"></i>
                            </td>
                            <td class="py-4 px-6 text-right font-semibold">R$ ${subtotal.toFixed(2).replace('.', ',')}</td>
                            <td class="py-4 px-6 text-center">
                                <button onclick="removeFromCart(${index})" class="text-red-500 hover:text-red-700"><i class="fas fa-trash-alt"></i></button>
                            </td>
                        </tr>`;
                }).join('');
            }
            updateSummary();
        }

        function updateSummary() {
            const total = cart.reduce((sum, item) => sum + (item.quantidade * item.preco_venda), 0);
            document.getElementById('subtotal').innerText = `R$ ${total.toFixed(2).replace('.', ',')}`;
            document.getElementById('total').innerText = `R$ ${total.toFixed(2).replace('.', ',')}`;
            finalizeVendaBtn.disabled = cart.length === 0;
        }

        window.editPrice = (index) => {
            const item = cart[index];
            const currentPrice = parseFloat(item.preco_venda).toFixed(2);
            const newPriceStr = prompt(`Alterar preço para "${item.nome}":`, currentPrice);

            if (newPriceStr !== null) {
                const newPrice = parseFloat(newPriceStr.replace(',', '.'));
                if (!isNaN(newPrice) && newPrice >= 0) {
                    item.preco_venda = newPrice;
                    renderCart();
                } else {
                    alert('Valor inválido. Por favor, insira um número.');
                }
            }
        };

        window.updateQuantity = (index, change) => {
            const item = cart[index];
            const newQuantity = item.quantidade + change;
            if (newQuantity > 0 && newQuantity <= item.estoque_disponivel) {
                item.quantidade = newQuantity;
            } else if (newQuantity > item.estoque_disponivel) {
                showFeedbackModal('error', 'Estoque', 'Quantidade máxima em estoque atingida.');
            } else if (newQuantity <= 0) {
                removeFromCart(index);
            }
            renderCart();
        };

        window.removeFromCart = (index) => {
            cart.splice(index, 1);
            renderCart();
        };

        finalizeVendaBtn.addEventListener('click', async () => {
            if (cart.length === 0) return;
            
            const total = cart.reduce((sum, item) => sum + (item.quantidade * item.preco_venda), 0);
            const numeroDocumento = document.getElementById('numero_documento').value;
            const formaPagamento = document.getElementById('forma_pagamento').value;
            
            const dataToSend = {
                carrinho: cart.map(item => ({ 
                    id_produto: item.id_produto,
                    id_variacao: item.id_variacao, 
                    quantidade: item.quantidade,
                    preco_venda: item.preco_venda
                })),
                total: total,
                numero_documento: numeroDocumento,
                forma_pagamento: formaPagamento
            };

            try {
                const response = await fetch('../../App/Controller/finalizar_venda.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(dataToSend)
                });
                const result = await response.json();
                
                if (result.sucesso) {
                    showFeedbackModal('success', 'Sucesso!', result.mensagem);
                    cart = [];
                    renderCart();
                    document.getElementById('numero_documento').value = '';
                } else {
                    showFeedbackModal('error', 'Erro!', result.mensagem);
                }
            } catch (error) {
                console.error('Erro ao finalizar venda:', error);
                showFeedbackModal('error', 'Erro!', 'Não foi possível comunicar com o servidor.');
            }
        });

        function showFeedbackModal(type, title, message) {
            feedbackModal.innerHTML = `
                <div class="bg-white rounded-lg p-8 shadow-xl text-center max-w-sm">
                    <div><i class="fas ${type === 'success' ? 'fa-check-circle text-green-500' : 'fa-times-circle text-red-500'} fa-3x"></i></div>
                    <h3 class="text-2xl font-bold mt-4">${title}</h3>
                    <p class="text-gray-600 mt-2">${message}</p>
                    <button onclick="closeModal()" class="mt-6 bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">OK</button>
                </div>`;
            feedbackModal.classList.remove('hidden');
        }

        window.closeModal = () => feedbackModal.classList.add('hidden');
    });
    </script>
</body>
</html>

