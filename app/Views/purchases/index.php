<div class="page-header">
  <div><h2><i class="bi bi-bag-plus text-accent"></i> Purchase Management</h2><div class="breadcrumb">Manage supplier purchase orders and stock intake</div></div>
  <a href="/purchases/create" class="btn btn-primary"><i class="bi bi-plus-circle"></i> New Purchase Order</a>
</div>

<div class="card">
  <div class="card-header"><span class="card-title">All Purchase Orders</span></div>
  <div class="card-body table-wrap">
    <table id="purchTable" class="data-table display responsive">
      <thead>
        <tr>
          <th>PO Number</th>
          <th>Supplier</th>
          <th>Order Date</th>
          <th>Grand Total</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<script>
let purchDT;

async function loadPurchases(){
  const r = await App.get('/api/purchases');
  if(purchDT){ purchDT.clear().rows.add(r.data || []).draw(); return; }
  purchDT = $('#purchTable').DataTable(App.dtDefaults({
    data: r.data || [],
    columns:[
      {data:'purchase_number', render:v=>`<code>${v}</code>`},
      {data:'supplier_name', render:(v,t,r)=>`<strong>${v}</strong> <small class="text-muted">(${r.supplier_code})</small>`},
      {data:'date'},
      {data:'grand_total', render:v=>App.formatCurrency(v)},
      {data:'status', render:v=>{
         let cls = 'muted';
         if(v==='approved') cls = 'success';
         if(v==='pending') cls = 'warning';
         if(v==='returned') cls = 'danger';
         return `<span class="badge badge-${cls}">${v.toUpperCase()}</span>`;
      }},
      {data:null, orderable:false, render:(d,t,r)=>{
         let actions = `<div class="d-flex gap-2">`;
         actions += `<a href="/purchases/${r.id}/pdf" class="btn btn-outline btn-sm btn-icon" title="Download PDF"><i class="bi bi-file-earmark-pdf"></i></a>`;
         if(r.status === 'pending') {
           actions += `<button class="btn btn-success btn-sm" onclick="approvePO(${r.id}, '${r.purchase_number}')"><i class="bi bi-check-circle"></i> Approve</button>`;
         } else if(r.status === 'approved') {
           actions += `<button class="btn btn-danger btn-sm" onclick="returnPO(${r.id}, '${r.purchase_number}')"><i class="bi bi-arrow-left-circle"></i> Return</button>`;
         }
         actions += `</div>`;
         return actions;
      }}
    ]
  }));
}

async function approvePO(id, poNum){
  App.confirm(`Approve Purchase Order ${poNum}? This will increase current stock levels in inventory.`, async()=>{
    const r = await App.post(`/api/purchases/${id}/approve`, {});
    App.toast(r.success?'success':'error', r.message);
    if(r.success) loadPurchases();
  });
}

async function returnPO(id, poNum){
  App.confirm(`Return Purchase Order ${poNum}? This will reverse the stock levels and mark the PO as returned.`, async()=>{
    const r = await App.post(`/api/purchases/${id}/return`, {});
    App.toast(r.success?'success':'error', r.message);
    if(r.success) loadPurchases();
  });
}

loadPurchases();
</script>
