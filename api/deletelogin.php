<?php
/*
Created 24/09/2026 By Roger Williams

deletes user login details from:

login_current

which fires a trigger to delete records for user in:

login_attempts

*/


#connect to database
require_once __DIR__ . '/../config/database.php';

#configure JSON
header('Content-Type: application/json; charset=utf-8');

#get data from web page
$data = json_decode(
    file_get_contents('php://input'),
    true
);


#validate JSON
if (!is_array($data)) {
   //do nothing as page is closing
 
    exit;
}

#validate data
$user = trim($data['TXTUser'] ?? '');

if ($user === '') {
  //do nothing as page is closing
 
    exit;
}


#delete Login_Current record
try {
    $stmt = $pdo->prepare(
        'DELETE FROM Login_Current WHERE LOG_User = :user;'
    );

    $stmt->execute([
        ':user' => $user,
    ]);

    #get results
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
   
    #Note: delete runs trigger to delete Login_Attempts records as well

        
} catch (PDOException $e) {
  //do nothing as page is closing
}

?>