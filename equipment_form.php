<?php
require_once __DIR__.'/config.php'; require_admin(); $pdo=db();
$id=(int)($_GET['id']??0); $editing=$id>0; $errors=[];
$fields=['asset_tag','name','category','manufacturer','model','serial_number','ip_address','mac_address','location','department','assigned_to','purchase_date','warranty_expiry','status','condition_note','notes'];
$data=array_fill_keys($fields,''); $data['status']='In Use'; $data['condition_note']='Good';
if($editing){$st=$pdo->prepare('SELECT * FROM equipment WHERE id=?');$st->execute([$id]);$found=$st->fetch();if(!$found){flash('Equipment record not found.','error');redirect('equipment.php');}$data=array_merge($data,$found);}
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    foreach($fields as $f) $data[$f]=trim((string)($_POST[$f]??''));
    if($data['asset_tag']==='')$errors[]='Asset tag is required.';
    if($data['name']==='')$errors[]='Equipment name is required.';
    if($data['category']==='')$errors[]='Category is required.';
    if($data['location']==='')$errors[]='Location is required.';
    if(!in_array($data['status'],statuses(),true))$errors[]='Choose a valid status.';
    if(!in_array($data['condition_note'],conditions(),true))$errors[]='Choose a valid condition.';
    $check=$pdo->prepare('SELECT id FROM equipment WHERE asset_tag=? AND id<>?');$check->execute([$data['asset_tag'],$id]);if($check->fetch())$errors[]='That asset tag already exists. Use a unique tag.';
    foreach(['purchase_date','warranty_expiry'] as $dateField){if($data[$dateField]!==''&&!preg_match('/^\d{4}-\d{2}-\d{2}$/',$data[$dateField]))$errors[]='Dates must use the YYYY-MM-DD format.';}
    if(!$errors){
        $values=[];foreach($fields as $f)$values[]=$data[$f];
        if($editing){$sql='UPDATE equipment SET '.implode('=?,', $fields).'=?, updated_at=CURRENT_TIMESTAMP WHERE id=?';$values[]=$id;$st=$pdo->prepare($sql);$st->execute($values);flash('Equipment updated successfully.');}
        else{$sql='INSERT INTO equipment ('.implode(',',$fields).') VALUES ('.implode(',',array_fill(0,count($fields),'?')).')';$st=$pdo->prepare($sql);$st->execute($values);flash('Equipment added successfully.');}
        redirect('equipment.php');
    }
}
$pageTitle=$editing?'Edit equipment':'Add equipment'; require 'header.php';
?>
<div class="page-heading"><div><div class="eyebrow">ASSET DETAILS</div><h1><?=$editing?'Edit equipment':'Add equipment'?></h1><p>Capture identification, network configuration and ownership information.</p></div><a class="btn secondary" href="equipment.php">← Back to register</a></div>
<?php if($errors): ?><div class="alert error"><b>Please correct the following:</b><ul><?php foreach($errors as $err): ?><li><?=e($err)?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" class="form-panel"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
<div class="form-section"><div class="section-title"><span>01</span><div><h2>Identification</h2><p>Basic asset and manufacturer details</p></div></div><div class="form-grid">
<div><label>Asset tag <em>*</em></label><input name="asset_tag" required value="<?=e($data['asset_tag'])?>" placeholder="e.g. NET-0006"></div>
<div><label>Equipment name <em>*</em></label><input name="name" required value="<?=e($data['name'])?>" placeholder="e.g. Floor 2 access switch"></div>
<div><label>Category <em>*</em></label><select name="category" required><option value="">Select category</option><?php foreach(categories() as $c): ?><option <?= $data['category']===$c?'selected':''?> value="<?=e($c)?>"><?=e($c)?></option><?php endforeach; ?></select></div>
<div><label>Manufacturer</label><input name="manufacturer" value="<?=e($data['manufacturer'])?>" placeholder="e.g. Cisco"></div>
<div><label>Model</label><input name="model" value="<?=e($data['model'])?>" placeholder="Model number"></div>
<div><label>Serial number</label><input name="serial_number" value="<?=e($data['serial_number'])?>" placeholder="Manufacturer serial number"></div>
</div></div>
<div class="form-section"><div class="section-title"><span>02</span><div><h2>Network & location</h2><p>Connection details and physical placement</p></div></div><div class="form-grid">
<div><label>IP address</label><input name="ip_address" value="<?=e($data['ip_address'])?>" placeholder="e.g. 192.168.1.10"></div>
<div><label>MAC address</label><input name="mac_address" value="<?=e($data['mac_address'])?>" placeholder="e.g. 00:11:22:33:44:55"></div>
<div><label>Location <em>*</em></label><input name="location" required value="<?=e($data['location'])?>" placeholder="e.g. Server Room"></div>
<div><label>Department</label><input name="department" value="<?=e($data['department'])?>" placeholder="e.g. ICT"></div>
<div><label>Assigned to</label><input name="assigned_to" value="<?=e($data['assigned_to'])?>" placeholder="Person, team or department"></div>
</div></div>
<div class="form-section"><div class="section-title"><span>03</span><div><h2>Lifecycle & condition</h2><p>Procurement, warranty and current status</p></div></div><div class="form-grid">
<div><label>Purchase date</label><input type="date" name="purchase_date" value="<?=e($data['purchase_date'])?>"></div>
<div><label>Warranty expiry</label><input type="date" name="warranty_expiry" value="<?=e($data['warranty_expiry'])?>"></div>
<div><label>Asset status <em>*</em></label><select name="status"><?php foreach(statuses() as $s): ?><option <?= $data['status']===$s?'selected':''?> value="<?=e($s)?>"><?=e($s)?></option><?php endforeach; ?></select></div>
<div><label>Physical condition <em>*</em></label><select name="condition_note"><?php foreach(conditions() as $c): ?><option <?= $data['condition_note']===$c?'selected':''?> value="<?=e($c)?>"><?=e($c)?></option><?php endforeach; ?></select></div>
<div class="span-2"><label>Notes</label><textarea name="notes" rows="3" placeholder="Configuration notes, purpose, or other details"><?=e($data['notes'])?></textarea></div>
</div></div><div class="form-actions"><a class="btn plain" href="equipment.php">Cancel</a><button class="btn primary" type="submit"><?=$editing?'Save changes':'Save equipment'?> <span>→</span></button></div></form>
<?php require 'footer.php'; ?>
