<?php
/**
 * File: htdocs/simmahasiswa71/api/programstudi.php
 * Deskripsi: Web Service API untuk mengelola & menampilkan data Program Studi
 */

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Menghubungkan ke file koneksi database
require_once '../koneksi.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    
    // 1. Opsi A: Menampilkan program studi berdasarkan ID Fakultas tertentu (?id_fakultas=X)
    if (isset($_GET['id_fakultas'])) {
        $id_fakultas = intval($_GET['id_fakultas']);

        $query = "SELECT ps.id_program_studi, ps.id_fakultas, f.kode_fakultas, f.nama_fakultas, 
                         ps.kode_program_studi, ps.nama_program_studi, ps.jenjang, ps.status_aktif, 
                         ps.created_at, ps.updated_at 
                  FROM program_studi ps
                  JOIN fakultas f ON ps.id_fakultas = f.id_fakultas
                  WHERE ps.id_fakultas = ?";

        $stmt = mysqli_prepare($koneksi, $query);
        mysqli_stmt_bind_param($stmt, "i", $id_fakultas);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }

        echo json_encode([
            "status" => true,
            "message" => "Data program studi berdasarkan id_fakultas " . $id_fakultas,
            "total_data" => count($data),
            "data" => $data
        ]);

    // 2. Opsi B: Menampilkan spesifik 1 record program studi berdasarkan ID Program Studi (?id_program_studi=X)
    } elseif (isset($_GET['id_program_studi'])) {
        $id_program_studi = intval($_GET['id_program_studi']);

        $query = "SELECT ps.id_program_studi, ps.id_fakultas, f.kode_fakultas, f.nama_fakultas, 
                         ps.kode_program_studi, ps.nama_program_studi, ps.jenjang, ps.status_aktif, 
                         ps.created_at, ps.updated_at 
                  FROM program_studi ps
                  JOIN fakultas f ON ps.id_fakultas = f.id_fakultas
                  WHERE ps.id_program_studi = ?";

        $stmt = mysqli_prepare($koneksi, $query);
        mysqli_stmt_bind_param($stmt, "i", $id_program_studi);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $data = mysqli_fetch_assoc($result);

        if ($data) {
            echo json_encode([
                "status" => true,
                "data" => $data
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                "status" => false,
                "message" => "Data program studi tidak ditemukan."
            ]);
        }

    // 3. Opsi C: Menampilkan seluruh record program studi (Tanpa parameter)
    } else {
        $query = "SELECT ps.id_program_studi, ps.id_fakultas, f.kode_fakultas, f.nama_fakultas, 
                         ps.kode_program_studi, ps.nama_program_studi, ps.jenjang, ps.status_aktif, 
                         ps.created_at, ps.updated_at 
                  FROM program_studi ps
                  JOIN fakultas f ON ps.id_fakultas = f.id_fakultas";

        $result = mysqli_query($koneksi, $query);
        $data = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }

        echo json_encode([
            "status" => true,
            "total_data" => count($data),
            "data" => $data
        ]);
    }

} else {
    // Jika method yang dipanggil bukan GET
    http_response_code(405);
    echo json_encode([
        "status" => false,
        "message" => "Metode HTTP tidak diizinkan."
    ]);
}
?>