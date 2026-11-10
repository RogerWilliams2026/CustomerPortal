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
  <link type="text/css" link rel="stylesheet" href="/css/reportstyle.css"></link>
  <body>
  <div id="divReportHeader">
     <h3>Report Results:</h3>
  </div>
   <div id="divReportDetail"> 
      <div id="divReportFlex"></div>   
   </div>      
';

// const CNST_STR_HTML_FOOTER = '
// <footer>
//    Date Printed: ' . date("d/m/Y") .
// '<hr id="lineReport">
// </footer>
//  </body>
// <script src="./JavaScript/general.js" type="module" defer></script>
// </html>
// ';

const CNST_STR_HTML_FOOTER = '
<footer>
<hr id="lineReport">
</footer>
 </body>
<script src="./JavaScript/general.js" type="module" defer></script>
</html>
';

//used for creating report pages
$strHTML = '';
$dateFrom = '';
$dateTo = '';
$intErrorCount = 0;

//*****custom funcs*****


function funcGetOutstandingAR()
{
/*
    Modified 28/10/2026 By Roger Williams

    in order to get any data to apply global $offset value to year
    now returns HTML for page iframe


    Created 21/10/2026 By Roger Williams

    gets outstanding AR and adds to $summaryJSON class

*/
    global $dateFrom;
    global $dateTo;
    global $strHTML;
    global $pdo;
    global $strCustomerID;

    $sql = "";
    #start date is $DateFrom - 1 month
    $dateStart = date_add(date_create($dateFrom), date_interval_create_from_date_string('-1 month'));

    //add year offset
    $dateStart = $dateStart->format('Y/m/d');
    #end date is $DateTo
    $dateEnd = date_create($dateTo); //date('Y/m/d');
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
    $sql = "
    SELECT DISTINCT 
            ARM_InvoiceID,
            ARM_SalesOrderID,
            ARM_CurrencyType,
            ARM_Region,
            ARM_CustomerPOID,
            ARM_InvoiceDate,
            ARM_DueDate,
            ARM_ARTermsDesc,
            ARM_InvoiceAmt,
            SumOfARP_PayAmt,
            QueryAR.BucketOne,
            QueryAR.BucketTwo,
            QueryAR.BucketThree,
            QueryAR.BucketFour
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
     ORDER BY ARM_InvoiceDate DESC;
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

        $result = $stmt->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT);

        #any records?
        if (!empty($result)) 
        {
funcWriteLog(CNST_STR_ERRORLOG, null, 'HTML: ' . $result);      
            //create detail header
            $strHTML = '<table id="tblReportDetail">' .
                       '<tr>' .
                       '<th>Invoice Date</th>' . 
                       '<th>Due Date</th>' . 
                       '<th>AR Terms</th>' . 
                       '<th>Invoice ID</th>' . 
                       '<th>Sales Order ID</th>' . 
                       '<th>Customer PO ID</th>' . 
                       '<th>Region</th>' . 
                       '<th>Invoice Amount</th>' . 
                       '<th>Amount Due</th>' . 
                       '<th>Bucket One</th>' . 
                       '<th>Bucket Two</th>' . 
                       '<th>Bucket Three</th>' . 
                       '<th>Bucket Four</th>' . 
                       '</tr>' . 
                       '<tr><hr id="lineReport"></tr>';

            //create first row
            $strHTML = $strHTML . '<tr class="clsReportDetail">' .
                '<td>' . $result[5] . '</td>' . 
                '<td>' . $result[6] . '</td>' . 
                '<td>' . $result[7] . '</td>' . 
                '<td>' . $result[0] . '</td>' . 
                '<td>' . $result[1] . '</td>' . 
                '<td>' . $result[4] . '</td>' . 
                '<td>' . $result[3] . '</td>' . 
                '<td>' . funcFormatCurrency($result[9], '£') . '</td>' . 
                '<td>' . funcFormatCurrency($result[10], '£') . '</td>' . 
                '<td>' . funcFormatCurrency($result[11], '£') . '</td>' . 
                '<td>' . funcFormatCurrency($result[12], '£') . '</td>' . 
                '<td>' . funcFormatCurrency($result[13], '£') . '</td>' . 
                '</tr>';

            //loop through records and create HTML page
            while ($row = $stmt->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT))
            {
 /*
     0        ARM_InvoiceID,*
     1        ARM_SalesOrderID,*
     2        ARM_CurrencyType,
     3        ARM_Region,*
     4        ARM_CustomerPOID,*
     5        ARM_InvoiceDate,*
     6        ARM_DueDate,*
     7        ARM_ARTermsDesc,*
     8        ARM_InvoiceAmt,*
     9        SumOfARP_PayAmt,*
     10       QueryAR.BucketOne,
     11       QueryAR.BucketTwo,
     12       QueryAR.BucketThree,
     13       QueryAR.BucketFour
*/
                //just incase for bizarre reason there is no invoice ID!
                if (!is_null($row[0]) ) 
                {
                    $strHTML = $strHTML . '<tr class="clsReportDetail">' .
                            '<td>' . $row[5] . '</td>' . 
                            '<td>' . $row[6] . '</td>' . 
                            '<td>' . $row[7] . '</td>' . 
                            '<td>' . $row[0] . '</td>' . 
                            '<td>' . $row[1] . '</td>' . 
                            '<td>' . $row[4] . '</td>' . 
                            '<td>' . $row[3] . '</td>' . 
                            '<td>' . funcFormatCurrency($row[9], '£') . '</td>' . 
                            '<td>' . funcFormatCurrency($row[10], '£') . '</td>' . 
                            '<td>' . funcFormatCurrency($row[11], '£') . '</td>' . 
                            '<td>' . funcFormatCurrency($row[12], '£') . '</td>' . 
                            '<td>' . funcFormatCurrency($row[13], '£') . '</td>' . 
                            '</tr>';
                }               
            }
             $strHTML = $strHTML . '</table>';
             return false;
        }
        else {
            return false;
        }
      } catch (PDOException $e) {
            funcWriteLog(CNST_STR_ERRORLOG, $e, 'Outstanding ARError');
            return true;
    }
};


