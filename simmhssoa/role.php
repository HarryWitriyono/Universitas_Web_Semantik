<?php
require_once __DIR__.'/role_helper.php';
$response=role_soa_call('list');
$rows=role_soa_rows($response,'roles');
$error=(!empty($response['error'])||($response['data']['success']??false)!==true)?role_soa_message($response,'Gagal mengambil data role.'):'';
?>
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Role - SIM Mahasiswa</title><link rel="stylesheet" href="style.css"></head>
<body class="app-body">
<?php include __DIR__.'/topbar.php'; ?><?php include __DIR__.'/sideleftbar.php'; ?>
<main class="main-content">
<div class="page-heading page-heading-flex"><div><span class="eyebrow">MASTER DATA</span><h1>Role Pengguna</h1><p>Kelola role dan hak akses dasar pengguna aplikasi.</p></div><a href="role_tambah.php" class="btn btn-primary">+ Tambah Role</a></div>
<?php if(isset($_SESSION['success'])):?><div class="alert alert-success"><?=role_h($_SESSION['success'])?></div><?php unset($_SESSION['success']);endif;?>
<?php if(isset($_SESSION['error'])):?><div class="alert alert-danger"><?=role_h($_SESSION['error'])?></div><?php unset($_SESSION['error']);endif;?>
<?php if($error!==''):?><div class="alert alert-danger"><?=role_h($error)?></div><?php endif;?>
<div class="dashboard-panel"><div class="table-responsive"><table class="data-table">
<thead><tr><th width="70">No.</th><th width="150">Kode Role</th><th>Nama Role</th><th>Keterangan</th><th width="160">Dibuat</th><th width="150">Aksi</th></tr></thead><tbody>
<?php if($rows):$no=1;foreach($rows as $row):?><tr>
<td><?=$no++?></td><td><span class="badge"><?=role_h($row['kode_role'])?></span></td><td><strong><?=role_h($row['nama_role'])?></strong></td><td><?=role_h($row['keterangan']??'-')?></td><td><?=role_h($row['created_at']??'-')?></td>
<td><div class="action-group"><a class="btn btn-sm btn-outline" href="role_edit.php?id=<?=(int)$row['id_role']?>">Edit</a>
<form action="role_hapus.php" method="post" onsubmit="return confirm('Hapus role ini?');"><?=csrf_field()?><input type="hidden" name="id_role" value="<?=(int)$row['id_role']?>"><button type="submit" class="btn btn-sm btn-danger">Hapus</button></form></div></td></tr>
<?php endforeach;else:?><tr><td colspan="6" class="empty-state"><?=$error!==''?'Data role belum dapat dimuat dari Provider SOA.':'Belum ada data role.'?></td></tr><?php endif;?>
</tbody></table></div></div></main></body></html>
