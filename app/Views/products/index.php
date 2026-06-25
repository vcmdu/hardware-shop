<div class="page-header">
  <div><h2><i class="bi bi-box text-accent"></i> Product Management</h2><div class="breadcrumb">Manage your product catalogue</div></div>
  <div class="d-flex gap-2">
    <a href="/products/export" class="btn btn-outline"><i class="bi bi-file-earmark-excel"></i> Export</a>
    <button class="btn btn-outline" onclick="document.getElementById('importModal') && App.openModal('importModal')"><i class="bi bi-upload"></i> Import</button>
    <button class="btn btn-primary" onclick="openProdModal()"><i class="bi bi-plus-circle"></i> Add Product</button>
  </div>
</div>

<div class="card">
  <div class="card-header"><span class="card-title">Product Catalogue</span></div>
  <div class="card-body table-wrap">
    <table id="prodTable" class="data-table display responsive">
      <thead><tr><th>#</th><th>Image</th><th>Code</th><th>Product</th><th>Category</th><th>Stock</th><th>Price</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal-overlay" id="prodModal">
  <div class="modal-box" style="max-width:700px;">
    <div class="modal-header">
      <h3 class="modal-title" id="prodModalTitle">Add Product</h3>
      <button class="modal-close">&times;</button>
    </div>
    <form id="prodForm" enctype="multipart/form-data">
      <input type="hidden" id="prodId" value="">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <div class="form-group">
          <label class="form-label">Product Code</label>
          <input type="text" class="form-control" id="prodCode" placeholder="Auto-generated if empty">
        </div>
        <div class="form-group">
          <label class="form-label">Barcode <span style="color:var(--danger)">*</span></label>
          <div class="d-flex gap-2">
            <input type="text" class="form-control" id="prodBarcode" placeholder="Scan or enter" required>
            <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('prodBarcode').value=App.generateBarcode()"><i class="bi bi-upc-scan"></i></button>
          </div>
        </div>
        <div class="form-group" style="grid-column:1/-1;">
          <label class="form-label">Product Name <span style="color:var(--danger)">*</span></label>
          <input type="text" class="form-control" id="prodName" placeholder="e.g. Stanley Claw Hammer 16oz" required>
        </div>
        <div class="form-group">
          <label class="form-label">Category <span style="color:var(--danger)">*</span></label>
          <select class="form-select" id="prodCat" required>
            <option value="">— Select Category —</option>
            <?php foreach($categories as $c): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Brand</label>
          <input type="text" class="form-control" id="prodBrand" placeholder="e.g. Stanley, Bosch">
        </div>
        <div class="form-group">
          <label class="form-label">Unit</label>
          <select class="form-select" id="prodUnit">
            <option value="pcs">Pieces (pcs)</option>
            <option value="box">Box</option>
            <option value="kg">Kilogram (kg)</option>
            <option value="m">Meter (m)</option>
            <option value="ltr">Litre (ltr)</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Purchase Price (₹) <span style="color:var(--danger)">*</span></label>
          <input type="number" step="0.01" class="form-control" id="prodPurchasePrice" placeholder="0.00" required>
        </div>
        <div class="form-group">
          <label class="form-label">Selling Price (₹) <span style="color:var(--danger)">*</span></label>
          <input type="number" step="0.01" class="form-control" id="prodSellingPrice" placeholder="0.00" required>
        </div>
        <div class="form-group">
          <label class="form-label">GST %</label>
          <select class="form-select" id="prodGst">
            <option value="0">0%</option>
            <option value="5">5%</option>
            <option value="12">12%</option>
            <option value="18" selected>18%</option>
            <option value="28">28%</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Current Stock</label>
          <input type="number" class="form-control" id="prodStock" value="0">
        </div>
        <div class="form-group">
          <label class="form-label">Minimum Stock Level</label>
          <input type="number" class="form-control" id="prodMinStock" value="5">
        </div>
        <div class="form-group">
          <label class="form-label">Rack Location</label>
          <input type="text" class="form-control" id="prodRack" placeholder="e.g. A-12">
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select class="form-select" id="prodStatus">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Product Image</label>
          <input type="file" class="form-control" id="prodImage" name="image" accept="image/*">
        </div>
      </div>
      <div class="d-flex gap-2" style="justify-content:flex-end;margin-top:8px;">
        <button type="button" class="btn btn-outline modal-close">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Save Product</button>
      </div>
    </form>
  </div>
</div>

<!-- Import Modal -->
<div class="modal-overlay" id="importModal">
  <div class="modal-box" style="max-width:480px;">
    <div class="modal-header"><h3 class="modal-title">Import Products via Excel</h3><button class="modal-close">&times;</button></div>
    <form id="importForm" enctype="multipart/form-data">
      <div class="alert alert-warning"><i class="bi bi-info-circle"></i> Excel columns: Code, Barcode, Name, Category, Brand, Unit, Purchase Price, Selling Price, GST%, Stock, Min Stock, Rack, Status</div>
      <div class="form-group">
        <label class="form-label">Select Excel File (.xlsx)</label>
        <input type="file" class="form-control" name="import_file" accept=".xlsx" required>
      </div>
      <div class="d-flex gap-2" style="justify-content:flex-end;">
        <button type="button" class="btn btn-outline modal-close">Cancel</button>
        <button type="submit" class="btn btn-success"><i class="bi bi-upload"></i> Import</button>
      </div>
    </form>
  </div>
</div>

