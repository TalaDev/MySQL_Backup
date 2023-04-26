<?php
// اطلاعات اتصال به پایگاه داده MySQL
$servername = "localhost";
$username = "username";
$password = "password";
$dbname = "dbname";

// ربات تلگرام توکن
$bot_token = "YOUR_BOT_TOKEN";
$chat_id = "YOUR_CHAT_ID";
<?php

error_reporting(0);

// بررسی اتصال
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// تنظیم فاصله زمانی بکاپ (ثانیه)
$backup_interval = 300;
$last_backup = 0;

// حلقه نامتناهی برای بکاپ هر 5 دقیقه
while (true) {

    if (time() - $last_backup >= $backup_interval) {
        $backup_file = "backup_" . time() . ".sql";
        $command = "mysqldump --user={$username} --password={$password} --host={$servername} {$dbname} > {$backup_file}";
        system($command);
        
        // فشرده‌سازی فایل SQL به ZIP
        $zip_file = 'backup_' . time() . '.zip';
        $zip = new ZipArchive();
        $zip->open($zip_file, ZipArchive::CREATE);
        $zip->addFile($backup_file);
        $zip->close();
        
        // ارسال فایل فشرده (ZIP) به تلگرام
        send_backup_to_telegram($bot_token, $chat_id, $zip_file);

        // به روزرسانی شمارنده بکاپ
        $last_backup = time();

        // حذف فایل‌های بکاپ و ZIP
        unlink($backup_file);
        unlink($zip_file);
    }

    sleep(1);
}

// تابع برای ارسال فایل بکاپ به کانال تلگرام
function send_backup_to_telegram($bot_token, $chat_id, $backup_file) {
    $api_url = "https://api.telegram.org/bot" . $bot_token . "/sendDocument";
    $filepath = realpath($backup_file);
    
    $curl = curl_init();

    $post_fields = array(
        'chat_id' => $chat_id,
        'document' => new CURLFile(realpath($filepath))
    );

    curl_setopt_array($curl, [
        CURLOPT_URL => $api_url,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array(
            "Content-Type:multipart/form-data"
        ),
        CURLOPT_POSTFIELDS => $post_fields
    ]);

    $response = curl_exec($curl);
    $error = curl_error($curl);

    if($error) {
        echo "Error: " . $error;
    } else {
        echo "File sent: \n" . $response;
    }

    // حذف فایل بکاپ از هاست پس از ارسال به تلگرام
    unlink($filepath);
    
    curl_close($curl);
}
?>
