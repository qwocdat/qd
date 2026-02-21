<?php
// =====================================================
// PROXY VIP CHO LINK4M - HỖ TRỢ 1000+ PROXY
// =====================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Cho phép mọi domain gọi đến (CORS)
header('Access-Control-Allow-Methods: GET');

// --- Cấu hình ---
$timeout = 15; // giây
$apiKey = $_GET['api'] ?? '';
$url    = $_GET['url'] ?? '';

if (!$apiKey || !$url) {
    http_response_code(400);
    die(json_encode(['error' => 'Missing api or url parameter']));
}

// Danh sách proxy (có thể thêm hàng ngàn dòng)
// Định dạng: proxy_server:port hoặc user:pass@server:port nếu có auth
$proxyList = [
    '', // Dòng trống = không dùng proxy (kết nối trực tiếp)
    'http://proxy1.example.com:8080',
    'http://proxy2.example.com:3128',
    'socks5://proxy3.example.com:1080',
    'http://user:pass@proxy4.example.com:8080',
    // ... thêm bao nhiêu tùy thích
];

// Chọn proxy ngẫu nhiên
$selectedProxy = $proxyList[array_rand($proxyList)];

// URL đích của link4m
$apiUrl = "https://link4m.com/api?api=" . urlencode($apiKey) . "&url=" . urlencode($url);

// --- Hàm gọi API bằng cURL (hỗ trợ proxy) ---
function curlRequest($url, $proxy = '', $timeout = 15) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // chỉ dùng nếu cần, không khuyến khích
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    if (!empty($proxy)) {
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
        // Nếu proxy yêu cầu auth (user:pass@host:port), cURL tự xử lý qua CURLOPT_PROXYUSERPWD
        if (preg_match('/@/', $proxy)) {
            $parts = parse_url($proxy);
            $proxyAuth = $parts['user'] . ':' . $parts['pass'];
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyAuth);
            // Xóa user:pass khỏi proxy để cURL không nhầm
            $proxy = $parts['host'] . ':' . $parts['port'];
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
        }
    }
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    if ($error) {
        return ['error' => $error];
    }
    return ['response' => $response, 'info' => $info];
}

// Thử với proxy đã chọn
$result = curlRequest($apiUrl, $selectedProxy, $timeout);

// Nếu lỗi và proxy đã chọn không phải rỗng, thử lại không qua proxy
if (isset($result['error']) && !empty($selectedProxy)) {
    $result = curlRequest($apiUrl, '', $timeout);
}

// Nếu vẫn lỗi, thử bằng file_get_contents (dự phòng)
if (isset($result['error'])) {
    $context = stream_context_create([
        'http' => [
            'timeout' => $timeout,
            'user_agent' => 'Mozilla/5.0'
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);
    $response = @file_get_contents($apiUrl, false, $context);
    if ($response !== false) {
        $result = ['response' => $response, 'info' => ['http_code' => 200]];
    } else {
        $result = ['error' => 'All methods failed'];
    }
}

// Trả kết quả về client
if (isset($result['error'])) {
    http_response_code(500);
    echo json_encode(['error' => $result['error']]);
} else {
    // Kiểm tra xem response có phải JSON không
    $decoded = json_decode($result['response'], true);
    if ($decoded) {
        echo json_encode($decoded);
    } else {
        // Nếu không phải JSON, trả về dạng text kèm thông báo lỗi
        echo json_encode(['error' => 'Invalid response from link4m', 'raw' => $result['response']]);
    }
}
