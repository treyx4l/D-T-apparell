(function () {
  'use strict';

  if (!window.dtSupabase) return;

  const ordersContainer = document.getElementById('ordersContainer');
  const orderModal = document.getElementById('orderModal');
  const orderDetails = document.getElementById('orderDetails');
  const accessToken = window.DT_APPARELS?.accessToken;
  let currentFilter = 'all';
  let allOrders = [];

  async function getAuthenticatedClient() {
    if (accessToken) {
      await window.dtSupabase.auth.setSession({
        access_token: accessToken,
        refresh_token: window.DT_APPARELS?.refreshToken || '',
      });
    }
    return window.dtSupabase;
  }

  window.filterOrders = function filterOrders(status) {
    currentFilter = status;
    document.querySelectorAll('[data-filter]').forEach((btn) => {
      btn.classList.toggle('filter-pill-active', btn.dataset.filter === status);
    });
    renderOrders();
  };

  function getStatusBadge(status) {
    const colors = { pending: 'bg-orange-500', processing: 'bg-blue-500', completed: 'bg-green-500', cancelled: 'bg-red-500' };
    return `<span class="inline-block rounded-full ${colors[status] || colors.pending} px-3 py-1 text-xs font-semibold uppercase text-white">${status}</span>`;
  }

  function renderOrders() {
    const orders = currentFilter === 'all' ? allOrders : allOrders.filter((o) => o.status === currentFilter);

    if (orders.length === 0) {
      ordersContainer.innerHTML = '<p class="py-16 text-center text-muted">No orders found.</p>';
      return;
    }

    ordersContainer.innerHTML = orders.map((order) => {
      const total = Number(order.total) || 0;
      const date = order.created_at ? new Date(order.created_at).toLocaleString() : 'N/A';
      return `
        <div class="admin-card">
          <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-bold">Order #${order.id.substring(0, 8)}</h2>
              <p class="text-sm text-muted">${escapeHtml(order.customer_name)} · ${escapeHtml(order.email)}</p>
              <p class="text-sm text-muted">${escapeHtml(order.phone)}</p>
            </div>
            ${getStatusBadge(order.status)}
          </div>
          <div class="mt-4 grid gap-4 sm:grid-cols-2 text-sm">
            <div>
              <p class="font-medium text-muted">Delivery</p>
              <p>${escapeHtml(order.address)}, ${escapeHtml(order.town)}</p>
              <p>${escapeHtml(order.region)}, ${escapeHtml(order.country)}</p>
            </div>
            <div>
              <p class="font-medium text-muted">Order Info</p>
              <p>Date: ${date}</p>
              <p>Items: ${(order.items || []).length}</p>
              <p class="text-lg font-bold">₵${total.toFixed(2)}</p>
            </div>
          </div>
          <div class="mt-4 flex flex-wrap gap-2">
            <button type="button" onclick="viewOrderDetails('${order.id}')" class="btn-primary text-sm py-2">View Details</button>
            <select onchange="updateOrderStatus('${order.id}', this.value)" class="input-field w-auto py-2 text-sm">
              <option value="">Update Status</option>
              <option value="pending" ${order.status === 'pending' ? 'selected' : ''}>Pending</option>
              <option value="processing" ${order.status === 'processing' ? 'selected' : ''}>Processing</option>
              <option value="completed" ${order.status === 'completed' ? 'selected' : ''}>Completed</option>
              <option value="cancelled" ${order.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
            </select>
          </div>
        </div>`;
    }).join('');
  }

  window.viewOrderDetails = function viewOrderDetails(orderId) {
    const order = allOrders.find((o) => o.id === orderId);
    if (!order) return;

    const total = Number(order.total) || 0;
    const items = order.items || [];

    orderDetails.innerHTML = `
      <div class="space-y-6">
        <div class="rounded-xl bg-surface p-4 dark:bg-gray-800">
          <div class="flex flex-wrap justify-between gap-4">
            <div><p class="text-sm text-muted">Order ID</p><p class="font-mono text-sm">${order.id}</p></div>
            <div>${getStatusBadge(order.status)}</div>
            <div><p class="text-sm text-muted">Total</p><p class="text-xl font-bold">₵${total.toFixed(2)}</p></div>
          </div>
        </div>
        <div class="rounded-xl bg-surface p-4 dark:bg-gray-800">
          <h4 class="mb-3 font-semibold">Customer</h4>
          <p>${escapeHtml(order.customer_name)} · ${escapeHtml(order.email)} · ${escapeHtml(order.phone)}</p>
          <p class="mt-1 text-sm text-muted">${escapeHtml(order.address)}, ${escapeHtml(order.town)}, ${escapeHtml(order.region)}</p>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead><tr class="border-b"><th class="p-2 text-left">Product</th><th class="p-2">Qty</th><th class="p-2 text-right">Subtotal</th></tr></thead>
            <tbody>
              ${items.map((item) => `
                <tr class="border-b border-gray-100 dark:border-gray-800">
                  <td class="p-2">${escapeHtml(item.name)}</td>
                  <td class="p-2 text-center">${item.quantity}</td>
                  <td class="p-2 text-right font-medium">₵${(item.price * item.quantity).toFixed(2)}</td>
                </tr>`).join('')}
            </tbody>
          </table>
        </div>
        <div>
          <label class="label-field">Update Status</label>
          <select id="statusSelect-${order.id}" class="input-field" onchange="updateOrderStatusFromModal('${order.id}')">
            ${['pending','processing','completed','cancelled'].map((s) => `<option value="${s}" ${order.status === s ? 'selected' : ''}>${s.charAt(0).toUpperCase() + s.slice(1)}</option>`).join('')}
          </select>
        </div>
      </div>`;

    orderModal.classList.add('active');
    document.body.style.overflow = 'hidden';
  };

  window.updateOrderStatus = async function updateOrderStatus(orderId, status) {
    if (!status) return;
    try {
      const client = await getAuthenticatedClient();
      const { error } = await client.from('orders').update({ status }).eq('id', orderId);
      if (error) throw error;
      showToast('Status updated');
      await loadOrders();
    } catch (err) {
      showToast(err.message || 'Update failed', 'error');
    }
  };

  window.updateOrderStatusFromModal = function updateOrderStatusFromModal(orderId) {
    const status = document.getElementById(`statusSelect-${orderId}`)?.value;
    if (status) updateOrderStatus(orderId, status);
  };

  window.closeOrderModal = function closeOrderModal() {
    orderModal.classList.remove('active');
    document.body.style.overflow = '';
  };

  orderModal?.addEventListener('click', (e) => { if (e.target === orderModal) closeOrderModal(); });

  async function loadOrders() {
    const client = await getAuthenticatedClient();
    const { data, error } = await client.from('orders').select('*').order('created_at', { ascending: false });
    if (error) {
      ordersContainer.innerHTML = '<p class="py-16 text-center text-red-500">Failed to load orders.</p>';
      return;
    }
    allOrders = data || [];
    renderOrders();
  }

  loadOrders();
})();
