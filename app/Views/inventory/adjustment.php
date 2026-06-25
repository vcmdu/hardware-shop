<div class="page-header">
  <div><h2><i class="bi bi-sliders text-accent"></i> Inventory Adjustments</h2><div class="breadcrumb">Adjust stock levels due to damage, physical counts, or transfers</div></div>
  <a href="/inventory/ledger" class="btn btn-outline"><i class="bi bi-journal-text"></i> Stock Ledger</a>
</div>

<!-- Tabs -->
<div class="d-flex gap-2" style="margin-bottom:16px; border-bottom: 1px solid #334155; padding-bottom:8px;">
  <button class="btn btn-outline" id="tabNewBtn" onclick="switchTab('new')"><i class="bi bi-plus-circle"></i> Create Stock Adjustment</button>
  <button class="btn btn-outline" id="tabHistoryBtn" onclick="switchTab('history')"><i class="bi bi-clock-history"></i> Adjustment History</button>
</div>

<!-- Tab: New Adjustment -->
<div id="tabNew" class="fade-in">
  <form id="adjForm">
    <div class="row" style="display:flex;gap:16px;margin-bottom:16px;flex-wrap:wrap;">
      <div class="card" style="flex:1;min-width:300px;">
        <div class="card-header"><span class="card-title">Adjustment details</span></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:12px;">
          <div class="form-group">
            <label class="form-label">Adjustment Type <span style="color:var(--danger)">*</span></label>
            <select class="form-select" id="adjType" required>
              <option value="adjustment">General Stock Adjustment</option>
              <option value="damaged">Damaged Stock Write-off</option>
              <option value="physical_verification">Physical Verification / Reconciliation</option>
              <option value="transfer">Stock Transfer</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Adjustment Date</label>
            <input type="date" class="form-control" id="adjDate" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Description / Reason Notes</label>
            <textarea class="form-control" id="adjDescription" placeholder="Explain why the adjustment is being made..."></textarea>
          </div>
        </div>
      </div>

      <div class="card" style="flex:1;min-width:300px;">
        <div class="card-header"><span class="card-title">Add Product to Adjust</span></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:12px;">
          <div class="form-group">
            <label class="form-label">Select Product</label>
            <select class="form-select" id="adjProdSelect" onchange="addProductToAdj()">
              <option value="">-- Select Active Product --</option>
              <?php foreach ($products as $p): ?>
                <option value="<?= $p['id'] ?>" data-name="<?= htmlspecialchars($p['product_name']) ?>" data-code="<?= $p['product_code'] ?>" data-stock="<?= $p['current_stock'] ?>" data-unit="<?= $p['unit'] ?>">
                  <?= htmlspecialchars($p['product_name']) ?> (Code: <?= $p['product_code'] ?> | Stock: <?= $p['current_stock'] ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="text-muted" style="font-size:0.8rem;"><i class="bi bi-info-circle"></i> Selecting a product adds a row to the table below, where you can modify the final stock level.</div>
        </div>
      </div>
    </div>

    <div class="card" style="margin-bottom:16px;">
      <div class="card-header"><span class="card-title">Adjusted Items</span></div>
      <div class="card-body table-wrap" style="padding:0;">
        <table class="data-table display" style="margin:0;width:100%;">
          <thead>
            <tr>
              <th>Product</th>
              <th width="120">Current Stock</th>
              <th width="150">Physical/Target Stock <span style="color:var(--danger)">*</span></th>
              <th width="120">Adjusted Qty</th>
              <th>Reason Note</th>
              <th width="60"></th>
            </tr>
          </thead>
          <tbody id="adjItemsBody">
            <tr id="adjEmptyRow"><td colspan="6" class="text-center text-muted" style="padding:24px;">No products added yet. Select a product above.</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="d-flex" style="justify-content:flex-end;">
      <button type="submit" class="btn btn-primary" style="padding:12px 24px;"><i class="bi bi-check-circle"></i> Record Stock Adjustment</button>
    </div>
  </form>
</div>

<!-- Tab: History -->
<div id="tabHistory" class="fade-in" style="display:none;">
  <div class="card">
    <div class="card-header"><span class="card-title">Stock Adjustments History</span></div>
    <div class="card-body table-wrap">
      <table id="historyTable" class="data-table display responsive" style="width:100%;">
        <thead>
          <tr>
            <th>Reference #</th>
            <th>Type</th>
            <th>Date</th>
            <th>Description</th>
            <th>Recorded By</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<!-- Details Modal -->
<div class="modal-overlay" id="detailsModal">
  <div class="modal-box" style="max-width:700px;">
    <div class="modal-header">
      <h3 class="modal-title" id="detailsTitle">Adjustment Details</h3>
      <button class="modal-close">&times;</button>
    </div>
    <div class="modal-body">
      <div style="display:flex;justify-content:between;margin-bottom:16px;font-size:0.9rem;border-bottom:1px solid #334155;padding-bottom:12px;">
        <div>
          <div>Type: <strong id="detType"></strong></div>
          <div>Date: <strong id="detDate"></strong></div>
        </div>
        <div style="text-align:right;">
          <div>Created By: <strong id="detCreator"></strong></div>
          <div id="detDesc" class="text-muted"></div>
        </div>
      </div>
      <table class="data-table display" style="width:100%;margin:0;">
        <thead>
          <tr>
            <th>Code</th>
            <th>Product Name</th>
            <th>Stock Before</th>
            <th>Stock After</th>
            <th>Net Adjustment</th>
            <th>Reason</th>
          </tr>
        </thead>
        <tbody id="detItemsBody"></tbody>
      </table>
    </div>
    <div class="d-flex" style="justify-content:flex-end;margin-top:16px;">
      <button class="btn btn-outline modal-close">Close</button>
    </div>
  </div>
</div>

<script>
const addedProducts = new Set();
let historyDT;

function switchTab(tab) {
  if (tab === 'new') {
    document.getElementById('tabNew').style.display = 'block';
    document.getElementById('tabHistory').style.display = 'none';
    document.getElementById('tabNewBtn').className = 'btn btn-primary';
    document.getElementById('tabHistoryBtn').className = 'btn btn-outline';
  } else {
    document.getElementById('tabNew').style.display = 'none';
    document.getElementById('tabHistory').style.display = 'block';
    document.getElementById('tabNewBtn').className = 'btn btn-outline';
    document.getElementById('tabHistoryBtn').className = 'btn btn-primary';
    loadHistory();
  }
}

function addProductToAdj() {
  const select = document.getElementById('adjProdSelect');
  const option = select.options[select.selectedIndex];
  if (!option || option.value === '') return;
  
  const id = parseInt(option.value);
  const name = option.dataset.name;
  const code = option.dataset.code;
  const stock = parseInt(option.dataset.stock);
  const unit = option.dataset.unit;
  
  if (addedProducts.has(id)) {
    App.toast('warning', 'Product already added.');
    select.value = '';
    return;
  }
  
  const emptyRow = document.getElementById('adjEmptyRow');
  if (emptyRow) emptyRow.remove();
  
  addedProducts.add(id);
  const body = document.getElementById('adjItemsBody');
  const tr = document.createElement('tr');
  tr.id = `adj-row-${id}`;
  tr.innerHTML = `
    <td>
      <strong>${name}</strong><br><code style="font-size:0.75rem;">${code}</code>
      <input type="hidden" class="adj-prod-id" value="${id}">
    </td>
    <td><span class="adj-current">${stock}</span> ${unit}</td>
    <td>
      <input type="number" class="form-control adj-physical" value="${stock}" style="text-align:center;" oninput="recalcAdj(${id})">
    </td>
    <td>
      <strong class="adj-net" style="display:block;text-align:center;">0</strong>
    </td>
    <td>
      <input type="text" class="form-control adj-reason" placeholder="e.g. Broken packaging">
    </td>
    <td>
      <button type="button" class="btn btn-outline btn-sm btn-icon" onclick="removeAdjRow(${id})" style="border:0;"><i class="bi bi-x-circle text-danger"></i></button>
    </td>
  `;
  body.appendChild(tr);
  select.value = '';
}

function removeAdjRow(id) {
  document.getElementById(`adj-row-${id}`).remove();
  addedProducts.delete(id);
  
  const body = document.getElementById('adjItemsBody');
  if (body.children.length === 0) {
    const tr = document.createElement('tr');
    tr.id = 'adjEmptyRow';
    tr.innerHTML = `<td colspan="6" class="text-center text-muted" style="padding:24px;">No products added yet. Select a product above.</td>`;
    body.appendChild(tr);
  }
}

function recalcAdj(id) {
  const tr = document.getElementById(`adj-row-${id}`);
  const current = parseInt(tr.querySelector('.adj-current').textContent);
  const physical = parseInt(tr.querySelector('.adj-physical').value || 0);
  const diff = physical - current;
  
  const netEl = tr.querySelector('.adj-net');
  netEl.textContent = (diff > 0 ? '+' : '') + diff;
  if(diff > 0) netEl.className = 'adj-net text-success';
  else if(diff < 0) netEl.className = 'adj-net text-danger';
  else netEl.className = 'adj-net';
}

document.getElementById('adjForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  
  if (addedProducts.size === 0) {
    App.toast('error', 'Please add at least one product to adjust.');
    return;
  }
  
  const items = [];
  document.querySelectorAll('#adjItemsBody tr').forEach(tr => {
    if (tr.id === 'adjEmptyRow') return;
    
    items.push({
      product_id: parseInt(tr.querySelector('.adj-prod-id').value),
      quantity_after: parseInt(tr.querySelector('.adj-physical').value),
      reason: tr.querySelector('.adj-reason').value.trim()
    });
  });
  
  const payload = {
    type: document.getElementById('adjType').value,
    date: document.getElementById('adjDate').value,
    description: document.getElementById('adjDescription').value.trim(),
    items: items
  };
  
  const r = await App.post('/api/inventory/adjustment', payload);
  App.toast(r.success ? 'success' : 'error', r.message);
  if (r.success) {
    setTimeout(() => {
      window.location.href = '/inventory/ledger';
    }, 1000);
  }
});

