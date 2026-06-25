<div class="page-header">
  <div><h2><i class="bi bi-truck text-accent"></i> Supplier Management</h2><div class="breadcrumb">Manage inventory suppliers and details</div></div>
  <button class="btn btn-primary" onclick="openSuppModal()"><i class="bi bi-plus-circle"></i> Add Supplier</button>
</div>

<div class="card">
  <div class="card-header"><span class="card-title">All Suppliers</span></div>
  <div class="card-body table-wrap">
    <table id="suppTable" class="data-table display responsive">
      <thead>
        <tr>
          <th>Code</th>
          <th>Supplier Name</th>
          <th>Contact Person</th>
          <th>Mobile</th>
          <th>Email</th>
          <th>GSTIN</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal-overlay" id="suppModal">
  <div class="modal-box">
    <div class="modal-header">
      <h3 class="modal-title" id="modalTitle">Add Supplier</h3>
      <button class="modal-close">&times;</button>
    </div>
    <form id="suppForm">
      <input type="hidden" id="suppId" value="">
      <div class="form-group">
        <label class="form-label">Supplier Name <span style="color:var(--danger)">*</span></label>
        <input type="text" class="form-control" id="suppName" placeholder="e.g. Apex Tool Group" required>
      </div>
      <div class="form-group">
        <label class="form-label">Contact Person</label>
        <input type="text" class="form-control" id="suppContact" placeholder="e.g. John Doe">
      </div>
      <div class="form-group">
        <label class="form-label">Mobile</label>
        <input type="text" class="form-control" id="suppMobile" placeholder="e.g. 9876543210">
      </div>
      <div class="form-group">
        <label class="form-label">Email</label>
        <input type="email" class="form-control" id="suppEmail" placeholder="e.g. sales@company.com">
      </div>
      <div class="form-group">
        <label class="form-label">GST Number</label>
        <input type="text" class="form-control" id="suppGst" placeholder="e.g. 29AAAAA1111A1Z1">
      </div>
      <div class="form-group">
        <label class="form-label">Address</label>
        <textarea class="form-control" id="suppAddress" placeholder="Supplier address..."></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Status</label>
        <select class="form-select" id="suppStatus">
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>
      <div class="d-flex gap-2" style="justify-content:flex-end;margin-top:8px;">
        <button type="button" class="btn btn-outline modal-close">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Save Supplier</button>
      </div>
    </form>
  </div>
</div>

<script>
let suppDT;

async function loadSuppliers(){
  const r = await App.get('/api/suppliers');
  if(suppDT){ suppDT.clear().rows.add(r.data || []).draw(); return; }
  suppDT = $('#suppTable').DataTable(App.dtDefaults({
    data: r.data || [],
    columns:[
      {data:'supplier_code', render:v=>`<code>${v}</code>`},
      {data:'supplier_name', render:v=>`<strong>${v}</strong>`},
      {data:'contact_person', defaultContent:'<span class="text-muted">—</span>'},
      {data:'mobile', defaultContent:'<span class="text-muted">—</span>'},
      {data:'email', defaultContent:'<span class="text-muted">—</span>'},
      {data:'gst_number', defaultContent:'<span class="text-muted">—</span>'},
      {data:'status', render:v=>`<span class="badge badge-${v==='active'?'success':'muted'}">${v}</span>`},
      {data:null, orderable:false, render:(d,t,r)=>`
        <div class="d-flex gap-2">
          <button class="btn btn-outline btn-sm btn-icon" onclick="editSupp(${r.id},'${r.supplier_name.replace(/'/g,"\\'")}','${(r.contact_person||'').replace(/'/g,"\\'")}','${(r.mobile||'').replace(/'/g,"\\'")}','${(r.email||'').replace(/'/g,"\\'")}','${(r.gst_number||'').replace(/'/g,"\\'")}','${(r.address||'').replace(/'/g,"\\'").replace(/\n/g,"\\n")}','${r.status}')" title="Edit"><i class="bi bi-pencil"></i></button>
          <button class="btn btn-danger btn-sm btn-icon" onclick="deleteSupp(${r.id},'${r.supplier_name.replace(/'/g,"\\'")}')" title="Delete"><i class="bi bi-trash"></i></button>
        </div>`
      }
    ]
  }));
}

function openSuppModal(edit=false){
  if(!edit){
    document.getElementById('suppId').value='';
    document.getElementById('suppForm').reset();
    document.getElementById('modalTitle').textContent='Add Supplier';
  }
  App.openModal('suppModal');
}

function editSupp(id, name, contact, mobile, email, gst, address, status){
  document.getElementById('suppId').value=id;
  document.getElementById('suppName').value=name;
  document.getElementById('suppContact').value=contact;
  document.getElementById('suppMobile').value=mobile;
  document.getElementById('suppEmail').value=email;
  document.getElementById('suppGst').value=gst;
  document.getElementById('suppAddress').value=address;
  document.getElementById('suppStatus').value=status;
  document.getElementById('modalTitle').textContent='Edit Supplier';
  App.openModal('suppModal');
}

async function deleteSupp(id, name){
  App.confirm(`Delete supplier "${name}"? This will fail if there are purchase orders linked to this supplier.`, async()=>{
    const r = await App.del(`/api/suppliers/${id}`);
    App.toast(r.success?'success':'error', r.message);
    if(r.success) loadSuppliers();
  });
}

document.getElementById('suppForm').addEventListener('submit', async function(e){
  e.preventDefault();
  const id = document.getElementById('suppId').value;
  const payload = {
    supplier_name: document.getElementById('suppName').value.trim(),
    contact_person: document.getElementById('suppContact').value.trim(),
    mobile: document.getElementById('suppMobile').value.trim(),
    email: document.getElementById('suppEmail').value.trim(),
    gst_number: document.getElementById('suppGst').value.trim(),
    address: document.getElementById('suppAddress').value.trim(),
    status: document.getElementById('suppStatus').value
  };
  const r = id ? await App.put(`/api/suppliers/${id}`, payload) : await App.post('/api/suppliers', payload);
  App.toast(r.success?'success':'error', r.message);
  if(r.success){ App.closeModal('suppModal'); loadSuppliers(); }
});

loadSuppliers();
</script>
