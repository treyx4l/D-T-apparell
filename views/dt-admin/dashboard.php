<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/config.php';

require_admin();
$pageTitle = 'Products — Admin';
$activeAdminNav = 'products';
$categories = require __DIR__ . '/../../includes/constants.php';
$extraScripts = ['js/admin.js'];

require __DIR__ . '/../../includes/admin-header.php';
?>

<section class="border-b border-gray-200 bg-white py-10 dark:border-gray-800 dark:bg-gray-900">
  <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold">Product Management</h1>
    <p class="mt-2 text-muted">Upload images, set stock quantity, and remove sold-out items</p>
  </div>
</section>

<section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
  <div class="admin-card mb-10">
    <h2 class="mb-6 text-xl font-bold">Add New Product</h2>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <div><label class="label-field">Product Name</label><input id="pname" type="text" class="input-field" maxlength="200"></div>
      <div><label class="label-field">Price (₵)</label><input id="pprice" type="number" step="0.01" min="0" class="input-field"></div>
      <div><label class="label-field">Quantity Available</label><input id="pquantity" type="number" min="0" step="1" value="1" class="input-field"></div>
      <div>
        <label class="label-field">Category</label>
        <select id="pcategory" class="input-field">
          <?php foreach ($categories as $cat): ?>
            <option><?= escape_output($cat) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="mt-4 grid gap-4 sm:grid-cols-2">
      <div>
        <label class="label-field">Product Image</label>
        <input id="pimageFile" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="input-field file:mr-4 file:rounded-lg file:border-0 file:bg-accent file:px-4 file:py-2 file:text-sm file:font-semibold file:text-ink">
        <p class="mt-1 text-xs text-muted">Uploads to Supabase Storage (max 5MB)</p>
        <img id="pimagePreview" alt="" class="mt-3 hidden h-32 w-32 rounded-xl object-cover border border-gray-200">
      </div>
      <div>
        <label class="label-field">Description</label>
        <textarea id="pdescription" class="input-field h-full min-h-[120px]" rows="3" maxlength="1000"></textarea>
      </div>
    </div>
    <button type="button" onclick="addProduct()" class="btn-primary mt-6"><i class="fas fa-plus"></i> Add Product</button>
  </div>

  <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
    <a href="<?= url(ADMIN_PATH . '/products.php') ?>" class="btn-secondary flex items-center gap-2 py-3 px-6 text-sm">
      <i class="fas fa-boxes text-accent"></i> View Inventory / All Products (<span id="productCount">0</span>)
    </a>
  </div>
</section>

<div id="productsModal" class="modal-backdrop">
  <div class="modal-panel max-w-3xl">
    <div class="flex items-center justify-between border-b border-gray-100 p-4 dark:border-gray-800">
      <h3 class="font-semibold">All Products (<span id="modalProductCount">0</span>)</h3>
      <button type="button" onclick="closeProductsModal()" class="icon-btn"><i class="fas fa-times"></i></button>
    </div>
    <div class="max-h-[75vh] overflow-y-auto p-3 sm:p-4">
      <div id="productsList" class="space-y-3"></div>
    </div>
  </div>
</div>

<div id="editProductModal" class="modal-backdrop">
  <div class="modal-panel max-w-2xl">
    <div class="flex items-center justify-between border-b border-gray-100 p-4 dark:border-gray-800">
      <h3 class="font-semibold">Edit Product</h3>
      <button type="button" onclick="closeEditModal()" class="icon-btn"><i class="fas fa-times"></i></button>
    </div>
    <div class="space-y-4 p-6">
      <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="label-field">Name</label><input id="editPname" type="text" class="input-field"></div>
        <div><label class="label-field">Price</label><input id="editPprice" type="number" step="0.01" class="input-field"></div>
        <div><label class="label-field">Quantity</label><input id="editPquantity" type="number" min="0" step="1" class="input-field"></div>
        <div>
          <label class="label-field">Category</label>
          <select id="editPcategory" class="input-field">
            <?php foreach ($categories as $cat): ?>
              <option><?= escape_output($cat) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div>
        <label class="label-field">Replace Image (optional)</label>
        <input id="editPimageFile" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="input-field file:mr-4 file:rounded-lg file:border-0 file:bg-accent file:px-4 file:py-2 file:text-sm file:font-semibold file:text-ink">
        <img id="editPimagePreview" alt="" class="mt-3 h-32 w-32 rounded-xl object-cover border border-gray-200">
        <input type="hidden" id="editPimage" value="">
      </div>
      <div><label class="label-field">Description</label><textarea id="editPdescription" class="input-field" rows="3"></textarea></div>
      <div class="flex justify-end gap-3">
        <button type="button" onclick="closeEditModal()" class="btn-secondary">Cancel</button>
        <button type="button" onclick="saveProductEdit()" class="btn-primary"><i class="fas fa-save"></i> Save</button>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
