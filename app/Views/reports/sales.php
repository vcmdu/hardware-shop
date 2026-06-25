<div class="page-header">
  <div><h2><i class="bi bi-bar-chart-line text-accent"></i> Sales Analytics</h2><div class="breadcrumb">Analyze revenue trends, tax collections, and metrics</div></div>
</div>

<div class="card" style="margin-bottom:16px;">
  <div class="card-body" style="padding:16px;">
    <form id="reportForm" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
      <div class="form-group" style="margin:0;flex:1;min-width:140px;">
        <label class="form-label">From Date</label>
        <input type="date" class="form-control" id="repFrom" value="<?= date('Y-m-01') ?>">
      </div>
      <div class="form-group" style="margin:0;flex:1;min-width:140px;">
        <label class="form-label">To Date</label>
        <input type="date" class="form-control" id="repTo" value="<?= date('Y-m-d') ?>">
      </div>
      <div class="form-group" style="margin:0;flex:1;min-width:140px;">
        <label class="form-label">Group By</label>
        <select class="form-select" id="repGroup">
          <option value="daily">Daily</option>
          <option value="weekly">Weekly</option>
          <option value="monthly">Monthly</option>
          <option value="yearly">Yearly</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Apply Filter</button>
      <button type="button" class="btn btn-outline" onclick="exportReport('excel')"><i class="bi bi-file-earmark-excel"></i> Export Excel</button>
      <button type="button" class="btn btn-outline" onclick="exportReport('pdf')"><i class="bi bi-file-earmark-pdf"></i> Export PDF</button>
    </form>
  </div>
</div>

<div class="row" style="display:flex;gap:16px;margin-bottom:16px;flex-wrap:wrap;">
  <div class="stat-card" style="flex:1;min-width:200px;">
    <div class="stat-title">Total Sales Revenue</div>
    <div class="stat-value text-primary" id="cardRevenue">₹0.00</div>
    <div class="stat-desc">Net sales in selected range</div>
  </div>
  <div class="stat-card" style="flex:1;min-width:200px;">
    <div class="stat-title">Tax Collected (GST)</div>
    <div class="stat-value" style="color:var(--accent);" id="cardGst">₹0.00</div>
    <div class="stat-desc">Total output tax</div>
  </div>
  <div class="stat-card" style="flex:1;min-width:200px;">
    <div class="stat-title">Discounts Issued</div>
    <div class="stat-value text-danger" id="cardDiscount">₹0.00</div>
    <div class="stat-desc">Total promo write-offs</div>
  </div>
  <div class="stat-card" style="flex:1;min-width:200px;">
    <div class="stat-title">Invoices Count</div>
    <div class="stat-value text-success" id="cardInvoices">0</div>
    <div class="stat-desc">Completed order invoices</div>
  </div>
</div>

<div class="row" style="display:flex;gap:16px;margin-bottom:16px;flex-wrap:wrap;">
  <!-- Chart -->
  <div class="card" style="flex:2;min-width:350px;margin:0;">
    <div class="card-header"><span class="card-title"><i class="bi bi-graph-up"></i> Sales Trend</span></div>
    <div class="card-body">
      <canvas id="salesChart" style="max-height:300px;width:100%;"></canvas>
    </div>
  </div>

  <!-- Data Table -->
  <div class="card" style="flex:1;min-width:300px;margin:0;">
    <div class="card-header"><span class="card-title"><i class="bi bi-table"></i> Breakdown Details</span></div>
    <div class="card-body table-wrap" style="padding:0;">
      <table class="data-table display" id="salesRepTable" style="margin:0;width:100%;font-size:0.85rem;">
        <thead>
          <tr>
            <th>Period</th>
            <th>Orders</th>
            <th style="text-align:right;">Sales (₹)</th>
          </tr>
        </thead>
        <tbody id="salesRepBody"></tbody>
      </table>
    </div>
  </div>
</div>

<script>
let chartInstance = null;

async function loadSalesReport() {
  const from = document.getElementById('repFrom').value;
  const to = document.getElementById('repTo').value;
  const group = document.getElementById('repGroup').value;
  
  const r = await App.get(`/api/reports/sales?from=${from}&to=${to}&group=${group}`);
  const data = r.data || [];
  
  let totalRevenue = 0;
  let totalGst = 0;
  let totalDiscount = 0;
  let totalInvoices = 0;
  
  const labels = [];
  const salesValues = [];
  const tbody = document.getElementById('salesRepBody');
  tbody.innerHTML = '';
  
  data.forEach(row => {
    const period = row.period.substring(0, 10);
    const invoices = parseInt(row.invoice_count);
    const revenue = parseFloat(row.total_sales);
    const gst = parseFloat(row.total_gst);
    const discount = parseFloat(row.total_discount);
    
    totalRevenue += revenue;
    totalGst += gst;
    totalDiscount += discount;
    totalInvoices += invoices;
    
    labels.push(period);
    salesValues.push(revenue);
    
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><strong>${period}</strong></td>
      <td>${invoices}</td>
      <td style="text-align:right;">${App.formatCurrency(revenue)}</td>
    `;
    tbody.appendChild(tr);
  });
  
  if (data.length === 0) {
    tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">No sales data found for the selected range.</td></tr>';
  }
  
  document.getElementById('cardRevenue').textContent = App.formatCurrency(totalRevenue);
  document.getElementById('cardGst').textContent = App.formatCurrency(totalGst);
  document.getElementById('cardDiscount').textContent = App.formatCurrency(totalDiscount);
  document.getElementById('cardInvoices').textContent = totalInvoices;
  
  // Render Chart
  renderChart(labels, salesValues);
}

function renderChart(labels, data) {
  const ctx = document.getElementById('salesChart').getContext('2d');
  if (chartInstance) {
    chartInstance.destroy();
  }
  chartInstance = new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [{
        label: 'Sales Revenue (₹)',
        data: data,
        borderColor: '#3b82f6',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        borderWidth: 2,
        tension: 0.3,
        fill: true
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false }
      },
      scales: {
        x: { grid: { color: 'rgba(255, 255, 255, 0.05)' }, ticks: { color: '#94a3b8' } },
        y: { grid: { color: 'rgba(255, 255, 255, 0.05)' }, ticks: { color: '#94a3b8' } }
      }
    }
  });
}

function exportReport(format) {
  const from = document.getElementById('repFrom').value;
  const to = document.getElementById('repTo').value;
  window.location.href = `/reports/export?type=sales&format=${format}&from=${from}&to=${to}`;
}

document.getElementById('reportForm').addEventListener('submit', function(e) {
  e.preventDefault();
  loadSalesReport();
});

loadSalesReport();
</script>
