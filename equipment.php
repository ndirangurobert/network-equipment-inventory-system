<?php
require_once __DIR__.'/config.php'; require_admin(); $pdo=db();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    if (($_POST['action'] ?? '')==='delete') {
        $stmt=$pdo->prepare('DELETE FROM equipment WHERE id=?'); $stmt->execute([(int)($_POST['id']??0)]);
        flash('Equipment record deleted.'); redirect('equipment.php');
    }
}
$q=trim($_GET['q']??''); $status=trim($_GET['status']??''); $category=trim($_GET['category']??'');
$sql='SELECT * FROM equipment WHERE 1=1'; $params=[];
if($q!==''){ $sql.=" AND (asset_tag LIKE ? OR name LIKE ? OR serial_number LIKE ? OR ip_address LIKE ? OR location LIKE ? OR manufacturer LIKE ?)"; $like='%'.$q.'%'; array_push($params,$like,$like,$like,$like,$like,$like); }
if($status!==''){ $sql.=' AND status=?'; $params[]=$status; }
if($category!==''){ $sql.=' AND category=?'; $params[]=$category; }
$sql.=' ORDER BY id DESC'; $stmt=$pdo->prepare($sql); $stmt->execute($params); $rows=$stmt->fetchAll();
$pageTitle='Equipment register'; require 'header.php';
?>
<div class="page-heading"><div><div class="eyebrow">ASSET CONTROL</div><h1>Equipment register</h1><p>Search, review and maintain your organization’s network assets.</p></div><a class="btn primary" href="equipment_form.php">＋ Add equipment</a></div>
<?php show_flash(); ?>
<section class="panel"><form class="filter-bar" method="get"><div class="search-box"><span>⌕</span><input name="q" value="<?=e($q)?>" placeholder="Search asset, serial, IP address, location…"></div><select name="category"><option value="">All categories</option><?php foreach(categories() as $c): ?><option <?= $category===$c?'selected':''?> value="<?=e($c)?>"><?=e($c)?></option><?php endforeach; ?></select><select name="status"><option value="">All statuses</option><?php foreach(statuses() as $s): ?><option <?= $status===$s?'selected':''?> value="<?=e($s)?>"><?=e($s)?></option><?php endforeach; ?></select><button class="btn secondary" type="submit">Filter</button><a class="btn plain" href="equipment.php">Reset</a></form>
<div class="table-meta"><span><b><?=count($rows)?></b> record(s) found</span><a class="text-link" href="reports.php">Export data →</a></div>
<div class="table-wrap"><table><thead><tr><th>ASSET DETAILS</th><th>NETWORK</th><th>LOCATION / OWNER</th><th>CONDITION</th><th>STATUS</th><th class="right">ACTIONS</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><div class="asset-cell"><span class="asset-symbol">⌁</span><div><b><?=e($r['name'])?></b><small><?=e($r['asset_tag'])?> · <?=e($r['category'])?></small></div></div></td><td><div><?=e($r['ip_address'] ?: 'No IP assigned')?></div><small class="muted"><?=e($r['manufacturer'].' '.$r['model'])?></small></td><td><div><?=e($r['location'] ?: '—')?></div><small class="muted"><?=e($r['assigned_to'] ?: $r['department'] ?: 'Unassigned')?></small></td><td><span class="condition <?=strtolower(str_replace(' ','-',e($r['condition_note'])))?>"><?=e($r['condition_note'])?></span></td><td><span class="status <?=strtolower(str_replace(' ','-',e($r['status'])))?>"><?=e($r['status'])?></span></td><td class="right actions"><a class="action-link" href="equipment_form.php?id=<?=(int)$r['id']?>">Edit</a><form method="post" class="inline-form" onsubmit="return confirm('Delete this equipment record? Related maintenance logs will also be deleted.')"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=(int)$r['id']?>"><button class="action-link danger-link" type="submit">Delete</button></form><a class="action-link" href="maintenance.php?equipment_id=<?=(int)$r['id']?>">Service</a></td></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="6" class="empty">No records match your search. Try changing the filters or add equipment.</td></tr><?php endif; ?></tbody></table></div></section>
<?php require 'footer.php'; ?>
