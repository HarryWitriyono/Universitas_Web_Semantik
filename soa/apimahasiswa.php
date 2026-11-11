<?php
/** SOA Provider: data mahasiswa dan profil. Letakkan di htdocs/soa/ */
require_once __DIR__.'/koneksi.php';
header('Content-Type: application/json; charset=utf-8');
function mj_out($ok,$msg='',$data=[],$code=200){http_response_code($code);echo json_encode(['success'=>$ok,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
$in=json_decode(file_get_contents('php://input'),true) ?: $_POST;
$action=$in['action']??'list'; $uid=(int)($in['id_pengguna']??0);
if(!in_array($_SERVER['REMOTE_ADDR']??'', ['127.0.0.1','::1'],true)) mj_out(false,'Akses provider ditolak.',[],403);
$s=mysqli_prepare($koneksi,"SELECT p.id_pengguna,r.kode_role,p.id_program_studi,p.id_fakultas,p.id_universitas FROM pengguna p JOIN roles r ON r.id_role=p.id_role WHERE p.id_pengguna=? AND p.status_aktif='Aktif' LIMIT 1");
mysqli_stmt_bind_param($s,'i',$uid);mysqli_stmt_execute($s);$actor=mysqli_fetch_assoc(mysqli_stmt_get_result($s));mysqli_stmt_close($s);
if(!$actor)mj_out(false,'Pengguna tidak aktif/tidak ditemukan.',[],403);
$role=$actor['kode_role']; if(!in_array($role,['ADMIN','OPERATOR_PRODI','DEKANAT','REKTORAT','MAHASISWA'],true))mj_out(false,'Role tidak diizinkan.',[],403);
$scope='';$types='';$vals=[];
if($role==='OPERATOR_PRODI'){$scope=' AND m.id_program_studi=?';$types='i';$vals[]=(int)$actor['id_program_studi'];}
elseif($role==='DEKANAT'){$scope=' AND f.id_fakultas=?';$types='i';$vals[]=(int)$actor['id_fakultas'];}
elseif($role==='REKTORAT'){$scope=' AND f.id_universitas=?';$types='i';$vals[]=(int)$actor['id_universitas'];}
elseif($role==='MAHASISWA'){$scope=' AND m.id_pengguna=?';$types='i';$vals[]=$uid;}
function mj_rows($db,$sql,$types='',$vals=[]){$st=mysqli_prepare($db,$sql);if(!$st)return null;if($types)mysqli_stmt_bind_param($st,$types,...$vals);mysqli_stmt_execute($st);$r=mysqli_stmt_get_result($st);$a=[];while($x=mysqli_fetch_assoc($r))$a[]=$x;mysqli_stmt_close($st);return $a;}
$base=" FROM mahasiswa m JOIN program_studi ps ON ps.id_program_studi=m.id_program_studi JOIN fakultas f ON f.id_fakultas=ps.id_fakultas JOIN universitas u ON u.id_universitas=f.id_universitas ";
if($action==='options'){$q="SELECT ps.id_program_studi,ps.nama_program_studi,f.nama_fakultas FROM program_studi ps JOIN fakultas f ON f.id_fakultas=ps.id_fakultas WHERE ps.status_aktif='Aktif'";if($role==='OPERATOR_PRODI')$q.=' AND ps.id_program_studi='.(int)$actor['id_program_studi'];elseif($role==='DEKANAT')$q.=' AND f.id_fakultas='.(int)$actor['id_fakultas'];elseif($role==='REKTORAT')$q.=' AND f.id_universitas='.(int)$actor['id_universitas'];mj_out(true,'Opsi prodi',['program_studi'=>mj_rows($koneksi,$q.' ORDER BY ps.nama_program_studi')]);}
if($action==='list'||$action==='report'){$extra='';$t=$types;$v=$vals;if(!empty($in['cari'])){$extra.=' AND (m.npm LIKE ? OR m.nama_mahasiswa LIKE ?)';$like='%'.$in['cari'].'%';$t.='ss';$v[]=$like;$v[]=$like;}if(!empty($in['status'])){$extra.=' AND m.status_mahasiswa=?';$t.='s';$v[]=$in['status'];}if(!empty($in['jenis_kelamin'])){$extra.=' AND m.jenis_kelamin=?';$t.='s';$v[]=$in['jenis_kelamin'];}$sql="SELECT m.*,ps.nama_program_studi,f.nama_fakultas,u.nama_universitas".$base." WHERE 1=1".$scope.$extra.' ORDER BY m.nama_mahasiswa';$rows=mj_rows($koneksi,$sql,$t,$v);if($rows===null)mj_out(false,'Gagal mengambil data mahasiswa.',[],500);mj_out(true,'Data mahasiswa',['mahasiswa'=>$rows]);}
if($action==='detail'){$id=(int)($in['id_mahasiswa']??0);$rows=mj_rows($koneksi,"SELECT m.*,ps.nama_program_studi,f.nama_fakultas,u.nama_universitas".$base.' WHERE m.id_mahasiswa=?'.$scope,'i'.$types,array_merge([$id],$vals));if(!$rows)mj_out(false,'Data tidak ditemukan atau di luar kewenangan.',[],404);mj_out(true,'Detail mahasiswa',['mahasiswa'=>$rows[0]]);}
if($action==='profile'){$rows=mj_rows($koneksi,"SELECT m.*,p.nama_lengkap,p.email,p.no_hp,ps.nama_program_studi,f.nama_fakultas,u.nama_universitas".$base.' JOIN pengguna p ON p.id_pengguna=m.id_pengguna WHERE m.id_pengguna=?','i',[$uid]);if(!$rows)mj_out(false,'Profil mahasiswa tidak ditemukan.',[],404);mj_out(true,'Profil mahasiswa',['mahasiswa'=>$rows[0]]);}
if($action==='delete'){if($role!=='ADMIN')mj_out(false,'Hanya ADMIN yang dapat menghapus.',[],403);$id=(int)($in['id_mahasiswa']??0);$st=mysqli_prepare($koneksi,'DELETE FROM mahasiswa WHERE id_mahasiswa=?');mysqli_stmt_bind_param($st,'i',$id);$ok=mysqli_stmt_execute($st);mysqli_stmt_close($st);mj_out($ok,$ok?'Data mahasiswa dihapus.':'Gagal menghapus data.');}
mj_out(false,'Aksi belum didukung pada paket ini.',[],422);
