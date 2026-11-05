<?php
/*



*/


//use strict data types
declare(strict_types=1);

#connect to database
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/globals.php';
require_once __DIR__ . '/logwriter.php';

const CNST_STR_HTML_HEADER = '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <!--
    Modified from the Debian original for Ubuntu
    Last updated: 2022-03-22
    See: https://launchpad.net/bugs/1966004
  -->
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>RogCustomer Portal 2026 - Report Results</title>
  </head>
  <link type="text/css" link rel="stylesheet" href="/css/style.css"></link>
  <body>
  <div id="divReportHeader">
     <h3>Report Results:</h3>
  </div>
   <div id="divReportDetail">
';

const CNST_STR_HTML_FOOTER = '
   </div>
 </body>
<script src="./JavaScript/general.js" type="module" defer></script>
</html>
';

//used for creating report pages
$strHTML = '';
$DateFrom = '';
$DateTo = '';
$intErrorCount = 0;

//*****custom funcs*****

function funcGetOutstandingAR()
{
/*
    Modified 28/10/2026 By Roger Williams

    in order to get any data to apply global $offset value to year


    Created 21/10/2026 By Roger Williams

    gets outstanding AR and adds to $summaryJSON class

*/
    global $DateFrom;
    global $DateTo;
    global $strHTML;
    global $pdo;
    global $strCustomerID;

    $sql = "";

    #start date is $DateFrom - 1 month
    $dateStart = date_add(date_create($DateFrom), date_interval_create_from_date_string('-1 month'));

    //add year offset
  //  $dateStart = date_add($dateStart, date_interval_create_from_date_string("{$intOffset} year"));
    $dateStart = $dateStart->format('Y/m/d');
 
    #end date is $DateTo
    $dateEnd = date_create($DateTo); //date('Y/m/d');
    $dateEnd = $dateEnd->format('Y/m/d');

    #bucket 1 = 1 months before start date
    $dateBucketOne = date_add(date_create($dateStart), date_interval_create_from_date_string('-1 month'));
    $dateBucketOne = $dateBucketOne->format('Y/m/d');
 
    #bucket 1 = 2 months before start date
    $dateBucketTwo = date_add(date_create($dateBucketOne), date_interval_create_from_date_string('-1 month'));
    $dateBucketTwo = $dateBucketTwo->format('Y/m/d');

     #bucket 3 = 3 months before start date
    $dateBucketThree = date_add(date_create($dateBucketTwo), date_interval_create_from_date_string('-1 month'));
    $dateBucketThree = $dateBucketThree->format('Y/m/d');

   
    try {
//    SELECT SUM(QueryAR.OutstandingAR) AS SumOfAR 
    $sql = "
    SELECT DISTINCT IVM.IVM_InvoiceID, ARM.ARM_SalesOrderID, ARM.ARM_CurrencyType, ARM.ARM_Region, ARM.ARM_CustomerID, ARM.ARM_DomesticBalanceDueAmt, ARM.ARM_DomesticInvoiceNetAmt, 
    CONCAT(CUS_BillName, ARM_CustomerID) AS CusNameID, CUS.CUS_BillName, CUS.CUS_BillState, CUS.CUS_BillPhone, CUS.CUS_BillContactName,  
    CUS.CUS_CurrentAmt, ARM.ARM_CustomerPOID, ARM.ARM_InvoiceID, ARM.ARM_InvoiceDate, ARM.ARM_DueDate, ARM.ARM_ARTermsDesc, ARM.ARM_InvoiceAmt, qryARAging_sub.SumOfARP_PayAmt, 
    FROM (
        SELECT DISTINCT
            IVM.IVM_InvoiceID,
            ARM.ARM_SalesOrderID,
            ARM.ARM_CurrencyType,
            ARM.ARM_Region,
            ARM.ARM_CustomerID,
            ARM.ARM_DomesticBalanceDueAmt,
            ARM.ARM_DomesticInvoiceNetAmt,
            CONCAT(CUS.CUS_BillName, ARM.ARM_CustomerID) AS CusNameID,
            CUS.CUS_BillName,
            CUS.CUS_BillState,
            CUS.CUS_BillPhone,
            CUS.CUS_BillContactName,
            ARM.ARM_CustomerPOID,
            ARM.ARM_InvoiceID,
            ARM.ARM_InvoiceDate,
            ARM.ARM_DueDate,
            ARM.ARM_ARTermsDesc,
            ARM.ARM_InvoiceAmt,
            qryARAging_sub.SumOfARP_PayAmt,

            ARM.ARM_InvoiceAmt -
                IFNULL(qryARAging_sub.SumOfARP_PayAmt, 0) AS OutstandingAR,

            ARM.ARM_InvoiceAmt -
                IFNULL(qryARAging_sub.SumOfARP_PayAmt, 0) AS DueAmt,

            CASE WHEN ARM.ARM_InvoiceDate BETWEEN :startdate AND :enddate
                THEN ARM.ARM_InvoiceAmt -
                    IFNULL(qryARAging_sub.SumOfARP_PayAmt, 0)
                ELSE 0 END AS CurrentDue,

            CASE WHEN ARM.ARM_InvoiceDate BETWEEN :bucketone AND :startdate2
                THEN ARM.ARM_InvoiceAmt -
                    IFNULL(qryARAging_sub.SumOfARP_PayAmt, 0)
                ELSE 0 END AS BucketOne,

            CASE WHEN ARM.ARM_InvoiceDate BETWEEN :buckettwo AND :bucketone2
                THEN ARM.ARM_InvoiceAmt -
                    IFNULL(qryARAging_sub.SumOfARP_PayAmt, 0)
                ELSE 0 END AS BucketTwo,

            CASE WHEN ARM.ARM_InvoiceDate BETWEEN :bucketthree AND :buckettwo2
                THEN ARM.ARM_InvoiceAmt -
                    IFNULL(qryARAging_sub.SumOfARP_PayAmt, 0)
                ELSE 0 END AS BucketThree,

            CASE WHEN ARM.ARM_InvoiceDate < :bucketthree2
                THEN ARM.ARM_InvoiceAmt -
                    IFNULL(qryARAging_sub.SumOfARP_PayAmt, 0)
                ELSE 0 END AS BucketFour

        FROM ARM
        INNER JOIN CUS
            ON ARM.ARM_CustomerID = CUS.CUS_CustomerID

        LEFT JOIN (
            SELECT
                SUM(ARP.ARP_PayAmt + ARP.ARP_PayDiscTakenAmt) AS SumOfARP_PayAmt,
                ARP.ARP_InvoiceID
            FROM ARP
            WHERE ARP.ARP_PayDate <= :enddate2
            GROUP BY ARP.ARP_InvoiceID
        ) AS qryARAging_sub
            ON ARM.ARM_InvoiceID = qryARAging_sub.ARP_InvoiceID

        LEFT JOIN IVM
            ON ARM.ARM_SalesOrderID = IVM.IVM_SalesOrderID

        WHERE ARM.ARM_InvoiceDate <= :enddate3
        AND ARM.ARM_InvoiceAmt -
            IFNULL(qryARAging_sub.SumOfARP_PayAmt, 0) <> 0
        AND ARM.ARM_CustomerID = :customerid
    ) AS QueryAR
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        'startdate'   => $dateStart,
        'enddate'     => $dateEnd,
        'startdate2'  => $dateStart,
        'bucketone'   => $dateBucketOne,
        'bucketone2'  => $dateBucketOne,
        'buckettwo'   => $dateBucketTwo,
        'buckettwo2'  => $dateBucketTwo,
        'bucketthree' => $dateBucketThree,
        'bucketthree2'=> $dateBucketThree,
        'enddate2'    => $dateEnd,
        'enddate3'    => $dateEnd,
        'customerid'  => $strCustomerID
    ]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

        #any records?
        if (!empty($result)) {
            //loop through records and create HTML page
            while ($row = $stmt->fetch())
            {
              $strHTML = $strHTML .  '<p>' . $$row['ARM_InvoiceID'] . funcFormatCurrency($result['SumOfAR'], '£') . '</p>';        
            }
           }
        else {
            return false;
        }
      } catch (PDOException $e) {
            funcWriteLog(CNST_STR_ERRORLOG, $e, 'Outstanding ARError');
            return true;
    }
  return false;
};


