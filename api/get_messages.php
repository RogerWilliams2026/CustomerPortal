<?php

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $stmt = $pdo->query(
        'SELECT id, MSG_Name, MSG_Message, timestamp
         FROM Messages
         ORDER BY timestamp DESC'
    );

    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
   
    echo json_encode([
            'success' => true,
            'data' => $messages
        ]);


} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => 'Unable to retrieve messages.'
    ]);
}
?>
