<?php
require_once __DIR__ . '/koneksi.php';
header('Content-Type: application/json; charset=utf-8');

function role_json(bool $ok,string $message='',array $data=[],int $code=200):void{
    http_response_code($code);
    echo json_encode(['success'=>$ok,'message'=>$message,'data'=>$data],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}
function role_input():array{
    $raw=file_get_contents('php://input'); $json=json_decode($raw,true);
    return is_array($json)?$json:($_POST?:[]);
}
function role_admin_valid(mysqli $db,int $actor):bool{
    if($actor<=0)return false;
    $st=mysqli_prepare($db,"SELECT p.id_pengguna FROM pengguna p INNER JOIN roles r ON r.id_role=p.id_role WHERE p.id_pengguna=? AND p.status_aktif='Aktif' AND r.kode_role='ADMIN' LIMIT 1");
    if(!$st)return false;
    mysqli_stmt_bind_param($st,'i',$actor); mysqli_stmt_execute($st);
    $row=mysqli_fetch_assoc(mysqli_stmt_get_result($st)); mysqli_stmt_close($st);
    return (bool)$row;
}
$remote=$_SERVER['REMOTE_ADDR']??'';
if(!in_array($remote,['127.0.0.1','::1'],true)) role_json(false,'Akses Provider ditolak.',[],403);
$in=role_input(); $actor=(int)($in['id_pengguna']??0);
if(!role_admin_valid($koneksi,$actor)) role_json(false,'Akses ditolak. Pengguna ADMIN aktif diperlukan.',[],403);
$action=(string)($in['action']??$_GET['action']??'list');

if($action==='list'){
    $res=mysqli_query($koneksi,"SELECT id_role,kode_role,nama_role,keterangan,created_at FROM roles ORDER BY id_role ASC");
    if(!$res)role_json(false,'Gagal mengambil data role.',[],500);
    $rows=[];while($r=mysqli_fetch_assoc($res))$rows[]=$r;
    mysqli_free_result($res);
    role_json(true,'Data role berhasil diambil.',['roles'=>$rows]);
}
if($action==='detail'){
    $id=(int)($in['target_id_role']??0);
    if($id<=0)role_json(false,'ID role tidak valid.',[],422);
    $st=mysqli_prepare($koneksi,"SELECT id_role,kode_role,nama_role,keterangan,created_at FROM roles WHERE id_role=? LIMIT 1");
    mysqli_stmt_bind_param($st,'i',$id);mysqli_stmt_execute($st);
    $row=mysqli_fetch_assoc(mysqli_stmt_get_result($st));mysqli_stmt_close($st);
    if(!$row)role_json(false,'Data role tidak ditemukan.',[],404);
    role_json(true,'Detail role berhasil diambil.',['role'=>$row]);
}
if($action==='create'){
    $code=strtoupper(trim((string)($in['kode_role']??'')));
    $name=trim((string)($in['nama_role']??''));$desc=trim((string)($in['keterangan']??''));
    if($code===''||$name==='')role_json(false,'Kode role dan nama role wajib diisi.',[],422);
    if(!preg_match('/^[A-Z0-9_]+$/',$code))role_json(false,'Kode role hanya boleh berisi huruf kapital, angka, dan underscore.',[],422);
    if(mb_strlen($code)>30)role_json(false,'Kode role maksimal 30 karakter.',[],422);
    if(mb_strlen($name)>100)role_json(false,'Nama role maksimal 100 karakter.',[],422);
    if(mb_strlen($desc)>255)role_json(false,'Keterangan maksimal 255 karakter.',[],422);
    $st=mysqli_prepare($koneksi,"SELECT id_role FROM roles WHERE kode_role=? LIMIT 1");
    mysqli_stmt_bind_param($st,'s',$code);mysqli_stmt_execute($st);
    $exists=mysqli_fetch_assoc(mysqli_stmt_get_result($st));mysqli_stmt_close($st);
    if($exists)role_json(false,'Kode role sudah digunakan.',[],409);
    $st=mysqli_prepare($koneksi,"INSERT INTO roles(kode_role,nama_role,keterangan) VALUES(?,?,NULLIF(?,''))");
    if(!$st)role_json(false,'Gagal menyiapkan penyimpanan role.',[],500);
    mysqli_stmt_bind_param($st,'sss',$code,$name,$desc);
    if(!mysqli_stmt_execute($st)){$err=mysqli_stmt_error($st);mysqli_stmt_close($st);role_json(false,'Gagal menambahkan role: '.$err,[],500);}
    $id=mysqli_insert_id($koneksi);mysqli_stmt_close($st);
    role_json(true,'Role berhasil ditambahkan.',['id_role'=>$id]);
}
if($action==='update'){
    $id=(int)($in['target_id_role']??0);
    $name=trim((string)($in['nama_role']??''));$desc=trim((string)($in['keterangan']??''));
    if($id<=0)role_json(false,'ID role tidak valid.',[],422);
    if($name==='')role_json(false,'Nama role wajib diisi.',[],422);
    if(mb_strlen($name)>100)role_json(false,'Nama role maksimal 100 karakter.',[],422);
    if(mb_strlen($desc)>255)role_json(false,'Keterangan maksimal 255 karakter.',[],422);
    $st=mysqli_prepare($koneksi,"SELECT id_role FROM roles WHERE id_role=? LIMIT 1");
    mysqli_stmt_bind_param($st,'i',$id);mysqli_stmt_execute($st);$found=mysqli_fetch_assoc(mysqli_stmt_get_result($st));mysqli_stmt_close($st);
    if(!$found)role_json(false,'Data role tidak ditemukan.',[],404);
    $st=mysqli_prepare($koneksi,"UPDATE roles SET nama_role=?,keterangan=NULLIF(?, '') WHERE id_role=?");
    if(!$st)role_json(false,'Gagal menyiapkan proses update.',[],500);
    mysqli_stmt_bind_param($st,'ssi',$name,$desc,$id);
    if(!mysqli_stmt_execute($st)){$err=mysqli_stmt_error($st);mysqli_stmt_close($st);role_json(false,'Gagal memperbarui role: '.$err,[],500);}
    mysqli_stmt_close($st);role_json(true,'Role berhasil diperbarui.',['id_role'=>$id]);
}
if($action==='delete'){
    $id=(int)($in['target_id_role']??0);
    if($id<=0)role_json(false,'ID role tidak valid.',[],422);
    $st=mysqli_prepare($koneksi,"SELECT kode_role FROM roles WHERE id_role=? LIMIT 1");
    mysqli_stmt_bind_param($st,'i',$id);mysqli_stmt_execute($st);
    $row=mysqli_fetch_assoc(mysqli_stmt_get_result($st));mysqli_stmt_close($st);
    if(!$row)role_json(false,'Role tidak ditemukan.',[],404);
    $core=['ADMIN','OPERATOR_PRODI','DEKANAT','REKTORAT','MAHASISWA'];
    if(in_array($row['kode_role'],$core,true))role_json(false,'Role inti sistem tidak boleh dihapus.',[],409);
    $st=mysqli_prepare($koneksi,"SELECT COUNT(*) AS total FROM pengguna WHERE id_role=?");
    mysqli_stmt_bind_param($st,'i',$id);mysqli_stmt_execute($st);
    $count=mysqli_fetch_assoc(mysqli_stmt_get_result($st));mysqli_stmt_close($st);
    if((int)$count['total']>0)role_json(false,'Role tidak dapat dihapus karena masih digunakan oleh pengguna.',[],409);
    $st=mysqli_prepare($koneksi,"DELETE FROM roles WHERE id_role=?");
    if(!$st)role_json(false,'Gagal menyiapkan proses hapus.',[],500);
    mysqli_stmt_bind_param($st,'i',$id);
    if(!mysqli_stmt_execute($st)){$err=mysqli_stmt_error($st);mysqli_stmt_close($st);role_json(false,'Gagal menghapus role: '.$err,[],409);}
    mysqli_stmt_close($st);role_json(true,'Role berhasil dihapus.',['id_role'=>$id]);
}
role_json(false,'Action API tidak dikenal.',[],400);