//*****end custom funcs*****




#connect to database
require_once __DIR__ . '/../config/database.php';

#configure JSON
header('Content-Type: application/json; charset=utf-8');

#get data from web page
$data = json_decode(
    file_get_contents('php://input'),
    true
);

//delete existing log file
funcDeleteLog(CNST_STR_ERRORLOG);

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
$strCustomerID = trim($data['customerid'] ?? '');

if ($strCustomerID === '') {
    http_response_code(400);

    echo json_encode([
        'status' => false,
        'error' => 'Customer ID Is Required'
    ]);

    exit;
}

#customer reports
$strReportType = trim($data['reporttype'] ?? '');
$DateFrom = trim($data['datefrom'] ?? '');
$DateTo = trim($data['dateto'] ?? '');


if ($strReportType === '') {
    http_response_code(400);

    echo json_encode([
        'status' => false,
        'error' => 'Report Type Is Required'
    ]);

    exit;
}

if ($DateFrom === '') {
    http_response_code(400);

    echo json_encode([
        'status' => false,
        'error' => 'From Date Is Required'
    ]);

    exit;
}

if ($DateTo === '') {
    http_response_code(400);

    echo json_encode([
        'status' => false,
        'error' => 'To Date Is Required'
    ]);

    exit;
}

//create report HTML based on user choice
switch ($strReportType)
{
   case 'AR':
       if (funcGetOutstandingAR()) $intErrorCount++;
}