function funcGetCreditMemos()
{
/*
    Created 09/11/2026 By Roger Williams

    gets credit memos for customer returns as HTML table


*/
    global $dateFrom;
    global $dateTo;
    global $strHTML;
    global $pdo;
    global $strCustomerID;
    global $strCreditMemo;

    $sql = '';
    $strInvoiceID = '';

funcWriteLog(CNST_STR_ERRORLOG, null, 'date to: ' . $dateTo);   
    if ($dateTo !== '')
    { 
        #start date
        $dateStart = date_create($dateFrom);
        $dateStart = $dateStart->format('Y/m/d');
        #end date is $DateTo
        $dateEnd = date_create($dateTo);
        $dateEnd = $dateEnd->format('Y/m/d');
        //ARM_Comments is for header ARC_Comments is 'item id'
        $sql = "
                SELECT ARM.ARM_InvoiceID, ARM.ARM_InvoiceDate, ARC.ARC_CreditAmt, ARC.ARC_Comment, 
                ARM.ARM_DueDate, ARM.ARM_Comments, IVI.IVI_TotalShipQty FROM ARC
                INNER JOIN ARM ON (ARC.ARC_InvoiceID = ARM.ARM_InvoiceID)
                INNER JOIN IVI ON (IVI.IVI_InvoiceID = ARM.ARM_InvoiceID)
                WHERE ARM.ARM_CustomerID = :customerid AND ARM.ARM_BalanceDueAmt <> 0
                AND ARM.ARM_InvoiceDate BETWEEN :startdate AND :enddate
                ORDER BY ARM.ARM_InvoiceDate DESC;
        ";
    }
    else
    {
funcWriteLog(CNST_STR_ERRORLOG, null, 'creditmemo: ' . $strCreditMemo);         
        if ($strCreditMemo === 'all' )
        {
            //ARM_Comments is for header ARC_Comments is 'item id'
            $sql = "
                    SELECT ARM.ARM_InvoiceID, ARM.ARM_InvoiceDate, ARC.ARC_CreditAmt, ARC.ARC_Comment, 
                    ARM.ARM_DueDate, ARM.ARM_Comments, IVI.IVI_TotalShipQty FROM ARC
                    INNER JOIN ARM ON (ARC.ARC_InvoiceID = ARM.ARM_InvoiceID)
                    INNER JOIN IVI ON (IVI.IVI_InvoiceID = ARM.ARM_InvoiceID)
                    WHERE ARM.ARM_CustomerID = :customerid AND ARM.ARM_BalanceDueAmt <> 0
                    ORDER BY ARM.ARM_InvoiceDate DESC;
            ";
        }    
        else
        {
            //ARM_Comments is for header ARC_Comments is 'item id'
            $sql = "
                    SELECT ARM.ARM_InvoiceID, ARM.ARM_InvoiceDate, ARC.ARC_CreditAmt, ARC.ARC_Comment, 
                    ARM.ARM_DueDate, ARM.ARM_Comments, IVI.IVI_TotalShipQty FROM ARC
                    INNER JOIN ARM ON (ARC.ARC_InvoiceID = ARM.ARM_InvoiceID)
                    INNER JOIN IVI ON (IVI.IVI_InvoiceID = ARM.ARM_InvoiceID)
                    WHERE ARM.ARM_InvoiceID = :creditmemo AND ARM.ARM_BalanceDueAmt <> 0
                    ORDER BY ARM.ARM_InvoiceDate DESC;
            ";
        }
    }    
   
    try 
    {    
        $stmt = $pdo->prepare($sql);

         if ($dateTo !== '')
         {     
            $stmt->execute([
            'startdate'   => $dateStart,
            'enddate'     => $dateEnd,
            'customerid'  => $strCustomerID
            ]);
        }
        else
        {
            if ($strCreditMemo === 'all')
            {        
                $stmt->execute(['customerid'  => $strCustomerID]);  
            }       
            else
            {
                $stmt->execute(['creditmemo' => $strCreditMemo]);   
            }        
        }    

        $result = $stmt->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT);

        #any records?
        if (!empty($result)) 
        {
funcWriteLog(CNST_STR_ERRORLOG, null, 'result: ' . $result);    
//ARM.ARM_InvoiceID, ARM.ARM_InvoiceDate, ARC.ARC_CreditAmt, ARC.ARC_Comment, 
//ARM.ARM_DueDate, ARM.ARM_Comments, IVI.IVI_TotalShipQty 

            $strHTML = '<table>' .
                       '<tr class="clsReportHeader">' .
                       '<th>Invoice ID</th>' . 
                       '<th>Invoice Date</th>' . 
                       '<th>Due Date</th>' . 
                       '<th>Comments</th>' . 
                       '</tr>' . 
                       '<tr style="border-bottom:1px solid white">
                          <td colspan="100%"></td>
                        </tr>';

            //create header lines
            $strHTML = $strHTML . '<tr class="clsReportHeaderDetail">' .
                    '<td>' . $result[0] . '</td>' . 
                    '<td>' . $result[1] . '</td>' .
                    '<td>' . $result[4] . '</td>' .
                    '<td>' . $result[5] . '</td>' . 
                    '</tr>';
            $strHTML = $strHTML . '</table>';

            // //create lines header
            $strHTML = $strHTML . '<table>' .
                       '<tr class="clsReportDetail">' .
                       '<th>What</th>' . 
                       '<th>Qty</th>' . 
                       '<th>Value</th>' . 
                       '</tr>' . 
                       '<tr style="border-bottom:1px solid white">
                          <td colspan="100%"></td>
                        </tr>';
                   
            //add first row 
            $strHTML = $strHTML . '<tr class="clsReportDetail">' .
                    '<td>' . $result[3] . '</td>' . 
                    '<td>' . $result[6] . '</td>' . 
                    '<td>' . funcFormatCurrency($result[2], '£') . '</td>' . 
                    '</tr>';
           
             //set current invoice ID             
            $strInvoiceID = $result[0];   

funcWriteLog(CNST_STR_ERRORLOG, null, 'HTML pre loop ' . $strHTML);    
//             //loop through records and create HTML page
            while ($row = $stmt->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT))
            {
                //just incase for bizarre reason there is no invoice ID!
                if (!is_null($row[0]) ) 
                {
                   if ($strInvoiceID !== $row[0])
                   {
                        //create detail header
                        $strHTML = $strHTML . '<table class="clsReportHeader">' .
                                '<tr>' .
                                '<th>Invoice ID</th>' . 
                                '<th>Invoice Date</th>' . 
                                '<th>Due Date</th>' . 
                                '<th>Comments</th>' . 
                                '</tr>' . 
                                '<tr style="border-bottom:1px solid white">
                                    <td colspan="100%"></td>
                                    </tr>';

                        //create header lines
                        $strHTML = $strHTML . '<tr class="clsReportHeaderDetail">' .
                                '<td>' . $row[0] . '</td>' . 
                                '<td>' . $row[1] . '</td>' .
                                '<td>' . $row[4] . '</td>' .
                                '<td>' . $row[5] . '</td>' . 
                                '</tr>';

                        //create lines header
                        $strHTML = $strHTML . '<table class="clsReportDetail">' .
                                '<tr>' .
                                    '<th>What</th>' . 
                                    '<th>Qty</th>' . 
                                    '<th>Value</th>' . 
                                '</tr>' . 
                                '<tr style="border-bottom:1px solid white">
                                    <td colspan="100%"></td>
                                    </tr>';
                      
                        //add first row 
                        $strHTML = $strHTML . '<tr class="clsReportDetail">' .
                                '<td>' . $row[3] . '</td>' . 
                                '<td>' . $row[6] . '</td>' . 
                                '<td>' . funcFormatCurrency($row[2], '£') . '</td>' . 
                                '</tr>';
funcWriteLog(CNST_STR_ERRORLOG, null, 'HTML loop new invoice ID ' . $strHTML);                                   

                        //set current invoice ID             
                        $strInvoiceID = $row[0];  
                   }  
                   else
                   {
                        //add lines to existing credit memo
                        $strHTML = $strHTML . '<tr class="clsReportDetail">' .
                                '<td>' . $result[3] . '</td>' . 
                                '<td>' . $result[6] . '</td>' . 
                                '<td>' . funcFormatCurrency($result[2], '£') . '</td>' . 
                                '</tr>';           
                   } 
                }               
            }
             $strHTML = $strHTML . '</table>';
funcWriteLog(CNST_STR_ERRORLOG, null, 'HTML post loop ' . $strHTML);                
             return false;
       }
       else {
           return false;
       }
      } catch (PDOException $e) {
            funcWriteLog(CNST_STR_ERRORLOG, $e, 'Credit Memos Error');
            return true;
    }
};


