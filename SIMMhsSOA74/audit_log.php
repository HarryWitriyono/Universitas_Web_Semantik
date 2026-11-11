<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/koneksi.php';

wajib_role(['ADMIN']);

function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function pretty_json(?string $json): string {
    if ($json === null || $json === '') return '-';
    $decoded = json_decode($json, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: $json;
    }
    return $json;
}
function bind_dynamic(mysqli_stmt $stmt, string $types, array &$params): void {
    $refs = [$types];
    foreach ($params as &$value) $refs[] = &$value;
    mysqli_stmt_bind_param($stmt, ...$refs);
}

$aksi_options = ['LOGIN','LOGOUT','INSERT','UPDATE','DELETE','VIEW'];
$keyword = trim($_GET['q'] ?? '');
$aksi = trim($_GET['aksi'] ?? '');
$tabel = trim($_GET['tabel_nama'] ?? '');
$tanggal_mulai = trim($_GET['tanggal_mulai'] ?? '');
$tanggal_selesai = trim($_GET['tanggal_selesai'] ?? '');

if (!in_array($aksi, $aksi_options, true)) $aksi = '';
if ($tanggal_mulai !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_mulai)) $tanggal_mulai = '';
if ($tanggal_selesai !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_selesai)) $tanggal_selesai = '';

$daftar_tabel = [];
$result_tabel = mysqli_query($koneksi, "SELECT DISTINCT tabel_nama FROM audit_log ORDER BY tabel_nama ASC");
if ($result_tabel) while ($row = mysqli_fetch_assoc($result_tabel)) $daftar_tabel[] = $row['tabel_nama'];

$per_page_options = [20,50,100];
$per_page = filter_input(INPUT_GET, 'per_page', FILTER_VALIDATE_INT);
if (!in_array($per_page, $per_page_options, true)) $per_page = 20;
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = $page && $page > 0 ? $page : 1;

$where = [];
$params = [];
$types = '';
if ($keyword !== '') {
    $where[] = '(a.record_id LIKE ? OR a.tabel_nama LIKE ? OR p.username LIKE ? OR p.nama_lengkap LIKE ? OR a.ip_address LIKE ?)';
    $like = '%' . $keyword . '%';
    for ($i=0;$i<5;$i++) $params[] = $like;
    $types .= 'sssss';
}
if ($aksi !== '') { $where[]='a.aksi = ?'; $params[]=$aksi; $types.='s'; }
if ($tabel !== '') { $where[]='a.tabel_nama = ?'; $params[]=$tabel; $types.='s'; }
if ($tanggal_mulai !== '') { $where[]='a.waktu >= ?'; $params[]=$tanggal_mulai.' 00:00:00'; $types.='s'; }
if ($tanggal_selesai !== '') { $where[]='a.waktu <= ?'; $params[]=$tanggal_selesai.' 23:59:59'; $types.='s'; }
$where_sql = $where ? ' WHERE '.implode(' AND ',$where) : '';

$sql_count = "SELECT COUNT(*) AS total FROM audit_log a LEFT JOIN pengguna p ON p.id_pengguna=a.id_pengguna $where_sql";
$total = 0;
$stmt_count = mysqli_prepare($koneksi, $sql_count);
if ($stmt_count) {
    if ($types !== '') { $count_params=$params; bind_dynamic($stmt_count,$types,$count_params); }
    mysqli_stmt_execute($stmt_count);
    $r=mysqli_stmt_get_result($stmt_count); $row=mysqli_fetch_assoc($r); $total=(int)($row['total']??0);
    mysqli_stmt_close($stmt_count);
}
$total_pages=max(1,(int)ceil($total/$per_page));
if ($page>$total_pages) $page=$total_pages;
$offset=($page-1)*$per_page;

