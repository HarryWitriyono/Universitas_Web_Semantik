<?php
require_once __DIR__.'/role_helper.php';
require_once __DIR__.'/csrf.php';
$error='';
$id_role=0;$data=['kode_role'=>'','nama_role'=>'','keterangan'=>''];
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        csrf_verify();
        $payload=['nama_role'=>trim($_POST['nama_role']??''),'keterangan'=>trim($_POST['keterangan']??'')];
$payload['kode_role']=strtoupper(trim($_POST['kode_role']??''));
        if($payload['kode_role']==='')throw new RuntimeException('Kode role dan nama role wajib diisi.');
        if(!preg_match('/^[A-Z0-9_]+$/',$payload['kode_role']))throw new RuntimeException('Kode role hanya boleh berisi huruf kapital, angka, dan underscore.');
        if(mb_strlen($payload['kode_role'])>30)throw new RuntimeException('Kode role maksimal 30 karakter.');

        if($payload['nama_role']==='')throw new RuntimeException('Nama role wajib diisi.');
        if(mb_strlen($payload['nama_role'])>100)throw new RuntimeException('Nama role maksimal 100 karakter.');
        if(mb_strlen($payload['keterangan'])>255)throw new RuntimeException('Keterangan maksimal 255 karakter.');
        $response=role_soa_call('create',$payload);
        if(!empty($response['error'])||($response['data']['success']??false)!==true)
            throw new RuntimeException(role_soa_message($response,'Operasi role gagal.'));
        $_SESSION['success']=role_soa_message($response,'Role berhasil disimpan.');
        header('Location: role.php');exit;
    }catch(Throwable $e){
        $error=$e->getMessage();
        $data['nama_role']=$payload['nama_role']??$data['nama_role'];
        $data['keterangan']=$payload['keterangan']??$data['keterangan'];
$data['kode_role']=$payload['kode_role']??$data['kode_role'];
    }
}
?>
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Tambah Role - SIM Mahasiswa</title><link rel="stylesheet" href="style.css"><style>
.role-form-card{width:100%;max-width:920px;background:#fff;border:1px solid #e5e7eb;border-radius:14px;box-shadow:0 4px 18px rgba(0,0,0,.05);overflow:hidden}
.role-form-header{display:flex;align-items:center;gap:15px;padding:22px 25px;background:#fbfdfb;border-bottom:1px solid #e5e7eb}
.role-form-icon{width:46px;height:46px;min-width:46px;border-radius:10px;background:#ecfdf5;display:flex;align-items:center;justify-content:center;font-size:22px}
.role-form-header h2{margin:0 0 5px;font-size:20px}.role-form-header p{margin:0;color:#6b7280;font-size:13px}
.role-form{display:block!important;width:100%!important;padding:25px!important;margin:0!important;box-sizing:border-box}
.role-grid{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr));gap:22px;width:100%}
.role-group{display:block!important;width:100%;min-width:0}.role-group.full{grid-column:1/-1}
.role-group label{display:block!important;margin:0 0 8px!important;color:#1f2937!important;font-size:14px!important;font-weight:700!important}
.role-input,.role-textarea{display:block!important;width:100%!important;box-sizing:border-box!important;border:1px solid #cbd5e1!important;border-radius:8px!important;background:#fff!important;color:#1f2937!important;font:14px Arial,sans-serif!important;padding:11px 13px!important}
.role-input{height:46px}.role-textarea{min-height:125px;resize:vertical}
.role-readonly{background:#f3f4f6!important;color:#4b5563!important}
.role-help{display:block;margin-top:7px;color:#6b7280;font-size:12px;line-height:1.5}
.role-footer{display:flex;justify-content:flex-end;gap:10px;margin-top:25px;padding-top:20px;border-top:1px solid #e5e7eb}
.role-footer .btn{min-width:120px}
@media(max-width:700px){.role-grid{grid-template-columns:1fr}.role-group.full{grid-column:auto}.role-form{padding:18px!important}.role-form-header{padding:18px}.role-footer{flex-direction:column-reverse}.role-footer .btn{width:100%}}
</style></head>
<body class="app-body"><?php include __DIR__.'/topbar.php'; ?><?php include __DIR__.'/sideleftbar.php'; ?>
<main class="main-content"><div class="page-heading page-heading-flex"><div><span class="eyebrow">MASTER DATA</span><h1>Tambah Role</h1><p>Tambahkan role baru ke dalam sistem.</p></div><a href="role.php" class="btn btn-outline">← Kembali</a></div>
<?php if($error!==''):?><div class="alert alert-danger"><?=role_h($error)?></div><?php endif;?>
<div class="role-form-card"><div class="role-form-header"><div class="role-form-icon">🔐</div><div><h2>Informasi Role</h2><p>Field bertanda <strong>*</strong> wajib diisi.</p></div></div>
<form class="role-form" method="post" action="role_tambah.php" autocomplete="off"><?=csrf_field()?>

<div class="role-grid"><div class="role-group"><label for="kode_role">Kode Role <span style="color:#dc2626">*</span></label><input class="role-input" type="text" id="kode_role" name="kode_role" maxlength="30" required value="<?=role_h($data['kode_role'])?>" placeholder="Contoh: OPERATOR_PRODI"><small class="role-help">Gunakan huruf kapital, angka, dan underscore.</small></div>
<div class="role-group"><label for="nama_role">Nama Role <span style="color:#dc2626">*</span></label><input class="role-input" type="text" id="nama_role" name="nama_role" maxlength="100" required value="<?=role_h($data['nama_role'])?>" placeholder="Contoh: Operator Program Studi"></div>
<div class="role-group full"><label for="keterangan">Keterangan</label><textarea class="role-textarea" id="keterangan" name="keterangan" maxlength="255" rows="5" placeholder="Jelaskan fungsi atau kewenangan role ini..."><?=role_h($data['keterangan']??'')?></textarea><small class="role-help">Maksimal 255 karakter.</small></div></div>
<div class="role-footer"><a href="role.php" class="btn btn-outline">Batal</a><button type="submit" class="btn btn-primary">Simpan Role</button></div>
</form></div></main></body></html>