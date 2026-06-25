<?php
// $settings is an associative array: ['key' => 'value', ...]
$s = $settings ?? [];
?>

<div class="page-header">
  <div>
    <h2><i class="bi bi-gear text-accent"></i> System Settings</h2>
    <div class="breadcrumb">Configure your shop information, invoice & tax preferences</div>
  </div>
</div>

<div class="card" style="max-width:820px;">
  <div class="card-header">
    <span class="card-title"><i class="bi bi-sliders"></i> General Settings</span>
  </div>
  <div class="card-body">
    <form method="POST" action="/settings" enctype="multipart/form-data" id="settings-form">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">

      <!-- ── Shop Info ── -->
      <h3 style="font-size:1rem;margin-bottom:16px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;">
        <i class="bi bi-shop"></i> Shop Information
      </h3>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
        <div class="form-group">
          <label class="form-label" for="shop_name">Shop Name</label>
          <input type="text" id="shop_name" name="shop_name" class="form-control"
                 value="<?= htmlspecialchars($s['shop_name'] ?? '') ?>" placeholder="e.g. Amman Hardware">
        </div>
        <div class="form-group">
          <label class="form-label" for="shop_phone">Phone Number</label>
          <input type="text" id="shop_phone" name="shop_phone" class="form-control"
                 value="<?= htmlspecialchars($s['shop_phone'] ?? '') ?>" placeholder="+91 98765 43210">
        </div>
      </div>

      <div class="form-group" style="margin-bottom:16px;">
        <label class="form-label" for="shop_address">Address</label>
        <textarea id="shop_address" name="shop_address" class="form-control" rows="2"
                  placeholder="Shop address..."><?= htmlspecialchars($s['shop_address'] ?? '') ?></textarea>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px;">
        <div class="form-group">
          <label class="form-label" for="shop_email">Email</label>
          <input type="email" id="shop_email" name="shop_email" class="form-control"
                 value="<?= htmlspecialchars($s['shop_email'] ?? '') ?>" placeholder="shop@example.com">
        </div>
        <div class="form-group">
          <label class="form-label" for="shop_gst">GST Number</label>
          <input type="text" id="shop_gst" name="shop_gst" class="form-control"
                 value="<?= htmlspecialchars($s['shop_gst'] ?? '') ?>" placeholder="22AAAAA0000A1Z5">
        </div>
      </div>

      <!-- ── Shop Logo ── -->
      <div class="form-group" style="margin-bottom:24px;">
        <label class="form-label" for="shop_logo">Shop Logo</label>
        <?php if (!empty($s['shop_logo'])): ?>
          <div style="margin-bottom:10px;">
            <img src="<?= htmlspecialchars($s['shop_logo']) ?>" alt="Shop Logo"
                 style="max-height:70px;border-radius:8px;border:1px solid var(--border);">
          </div>
        <?php endif; ?>
        <input type="file" id="shop_logo" name="shop_logo" class="form-control" accept="image/*">
        <small style="color:var(--text-muted);">Upload PNG/JPG. Leave blank to keep current logo.</small>
      </div>

      <hr style="border-color:var(--border);margin:24px 0;">

      <!-- ── Invoice & Currency ── -->
      <h3 style="font-size:1rem;margin-bottom:16px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;">
        <i class="bi bi-receipt"></i> Invoice & Currency
      </h3>

      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:24px;">
        <div class="form-group">
          <label class="form-label" for="invoice_prefix">Invoice Prefix</label>
          <input type="text" id="invoice_prefix" name="invoice_prefix" class="form-control"
                 value="<?= htmlspecialchars($s['invoice_prefix'] ?? 'INV') ?>" placeholder="INV">
        </div>
        <div class="form-group">
          <label class="form-label" for="currency">Currency Code</label>
          <input type="text" id="currency" name="currency" class="form-control"
                 value="<?= htmlspecialchars($s['currency'] ?? 'INR') ?>" placeholder="INR">
        </div>
        <div class="form-group">
          <label class="form-label" for="currency_symbol">Currency Symbol</label>
          <input type="text" id="currency_symbol" name="currency_symbol" class="form-control"
                 value="<?= htmlspecialchars($s['currency_symbol'] ?? '₹') ?>" placeholder="₹">
        </div>
      </div>

      <hr style="border-color:var(--border);margin:24px 0;">

      <!-- ── Tax ── -->
      <h3 style="font-size:1rem;margin-bottom:16px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;">
        <i class="bi bi-percent"></i> Tax Settings
      </h3>

      <div class="form-group" style="margin-bottom:24px;">
        <label class="form-label" for="tax_settings">Default Tax Rate (%)</label>
        <input type="number" id="tax_settings" name="tax_settings" class="form-control"
               style="max-width:180px;"
               value="<?= htmlspecialchars($s['tax_settings'] ?? '0') ?>"
               min="0" max="100" step="0.01" placeholder="e.g. 18">
        <small style="color:var(--text-muted);">Applied as a percentage on taxable items.</small>
      </div>

      <!-- ── Actions ── -->
      <div style="display:flex;gap:12px;justify-content:flex-end;padding-top:8px;">
        <a href="/" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary" id="save-settings-btn">
          <i class="bi bi-floppy"></i> Save Settings
        </button>
      </div>
    </form>
  </div>
</div>

<script>
document.getElementById('settings-form').addEventListener('submit', function() {
  const btn = document.getElementById('save-settings-btn');
  btn.disabled = true;
  btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving…';
});
</script>
