<div class="page-header">
  <div><h2><i class="bi bi-graph-up-arrow text-accent"></i> Financial Reports</h2><div class="breadcrumb">Review company income, purchase expenses, profits, and GST taxes</div></div>
</div>

<div class="card" style="margin-bottom:16px;">
  <div class="card-body" style="padding:16px;">
    <form id="finReportForm" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
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
    <div class="stat-title">Gross Revenue (Sales)</div>
    <div class="stat-value text-success" id="cardRev">₹0.00</div>
    <div class="stat-desc">Invoice totals excluding tax</div>
  </div>
  <div class="stat-card" style="flex:1;min-width:200px;">
    <div class="stat-title">Procurement Cost (Purchases)</div>
    <div class="stat-value text-danger" id="cardCost">₹0.00</div>
    <div class="stat-desc">Cost of approved POs</div>
  </div>
  <div class="stat-card" style="flex:1;min-width:200px;">
    <div class="stat-title">Estimated Net Profit</div>
    <div class="stat-value text-primary" id="cardProfit">₹0.00</div>
    <div class="stat-desc">Revenue minus Purchase Costs</div>
  </div>
  <div class="stat-card" style="flex:1;min-width:200px;">
    <div class="stat-title">GST Tax Collected</div>
    <div class="stat-value" style="color:var(--accent);" id="cardGst">₹0.00</div>
    <div class="stat-desc">Total output GST</div>
  </div>
</div>

<div class="row" style="display:flex;gap:16px;flex-wrap:wrap;">
  <!-- Visual Profit Chart -->
  <div class="card" style="flex:1.5;min-width:350px;margin:0;">
    <div class="card-header"><span class="card-title"><i class="bi bi-pie-chart"></i> Revenue vs procurement Cost</span></div>
    <div class="card-body">
      <canvas id="finChart" style="max-height:300px;width:100%;"></canvas>
    </div>
  </div>

  <!-- GST Breakdown -->
  <div class="card" style="flex:1;min-width:300px;margin:0;">
    <div class="card-header"><span class="card-title"><i class="bi bi-receipt"></i> Monthly GST Breakdown</span></div>
    <div class="card-body table-wrap" style="padding:0;">
      <table class="data-table display" id="gstRepTable" style="margin:0;width:100%;font-size:0.85rem;">
        <thead>
          <tr>
            <th>Month</th>
            <th style="text-align:right;">GST Collected</th>
          </tr>
        </thead>
        <tbody id="gstRepBody"></tbody>
      </table>
    </div>
  </div>
</div>

<script>
let finChartInstance = null;

async function loadFinancialReport() {
  const from = document.getElementById('repFrom').value;
  const to = document.getElementById('repTo').value;
  
  const r = await App.get(`/api/reports/financial?from=${from}&to=${to}`);
  const d = r.data || {};
  
  document.getElementById('cardRev').textContent = App.formatCurrency(d.revenue || 0);
  document.getElementById('cardCost').textContent = App.formatCurrency(d.cost || 0);
  
  const profit = d.profit || 0;
  const profitEl = document.getElementById('cardProfit');
  profitEl.textContent = App.formatCurrency(profit);
  if(profit >= 0) {
    profitEl.className = 'stat-value text-success';
  } else {
    profitEl.className = 'stat-value text-danger';
  }
  
  document.getElementById('cardGst').textContent = App.formatCurrency(d.gst_collected || 0);
  
  // Render GST Table
  const tbody = document.getElementById('gstRepBody');
  tbody.innerHTML = '';
  const gstData = d.gst_breakdown || [];
  
  const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
  
  gstData.forEach(row => {
    const monthNum = parseInt(row.month_num);
    const monthName = months[monthNum - 1] || `Month ${monthNum}`;
    const gstVal = parseFloat(row.gst_collected);
    
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><strong>${monthName}</strong></td>
      <td style="text-align:right;">${App.formatCurrency(gstVal)}</td>
    `;
    tbody.appendChild(tr);
  });
  
  if (gstData.length === 0) {
    tbody.innerHTML = '<tr><td colspan="2" class="text-center text-muted">No tax data found.</td></tr>';
  }
  
  // Render Pie Chart
  renderFinChart(d.revenue || 0, d.cost || 0);
}

function renderFinChart(rev, cost) {
  const ctx = document.getElementById('finChart').getContext('2d');
  if (finChartInstance) {
    finChartInstance.destroy();
  }
  finChartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: ['Revenue (Sales)', 'Procurement Cost'],
      datasets: [{
        label: 'Financial Flow (₹)',
        data: [rev, cost],
        backgroundColor: ['#10b981', '#ef4444'],
        borderWidth: 0,
        borderRadius: 8
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false }
      },
      scales: {
        x: { grid: { display: false }, ticks: { color: '#94a3b8' } },
        y: { grid: { color: 'rgba(255, 255, 255, 0.05)' }, ticks: { color: '#94a3b8' } }
      }
    }
  });
}

document.getElementById('finReportForm').addEventListener('submit', function(e) {
  e.preventDefault();
  loadFinancialReport();
});

loadFinancialReport();
</script>
