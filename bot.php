<?php
// اطلاعات اتصال به پایگاه داده MySQL
$servername = "localhost";
$username = "username";
$password = "password";
$dbname = "dbname";

// ربات تلگرام توکن
$bot_token = "YOUR_BOT_TOKEN";
$chat_id = "YOUR_CHAT_ID";


error_reporting(0);
backup_database_and_send_to_telegram();

function backup_database_and_send_to_telegram() {
    global $username, $password, $servername, $dbname, $bot_token, $chat_id;

    $backup_file = "backup_" . time() . ".sql";
    $command = "mysqldump --user={$username} --password={$password} --host={$servername} {$dbname} > {$backup_file}";
    system($command);

    $zip_file = 'backup_' . time() . '.zip';
    $zip = new ZipArchive();
    $zip->open($zip_file, ZipArchive::CREATE);
    $zip->addFile($backup_file);
    $zip->close();

    send_backup_to_telegram($bot_token, $chat_id, $zip_file);

    unlink($backup_file);
    unlink($zip_file);
}

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
    echo "بکاپ با موفقیت ارسال شد.";
}

    unlink($filepath);
    
    curl_close($curl);
}
?>