async function loadHistory() {
  const r = await App.get('/api/inventory/adjustments');
  if(historyDT){ historyDT.clear().rows.add(r.data || []).draw(); return; }
  historyDT = $('#historyTable').DataTable(App.dtDefaults({
    data: r.data || [],
    columns:[
      {data:'reference_number', render:v=>`<code>${v}</code>`},
      {data:'type', render:v=>`<span class="badge badge-outline">${v.replace('_',' ').toUpperCase()}</span>`},
      {data:'date'},
      {data:'description', defaultContent:'<span class="text-muted">—</span>'},
      {data:'creator_name', render:v=>`<strong>${v}</strong>`},
      {data:null, orderable:false, render:(d,t,r)=>`
        <button class="btn btn-outline btn-sm" onclick="viewDetails(${r.id},'${r.reference_number}','${r.type}','${r.date}','${r.creator_name}','${(r.description||'').replace(/'/g,"\\'").replace(/\n/g,"\\n")}')"><i class="bi bi-eye"></i> View</button>`
      }
    ]
  }));
}

async function viewDetails(id, ref, type, date, creator, desc) {
  document.getElementById('detailsTitle').textContent = `Adjustment: ${ref}`;
  document.getElementById('detType').textContent = type.replace('_',' ').toUpperCase();
  document.getElementById('detDate').textContent = date;
  document.getElementById('detCreator').textContent = creator;
  document.getElementById('detDesc').textContent = desc || 'No notes';
  
  const r = await App.get(`/api/inventory/adjustments?id=${id}`);
  const body = document.getElementById('detItemsBody');
  body.innerHTML = '';
  
  if (r.data && r.data.length > 0) {
    r.data.forEach(item => {
      const tr = document.createElement('tr');
      const net = parseInt(item.quantity_adjusted);
      const netText = (net > 0 ? '+' : '') + net;
      const netClass = net > 0 ? 'text-success' : (net < 0 ? 'text-danger' : '');
      
      tr.innerHTML = `
        <td><code>${item.product_code}</code></td>
        <td><strong>${item.product_name}</strong> <small class="text-muted">(${item.unit})</small></td>
        <td>${item.quantity_before}</td>
        <td>${item.quantity_after}</td>
        <td><strong class="${netClass}">${netText}</strong></td>
        <td>${item.reason || '<span class="text-muted">—</span>'}</td>
      `;
      body.appendChild(tr);
    });
  } else {
    body.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No items found.</td></tr>';
  }
  
  App.openModal('detailsModal');
}
</script>
