<div class="page-header">
  <div><h2><i class="bi bi-people text-accent"></i> Customer Management</h2><div class="breadcrumb">View and manage store customers and balances</div></div>
  <button class="btn btn-primary" onclick="openCustModal()"><i class="bi bi-plus-circle"></i> Add Customer</button>
</div>

<div class="card">
  <div class="card-header"><span class="card-title">All Customers</span></div>
  <div class="card-body table-wrap">
    <table id="custTable" class="data-table display responsive">
      <thead>
        <tr>
          <th>Code</th>
          <th>Name</th>
          <th>Mobile</th>
          <th>Email</th>
          <th>GSTIN</th>
          <th>Credit Limit</th>
          <th>Outstanding</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal-overlay" id="custModal">
  <div class="modal-box">
    <div class="modal-header">
      <h3 class="modal-title" id="modalTitle">Add Customer</h3>
      <button class="modal-close">&times;</button>
    </div>
    <form id="custForm">
      <input type="hidden" id="custId" value="">
      <div class="form-group">
        <label class="form-label">Customer Name <span style="color:var(--danger)">*</span></label>
        <input type="text" class="form-control" id="custName" placeholder="e.g. Metro Builders" required>
      </div>
      <div class="form-group">
        <label class="form-label">Mobile Number</label>
        <input type="text" class="form-control" id="custMobile" placeholder="e.g. 9876543210">
      </div>
      <div class="form-group">
        <label class="form-label">Email</label>
        <input type="email" class="form-control" id="custEmail" placeholder="e.g. client@example.com">
      </div>
      <div class="form-group">
        <label class="form-label">GST Number</label>
        <input type="text" class="form-control" id="custGst" placeholder="e.g. 29ABCDE1234F1Z5">
      </div>
      <div class="form-group">
        <label class="form-label">Address</label>
        <textarea class="form-control" id="custAddress" placeholder="Customer address..."></textarea>
      </div>
      <div class="row" style="display:flex;gap:12px;">
        <div class="form-group" style="flex:1;">
          <label class="form-label">Credit Limit (₹)</label>
          <input type="number" step="0.01" class="form-control" id="custLimit" value="0.00">
        </div>
        <div class="form-group" style="flex:1;">
          <label class="form-label">Outstanding Balance (₹)</label>
          <input type="number" step="0.01" class="form-control" id="custBalance" value="0.00">
        </div>
      </div>
      <div class="d-flex gap-2" style="justify-content:flex-end;margin-top:8px;">
        <button type="button" class="btn btn-outline modal-close">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Save Customer</button>
      </div>
    </form>
  </div>
</div>

<script>
let custDT;

async function loadCustomers(){
  const r = await App.get('/api/customers');
  if(custDT){ custDT.clear().rows.add(r.data || []).draw(); return; }
  custDT = $('#custTable').DataTable(App.dtDefaults({
    data: r.data || [],
    columns:[
      {data:'customer_code', render:v=>`<code>${v}</code>`},
      {data:'name', render:v=>`<strong>${v}</strong>`},
      {data:'mobile', defaultContent:'<span class="text-muted">—</span>'},
      {data:'email', defaultContent:'<span class="text-muted">—</span>'},
      {data:'gst_number', defaultContent:'<span class="text-muted">—</span>'},
      {data:'credit_limit', render:v=>App.formatCurrency(v)},
      {data:'outstanding_balance', render:v=>`<span class="${parseFloat(v)>0?'text-danger':'text-success'}">${App.formatCurrency(v)}</span>`},
      {data:null, orderable:false, render:(d,t,r)=>`
        <div class="d-flex gap-2">
          <button class="btn btn-outline btn-sm btn-icon" onclick="editCust(${r.id},'${r.name.replace(/'/g,"\\'")}','${(r.mobile||'').replace(/'/g,"\\'")}','${(r.email||'').replace(/'/g,"\\'")}','${(r.gst_number||'').replace(/'/g,"\\'")}','${(r.address||'').replace(/'/g,"\\'").replace(/\n/g,"\\n")}',${r.credit_limit},${r.outstanding_balance})" title="Edit"><i class="bi bi-pencil"></i></button>
          <button class="btn btn-danger btn-sm btn-icon" onclick="deleteCust(${r.id},'${r.name.replace(/'/g,"\\'")}')" title="Delete"><i class="bi bi-trash"></i></button>
        </div>`
      }
    ]
  }));
}

function openCustModal(edit=false){
  if(!edit){
    document.getElementById('custId').value='';
    document.getElementById('custForm').reset();
    document.getElementById('modalTitle').textContent='Add Customer';
  }
  App.openModal('custModal');
}

function editCust(id, name, mobile, email, gst, address, creditLimit, outstandingBalance){
  document.getElementById('custId').value=id;
  document.getElementById('custName').value=name;
  document.getElementById('custMobile').value=mobile;
  document.getElementById('custEmail').value=email;
  document.getElementById('custGst').value=gst;
  document.getElementById('custAddress').value=address;
  document.getElementById('custLimit').value=creditLimit;
  document.getElementById('custBalance').value=outstandingBalance;
  document.getElementById('modalTitle').textContent='Edit Customer';
  App.openModal('custModal');
}

async function deleteCust(id, name){
  App.confirm(`Delete customer "${name}"? This will fail if there are sales linked to this customer.`, async()=>{
    const r = await App.del(`/api/customers/${id}`);
    App.toast(r.success?'success':'error', r.message);
    if(r.success) loadCustomers();
  });
}

document.getElementById('custForm').addEventListener('submit', async function(e){
  e.preventDefault();
  const id = document.getElementById('custId').value;
  const payload = {
    name: document.getElementById('custName').value.trim(),
    mobile: document.getElementById('custMobile').value.trim(),
    email: document.getElementById('custEmail').value.trim(),
    gst_number: document.getElementById('custGst').value.trim(),
    address: document.getElementById('custAddress').value.trim(),
    credit_limit: parseFloat(document.getElementById('custLimit').value || 0),
    outstanding_balance: parseFloat(document.getElementById('custBalance').value || 0)
  };
  const r = id ? await App.put(`/api/customers/${id}`, payload) : await App.post('/api/customers', payload);
  App.toast(r.success?'success':'error', r.message);
  if(r.success){ App.closeModal('custModal'); loadCustomers(); }
});

loadCustomers();
</script>
