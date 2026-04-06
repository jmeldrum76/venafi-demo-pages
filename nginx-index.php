<?php
$certFile = '/etc/pki/tls/certs/nginx.crt';
$certPem  = file_get_contents($certFile);
$cert     = openssl_x509_parse($certPem);
$subject  = $cert['subject'] ?? [];
$issuer   = $cert['issuer']  ?? [];

$cn         = $subject['CN']  ?? '-';
$o          = $subject['O']   ?? '-';
$ou         = $subject['OU']  ?? '-';
$serial     = $cert['serialNumberHex'] ?? ($cert['serialNumber'] ?? '-');
$issuerCn   = $issuer['CN']   ?? '-';
$notBefore  = isset($cert['validFrom_time_t'])  ? gmdate('M d H:i:s Y \G\M\T', $cert['validFrom_time_t'])  : '-';
$notAfter   = isset($cert['validTo_time_t'])    ? gmdate('M d H:i:s Y \G\M\T', $cert['validTo_time_t'])    : '-';
$isExpired  = isset($cert['validTo_time_t']) && $cert['validTo_time_t'] < time();
$statusColor = $isExpired ? '#ff6b6b' : '#00dc82';
$statusText  = $isExpired ? 'Expired &#x2014; Pending Renewal' : 'Valid';
$expiredClass = $isExpired ? 'warn' : '';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NGINX Lab - CyberArk Certificate Manager</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap');
  *{margin:0;padding:0;box-sizing:border-box}
  body{font-family:'Inter',sans-serif;background:#0a0e1a;color:#e8eaf0;min-height:100vh;overflow-x:hidden}
  body::before{content:'';position:fixed;top:0;left:0;right:0;bottom:0;background:radial-gradient(ellipse at 20% 20%,rgba(250,88,45,.12) 0%,transparent 50%),radial-gradient(ellipse at 80% 80%,rgba(0,180,230,.12) 0%,transparent 50%),radial-gradient(ellipse at 50% 50%,rgba(26,43,74,.8) 0%,transparent 70%);pointer-events:none;z-index:0}
  .container{position:relative;z-index:1;max-width:1100px;margin:0 auto;padding:0 2rem}
  header{padding:1.5rem 0;border-bottom:1px solid rgba(255,255,255,.08);backdrop-filter:blur(10px);background:rgba(10,14,26,.7);position:sticky;top:0;z-index:100}
  .header-inner{max-width:1100px;margin:0 auto;padding:0 2rem;display:flex;align-items:center;justify-content:space-between}
  .logo-group{display:flex;align-items:center;gap:1.5rem}
  .logo-pill{background:linear-gradient(135deg,#FA582D,#ff7f57);color:#fff;font-weight:700;font-size:.75rem;letter-spacing:.08em;text-transform:uppercase;padding:.35rem .9rem;border-radius:999px}
  .logo-divider{width:1px;height:24px;background:rgba(255,255,255,.2)}
  .logo-cyberark{background:linear-gradient(135deg,#00B4E6,#0080ff);color:#fff;font-weight:700;font-size:.75rem;letter-spacing:.08em;text-transform:uppercase;padding:.35rem .9rem;border-radius:999px}
  .header-badge{font-size:.75rem;color:rgba(255,255,255,.4);background:rgba(255,255,255,.06);padding:.3rem .8rem;border-radius:999px;border:1px solid rgba(255,255,255,.1)}
  .hero{padding:5rem 0 2.5rem;text-align:center}
  .hero-eyebrow{display:inline-flex;align-items:center;gap:.5rem;font-size:.8rem;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:#FA582D;background:rgba(250,88,45,.1);border:1px solid rgba(250,88,45,.3);padding:.4rem 1rem;border-radius:999px;margin-bottom:2rem}
  .hero-eyebrow::before{content:'';width:6px;height:6px;background:#FA582D;border-radius:50%;animation:pulse 2s infinite}
  @keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.5;transform:scale(.8)}}
  .hero h1{font-size:clamp(2.5rem,5vw,4rem);font-weight:800;line-height:1.1;letter-spacing:-.03em;margin-bottom:1.5rem}
  .gradient{background:linear-gradient(135deg,#FA582D 0%,#ff9a7b 40%,#00B4E6 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
  .hero p{font-size:1.15rem;color:rgba(255,255,255,.55);max-width:560px;margin:0 auto 2.5rem;line-height:1.7}
  .hero-tags{display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap}
  .tag{font-size:.78rem;font-weight:500;padding:.45rem 1rem;border-radius:999px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05);color:rgba(255,255,255,.7)}
  .tag.accent{border-color:rgba(250,88,45,.4);background:rgba(250,88,45,.08);color:#FA582D}
  .tag.blue{border-color:rgba(0,180,230,.4);background:rgba(0,180,230,.08);color:#00B4E6}
  .cert-panel{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.1);border-radius:16px;padding:2rem 2.5rem;margin:3rem 0 1.5rem;position:relative;overflow:hidden}
  .cert-panel::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#FA582D,#00B4E6)}
  .cert-panel-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.75rem}
  .cert-panel-title{font-size:.85rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:rgba(255,255,255,.5)}
  .cert-status{display:flex;align-items:center;gap:.5rem;font-size:.8rem;font-weight:600}
  .cert-dot{width:8px;height:8px;border-radius:50%;background:<?php echo $statusColor ?>;box-shadow:0 0 8px <?php echo $statusColor ?>}
  .cert-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1.25rem}
  .cert-label{font-size:.7rem;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.3);margin-bottom:.35rem}
  .cert-value{font-size:.9rem;font-weight:600;color:rgba(255,255,255,.85);font-family:'Consolas',monospace;word-break:break-all}
  .cert-value.highlight{color:#00B4E6}
  .cert-value.warn{color:#ff6b6b}
  .cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:1.5rem;padding:2rem 0}
  .card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:2rem;transition:transform .2s,border-color .2s,background .2s}
  .card:hover{transform:translateY(-4px);border-color:rgba(250,88,45,.35);background:rgba(250,88,45,.06)}
  .card-icon{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;margin-bottom:1.2rem}
  .card-icon.orange{background:rgba(250,88,45,.15)}.card-icon.blue{background:rgba(0,180,230,.15)}.card-icon.green{background:rgba(0,220,130,.15)}
  .card h3{font-size:1rem;font-weight:700;margin-bottom:.5rem}
  .card p{font-size:.875rem;color:rgba(255,255,255,.5);line-height:1.6}
  .info-strip{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);border-radius:12px;padding:1.5rem 2rem;display:flex;gap:2rem;flex-wrap:wrap;align-items:center;justify-content:center;margin:1rem 0 4rem}
  .info-item{display:flex;align-items:center;gap:.6rem}
  .info-dot{width:8px;height:8px;border-radius:50%;background:#00dc82;box-shadow:0 0 8px #00dc82}
  .info-label{font-size:.75rem;color:rgba(255,255,255,.35);text-transform:uppercase;letter-spacing:.08em}
  .info-value{font-size:.875rem;font-weight:600;color:rgba(255,255,255,.8)}
  footer{border-top:1px solid rgba(255,255,255,.06);padding:1.5rem 0;text-align:center;font-size:.75rem;color:rgba(255,255,255,.25)}
  footer span{color:#FA582D}
</style>
</head>
<body>
<header>
  <div class="header-inner">
    <div class="logo-group">
      <div class="logo-pill">Palo Alto Networks</div>
      <div class="logo-divider"></div>
      <div class="logo-cyberark">CyberArk</div>
    </div>
    <div class="header-badge">Certificate Manager Demo</div>
  </div>
</header>
<div class="container">
  <section class="hero">
    <div class="hero-eyebrow">Live Demo Environment</div>
    <h1>NGINX Lab<br><span class="gradient">Certificate Automation</span></h1>
    <p>Automated certificate lifecycle management powered by CyberArk Certificate Manager. This NGINX server demonstrates machine identity security for high-performance reverse proxy workloads.</p>
    <div class="hero-tags">
      <span class="tag accent">NGINX 1.x</span>
      <span class="tag blue">HTTPS &middot; Port 8443</span>
      <span class="tag">TLS Automation</span>
      <span class="tag">Agentless Deployment</span>
    </div>
  </section>

  <div class="cert-panel">
    <div class="cert-panel-header">
      <span class="cert-panel-title">&#128274; Live Certificate Details</span>
      <span class="cert-status"><span class="cert-dot"></span><?php echo $statusText ?></span>
    </div>
    <div class="cert-grid">
      <div class="cert-field"><div class="cert-label">Common Name (CN)</div><div class="cert-value highlight"><?php echo htmlspecialchars($cn) ?></div></div>
      <div class="cert-field"><div class="cert-label">Organization (O)</div><div class="cert-value"><?php echo htmlspecialchars($o) ?></div></div>
      <div class="cert-field"><div class="cert-label">Org Unit (OU)</div><div class="cert-value"><?php echo htmlspecialchars($ou) ?></div></div>
      <div class="cert-field"><div class="cert-label">Issuer</div><div class="cert-value"><?php echo htmlspecialchars($issuerCn) ?></div></div>
      <div class="cert-field"><div class="cert-label">Serial Number</div><div class="cert-value"><?php echo htmlspecialchars($serial) ?></div></div>
      <div class="cert-field"><div class="cert-label">Valid From</div><div class="cert-value"><?php echo htmlspecialchars($notBefore) ?></div></div>
      <div class="cert-field"><div class="cert-label">Valid To</div><div class="cert-value <?php echo $expiredClass ?>"><?php echo htmlspecialchars($notAfter) ?></div></div>
      <div class="cert-field"><div class="cert-label">Managed By</div><div class="cert-value highlight">CyberArk Certificate Manager</div></div>
    </div>
  </div>

  <div class="cards">
    <div class="card"><div class="card-icon orange">&#128274;</div><h3>Certificate Provisioning</h3><p>CyberArk Certificate Manager issues and renews certificates automatically. Certificates are pushed directly to NGINX with zero manual intervention required.</p></div>
    <div class="card"><div class="card-icon blue">&#9881;</div><h3>Agentless &amp; Agent-Based</h3><p>Supports both agentless (SSH-based) and agent-based certificate delivery. Certificate Manager handles the full install, reload, and verification cycle including nginx reload with zero downtime.</p></div>
    <div class="card"><div class="card-icon green">&#128200;</div><h3>Visibility &amp; Compliance</h3><p>Full certificate inventory and expiry tracking in the CyberArk dashboard. Instant alerts, policy enforcement, and a complete audit trail across all servers.</p></div>
  </div>

  <div class="info-strip">
    <div class="info-item"><div class="info-dot"></div><span class="info-label">Status</span><span class="info-value">Running</span></div>
    <div class="info-item"><span class="info-label">Server</span><span class="info-value">NGINX 1.x (CentOS 7)</span></div>
    <div class="info-item"><span class="info-label">Port</span><span class="info-value">8443 HTTPS</span></div>
    <div class="info-item"><span class="info-label">Domain</span><span class="info-value">venafilab.com</span></div>
  </div>
</div>
<footer>Powered by <span>CyberArk Certificate Manager</span> &amp; <span>Palo Alto Networks</span> &mdash; Demo Environment</footer>
</body></html>