$sql = "SELECT a.id_audit,a.id_pengguna,a.tabel_nama,a.record_id,a.aksi,a.data_lama,a.data_baru,a.ip_address,a.user_agent,a.waktu,p.username,p.nama_lengkap
        FROM audit_log a LEFT JOIN pengguna p ON p.id_pengguna=a.id_pengguna
        $where_sql ORDER BY a.waktu DESC,a.id_audit DESC LIMIT ? OFFSET ?";
$data_params=$params; $data_types=$types.'ii'; $data_params[]=$per_page; $data_params[]=$offset;
$rows=[];
$stmt=mysqli_prepare($koneksi,$sql);
if ($stmt) { bind_dynamic($stmt,$data_types,$data_params); mysqli_stmt_execute($stmt); $r=mysqli_stmt_get_result($stmt); while($row=mysqli_fetch_assoc($r))$rows[]=$row; mysqli_stmt_close($stmt); }

$summary=array_fill_keys($aksi_options,0);
$sql_summary="SELECT a.aksi,COUNT(*) AS jumlah FROM audit_log a LEFT JOIN pengguna p ON p.id_pengguna=a.id_pengguna $where_sql GROUP BY a.aksi";
$stmt_summary=mysqli_prepare($koneksi,$sql_summary);
if($stmt_summary){
    if($types!==''){ $summary_params=$params; bind_dynamic($stmt_summary,$types,$summary_params); }
    mysqli_stmt_execute($stmt_summary); $r=mysqli_stmt_get_result($stmt_summary);
    while($row=mysqli_fetch_assoc($r)) if(isset($summary[$row['aksi']])) $summary[$row['aksi']]=(int)$row['jumlah'];
    mysqli_stmt_close($stmt_summary);
}

