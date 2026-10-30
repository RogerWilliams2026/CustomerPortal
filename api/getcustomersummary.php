<?php
/*
Modified 30/10/2026 By Roger Williams

Implemented new log writing system using new PHP files:
- logwriter
- globals

uses globals.php to get log name and logwritefunction:
funcWriteLog
to write to chosen log

no longer need $strErrors!


Modified 29/10/2026 By Roger Williams

changed functions to return true if error and populate $strErrors if error


Modified 28/10/2026 By Roger Williams

added new var: $offset = -6

this is because YTD/MTD etc will return ZERO as last actual order date for the customers
in the SOM file is 2021, so apply offset to the date in the SQL queries!


Modified 26/10/2026 By Roger Williams

now returns HTML for use in DIVSummary in page main.html

NOTE: when returning JSON MUST have 'status' => in code!!


Created 21/10/2026 By Roger Williams

web page passes customer ID to this script which runs numerous SQL queries to get customer summary information
which is put into a JSON file and returned

returns:

JSON file containing customer summary:

- credit limit
- outstanding AR
- outstanding backlog
- outstanding invoices
- outstanding credit memo value
- sales YTD
- sales MTD
- last year sales YTD

*/

//use strict data types
declare(strict_types=1);

#connect to database
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/globals.php';
require_once __DIR__ . '/logwriter.php';

#VARS
$strSummaryHTML = '<h3>Customer Summary</h3>';
$strCustomerID = '';
$fltCUS_CreditLimitAmt = 0.00;
//year offset for queries
$intOffset = -5;
//for error handling
$intErrorCount = 0;


function funcFormatCurrency(float $fltAmount, string $strCurrencySymbol)
{
/*
    Created 28/10/2026 By Roger Williams

    
    returns string with currency symbol and $amount formatted
    as currency

*/

    $fltFormattedAmount = 0.00;
    $parts = '';

    if (!is_numeric($fltAmount)) {
        return  $strCurrencySymbol . '0.00';
    }

    //convert to float
    $fltAmount = floatval($fltAmount);
    //format as currency
    $fltFormattedAmount = number_format($fltAmount, 2, '.', '');
    //$formattedAmount  needs splitting into MSB/LSB then formatting
    //to get an accurate return string
    $parts = explode('.', $fltFormattedAmount);
    $parts[0] = number_format((float)$parts[0]);

    //return string with currency symbol and  
    return $strCurrencySymbol . implode('.', $parts);
}


function funcSalesLastYTD(PDO $pdo, $strCustomerID)
{
/*
    Created 26/10/2026 By Roger Williams

    gets sales last YTD and adds to $summaryJSON class

    if error returns false and puts error message into global var: $strErrors

*/
    global $intOffset; 
    global $strSummaryHTML;
 
    $intStartOffset = (int)$intOffset - 2;
    $intEndOffset   = (int)$intOffset - 1;

    //using "" as will insert variable into string
    $SQL = "
            SELECT Sum(SOD.SOD_UnitPrice * SOD.SOD_RequiredQty) AS SalesLastYTD FROM SOM
            INNER JOIN SOD ON (SOM.SOM_SalesOrderID = SOD.SOD_SalesOrderID)
            WHERE SOM.SOM_CustomerID = :customerid AND SOD.SOD_ShipComplete <> 0
            AND SOM.SOM_SalesOrderDate BETWEEN DATE_ADD(CURDATE(), INTERVAL :startoffset YEAR) AND DATE_ADD(CURDATE(), INTERVAL :endoffset YEAR);
           "; 

      try {
        $stmt = $pdo->prepare($SQL);
        $stmt->execute([
            ':customerid' => $strCustomerID,
            ':startoffset' => $intStartOffset,
            ':endoffset' => $intEndOffset
            ]);

        #get results
        $result = $stmt->fetch();

        #any records?
        if (!empty($result)) {
            $strSummaryHTML = $strSummaryHTML . '<p>Sales Last YTD: ' . funcFormatCurrency($result['SalesLastYTD'], '£') . '</p>';  
            }
        else {      
            return false;
        }
    } catch (PDOException $e) {
        funcWriteLog(CNST_STR_ERRORLOG, $e, 'Sales Last YTD Error');
        return true;
    }
  return false;
}


