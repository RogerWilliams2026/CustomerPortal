<?php

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$data = json_decode(
    file_get_contents('php://input'),
    true
);

if (!is_array($data)) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'error' => 'Invalid JSON.'
    ]);

    exit;
}

$name = trim($data['name'] ?? '');
$message = trim($data['message'] ?? '');

if ($name === '' || $message === '') {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'error' => 'Name and message are required.'
    ]);

    exit;
}

try {

    $stmt = $pdo->prepare(
        'INSERT INTO Messages (MSG_Name, MSG_Message)
         VALUES (:name, :message)'
    );

    $stmt->execute([
        ':name' => $name,
        ':message' => $message
    ]);

    echo json_encode([
        'success' => true,
        'id' => $pdo->lastInsertId()
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => 'Unable to save message.'
    ]);
}

?>