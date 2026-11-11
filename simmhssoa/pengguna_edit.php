<?php
require_once __DIR__ . '/pengguna_helper.php';
require_once __DIR__ . '/csrf.php';

$target_id = (int)($_GET['id'] ?? $_POST['target_id_pengguna'] ?? 0);
if ($target_id <= 0) {
    $_SESSION['error'] = 'ID pengguna tidak valid.';
    header('Location: pengguna.php'); exit;
}
$detailResponse = pengguna_soa_call('detail', ['target_id_pengguna' => $target_id]);
$data_pengguna = pengguna_soa_data($detailResponse, 'pengguna');
if (!empty($detailResponse['error']) || !$data_pengguna) {
    $_SESSION['error'] = pengguna_soa_message($detailResponse, 'Data pengguna tidak ditemukan.');
    header('Location: pengguna.php'); exit;
}

$options = pengguna_options();
$roles = $options['roles'] ?? [];
$universitas = $options['universitas'] ?? [];
$fakultas = $options['fakultas'] ?? [];
$prodi = $options['program_studi'] ?? [];
if (!$roles || !$universitas) {
    $optionsError = 'Data pilihan Role atau Universitas belum dapat dimuat dari Provider SOA.';
} else {
    $optionsError = '';
}
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_verify();
        $payload = [
            'id_role' => (int)($_POST['id_role'] ?? 0),
            'id_universitas' => (int)($_POST['id_universitas'] ?? 0),
            'id_fakultas' => (int)($_POST['id_fakultas'] ?? 0),
            'id_program_studi' => (int)($_POST['id_program_studi'] ?? 0),
            'username' => trim($_POST['username'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'nama_lengkap' => trim($_POST['nama_lengkap'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'no_hp' => trim($_POST['no_hp'] ?? ''),
            'status_aktif' => $_POST['status_aktif'] ?? 'Aktif'
        ];
        $roleCode = '';
        foreach ($roles as $roleRow) {
            if ((int)$roleRow['id_role'] === $payload['id_role']) {
                $roleCode = $roleRow['kode_role'];
                break;
            }
        }
        if ($roleCode === '') {
            throw new RuntimeException('Role tidak ditemukan pada data pilihan.');
        }
        $scopeError = pengguna_validate_scope(
            $roleCode,
            $payload['id_universitas'] ?: null,
            $payload['id_fakultas'] ?: null,
            $payload['id_program_studi'] ?: null
        );
        if ($scopeError !== '') throw new RuntimeException($scopeError);

        $payload['target_id_pengguna'] = $target_id;
        $response = pengguna_soa_call('update', $payload);
        if (!empty($response['error'])) {
            throw new RuntimeException(pengguna_soa_message($response, 'Operasi pengguna gagal.'));
        }
        if (($response['data']['success'] ?? false) !== true) {
            throw new RuntimeException(pengguna_soa_message($response, 'Operasi pengguna gagal.'));
        }
        $_SESSION['success'] = pengguna_soa_message($response, 'Pengguna berhasil disimpan.');
        header('Location: pengguna.php'); exit;
    } catch (Throwable $e) {
        $error = $e->getMessage();
        $data_pengguna = array_merge($data_pengguna, [
            'id_role' => $payload['id_role'] ?? $data_pengguna['id_role'],
            'id_universitas' => $payload['id_universitas'] ?? $data_pengguna['id_universitas'],
            'id_fakultas' => $payload['id_fakultas'] ?? $data_pengguna['id_fakultas'],
            'id_program_studi' => $payload['id_program_studi'] ?? $data_pengguna['id_program_studi'],
            'username' => $payload['username'] ?? $data_pengguna['username'],
            'nama_lengkap' => $payload['nama_lengkap'] ?? $data_pengguna['nama_lengkap'],
            'email' => $payload['email'] ?? $data_pengguna['email'],
            'no_hp' => $payload['no_hp'] ?? $data_pengguna['no_hp'],
            'status_aktif' => $payload['status_aktif'] ?? $data_pengguna['status_aktif']
        ]);
    }
}
?>
<!DOCTYPE html>
<html lang="id"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Pengguna - SIM Mahasiswa</title><link rel="stylesheet" href="style.css">

<style>
.user-form-card{width:100%;max-width:1000px;background:#fff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;box-shadow:0 4px 18px rgba(0,0,0,.05)}
.user-form-header{display:flex;align-items:center;gap:15px;padding:22px 25px;background:#fbfdfb;border-bottom:1px solid #e5e7eb}
.user-form-icon{width:46px;height:46px;min-width:46px;border-radius:10px;background:#ecfdf5;display:flex;align-items:center;justify-content:center;font-size:21px}
.user-form-header h2{margin:0 0 4px;font-size:20px}.user-form-header p{margin:0;color:#6b7280;font-size:13px}
.user-form{display:block!important;width:100%!important;box-sizing:border-box!important;padding:25px!important;margin:0!important}
.user-form-grid{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;width:100%}
.user-form-group{display:block!important;min-width:0;width:100%}.user-form-full{grid-column:1/-1}
.user-form-group label{display:block!important;margin:0 0 8px!important;font-size:14px!important;font-weight:700!important;color:#1f2937!important}
.user-form-control{display:block!important;width:100%!important;box-sizing:border-box!important;min-height:44px!important;padding:11px 13px!important;border:1px solid #cbd5e1!important;border-radius:8px!important;background:#fff!important;color:#1f2937!important;font:inherit!important}
.user-form-control:focus{outline:2px solid #bbf7d0!important;border-color:#166534!important}
.user-form-help{display:block;margin-top:6px;color:#6b7280;font-size:12px;line-height:1.5}
.user-form-footer{display:flex;justify-content:flex-end;gap:10px;margin-top:25px;padding-top:20px;border-top:1px solid #e5e7eb}
.user-form-footer .btn{min-width:120px}.user-scope-box{padding:15px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px}
.user-scope-title{font-size:13px;font-weight:700;margin-bottom:12px;color:#374151}
.user-scope-note,.password-note{font-size:12px;color:#6b7280;margin-top:8px;line-height:1.5}
@media(max-width:700px){.user-form-grid{grid-template-columns:1fr}.user-form-full{grid-column:auto}.user-form{padding:18px!important}.user-form-header{padding:18px}.user-form-footer{flex-direction:column-reverse}.user-form-footer .btn{width:100%}}
</style>

</head><body class="app-body">
<?php include __DIR__ . '/topbar.php'; ?>
<?php include __DIR__ . '/sideleftbar.php'; ?>
<main class="main-content">
    <div class="page-heading page-heading-flex">
        <div><span class="eyebrow">MASTER DATA</span><h1>Edit Pengguna</h1>
        <p>Perbarui akun dan scope kewenangan pengguna.</p></div>
        <a href="pengguna.php" class="btn btn-outline">← Kembali</a>
    </div>
    <?php if ($error !== ''): ?><div class="alert alert-danger"><?= pengguna_h($error) ?></div><?php endif; ?>
    <?php if ($optionsError !== ''): ?><div class="alert alert-danger"><?= pengguna_h($optionsError) ?></div><?php endif; ?>

    <div class="user-form-card">
        <div class="user-form-header"><div class="user-form-icon">👤</div><div>
            <h2>Informasi Pengguna</h2><p>Field bertanda <strong>*</strong> wajib diisi.</p>
        </div></div>
        <form method="post" class="user-form" action="pengguna_edit.php?id=<?= (int)$target_id ?>" autocomplete="off">
            <?= csrf_field() ?>
            <input type="hidden" name="target_id_pengguna" value="<?= (int)$target_id ?>">
            <div class="user-form-grid">
                <div class="user-form-group">
                    <label for="id_role">Role <span style="color:#dc2626">*</span></label>
                    <select class="user-form-control" id="id_role" name="id_role" required>
                        <option value="">-- Pilih Role --</option>
                        <?php foreach ($roles as $r): ?>
                        <option value="<?= (int)$r['id_role'] ?>" data-kode="<?= pengguna_h($r['kode_role']) ?>"
                            <?= (string)($data_pengguna['id_role'] ?? '') === (string)$r['id_role'] ? 'selected' : '' ?>>
                            <?= pengguna_h($r['nama_role']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="user-form-group">
                    <label for="username">Username <span style="color:#dc2626">*</span></label>
                    <input class="user-form-control" type="text" id="username" name="username" maxlength="100" required value="<?= pengguna_h($data_pengguna['username'] ?? '') ?>">
                </div>
                <div class="user-form-group">
                    <label for="password">Password Baru  </label>
                    <input class="user-form-control" type="password" id="password" name="password" minlength="8"  >
                    <div class="password-note">Kosongkan jika password tidak ingin diubah.</div>
                </div>
                <div class="user-form-group">
                    <label for="nama_lengkap">Nama Lengkap <span style="color:#dc2626">*</span></label>
                    <input class="user-form-control" type="text" id="nama_lengkap" name="nama_lengkap" maxlength="200" required value="<?= pengguna_h($data_pengguna['nama_lengkap'] ?? '') ?>">
                </div>
                <div class="user-form-group">
                    <label for="email">Email</label>
                    <input class="user-form-control" type="email" id="email" name="email" maxlength="150" value="<?= pengguna_h($data_pengguna['email'] ?? '') ?>">
                </div>
                <div class="user-form-group">
                    <label for="no_hp">No. HP</label>
                    <input class="user-form-control" type="text" id="no_hp" name="no_hp" maxlength="30" value="<?= pengguna_h($data_pengguna['no_hp'] ?? '') ?>">
                </div>
                <div class="user-form-group">
                    <label for="status_aktif">Status</label>
                    <select class="user-form-control" id="status_aktif" name="status_aktif">
                        <option value="Aktif" <?= ($data_pengguna['status_aktif'] ?? 'Aktif') === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                        <option value="Tidak Aktif" <?= ($data_pengguna['status_aktif'] ?? '') === 'Tidak Aktif' ? 'selected' : '' ?>>Tidak Aktif</option>
                    </select>
                </div>
                <div class="user-form-group user-form-full">
                    <div class="user-scope-box">
                        <div class="user-scope-title">Scope Kewenangan</div>
                        <div class="user-form-grid">
                            <div class="user-form-group" id="group_universitas">
                                <label for="id_universitas">Universitas</label>
                                <select class="user-form-control" id="id_universitas" name="id_universitas">
                                    <option value="">-- Pilih Universitas --</option>
                                    <?php foreach ($universitas as $u): ?>
                                    <option value="<?= (int)$u['id_universitas'] ?>" <?= (string)($data_pengguna['id_universitas'] ?? '') === (string)$u['id_universitas'] ? 'selected' : '' ?>>
                                        <?= pengguna_h($u['kode_universitas'] . ' - ' . $u['nama_universitas']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="user-form-group" id="group_fakultas">
                                <label for="id_fakultas">Fakultas</label>
                                <select class="user-form-control" id="id_fakultas" name="id_fakultas">
                                    <option value="">-- Pilih Fakultas --</option>
                                    <?php foreach ($fakultas as $f): ?>
                                    <option value="<?= (int)$f['id_fakultas'] ?>" data-universitas="<?= (int)$f['id_universitas'] ?>" <?= (string)($data_pengguna['id_fakultas'] ?? '') === (string)$f['id_fakultas'] ? 'selected' : '' ?>>
                                        <?= pengguna_h($f['kode_fakultas'] . ' - ' . $f['nama_fakultas']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="user-form-group" id="group_prodi">
                                <label for="id_program_studi">Program Studi</label>
                                <select class="user-form-control" id="id_program_studi" name="id_program_studi">
                                    <option value="">-- Pilih Program Studi --</option>
                                    <?php foreach ($prodi as $p): ?>
                                    <option value="<?= (int)$p['id_program_studi'] ?>" data-fakultas="<?= (int)$p['id_fakultas'] ?>" <?= (string)($data_pengguna['id_program_studi'] ?? '') === (string)$p['id_program_studi'] ? 'selected' : '' ?>>
                                        <?= pengguna_h($p['kode_program_studi'] . ' - ' . $p['nama_program_studi']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="user-scope-note">
                            Scope mengikuti role. Operator Program Studi dan Mahasiswa menggunakan Universitas → Fakultas → Program Studi; Dekanat menggunakan Universitas → Fakultas; Rektorat dan Admin menggunakan Universitas.
                        </div>
                    </div>
                </div>
            </div>
            <div class="user-form-footer">
                <a href="pengguna.php" class="btn btn-outline">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</main>

<script>
(function(){
    const roleSelect=document.getElementById('id_role');
    const uni=document.getElementById('id_universitas');
    const fakultas=document.getElementById('id_fakultas');
    const prodi=document.getElementById('id_program_studi');
    if(!roleSelect||!uni||!fakultas||!prodi)return;

    const fakultasData=Array.from(fakultas.options).filter(o=>o.value).map(o=>({
        value:o.value,text:o.text,universitas:o.dataset.universitas||''
    }));
    const prodiData=Array.from(prodi.options).filter(o=>o.value).map(o=>({
        value:o.value,text:o.text,fakultas:o.dataset.fakultas||''
    }));

    function filterFakultas(selected=''){
        const uniValue=uni.value;
        fakultas.innerHTML='<option value="">-- Pilih Fakultas --</option>';
        fakultasData.forEach(item=>{
            if(!uniValue||item.universitas===uniValue){
                const o=document.createElement('option');o.value=item.value;o.textContent=item.text;
                o.dataset.universitas=item.universitas;if(item.value===selected)o.selected=true;
                fakultas.appendChild(o);
            }
        });
        filterProdi(fakultas.value,'');
    }
    function filterProdi(selectedFaculty='',selected=''){
        prodi.innerHTML='<option value="">-- Pilih Program Studi --</option>';
        prodiData.forEach(item=>{
            if(!selectedFaculty||item.fakultas===selectedFaculty){
                const o=document.createElement('option');o.value=item.value;o.textContent=item.text;
                o.dataset.fakultas=item.fakultas;if(item.value===selected)o.selected=true;
                prodi.appendChild(o);
            }
        });
    }
    function applyRole(){
        const code=roleSelect.options[roleSelect.selectedIndex]?.dataset.kode||'';
        const needUni=['ADMIN','OPERATOR_PRODI','DEKANAT','REKTORAT','MAHASISWA'].includes(code);
        const needFaculty=['OPERATOR_PRODI','DEKANAT','MAHASISWA'].includes(code);
        const needProdi=['OPERATOR_PRODI','MAHASISWA'].includes(code);
        document.getElementById('group_universitas').style.display=needUni?'':'none';
        document.getElementById('group_fakultas').style.display=needFaculty?'':'none';
        document.getElementById('group_prodi').style.display=needProdi?'':'none';
        uni.required=needUni;fakultas.required=needFaculty;prodi.required=needProdi;
        if(!needUni){uni.value='';fakultas.value='';prodi.value='';}
        else if(!needFaculty){fakultas.value='';prodi.value='';}
        else if(!needProdi){prodi.value='';}
    }
    uni.addEventListener('change',()=>filterFakultas(''));
    fakultas.addEventListener('change',()=>filterProdi(fakultas.value,''));
    roleSelect.addEventListener('change',applyRole);

    const initialFaculty=fakultas.value, initialStudy=prodi.value;
    filterFakultas(initialFaculty);
    if(initialFaculty)filterProdi(initialFaculty,initialStudy);
    applyRole();
})();
</script>

</body></html>
