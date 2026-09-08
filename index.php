<?php
// Atur zona waktu
date_default_timezone_set('Asia/Jakarta');

// Kunci Konfigurasi
$tokenFonnte   = '4wmbaY2g78JSi1VyW17s';
$apiKeyGemini  = 'AQ.Ab8RN6KwKgaS8lzsqylWwPhd7NklF3JEHowIi6dU5vmCa7Tvuw';

// Tangkap payload webhook dari Fonnte
$rawInput = file_get_contents('php://input');
$dataJson = json_decode($rawInput, true);

$pengirim = $dataJson['sender'] ?? $_POST['sender'] ?? '';
$pesan    = trim($dataJson['message'] ?? $_POST['message'] ?? '');

if (empty($pengirim) || empty($pesan)) {
    http_response_code(200);
    echo "Webhook Active";
    exit;
}

// 1. Respon Cepat
$pesanLower = strtolower($pesan);
if ($pesanLower === 'aiohumas' || $pesanLower === 'menu' || $pesanLower === 'halo') {
    $balasanAwal = "Hai! Sahabat Layanan Kemenag. Ada yang bisa kami bantu hari ini? 😊\n\n"
                 . "Silakan ajukan pertanyaan seputar administrasi, berkas bulanan, atau layanan publik lainnya.";
    kirimPesanFonnte($pengirim, $balasanAwal, $tokenFonnte);
    exit;
}

// 2. Respon AI Gemini
$jawabanAI = tanyaGemini($pesan, $apiKeyGemini);
kirimPesanFonnte($pengirim, $jawabanAI, $tokenFonnte);

http_response_code(200);
exit;

function tanyaGemini($promptUser, $apiKey) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;

    $systemPrompt = "Kamu adalah asisten virtual resmi Humas Kementerian Agama (Kemenag). "
                  . "Tugasmu melayani pertanyaan pegawai dan masyarakat secara sopan, ramah, ringkas, dan solutif. "
                  . "Format jawaban rapi dan mudah dibaca via WhatsApp (gunakan poin dan cetak tebal bila perlu). "
                  . "Jika ada pertanyaan di luar wewenang atau informasi sensitif, arahkan pengguna untuk menghubungi admin kepegawaian langsung.";

    $payload = [
        "contents" => [
            [
                "role" => "user",
                "parts" => [
                    ["text" => $systemPrompt . "\n\nPertanyaan Masuk: " . $promptUser]
                ]
            ]
        ],
        "generationConfig" => [
            "temperature" => 0.4,
            "maxOutputTokens" => 500
        ]
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $resArr = json_decode($response, true);
    return $resArr['candidates'][0]['content']['parts'][0]['text'] ?? "Maaf, pesan kamu belum bisa diproses saat ini.";
}

function kirimPesanFonnte($nomor, $pesan, $token) {
    $url = 'https://api.fonnte.com/send';

    $payload = [
        'target'  => $nomor,
        'message' => $pesan
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            "Authorization: " . $token
        ],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0
    ]);

    curl_exec($ch);
    curl_close($ch);
}
