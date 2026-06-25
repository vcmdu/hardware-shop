<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Hardware Shop Inventory Management System">
  <title><?= htmlspecialchars($title ?? 'Login') ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
    *{box-sizing:border-box;margin:0;padding:0;}
    body{font-family:'Inter',sans-serif;min-height:100vh;background:#0a0e1a;display:flex;align-items:center;justify-content:center;overflow:hidden;}
    .bg-orbs{position:fixed;inset:0;pointer-events:none;}
    .orb{position:absolute;border-radius:50%;filter:blur(80px);opacity:0.18;}
    .orb1{width:500px;height:500px;background:#3b82f6;top:-120px;left:-120px;animation:drift 8s ease-in-out infinite alternate;}
    .orb2{width:400px;height:400px;background:#06b6d4;bottom:-80px;right:-80px;animation:drift 10s ease-in-out infinite alternate-reverse;}
    .orb3{width:300px;height:300px;background:#8b5cf6;top:40%;left:40%;animation:drift 12s ease-in-out infinite alternate;}
    @keyframes drift{from{transform:translate(0,0);}to{transform:translate(30px,20px);}}
    .login-wrap{position:relative;z-index:10;width:90%;max-width:440px;}
    .brand{text-align:center;margin-bottom:36px;}
    .brand-icon{width:64px;height:64px;background:linear-gradient(135deg,#3b82f6,#06b6d4);border-radius:18px;display:flex;align-items:center;justify-content:center;font-size:2rem;color:#fff;margin:0 auto 16px;}
    .brand h1{font-size:1.5rem;font-weight:800;color:#f1f5f9;}
    .brand p{font-size:0.82rem;color:#94a3b8;margin-top:4px;}
    .card{background:rgba(17,24,39,0.85);border:1px solid rgba(255,255,255,0.07);border-radius:20px;padding:36px;backdrop-filter:blur(20px);box-shadow:0 24px 64px rgba(0,0,0,0.5);}
    .form-label{display:block;font-size:0.75rem;font-weight:600;color:#94a3b8;margin-bottom:7px;letter-spacing:0.3px;}
    .form-group{margin-bottom:20px;}
    .input-wrap{position:relative;}
    .input-wrap i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#64748b;font-size:1rem;}
    .form-control{width:100%;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.07);border-radius:11px;color:#f1f5f9;padding:12px 14px 12px 42px;font-size:0.9rem;font-family:inherit;transition:border-color .22s,box-shadow .22s;}
    .form-control:focus{outline:none;border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.15);}
    .form-control::placeholder{color:#475569;}
    .btn-login{width:100%;background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border:none;border-radius:11px;padding:13px;font-size:0.95rem;font-weight:700;cursor:pointer;transition:all .22s;display:flex;align-items:center;justify-content:center;gap:9px;font-family:inherit;}
    .btn-login:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(59,130,246,.4);}
    .btn-login:active{transform:translateY(0);}
    .btn-login .spinner{width:18px;height:18px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:spin .6s linear infinite;display:none;}
    .btn-login.loading .btn-text{display:none;}
    .btn-login.loading .spinner{display:block;}
    @keyframes spin{to{transform:rotate(360deg);}}
    .hint{text-align:center;margin-top:22px;font-size:0.78rem;color:#64748b;}
    .hint strong{color:#94a3b8;}
    .error-msg{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);color:#f87171;padding:12px 16px;border-radius:10px;font-size:0.83rem;margin-bottom:16px;display:none;align-items:center;gap:8px;}
    .error-msg.show{display:flex;}
  </style>
</head>
<body>
<div class="bg-orbs"><div class="orb orb1"></div><div class="orb orb2"></div><div class="orb orb3"></div></div>
  <?= $content ?>
</body>
</html>
