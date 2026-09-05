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

  function stockBadge(product) {
    if (product.sold_out || product.quantity <= 0) {
      return '<span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">Sold Out</span>';
    }
    return `<span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">${product.quantity} in stock</span>`;
  }

  async function renderProducts() {
    const productsList = document.getElementById('productsList');
    if (!productsList) return;

    const client = await getAuthenticatedClient();
    const { data, error } = await client.from('products').select('*').order('created_at', { ascending: false });

    if (error) {
      productsList.innerHTML = '<p class="col-span-full text-center text-red-500">Failed to load products.</p>';
      return;
    }

    const products = (data || []).map((p) => typeof normalizeProductImage === 'function' ? normalizeProductImage({ ...p }) : p);
    document.getElementById('productCount').textContent = products.length;
    document.getElementById('modalProductCount').textContent = products.length;

    if (products.length === 0) {
      productsList.innerHTML = '<p class="col-span-full py-12 text-center text-muted">No products yet.</p>';
      return;
    }

    productsList.innerHTML = products.map((p) => {
      const soldOut = p.sold_out || p.quantity <= 0;
      const safeName = escapeHtml(p.name).replace(/'/g, "\\'");
      const imgUrl = escapeHtml(typeof normalizeStorageUrl === 'function' ? normalizeStorageUrl(p.image) : p.image);
      return `
      <div class="flex items-center gap-3 rounded-xl border border-gray-100 bg-white p-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <img src="${imgUrl}" alt="${safeName}" class="h-16 w-16 sm:h-20 sm:w-20 rounded-xl object-cover border border-gray-100 dark:border-gray-800 flex-shrink-0">
        <div class="min-w-0 flex-1">
          <div class="flex flex-wrap items-center gap-1.5 mb-1">
            <span class="product-category-badge text-[10px] px-2 py-0.5">${escapeHtml(p.category)}</span>
            ${stockBadge(p)}
          </div>
          <h4 class="font-semibold text-sm sm:text-base text-ink dark:text-white truncate">${escapeHtml(p.name)}</h4>
          <p class="text-sm font-bold text-ink dark:text-accent">₵${Number(p.price).toFixed(2)}</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-1.5 flex-shrink-0">
          <button type="button" class="btn-primary text-xs py-1.5 px-3 flex items-center justify-center gap-1" onclick="editProduct('${p.id}')">
            <i class="fas fa-edit text-xs"></i><span>Edit</span>
          </button>
          <button type="button" class="rounded-xl py-1.5 px-3 text-xs font-semibold text-white flex items-center justify-center gap-1 ${soldOut ? 'bg-red-500 hover:bg-red-600' : 'bg-gray-200 text-gray-400 dark:bg-gray-800 dark:text-gray-600 cursor-not-allowed'}"
            ${soldOut ? `onclick="deleteProduct('${p.id}', '${safeName}', true, '${escapeHtml(p.image)}')"` : 'disabled title="Only sold-out items can be deleted"'}>
            <i class="fas fa-trash text-xs"></i><span>${soldOut ? 'Delete' : 'Locked'}</span>
          </button>
        </div>
      </div>`;
    }).join('');
  }

  window.openProductsModal = function openProductsModal() {
    document.getElementById('productsModal').classList.add('active');
    document.body.style.overflow = 'hidden';
    renderProducts();
  };

  window.closeProductsModal = function closeProductsModal() {
    document.getElementById('productsModal').classList.remove('active');
    document.body.style.overflow = '';
  };

  window.closeEditModal = function closeEditModal() {
    document.getElementById('editProductModal').classList.remove('active');
    document.body.style.overflow = '';
    editingProductId = null;
    editingImagePath = null;
  };

  renderProducts();
})();
