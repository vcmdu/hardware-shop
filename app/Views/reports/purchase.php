<div class="page-header">
  <div><h2><i class="bi bi-file-earmark-bar-graph text-accent"></i> Purchase Analytics</h2><div class="breadcrumb">Audit supplier procurements, costs, and statuses</div></div>
</div>

<div class="card" style="margin-bottom:16px;">
  <div class="card-body" style="padding:16px;">
    <form id="purchReportForm" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
      <div class="form-group" style="margin:0;flex:1;min-width:140px;">
        <label class="form-label">From Date</label>
        <input type="date" class="form-control" id="repFrom" value="<?= date('Y-m-01') ?>">
      </div>
      <div class="form-group" style="margin:0;flex:1;min-width:140px;">
        <label class="form-label">To Date</label>
        <input type="date" class="form-control" id="repTo" value="<?= date('Y-m-d') ?>">
      </div>
      <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Apply Filter</button>
    </form>
  </div>
</div>

<div class="row" style="display:flex;gap:16px;margin-bottom:16px;flex-wrap:wrap;">
  <div class="stat-card" style="flex:1;min-width:200px;">
    <div class="stat-title">Total Purchase Cost</div>
    <div class="stat-value text-primary" id="cardSpend">₹0.00</div>
    <div class="stat-desc">Total procurement value</div>
  </div>
  <div class="stat-card" style="flex:1;min-width:200px;">
    <div class="stat-title">Approved Orders</div>
    <div class="stat-value text-success" id="cardApproved">0</div>
    <div class="stat-desc">Approved inventory receipts</div>
  </div>
  <div class="stat-card" style="flex:1;min-width:200px;">
    <div class="stat-title">Pending Orders</div>
    <div class="stat-value text-warning" id="cardPending">0</div>
    <div class="stat-desc">Awaiting approval</div>
  </div>
</div>

<div class="card">
  <div class="card-header"><span class="card-title">Purchase Orders Summary</span></div>
  <div class="card-body table-wrap">
    <table id="purchRepTable" class="data-table display responsive" style="width:100%;">
      <thead>
        <tr>
          <th>PO Number</th>
          <th>Supplier</th>
          <th>Order Date</th>
          <th>Status</th>
          <th style="text-align:right;">Grand Total</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<script>
let purchRepDT;

async function loadPurchaseReport() {
  const from = document.getElementById('repFrom').value;
  const to = document.getElementById('repTo').value;
  
  const r = await App.get(`/api/reports/purchase?from=${from}&to=${to}`);
  const data = r.data || [];
  
  let totalSpend = 0;
  let approvedCount = 0;
  let pendingCount = 0;
  
  data.forEach(row => {
    const total = parseFloat(row.grand_total);
    if(row.status === 'approved') {
      totalSpend += total;
      approvedCount++;
    } else if(row.status === 'pending') {
      pendingCount++;
    }
  });
  
  document.getElementById('cardSpend').textContent = App.formatCurrency(totalSpend);
  document.getElementById('cardApproved').textContent = approvedCount;
  document.getElementById('cardPending').textContent = pendingCount;
  
  if (purchRepDT) {
    purchRepDT.clear().rows.add(data).draw();
  } else {
    purchRepDT = $('#purchRepTable').DataTable(App.dtDefaults({
      data: data,
      columns: [
        {data: 'purchase_number', render: v => `<code>${v}</code>`},
        {data: 'supplier_name', render: v => `<strong>${v}</strong>`},
        {data: 'date'},
        {data: 'status', render: v => {
           let cls = 'muted';
           if(v==='approved') cls = 'success';
           if(v==='pending') cls = 'warning';
           if(v==='returned') cls = 'danger';
           return `<span class="badge badge-${cls}">${v.toUpperCase()}</span>`;
        }},
        {data: 'grand_total', render: v => App.formatCurrency(v), className: 'text-right'}
      ]
    }));
  }
}

document.getElementById('purchReportForm').addEventListener('submit', function(e) {
  e.preventDefault();
  loadPurchaseReport();
});

loadPurchaseReport();
</script>
