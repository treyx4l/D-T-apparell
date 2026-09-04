(function () {
  'use strict';

  const productsDiv = document.getElementById('products');
  const modal = document.getElementById('modal');
  if (!productsDiv || !window.dtSupabase) return;

  let selectedCategory = 'All';
  let allProducts = [];

  window.filterByCategory = function filterByCategory(category) {
    selectedCategory = category;
    document.querySelectorAll('[data-filter]').forEach((btn) => {
      btn.classList.toggle('filter-pill-active', btn.dataset.filter === category);
    });
    renderProducts();
  };

  function renderProducts() {
    const filtered = (selectedCategory === 'All' ? allProducts : allProducts.filter((p) => p.category === selectedCategory))
      .filter((p) => !p.sold_out && p.quantity > 0);

    if (filtered.length === 0) {
      productsDiv.innerHTML = '<p class="col-span-full py-16 text-center text-muted">No products found in this category.</p>';
      return;
    }

    productsDiv.innerHTML = filtered.map((p) => `
      <article class="product-card cursor-pointer" data-id="${p.id}">
        <img src="${escapeHtml(typeof normalizeStorageUrl === 'function' ? normalizeStorageUrl(p.image) : p.image)}" alt="${escapeHtml(p.name)}" class="product-card-image" loading="lazy">
        <div class="product-card-body">
          <span class="product-category-badge">${escapeHtml(p.category)}</span>
          <h3 class="text-base font-semibold line-clamp-2">${escapeHtml(p.name)}</h3>
          <p class="text-xs text-muted">${p.quantity} available</p>
          <p class="mt-auto pt-2 text-xl font-bold">₵${Number(p.price).toFixed(2)}</p>
        </div>
      </article>
    `).join('');

    productsDiv.querySelectorAll('.product-card').forEach((card) => {
      card.addEventListener('click', () => {
        const product = allProducts.find((p) => p.id === card.dataset.id);
        if (product) openModal(product);
      });
    });
  }

  function openModal(product) {
    document.getElementById('modalImg').src = typeof normalizeStorageUrl === 'function' ? normalizeStorageUrl(product.image) : product.image;
    document.getElementById('modalTitle').textContent = product.name;
    document.getElementById('modalCategory').textContent = product.category;
    document.getElementById('modalPrice').textContent = '₵' + Number(product.price).toFixed(2);
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  window.closeModal = function closeModal() {
    modal.classList.remove('active');
    document.body.style.overflow = '';
  };

  modal?.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });

  async function loadProducts() {
    const { data, error } = await window.dtSupabase
      .from('products')
      .select('*')
      .order('created_at', { ascending: false });

    if (error) {
      productsDiv.innerHTML = '<p class="col-span-full py-16 text-center text-red-500">Failed to load products.</p>';
      return;
    }

    allProducts = (data || []).map((p) => typeof normalizeProductImage === 'function' ? normalizeProductImage({ ...p }) : p);
    renderProducts();
  }

  loadProducts();
})();
