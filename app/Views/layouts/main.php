<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
  <meta name="description" content="Hardware Shop Inventory Management System">
  <title><?= htmlspecialchars($title ?? 'Hardware Shop IMS') ?></title>

  <!-- Icons & Fonts -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">

  <!-- Custom CSS -->
  <link rel="stylesheet" href="/assets/css/style.css">

  <!-- JS Dependencies loaded in head so inline script blocks in view contents execute successfully -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
  <script src="/assets/js/main.js"></script>
</head>

<body>

  <!-- Sidebar Overlay (mobile) -->
  <div id="sidebar-overlay"
    style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;backdrop-filter:blur(4px);"
    onclick="document.getElementById('sidebar').classList.remove('mobile-open');this.style.display='none';"></div>

  <!-- ══ SIDEBAR ════════════════════════════════════════════════ -->
  <nav id="sidebar">
    <div class="sidebar-brand">
      <div class="brand-icon"><i class="bi bi-box-seam"></i></div>
      <!--<div class="brand-icon">
        <img src="/assets/1.png" alt="Amman Logo">
      </div>-->
      <div>
        <h1>AMMAN</h1>
        <span>Inventory System</span>
      </div>
    </div>

    <div class="sidebar-nav">
      <div class="nav-section-title">Overview</div>
      <div class="nav-item">
        <a href="/" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a>
      </div>

      <div class="nav-section-title">Catalogue</div>
      <div class="nav-item">
        <a href="/categories" class="nav-link"><i class="bi bi-tags"></i> Categories</a>
      </div>
      <div class="nav-item">
        <a href="/products" class="nav-link"><i class="bi bi-box"></i> Products</a>
      </div>

      <div class="nav-section-title">Stakeholders</div>
      <div class="nav-item">
        <a href="/suppliers" class="nav-link"><i class="bi bi-truck"></i> Suppliers</a>
      </div>
      <div class="nav-item">
        <a href="/customers" class="nav-link"><i class="bi bi-people"></i> Customers</a>
      </div>

      <div class="nav-section-title">Transactions</div>
      <div class="nav-item">
        <a href="/pos" class="nav-link"><i class="bi bi-cart3"></i> Point of Sale</a>
      </div>
      <div class="nav-item">
        <a href="/sales" class="nav-link"><i class="bi bi-receipt"></i> Sales History</a>
      </div>
      <div class="nav-item">
        <a href="/purchases" class="nav-link"><i class="bi bi-bag-plus"></i> Purchases</a>
      </div>

      <div class="nav-section-title">Inventory</div>
      <div class="nav-item">
        <a href="/inventory/ledger" class="nav-link"><i class="bi bi-journal-text"></i> Stock Ledger</a>
      </div>
      <div class="nav-item">
        <a href="/inventory/adjustment" class="nav-link"><i class="bi bi-sliders"></i> Adjustments</a>
      </div>

      <div class="nav-section-title">Analytics</div>
      <div class="nav-item">
        <a href="/reports/sales" class="nav-link"><i class="bi bi-bar-chart-line"></i> Sales Reports</a>
      </div>
      <div class="nav-item">
        <a href="/reports/purchase" class="nav-link"><i class="bi bi-file-earmark-bar-graph"></i> Purchase Reports</a>
      </div>
      <div class="nav-item">
        <a href="/reports/inventory" class="nav-link"><i class="bi bi-clipboard-data"></i> Inventory Reports</a>
      </div>
      <div class="nav-item">
        <a href="/reports/financial" class="nav-link"><i class="bi bi-graph-up-arrow"></i> Financial Reports</a>
      </div>

      <?php if (in_array($currentUser['role'] ?? '', ['super_admin', 'admin'])): ?>
        <div class="nav-section-title">Admin</div>
        <div class="nav-item">
          <a href="/audit" class="nav-link"><i class="bi bi-shield-check"></i> Audit Trail</a>
        </div>
        <div class="nav-item">
          <a href="/settings" class="nav-link"><i class="bi bi-gear"></i> Settings</a>
        </div>
      <?php endif; ?>
    </div>
  </nav>

  <!-- ══ TOP BAR ═══════════════════════════════════════════════ -->
  <header id="topbar">
    <button class="topbar-toggle" id="sidebar-toggle" aria-label="Toggle sidebar">
      <i class="bi bi-list"></i>
    </button>
    <span class="topbar-title"><?= htmlspecialchars($title ?? '') ?></span>
    <div class="topbar-spacer"></div>
    <div class="topbar-actions">
      <a href="/pos" class="topbar-btn"><i class="bi bi-cart3"></i> POS</a>
      <div class="topbar-user">
        <div class="user-avatar"><?= strtoupper(substr($currentUser['username'] ?? 'U', 0, 1)) ?></div>
        <div>
          <div class="user-name"><?= htmlspecialchars($currentUser['username'] ?? '') ?></div>
          <div class="user-role"><?= str_replace('_', ' ', ucfirst($currentUser['role'] ?? '')) ?></div>
        </div>
      </div>
      <form method="POST" action="/logout" style="margin:0;">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
        <button type="submit" class="topbar-btn" title="Logout"><i class="bi bi-box-arrow-right"></i></button>
      </form>
    </div>
  </header>

  <!-- ══ MAIN CONTENT ══════════════════════════════════════════ -->
  <main id="main-content">
    <?php
    $flash = \App\Core\Session::getFlash('success');
    if ($flash): ?>
      <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>
    <?= $content ?>
  </main>

</body>

</html>