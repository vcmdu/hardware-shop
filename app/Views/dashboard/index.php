<div class="page-header">
  <div>
    <h2><i class="bi bi-speedometer2 text-accent"></i> Dashboard</h2>
    <div class="breadcrumb">Welcome back, <?= htmlspecialchars($currentUser['username'] ?? '') ?> &mdash; <?= date('D, d M Y') ?></div>
  </div>
  <a href="/pos" class="btn btn-primary"><i class="bi bi-cart3"></i> Open POS</a>
</div>

<!-- ── Low Stock Alert Banner ── -->
<?php if(($outOfStockCount ?? 0) > 0): ?>
<div class="alert alert-danger pulse-danger" style="margin-bottom:18px;">
  <i class="bi bi-exclamation-triangle-fill"></i>
  <strong><?= $outOfStockCount ?> product(s)</strong> are OUT OF STOCK!
  <a href="/products" class="btn btn-danger btn-sm" style="margin-left:auto;">View Products</a>
</div>
<?php endif; ?>
<?php if(($lowStockCount ?? 0) > 0): ?>
<div class="alert alert-warning" style="margin-bottom:18px;">
  <i class="bi bi-exclamation-circle-fill"></i>
  <strong><?= $lowStockCount ?> product(s)</strong> are running LOW on stock.
  <a href="/products" class="btn btn-warning btn-sm" style="margin-left:auto;">View Products</a>
</div>
<?php endif; ?>

<!-- ── Stat Cards ── -->
<div class="stats-grid">
  <div class="stat-card blue">
    <div class="stat-icon"><i class="bi bi-box"></i></div>
    <div class="stat-value"><?= number_format($totalProducts ?? 0) ?></div>
    <div class="stat-label">Total Products</div>
  </div>
  <div class="stat-card green">
    <div class="stat-icon"><i class="bi bi-tags"></i></div>
    <div class="stat-value"><?= number_format($totalCategories ?? 0) ?></div>
    <div class="stat-label">Categories</div>
  </div>
  <div class="stat-card amber">
    <div class="stat-icon"><i class="bi bi-truck"></i></div>
    <div class="stat-value"><?= number_format($totalSuppliers ?? 0) ?></div>
    <div class="stat-label">Suppliers</div>
  </div>
  <div class="stat-card cyan">
    <div class="stat-icon"><i class="bi bi-people"></i></div>
    <div class="stat-value"><?= number_format($totalCustomers ?? 0) ?></div>
    <div class="stat-label">Customers</div>
  </div>
  <div class="stat-card green">
    <div class="stat-icon"><i class="bi bi-currency-rupee"></i></div>
    <div class="stat-value">₹<?= number_format($todaySales ?? 0, 0) ?></div>
    <div class="stat-label">Today's Sales</div>
  </div>
  <div class="stat-card blue">
    <div class="stat-icon"><i class="bi bi-calendar-month"></i></div>
    <div class="stat-value">₹<?= number_format($monthlySales ?? 0, 0) ?></div>
    <div class="stat-label">Monthly Sales</div>
  </div>
  <div class="stat-card amber">
    <div class="stat-icon"><i class="bi bi-bag-check"></i></div>
    <div class="stat-value">₹<?= number_format($approvedPurchases ?? 0, 0) ?></div>
    <div class="stat-label">Purchase (Approved)</div>
  </div>
  <div class="stat-card red">
    <div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div>
    <div class="stat-value"><?= number_format(($lowStockCount ?? 0) + ($outOfStockCount ?? 0)) ?></div>
    <div class="stat-label">Stock Alerts</div>
  </div>
</div>

<!-- ── Charts Row ── -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:24px;">
  <div class="card">
    <div class="card-header">
      <span class="card-title"><i class="bi bi-graph-up"></i> Monthly Sales Trend (<?= date('Y') ?>)</span>
    </div>
    <div class="card-body">
      <div class="chart-container"><canvas id="salesChart"></canvas></div>
    </div>
  </div>
  <div class="card">
    <div class="card-header">
      <span class="card-title"><i class="bi bi-bar-chart"></i> Top Selling Products</span>
    </div>
    <div class="card-body">
      <div class="chart-container"><canvas id="topChart"></canvas></div>
    </div>
  </div>
