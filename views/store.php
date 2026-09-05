<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';

$pageTitle = 'Store — ' . APP_NAME;
$activeNav = 'store';
$categories = require __DIR__ . '/../includes/constants.php';
$extraScripts = ['js/store.js'];

require __DIR__ . '/../includes/header.php';
?>

<section class="border-b border-gray-100 bg-surface py-16 dark:border-gray-800 dark:bg-gray-900">
  <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
    <h1 class="text-4xl font-bold tracking-tight sm:text-5xl">Our Store</h1>
    <p class="mt-3 text-muted">Browse our curated collection of premium apparel</p>
  </div>
</section>

<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
  <!-- Search -->
  <div class="relative mx-auto mb-8 max-w-xl">
    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-muted"></i>
    <input type="text" id="searchInput" placeholder="Search products..." class="input-field pl-11" oninput="handleSearch()">
    <button type="button" id="clearSearchBtn" onclick="clearSearch()" class="absolute right-3 top-1/2 hidden -translate-y-1/2 text-muted hover:text-ink"><i class="fas fa-times"></i></button>
  </div>

  <!-- Filters -->
  <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div class="flex flex-wrap gap-2">
      <button type="button" onclick="filterByCategory('All', event)" data-filter="All" class="filter-pill filter-pill-active">All</button>
      <?php foreach ($categories as $cat): ?>
        <button type="button" onclick="filterByCategory('<?= escape_output($cat) ?>', event)" data-filter="<?= escape_output($cat) ?>" class="filter-pill"><?= escape_output($cat) ?></button>
      <?php endforeach; ?>
    </div>
    <div class="flex flex-wrap gap-3">
      <div>
        <label class="label-field text-xs">Sort By</label>
        <select id="sortSelect" class="input-field py-2" onchange="applyFilters()">
          <option value="newest">Newest First</option>
          <option value="oldest">Oldest First</option>
          <option value="price-low">Price: Low to High</option>
          <option value="price-high">Price: High to Low</option>
          <option value="name-asc">Name: A to Z</option>
          <option value="name-desc">Name: Z to A</option>
        </select>
      </div>
      <div>
        <label class="label-field text-xs">Price Range</label>
        <select id="priceFilter" class="input-field py-2" onchange="applyFilters()">
          <option value="all">All Prices</option>
          <option value="0-100">₵0 - ₵100</option>
          <option value="100-500">₵100 - ₵500</option>
          <option value="500-1000">₵500 - ₵1,000</option>
          <option value="1000+">₵1,000+</option>
        </select>
      </div>
    </div>
  </div>

  <p class="mb-6 text-sm text-muted">Showing <span id="showingCount">0</span> of <span id="totalCount">0</span> products</p>

  <div id="products" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 lg:gap-6"></div>
  <div id="pagination" class="mt-10 flex flex-wrap justify-center gap-2"></div>
</section>

<!-- Cart dropdown (fixed in nav area via JS) -->
<div id="cartMenu" class="fixed right-4 top-20 z-50 hidden w-96 max-w-[calc(100vw-2rem)] rounded-2xl border border-gray-100 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900">
  <div class="border-b border-gray-100 p-4 dark:border-gray-800">
    <h3 class="font-semibold"><i class="fas fa-shopping-bag mr-2"></i>Cart Items</h3>
  </div>
  <div id="cartItems" class="max-h-80 overflow-y-auto"></div>
  <div class="border-t border-gray-100 p-4 dark:border-gray-800">
    <button type="button" onclick="openCheckout()" class="btn-primary w-full">Checkout</button>
  </div>
</div>

<!-- Product Modal -->
<div id="productModal" class="modal-backdrop">
  <div class="modal-panel max-w-lg">
    <div class="flex items-center justify-between border-b border-gray-100 p-4 dark:border-gray-800">
      <h3 class="font-semibold">Product Details</h3>
      <button type="button" onclick="closeProductModal()" class="icon-btn"><i class="fas fa-times"></i></button>
    </div>
    <img id="modalImg" alt="" class="h-64 w-full object-cover">
    <div class="p-6">
      <span id="modalCategory" class="product-category-badge"></span>
      <h3 id="modalTitle" class="mt-2 text-xl font-bold"></h3>
      <p id="modalPrice" class="mt-2 text-2xl font-bold"></p>
      <div class="mt-4 border-t border-gray-100 pt-4 dark:border-gray-800">
        <h4 class="mb-2 font-semibold">Description</h4>
        <p id="modalDescription" class="text-sm text-muted"></p>
      </div>
      <button type="button" onclick="addToCartFromModal()" class="btn-primary mt-6 w-full"><i class="fas fa-shopping-cart"></i> Add to Cart</button>
    </div>
  </div>
</div>

<!-- Checkout Modal -->
<div id="checkoutModal" class="modal-backdrop">
  <div class="modal-panel max-w-2xl">
    <div class="flex items-center justify-between border-b border-gray-100 p-4 dark:border-gray-800">
      <h3 class="font-semibold">Checkout</h3>
      <button type="button" onclick="closeCheckoutModal()" class="icon-btn"><i class="fas fa-times"></i></button>
    </div>
    <div class="p-6">
      <div class="mb-6 rounded-xl bg-surface p-4 dark:bg-gray-800">
        <h4 class="mb-3 font-semibold">Order Summary</h4>
        <div id="checkoutCartItems"></div>
        <div class="mt-4 flex justify-between border-t border-gray-200 pt-4 dark:border-gray-700">
          <span class="font-semibold">Total:</span>
          <span id="checkoutTotal" class="text-xl font-bold">₵0</span>
        </div>
      </div>
      <form id="checkoutForm" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?= escape_output(csrf_token()) ?>">
        <div><label class="label-field">Full Name *</label><input type="text" id="customerName" class="input-field" required maxlength="100"></div>
        <div><label class="label-field">Email *</label><input type="email" id="customerEmail" class="input-field" required maxlength="150" value="<?= escape_output($_SESSION['user_email'] ?? '') ?>"></div>
        <div><label class="label-field">Phone *</label><input type="tel" id="customerPhone" class="input-field" required maxlength="20"></div>
        <div>
          <label class="label-field">Region *</label>
          <select id="customerRegion" class="input-field" required>
            <option value="">Select Region</option>
            <?php foreach (['Ashanti','Greater Accra','Volta','Eastern','Central','Western','Northern','Upper East','Upper West','Ahafo','Bono','Bono East','Oti','Savannah','North East','Western North'] as $region): ?>
              <option><?= escape_output($region) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div><label class="label-field">Town *</label><input type="text" id="customerTown" class="input-field" required maxlength="100"></div>
        <div><label class="label-field">Address *</label><textarea id="customerAddress" class="input-field" rows="3" required maxlength="300"></textarea></div>
        <div><label class="label-field">Country</label><input type="text" id="customerCountry" class="input-field bg-gray-50" value="Ghana" readonly></div>
        <button type="submit" class="btn-primary w-full"><i class="fas fa-check"></i> Place Order</button>
      </form>
    </div>
  </div>
</div>

<script>
  document.querySelector('nav .flex.items-center.gap-2').insertAdjacentHTML('beforeend',
    '<button type="button" onclick="toggleCart()" class="icon-btn relative" id="cartToggle"><i class="fas fa-shopping-cart"></i><span id="cartBadge" class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-accent text-xs font-bold text-ink">0</span></button>'
  );
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