function funcGetCreditMemosList()
{
/*
    Created 09/11/2026 By Roger Williams

    gets credit memos for customer returns as select list items HTML


*/
    global $strHTML;
    global $pdo;
    global $strCustomerID;

    //ARM_Comments is for header ARC_Comments is 'item id'
    $sql = "
            SELECT DISTINCT ARM_InvoiceID, ARM.ARM_InvoiceDate FROM ARM 
            INNER JOIN ARC ON ARM.ARM_InvoiceID = ARC.ARC_InvoiceID
            WHERE ARM.ARM_CustomerID = :customerid AND ARM.ARM_BalanceDueAmt <> 0
            ORDER BY ARM.ARM_InvoiceDate DESC;
    ";
    //add default option
    $strHTML = $strHTML . '<option value="all">All</option>'; 

    try 
    {    
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['customerid'  => $strCustomerID]);
        $result = $stmt->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT);

        #any records?
        if (!empty($result)) 
        {
//funcWriteLog(CNST_STR_ERRORLOG, null, 'HTML: ' . $result);      
           $strHTML = $strHTML . '<option value="' . $result[0] . '">' . $result[0]  .'</option>'; 
   
           //loop through records and create HTML page
            while ($row = $stmt->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT))
            {
                //just incase for bizarre reason there is no invoice ID!
                if (!is_null($row[0]) ) 
                {
                    $strHTML = $strHTML . '<option value="' . $row[0] . '">' . $row[0]  .'</option>'; 
                }               
            }