<script>
let prodDT;
const csrf = document.querySelector('meta[name="csrf-token"]').content;

async function loadProducts(){
  const r = await App.get('/api/products');
  if(prodDT){ prodDT.clear().rows.add(r.data||[]).draw(); return; }
  prodDT = $('#prodTable').DataTable(App.dtDefaults({
    data: r.data||[],
    columns:[
      {data:null, render:(d,t,r,m)=>m.row+1},
      {data:'image_path', render:v=>v?`<img src="${v}" style="width:36px;height:36px;border-radius:8px;object-fit:cover;">`:'<div style="width:36px;height:36px;border-radius:8px;background:var(--glass);display:flex;align-items:center;justify-content:center;"><i class="bi bi-box" style="color:var(--text-muted);"></i></div>'},
      {data:'product_code', render:v=>`<code style="color:var(--accent);font-size:.78rem;">${v}</code>`},
      {data:'product_name', render:(v,t,r)=>`<div><strong>${v}</strong><div class="fs-sm text-muted">${r.brand||''} &bull; ${r.unit}</div></div>`},
      {data:'category_name', defaultContent:'<span class="text-muted">—</span>'},
      {data:'current_stock', render:(v,t,r)=>`<span class="badge badge-${v<=0?'danger':v<=r.minimum_stock?'warning':'success'}">${v}</span>`},
      {data:'selling_price', render:v=>`<strong>₹${parseFloat(v).toLocaleString('en-IN',{minimumFractionDigits:2})}</strong>`},
      {data:'status', render:v=>`<span class="badge badge-${v==='active'?'success':'muted'}">${v}</span>`},
      {data:null, orderable:false, render:(d,t,r)=>`
        <div class="d-flex gap-2">
          <button class="btn btn-outline btn-sm btn-icon" onclick="editProd(${r.id})" title="Edit"><i class="bi bi-pencil"></i></button>
          <button class="btn btn-danger btn-sm btn-icon" onclick="deleteProd(${r.id},'${r.product_name.replace(/'/g,"\\'")}')" title="Delete"><i class="bi bi-trash"></i></button>
        </div>`}
    ]
  }));
}

let allProds = [];
async function editProd(id){
  if(!allProds.length){ const r = await App.get('/api/products'); allProds = r.data||[]; }
  const p = allProds.find(x=>x.id==id); if(!p) return;
  document.getElementById('prodId').value = p.id;
  document.getElementById('prodCode').value = p.product_code;
  document.getElementById('prodBarcode').value = p.barcode;
  document.getElementById('prodName').value = p.product_name;
  document.getElementById('prodCat').value = p.category_id;
  document.getElementById('prodBrand').value = p.brand||'';
  document.getElementById('prodUnit').value = p.unit;
  document.getElementById('prodPurchasePrice').value = p.purchase_price;
  document.getElementById('prodSellingPrice').value = p.selling_price;
  document.getElementById('prodGst').value = p.gst_percentage;
  document.getElementById('prodStock').value = p.current_stock;
  document.getElementById('prodMinStock').value = p.minimum_stock;
  document.getElementById('prodRack').value = p.rack_location||'';
  document.getElementById('prodStatus').value = p.status;
  document.getElementById('prodModalTitle').textContent = 'Edit Product';
  App.openModal('prodModal');
}

function openProdModal(){ document.getElementById('prodId').value=''; document.getElementById('prodForm').reset(); document.getElementById('prodModalTitle').textContent='Add Product'; App.openModal('prodModal'); }

async function deleteProd(id,name){
  App.confirm(`Delete product "${name}"?`, async()=>{
    const r = await App.del(`/api/products/${id}`);
    App.toast(r.success?'success':'error', r.message);
    if(r.success){ allProds=[]; loadProducts(); }
  });
}

document.getElementById('prodForm').addEventListener('submit', async function(e){
  e.preventDefault();
  const id = document.getElementById('prodId').value;
  const fd = new FormData();
  fd.append('product_code', document.getElementById('prodCode').value);
  fd.append('barcode', document.getElementById('prodBarcode').value);
  fd.append('product_name', document.getElementById('prodName').value);
  fd.append('category_id', document.getElementById('prodCat').value);
  fd.append('brand', document.getElementById('prodBrand').value);
  fd.append('unit', document.getElementById('prodUnit').value);
  fd.append('purchase_price', document.getElementById('prodPurchasePrice').value);
  fd.append('selling_price', document.getElementById('prodSellingPrice').value);
  fd.append('gst_percentage', document.getElementById('prodGst').value);
  fd.append('current_stock', document.getElementById('prodStock').value);
  fd.append('minimum_stock', document.getElementById('prodMinStock').value);
  fd.append('rack_location', document.getElementById('prodRack').value);
  fd.append('status', document.getElementById('prodStatus').value);
  const img = document.getElementById('prodImage').files[0];
  if(img) fd.append('image', img);
  const url = id ? `/api/products/${id}` : '/api/products';
  const r = await App.postForm(url, fd);
  App.toast(r.success?'success':'error', r.message);
  if(r.success){ App.closeModal('prodModal'); allProds=[]; loadProducts(); }
});

document.getElementById('importForm').addEventListener('submit', async function(e){
  e.preventDefault();
  const fd = new FormData(this);
  const r = await App.postForm('/api/products/import', fd);
  App.toast(r.success?'success':'error', r.message);
  if(r.success){ App.closeModal('importModal'); this.reset(); loadProducts(); }
});

loadProducts();
</script>
