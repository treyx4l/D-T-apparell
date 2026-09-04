<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';

require_admin();
$pageTitle = 'Orders — Admin';
$activeAdminNav = 'orders';
$extraScripts = ['js/orders.js'];

require __DIR__ . '/../includes/admin-header.php';
?>

<section class="border-b border-gray-200 bg-white py-10 dark:border-gray-800 dark:bg-gray-900">
  <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold">Orders Management</h1>
    <p class="mt-2 text-muted">View and manage customer orders</p>
  </div>
</section>

<section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
  <div class="mb-8 flex flex-wrap justify-center gap-2">
    <button type="button" onclick="filterOrders('all')" data-filter="all" class="filter-pill filter-pill-active">All</button>
    <button type="button" onclick="filterOrders('pending')" data-filter="pending" class="filter-pill">Pending</button>
    <button type="button" onclick="filterOrders('processing')" data-filter="processing" class="filter-pill">Processing</button>
    <button type="button" onclick="filterOrders('completed')" data-filter="completed" class="filter-pill">Completed</button>
    <button type="button" onclick="filterOrders('cancelled')" data-filter="cancelled" class="filter-pill">Cancelled</button>
  </div>

  <div id="ordersContainer" class="space-y-4">
    <div class="py-16 text-center text-muted">Loading orders...</div>
  </div>
</section>

<div id="orderModal" class="modal-backdrop">
  <div class="modal-panel max-w-3xl">
    <div class="flex items-center justify-between border-b border-gray-100 p-4 dark:border-gray-800">
      <h3 class="font-semibold">Order Details</h3>
      <button type="button" onclick="closeOrderModal()" class="icon-btn"><i class="fas fa-times"></i></button>
    </div>
    <div id="orderDetails" class="max-h-[70vh] overflow-y-auto p-6"></div>
    <div class="border-t border-gray-100 p-4 dark:border-gray-800">
      <button type="button" onclick="closeOrderModal()" class="btn-secondary">Close</button>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
