<div class="page-header">
  <div><h2><i class="bi bi-clipboard-data text-accent"></i> Inventory Analytics</h2><div class="breadcrumb">Review stock valuations, dead stock, and fast-moving items</div></div>
</div>

<div class="card" style="margin-bottom:16px;">
  <div class="card-body" style="padding:16px;">
    <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
      <span style="font-weight:600;">Report Type:</span>
      <button class="btn btn-primary" id="btn_current" onclick="loadInvReport('current')"><i class="bi bi-box"></i> Stock Status</button>
      <button class="btn btn-outline" id="btn_valuation" onclick="loadInvReport('valuation')"><i class="bi bi-currency-dollar"></i> Valuation Report</button>
      <button class="btn btn-outline" id="btn_dead_stock" onclick="loadInvReport('dead_stock')"><i class="bi bi-exclamation-octagon"></i> Dead Stock (90 Days)</button>
      <button class="btn btn-outline" id="btn_fast_moving" onclick="loadInvReport('fast_moving')"><i class="bi bi-lightning-charge"></i> Fast Moving (30 Days)</button>
      
      <div style="margin-left:auto; display:flex; gap:8px;">
        <button class="btn btn-outline btn-sm" onclick="exportValuation('excel')"><i class="bi bi-file-earmark-excel"></i> Export Excel</button>
      </div>
    </div>
  </div>
</div>

<!-- Valuation Summary Card (only shown for valuation report) -->
<div class="row" id="valuationSummary" style="display:none; gap:16px; margin-bottom:16px;">
  <div class="stat-card" style="flex:1;">
    <div class="stat-title">Total Inventory Valuation (Cost Price)</div>
    <div class="stat-value text-primary" id="totalValCost">₹0.00</div>
    <div class="stat-desc">Calculated as: current stock * purchase price</div>
  </div>
  <div class="stat-card" style="flex:1;">
    <div class="stat-title">Potential Retail Value (Selling Price)</div>
    <div class="stat-value text-success" id="totalValRetail">₹0.00</div>
    <div class="stat-desc">Calculated as: current stock * selling price</div>
  </div>
</div>

<div class="card">
  <div class="card-header"><span class="card-title" id="tableTitle">Inventory Status</span></div>
  <div class="card-body table-wrap" id="tableContainer">
    <!-- Dynamic table will be drawn here -->
  </div>
</div>

<script>
let invDT;
let currentType = 'current';

