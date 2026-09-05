(function () {
  'use strict';

  if (!window.dtSupabase) return;

  const config = window.DT_APPARELS || {};
  const bucket = config.storageBucket || 'product-images';
  let editingProductId = null;
  let editingImagePath = null;
  const accessToken = config.accessToken;

  async function getAuthenticatedClient() {
    if (accessToken) {
      await window.dtSupabase.auth.setSession({
        access_token: accessToken,
        refresh_token: config.refreshToken || '',
      });
    }
    return window.dtSupabase;
  }

  function getStoragePathFromUrl(imageUrl) {
    if (!imageUrl) return null;
    const normalized = typeof normalizeStorageUrl === 'function' ? normalizeStorageUrl(imageUrl) : imageUrl;
    const markers = [
      `/storage/v1/object/public/${bucket}/`,
      `/storage/v1/object/${bucket}/`,
    ];
    for (const marker of markers) {
      const idx = normalized.indexOf(marker);
      if (idx !== -1) {
        return decodeURIComponent(normalized.substring(idx + marker.length));
      }
    }
    return null;
  }

  async function uploadImage(file, prefix) {
    if (!file) return null;
    if (file.size > 5 * 1024 * 1024) throw new Error('Image must be 5MB or smaller');

    const ext = (file.name.split('.').pop() || 'jpg').toLowerCase();
    const path = `${prefix}/${Date.now()}-${Math.random().toString(36).slice(2)}.${ext}`;
    const client = await getAuthenticatedClient();
    const { data: uploadData, error } = await client.storage.from(bucket).upload(path, file, {
      cacheControl: '3600',
      upsert: false,
      contentType: file.type,
    });
    if (error) throw error;

    const supabaseUrl = config.supabase?.url || '';
    const publicUrl = typeof buildPublicStorageUrl === 'function'
      ? buildPublicStorageUrl(supabaseUrl, bucket, path)
      : client.storage.from(bucket).getPublicUrl(path).data.publicUrl;

    return { publicUrl: typeof normalizeStorageUrl === 'function' ? normalizeStorageUrl(publicUrl) : publicUrl, path };
  }

  async function deleteStorageImage(imageUrl) {
    const path = getStoragePathFromUrl(imageUrl);
    if (!path) return;
    const client = await getAuthenticatedClient();
    await client.storage.from(bucket).remove([path]);
  }

  function previewFile(input, previewEl) {
    const file = input.files?.[0];
    if (!file) return;
    previewEl.src = URL.createObjectURL(file);
    previewEl.classList.remove('hidden');
  }

  document.getElementById('pimageFile')?.addEventListener('change', (e) => {
    previewFile(e.target, document.getElementById('pimagePreview'));
  });
  document.getElementById('editPimageFile')?.addEventListener('change', (e) => {
    previewFile(e.target, document.getElementById('editPimagePreview'));
  });

  window.addProduct = async function addProduct() {
    const name = document.getElementById('pname').value.trim();
    const price = parseFloat(document.getElementById('pprice').value);
    const quantity = parseInt(document.getElementById('pquantity').value, 10);
    const category = document.getElementById('pcategory').value;
    const description = document.getElementById('pdescription').value.trim();
    const file = document.getElementById('pimageFile').files?.[0];

    if (!name || !file || isNaN(price) || price <= 0 || isNaN(quantity) || quantity < 0) {
      showToast('Name, image, valid price, and quantity are required', 'error');
      return;
    }

    try {
      const uploaded = await uploadImage(file, 'products');
      const client = await getAuthenticatedClient();
      const { error } = await client.from('products').insert([{
        name: name.slice(0, 200),
        price,
        quantity,
        image: uploaded.publicUrl,
        category,
        description: description.slice(0, 1000) || 'No description available',
      }]);
      if (error) throw error;

      showToast('Product added successfully!');
      document.getElementById('pname').value = '';
      document.getElementById('pprice').value = '';
      document.getElementById('pquantity').value = '1';
      document.getElementById('pimageFile').value = '';
      document.getElementById('pdescription').value = '';
      document.getElementById('pimagePreview').classList.add('hidden');
      renderProducts();
    } catch (err) {
      showToast(err.message || 'Error adding product', 'error');
    }
  };

  window.editProduct = async function editProduct(productId) {
    editingProductId = productId;
    const client = await getAuthenticatedClient();
    const { data, error } = await client.from('products').select('*').eq('id', productId).single();
    if (error || !data) { showToast('Product not found', 'error'); return; }

    editingImagePath = getStoragePathFromUrl(data.image);
    const normalizedImage = typeof normalizeStorageUrl === 'function' ? normalizeStorageUrl(data.image) : data.image;
    document.getElementById('editPname').value = data.name;
    document.getElementById('editPprice').value = data.price;
    document.getElementById('editPquantity').value = data.quantity ?? 0;
    document.getElementById('editPimage').value = normalizedImage;
    document.getElementById('editPcategory').value = data.category;
    document.getElementById('editPdescription').value = data.description || '';
    document.getElementById('editPimagePreview').src = normalizedImage;
    document.getElementById('editPimageFile').value = '';
    document.getElementById('editProductModal').classList.add('active');
    document.body.style.overflow = 'hidden';
  };

  window.saveProductEdit = async function saveProductEdit() {
    if (!editingProductId) return;

    const name = document.getElementById('editPname').value.trim();
    const price = parseFloat(document.getElementById('editPprice').value);
    const quantity = parseInt(document.getElementById('editPquantity').value, 10);
    const category = document.getElementById('editPcategory').value;
    const description = document.getElementById('editPdescription').value.trim();
    const file = document.getElementById('editPimageFile').files?.[0];
    let image = document.getElementById('editPimage').value;

    if (!name || !image || isNaN(price) || price <= 0 || isNaN(quantity) || quantity < 0) {
      showToast('Name, image, valid price, and quantity are required', 'error');
      return;
    }

    try {
      if (file) {
        const uploaded = await uploadImage(file, 'products');
        if (editingImagePath) await deleteStorageImage(image);
        image = uploaded.publicUrl;
        editingImagePath = uploaded.path;
      } else if (typeof normalizeStorageUrl === 'function') {
        image = normalizeStorageUrl(image);
      }

      const client = await getAuthenticatedClient();
      const { error } = await client.from('products').update({
        name, price, quantity, image, category,
        description: description || 'No description available',
        updated_at: new Date().toISOString(),
      }).eq('id', editingProductId);
      if (error) throw error;

      showToast('Product updated!');
      closeEditModal();
      renderProducts();
    } catch (err) {
      showToast(err.message || 'Error updating product', 'error');
    }
  };

  window.deleteProduct = async function deleteProduct(productId, productName, isSoldOut, imageUrl) {
    if (!isSoldOut) {
      showToast('Only sold-out items can be deleted. Set quantity to 0 first.', 'error');
      return;
    }
    if (!confirm(`Delete sold-out item "${productName}"? This removes the product and its image.`)) return;

    try {
      const client = await getAuthenticatedClient();
      const { error } = await client.from('products').delete().eq('id', productId);
      if (error) throw error;
      await deleteStorageImage(imageUrl);
      showToast('Sold-out product deleted');
      renderProducts();
    } catch (err) {
      showToast(err.message || 'Error deleting product', 'error');
    }
  };

  let rawProducts = [];
  let currentStockFilter = 'all';
  let currentSearchQuery = '';

  function stockBadge(product) {
    if (product.sold_out || product.quantity <= 0) {
      return '<span class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold text-red-700 dark:bg-red-950 dark:text-red-300">Sold Out</span>';
    }
    return `<span class="rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-semibold text-green-700 dark:bg-green-950 dark:text-green-300">${product.quantity} in stock</span>`;
  }

  function getFilteredProducts() {
    return rawProducts.filter((p) => {
      const isSoldOut = p.sold_out || p.quantity <= 0;
      if (currentStockFilter === 'instock' && isSoldOut) return false;
      if (currentStockFilter === 'soldout' && !isSoldOut) return false;

      if (currentSearchQuery) {
        const q = currentSearchQuery.toLowerCase();
        const nameMatch = (p.name || '').toLowerCase().includes(q);
        const catMatch = (p.category || '').toLowerCase().includes(q);
        if (!nameMatch && !catMatch) return false;
      }
      return true;
    });
  }

  function renderInventoryList() {
    const inventoryList = document.getElementById('inventoryList');
    const productsList = document.getElementById('productsList');
    const filtered = getFilteredProducts();

    const showingCountEl = document.getElementById('showingItemsCount');
    if (showingCountEl) showingCountEl.textContent = filtered.length;

    const html = filtered.length === 0
      ? '<div class="py-12 text-center text-sm text-muted">No products found matching your search.</div>'
      : filtered.map((p) => {
          const soldOut = p.sold_out || p.quantity <= 0;
          const safeName = escapeHtml(p.name).replace(/'/g, "\\'");
          const imgUrl = escapeHtml(typeof normalizeStorageUrl === 'function' ? normalizeStorageUrl(p.image) : p.image);
          return `
          <div class="flex items-center gap-3 p-2.5 sm:p-3 hover:bg-gray-50/80 dark:hover:bg-gray-800/40 transition-colors">
            <img src="${imgUrl}" alt="${safeName}" class="h-12 w-12 sm:h-14 sm:w-14 rounded-lg object-cover bg-gray-100 dark:bg-gray-800 border border-gray-100 dark:border-gray-800 flex-shrink-0">
            <div class="min-w-0 flex-1">
              <h4 class="font-medium text-xs sm:text-sm text-ink dark:text-white truncate">${escapeHtml(p.name)}</h4>
              <div class="mt-1 flex flex-wrap items-center gap-1.5">
                <span class="text-xs sm:text-sm font-bold text-accent">₵${Number(p.price).toFixed(2)}</span>
                ${stockBadge(p)}
                <span class="text-[10px] text-muted hidden sm:inline">• ${escapeHtml(p.category)}</span>
              </div>
            </div>
            <div class="flex items-center gap-1.5 flex-shrink-0">
              <button type="button" class="h-8 w-8 rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-ink dark:text-white flex items-center justify-center text-xs transition" title="Edit Product" onclick="editProduct('${p.id}')">
                <i class="fas fa-pen"></i>
              </button>
              <button type="button" class="h-8 w-8 rounded-lg text-xs flex items-center justify-center transition ${soldOut ? 'bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-950 dark:text-red-300' : 'opacity-30 cursor-not-allowed text-gray-400 dark:text-gray-600'}"
                ${soldOut ? `onclick="deleteProduct('${p.id}', '${safeName}', true, '${escapeHtml(p.image)}')"` : 'disabled title="Only sold-out items can be deleted"'}>
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>`;
        }).join('');

    if (inventoryList) inventoryList.innerHTML = html;
    if (productsList) productsList.innerHTML = html;
  }

  async function renderProducts() {
    const client = await getAuthenticatedClient();
    const { data, error } = await client.from('products').select('*').order('created_at', { ascending: false });

    if (error) {
      const errHtml = '<p class="col-span-full py-8 text-center text-red-500 text-sm">Failed to load products.</p>';
      const invList = document.getElementById('inventoryList');
      const prodList = document.getElementById('productsList');
      if (invList) invList.innerHTML = errHtml;
      if (prodList) prodList.innerHTML = errHtml;
      return;
    }

    rawProducts = (data || []).map((p) => typeof normalizeProductImage === 'function' ? normalizeProductImage({ ...p }) : p);

    const countEls = ['productCount', 'modalProductCount', 'totalItemsCount', 'showingItemsCount'];
    countEls.forEach((id) => {
      const el = document.getElementById(id);
      if (el) el.textContent = rawProducts.length;
    });

    renderInventoryList();
  }

  window.handleInventorySearch = function handleInventorySearch() {
    const input = document.getElementById('inventorySearch');
    const clearBtn = document.getElementById('clearInvSearch');
    currentSearchQuery = (input?.value || '').trim();
    if (clearBtn) {
      clearBtn.classList.toggle('hidden', currentSearchQuery === '');
    }
    renderInventoryList();
  };

  window.clearInventorySearch = function clearInventorySearch() {
    const input = document.getElementById('inventorySearch');
    const clearBtn = document.getElementById('clearInvSearch');
    if (input) input.value = '';
    if (clearBtn) clearBtn.classList.add('hidden');
    currentSearchQuery = '';
    renderInventoryList();
  };

  window.filterInventoryByStock = function filterInventoryByStock(filter, e) {
    currentStockFilter = filter;
    document.querySelectorAll('[data-stock-filter]').forEach((btn) => {
      btn.classList.remove('filter-pill-active');
    });
    if (e?.target) {
      e.target.classList.add('filter-pill-active');
    }
    renderInventoryList();
  };

  window.openProductsModal = function openProductsModal() {
    document.getElementById('productsModal')?.classList.add('active');
    document.body.style.overflow = 'hidden';
    renderProducts();
  };

  window.closeProductsModal = function closeProductsModal() {
    document.getElementById('productsModal')?.classList.remove('active');
    document.body.style.overflow = '';
  };

  window.closeEditModal = function closeEditModal() {
    document.getElementById('editProductModal')?.classList.remove('active');
    document.body.style.overflow = '';
    editingProductId = null;
    editingImagePath = null;
  };

  renderProducts();
})();
