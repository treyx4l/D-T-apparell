<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/config.php';

require_admin();
$pageTitle = 'Inventory — Admin';
$activeAdminNav = 'inventory';
$categories = require __DIR__ . '/../../includes/constants.php';
$extraScripts = ['js/admin.js'];

require __DIR__ . '/../../includes/admin-header.php';
?>

<!-- Header -->
<section class="border-b border-gray-200 bg-white py-6 dark:border-gray-800 dark:bg-gray-900">
  <div class="mx-auto flex max-w-4xl items-center justify-between px-4 sm:px-6">
    <div>
      <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Product Inventory</h1>
      <p class="mt-1 text-xs sm:text-sm text-muted">Manage stock, edit prices, and delete sold-out items</p>
    </div>
    <a href="<?= url(ADMIN_PATH . '/dashboard.php') ?>" class="btn-primary flex items-center gap-2 py-2 px-3 sm:px-4 text-xs sm:text-sm">
      <i class="fas fa-plus"></i><span class="hidden sm:inline">Add Product</span>
    </a>
  </div>
</section>

<!-- Main Inventory Content -->
<section class="mx-auto max-w-4xl px-4 py-6 sm:px-6">
  <!-- Search & Filters -->
  <div class="mb-4 space-y-3">
    <div class="relative">
      <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-muted text-sm"></i>
      <input type="text" id="inventorySearch" placeholder="Search by product name or category..." class="input-field pl-10 py-2 text-sm" oninput="handleInventorySearch()">
      <button type="button" id="clearInvSearch" onclick="clearInventorySearch()" class="absolute right-3 top-1/2 -translate-y-1/2 text-muted hover:text-ink hidden text-xs">
        <i class="fas fa-times"></i>
      </button>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-2">
      <div class="flex gap-1.5 overflow-x-auto pb-1 text-xs">
        <button type="button" onclick="filterInventoryByStock('all', event)" data-stock-filter="all" class="filter-pill filter-pill-active py-1 px-3 text-xs">All (<span id="totalItemsCount">0</span>)</button>
        <button type="button" onclick="filterInventoryByStock('instock', event)" data-stock-filter="instock" class="filter-pill py-1 px-3 text-xs">In Stock</button>
        <button type="button" onclick="filterInventoryByStock('soldout', event)" data-stock-filter="soldout" class="filter-pill py-1 px-3 text-xs">Sold Out</button>
      </div>
      <p class="text-xs text-muted">Showing <span id="showingItemsCount">0</span> products</p>
    </div>
  </div>

  <!-- Small List Form for Inventory -->
  <div id="inventoryList" class="divide-y divide-gray-100 dark:divide-gray-800 rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
    <div class="py-16 text-center text-sm text-muted">
      <i class="fas fa-circle-notch fa-spin mr-2"></i>Loading inventory...
    </div>
  </div>
</section>

<!-- Edit Product Modal -->
<div id="editProductModal" class="modal-backdrop">
  <div class="modal-panel max-w-xl">
    <div class="flex items-center justify-between border-b border-gray-100 p-4 dark:border-gray-800">
      <h3 class="font-semibold text-base">Edit Product</h3>
      <button type="button" onclick="closeEditModal()" class="icon-btn"><i class="fas fa-times"></i></button>
    </div>
    <div class="space-y-4 p-4 sm:p-6">
      <div class="grid gap-3 sm:grid-cols-2">
        <div><label class="label-field text-xs">Name</label><input id="editPname" type="text" class="input-field text-sm"></div>
        <div><label class="label-field text-xs">Price (₵)</label><input id="editPprice" type="number" step="0.01" class="input-field text-sm"></div>
        <div><label class="label-field text-xs">Quantity</label><input id="editPquantity" type="number" min="0" step="1" class="input-field text-sm"></div>
        <div>
          <label class="label-field text-xs">Category</label>
          <select id="editPcategory" class="input-field text-sm">
            <?php foreach ($categories as $cat): ?>
              <option><?= escape_output($cat) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div>
        <label class="label-field text-xs">Replace Image (optional)</label>
        <input id="editPimageFile" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="input-field text-xs file:mr-3 file:rounded-lg file:border-0 file:bg-accent file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-ink">
        <img id="editPimagePreview" alt="" class="mt-2 h-24 w-24 rounded-lg object-cover border border-gray-200">
        <input type="hidden" id="editPimage" value="">
      </div>
      <div><label class="label-field text-xs">Description</label><textarea id="editPdescription" class="input-field text-sm" rows="2"></textarea></div>
      <div class="flex justify-end gap-2 pt-2">
        <button type="button" onclick="closeEditModal()" class="btn-secondary py-1.5 px-4 text-xs">Cancel</button>
        <button type="button" onclick="saveProductEdit()" class="btn-primary py-1.5 px-4 text-xs"><i class="fas fa-save mr-1"></i> Save Changes</button>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
