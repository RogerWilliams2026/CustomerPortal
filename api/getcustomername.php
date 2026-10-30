<?php
/*
Created 30/09/2026 By Roger Williams

login page passes customer ID to this script checks against values in
CUS_CustomerDetails

if valid:

returns CUS_BillName

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
    http_response_code(400);

    echo json_encode([
        'status' => false,
        'error' => 'Invalid JSON.'
    ]);

    exit;
}

#validate data
$customerID = trim($data['CUS_CustomerID'] ?? '');

if ($customerID === '') {
    http_response_code(400);

    echo json_encode([
        'status' => false,
        'error' => 'Customer ID Is Required'
    ]);

    exit;
}

#get customer name from CUS_Customer_Details
try {
    $stmt = $pdo->prepare(
        'SELECT CUS_BillName
         FROM CUS_Customer_Details WHERE CUS_CustomerID = :customerid'
    );

    $stmt->execute([
        ':customerid' => $customerID,
    ]);

    #get results
    $result = $stmt->fetch();
   
    #any records?
    if (!empty($result)) {
            #login successful
            echo json_encode([
                    'status' => true,
                    'error' => '',
                    'customerid' => $result['CUS_CustomerID'] 
                ]);
        }
        else {
            echo json_encode([
                    'status' => false,
                    'error' => 'Customer ID Incorrect Or Not Found!'
                ]);
        }
} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'status' => false,
        'error' => 'Unable To Process Request'
    ]);
}

?>