//any errors
if ($intErrorCount !== 0)
    {
    echo json_encode([
            'status' => false,
            'error' => 'Errors Stopped Processing Check PHP Log File On Server'
        ]);
    }

if ($HTML !== '')
{
    echo json_encode([
        'status' => true,
        'HTML' => $strHTML
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
else
{
    funcWriteLog(CNST_STR_ERRORLOG, null, 'Error Trying To Create HTML');
}    
// try {
//     $stmt = $pdo->prepare(
//         'SELECT CUS_CreditLimitAmt FROM CUS WHERE CUS_CustomerID = :customerid;'
//     );

//     $stmt->execute([
//         ':customerid' => $strCustomerID,
//     ]);

//       #get results
//     $result = $stmt->fetch();  #$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

//     #any records?
//     if (!empty($result)) {
//         $intErrorCount = 0;

//         #customer summary start
//         $strSummaryHTML = $strSummaryHTML . '<p>Credit Limit: ' . funcFormatCurrency($result['CUS_CreditLimitAmt'], '£') . '</p>';  
//          #call functions to get rest of values
//         if (funcGetBacklog($pdo, $strCustomerID)) $intErrorCount++;
//         if (funcOutstandingCreditMemoValue($pdo, $strCustomerID)) $intErrorCount++;
//         if (funcOutstandingInvoiceValue($pdo, $strCustomerID)) $intErrorCount++;
//         if (funcSalesYTD($pdo, $strCustomerID)) $intErrorCount++;
//         if (funcSalesMTD($pdo, $strCustomerID)) $intErrorCount++;
//         if (funcSalesLastYTD($pdo, $strCustomerID)) $intErrorCount++;
//         if (funcGetOutstandingAR($pdo, $strCustomerID)) $intErrorCount++;
      
//         //any errors
//         if ($intErrorCount !== 0)
//             {
//             // error_log('Errors: '. $blnError . ' ' . print_r($strErrors, true) . PHP_EOL,3,'/var/www/WebApp/public/phperrors.log');      
//             // funcWriteLog(CNST_STR_ERRORLOG, null, 'Last YTD Error');
//             echo json_encode([
//                     'status' => false,
//                     'error' => 'Errors Stopped Processing Check PHp Log File On Server'
//                 ]);
//             }
//         else
//             {
//                 echo json_encode([
//                     'status' => true,
//                     'HTML' => $strHTML
//                 ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
//             }
//         }
//     else {
//         funcWriteLog(CNST_STR_ERRORLOG, $e, 'No Customer Record Found');     
//         echo json_encode([
//                 'status' => false,
//                 'error' => 'No Customer Record Found'
//             ]);
//     }
// } catch (PDOException $e) {
//     funcWriteLog(CNST_STR_ERRORLOG, $e, 'Error Trying To Read Customer Summary Data');
//     http_response_code(500);

//     echo json_encode([
//         'status' => false,
//         'error' => 'JSON Unable To Process Request'
//     ]);
//     exit;
// }

?>
