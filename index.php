<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';

$pageTitle = APP_NAME . ' — Premium Apparel';
$activeNav = 'home';
$categories = require __DIR__ . '/includes/constants.php';
$extraScripts = ['js/carousel.js', 'js/app.js'];

require __DIR__ . '/includes/header.php';
?>

<!-- Hero Carousel -->
<section class="relative h-[70vh] min-h-[480px] overflow-hidden bg-ink">
  <div id="heroCarousel" class="relative h-full"></div>
  <div id="heroDots" class="absolute bottom-8 left-1/2 flex -translate-x-1/2 gap-2"></div>
</section>

<!-- Featured Products -->
<section id="products-section" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
  <div class="mb-12 text-center">
    <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">Our Collection</h2>
    <p class="mt-3 text-muted">Curated apparel for every style</p>
  </div>

  <div class="mb-10 flex flex-wrap justify-center gap-2">
    <button type="button" onclick="filterByCategory('All', event)" data-filter="All" class="filter-pill filter-pill-active">All</button>
    <?php foreach ($categories as $cat): ?>
      <button type="button" onclick="filterByCategory('<?= escape_output($cat) ?>', event)" data-filter="<?= escape_output($cat) ?>" class="filter-pill"><?= escape_output($cat) ?></button>
    <?php endforeach; ?>
  </div>

  <div id="products" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 lg:gap-6"></div>
</section>

<!-- Features -->
<section id="features" class="bg-surface py-20 dark:bg-gray-900">
  <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <h2 class="mb-12 text-center text-3xl font-bold">Why Choose D&amp;T?</h2>
    <div class="grid gap-8 sm:grid-cols-3">
      <div class="rounded-2xl border border-gray-100 bg-white p-8 text-center dark:border-gray-800 dark:bg-gray-950">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-xl bg-accent text-ink"><i class="fas fa-star text-xl"></i></div>
        <h3 class="font-semibold">Premium Quality</h3>
        <p class="mt-2 text-sm text-muted">Hand-picked apparel meeting the highest standards</p>
      </div>
      <div class="rounded-2xl border border-gray-100 bg-white p-8 text-center dark:border-gray-800 dark:bg-gray-950">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-xl bg-accent text-ink"><i class="fas fa-shipping-fast text-xl"></i></div>
        <h3 class="font-semibold">Fast Delivery</h3>
        <p class="mt-2 text-sm text-muted">Quick processing and reliable delivery </p>
      </div>
      <div class="rounded-2xl border border-gray-100 bg-white p-8 text-center dark:border-gray-800 dark:bg-gray-950">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-xl bg-accent text-ink"><i class="fas fa-tag text-xl"></i></div>
        <h3 class="font-semibold">Best Prices</h3>
        <p class="mt-2 text-sm text-muted">Competitive pricing with exclusive member benefits</p>
      </div>
    </div>
  </div>
</section>

<!-- Product Modal -->
<div id="modal" class="modal-backdrop">
  <div class="modal-panel max-w-md">
    <div class="relative">
      <button type="button" onclick="closeModal()" class="absolute right-4 top-4 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-ink shadow"><i class="fas fa-times"></i></button>
      <img id="modalImg" alt="" class="h-72 w-full object-cover">
      <div class="p-6">
        <span id="modalCategory" class="product-category-badge"></span>
        <h3 id="modalTitle" class="mt-2 text-xl font-bold"></h3>
        <p id="modalPrice" class="mt-2 text-2xl font-bold text-ink dark:text-accent"></p>
        <a href="<?= url('store.php') ?>" class="btn-primary mt-6 w-full">View in Store</a>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