$query_base=['q'=>$keyword?:null,'aksi'=>$aksi?:null,'tabel_nama'=>$tabel?:null,'tanggal_mulai'=>$tanggal_mulai?:null,'tanggal_selesai'=>$tanggal_selesai?:null,'per_page'=>$per_page];
$build_page_url=static function(int $target_page)use($query_base):string{ $q=$query_base; $q['page']=$target_page; return 'audit_log.php?'.http_build_query(array_filter($q,static fn($v)=>$v!==null&&$v!=='')); };
$range_start=$total>0?$offset+1:0; $range_end=min($offset+$per_page,$total);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Audit Log - SIM Mahasiswa</title><link rel="stylesheet" href="style.css">
<style>
.audit-summary{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px;margin-bottom:20px}.audit-summary-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:15px;box-shadow:0 4px 18px rgba(0,0,0,.04)}.audit-summary-card .label{font-size:11px;color:#6b7280;margin-bottom:5px;font-weight:700}.audit-summary-card .value{font-size:22px;font-weight:800;color:#111827}.audit-panel{background:#fff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;box-shadow:0 4px 18px rgba(0,0,0,.04);margin-bottom:20px}.audit-panel-header{padding:20px 22px;border-bottom:1px solid #e5e7eb}.audit-panel-header h2{margin:0 0 5px;font-size:19px}.audit-panel-header p{margin:0;color:#6b7280;font-size:13px}.audit-filter{padding:20px 22px}.audit-filter-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:15px}.audit-filter-group label{display:block;margin-bottom:7px;font-size:12px;font-weight:700;color:#374151}.audit-filter-group input,.audit-filter-group select{width:100%;box-sizing:border-box;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;background:#fff;font:inherit}.audit-filter-actions{display:flex;gap:10px;align-items:center;margin-top:16px}.audit-table-wrap{overflow-x:auto}.audit-table{width:100%;min-width:1100px;border-collapse:collapse}.audit-table th,.audit-table td{padding:11px 12px;border-bottom:1px solid #e5e7eb;text-align:left;vertical-align:top;font-size:12px}.audit-table th{background:#f8fafc;color:#374151;white-space:nowrap;font-size:11px}.audit-table .center{text-align:center}.audit-id{color:#6b7280;font-family:monospace}.audit-action{display:inline-block;padding:4px 8px;border-radius:999px;background:#f3f4f6;font-size:10px;font-weight:800}.audit-user strong{display:block;color:#111827}.audit-user span{color:#6b7280;font-size:11px}.audit-record,.audit-ip{font-family:monospace;color:#374151}.audit-detail{margin-top:8px}.audit-detail summary{cursor:pointer;color:#2563eb;font-size:11px;font-weight:700}.audit-json{margin:8px 0 0;padding:12px;max-width:520px;max-height:260px;overflow:auto;background:#111827;color:#f9fafb;border-radius:8px;font:11px/1.5 Consolas,Monaco,monospace;white-space:pre-wrap;word-break:break-word}.audit-user-agent{max-width:280px;color:#6b7280;line-height:1.4;word-break:break-word}.audit-footer{padding:14px 20px;border-top:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;gap:15px;color:#6b7280;font-size:12px}.audit-pagination{display:flex;gap:6px;align-items:center;flex-wrap:wrap}.audit-pagination a,.audit-pagination span{display:inline-flex;align-items:center;justify-content:center;min-width:32px;height:32px;padding:0 8px;box-sizing:border-box;border:1px solid #d1d5db;border-radius:7px;text-decoration:none;font-size:12px}.audit-pagination a{color:#374151;background:#fff}.audit-pagination .active{background:#111827;color:#fff;border-color:#111827}
@media(max-width:1100px){.audit-summary{grid-template-columns:repeat(3,minmax(0,1fr))}.audit-filter-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:600px){.audit-summary{grid-template-columns:repeat(2,minmax(0,1fr))}.audit-filter-grid{grid-template-columns:1fr}.audit-filter-actions{flex-direction:column;align-items:stretch}.audit-filter-actions .btn{width:100%;text-align:center;box-sizing:border-box}.audit-footer{flex-direction:column;align-items:flex-start}}
</style>
</head>
<body class="app-body">
<?php include __DIR__.'/topbar.php'; ?>
<?php include __DIR__.'/sideleftbar.php'; ?>
<main class="main-content">
<div class="page-heading"><span class="eyebrow">KEAMANAN SISTEM</span><h1>Audit Log</h1><p>Riwayat aktivitas pengguna dan perubahan data pada sistem.</p></div>
<div class="audit-summary">
<?php foreach($aksi_options as $item): ?><div class="audit-summary-card"><div class="label"><?=e($item)?></div><div class="value"><?=number_format($summary[$item],0,',','.')?></div></div><?php endforeach; ?>
</div>
<div class="audit-panel"><div class="audit-panel-header"><h2>Filter Audit Log</h2><p>Menu ini hanya dapat diakses oleh Administrator.</p></div>
<form method="get" class="audit-filter"><div class="audit-filter-grid">
<div class="audit-filter-group"><label for="q">Pencarian</label><input type="search" name="q" id="q" maxlength="100" value="<?=e($keyword)?>" placeholder="User, tabel, record, IP..."></div>
<div class="audit-filter-group"><label for="aksi">Aksi</label><select name="aksi" id="aksi"><option value="">-- Semua Aksi --</option><?php foreach($aksi_options as $item): ?><option value="<?=e($item)?>" <?=$aksi===$item?'selected':''?>><?=e($item)?></option><?php endforeach;?></select></div>
<div class="audit-filter-group"><label for="tabel_nama">Tabel</label><select name="tabel_nama" id="tabel_nama"><option value="">-- Semua Tabel --</option><?php foreach($daftar_tabel as $item): ?><option value="<?=e($item)?>" <?=$tabel===$item?'selected':''?>><?=e($item)?></option><?php endforeach;?></select></div>
<div class="audit-filter-group"><label for="per_page">Jumlah per halaman</label><select name="per_page" id="per_page"><?php foreach($per_page_options as $item): ?><option value="<?=$item?>" <?=$per_page===$item?'selected':''?>><?=$item?> data</option><?php endforeach;?></select></div>
<div class="audit-filter-group"><label for="tanggal_mulai">Tanggal Mulai</label><input type="date" name="tanggal_mulai" id="tanggal_mulai" value="<?=e($tanggal_mulai)?>"></div>
<div class="audit-filter-group"><label for="tanggal_selesai">Tanggal Selesai</label><input type="date" name="tanggal_selesai" id="tanggal_selesai" value="<?=e($tanggal_selesai)?>"></div>
</div><div class="audit-filter-actions"><button type="submit" class="btn btn-primary">Tampilkan</button><a href="audit_log.php" class="btn btn-outline">Reset</a></div></form></div>
<div class="audit-panel"><div class="audit-panel-header"><h2>Riwayat Aktivitas</h2><p>Menampilkan <strong><?=number_format($range_start,0,',','.')?></strong> - <strong><?=number_format($range_end,0,',','.')?></strong> dari <strong><?=number_format($total,0,',','.')?></strong> log.</p></div>
<div class="audit-table-wrap"><table class="audit-table"><thead><tr><th class="center">ID</th><th>Waktu</th><th>Pengguna</th><th>Tabel</th><th>Record ID</th><th>Aksi</th><th>IP Address</th><th>User Agent</th><th>Detail</th></tr></thead><tbody>
<?php if(!$rows): ?><tr><td colspan="9" class="center">Tidak ada audit log sesuai filter.</td></tr><?php else: foreach($rows as $row): ?><tr>
<td class="center"><span class="audit-id">#<?=intval($row['id_audit'])?></span></td><td><?=e($row['waktu'])?></td>
<td class="audit-user"><?php if($row['nama_lengkap']!==null): ?><strong><?=e($row['nama_lengkap'])?></strong><span><?=e($row['username'])?></span><?php else: ?><strong>Pengguna tidak tersedia</strong><span>ID: <?=e($row['id_pengguna']??'-')?></span><?php endif;?></td>
<td><strong><?=e($row['tabel_nama'])?></strong></td><td class="audit-record"><?=e($row['record_id']??'-')?></td><td><span class="audit-action"><?=e($row['aksi'])?></span></td><td class="audit-ip"><?=e($row['ip_address']??'-')?></td><td class="audit-user-agent"><?=e($row['user_agent']??'-')?></td>
<td><?php if(($row['data_lama']??'')!==''||($row['data_baru']??'')!==''): ?><details class="audit-detail"><summary>Lihat data</summary><?php if(($row['data_lama']??'')!==''): ?><div style="margin-top:8px;font-size:11px;font-weight:700;">Data Lama</div><pre class="audit-json"><?=e(pretty_json($row['data_lama']))?></pre><?php endif;?><?php if(($row['data_baru']??'')!==''): ?><div style="margin-top:8px;font-size:11px;font-weight:700;">Data Baru</div><pre class="audit-json"><?=e(pretty_json($row['data_baru']))?></pre><?php endif;?></details><?php else: ?><span style="color:#9ca3af;">-</span><?php endif;?></td>
</tr><?php endforeach; endif; ?></tbody></table></div>
<div class="audit-footer"><div>Halaman <?=number_format($page,0,',','.')?> dari <?=number_format($total_pages,0,',','.')?></div><div class="audit-pagination"><?php if($page>1): ?><a href="<?=e($build_page_url(1))?>">&laquo;</a><a href="<?=e($build_page_url($page-1))?>">&lsaquo;</a><?php endif;?><?php $start_page=max(1,$page-2);$end_page=min($total_pages,$page+2);for($i=$start_page;$i<=$end_page;$i++): ?><?php if($i===$page): ?><span class="active"><?=$i?></span><?php else: ?><a href="<?=e($build_page_url($i))?>"><?=$i?></a><?php endif;?><?php endfor;?><?php if($page<$total_pages): ?><a href="<?=e($build_page_url($page+1))?>">&rsaquo;</a><a href="<?=e($build_page_url($total_pages))?>">&raquo;</a><?php endif;?></div></div></div>
</main></body></html>
