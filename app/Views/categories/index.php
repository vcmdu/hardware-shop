<div class="page-header">
  <div><h2><i class="bi bi-tags text-accent"></i> Category Management</h2><div class="breadcrumb">Manage product categories</div></div>
  <button class="btn btn-primary" onclick="openCatModal()"><i class="bi bi-plus-circle"></i> Add Category</button>
</div>

<div class="card">
  <div class="card-header"><span class="card-title">All Categories</span></div>
  <div class="card-body table-wrap">
    <table id="catTable" class="data-table display responsive">
      <thead><tr><th>#</th><th>Name</th><th>Description</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal-overlay" id="catModal">
  <div class="modal-box">
    <div class="modal-header">
      <h3 class="modal-title" id="modalTitle">Add Category</h3>
      <button class="modal-close">&times;</button>
    </div>
    <form id="catForm">
      <input type="hidden" id="catId" value="">
      <div class="form-group">
        <label class="form-label">Category Name <span style="color:var(--danger)">*</span></label>
        <input type="text" class="form-control" id="catName" placeholder="e.g. Hand Tools" required>
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea class="form-control" id="catDesc" placeholder="Optional description..."></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Status</label>
        <select class="form-select" id="catStatus">
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>
      <div class="d-flex gap-2" style="justify-content:flex-end;margin-top:8px;">
        <button type="button" class="btn btn-outline modal-close">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Save Category</button>
      </div>
    </form>
  </div>
</div>

<script>
let catDT;
const csrf = document.querySelector('meta[name="csrf-token"]').content;

async function loadCategories(){
  const r = await App.get('/api/categories');
  if(catDT){ catDT.clear().rows.add(r.data || []).draw(); return; }
  catDT = $('#catTable').DataTable(App.dtDefaults({
    data: r.data || [],
    columns:[
      {data:null, render:(d,t,r,m) => m.row+1},
      {data:'name', render:v=>`<strong>${v}</strong>`},
      {data:'description', defaultContent:'<span class="text-muted">—</span>'},
      {data:'status', render:v=>`<span class="badge badge-${v==='active'?'success':'muted'}">${v}</span>`},
      {data:null, orderable:false, render:(d,t,r)=>`
        <div class="d-flex gap-2">
          <button class="btn btn-outline btn-sm btn-icon" onclick="editCat(${r.id},'${r.name.replace(/'/g,"\\'")}','${(r.description||'').replace(/'/g,"\\'")}','${r.status}')" title="Edit"><i class="bi bi-pencil"></i></button>
          <button class="btn btn-danger btn-sm btn-icon" onclick="deleteCat(${r.id},'${r.name.replace(/'/g,"\\'")}')" title="Delete"><i class="bi bi-trash"></i></button>
        </div>`
      }
    ]
  }));
}

function openCatModal(edit=false){
  if(!edit){ document.getElementById('catId').value=''; document.getElementById('catForm').reset(); document.getElementById('modalTitle').textContent='Add Category'; }
  App.openModal('catModal');
}

function editCat(id,name,desc,status){
  document.getElementById('catId').value=id;
  document.getElementById('catName').value=name;
  document.getElementById('catDesc').value=desc;
  document.getElementById('catStatus').value=status;
  document.getElementById('modalTitle').textContent='Edit Category';
  App.openModal('catModal');
}

async function deleteCat(id,name){
  App.confirm(`Delete category "${name}"? Products using it cannot be deleted.`, async()=>{
    const r = await App.del(`/api/categories/${id}`);
    App.toast(r.success?'success':'error', r.message);
    if(r.success) loadCategories();
  });
}

document.getElementById('catForm').addEventListener('submit', async function(e){
  e.preventDefault();
  const id = document.getElementById('catId').value;
  const payload = { name:document.getElementById('catName').value.trim(), description:document.getElementById('catDesc').value.trim(), status:document.getElementById('catStatus').value };
  const r = id ? await App.put(`/api/categories/${id}`, payload) : await App.post('/api/categories', payload);
  App.toast(r.success?'success':'error', r.message);
  if(r.success){ App.closeModal('catModal'); loadCategories(); }
});

loadCategories();
</script>
