<?php
// اطلاعات اتصال به پایگاه داده MySQL
$servername = "localhost";
$username = "username";
$password = "password";
$dbname = "dbname";

// ربات تلگرام توکن
$bot_token = "YOUR_BOT_TOKEN";
$chat_id = "YOUR_CHAT_ID";

// ایجاد اتصال
$conn = new mysqli($servername, $username, $password, $dbname);
// بررسی اتصال
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// زمان اجرای این فایل بکاپ خودکار را تنظیم کنید (به ثانیه)
$backup_interval = 300;
$last_backup = 0;

// حلقه نامتناهی برای بکاپ هر 5 دقیقه
while (true) {

    if (time() - $last_backup >= $backup_interval) {
        $backup_file = "backup_" . time() . ".sql";
        $command = "mysqldump --user={$username} --password={$password} --host={$servername} {$dbname} > {$backup_file}";
        system($command);
        
        // ارسال فایل به تلگرام
        send_backup_to_telegram($bot_token, $chat_id, $backup_file);

        // به روزرسانی شمارنده بکاپ
        $last_backup = time();

        // حذف فایل بکاپ
        unlink($backup_file);
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
    curl_close($curl);
}
?>