async function loadInvReport(type) {
  currentType = type;
  
  // Update button highlights
  ['current', 'valuation', 'dead_stock', 'fast_moving'].forEach(t => {
    const btn = document.getElementById(`btn_${t}`);
    if (t === type) {
      btn.className = 'btn btn-primary';
    } else {
      btn.className = 'btn btn-outline';
    }
  });

  const summaryDiv = document.getElementById('valuationSummary');
  if (type === 'valuation') {
    summaryDiv.style.display = 'flex';
  } else {
    summaryDiv.style.display = 'none';
  }
  
  const r = await App.get(`/api/reports/inventory?type=${type}`);
  const data = r.data || [];
  
  const container = document.getElementById('tableContainer');
  container.innerHTML = '';
  
  if (type === 'valuation') {
    document.getElementById('tableTitle').textContent = 'Inventory Valuation Details';
    
    let totalCost = 0;
    let totalRetail = 0;
    data.forEach(row => {
      totalCost += parseFloat(row.stock_value || 0);
      totalRetail += parseFloat(row.sale_value || 0);
    });
    document.getElementById('totalValCost').textContent = App.formatCurrency(totalCost);
    document.getElementById('totalValRetail').textContent = App.formatCurrency(totalRetail);

    container.innerHTML = `
      <table id="invRepTable" class="data-table display responsive" style="width:100%;">
        <thead>
          <tr>
            <th>Code</th>
            <th>Product Name</th>
            <th>Stock Level</th>
            <th style="text-align:right;">Purchase Price (₹)</th>
            <th style="text-align:right;">Valuation Cost (₹)</th>
            <th style="text-align:right;">Selling Price (₹)</th>
            <th style="text-align:right;">Retail Value (₹)</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    `;
    
    invDT = $('#invRepTable').DataTable(App.dtDefaults({
      data: data,
      columns: [
        {data: 'product_code', render: v => `<code>${v}</code>`},
        {data: 'product_name', render: v => `<strong>${v}</strong>`},
        {data: 'current_stock'},
        {data: 'purchase_price', render: v => App.formatCurrency(v), className: 'text-right'},
        {data: 'stock_value', render: v => App.formatCurrency(v), className: 'text-right'},
        {data: 'selling_price', render: v => App.formatCurrency(v), className: 'text-right'},
        {data: 'sale_value', render: v => App.formatCurrency(v), className: 'text-right'}
      ]
    }));
    
  } else if (type === 'dead_stock') {
    document.getElementById('tableTitle').textContent = 'Dead Stock Items (No Sales in Past 90 Days)';
    
    container.innerHTML = `
      <table id="invRepTable" class="data-table display responsive" style="width:100%;">
        <thead>
          <tr>
            <th>Code</th>
            <th>Product Name</th>
            <th>Brand</th>
            <th>Stock Level</th>
            <th>Purchase Price (₹)</th>
            <th>Last Sold (90 Days)</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    `;
    
    invDT = $('#invRepTable').DataTable(App.dtDefaults({
      data: data,
      columns: [
        {data: 'product_code', render: v => `<code>${v}</code>`},
        {data: 'product_name', render: v => `<strong>${v}</strong>`},
        {data: 'brand', defaultContent: '<span class="text-muted">—</span>'},
        {data: 'current_stock'},
        {data: 'purchase_price', render: v => App.formatCurrency(v)},
        {data: null, render: () => `0 units sold`, className: 'text-danger'}
      ]
    }));
    
  } else if (type === 'fast_moving') {
    document.getElementById('tableTitle').textContent = 'Fast Moving Items (Top Sales Past 30 Days)';
    
    container.innerHTML = `
      <table id="invRepTable" class="data-table display responsive" style="width:100%;">
        <thead>
          <tr>
            <th>Code</th>
            <th>Product Name</th>
            <th>Units Sold (30 Days)</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    `;
    
    invDT = $('#invRepTable').DataTable(App.dtDefaults({
      data: data,
      order: [[2, 'desc']],
      columns: [
        {data: 'product_code', render: v => `<code>${v}</code>`},
        {data: 'product_name', render: v => `<strong>${v}</strong>`},
        {data: 'sold_qty', render: v => `<strong class="text-success">${v} units</strong>`}
      ]
    }));
    
  } else {
    // Current stock status
    document.getElementById('tableTitle').textContent = 'Current Stock Status';
    
    container.innerHTML = `
      <table id="invRepTable" class="data-table display responsive" style="width:100%;">
        <thead>
          <tr>
            <th>Code</th>
            <th>Product Name</th>
            <th>Category</th>
            <th>Brand</th>
            <th>Stock Level</th>
            <th>Min Stock</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    `;
    
    invDT = $('#invRepTable').DataTable(App.dtDefaults({
      data: data,
      columns: [
        {data: 'product_code', render: v => `<code>${v}</code>`},
        {data: 'product_name', render: v => `<strong>${v}</strong>`},
        {data: 'category_name', defaultContent: '<span class="text-muted">—</span>'},
        {data: 'brand', defaultContent: '<span class="text-muted">—</span>'},
        {data: 'current_stock', render: (v, t, r) => {
           let cls = '';
           if(parseInt(v) <= 0) cls = 'text-danger font-bold';
           else if(parseInt(v) <= parseInt(r.minimum_stock)) cls = 'text-warning font-bold';
           return `<span class="${cls}">${v} ${r.unit}</span>`;
        }},
        {data: 'minimum_stock'},
        {data: 'status', render: v => `<span class="badge badge-${v==='active'?'success':'muted'}">${v}</span>`}
      ]
    }));
  }
}

function exportValuation(format) {
  window.location.href = `/reports/export?type=inventory&format=${format}`;
}

loadInvReport('current');
</script>
