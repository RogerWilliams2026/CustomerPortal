<?php
/*
Created 24/09/2026 By Roger Williams

login page passes user and password to this script checks against values in
Login_Details

if valid:

deletes any records for user in Login_Attempts
returns JSON with status of true

if not valid creates record in Login_Attempts
returns JSON with status of false an error message for the web page 
to show the user

if user misses user or password returns JSON with status of false and
an error message for the web page to show the user

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
$user = trim($data['TXTUser'] ?? '');
$password = trim($data['TXTPassword'] ?? '');

if ($user === '' || $password === '') {
    http_response_code(400);

    echo json_encode([
        'status' => false,
        'error' => 'User and Password Are Required'
    ]);

    exit;
}

#validate login data with Login_Details table
try {
    $stmt = $pdo->prepare(
        'SELECT LOGD_User, LOGD_Password
         FROM Login_Details WHERE LOGD_User = :user AND LOGD_Password = :pwd;'
    );

    $stmt->execute([
        ':user' => $user,
        ':pwd' => $password
    ]);

    #get results
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
   
    #any records?
    if (!empty($result)) {
            #login successful

            #get customer name from CUS_Customer_Details
            try {
                $stmt = $pdo->prepare(
                    'SELECT * 
                    FROM CUS WHERE CUS_CustomerID = :customerid'
                );

                $stmt->execute([
                    ':customerid' => $user
                ]);

                #get results
                $result = $stmt->fetch();
            
                #any records?
                if (!empty($result)) {
                        #login successful
                        echo json_encode([
                                'status' => true,
                                'error' => '',
                                'customerid' => $result['CUS_CustomerID'], 
                                'customername' => $result['CUS_BillName'] 
                            ]);
                    }
                else {
                    echo json_encode([
                            'status' => false,
                            'error' => 'Customer Data Not Found!'
                        ]);
                }
            } catch (PDOException $e) {

                http_response_code(500);

                echo json_encode([
                    'status' => false,
                    'error' => 'Unable To Process Request'
                ]);
            }


            // echo json_encode([
            //         'status' => true,
            //         'error' => ''
            //     ]);

            #create record in Login_Current
            try {

                $stmt = $pdo->prepare(
                    'INSERT INTO Login_Current (LOG_User)
                    VALUES (:name)'
                );

                $stmt->execute([
                    ':name' => $user
                ]);
            } catch (PDOException $e) {

                http_response_code(500);

                echo json_encode([
                    'success' => false,
                    'error' => 'Unable To Create Login Current Record'
                ]);
            }
        }
        else {
            echo json_encode([
                    'status' => false,
                    'error' => 'User Or Password Incorrect'
                ]);

            #create record in Login_Attempts
            try {

                $stmt = $pdo->prepare(
                    'INSERT INTO Login_Attempts (LOGA_User)
                    VALUES (:name)'
                );

                $stmt->execute([
                    ':name' => $user
                ]);
            } catch (PDOException $e) {

                http_response_code(500);

                echo json_encode([
                    'success' => false,
                    'error' => 'Unable To Create Login Attempt Record'
                ]);
            }
        }
} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'status' => false,
        'error' => 'Unable To Process Request'
    ]);
}

?>