<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/api.php';

$user = $_SESSION['user'] ?? null;
if (!is_array($user) || ($_SESSION['auth_source'] ?? '') !== 'local_admin' || ($user['kode_role'] ?? '') !== 'ADMIN_SIMSKRIPSI') {
    http_response_code(403);
    exit('403 Forbidden: hanya Admin SIMSKRIPSI yang dapat mengubah konfigurasi API.');
}
$message = '';
$error = '';
$base = '';
try { $base = simskripsi_api_base_url(); } catch (Throwable $e) { $error = 'Konfigurasi belum tersedia. Jalankan setupadmin.php terlebih dahulu.'; }
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_verify();
    $action = (string)($_POST['action'] ?? 'save');
    if ($action === 'save') {
        $candidate = trim((string)($_POST['api_base_url'] ?? ''));
        $parts = parse_url($candidate);
        $valid = is_array($parts) && isset($parts['scheme'], $parts['host']) && in_array(strtolower($parts['scheme']), ['http','https'], true) && !isset($parts['user']) && !isset($parts['pass']) && !isset($parts['query']) && !isset($parts['fragment']);
        if (!$valid) $error = 'URL tidak valid. Gunakan alamat http:// atau https:// tanpa kredensial, query, atau fragment.';
        else {
            try {
                $candidate = rtrim($candidate, '/');
                $db = simskripsi_db();
                $key = 'api_base_url';
                $adminId = (int)str_replace('LOCAL_ADMIN_', '', (string)($user['id_pengguna'] ?? '0'));
                $stmt = $db->prepare('INSERT INTO konfigurasi_aplikasi (nama_konfigurasi, nilai_konfigurasi, updated_by) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE nilai_konfigurasi = VALUES(nilai_konfigurasi), updated_by = VALUES(updated_by)');
                $stmt->bind_param('ssi', $key, $candidate, $adminId);
                $stmt->execute(); $stmt->close(); $db->close();
                $base = $candidate; $message = 'Alamat dasar API berhasil disimpan.';
            } catch (Throwable $e) { error_log('SIMSKRIPSI API config save: '.$e->getMessage()); $error = 'Gagal menyimpan konfigurasi API.'; }
        }
    } elseif ($action === 'test') {
        $url = simskripsi_api_login_url();
        if ($url === '' || !function_exists('curl_init')) $error = 'URL API belum tersedia atau ekstensi cURL belum aktif.';
        else {
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_CONNECTTIMEOUT=>5, CURLOPT_TIMEOUT=>8, CURLOPT_NOBODY=>true, CURLOPT_FOLLOWLOCATION=>false, CURLOPT_SSL_VERIFYPEER=>true, CURLOPT_SSL_VERIFYHOST=>2]);
            curl_exec($ch); $errno = curl_errno($ch); $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
            // Endpoint login mungkin menolak HEAD (405); ini hanya menguji keterjangkauan HTTP, bukan validitas autentikasi.
            if ($errno === 0 && $http > 0) $message = 'Server API dapat dijangkau (HTTP '.$http.'). Ini belum membuktikan login API berhasil.';
            else $error = 'API tidak dapat dijangkau. Periksa URL, server, firewall, dan SSL.';
        }
    }
}
function api_e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Pengaturan API | SIMSKRIPSI</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><style>body{background:#f4f7fb;font-family:system-ui,sans-serif}.wrap{max-width:760px;margin:5vh auto;padding:0 16px}.card{border:1px solid #e2e8f0;border-radius:16px;box-shadow:0 12px 36px #142d5510}.muted{color:#68758a}</style></head><body><main class="wrap"><div class="card p-4 p-md-5"><div class="text-primary fw-bold mb-2">SIMSKRIPSI UMB</div><h1 class="h3">Pengaturan Integrasi API</h1><p class="muted">Ubah alamat dasar Web API SIM Mahasiswa. Perubahan ini hanya tersedia untuk Admin SIMSKRIPSI.</p>
<?php if ($message): ?><div class="alert alert-success"><?php echo api_e($message); ?></div><?php endif; ?><?php if ($error): ?><div class="alert alert-danger"><?php echo api_e($error); ?></div><?php endif; ?>
<form method="post" class="mb-3"><?php echo csrf_field(); ?><input type="hidden" name="action" value="save"><label for="api_base_url" class="form-label fw-semibold">Alamat dasar API</label><input class="form-control mb-3" type="url" name="api_base_url" id="api_base_url" value="<?php echo api_e($base); ?>" required><button class="btn btn-primary" type="submit">Simpan konfigurasi</button></form>
<form method="post" class="d-inline"><?php echo csrf_field(); ?><input type="hidden" name="action" value="test"><button class="btn btn-outline-secondary" type="submit">Uji keterjangkauan API</button></form> <a class="btn btn-link" href="../dashboard.php">Kembali ke dashboard</a><div class="alert alert-warning mt-4 mb-0 small">Gunakan HTTPS pada server produksi. Uji keterjangkauan hanya menguji respons HTTP endpoint login, bukan melakukan login sesungguhnya.</div></div></main></body></html>
