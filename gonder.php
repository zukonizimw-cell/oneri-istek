<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, HEAD');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(0);
ini_set('display_errors', 0);

$telegramToken = "8761753927:AAFrVMhziZNflfozhQA6d1V1INQn7_iBi7A";
$telegramChatID = "6671499665";

$dosyaKlasoru = "uploads/";
$maxDosyaBoyutu = 100 * 1024 * 1024;

$response = ['success' => false, 'error' => ''];

try {
    
    if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
        http_response_code(200);
        exit;
    }

    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Geçersiz istek metodu.');
    }

   
    $oneri = isset($_POST['öneri']) ? trim($_POST['öneri']) : '';
    if (empty($oneri)) {
        throw new Exception('Öneri • İstek metni boş olamaz.');
    }

    
    $dosyaYolu = '';
    $dosyaAdi = '';
    $dosyaBoyutu = 0;
    $telegramDosya = null;

    if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
        $dosya = $_FILES['media'];
        $dosyaBoyutu = $dosya['size'];
        
        if ($dosyaBoyutu > $maxDosyaBoyutu) {
            throw new Exception('Dosya çok büyük. Maksimum 100MB.');
        }

        
        if (!is_dir($dosyaKlasoru)) {
            mkdir($dosyaKlasoru, 0777, true);
        }

        
        $uzanti = pathinfo($dosya['name'], PATHINFO_EXTENSION);
        $dosyaAdi = time() . '_' . bin2hex(random_bytes(8)) . '.' . $uzanti;
        $dosyaYolu = $dosyaKlasoru . $dosyaAdi;

        
        if (!move_uploaded_file($dosya['tmp_name'], $dosyaYolu)) {
            throw new Exception('Dosya yüklenirken hata oluştu.');
        }

       
        $telegramDosya = new CURLFile($dosyaYolu);
    }

 
    $mesaj = "🫣 *Yeni Anonim Öneri • İstek!*\n\n";
    $mesaj .= "📝 " . $oneri . "\n\n";
    $mesaj .= "📅 " . date('d.m.Y H:i:s') . "\n";
    $mesaj .= "🌐 IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Bilinmiyor');

  
    $url = "https://api.telegram.org/bot{$telegramToken}/sendMessage";
    $data = [
        'chat_id' => $telegramChatID,
        'text' => $mesaj,
        'parse_mode' => 'Markdown'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $sonuc = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

  
    if ($telegramDosya && $httpCode == 200) {
        $urlDosya = "https://api.telegram.org/bot{$telegramToken}/sendDocument";
        
        $dataDosya = [
            'chat_id' => $telegramChatID,
            'document' => $telegramDosya,
            'caption' => "📎 Ek dosya"
        ];

        $ch2 = curl_init();
        curl_setopt($ch2, CURLOPT_URL, $urlDosya);
        curl_setopt($ch2, CURLOPT_POST, true);
        curl_setopt($ch2, CURLOPT_POSTFIELDS, $dataDosya);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_TIMEOUT, 60);
        
        curl_exec($ch2);
        curl_close($ch2);
    }

    
    if ($httpCode == 200) {
        $response['success'] = true;
    } else {
        $response['error'] = 'Telegram\'a gönderilemedi. HTTP: ' . $httpCode;
    }

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>