//funcWriteLog(CNST_STR_ERRORLOG, null, 'HTML: ' . $strHTML);
             return false;
        }
        else
        {
            return false;
        }         
    } 
    catch (PDOException $e) 
    {
            funcWriteLog(CNST_STR_ERRORLOG, $e, 'Outstanding ARError');
            return true;
    }
};

function funcGetBacklog()
{
/*
    Created 26/10/2026 By Roger Williams

    gets sales backlog returns HTML for page iframe

*/
    global $strHTML;
    global $pdo;
    global $strCustomerID;

    try {
       $stmt = $pdo->prepare('
                SELECT SOM.SOM_SalesOrderID, SOM.SOM_SalesOrderDate, SOD.SOD_SOLineNbr, 
                SOD.SOD_RequiredQty, SOD.SOD_UnitPrice,  
                SOD.SOD_UnitPrice * SOD.SOD_RequiredQty AS LineTotal FROM SOM
                INNER JOIN SOD ON (SOM.SOM_SalesOrderID = SOD.SOD_SalesOrderID)
                WHERE SOM.SOM_CustomerID = :customerid AND SOD.SOD_ShipComplete <> 0;');


        $stmt->execute([':customerid' => $strCustomerID]);
        #get results
        $result = $stmt->fetch();
    
        $result = $stmt->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT);

        #any records?
        if (!empty($result)) 
        {
//funcWriteLog(CNST_STR_ERRORLOG, null, 'HTML: ' . $result);     
            //create detail header
            $strHTML = '<table id="tblReportDetail">' .
                       '<tr>' .
                       '<th>Sales Order ID</th>' . 
                       '<th>Sales Order Date</th>' . 
                       '<th>Line Nbr</th>' . 
                       '<th>Qty</th>' . 
                       '<th>Unit Price</th>' . 
                       '<th>Line Total</th>' . 
                       '</tr>' . 
                       '<tr><hr id="lineReport"></tr>';
           //add first row 
            $strHTML = $strHTML . '<tr class="clsReportDetail">' .
                    '<td>' . $result[0] . '</td>' . 
                    '<td>' . $result[1] . '</td>' . 
                    '<td>' . $result[2] . '</td>' . 
                    '<td>' . $result[3] . '</td>' . 
                    '<td>' . funcFormatCurrency($result[4], '£') . '</td>' . 
                    '<td>' . funcFormatCurrency($result[5], '£') . '</td>' . 
                    '</tr>';
                       
            //loop through records and create HTML page
            while ($row = $stmt->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT))
            {
                //just incase for bizarre reason there is no invoice ID!
                if (!is_null($row[0]) ) 
                {
                    $strHTML = $strHTML . '<tr class="clsReportDetail">' .
                            '<td>' . $row[0] . '</td>' . 
                            '<td>' . $row[1] . '</td>' . 
                            '<td>' . $row[2] . '</td>' . 
                            '<td>' . $row[3] . '</td>' . 
                            '<td>' . funcFormatCurrency($row[4], '£') . '</td>' . 
                            '<td>' . funcFormatCurrency($row[5], '£') . '</td>' . 
                            '</tr>';
                }   
            }

            $strHTML = $strHTML . '</table>';
//funcWriteLog(CNST_STR_ERRORLOG,null, 'HTML2: ' . $strHTML);            
            return false;
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
$dateFrom = trim($data['datefrom'] ?? '');
$dateTo = trim($data['dateto'] ?? '');
$strCreditMemo = trim($data['creditmemo'] ?? '');
funcWriteLog(CNST_STR_ERRORLOG, null, 'creditmemo from JS: ' . $strCreditMemo);  

if ($strReportType === '') {
    http_response_code(400);

    echo json_encode([
        'status' => false,
        'error' => 'Report Type Is Required'
    ]);

    exit;
}
funcWriteLog(CNST_STR_ERRORLOG, null, 'credit memo: ' . $strCreditMemo); 
funcWriteLog(CNST_STR_ERRORLOG, null, 'report type: ' . $strReportType); 
//create report HTML based on user choice
switch ($strReportType)
{
   case 'ar':
        if ($dateFrom === '') {
            http_response_code(400);

            echo json_encode([
                'status' => false,
                'error' => 'From Date Is Required'
            ]);

            exit;
        }

        if ($dateTo === '') {
            http_response_code(400);

            echo json_encode([
                'status' => false,
                'error' => 'To Date Is Required'
            ]);

            exit;
        }
        if (funcGetOutstandingAR()) $intErrorCount++;
           break;
           
   case 'backlog':
       if ((funcGetBacklog())) $intErrorCount++;   
       break;

   case 'creditmemos':
        if (funcGetCreditMemos()) $intErrorCount++;
           break;   

   case 'creditmemoslist':
        if (funcGetCreditMemosList()) $intErrorCount++;
        break;                  
}

funcWriteLog(CNST_STR_ERRORLOG, null, 'error count: ' . (string)$intErrorCount); 
//any errors
if ($intErrorCount !== 0)
    {
    echo json_encode([
            'status' => false,
            'error' => 'Errors Stopped Processing Check PHP Log File On Server'
        ]);
    }

funcWriteLog(CNST_STR_ERRORLOG, null,"HTML: " .  $strHTML);  

//if not getting a list check for errors and return $strHTML
if ($strReportType !== 'creditmemoslist')
{
    if ($strHTML !== '')
    {
        $strHTML = CNST_STR_HTML_HEADER . $strHTML . CNST_STR_HTML_FOOTER;
    
        echo json_encode([
            'status' => true,
            'HTML' => $strHTML,
            'norecords' => false
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    else
    {
        echo json_encode([
            'status' => true,
            'HTML' => $strHTML,
            'norecords' => true
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    //   funcWriteLog(CNST_STR_ERRORLOG, null, 'Error Trying To Create HTML');
    }
}    
else
{
    if ($strHTML !== '')
    {
        echo json_encode([
            'status' => true,
            'HTML' => $strHTML,
            'norecords' => false
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    else
    {
        echo json_encode([
            'status' => true,
            'HTML' => $strHTML,
            'norecords' => true
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }    
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
