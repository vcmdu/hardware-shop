<div class="page-header">
  <div>
    <h2><i class="bi bi-file-earmark-plus text-accent"></i> New Purchase Order</h2>
    <div class="breadcrumb">Create a new supplier purchase order to load stock</div>
  </div>
  <a href="/purchases" class="btn btn-outline"><i class="bi bi-arrow-left"></i> Back to PO List</a>
</div>

<form id="poForm" class="fade-in">
  <div class="row" style="display:flex;gap:16px;margin-bottom:16px;flex-wrap:wrap;">
    <div class="card" style="flex:1;min-width:300px;">
      <div class="card-header"><span class="card-title">Order Information</span></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:12px;">
        <div class="form-group">
          <label class="form-label">Supplier <span style="color:var(--danger)">*</span></label>
          <select class="form-select" id="poSupplier" required>
            <option value="">-- Select Supplier --</option>
            <?php foreach ($suppliers as $s): ?>
              <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['supplier_name']) ?> (<?= $s['supplier_code'] ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Purchase Date</label>
          <input type="date" class="form-control" id="poDate" value="<?= date('Y-m-d') ?>" required>
        </div>
      </div>
    </div>

    <div class="card" style="flex:1;min-width:300px;">
      <div class="card-header"><span class="card-title">Product Lookup</span></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:12px;position:relative;">
        <div class="form-group">
          <label class="form-label">Search Product by Name/Code/Barcode</label>
          <div class="input-wrap">
            <i class="bi bi-search"
              style="position:absolute;left:35px;top:42%;transform:translateY(-50%);color:#64748b;"></i>
            <input type="text" class="form-control" id="prodSearch" placeholder="Type here to search products..."
              style="padding-left:36px;">
          </div>
          <div id="searchDropdown" class="card"
            style="display:none;position:absolute;left:15px;right:15px;z-index:999;max-height:220px;overflow-y:auto;background:#1e293b;border:1px solid #334155;margin-top:4px;">
            <div class="card-body" style="padding:4px;" id="searchResults"></div>
          </div>
        </div>
        <div class="text-muted" style="font-size:0.8rem;"><i class="bi bi-info-circle"></i> Added items will populate in
          the item lines table below.</div>
      </div>
    </div>
  </div>

  <div class="card" style="margin-bottom:16px;">
    <div class="card-header"><span class="card-title">Order Items</span></div>
    <div class="card-body table-wrap" style="padding:0;">
      <table class="data-table display" id="itemsTable" style="margin:0;width:100%;">
        <thead>
          <tr>
            <th>Product</th>
            <th width="100">Stock</th>
            <th width="100">Qty <span style="color:var(--danger)">*</span></th>
            <th width="120">Unit Cost (₹) <span style="color:var(--danger)">*</span></th>
            <th width="90">GST (%)</th>
            <th width="100">Disc (₹)</th>
            <th width="120">Line Total</th>
            <th width="60">Action</th>
          </tr>
        </thead>
        <tbody id="poItemsBody">
          <tr id="emptyRow">
            <td colspan="8" class="text-center text-muted" style="padding:24px;">No products added yet. Search and
              select above to add products.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="row" style="display:flex;justify-content:flex-end;">
    <div class="card" style="width:100%;max-width:400px;">
      <div class="card-body" style="display:flex;flex-direction:column;gap:12px;">
        <div class="d-flex justify-between" style="font-size:0.9rem;">
          <span>Subtotal:</span>
          <strong id="summarySubtotal">₹0.00</strong>
        </div>
        <div class="d-flex justify-between" style="font-size:0.9rem;">
          <span>GST Total:</span>
          <strong id="summaryGst">₹0.00</strong>
        </div>
        <div class="form-group" style="margin:0;">
          <div class="d-flex justify-between align-center">
            <label class="form-label" style="margin:0;">Overall Discount (₹):</label>
            <input type="number" step="0.01" class="form-control" id="poDiscount" value="0.00"
              style="width:120px;text-align:right;padding:6px 10px;">
          </div>
        </div>
        <hr style="border:0;border-top:1px solid #334155;">
        <div class="d-flex justify-between align-center">
          <span style="font-size:1.1rem;font-weight:600;">Grand Total:</span>
          <span style="font-size:1.4rem;font-weight:700;color:var(--primary);" id="summaryGrand">₹0.00</span>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top:8px;padding:12px;"><i
            class="bi bi-cart-check"></i> Save Purchase Order</button>
      </div>
    </div>
  </div>
</form>

<script>
  let searchTimeout = null;
  const selectedProducts = new Set();

  // Handle search lookup
  document.getElementById('prodSearch').addEventListener('input', function () {
    clearTimeout(searchTimeout);
    const q = this.value.trim();
    if (q.length < 2) {
      document.getElementById('searchDropdown').style.display = 'none';
      return;
    }
    searchTimeout = setTimeout(async () => {
      const r = await App.get(`/api/products/search?q=${encodeURIComponent(q)}`);
      const resultsDiv = document.getElementById('searchResults');
      resultsDiv.innerHTML = '';

      if (r.data && r.data.length > 0) {
        r.data.forEach(p => {
          const row = document.createElement('div');
          row.style.padding = '8px 12px';
          row.style.cursor = 'pointer';
          row.style.borderBottom = '1px solid #334155';
          row.className = 'search-item-row';
          row.innerHTML = `
          <div style="display:flex;justify-content:between;font-weight:600;">
            <span>${p.product_name}</span>
            <span style="color:var(--accent); font-family:monospace;">${p.product_code}</span>
          </div>
          <div style="display:flex;justify-content:between;font-size:0.75rem;color:#94a3b8;margin-top:2px;">
            <span>Brand: ${p.brand || 'N/A'} | Stock: ${p.current_stock} ${p.unit}</span>
            <span>Cost: ₹${p.purchase_price}</span>
          </div>
        `;
          row.addEventListener('click', () => {
            addProductRow(p);
            document.getElementById('prodSearch').value = '';
            document.getElementById('searchDropdown').style.display = 'none';
          });
          resultsDiv.appendChild(row);
        });
        document.getElementById('searchDropdown').style.display = 'block';
      } else {
        resultsDiv.innerHTML = '<div style="padding:12px;text-align:center;color:#64748b;">No products found</div>';
        document.getElementById('searchDropdown').style.display = 'block';
      }
    }, 300);
  });

  // Close dropdown on click outside
  document.addEventListener('click', function (e) {
    if (e.target.id !== 'prodSearch' && !e.target.closest('#searchDropdown')) {
      document.getElementById('searchDropdown').style.display = 'none';
    }
  });

  function addProductRow(p) {
    if (selectedProducts.has(p.id)) {
      App.toast('warning', 'Product already added to items.');
      return;
    }

    const emptyRow = document.getElementById('emptyRow');
    if (emptyRow) emptyRow.remove();

    selectedProducts.add(p.id);
    const body = document.getElementById('poItemsBody');
    const tr = document.createElement('tr');
    tr.id = `row-${p.id}`;
    tr.innerHTML = `
    <td>
      <strong style="display:block;">${p.product_name}</strong>
      <code style="font-size:0.75rem;">${p.product_code}</code>
      <input type="hidden" class="item-prod-id" value="${p.id}">
    </td>
    <td>${p.current_stock} ${p.unit}</td>
    <td>
      <input type="number" class="form-control item-qty" value="1" min="1" style="text-align:center;" oninput="recalc()">
    </td>
    <td>
      <input type="number" step="0.01" class="form-control item-cost" value="${p.purchase_price}" min="0.01" style="text-align:right;" oninput="recalc()">
    </td>
    <td>
      <input type="number" step="0.01" class="form-control item-gst" value="${p.gst_percentage}" style="text-align:right;" oninput="recalc()">
    </td>
    <td>
      <input type="number" step="0.01" class="form-control item-disc" value="0.00" style="text-align:right;" oninput="recalc()">
    </td>
    <td>
      <strong class="item-total" style="display:block;text-align:right;">₹0.00</strong>
    </td>
    <td>
      <button type="button" class="btn btn-danger btn-sm btn-icon" onclick="removeRow(${p.id})"><i class="bi bi-trash"></i></button>
    </td>
  `;
    body.appendChild(tr);
    recalc();
  }

  function removeRow(id) {
    document.getElementById(`row-${id}`).remove();
    selectedProducts.delete(id);

    const body = document.getElementById('poItemsBody');
    if (body.children.length === 0) {
      const tr = document.createElement('tr');
      tr.id = 'emptyRow';
      tr.innerHTML = `<td colspan="8" class="text-center text-muted" style="padding:24px;">No products added yet. Search and select above to add products.</td>`;
      body.appendChild(tr);
    }
    recalc();
  }

  function recalc() {
    let subtotal = 0;
    let gstTotal = 0;

    document.querySelectorAll('#poItemsBody tr').forEach(tr => {
      if (tr.id === 'emptyRow') return;

      const qty = parseInt(tr.querySelector('.item-qty').value || 0);
      const cost = parseFloat(tr.querySelector('.item-cost').value || 0);
      const gstPercent = parseFloat(tr.querySelector('.item-gst').value || 0);
      const disc = parseFloat(tr.querySelector('.item-disc').value || 0);

      const rawTotal = qty * cost;
      const gstAmt = rawTotal * (gstPercent / 100);
      const lineTotal = rawTotal + gstAmt - disc;

      tr.querySelector('.item-total').textContent = App.formatCurrency(lineTotal);

      subtotal += rawTotal;
      gstTotal += gstAmt;
    });

    const discount = parseFloat(document.getElementById('poDiscount').value || 0);
    const grandTotal = subtotal + gstTotal - discount;

    document.getElementById('summarySubtotal').textContent = App.formatCurrency(subtotal);
    document.getElementById('summaryGst').textContent = App.formatCurrency(gstTotal);
    document.getElementById('summaryGrand').textContent = App.formatCurrency(grandTotal);
  }

  document.getElementById('poDiscount').addEventListener('input', recalc);

  document.getElementById('poForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    if (selectedProducts.size === 0) {
      App.toast('error', 'Please add at least one product to the purchase order.');
      return;
    }

    const items = [];
    document.querySelectorAll('#poItemsBody tr').forEach(tr => {
      if (tr.id === 'emptyRow') return;

      items.push({
        product_id: parseInt(tr.querySelector('.item-prod-id').value),
        quantity: parseInt(tr.querySelector('.item-qty').value),
        unit_cost: parseFloat(tr.querySelector('.item-cost').value),
        gst_percentage: parseFloat(tr.querySelector('.item-gst').value),
        discount: parseFloat(tr.querySelector('.item-disc').value)
      });
    });

    const payload = {
      supplier_id: parseInt(document.getElementById('poSupplier').value),
      date: document.getElementById('poDate').value,
      discount: parseFloat(document.getElementById('poDiscount').value || 0),
      items: items
    };

    const r = await App.post('/api/purchases', payload);
    App.toast(r.success ? 'success' : 'error', r.message);
    if (r.success) {
      setTimeout(() => {
        window.location.href = '/purchases';
      }, 1000);
    }
  });
</script>

<style>
  .search-item-row:hover {
    background: #334155;
  }
</style>