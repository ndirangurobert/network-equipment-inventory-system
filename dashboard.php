<?php
require_once __DIR__.'/config.php'; require_admin();
$pdo = db();
$total = (int)$pdo->query('SELECT COUNT(*) FROM equipment')->fetchColumn();
$inUse = (int)$pdo->query("SELECT COUNT(*) FROM equipment WHERE status='In Use'")->fetchColumn();
$storage = (int)$pdo->query("SELECT COUNT(*) FROM equipment WHERE status='In Storage'")->fetchColumn();
$repair = (int)$pdo->query("SELECT COUNT(*) FROM equipment WHERE status='Under Maintenance' OR condition_note IN ('Needs Repair','Damaged')")->fetchColumn();
$categoryRows = $pdo->query('SELECT category, COUNT(*) total FROM equipment GROUP BY category ORDER BY total DESC LIMIT 6')->fetchAll();
$recent = $pdo->query('SELECT * FROM equipment ORDER BY id DESC LIMIT 6')->fetchAll();
$pageTitle='Dashboard'; require 'header.php';
?>
<div class="page-heading"><div><div class="eyebrow">OVERVIEW</div><h1>Good to see you, <?=e(explode(' ', $_SESSION['full_name'] ?? 'Administrator')[0])?>.</h1><p>Here’s what is happening across your network equipment inventory.</p></div><a class="btn primary" href="equipment_form.php">＋ Add equipment</a></div>
<div class="stats-grid">
<div class="stat-card"><div class="stat-top"><span class="stat-icon blue">▤</span><span class="stat-caption">ALL ASSETS</span></div><div class="stat-value"><?=number_format($total)?></div><div class="stat-bottom">Equipment records in register</div></div>
<div class="stat-card"><div class="stat-top"><span class="stat-icon green">✓</span><span class="stat-caption">DEPLOYED</span></div><div class="stat-value"><?=number_format($inUse)?></div><div class="stat-bottom">Currently in use</div></div>
<div class="stat-card"><div class="stat-top"><span class="stat-icon amber">▣</span><span class="stat-caption">IN STORAGE</span></div><div class="stat-value"><?=number_format($storage)?></div><div class="stat-bottom">Available / spare assets</div></div>
<div class="stat-card"><div class="stat-top"><span class="stat-icon red">⚒</span><span class="stat-caption">NEEDS ATTENTION</span></div><div class="stat-value"><?=number_format($repair)?></div><div class="stat-bottom">Maintenance or condition flag</div></div>
</div>
<div class="dashboard-grid"><section class="panel"><div class="panel-heading"><div><h2>Recently added equipment</h2><p>Latest records in the equipment register</p></div><a class="text-link" href="equipment.php">View all →</a></div>
<div class="table-wrap"><table><thead><tr><th>ASSET</th><th>CATEGORY</th><th>LOCATION</th><th>STATUS</th></tr></thead><tbody>
<?php foreach($recent as $r): ?><tr><td><div class="asset-cell"><span class="asset-symbol">⌁</span><div><b><?=e($r['name'])?></b><small><?=e($r['asset_tag'])?></small></div></div></td><td><?=e($r['category'])?></td><td><?=e($r['location'] ?: '—')?></td><td><span class="status <?=strtolower(str_replace(' ','-',e($r['status'])))?>"><?=e($r['status'])?></span></td></tr><?php endforeach; ?>
<?php if(!$recent): ?><tr><td colspan="4" class="empty">No equipment recorded yet.</td></tr><?php endif; ?></tbody></table></div></section>
<section class="panel category-panel"><div class="panel-heading"><div><h2>Assets by category</h2><p>Distribution of registered equipment</p></div></div>
<?php if(!$categoryRows): ?><p class="empty">Add equipment to see category breakdown.</p><?php else: foreach($categoryRows as $c): $pct = $total ? round($c['total']/$total*100) : 0; ?><div class="category-row"><div class="category-label"><span><?=e($c['category'])?></span><b><?=e($c['total'])?></b></div><div class="progress"><span style="width:<?=$pct?>%"></span></div></div><?php endforeach; endif; ?>
<a class="btn secondary full" href="reports.php">Open inventory reports</a></section></div>
<div class="notice"><span class="notice-icon">i</span><div><b>Project demo data is included</b><p>Sample network devices are preloaded so you can explore the dashboard immediately. Replace them with your organization’s actual records when ready.</p></div></div>
<?php require 'footer.php'; ?>
