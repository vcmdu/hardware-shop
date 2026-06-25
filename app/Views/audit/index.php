<div class="page-header">
  <div><h2><i class="bi bi-shield-check text-accent"></i> System Audit Trail</h2><div class="breadcrumb">Security logs tracking administrative actions, logins, and configurations</div></div>
</div>

<div class="card">
  <div class="card-header"><span class="card-title">Security & Action Logs</span></div>
  <div class="card-body table-wrap">
    <table id="auditTable" class="data-table display responsive" style="width:100%;">
      <thead>
        <tr>
          <th>Timestamp</th>
          <th>Username</th>
          <th>Role</th>
          <th>Action Executed</th>
          <th>Parameters / details</th>
          <th>IP Address</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<script>
let auditDT;

async function loadAuditLogs() {
  const r = await App.get('/api/audit');
  if(auditDT) { auditDT.clear().rows.add(r.data || []).draw(); return; }
  auditDT = $('#auditTable').DataTable(App.dtDefaults({
    data: r.data || [],
    order: [[0, 'desc']],
    columns: [
      {data: 'created_at', render: v => v.replace('T', ' ').substring(0, 19)},
      {data: 'username', render: v => v ? `<strong>${v}</strong>` : '<span class="text-muted">system</span>'},
      {data: 'role', render: v => v ? `<span class="badge badge-outline">${v.replace('_', ' ').toUpperCase()}</span>` : '<span class="text-muted">—</span>'},
      {data: 'action', render: v => `<span class="font-semibold text-primary">${v}</span>`},
      {data: 'details', defaultContent: '<span class="text-muted">—</span>', render: v => {
         if(!v) return '<span class="text-muted">—</span>';
         try {
           const parsed = JSON.parse(v);
           return `<pre style="margin:0;font-size:0.75rem;max-height:80px;overflow:auto;background:rgba(0,0,0,0.25);padding:4px;border-radius:4px;color:#cbd5e1;">${JSON.stringify(parsed, null, 2)}</pre>`;
         } catch(e) {
           return `<span>${v}</span>`;
         }
      }},
      {data: 'ip_address', render: v => v ? `<code>${v}</code>` : '<span class="text-muted">localhost</span>'}
    ]
  }));
}

loadAuditLogs();
</script>
