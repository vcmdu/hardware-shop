<div class="login-wrap fade-in" style="animation:fadeIn .5s ease;">
  <div class="brand">
    <div class="brand-icon"><i class="bi bi-box-seam"></i></div>
    <h1>AMMAN TRADERS</h1>
    <p>Inventory Management System</p>
  </div>
  <div class="card">
    <div class="error-msg" id="errorMsg"><i class="bi bi-exclamation-triangle-fill"></i><span id="errorText">Invalid
        credentials.</span></div>
    <form id="loginForm" novalidate>
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
      <div class="form-group">
        <label class="form-label">Username</label>
        <div class="input-wrap">
          <i class="bi bi-person"></i>
          <input type="text" class="form-control" name="username" id="username" placeholder="Enter your username"
            required autocomplete="username">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <div class="input-wrap">
          <i class="bi bi-lock"></i>
          <input type="password" class="form-control" name="password" id="password" placeholder="Enter your password"
            required autocomplete="current-password">
        </div>
      </div>
      <button type="submit" class="btn-login" id="loginBtn">
        <span class="btn-text"><i class="bi bi-box-arrow-in-right"></i> Sign In</span>
        <div class="spinner"></div>
      </button>
    </form>
    <div class="hint">Default credentials: <strong>superadmin</strong> / <strong>admin123</strong></div>
  </div>
</div>
<style>
  @keyframes fadeIn {
    from {
      opacity: 0;
      transform: translateY(20px);
    }

    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
</style>
<script>
  document.getElementById('loginForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn = document.getElementById('loginBtn');
    const errDiv = document.getElementById('errorMsg');
    const errText = document.getElementById('errorText');
    btn.classList.add('loading');
    errDiv.classList.remove('show');
    const fd = new FormData(this);
    const data = Object.fromEntries(fd.entries());
    try {
      const res = await fetch('/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(data)
      });
      const json = await res.json();
      if (json.success) { window.location.href = json.redirect || '/'; }
      else { errText.textContent = json.message; errDiv.classList.add('show'); }
    } catch (err) {
      errText.textContent = 'Connection error. Please try again.';
      errDiv.classList.add('show');
    }
    btn.classList.remove('loading');
  });
</script>