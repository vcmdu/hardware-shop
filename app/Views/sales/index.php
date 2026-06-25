<div class="page-header">
  <div><h2><i class="bi bi-receipt text-accent"></i> Sales History</h2><div class="breadcrumb">Browse past sales invoices and payments</div></div>
  <a href="/pos" class="btn btn-primary"><i class="bi bi-cart3"></i> Open POS Terminal</a>
</div>

<div class="card">
  <div class="card-header"><span class="card-title">Completed Sales Invoices</span></div>
  <div class="card-body table-wrap">
    <table id="salesTable" class="data-table display responsive">
      <thead>
        <tr>
          <th>Invoice #</th>
          <th>Customer</th>
          <th>Date</th>
          <th>Total Amount</th>
          <th>Paid Amount</th>
          <th>Payment Method</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<script>
let salesDT;

async function loadSales(){
  const r = await App.get('/api/sales');
  if(salesDT){ salesDT.clear().rows.add(r.data || []).draw(); return; }
  salesDT = $('#salesTable').DataTable(App.dtDefaults({
    data: r.data || [],
    columns:[
      {data:'invoice_number', render:v=>`<code>${v}</code>`},
      {data:'customer_name', render:(v,t,r)=>`<strong>${v}</strong> <small class="text-muted">(${r.customer_code})</small>`},
      {data:'date'},
      {data:'grand_total', render:v=>App.formatCurrency(v)},
      {data:'paid_amount', render:v=>App.formatCurrency(v)},
      {data:'payment_method', render:v=>`<span class="badge badge-outline">${v.toUpperCase()}</span>`},
      {data:'payment_status', render:v=>{
         let cls = 'muted';
         if(v==='paid') cls = 'success';
         if(v==='partial') cls = 'warning';
         if(v==='unpaid') cls = 'danger';
         return `<span class="badge badge-${cls}">${v.toUpperCase()}</span>`;
      }},
      {data:null, orderable:false, render:(d,t,r)=>`
        <div class="d-flex gap-2">
          <a href="/sales/${r.id}/pdf" class="btn btn-outline btn-sm btn-icon" title="Download PDF Invoice"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
        </div>`
      }
    ]
  }));
}

loadSales();
</script>