</div>

<!-- ── Bottom Row ── -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
  <!-- Recent Sales -->
  <div class="card">
    <div class="card-header">
      <span class="card-title"><i class="bi bi-receipt"></i> Recent Invoices</span>
      <a href="/sales" class="btn btn-outline btn-sm">View All</a>
    </div>
    <div class="card-body" style="padding:0;">
      <table class="data-table">
        <thead><tr><th>Invoice</th><th>Customer</th><th>Amount</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach(($recentSales ?? []) as $s): ?>
          <tr>
            <td><span class="text-accent fw-bold"><?= htmlspecialchars($s['invoice_number']) ?></span></td>
            <td><?= htmlspecialchars($s['customer_name'] ?? 'Walk-In') ?></td>
            <td class="fw-bold">₹<?= number_format($s['grand_total'], 2) ?></td>
            <td><span class="badge badge-<?= $s['payment_status']==='paid'?'success':($s['payment_status']==='partial'?'warning':'danger') ?>"><?= ucfirst($s['payment_status']) ?></span></td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($recentSales)): ?><tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:24px;">No sales yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Low Stock Alert List -->
  <div class="card">
    <div class="card-header">
      <span class="card-title"><i class="bi bi-exclamation-diamond text-warning"></i> Low Stock Items</span>
      <a href="/inventory/adjustment" class="btn btn-outline btn-sm">Adjust</a>
    </div>
    <div class="card-body" style="padding:0;">
      <table class="data-table">
        <thead><tr><th>Product</th><th>Stock</th><th>Min</th><th>Alert</th></tr></thead>
        <tbody>
          <?php foreach(array_merge($outStockItems ?? [], $lowStockItems ?? []) as $p): ?>
          <tr>
            <td>
              <div class="fw-bold fs-sm"><?= htmlspecialchars($p['product_name']) ?></div>
              <div class="fs-sm text-muted"><?= htmlspecialchars($p['product_code']) ?></div>
            </td>
            <td class="fw-bold <?= $p['current_stock']<=0?'text-danger':'text-warning' ?>"><?= $p['current_stock'] ?></td>
            <td><?= $p['minimum_stock'] ?></td>
            <td><span class="badge badge-<?= $p['current_stock']<=0?'danger':'warning' ?>"><?= $p['current_stock']<=0?'Out of Stock':'Low Stock' ?></span></td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($lowStockItems) && empty($outStockItems)): ?>
          <tr><td colspan="4" style="text-align:center;color:var(--success);padding:24px;"><i class="bi bi-check-circle"></i> All stock levels are healthy!</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
const trendData = <?= json_encode($monthlyTrend ?? []) ?>;
const topData   = <?= json_encode($topSelling ?? []) ?>;

Chart.defaults.color = '#94a3b8';
Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';

// Sales Trend Line Chart
new Chart(document.getElementById('salesChart'), {
  type: 'line',
  data: {
    labels: trendData.map(r => r.month_name.trim()),
    datasets: [{
      label: 'Sales (₹)',
      data: trendData.map(r => parseFloat(r.total_sales)),
      borderColor: '#3b82f6',
      backgroundColor: 'rgba(59,130,246,0.08)',
      tension: 0.4, fill: true,
      pointBackgroundColor: '#3b82f6',
      pointRadius: 4, pointHoverRadius: 7,
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { callback: v => '₹'+v.toLocaleString('en-IN') } },
      x: { grid: { display: false } }
    }
  }
});

// Top Products Bar Chart
new Chart(document.getElementById('topChart'), {
  type: 'bar',
  data: {
    labels: topData.map(r => r.product_name.length > 14 ? r.product_name.substring(0,14)+'…' : r.product_name),
    datasets: [{
      label: 'Units Sold',
      data: topData.map(r => parseInt(r.total_qty)),
      backgroundColor: ['#3b82f6','#06b6d4','#10b981','#f59e0b','#ef4444'],
      borderRadius: 8, borderSkipped: false,
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      y: { grid: { color: 'rgba(255,255,255,0.04)' } },
      x: { grid: { display: false } }
    }
  }
});
</script>
