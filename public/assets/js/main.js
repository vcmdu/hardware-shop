// Hardware Shop Inventory – Global JavaScript
// Handles sidebar, AJAX utilities, DataTable defaults, and alert helpers

const App = {
  csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || '',

  // ── Sidebar toggle ──────────────────────────────────────────
  initSidebar() {
    const sidebar  = document.getElementById('sidebar');
    const topbar   = document.getElementById('topbar');
    const content  = document.getElementById('main-content');
    const toggle   = document.getElementById('sidebar-toggle');
    const overlay  = document.getElementById('sidebar-overlay');
    const isMobile = () => window.innerWidth < 900;

    const collapse = () => {
      if (isMobile()) {
        sidebar.classList.remove('mobile-open');
      } else {
        sidebar.classList.add('collapsed');
        topbar.classList.add('full');
        content.classList.add('full');
      }
    };
    const expand = () => {
      if (isMobile()) {
        sidebar.classList.add('mobile-open');
      } else {
        sidebar.classList.remove('collapsed');
        topbar.classList.remove('full');
        content.classList.remove('full');
      }
    };
    const isCollapsed = () =>
      isMobile() ? !sidebar.classList.contains('mobile-open') : sidebar.classList.contains('collapsed');

    if (toggle) {
      toggle.addEventListener('click', () => isCollapsed() ? expand() : collapse());
    }
    if (overlay) {
      overlay.addEventListener('click', collapse);
    }

    // Mark active nav link
    const path = window.location.pathname;
    document.querySelectorAll('.nav-link').forEach(link => {
      const href = link.getAttribute('href');
      if (href && path.startsWith(href) && href !== '/') {
        link.classList.add('active');
      } else if (href === '/' && path === '/') {
        link.classList.add('active');
      }
    });
  },

  // ── AJAX helper ─────────────────────────────────────────────
  async request(method, url, data = null, isFormData = false) {
    const opts = {
      method,
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': this.csrfToken },
    };
    if (data) {
      if (isFormData) {
        opts.body = data; // FormData (includes files)
      } else {
        opts.headers['Content-Type'] = 'application/json';
        if (data instanceof FormData) {
          // Append CSRF to FormData
          data.append('_csrf', this.csrfToken);
          opts.body = data;
          delete opts.headers['Content-Type'];
        } else {
          data._csrf = this.csrfToken;
          opts.body = JSON.stringify(data);
        }
      }
    }
    if (method !== 'GET' && !isFormData && data) {
      if (!opts.body) { data._csrf = this.csrfToken; opts.body = JSON.stringify(data); }
    }
    const res = await fetch(url, opts);
    return res.json();
  },

  get(url)                  { return this.request('GET', url); },
  post(url, data)           { return this.request('POST', url, data); },
  postForm(url, formData)   { formData.append('_csrf', this.csrfToken); return fetch(url, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest','X-CSRF-Token':this.csrfToken}, body:formData }).then(r=>r.json()); },
  put(url, data)            { return this.request('PUT', url, data); },
  del(url)                  { return this.request('DELETE', url, {}); },

  // ── SweetAlert2 helpers ──────────────────────────────────────
  toast(type, msg) {
    Swal.fire({ toast: true, position: 'top-end', icon: type, title: msg,
      showConfirmButton: false, timer: 3000, timerProgressBar: true,
      background: '#111827', color: '#f1f5f9' });
  },

  confirm(msg, action) {
    return Swal.fire({
      title: 'Are you sure?', text: msg,
      icon: 'warning', showCancelButton: true,
      confirmButtonColor: '#ef4444', cancelButtonColor: '#374151',
      confirmButtonText: 'Yes, proceed!',
      background: '#111827', color: '#f1f5f9',
    }).then(res => { if (res.isConfirmed) action(); });
  },

  // ── DataTable defaults ───────────────────────────────────────
  dtDefaults(extraOpts = {}) {
    return Object.assign({
      responsive: true,
      pageLength: 25,
      dom: '<"d-flex justify-between align-center mb-3"<"d-flex gap-2"B><"d-flex gap-2 align-center"fl>>rt<"d-flex justify-between align-center mt-3"ip>',
      buttons: [
        { extend: 'excel', className: 'btn btn-outline btn-sm', text: '<i class="bi bi-file-earmark-excel"></i> Excel' },
        { extend: 'pdf',   className: 'btn btn-outline btn-sm', text: '<i class="bi bi-file-earmark-pdf"></i> PDF' },
        { extend: 'print', className: 'btn btn-outline btn-sm', text: '<i class="bi bi-printer"></i> Print' },
      ],
      language: {
        search: '', searchPlaceholder: 'Search...',
        lengthMenu: 'Show _MENU_',
        info: 'Showing _START_ to _END_ of _TOTAL_',
        paginate: { previous: '←', next: '→' }
      }
    }, extraOpts);
  },

  // ── Modal helpers ────────────────────────────────────────────
  openModal(id) {
    const el = document.getElementById(id);
    if (el) { el.classList.add('open'); document.body.style.overflow='hidden'; }
  },
  closeModal(id) {
    const el = document.getElementById(id);
    if (el) { el.classList.remove('open'); document.body.style.overflow=''; }
  },

  // ── Close modal on overlay click ─────────────────────────────
  initModals() {
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
      overlay.addEventListener('click', e => {
        if (e.target === overlay) this.closeModal(overlay.id);
      });
    });
    document.querySelectorAll('.modal-close').forEach(btn => {
      btn.addEventListener('click', () => {
        const modal = btn.closest('.modal-overlay');
        if (modal) this.closeModal(modal.id);
      });
    });
  },

  // ── Format currency ──────────────────────────────────────────
  formatCurrency(amount, symbol = '₹') {
    return symbol + parseFloat(amount || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  },

  // ── Generate barcode string ──────────────────────────────────
  generateBarcode() {
    return 'BC' + Date.now() + Math.floor(Math.random() * 1000);
  },

  init() {
    this.initSidebar();
    this.initModals();
    // Fade-in all cards
    document.querySelectorAll('.stat-card, .card').forEach((el, i) => {
      el.style.animationDelay = (i * 0.04) + 's';
      el.classList.add('fade-in');
    });
  }
};

document.addEventListener('DOMContentLoaded', () => App.init());
