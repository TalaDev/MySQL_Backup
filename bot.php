<?php
// اطلاعات مربوط به دیتابیس
$servername = "localhost";
$username = "username";
$password = "password";
$dbname = "dbname";

// اطلاعات مربوط به تلگرام
$bot_token = "YOUR_BOT_TOKEN";
$chat_id = "YOUR_CHAT_ID";

function backup_db($servername, $username, $password, $dbname, $bot_token, $chat_id) {
  $conn = mysqli_connect($servername, $username, $password, $dbname);
  mysqli_set_charset($conn, "utf8"); 

  $tables = mysqli_query($conn, 'SHOW TABLES');
  while($row = mysqli_fetch_row($tables)) {
    $table_array[] = $row[0];
  }
  
  $sql_script = '';
  foreach ($table_array as $table_name) {
    
    $structure_res = mysqli_query($conn, "SHOW CREATE TABLE $table_name");
    $structure_row = mysqli_fetch_row($structure_res);
    $sql_script .= "\n--\n-- Table structure for `$table_name`\n--\n";
    $sql_script .= "DROP TABLE IF EXISTS $table_name;\n";
    $sql_script .= $structure_row[1].";\n\n";

    $offset = 0;
    $count = 0;
    do {
        $table_data = mysqli_query($conn, "SELECT * FROM $table_name LIMIT $offset, 100");
        $count = mysqli_num_rows($table_data);
        $offset += 100;
        
        while($row = mysqli_fetch_assoc($table_data)) {
            $row_escaped = array();
            foreach ($row as $key => $value) {
                $row_escaped[$key] = "'" . mysqli_real_escape_string($conn, $value) . "'";
            }
            $insert_row = implode(",", $row_escaped);
            $sql_script .= "INSERT INTO $table_name VALUES ($insert_row);\n";
        }
    } while ($count > 0);

  }
  
  $backup_file = "db_backup_" . date("Ymd") . ".sql";
  file_put_contents($backup_file, $sql_script);

  $zip = new ZipArchive();
  $zip_file = "db_backup_" . date("Ymd") . ".zip"; 
  $zip->open($zip_file, ZipArchive::CREATE);
  $zip->addFile($backup_file);
  $zip->close();

  $url = "https://api.telegram.org/bot$bot_token/sendDocument";
  $data = array('chat_id' => $chat_id, 'document' => new CURLFile(realpath($zip_file)));

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_POST, 1); 
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $result = curl_exec($ch);
  curl_close($ch);

  unlink($backup_file);
  unlink($zip_file);
}

backup_db($servername, $username, $password, $dbname, $bot_token, $chat_id);
?>
