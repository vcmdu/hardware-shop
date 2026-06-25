<div class="page-header">
  <div><h2><i class="bi bi-journal-text text-accent"></i> Stock Ledger</h2><div class="breadcrumb">Audit and track all inventory transactions and stock movements</div></div>
  <a href="/inventory/adjustment" class="btn btn-primary"><i class="bi bi-sliders"></i> Stock Adjustment</a>
</div>

<div class="card">
  <div class="card-header"><span class="card-title">Stock Movements Log</span></div>
  <div class="card-body table-wrap">
    <table id="ledgerTable" class="data-table display responsive">
      <thead>
        <tr>
          <th>Transaction Date</th>
          <th>Product Code</th>
          <th>Product Name</th>
          <th>Transaction Type</th>
          <th>Reference Description</th>
          <th>Qty Change</th>
          <th>Balance After</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<script>
let ledgerDT;

async function loadLedger(){
  const r = await App.get('/api/inventory/ledger');
  if(ledgerDT){ ledgerDT.clear().rows.add(r.data || []).draw(); return; }
  ledgerDT = $('#ledgerTable').DataTable(App.dtDefaults({
    data: r.data || [],
    order: [[0, 'desc']],
    columns:[
      {data:'created_at', render:v=>v.replace('T', ' ').substring(0, 19)},
      {data:'product_code', render:v=>`<code>${v}</code>`},
      {data:'product_name', render:(v,t,r)=>`<strong>${v}</strong> <small class="text-muted">(${r.unit})</small>`},
      {data:'transaction_type', render:v=>{
         let cls = 'outline';
         let typeText = v.replace('_', ' ');
         if(v.startsWith('purchase') || v.startsWith('adjustment_add') || v.startsWith('transfer_in')) cls = 'success';
         if(v.startsWith('sale') || v.startsWith('adjustment_sub') || v.startsWith('transfer_out') || v.startsWith('damaged')) cls = 'danger';
         return `<span class="badge badge-${cls}">${typeText.toUpperCase()}</span>`;
      }},
      {data:'ref_desc', defaultContent:'<span class="text-muted">—</span>'},
      {data:'quantity', render:v=>`<strong class="${parseInt(v)>0?'text-success':'text-danger'}">${parseInt(v)>0?'+':''}${v}</strong>`},
      {data:'balance_after', render:v=>`<strong>${v}</strong>`}
    ]
  }));
}

loadLedger();
</script>