function funcSalesMTD(PDO $pdo, $strCustomerID)
{
/*
    Created 26/10/2026 By Roger Williams

    gets sales MTD and adds to $summaryJSON class

*/
    global $intOffset;
    global $strSummaryHTML;

    $HTML = "";
    $dateStart = date('Y/m/d');
    $dateStart = date_add(date_create($dateStart), date_interval_create_from_date_string('-1 month'));

    //add year offset
    $dateStart = date_add($dateStart, date_interval_create_from_date_string("{$intOffset} year"));   
    $dateStart = $dateStart->format('Y/m/d');

    try {
        $stmt = $pdo->prepare("
                SELECT Sum(SOD.SOD_UnitPrice * SOD.SOD_RequiredQty) AS SalesMTD FROM SOM
                INNER JOIN SOD ON (SOM.SOM_SalesOrderID = SOD.SOD_SalesOrderID)
                WHERE SOM.SOM_CustomerID = :customerid AND SOD.SOD_ShipComplete <> 0
                AND SOM.SOM_SalesOrderDate BETWEEN :startdate  AND CURDATE();
                ");

                $stmt->execute([
                    ':customerid' => $strCustomerID,
                    ':startdate' => $dateStart
                    ]);

        #get results
        $result = $stmt->fetch();
    
        #any records?
        if (!empty($result)) {
            if (is_null($result['SalesMTD']))
            {
                $strSummaryHTML = $strSummaryHTML . '<p>Sales MTD: ' . funcFormatCurrency(0, '£') . '</p>';
            }
            else
            {
                $strSummaryHTML = $strSummaryHTML . '<p>Sales MTD: ' . funcFormatCurrency($result['SalesMTD'], '£') . '</p>';
            }
            }
        else {
            return false;
        }
    } catch (PDOException $e) {
        funcWriteLog(CNST_STR_ERRORLOG, $e, 'Sales MTD Error');
        return true;
    }
  return false;
}


function funcSalesYTD(PDO $pdo, $strCustomerID)
{
/*
    Created 26/10/2026 By Roger Williams

    gets sales YTD and adds to $summaryJSON class

*/
    global $intOffset; 
    global $strSummaryHTML;

    $intNum = $intOffset -1;

    try {
        $stmt = $pdo->prepare("
        SELECT Sum(SOD.SOD_UnitPrice * SOD.SOD_RequiredQty) AS SalesYTD FROM SOM
        INNER JOIN SOD ON (SOM.SOM_SalesOrderID = SOD.SOD_SalesOrderID)
        WHERE SOM.SOM_CustomerID = :customerid AND SOD.SOD_ShipComplete <> 0
        AND SOM.SOM_SalesOrderDate BETWEEN DATE_ADD(CURDATE(),INTERVAL :lastyear YEAR) AND CURDATE();
        "
        ); 

        $stmt->execute([
            ':customerid' => $strCustomerID,
            ':lastyear' => $intNum
            ]);

        #get results
        $result = $stmt->fetch();
    
        #any records?
        if (!empty($result)) {
              $strSummaryHTML = $strSummaryHTML . '<p>Sales YTD: ' . funcFormatCurrency($result['SalesYTD'], '£') . '</p>';          
            }
        else {
           return false;
        }
      } catch (PDOException $e) {

        funcWriteLog(CNST_STR_ERRORLOG, $e, 'Sales YTD Error');
        return true;
    }
  return false;
}




function funcOutstandingInvoiceValue(PDO $pdo, $strCustomerID)
{
/*
    Created 26/10/2026 By Roger Williams

    gets outstanding invoices value and adds to $summaryJSON class

*/
    global $strSummaryHTML;

    try {
        $stmt = $pdo->prepare("
            SELECT Sum(IVM.IVM_InvoiceAmt) AS OutstandingInvoices FROM ARM
            INNER JOIN IVM ON (ARM.ARM_InvoiceID = IVM.IVM_InvoiceID)
            WHERE ARM.ARM_CustomerID = :customerid AND ARM.ARM_BalanceDueAmt <> 0 AND IVM.IVM_InvoiceAmt > 0; ");
        $stmt->execute([':customerid' => $strCustomerID]);

        #get results
        $result = $stmt->fetch();
    
        #any records?
        if (!empty($result)) {
              $strSummaryHTML = $strSummaryHTML .  '<p>Outstanding Invoices: ' . funcFormatCurrency($result['OutstandingInvoices'], '£') . '</p>';            
            }
        else {
          return false;                          
        }
      } catch (PDOException $e) {
        funcWriteLog(CNST_STR_ERRORLOG, $e, 'Last YTD Error');
        return true;
    }
  return false;
}



function funcOutstandingCreditMemoValue(PDO $pdo, $strCustomerID)
{
/*
    Created 26/10/2026 By Roger Williams

    gets outstanding credit memo value and adds to $summaryJSON class

*/
    global $strSummaryHTML;


    try {
        $stmt = $pdo->prepare("
            SELECT Sum(ARC.ARC_CreditAmt) AS OpenCreditMemos FROM ARC
            INNER JOIN ARM ON (ARC.ARC_InvoiceID = ARM.ARM_InvoiceID)
            WHERE ARM.ARM_CustomerID = :customerid AND ARM.ARM_BalanceDueAmt <> 0;");
        $stmt->execute([':customerid' => $strCustomerID]);

        #get results
        $result = $stmt->fetch();
    
        #any records?
        if (!empty($result)) {
                $strSummaryHTML = $strSummaryHTML . '<p>Outstanding Credit Memo Value: ' . funcFormatCurrency($result['OpenCreditMemos'], '£') . '</p>';        
            }
        else {
            echo json_encode([
                    'status' => false,
                    'error' => 'Customer ID Incorrect Or Not Found!'
                ]);
        }
      } catch (PDOException $e) {
        funcWriteLog(CNST_STR_ERRORLOG, $e, 'Outstanding Credit Memos Error');
        return true;
    }
  return false;
}



function funcGetBacklog(PDO $pdo, $strCustomerID)
{
/*
    Created 26/10/2026 By Roger Williams

    gets sales backlog and adds to $summaryJSON class

*/
    global $strSummaryHTML;

    try {

       $stmt = $pdo->prepare('
                SELECT Sum(SOD.SOD_UnitPrice * SOD.SOD_RequiredQty) AS Backlog FROM SOM
                INNER JOIN SOD ON (SOM.SOM_SalesOrderID = SOD.SOD_SalesOrderID)
                WHERE SOM.SOM_CustomerID = :customerid AND SOD.SOD_ShipComplete <> 0;');
        $stmt->execute([':customerid' => $strCustomerID]);

        #get results
        $result = $stmt->fetch();
    
        #any records?
        if (!empty($result)) {
            #customer found
            $strSummaryHTML = $strSummaryHTML . '<p>Sales Order Backlog Value: ' . funcFormatCurrency($result['Backlog'], '£') . '</p>';          
            }
        else {
           return false; 
        }
      } catch (PDOException $e) {
        funcWriteLog(CNST_STR_ERRORLOG, $e, 'Backlog Error');
        return true;
    }
  return false;
}

function funcGetOutstandingAR(PDO $pdo, $strCustomerID)
{
/*
    Modified 28/10/2026 By Roger Williams

    in order to get any data to apply global $offset value to year


    Created 21/10/2026 By Roger Williams

    gets outstanding AR and adds to $summaryJSON class

*/
    global $intOffset; 
    global $strSummaryHTML;

    $sql = "";

    #start date is today - 1 month
    $dateStart = date('Y/m/d');
    $dateStart = date_add(date_create($dateStart), date_interval_create_from_date_string('-1 month'));

    //add year offset
    $dateStart = date_add($dateStart, date_interval_create_from_date_string("{$intOffset} year"));
    $dateStart = $dateStart->format('Y/m/d');
 
    #end date is today
    $dateEnd = date('Y/m/d');
 
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
 
    $sql = "
    SELECT SUM(QueryAR.OutstandingAR) AS SumOfAR
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
              $strSummaryHTML = $strSummaryHTML .  '<p>Outstanding AR: ' . funcFormatCurrency($result['SumOfAR'], '£') . '</p>';        
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


#************end funcs**********


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
$strCustomerID = trim($data['customerID'] ?? '');

if ($strCustomerID === '') {
    http_response_code(400);

    echo json_encode([
        'status' => false,
        'error' => 'Customer ID Is Required'
    ]);

    exit;
}

#customer summary

try {
    $stmt = $pdo->prepare(
        'SELECT CUS_CreditLimitAmt FROM CUS WHERE CUS_CustomerID = :customerid;'
    );

    $stmt->execute([
        ':customerid' => $strCustomerID,
    ]);

      #get results
    $result = $stmt->fetch();  #$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    #any records?
    if (!empty($result)) {
        $intErrorCount = 0;

        #customer summary start
        $strSummaryHTML = $strSummaryHTML . '<p>Credit Limit: ' . funcFormatCurrency($result['CUS_CreditLimitAmt'], '£') . '</p>';  
         #call functions to get rest of values
        if (funcGetBacklog($pdo, $strCustomerID)) $intErrorCount++;
        if (funcOutstandingCreditMemoValue($pdo, $strCustomerID)) $intErrorCount++;
        if (funcOutstandingInvoiceValue($pdo, $strCustomerID)) $intErrorCount++;
        if (funcSalesYTD($pdo, $strCustomerID)) $intErrorCount++;
        if (funcSalesMTD($pdo, $strCustomerID)) $intErrorCount++;
        if (funcSalesLastYTD($pdo, $strCustomerID)) $intErrorCount++;
        if (funcGetOutstandingAR($pdo, $strCustomerID)) $intErrorCount++;
      
        //any errors
        if ($intErrorCount !== 0)
            {
            // error_log('Errors: '. $blnError . ' ' . print_r($strErrors, true) . PHP_EOL,3,'/var/www/WebApp/public/phperrors.log');      
            // funcWriteLog(CNST_STR_ERRORLOG, null, 'Last YTD Error');
            echo json_encode([
                    'status' => false,
                    'error' => 'Errors Stopped Processing Check PHp Log File On Server'
                ]);
            }
        else
            {
                echo json_encode([
                    'status' => true,
                    'SummaryHTML' => $strSummaryHTML
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }
    else {
        funcWriteLog(CNST_STR_ERRORLOG, $e, 'No Customer Record Found');     
        echo json_encode([
                'status' => false,
                'error' => 'No Customer Record Found'
            ]);
    }
} catch (PDOException $e) {
    funcWriteLog(CNST_STR_ERRORLOG, $e, 'Error Trying To Read Customer Summary Data');
    http_response_code(500);

    echo json_encode([
        'status' => false,
        'error' => 'JSON Unable To Process Request'
    ]);
    exit;
}

?>