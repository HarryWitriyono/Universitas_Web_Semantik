<?php
/**
 * api_client.php
 * HTTP client sederhana untuk menghubungkan SOA Consumer ke SOA Server.
 * File ini berada di htdocs/simmhssoa/, bukan di folder soa.
 */

declare(strict_types=1);

// Alamat SOA Server. Ubah jika nanti server dipindahkan ke komputer lain.
define('SOA_BASE_URL', 'http://localhost/soa/');

/**
 * Mengirim request JSON ke endpoint SOA.
 *
 * @return array{http_code:int, data:array|null, error:?string}
 */
function soa_request(string $endpoint, array $payload = [], string $method = 'POST'): array
{
    // Hanya izinkan nama file endpoint sederhana, bukan URL bebas.
    if (!preg_match('/^[a-zA-Z0-9_-]+\.php$/', $endpoint)) {
        return [
            'http_code' => 0,
            'data' => null,
            'error' => 'Nama endpoint SOA tidak valid.'
        ];
    }

    $url = rtrim(SOA_BASE_URL, '/') . '/' . $endpoint;
    $method = strtoupper($method);

    $headers = [
        'Accept: application/json',
        'Content-Type: application/json; charset=utf-8',
        'Connection: close'
    ];

    $options = [
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers),
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ];

    if ($method === 'POST') {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            return [
                'http_code' => 0,
                'data' => null,
                'error' => 'Gagal membentuk data JSON.'
            ];
        }

        $options['http']['content'] = $json;
    }

    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        return [
            'http_code' => 0,
            'data' => null,
            'error' => 'SOA Server tidak dapat dihubungi.'
        ];
    }

    $httpCode = 0;
    foreach ($http_response_header ?? [] as $header) {
        if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $header, $matches)) {
            $httpCode = (int) $matches[1];
        }
    }

    $decoded = json_decode($response, true);

    if (!is_array($decoded)) {
        return [
            'http_code' => $httpCode,
            'data' => null,
            'error' => 'Respons SOA Server bukan JSON yang valid.'
        ];
    }

    // HTTP non-2xx harus dianggap gagal, walaupun body berisi JSON.
    if ($httpCode < 200 || $httpCode >= 300) {
        return [
            'http_code' => $httpCode,
            'data' => $decoded,
            'error' => $decoded['message'] ?? ('SOA Server mengembalikan HTTP ' . $httpCode)
        ];
    }

    // Jangan memaksakan struktur JSON tertentu di client generik.
    // Pemeriksaan field "success" dilakukan oleh helper masing-masing modul.
    return [
        'http_code' => $httpCode,
        'data' => $decoded,
        'error' => null
    ];
}
