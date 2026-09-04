(function () {
  'use strict';

  if (!window.dtSupabase) return;

  const productsDiv = document.getElementById('products');
  const productModal = document.getElementById('productModal');
  const checkoutModal = document.getElementById('checkoutModal');
  const cartItems = document.getElementById('cartItems');
  const cartBadge = document.getElementById('cartBadge');
  const checkoutCartItems = document.getElementById('checkoutCartItems');
  const checkoutTotal = document.getElementById('checkoutTotal');
  const config = window.DT_APPARELS || {};

  let cart = JSON.parse(localStorage.getItem('cart') || '[]').map((item) => {
    if (item.image && typeof normalizeStorageUrl === 'function') {
      item.image = normalizeStorageUrl(item.image);
    }
    return item;
  });
  let currentProduct = null;
  let selectedCategory = 'All';
  let searchQuery = '';
  let sortBy = 'newest';
  let priceFilter = 'all';
  let currentPage = 1;
  const productsPerPage = 12;
  let allProducts = [];
  let filteredProducts = [];

  updateCartDisplay();

  window.toggleCart = function toggleCart() {
    document.getElementById('cartMenu')?.classList.toggle('hidden');
  };

  document.addEventListener('click', (e) => {
    const menu = document.getElementById('cartMenu');
    const toggle = document.getElementById('cartToggle');
    if (menu && !menu.classList.contains('hidden') && !menu.contains(e.target) && !toggle?.contains(e.target)) {
      menu.classList.add('hidden');
    }
  });

  window.filterByCategory = function filterByCategory(category) {
    selectedCategory = category;
    currentPage = 1;
    document.querySelectorAll('[data-filter]').forEach((btn) => {
      btn.classList.toggle('filter-pill-active', btn.dataset.filter === category);
    });
    applyFilters();
  };

  window.handleSearch = function handleSearch() {
    searchQuery = document.getElementById('searchInput').value.toLowerCase().trim();
    document.getElementById('clearSearchBtn').classList.toggle('hidden', !searchQuery);
    currentPage = 1;
    applyFilters();
  };

  window.clearSearch = function clearSearch() {
    document.getElementById('searchInput').value = '';
    searchQuery = '';
    document.getElementById('clearSearchBtn').classList.add('hidden');
    currentPage = 1;
    applyFilters();
  };

  window.applyFilters = function applyFilters() {
    sortBy = document.getElementById('sortSelect').value;
    priceFilter = document.getElementById('priceFilter').value;

    filteredProducts = allProducts.filter((product) => {
      if (product.sold_out || product.quantity <= 0) return false;
      const categoryMatch = selectedCategory === 'All' || product.category === selectedCategory;
      const searchMatch = !searchQuery ||
        product.name.toLowerCase().includes(searchQuery) ||
        (product.description && product.description.toLowerCase().includes(searchQuery));

      let priceMatch = true;
      const price = Number(product.price) || 0;
      if (priceFilter === '0-100') priceMatch = price <= 100;
      else if (priceFilter === '100-500') priceMatch = price > 100 && price <= 500;
      else if (priceFilter === '500-1000') priceMatch = price > 500 && price <= 1000;
      else if (priceFilter === '1000+') priceMatch = price > 1000;

      return categoryMatch && searchMatch && priceMatch;
    });

    filteredProducts.sort((a, b) => {
      switch (sortBy) {
        case 'oldest': return new Date(a.created_at) - new Date(b.created_at);
        case 'price-low': return (a.price || 0) - (b.price || 0);
        case 'price-high': return (b.price || 0) - (a.price || 0);
        case 'name-asc': return (a.name || '').localeCompare(b.name || '');
        case 'name-desc': return (b.name || '').localeCompare(a.name || '');
        default: return new Date(b.created_at) - new Date(a.created_at);
      }
    });

    updateProductsCount();
    renderPaginatedProducts();
  };

  function renderPaginatedProducts() {
    if (!productsDiv) return;
    productsDiv.innerHTML = '';

    if (filteredProducts.length === 0) {
      productsDiv.innerHTML = '<p class="col-span-full py-16 text-center text-muted">No products found matching your criteria.</p>';
      document.getElementById('pagination').innerHTML = '';
      return;
    }

    const totalPages = Math.ceil(filteredProducts.length / productsPerPage);
    const start = (currentPage - 1) * productsPerPage;
    const pageProducts = filteredProducts.slice(start, start + productsPerPage);

    productsDiv.innerHTML = pageProducts.map((p) => `
      <article class="product-card">
        <img src="${escapeHtml(typeof normalizeStorageUrl === 'function' ? normalizeStorageUrl(p.image) : p.image)}" alt="${escapeHtml(p.name)}" class="product-card-image" loading="lazy">
        <div class="product-card-body">
          <span class="product-category-badge">${escapeHtml(p.category)}</span>
          <h3 class="font-semibold line-clamp-2">${escapeHtml(p.name)}</h3>
          <p class="text-xs text-muted">${p.quantity} available</p>
          <p class="mt-auto pt-2 text-xl font-bold">₵${Number(p.price).toFixed(2)}</p>
          <button type="button" class="btn-primary mt-4 w-full buy-btn" data-id="${p.id}"><i class="fas fa-shopping-bag"></i> Buy Now</button>
        </div>
      </article>
    `).join('');

    productsDiv.querySelectorAll('.buy-btn').forEach((btn) => {
      btn.addEventListener('click', () => {
        const product = allProducts.find((p) => p.id === btn.dataset.id);
        if (product) openProductModal(product);
      });
    });

    renderPagination(totalPages);
  }

  function renderPagination(totalPages) {
    const paginationDiv = document.getElementById('pagination');
    if (totalPages <= 1) { paginationDiv.innerHTML = ''; return; }

    let html = `<button type="button" onclick="goToPage(${currentPage - 1})" class="filter-pill" ${currentPage === 1 ? 'disabled' : ''}><i class="fas fa-chevron-left"></i></button>`;
    for (let i = 1; i <= totalPages; i++) {
      html += `<button type="button" onclick="goToPage(${i})" class="filter-pill ${i === currentPage ? 'filter-pill-active' : ''}">${i}</button>`;
    }
    html += `<button type="button" onclick="goToPage(${currentPage + 1})" class="filter-pill" ${currentPage === totalPages ? 'disabled' : ''}><i class="fas fa-chevron-right"></i></button>`;
    paginationDiv.innerHTML = html;
  }

  window.goToPage = function goToPage(page) {
    const totalPages = Math.ceil(filteredProducts.length / productsPerPage);
    if (page >= 1 && page <= totalPages) {
      currentPage = page;
      renderPaginatedProducts();
      document.getElementById('products').scrollIntoView({ behavior: 'smooth' });
    }
  };

  function updateProductsCount() {
    const start = filteredProducts.length ? (currentPage - 1) * productsPerPage + 1 : 0;
    const end = Math.min(currentPage * productsPerPage, filteredProducts.length);
    document.getElementById('showingCount').textContent = filteredProducts.length ? `${start}-${end}` : '0';
    document.getElementById('totalCount').textContent = filteredProducts.length;
  }

  window.clearAllFilters = function clearAllFilters() {
    clearSearch();
    filterByCategory('All');
    document.getElementById('sortSelect').value = 'newest';
    document.getElementById('priceFilter').value = 'all';
  };

  function openProductModal(product) {
    currentProduct = product;
    document.getElementById('modalImg').src = typeof normalizeStorageUrl === 'function' ? normalizeStorageUrl(product.image) : product.image;
    document.getElementById('modalTitle').textContent = product.name;
    document.getElementById('modalCategory').textContent = product.category;
    document.getElementById('modalPrice').textContent = '₵' + Number(product.price).toFixed(2);
    document.getElementById('modalDescription').textContent = (product.description || 'No description available.') + ` (${product.quantity} in stock)`;
    productModal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  window.addToCartFromModal = function addToCartFromModal() {
    if (currentProduct) {
      addToCart(currentProduct);
      closeProductModal();
    }
  };

  window.closeProductModal = function closeProductModal() {
    productModal.classList.remove('active');
    document.body.style.overflow = '';
  };

  function addToCart(product) {
    const live = allProducts.find((p) => p.id === product.id);
    const available = live ? live.quantity : (product.quantity ?? 0);
    if (available <= 0 || live?.sold_out) {
      showToast('This item is sold out.', 'error');
      return;
    }

    const existing = cart.find((item) => item.id === product.id);
    const inCart = existing ? existing.quantity : 0;
    if (inCart + 1 > available) {
      showToast(`Only ${available} item(s) available.`, 'error');
      return;
    }

    if (existing) existing.quantity += 1;
    else cart.push({
      id: product.id,
      name: product.name,
      price: Number(product.price),
      image: typeof normalizeStorageUrl === 'function' ? normalizeStorageUrl(product.image) : product.image,
      category: product.category,
      quantity: 1,
      maxQuantity: available,
    });
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartDisplay();
    showToast('Product added to cart!');
  }

  window.removeFromCart = function removeFromCart(productId) {
    cart = cart.filter((item) => item.id !== productId);
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartDisplay();
    updateCheckoutDisplay();
  };

  window.updateQuantity = function updateQuantity(productId, change) {
    const item = cart.find((i) => i.id === productId);
    if (!item) return;
    const live = allProducts.find((p) => p.id === productId);
    const maxQty = live ? live.quantity : (item.maxQuantity || item.quantity);

    if (change > 0 && item.quantity >= maxQty) {
      showToast(`Only ${maxQty} item(s) available.`, 'error');
      return;
    }

    item.quantity += change;
    if (item.quantity <= 0) removeFromCart(productId);
    else {
      localStorage.setItem('cart', JSON.stringify(cart));
      updateCartDisplay();
      updateCheckoutDisplay();
    }
  };

  function updateCartDisplay() {
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    if (cartBadge) cartBadge.textContent = totalItems;
    if (!cartItems) return;

    if (cart.length === 0) {
      cartItems.innerHTML = '<p class="p-6 text-center text-muted">Your cart is empty</p>';
      return;
    }

    cartItems.innerHTML = cart.map((item) => `
      <div class="flex items-center gap-3 border-b border-gray-100 p-4 dark:border-gray-800">
        <img src="${escapeHtml(typeof normalizeStorageUrl === 'function' ? normalizeStorageUrl(item.image) : item.image)}" alt="" class="h-14 w-14 rounded-lg object-cover">
        <div class="flex-1 min-w-0">
          <p class="truncate text-sm font-medium">${escapeHtml(item.name)}</p>
          <p class="text-sm font-bold">₵${item.price} × ${item.quantity}</p>
        </div>
        <div class="flex items-center gap-1">
          <button type="button" onclick="updateQuantity('${item.id}', -1)" class="icon-btn text-sm">-</button>
          <span class="w-6 text-center text-sm">${item.quantity}</span>
          <button type="button" onclick="updateQuantity('${item.id}', 1)" class="icon-btn text-sm">+</button>
        </div>
        <button type="button" onclick="removeFromCart('${item.id}')" class="text-red-500"><i class="fas fa-times"></i></button>
      </div>
    `).join('');
  }

  window.openCheckout = function openCheckout() {
    if (cart.length === 0) { showToast('Your cart is empty!', 'error'); return; }
    updateCheckoutDisplay();
    checkoutModal.classList.add('active');
    document.body.style.overflow = 'hidden';
    document.getElementById('cartMenu')?.classList.add('hidden');
  };

  window.closeCheckoutModal = function closeCheckoutModal() {
    checkoutModal.classList.remove('active');
    document.body.style.overflow = '';
  };

  function updateCheckoutDisplay() {
    const total = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
    checkoutTotal.textContent = '₵' + total.toFixed(2);
    checkoutCartItems.innerHTML = cart.map((item) => `
      <div class="flex justify-between py-2 text-sm">
        <span>${escapeHtml(item.name)} × ${item.quantity}</span>
        <span class="font-bold">₵${(item.price * item.quantity).toFixed(2)}</span>
      </div>
    `).join('');
  }

  document.getElementById('checkoutForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (cart.length === 0) { showToast('Your cart is empty!', 'error'); return; }

    const csrfToken = document.querySelector('#checkoutForm input[name="csrf_token"]')?.value;
    const orderData = {
      user_id: (config.userId && typeof config.userId === 'string' && config.userId.length > 20) ? config.userId : null,
      customer_name: document.getElementById('customerName').value.trim().slice(0, 100),
      email: document.getElementById('customerEmail').value.trim().slice(0, 150),
      phone: document.getElementById('customerPhone').value.trim().slice(0, 20),
      region: document.getElementById('customerRegion').value,
      town: document.getElementById('customerTown').value.trim().slice(0, 100),
      address: document.getElementById('customerAddress').value.trim().slice(0, 300),
      country: 'Ghana',
      items: cart,
      total: cart.reduce((sum, item) => sum + item.price * item.quantity, 0),
      status: 'pending',
    };

    if (!orderData.customer_name || !orderData.email || !orderData.phone || !orderData.region || !orderData.town || !orderData.address) {
      showToast('Please fill in all required fields.', 'error');
      return;
    }

    for (const item of cart) {
      const live = allProducts.find((p) => p.id === item.id);
      if (!live || live.sold_out || live.quantity < item.quantity) {
        showToast(`${item.name} no longer has enough stock.`, 'error');
        return;
      }
    }

    try {
      const endpoint = typeof resolveAppUrl === 'function' ? resolveAppUrl('/api/place-order.php') : ((config.baseUrl || '') + '/api/place-order.php');
      const response = await fetch(endpoint, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken || config.csrfToken || '',
        },
        body: JSON.stringify({
          csrf_token: csrfToken || config.csrfToken || '',
          customer_name: orderData.customer_name,
          email: orderData.email,
          phone: orderData.phone,
          region: orderData.region,
          town: orderData.town,
          address: orderData.address,
          items: cart,
        }),
      });

      const resText = await response.text();
      let resData = {};
      try {
        resData = JSON.parse(resText);
      } catch (parseErr) {
        throw new Error('Server error: ' + resText.replace(/<[^>]*>?/gm, '').trim().substring(0, 150));
      }

      if (!response.ok || !resData.success) {
        throw new Error(resData.error || 'Error placing order.');
      }

      if (resData.whatsapp_url) {
        window.open(resData.whatsapp_url, '_blank');
      }

      cart = [];
      localStorage.setItem('cart', JSON.stringify(cart));
      updateCartDisplay();
      closeCheckoutModal();
      showToast('Order placed successfully! ID: ' + (resData.order_id || '').substring(0, 8), 'success');
      document.getElementById('checkoutForm').reset();
      await loadProducts();
    } catch (err) {
      showToast(err.message || 'Error placing order.', 'error');
    }
  });

  function sendWhatsAppOrder(orderData, orderId) {
    const rawNumber = config.whatsappNumber || '233544570238';
    const whatsappNumber = rawNumber.replace(/[^0-9]/g, '');
    let message = `📦 *New Order - D&T Apparels*\n\n*Order ID:* ${orderId}\n\n*Customer:*\n`;
    message += `Name: ${orderData.customer_name}\nEmail: ${orderData.email}\nPhone: ${orderData.phone}\n`;
    message += `Region: ${orderData.region}\nTown: ${orderData.town}\nAddress: ${orderData.address}\n\n*Items:*\n`;
    orderData.items.forEach((item, i) => {
      message += `${i + 1}. ${item.name} (${item.category})\n   Qty: ${item.quantity} × ₵${item.price} = ₵${(item.price * item.quantity).toFixed(2)}\n`;
    });
    message += `\n*Total: ₵${orderData.total.toFixed(2)}*`;
    window.open(`https://wa.me/${whatsappNumber}?text=${encodeURIComponent(message)}`, '_blank');
  }

  productModal?.addEventListener('click', (e) => { if (e.target === productModal) closeProductModal(); });
  checkoutModal?.addEventListener('click', (e) => { if (e.target === checkoutModal) closeCheckoutModal(); });

  async function loadProducts() {
    const { data, error } = await window.dtSupabase.from('products').select('*').order('created_at', { ascending: false });
    if (error) {
      productsDiv.innerHTML = '<p class="col-span-full py-16 text-center text-red-500">Failed to load products.</p>';
      return;
    }
    allProducts = (data || []).map((p) => typeof normalizeProductImage === 'function' ? normalizeProductImage({ ...p }) : p);
    applyFilters();
  }

  loadProducts();
})();
