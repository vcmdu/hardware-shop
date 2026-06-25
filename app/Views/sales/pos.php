<div class="page-header" style="margin-bottom:12px;">
  <div>
    <h2><i class="bi bi-cpu text-accent"></i> POS Terminal</h2>
    <div class="breadcrumb">Quick checkout billing terminal</div>
  </div>
  <a href="/sales" class="btn btn-outline btn-sm"><i class="bi bi-receipt"></i> Sales History</a>
</div>

<div class="row" style="display:flex;gap:16px;flex-wrap:wrap;align-items:flex-start;">
  <!-- Left Side: Cart & Product Search -->
  <div style="flex:2;min-width:350px;display:flex;flex-direction:column;gap:12px;position:relative;z-index:20;">
    <!-- Product Search Card -->
    <div style="position:relative;z-index:100;">
      <div class="card" style="margin:0;overflow:visible;">
        <div class="card-body" style="padding:12px;overflow:visible;">
          <div class="form-group" style="margin:0;position:relative;">
            <div class="input-wrap" style="position:relative;">
              <i class="bi bi-search"
                style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#64748b;font-size:1.1rem;z-index:2;pointer-events:none;"></i>
              <input type="text" class="form-control" id="posProdSearch"
                placeholder="Scan Barcode or Type Product Name/Code to add..."
                style="padding-left:40px;font-size:1rem;height:44px;"
                autocomplete="off">
            </div>
            <!-- Autocomplete dropdown — rendered OUTSIDE the card body stacking context -->
            <div id="posSearchDropdown"
              style="display:none;position:absolute;left:0;right:0;top:100%;margin-top:4px;z-index:9999;max-height:280px;overflow-y:auto;background:#1e293b;border:1px solid #334155;border-radius:10px;box-shadow:0 16px 40px rgba(0,0,0,0.6);">
              <div id="posSearchResults"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Cart Items Card -->
    <div class="card" style="margin:0;">
      <div class="card-header"><span class="card-title"><i class="bi bi-cart3"></i> Billing Cart Items</span></div>
      <div class="card-body" style="padding:0;min-height:280px;overflow-x:auto;">
        <!-- NOTE: No 'data-table' or 'display' class — we manage this table manually -->
        <table id="posCartTable" style="width:100%;border-collapse:collapse;">
          <thead>
            <tr style="border-bottom:1px solid #334155;">
              <th style="padding:10px 12px;text-align:left;font-size:0.8rem;color:#94a3b8;font-weight:600;">Product</th>
              <th style="padding:10px 8px;text-align:center;font-size:0.8rem;color:#94a3b8;font-weight:600;width:70px;">Stock</th>
              <th style="padding:10px 8px;text-align:center;font-size:0.8rem;color:#94a3b8;font-weight:600;width:90px;">Qty</th>
              <th style="padding:10px 8px;text-align:right;font-size:0.8rem;color:#94a3b8;font-weight:600;width:110px;">Price (₹)</th>
              <th style="padding:10px 8px;text-align:right;font-size:0.8rem;color:#94a3b8;font-weight:600;width:65px;">GST%</th>
              <th style="padding:10px 8px;text-align:right;font-size:0.8rem;color:#94a3b8;font-weight:600;width:90px;">Disc (₹)</th>
              <th style="padding:10px 12px;text-align:right;font-size:0.8rem;color:#94a3b8;font-weight:600;width:110px;">Total</th>
              <th style="padding:10px 8px;width:36px;"></th>
            </tr>
          </thead>
          <tbody id="posCartBody">
            <tr id="posEmptyRow">
              <td colspan="8" style="padding:48px;text-align:center;color:#64748b;font-size:0.9rem;">
                <i class="bi bi-cart-x" style="font-size:2rem;display:block;margin-bottom:8px;opacity:0.4;"></i>
                Cart is empty — search for products above or scan a barcode to add items.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Right Side: Customer & Checkout Calculations -->
  <div style="flex:1;min-width:300px;max-width:420px;display:flex;flex-direction:column;gap:12px;">
    <div class="card" style="margin:0;">
      <div class="card-header"><span class="card-title"><i class="bi bi-person-bounding-box"></i> Customer & Invoice Info</span></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:12px;padding:16px;">
        <div class="form-group" style="margin:0;">
          <label class="form-label">Select Customer <span style="color:var(--danger)">*</span></label>
          <select class="form-select" id="posCustomer" onchange="checkCustomerCredit()" required>
            <option value="">— Select Customer —</option>
            <?php foreach ($customers as $c): ?>
              <option value="<?= $c['id'] ?>"
                data-balance="<?= $c['outstanding_balance'] ?>"
                data-limit="<?= $c['credit_limit'] ?>"
                <?= $c['customer_code'] === 'CUST001' ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['name']) ?> (<?= $c['customer_code'] ?>)
              </option>
            <?php endforeach; ?>
          </select>
          <div id="customerInfo" style="font-size:0.8rem;margin-top:6px;display:none;padding:8px;background:rgba(59,130,246,0.08);border-radius:6px;border:1px solid rgba(59,130,246,0.2);"></div>
        </div>
        <div class="form-group" style="margin:0;">
          <label class="form-label">Billing Date</label>
          <input type="date" class="form-control" id="posDate" value="<?= date('Y-m-d') ?>">
        </div>
      </div>
    </div>

    <!-- Payments Card -->
    <div class="card" style="margin:0;">
      <div class="card-header"><span class="card-title"><i class="bi bi-wallet2"></i> Payment Details</span></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:10px;padding:16px;">
        <div class="d-flex justify-between" style="font-size:0.9rem;">
          <span>Subtotal:</span>
          <strong id="posSubtotal">₹0.00</strong>
        </div>
        <div class="d-flex justify-between" style="font-size:0.9rem;">
          <span>GST Tax:</span>
          <strong id="posGst">₹0.00</strong>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;">
          <label class="form-label" style="margin:0;font-size:0.85rem;">Invoice Discount (₹):</label>
          <input type="number" step="0.01" class="form-control" id="posDiscount" value="0.00" min="0"
            style="width:110px;text-align:right;padding:4px 8px;" oninput="posRecalc()">
        </div>
        <hr style="border:0;border-top:1px solid #334155;margin:0;">
        <div class="d-flex justify-between align-center">
          <span style="font-size:1.1rem;font-weight:600;">Grand Total:</span>
          <span style="font-size:1.4rem;font-weight:700;color:var(--primary);" id="posGrand">₹0.00</span>
        </div>
        <hr style="border:0;border-top:1px solid #334155;margin:0;">
        <div class="form-group" style="margin:0;">
          <label class="form-label">Payment Method</label>
          <select class="form-select" id="posPaymentMethod" onchange="onPaymentMethodChange()">
            <option value="cash">Cash</option>
            <option value="upi">UPI / QR Code</option>
            <option value="card">Card Payment</option>
            <option value="credit">Customer Credit / Unpaid</option>
          </select>
        </div>
        <div class="form-group" style="margin:0;">
          <label class="form-label">Amount Paid (₹)</label>
          <input type="number" step="0.01" class="form-control" id="posPaidAmt" value="0.00" min="0"
            oninput="calculateChange()">
        </div>
        <div class="d-flex justify-between" style="font-size:0.9rem;" id="changeDiv">
          <span id="changeDivLabel">Change / Balance Due:</span>
          <strong id="posChangeText" class="text-success">₹0.00</strong>
        </div>
        <button class="btn btn-primary" id="posPayBtn"
          style="margin-top:4px;padding:13px;font-size:1rem;width:100%;display:flex;align-items:center;justify-content:center;gap:8px;"
          onclick="completeSale()">
          <i class="bi bi-receipt-cutoff"></i> Pay &amp; Generate Invoice
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  let posSearchTimeout = null;
  const cartProducts = new Set();   // tracks product IDs currently in cart
  let grandTotalVal = 0;

  // ── On load ──────────────────────────────────────────────────────────────
  document.addEventListener('DOMContentLoaded', () => {
    checkCustomerCredit();
  });

  // ── Product Search ───────────────────────────────────────────────────────
  document.getElementById('posProdSearch').addEventListener('input', function () {
    clearTimeout(posSearchTimeout);
    const q = this.value.trim();
    if (q.length < 2) {
      document.getElementById('posSearchDropdown').style.display = 'none';
      return;
    }
    posSearchTimeout = setTimeout(async () => {
      try {
        const r = await App.get(`/api/products/search?q=${encodeURIComponent(q)}`);
        const resultsDiv = document.getElementById('posSearchResults');
        resultsDiv.innerHTML = '';

        if (r.data && r.data.length > 0) {
          r.data.forEach(p => {
            const row = document.createElement('div');
            row.style.cssText = 'padding:10px 14px;cursor:pointer;border-bottom:1px solid #1e3a5f;';
            row.className = 'pos-search-item';
            row.innerHTML = `
              <div style="display:flex;justify-content:space-between;align-items:center;">
                <span style="font-weight:600;color:#f1f5f9;">${escHtml(p.product_name)}</span>
                <span style="font-size:0.75rem;color:var(--accent);font-family:monospace;background:rgba(6,182,212,0.1);padding:2px 6px;border-radius:4px;">${escHtml(p.product_code)}</span>
              </div>
              <div style="display:flex;justify-content:space-between;font-size:0.75rem;color:#94a3b8;margin-top:3px;">
                <span>Stock: <strong style="color:${p.current_stock > 0 ? '#4ade80' : '#f87171'}">${p.current_stock}</strong> | Rack: ${escHtml(p.rack_location || '—')}</span>
                <span style="font-weight:600;color:#f1f5f9;">₹${parseFloat(p.selling_price).toFixed(2)}</span>
              </div>
            `;
            row.addEventListener('mousedown', (e) => {
              e.preventDefault(); // prevent blur from hiding dropdown before click fires
              addToCart(p);
              document.getElementById('posProdSearch').value = '';
              document.getElementById('posSearchDropdown').style.display = 'none';
            });
            resultsDiv.appendChild(row);
          });
          document.getElementById('posSearchDropdown').style.display = 'block';
        } else {
          resultsDiv.innerHTML = '<div style="padding:14px;text-align:center;color:#64748b;font-size:0.85rem;"><i class="bi bi-search" style="display:block;font-size:1.5rem;margin-bottom:6px;"></i>No products found</div>';
          document.getElementById('posSearchDropdown').style.display = 'block';
        }
      } catch (err) {
        console.error('Product search error:', err);
      }
    }, 300);
  });

  // Hide dropdown when input loses focus (with small delay to allow mousedown click)
  document.getElementById('posProdSearch').addEventListener('blur', function () {
    setTimeout(() => {
      document.getElementById('posSearchDropdown').style.display = 'none';
    }, 200);
  });

  // Close dropdown on click outside
  document.addEventListener('click', function (e) {
    if (!e.target.closest('#posSearchDropdown') && e.target.id !== 'posProdSearch') {
      document.getElementById('posSearchDropdown').style.display = 'none';
    }
  });

  // ── Add to cart ──────────────────────────────────────────────────────────
  function addToCart(p) {
    if (cartProducts.has(p.id)) {
      // Increase quantity
      const tr = document.getElementById(`cart-row-${p.id}`);
      const qtyInput = tr.querySelector('.cart-qty');
      const newQty = parseInt(qtyInput.value) + 1;
      if (newQty > parseInt(qtyInput.max)) {
        App.toast('warning', `Only ${qtyInput.max} units in stock.`);
        return;
      }
      qtyInput.value = newQty;
      posRecalc();
      return;
    }

    const emptyRow = document.getElementById('posEmptyRow');
    if (emptyRow) emptyRow.remove();

    cartProducts.add(p.id);
    const body = document.getElementById('posCartBody');
    const tr = document.createElement('tr');
    tr.id = `cart-row-${p.id}`;
    tr.style.borderBottom = '1px solid #1e293b';
    tr.innerHTML = `
      <td style="padding:10px 12px;">
        <strong style="display:block;color:#f1f5f9;">${escHtml(p.product_name)}</strong>
        <code style="font-size:0.72rem;color:#64748b;">${escHtml(p.product_code)}</code>
        <input type="hidden" class="cart-prod-id" value="${p.id}">
      </td>
      <td style="padding:10px 8px;text-align:center;font-size:0.85rem;color:${p.current_stock > 5 ? '#4ade80' : '#f59e0b'};">
        ${p.current_stock}
      </td>
      <td style="padding:6px 8px;">
        <input type="number" class="form-control cart-qty" value="1" min="1" max="${p.current_stock}"
          style="text-align:center;padding:4px 6px;width:70px;" oninput="posRecalc()">
      </td>
      <td style="padding:6px 8px;">
        <input type="number" step="0.01" class="form-control cart-price" value="${p.selling_price}"
          style="text-align:right;padding:4px 6px;width:90px;" oninput="posRecalc()">
      </td>
      <td style="padding:6px 8px;">
        <input type="number" step="0.01" class="form-control cart-gst" value="${p.gst_percentage}"
          style="text-align:right;padding:4px 6px;width:56px;" readonly>
      </td>
      <td style="padding:6px 8px;">
        <input type="number" step="0.01" class="form-control cart-disc" value="0.00" min="0"
          style="text-align:right;padding:4px 6px;width:80px;" oninput="posRecalc()">
      </td>
      <td style="padding:10px 12px;text-align:right;">
        <strong class="cart-total" style="font-size:0.95rem;">₹0.00</strong>
      </td>
      <td style="padding:6px 8px;text-align:center;">
        <button type="button" class="btn btn-icon btn-sm"
          onclick="removeCartRow(${p.id})"
          style="border:0;background:transparent;color:#f87171;font-size:1.1rem;padding:2px 6px;cursor:pointer;"
          title="Remove">
          <i class="bi bi-x-circle"></i>
        </button>
      </td>
    `;
    body.appendChild(tr);
    posRecalc();
  }

  function removeCartRow(id) {
    const row = document.getElementById(`cart-row-${id}`);
    if (row) row.remove();
    cartProducts.delete(id);

    const body = document.getElementById('posCartBody');
    if (body.children.length === 0) {
      const tr = document.createElement('tr');
      tr.id = 'posEmptyRow';
      tr.innerHTML = `<td colspan="8" style="padding:48px;text-align:center;color:#64748b;font-size:0.9rem;">
        <i class="bi bi-cart-x" style="font-size:2rem;display:block;margin-bottom:8px;opacity:0.4;"></i>
        Cart is empty — search for products above or scan a barcode to add items.
      </td>`;
      body.appendChild(tr);
    }
    posRecalc();
  }

  // ── Recalculate totals ───────────────────────────────────────────────────
  function posRecalc() {
    let subtotal = 0;
    let gstTotal = 0;
    let itemDiscTotal = 0;

    document.querySelectorAll('#posCartBody tr').forEach(tr => {
      if (tr.id === 'posEmptyRow') return;

      const qty   = parseInt(tr.querySelector('.cart-qty').value || 0);
      const price = parseFloat(tr.querySelector('.cart-price').value || 0);
      const gstP  = parseFloat(tr.querySelector('.cart-gst').value || 0);
      const disc  = parseFloat(tr.querySelector('.cart-disc').value || 0);

      const rawTotal = qty * price;
      const gstAmt   = rawTotal * (gstP / 100);
      const lineTotal = rawTotal + gstAmt - disc;

      tr.querySelector('.cart-total').textContent = App.formatCurrency(lineTotal);

      subtotal     += rawTotal;
      gstTotal     += gstAmt;
      itemDiscTotal += disc;
    });

    const invoiceDisc = parseFloat(document.getElementById('posDiscount').value || 0);
    grandTotalVal = subtotal + gstTotal - itemDiscTotal - invoiceDisc;
    if (grandTotalVal < 0) grandTotalVal = 0;

    document.getElementById('posSubtotal').textContent = App.formatCurrency(subtotal);
    document.getElementById('posGst').textContent      = App.formatCurrency(gstTotal);
    document.getElementById('posGrand').textContent    = App.formatCurrency(grandTotalVal);

    // Keep paid amount in sync unless credit mode
    const pm = document.getElementById('posPaymentMethod').value;
    if (pm !== 'credit') {
      document.getElementById('posPaidAmt').value = grandTotalVal.toFixed(2);
    }
    calculateChange();
  }

  function calculateChange() {
    const paid  = parseFloat(document.getElementById('posPaidAmt').value || 0);
    const diff  = paid - grandTotalVal;
    const textEl  = document.getElementById('posChangeText');
    const labelEl = document.getElementById('changeDivLabel');

    if (diff >= 0) {
      labelEl.textContent = 'Change Due:';
      textEl.textContent  = App.formatCurrency(diff);
      textEl.className    = 'text-success';
    } else {
      labelEl.textContent = 'Balance Outstanding:';
      textEl.textContent  = App.formatCurrency(Math.abs(diff));
      textEl.className    = 'text-danger';
    }
  }

  function onPaymentMethodChange() {
    const pm = document.getElementById('posPaymentMethod').value;
    if (pm === 'credit') {
      document.getElementById('posPaidAmt').value = '0.00';
    } else {
      document.getElementById('posPaidAmt').value = grandTotalVal.toFixed(2);
    }
    calculateChange();
  }

  // ── Customer credit check ─────────────────────────────────────────────────
  function checkCustomerCredit() {
    const select = document.getElementById('posCustomer');
    const option = select.options[select.selectedIndex];
    const infoDiv = document.getElementById('customerInfo');

    if (!option || !option.value) {
      infoDiv.style.display = 'none';
      return;
    }

    const balance = parseFloat(option.dataset.balance || 0);
    const limit   = parseFloat(option.dataset.limit   || 0);

    infoDiv.innerHTML = `
      <span style="margin-right:12px;">Outstanding: <strong class="${balance > 0 ? 'text-danger' : 'text-success'}">${App.formatCurrency(balance)}</strong></span>
      <span>Credit Limit: <strong>${App.formatCurrency(limit)}</strong></span>
    `;
    infoDiv.style.display = 'block';
  }

  // ── Complete Sale ─────────────────────────────────────────────────────────
  async function completeSale() {
    // Validate customer
    const select = document.getElementById('posCustomer');
    if (!select.value) {
      App.toast('error', 'Please select a customer before completing the sale.');
      select.focus();
      return;
    }

    // Validate cart
    if (cartProducts.size === 0) {
      App.toast('error', 'Cart is empty. Add products to complete the sale.');
      return;
    }

    // Build items array
    const items = [];
    let quantityError = false;

    document.querySelectorAll('#posCartBody tr').forEach(tr => {
      if (tr.id === 'posEmptyRow') return;

      const qty    = parseInt(tr.querySelector('.cart-qty').value);
      const maxQty = parseInt(tr.querySelector('.cart-qty').getAttribute('max'));

      if (qty > maxQty) {
        quantityError = true;
      }

      items.push({
        product_id:     parseInt(tr.querySelector('.cart-prod-id').value),
        quantity:       qty,
        price:          parseFloat(tr.querySelector('.cart-price').value),
        gst_percentage: parseFloat(tr.querySelector('.cart-gst').value),
        discount:       parseFloat(tr.querySelector('.cart-disc').value)
      });
    });

    if (quantityError) {
      App.toast('error', 'One or more items exceed available stock levels.');
      return;
    }

    // Credit limit check
    const option        = select.options[select.selectedIndex];
    const balance       = parseFloat(option.dataset.balance || 0);
    const limit         = parseFloat(option.dataset.limit   || 0);
    const paidAmount    = parseFloat(document.getElementById('posPaidAmt').value || 0);
    const creditRequired = grandTotalVal - paidAmount;

    if (creditRequired > 0 && limit > 0) {
      const remaining = limit - balance;
      if (creditRequired > remaining) {
        App.toast('error', `Credit limit exceeded! Remaining credit: ${App.formatCurrency(remaining)}, Required: ${App.formatCurrency(creditRequired)}`);
        return;
      }
    }

    // Disable button to prevent double-submit
    const btn = document.getElementById('posPayBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner" style="width:18px;height:18px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;display:inline-block;animation:spin .6s linear infinite;"></span> Processing…';

    const payload = {
      customer_id:    parseInt(select.value),
      date:           document.getElementById('posDate').value,
      discount:       parseFloat(document.getElementById('posDiscount').value || 0),
      paid_amount:    paidAmount,
      payment_method: document.getElementById('posPaymentMethod').value,
      items:          items
    };

    try {
      const r = await App.post('/api/sales', payload);

      if (r.success) {
        App.toast('success', r.message || 'Sale completed! Invoice generating…');
        // Open PDF in new tab (doesn't navigate away from POS)
        window.open(`/sales/${r.sale_id}/pdf`, '_blank');
        // Reset the POS after a short delay
        setTimeout(() => {
          window.location.reload();
        }, 1500);
      } else {
        App.toast('error', r.message || 'Sale failed. Please try again.');
        // Re-enable button so user can retry
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-receipt-cutoff"></i> Pay &amp; Generate Invoice';
      }
    } catch (err) {
      console.error('Sale error:', err);
      App.toast('error', 'Network error. Please check your connection and try again.');
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-receipt-cutoff"></i> Pay &amp; Generate Invoice';
    }
  }

  // ── Utility ───────────────────────────────────────────────────────────────
  function escHtml(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
</script>

<style>
  .pos-search-item:hover {
    background: rgba(59,130,246,0.12);
  }
  .pos-search-item:last-child {
    border-bottom: 0 !important;
  }
  @keyframes spin { to { transform: rotate(360deg); } }
</style>