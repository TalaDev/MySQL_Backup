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
  $conn = new mysqli($servername, $username, $password, $dbname);

  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

  if (!$conn->set_charset("utf8mb4")) {
    printf("Error loading character set utf8mb4: %s\n", $conn->error);
    exit();
  }

  $tablesRes = $conn->query('SHOW TABLES');
  while($row = $tablesRes->fetch_array()) {
    $tables[] = $row[0];
  }
  
  $sqlScript = '';
  foreach ($tables as $table) {

    $tableRes = $conn->query('SELECT * FROM '.$table);
    $numFields = $tableRes->field_count;
  
    $sqlScript .= "DROP TABLE IF EXISTS $table;";
    $create = $conn->query('SHOW CREATE TABLE '.$table);
    $row2 = $create->fetch_row();
     
    $sqlScript .= "\n\n".$row2[1].";\n\n";
     
    for ($i = 0; $i < $numFields; $i++) 
    {
        while($row = $tableRes->fetch_row())  
        { 
            $sqlScript .= "INSERT INTO $table VALUES(";
            
            for($j=0; $j<$numFields; $j++) 
            { 
                $row[$j] = addslashes($row[$j]); 
                $row[$j] = preg_replace("/\n/","\\n", $row[$j] ); 
                
                if (isset($row[$j])) { $sqlScript .= '"'.$row[$j].'"' ; } 
                else { $sqlScript .= '""'; } 
                
                if ($j < ($numFields-1)) { $sqlScript .= ','; }
            } 
   
            $sqlScript .= ");\n";
        }
     }
     $sqlScript .= "\n"; 
  }

  $backup_file = "db_backup_" . date("Ymd") . ".sql";
  file_put_contents($backup_file, $sqlScript);

  $zip = new ZipArchive();
  $zip_file = "db_backup_" . date("Ymd") . ".zip"; 
  $zip->open($zip_file, ZipArchive::CREATE);
  $zip->addFile($backup_file);
  $zip->close();

  $url = "https://api.telegram.org/bot$bot_token/sendDocument";
  $data = array('chat_id' => $chat_id, 'document' => new CURLFile(realpath($zip_file)));
  mysqli_set_charset($conn, 'utf8mb4');


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
