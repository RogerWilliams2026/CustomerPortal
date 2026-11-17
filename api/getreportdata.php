<?php
/*



*/


//use strict data types
declare(strict_types=1);

#connect to database
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/globals.php';
require_once __DIR__ . '/logwriter.php';

// const CNST_STR_INVOICEHEADER_REPORT = '<div class="clsInvoiceHeader"><h3>Orbik Electronics Limited</h3></div><br>  
//                                 <div>Orbik House</div><br> 
//                                 <div>Northgate Way</div><br> 
//                                 <div>Aldridge</div><br> 
//                                 <div>Walsall</div><br> 
//                                 <div>WS9 8TX</div><br> 
//                                 <div>Telephone: (44) 01922 646532</div><br>
//                                 <div> Email: MyCompany@here.com</div><br>
//                                 ';
const CNST_STR_INVOICEHEADER_REPORT = '<h3>My Company Limited</h3>  
                                Company House<br> 
                                Northgate Far<br> 
                                Belridge<br> 
                                New Town<br> 
                                WZ9 1SX<br> 
                                Telephone: (44) 01922 646532<br>
                                Email: MyCompany@here.com
                                ';

const CNST_STR_INVOICEFOOTER_REPORT = '<div class="clsInvoiceFooter">Please Make Cheques Payable To Rog Electronics Ltd
                                Or BACS Payments To Acct No: 16340300 Sort Code: 16-22-10</div>';


const CNST_STR_HTML_HEADER = '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <!--
    Modified from the Debian original for Ubuntu
    Last updated: 2022-03-22
    See: https://launchpad.net/bugs/1966004
  -->
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <title>RogCustomer Portal 2026 - Report Results</title>
  </head>
  <link type="text/css" link rel="stylesheet" href="/css/reportstyle.css"></link>
  <body>
 
';
/*
  <div id="divReportHeader">
     <h3>Report Results:</h3>
  </div> 
<div id="divReportDetail"> 
      <div id="divReportFlex"></div>   
   </div>   
*/   

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
$strInvoice = '';

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
    global $blnOpenCreditMemo;

    $sql = '';
    $strInvoiceID = '';
    $strName = '';

funcWriteLog(CNST_STR_ERRORLOG, null, 'date to: ' . $dateTo);   
funcWriteLog(CNST_STR_ERRORLOG, null, 'creditmemo: ' . $strCreditMemo);   
funcWriteLog(CNST_STR_ERRORLOG, null, 'open only: ' . $blnOpenCreditMemo);  
    if ($dateTo !== '')
    { 
        #start date
        $dateStart = date_create($dateFrom);
        $dateStart = $dateStart->format('Y/m/d');
        #end date is $DateTo
        $dateEnd = date_create($dateTo);
        $dateEnd = $dateEnd->format('Y/m/d');
        //ARM_Comments is for header ARC_Comments is 'item id'
        if ($blnOpenCreditMemo)
        {
            $sql = "
                    SELECT ARM.ARM_InvoiceID, ARM.ARM_CustomerID, ARM.ARM_InvoiceDate, ARM.ARM_CustomerPOID, 
                    -(ARM_InvoiceAmt) AS InvAmt, ARM.ARM_DueDate, ARM.ARM_Comments, CUS.CUS_BillName, CUS.CUS_BillAddress, 
                    CUS.CUS_BillAddr2, CUS.CUS_BillAddr3, CUS.CUS_BillCity, CUS.CUS_BillState, CUS.CUS_BillPostalCode, 
                    CUS.CUS_BillCountry, ARC.ARC_CreditAmt, ARC.ARC_Comment, 
                    ARM.ARM_CurrencyType
                    FROM (ARM INNER JOIN CUS ON ARM.ARM_CustomerID = CUS.CUS_CustomerID) 
                    INNER JOIN ARC ON ARM.ARM_InvoiceID = ARC.ARC_InvoiceID
                    WHERE ARM.ARM_CustomerID = :customerid AND ARM.ARM_BalanceDueAmt <> 0
                    AND ARM.ARM_InvoiceDate BETWEEN :startdate AND :enddate
                    ORDER BY ARM.ARM_InvoiceDate DESC;
            ";
        }    
        else
        {
            $sql = "
                    SELECT ARM.ARM_InvoiceID, ARM.ARM_CustomerID, ARM.ARM_InvoiceDate, ARM.ARM_CustomerPOID, 
                    -(ARM_InvoiceAmt) AS InvAmt, ARM.ARM_DueDate, ARM.ARM_Comments, CUS.CUS_BillName, CUS.CUS_BillAddress, 
                    CUS.CUS_BillAddr2, CUS.CUS_BillAddr3, CUS.CUS_BillCity, CUS.CUS_BillState, CUS.CUS_BillPostalCode, 
                    CUS.CUS_BillCountry, ARC.ARC_CreditAmt, ARC.ARC_Comment, 
                    ARM.ARM_CurrencyType
                    FROM (ARM INNER JOIN CUS ON ARM.ARM_CustomerID = CUS.CUS_CustomerID) 
                    INNER JOIN ARC ON ARM.ARM_InvoiceID = ARC.ARC_InvoiceID
                    WHERE ARM.ARM_CustomerID = :customerid AND ARM.ARM_InvoiceDate BETWEEN :startdate AND :enddate
                    ORDER BY ARM.ARM_InvoiceDate DESC;
            ";
        }    
            
    }
    else
    {     
        if ($strCreditMemo === 'all' )
        {
            //ARM_Comments is for header ARC_Comments is 'item id'
            if ($blnOpenCreditMemo)
            {
                $sql = "
                        SELECT ARM.ARM_InvoiceID, ARM.ARM_CustomerID, ARM.ARM_InvoiceDate, ARM.ARM_CustomerPOID, 
                        -(ARM_InvoiceAmt) AS InvAmt, ARM.ARM_DueDate, ARM.ARM_Comments, CUS.CUS_BillName, CUS.CUS_BillAddress, 
                        CUS.CUS_BillAddr2, CUS.CUS_BillAddr3, CUS.CUS_BillCity, CUS.CUS_BillState, CUS.CUS_BillPostalCode, 
                        CUS.CUS_BillCountry, ARC.ARC_CreditAmt, ARC.ARC_Comment, 
                        ARM.ARM_CurrencyType
                        FROM (ARM INNER JOIN CUS ON ARM.ARM_CustomerID = CUS.CUS_CustomerID) 
                        INNER JOIN ARC ON ARM.ARM_InvoiceID = ARC.ARC_InvoiceID
                        WHERE CUS.CUS_CustomerID = :customerid AND ARM.ARM_BalanceDueAmt <> 0
                        ORDER BY ARM.ARM_InvoiceDate DESC;              
                ";
            }
            else
            {
                $sql = "
                        SELECT ARM.ARM_InvoiceID, ARM.ARM_CustomerID, ARM.ARM_InvoiceDate, ARM.ARM_CustomerPOID,
                        -(ARM_InvoiceAmt) AS InvAmt, ARM.ARM_DueDate, ARM.ARM_Comments, CUS.CUS_BillName, CUS.CUS_BillAddress, 
                        CUS.CUS_BillAddr2, CUS.CUS_BillAddr3, CUS.CUS_BillCity, CUS.CUS_BillState, CUS.CUS_BillPostalCode, 
                        CUS.CUS_BillCountry, ARC.ARC_CreditAmt, ARC.ARC_Comment, 
                        ARM.ARM_CurrencyType
                        FROM (ARM INNER JOIN CUS ON ARM.ARM_CustomerID = CUS.CUS_CustomerID) 
                        INNER JOIN ARC ON ARM.ARM_InvoiceID = ARC.ARC_InvoiceID
                        WHERE CUS.CUS_CustomerID = :customerid
                        ORDER BY ARM.ARM_InvoiceDate DESC;    
                ";                  
            }
        }    
        else
        {
            //ARM_Comments is for header ARC_Comments is 'item id'
                if ($blnOpenCreditMemo)
                {            
                    $sql = "
                            SELECT ARM.ARM_InvoiceID, ARM.ARM_CustomerID, ARM.ARM_InvoiceDate, ARM.ARM_CustomerPOID, 
                            -(ARM_InvoiceAmt) AS InvAmt, ARM.ARM_DueDate, ARM.ARM_Comments, CUS.CUS_BillName, CUS.CUS_BillAddress, 
                            CUS.CUS_BillAddr2, CUS.CUS_BillAddr3, CUS.CUS_BillCity, CUS.CUS_BillState, CUS.CUS_BillPostalCode, 
                            CUS.CUS_BillCountry, ARC.ARC_CreditAmt, ARC.ARC_Comment, 
                            ARM.ARM_CurrencyType
                            FROM (ARM INNER JOIN CUS ON ARM.ARM_CustomerID = CUS.CUS_CustomerID) 
                            INNER JOIN ARC ON ARM.ARM_InvoiceID = ARC.ARC_InvoiceID
                            WHERE ARM.ARM_InvoiceID = :creditmemo AND ARM.ARM_BalanceDueAmt <> 0
                            ORDER BY ARM.ARM_InvoiceDate DESC;                 
                    
                    ";
                }
                else
                {
                    $sql = "
                            SELECT ARM.ARM_InvoiceID, ARM.ARM_CustomerID, ARM.ARM_InvoiceDate, ARM.ARM_CustomerPOID, 
                            -(ARM_InvoiceAmt) AS InvAmt, ARM.ARM_DueDate, ARM.ARM_Comments, CUS.CUS_BillName, CUS.CUS_BillAddress, 
                            CUS.CUS_BillAddr2, CUS.CUS_BillAddr3, CUS.CUS_BillCity, CUS.CUS_BillState, CUS.CUS_BillPostalCode, 
                            CUS.CUS_BillCountry, ARC.ARC_CreditAmt, ARC.ARC_Comment, 
                            ARM.ARM_CurrencyType
                            FROM (ARM INNER JOIN CUS ON ARM.ARM_CustomerID = CUS.CUS_CustomerID) 
                            INNER JOIN ARC ON ARM.ARM_InvoiceID = ARC.ARC_InvoiceID
                            WHERE ARM.ARM_InvoiceID = :creditmemo
                            ORDER BY ARM.ARM_InvoiceDate DESC;  
                    ";               
                }    
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
/*
ARM.ARM_InvoiceID,                   0
ARM.ARM_CustomerID,                  1
ARM.ARM_InvoiceDate,                 2 
ARM.ARM_CustomerPOID                 3
-(ARM_InvoiceAmt) AS InvAmt,         4 
ARM.ARM_DueDate                      5
ARM.ARM_Comments,                    6

CUS.CUS_BillName,                    7
CUS.CUS_BillAddress,                 8
CUS.CUS_BillAddr2,                   9
CUS.CUS_BillAddr3,                   10 
CUS.CUS_BillCity,                    11
CUS.CUS_BillState,                   12
CUS.CUS_BillPostalCode,              13
CUS.CUS_BillCountry,                 14

ARC.ARC_CreditAmt,                   15
ARC.ARC_Comment,                     16  
ARM.ARM_CurrencyType,                17
-(ARM.ARM_InvoiceAmt) AS InvTotal    18  
*/

            //create customer address/name string
            $strName = $result[7];
            $strName = $strName . (is_null($result[8]) ? '' : $result[8]. '<br>');
            $strName = $strName . (is_null($result[9]) ? '' : $result[9]. '<br>');
            $strName = $strName . (is_null($result[10]) ? '' : $result[10]. '<br>');
            $strName = $strName . (is_null($result[11]) ? '' : $result[11]. '<br>');
            $strName = $strName . (is_null($result[12]) ? '' : $result[12]. '<br>');
            $strName = $strName . (is_null($result[13]) ? '' : $result[13]. '<br>');
            $strName = $strName . (is_null($result[14]) ? '' : $result[14]. '<br>');

            $strHTML = '<table>' .
                       '<tr class="clsReportHeader">' .
                       '<th>Customer</th>' .
                       '<th>Invoice ID</th>' . 
                       '<th>Invoice Date</th>' . 
                       '<th>Customer PO ID</th>' .
                       '<th>Invoice Total</th>' .
                       '<th>Due Date</th>' . 
                       '<th>Comments</th>' . 
                       '</tr>' . 
                       '<tr>' .
                       '     <td colspan="100%" style="box-shadow: inset 0 -2px 0 white;">' .
                       '         <br>  ;' .
                       '     </td>' .
                       '</tr>';

            //create header lines
            $strHTML = $strHTML . '<tr class="clsReportHeaderDetail">' .
                    '<td>' . $strName . '</td>' .
                    '<td>' . $result[0] . '</td>' . 
                    '<td>' . $result[2] . '</td>' .
                    '<td>' . $result[3] . '</td>' .
                    '<td>' . $result[4] . '</td>' .
                    '<td>' . $result[5] . '</td>' .
                    '<td>' . $result[6] . '</td>' . 
                    '</tr>';
            $strHTML = $strHTML . '</table>';

            //create lines header
            $strHTML = $strHTML . '<table>' .
                       '<tr class="clsReportDetail">' .
                       '<th>What</th>' . 
                       '<th>Value</th>' . 
                        '<tr>' .
                        '     <td colspan="100%" style="box-shadow: inset 0 -2px 0 white;">' .
                        '         <br>  ;' .
                        '     </td>' .
                        '</tr>';

            
            //add first row 
            $strHTML = $strHTML . '<tr class="clsReportDetail">' .
                    '<td>' . $result[16] . '</td>' . 
                    '<td>' . funcFormatCurrency($result[15], '£') . '</td>' . 
                    '</tr>';
           
             //set current invoice ID             
            $strInvoiceID = $result[0];   

funcWriteLog(CNST_STR_ERRORLOG, null, 'sql ' . $sql);    
funcWriteLog(CNST_STR_ERRORLOG, null, 'HTML pre loop ' . $strHTML);    
            //loop through records and create HTML page
            while ($row = $stmt->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT))
            {
                //just incase for bizarre reason there is no invoice ID!
                if (!is_null($row[0]) ) 
                {
funcWriteLog(CNST_STR_ERRORLOG, null, '$strInvoiceID ' . $strInvoiceID . ' ' . $row[0]);                      
                   if ($strInvoiceID !== $row[0])
                   {
                        $strHTML = $strHTML . '</table>';                    
                        //create customer address/name string
                        $strName = $row[7];
                        $strName = $strName . (is_null($row[8]) ? '' : $row[8]. '<br>');
                        $strName = $strName . (is_null($row[9]) ? '' : $row[9]. '<br>');
                        $strName = $strName . (is_null($row[10]) ? '' : $row[10]. '<br>');
                        $strName = $strName . (is_null($row[11]) ? '' : $row[11]. '<br>');
                        $strName = $strName . (is_null($row[12]) ? '' : $row[12]. '<br>');
                        $strName = $strName . (is_null($row[13]) ? '' : $row[13]. '<br>');
                        $strName = $strName . (is_null($row[14]) ? '' : $row[14]. '<br>');

                        $strHTML =  $strHTML . '<table>' .
                                '<tr class="clsReportHeader">' .
                                '<th>Customer</th>' .
                                '<th>Invoice ID</th>' . 
                                '<th>Invoice Date</th>' . 
                                '<th>Customer PO ID</th>' .
                                '<th>Invoice Total</th>' .
                                '<th>Due Date</th>' . 
                                '<th>Comments</th>' . 
                                '</tr>' . 
                                '<tr>' .
                                '     <td colspan="100%" style="box-shadow: inset 0 -2px 0 white;">' .
                                '         <br>  ;' .
                                '     </td>' .
                                '</tr>';


                        //create header lines
                        $strHTML = $strHTML . '<tr class="clsReportHeaderDetail">' .
                                '<td>' . $strName . '</td>' .
                                '<td>' . $row[0] . '</td>' . 
                                '<td>' . $row[2] . '</td>' .
                                '<td>' . $row[3] . '</td>' .
                                '<td>' . $row[4] . '</td>' .
                                '<td>' . $row[5] . '</td>' .
                                '<td>' . $row[6] . '</td>' . 
                                '</tr>';
                        $strHTML = $strHTML . '</table>';

                        //create lines header
                        $strHTML = $strHTML . '<table>' .
                                '<tr class="clsReportDetail">' .
                                '<th>What</th>' . 
                                '<th>Value</th>' . 
                                '</tr>' . 
                                '<tr>' .
                                '     <td colspan="100%" style="box-shadow: inset 0 -2px 0 white;">' .
                                '         <br>  ;' .
                                '     </td>' .
                                '</tr>';
                            
                        //add first row 
                        $strHTML = $strHTML . '<tr class="clsReportDetail">' .
                                '<td>' . $row[16] . '</td>' . 
                                '<td>' . funcFormatCurrency($row[15], '£') . '</td>' . 
                                '</tr>';
//funcWriteLog(CNST_STR_ERRORLOG, null, 'HTML loop new invoice ID ' . $strHTML);                                   

                        //set current invoice ID             
                        $strInvoiceID = $row[0];  
                   }  
                   else
                   {
                        //add lines to existing credit memo
                        $strHTML = $strHTML . '<tr class="clsReportDetail">' .
                                '<td>' . $row[16] . '</td>' . 
                                '<td>' . funcFormatCurrency($row[15], '£') . '</td>' . 
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


function funcGetInvoices()
{
{
/*
    Created 12/11/2026 By Roger Williams

    gets invoices for customer returns as HTML table


*/
    global $dateFrom;
    global $dateTo;
    global $strHTML;
    global $pdo;
    global $strCustomerID;
    global $strInvoice;

    $sql = '';
    $strInvoiceID = '';
    $strName = '';
    $strShipToName = '';
    $fltUnitPrice = 0;
    $fltDisc = 0;
    $fltVAT = 0;
    $fltTotal = 0;
    $fltGrandTotalValue = 0;
    $fltGrandTotalVAT = 0;
    $fltInvoiceTotalValue = 0;
    $fltInvoiceTotalVAT = 0;

 
    if ($dateTo !== '')
    { 
        #start date
        $dateStart = date_create($dateFrom);
        $dateStart = $dateStart->format('Y/m/d');
        #end date is $DateTo
        $dateEnd = date_create($dateTo);
        $dateEnd = $dateEnd->format('Y/m/d');

        $sql = "
            SELECT DISTINCTROW IVM.IVM_InvoiceID, IVM.IVM_CustomerPOID, 
            IVM.IVM_InvoiceDate, IVM.IVM_SalesOrderID, IVM.IVM_ShipDate, IVM.IVM_ShipToName, 
            IVM.IVM_ShipToAddress, IVM.IVM_ShipToAddr2, IVM.IVM_ShipToAddr3, IVM.IVM_ShipToCity, 
            IVM.IVM_ShipToState, IVM.IVM_ShipToPostalCode, IVM.IVM_ShipToCountry, IVM.IVM_CustomerID, 
            IVM.IVM_BillName, IVM.IVM_BillAddress, IVM.IVM_BillAddr2, IVM.IVM_BillAddr3, IVM.IVM_BillCity, 
            IVM.IVM_BillState, IVM.IVM_BillPostalCode, IVM.IVM_BillCountry, IVM.IVM_SalesOrderDate, 
            IVM.IVM_ShipMethod, IVM.IVM_TerritoryCodes, 
            IVM.IVM_CurrencyType,
            IVM.IVM_Region, IVM.IVM_NetInvoiceAmt, IVM.IVM_SlsTaxAmt, IVI.IVI_SlsTaxAmt, IVM.IVM_BalanceDueAmt, 
            IVM.IVM_SlsTaxDesc, IVM.IVM_SlsTaxPercent, IVI.IVI_SlsTaxPercent, 
            IVM.IVM_ARTermsDesc,
            IVI.IVI_SOLineNbr, IVI.IVI_CustPOLineNbr, IVI.IVI_ItemID, IVI.IVI_ItemName, 
            IVI.IVI_TaxableFlag, IVI.IVI_UnitMeasure, IVI.IVI_LineSpecialInst, IVD.IVD_RequiredDate, 
            IVD.IVD_ShipQty, IVD.IVD_UnitPrice, IVD.IVD_DiscPercent, 
            IVM.IVM_BalanceDueDate, SOS.SOS_InvoiceID, 
            SOS.SOS_SOLineNbr, SOS.SOS_RequiredDate, SOS.SOS_ShipDate, SOS.SOS_PackingSlipNbr
            FROM 
            (IVM LEFT JOIN IVI ON IVM.IVM_InvoiceID = IVI.IVI_InvoiceID) 
            LEFT JOIN (SOS RIGHT JOIN IVD ON (SOS.SOS_RequiredDate = IVD.IVD_RequiredDate) 
            AND (SOS.SOS_SOLineNbr = IVD.IVD_SOLineNbr) AND (SOS.SOS_InvoiceID = IVD.IVD_InvoiceID)) 
            ON (IVI.IVI_SOLineNbr = IVD.IVD_SOLineNbr) AND (IVI.IVI_InvoiceID = IVD.IVD_InvoiceID) 
            WHERE IVM.IVM_SalesOrderID <> 'Credit'  
            AND IVM.IVM_InvoiceDate BETWEEN :startdate AND :enddate
            AND  IVM.IVM_CustomerID = :customerid;
        ";
    }
    else
    {
        if ($strInvoice === 'all' )
        {
            $sql = "
                SELECT DISTINCTROW IVM.IVM_InvoiceID, IVM.IVM_CustomerPOID, 
                IVM.IVM_InvoiceDate, IVM.IVM_SalesOrderID, IVM.IVM_ShipDate, IVM.IVM_ShipToName, 
                IVM.IVM_ShipToAddress, IVM.IVM_ShipToAddr2, IVM.IVM_ShipToAddr3, IVM.IVM_ShipToCity, 
                IVM.IVM_ShipToState, IVM.IVM_ShipToPostalCode, IVM.IVM_ShipToCountry, IVM.IVM_CustomerID, 
                IVM.IVM_BillName, IVM.IVM_BillAddress, IVM.IVM_BillAddr2, IVM.IVM_BillAddr3, IVM.IVM_BillCity, 
                IVM.IVM_BillState, IVM.IVM_BillPostalCode, IVM.IVM_BillCountry, IVM.IVM_SalesOrderDate, 
                IVM.IVM_ShipMethod, IVM.IVM_TerritoryCodes, 
                IVM.IVM_CurrencyType,
                IVM.IVM_Region, IVM.IVM_NetInvoiceAmt, IVM.IVM_SlsTaxAmt, IVI.IVI_SlsTaxAmt, IVM.IVM_BalanceDueAmt, 
                IVM.IVM_SlsTaxDesc, IVM.IVM_SlsTaxPercent, IVI.IVI_SlsTaxPercent, 
                IVM.IVM_ARTermsDesc,
                IVI.IVI_SOLineNbr, IVI.IVI_CustPOLineNbr, IVI.IVI_ItemID, IVI.IVI_ItemName, 
                IVI.IVI_TaxableFlag, IVI.IVI_UnitMeasure, IVI.IVI_LineSpecialInst, IVD.IVD_RequiredDate, 
                IVD.IVD_ShipQty, IVD.IVD_UnitPrice, IVD.IVD_DiscPercent, 
                IVM.IVM_BalanceDueDate, SOS.SOS_InvoiceID, 
                SOS.SOS_SOLineNbr, SOS.SOS_RequiredDate, SOS.SOS_ShipDate, SOS.SOS_PackingSlipNbr
                FROM 
                (IVM LEFT JOIN IVI ON IVM.IVM_InvoiceID = IVI.IVI_InvoiceID) 
                LEFT JOIN (SOS RIGHT JOIN IVD ON (SOS.SOS_RequiredDate = IVD.IVD_RequiredDate) 
                AND (SOS.SOS_SOLineNbr = IVD.IVD_SOLineNbr) AND (SOS.SOS_InvoiceID = IVD.IVD_InvoiceID)) 
                ON (IVI.IVI_SOLineNbr = IVD.IVD_SOLineNbr) AND (IVI.IVI_InvoiceID = IVD.IVD_InvoiceID) 
                WHERE IVM.IVM_SalesOrderID <> 'Credit'  
                AND  IVM.IVM_CustomerID = :customerid; 
                ";                  
        }    
        else
        {
            $sql = "
                SELECT DISTINCTROW IVM.IVM_InvoiceID, IVM.IVM_CustomerPOID, 
                IVM.IVM_InvoiceDate, IVM.IVM_SalesOrderID, IVM.IVM_ShipDate, IVM.IVM_ShipToName, 
                IVM.IVM_ShipToAddress, IVM.IVM_ShipToAddr2, IVM.IVM_ShipToAddr3, IVM.IVM_ShipToCity, 
                IVM.IVM_ShipToState, IVM.IVM_ShipToPostalCode, IVM.IVM_ShipToCountry, IVM.IVM_CustomerID, 
                IVM.IVM_BillName, IVM.IVM_BillAddress, IVM.IVM_BillAddr2, IVM.IVM_BillAddr3, IVM.IVM_BillCity, 
                IVM.IVM_BillState, IVM.IVM_BillPostalCode, IVM.IVM_BillCountry, IVM.IVM_SalesOrderDate, 
                IVM.IVM_ShipMethod, IVM.IVM_TerritoryCodes, 
                IVM.IVM_CurrencyType,
                IVM.IVM_Region, IVM.IVM_NetInvoiceAmt, IVM.IVM_SlsTaxAmt, IVI.IVI_SlsTaxAmt, IVM.IVM_BalanceDueAmt, 
                IVM.IVM_SlsTaxDesc, IVM.IVM_SlsTaxPercent, IVI.IVI_SlsTaxPercent, 
                IVM.IVM_ARTermsDesc,
                IVI.IVI_SOLineNbr, IVI.IVI_CustPOLineNbr, IVI.IVI_ItemID, IVI.IVI_ItemName, 
                IVI.IVI_TaxableFlag, IVI.IVI_UnitMeasure, IVI.IVI_LineSpecialInst, IVD.IVD_RequiredDate, 
                IVD.IVD_ShipQty, IVD.IVD_UnitPrice, IVD.IVD_DiscPercent, 
                IVM.IVM_BalanceDueDate, SOS.SOS_InvoiceID, 
                SOS.SOS_SOLineNbr, SOS.SOS_RequiredDate, SOS.SOS_ShipDate, SOS.SOS_PackingSlipNbr
                FROM 
                (IVM LEFT JOIN IVI ON IVM.IVM_InvoiceID = IVI.IVI_InvoiceID) 
                LEFT JOIN (SOS RIGHT JOIN IVD ON (SOS.SOS_RequiredDate = IVD.IVD_RequiredDate) 
                AND (SOS.SOS_SOLineNbr = IVD.IVD_SOLineNbr) AND (SOS.SOS_InvoiceID = IVD.IVD_InvoiceID)) 
                ON (IVI.IVI_SOLineNbr = IVD.IVD_SOLineNbr) AND (IVI.IVI_InvoiceID = IVD.IVD_InvoiceID) 
                WHERE IVM.IVM_SalesOrderID <> 'Credit'  
                AND IVM.IVM_InvoiceID = :invoice;
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
            if ($strInvoice === 'all')
            {        
                $stmt->execute(['customerid'  => $strCustomerID]);  
            }       
            else
            {
                $stmt->execute(['invoice' => $strInvoice]);   
            }        
        }    

        $result = $stmt->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT);

        #any records?
        if (!empty($result)) 
        {
//funcWriteLog(CNST_STR_ERRORLOG, null, 'result found: ' . $result);    
/*
IVM.IVM_InvoiceID            0
IVM.IVM_CustomerPOID         1
IVM.IVM_InvoiceDate          2
IVM.IVM_SalesOrderID         3
IVM.IVM_ShipDate             4
IVM.IVM_ShipToName           5
IVM.IVM_ShipToAddress        6  
IVM.IVM_ShipToAddr2          7
IVM.IVM_ShipToAddr3          8
IVM.IVM_ShipToCity           9 
IVM.IVM_ShipToState          10
IVM.IVM_ShipToPostalCode     11 
IVM.IVM_ShipToCountry        12
IVM.IVM_CustomerID           13
IVM.IVM_BillName             14
IVM.IVM_BillAddress          15
IVM.IVM_BillAddr2            16
IVM.IVM_BillAddr3            17
IVM.IVM_BillCity             18
IVM.IVM_BillState            19
IVM.IVM_BillPostalCode       20
IVM.IVM_BillCountry          21
IVM.IVM_SalesOrderDate       22
IVM.IVM_ShipMethod           23
IVM.IVM_TerritoryCodes       24 
IVM.IVM_CurrencyType         25
IVM.IVM_Region               26
IVM.IVM_NetInvoiceAmt        27
IVM.IVM_SlsTaxAmt            28
IVI.IVI_SlsTaxAmt            29
IVM.IVM_BalanceDueAmt        30  
IVM.IVM_SlsTaxDesc           31
IVM.IVM_SlsTaxPercent        32
IVI.IVI_SlsTaxPercent        33
IVM.IVM_ARTermsDesc          34
IVI.IVI_SOLineNbr            35
IVI.IVI_CustPOLineNbr        36
IVI.IVI_ItemID               37
IVI.IVI_ItemName             38
IVI.IVI_TaxableFlag          39
IVI.IVI_UnitMeasure          40
IVI.IVI_LineSpecialInst      41
IVD.IVD_RequiredDate         42
IVD.IVD_ShipQty              43
IVD.IVD_UnitPrice            44
IVD.IVD_DiscPercent          45
IVM.IVM_BalanceDueDate       46 
SOS.SOS_InvoiceID            47
SOS.SOS_SOLineNbr            48
SOS.SOS_RequiredDate         49
SOS.SOS_ShipDate             50
SOS.SOS_PackingSlipNbr       51 
*/

            //create customer address/name string
            $strName = $result[14];
            $strName = $strName . (is_null($result[15]) ? '' : $result[15]. '<br>');
            $strName = $strName . (is_null($result[16]) ? '' : $result[16]. '<br>');
            $strName = $strName . (is_null($result[17]) ? '' : $result[17]. '<br>');
            $strName = $strName . (is_null($result[18]) ? '' : $result[18]. '<br>');
            $strName = $strName . (is_null($result[19]) ? '' : $result[19]. '<br>');
            $strName = $strName . (is_null($result[20]) ? '' : $result[20]. '<br>');
            $strName = $strName . (is_null($result[21]) ? '' : $result[21]. '<br>');

            //create shipto address/name string
            $strShipToName = $result[5];
            $strShipToName = $strShipToName . (is_null($result[6]) ? '' : $result[6]. '<br>');
            $strShipToName = $strShipToName . (is_null($result[7]) ? '' : $result[7]. '<br>');
            $strShipToName = $strShipToName . (is_null($result[8]) ? '' : $result[8]. '<br>');
            $strShipToName = $strShipToName . (is_null($result[9]) ? '' : $result[9]. '<br>');
            $strShipToName = $strShipToName . (is_null($result[10]) ? '' : $result[10]. '<br>');
            $strShipToName = $strShipToName . (is_null($result[11]) ? '' : $result[11]. '<br>');
            $strShipToName = $strShipToName . (is_null($result[12]) ? '' : $result[12]. '<br>');

funcWriteLog(CNST_STR_ERRORLOG, null, 'pre grid ' . $strHTML); 
funcWriteLog(CNST_STR_ERRORLOG, null, 'inv date ' . $result[2]); 
funcWriteLog(CNST_STR_ERRORLOG, null, 'ship date ' . $result[4]); 
            $strHTML = '<div class="clsGridReportHeader1">' .
                            '<div id="imgLogo"><img src="../images/companylogo.png" alt="Company Logo"></div>' .
                            '<div class="clsMainCompanyAddress">' . CNST_STR_INVOICEHEADER_REPORT . '</div>' .
                            '<div class="clsInvoiceHeaderDetails">
                                Invoice ID: ' . $result[0] . '<br>    
                                Sales Order ID: ' . $result[3] . '<br>    
                                Invoice Date: ' . funcFormatToUKDate($result[2]) . '<br>    
                                Ship Date: ' . funcFormatToUKDate($result[4]) . '<br>    
                                Customer PO ID: ' . $result[1] . '<br>    
                                Territory: ' . $result[24] . '<br>    
                            </div>
                            <div class="clsInvoiceReprint clsInvoiceHeaderDetails">
                                Invoice Reprint 
                            </div>   
                       </div>
                       <hr> 
                      
                        <div class="clsGridReportHeader2">
                                <div>
                                    <div class="clsInvoiceHeaderDetails">
                                        AR Terms:' . $result[34] . '<br>  
                                    </div>
                                    <div class="clsCustomerAddress">
                                        Bill To:<br>  
                                        ' . $strName. '<br>
                                    </div>  
                                    <div class="clsInvoiceHeaderDetails">
                                        Order Date: ' . funcFormatToUKDate($result[22]) . '<br>    
                                        Packing Slip Nbr: ' . $result[51] . '
                                    </div>    
                                </div> 
                                <div>
                                    <div class="clsInvoiceHeaderDetails">
                                        Due Date: ' . funcFormatToUKDate($result[46]) . '<br>    
                                    </div>
                                    <div class="clsShipToAddress">
                                    Ship To:<br>  
                                    ' . $strShipToName . '<br>  
                                    </div>
                                    <div class="clsInvoiceHeaderDetails">
                                        Ship Method: ' . $result[23] . '
                                        Currency Type: ' . $result[25] . '<br>  
                                    </div>    
                                </div>
                        </div>
                        <hr>
                ';
funcWriteLog(CNST_STR_ERRORLOG, null, 'grid ' . $strHTML); 
            // //create lines header
            $strHTML = $strHTML . '<div><table>' .
                       '<tr class="clsReportDetail">' .
                       '    <th>Line Nbr/PO Line Nbr</th>' . 
                       '    <th>Item ID</th>' . 
                       '    <th>Description</th>' .
                       '    <th>UOM</th>' .
                       '    <th>Shipped Qty</th>' .
                       '    <th>Unit Price</th>' .
                       '    <th>Disc %</th>' .
                       '    <th>VAT</th>' .
                       '    <th>Line Value</th>' .
                       '<tr>' .
                       '     <td colspan="100%" style="box-shadow: inset 0 -2px 0 white;">' .
                       '        &nbsp' .
                       '     </td>' .
                       '</tr>';

            //calulate qty * price minus disc and previous plus vat and vat on its own
            $fltVAT = $result[29];
            $fltDisc = $result[45];
            $fltUnitPrice = $result[44];           
            $fltTotal = $fltUnitPrice * $result[43];
            //minus discount if any
            $fltTotal = $fltTotal - ($fltUnitPrice * $fltDisc);
            //add vat if any
            $fltTotal = $fltTotal + $fltVAT;
            //reset invoice totals as new invoice
            $fltInvoiceTotalValue = 0;
            $fltInvoiceTotalVAT = 0;
            //add to invoice totals
            $fltInvoiceTotalValue = $fltInvoiceTotalValue + $fltTotal;
            $fltInvoiceTotalVAT = $fltInvoiceTotalVAT + $fltVAT;
            //add to grand totals
            $fltGrandTotalValue = $fltGrandTotalValue + $fltTotal;
            $fltGrandTotalVAT = $fltGrandTotalVAT + $fltVAT;

            //add first row 
            $strHTML = $strHTML . '<tr class="clsReportDetail">' .
                    '<td>' . $result[35] . '/' . $result[36] . '</td>' . 
                    '<td>' . $result[37] . 
                    '<td>' . $result[38] .
                    '<td>' . $result[40] .
                    '<td>' . $result[43] .
                    '<td>' . funcFormatCurrency($result[44], '£') . '</td>' .
                    '<td>' . $result[45] . '%' . 
                    '<td>' . funcFormatCurrency($result[29], '£') . '</td>' .
                    '<td>' . funcFormatCurrency($fltTotal, '£') . '</td>' .
                    '</tr>';
            $strHTML = $strHTML . '</div></table>';            
             //set current invoice ID             
            $strInvoiceID = $result[0];   

funcWriteLog(CNST_STR_ERRORLOG, null, 'vat ' . $fltVAT);  
funcWriteLog(CNST_STR_ERRORLOG, null, 'disc ' . $fltDisc);  
funcWriteLog(CNST_STR_ERRORLOG, null, 'price ' . $fltUnitPrice);  
funcWriteLog(CNST_STR_ERRORLOG, null, 'price * qty ' . $fltUnitPrice * $result[43]);   
funcWriteLog(CNST_STR_ERRORLOG, null, 'price minus disc ' . ($fltUnitPrice * $result[43]) - ($fltUnitPrice * $fltDisc));    
funcWriteLog(CNST_STR_ERRORLOG, null, 'total + vat ' . ($fltUnitPrice * $result[43]) - ($fltUnitPrice * $fltDisc) + $fltVAT);   


//funcWriteLog(CNST_STR_ERRORLOG, null, 'sql ' . $sql);    
//funcWriteLog(CNST_STR_ERRORLOG, null, 'HTML pre loop ' . $strHTML);    
            //loop through records and create HTML page
            while ($row = $stmt->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT))
            {
                //just incase for bizarre reason there is no invoice ID!
                if (!is_null($row[0]) ) 
                {
funcWriteLog(CNST_STR_ERRORLOG, null, '$strInvoiceID ' . $strInvoiceID . ' ' . $row[0]);                      
                   if ($strInvoiceID !== $row[0])
                   {
                        //add invoice totals as footer
                        $strHTML = $strHTML . '</div></table>
                               <div class="clsReportLocalTotals">
                                   <hr>
                                    Total Vat For Invoice  : ' . funcFormatCurrency($fltInvoiceTotalVAT , '£') . '<br>
                                    Total Value Of Invoice : ' . funcFormatCurrency($fltInvoiceTotalValue , '£') . '
                                   <hr>
                               </div>
                               <div><br><br></div>
                               ';

                        //create customer address/name string
                        $strName = $row[14];
                        $strName = $strName . (is_null($row[15]) ? '' : $row[15]. '<br>');
                        $strName = $strName . (is_null($row[16]) ? '' : $row[16]. '<br>');
                        $strName = $strName . (is_null($row[17]) ? '' : $row[17]. '<br>');
                        $strName = $strName . (is_null($row[18]) ? '' : $row[18]. '<br>');
                        $strName = $strName . (is_null($row[19]) ? '' : $row[19]. '<br>');
                        $strName = $strName . (is_null($row[20]) ? '' : $row[20]. '<br>');
                        $strName = $strName . (is_null($row[21]) ? '' : $row[21]. '<br>');

                        //create shipto address/name string
                        $strShipToName = $row[5];
                        $strShipToName = $strShipToName . (is_null($row[6]) ? '' : $row[6]. '<br>');
                        $strShipToName = $strShipToName . (is_null($row[7]) ? '' : $row[7]. '<br>');
                        $strShipToName = $strShipToName . (is_null($row[8]) ? '' : $row[8]. '<br>');
                        $strShipToName = $strShipToName . (is_null($row[9]) ? '' : $row[9]. '<br>');
                        $strShipToName = $strShipToName . (is_null($row[10]) ? '' : $row[10]. '<br>');
                        $strShipToName = $strShipToName . (is_null($row[11]) ? '' : $row[11]. '<br>');
                        $strShipToName = $strShipToName . (is_null($row[12]) ? '' : $row[12]. '<br>');
funcWriteLog(CNST_STR_ERRORLOG, null, 'html before date: ' . $strHTML);   
                        //create new report header and first row
                        $strHTML = $strHTML . 
                                '<div class="clsGridReportHeader1">' .
                                        '<div id="imgLogo"><img src="../images/companylogo.png" alt="Company Logo"></div>' .
                                        '<div class="clsMainCompanyAddress">' . CNST_STR_INVOICEHEADER_REPORT . '</div>' .
                                        '<div class="clsInvoiceHeaderDetails">
                                            Invoice ID: ' . $row[0] . '<br>    
                                            Sales Order ID: ' . $row[3] . '<br>    
                                            Invoice Date: ' . funcFormatToUKDate($row[2]) . '<br>    
                                            Ship Date: ' . funcFormatToUKDate($row[4]) . '<br>    
                                            Customer PO ID: ' . $row[1] . '<br>    
                                            Territory: ' . $row[24] . '<br>    
                                        </div>
                                        <div class="clsInvoiceReprint clsInvoiceHeaderDetails">
                                            Invoice Reprint 
                                        </div>   
                                </div>
                                <hr> 
                                
                                <div class="clsGridReportHeader2">
                                        <div>
                                           <div class="clsInvoiceHeaderDetails">
                                               AR Terms:' . $row[34] . '<br>  
                                            </div>
                                            <div class="clsCustomerAddress">
                                            Bill To:<br>  
                                            ' . $strName. '<br>
                                            </div>  
                                            <div class="clsInvoiceHeaderDetails">
                                                Order Date: ' . funcFormatToUKDate($row[22]) . '<br>    
                                                Packing Slip Nbr: ' . $row[51] . '
                                            </div>    
                                        </div> 
                                        <div>
                                            <div class="clsInvoiceHeaderDetails">
                                               Due Date: ' . funcFormatToUKDate($row[46]) . '<br>    
                                            </div>
                                            <div class="clsShipToAddress">
                                            Ship To:<br>  
                                            ' . $strShipToName . '<br>  
                                            </div>
                                            <div class="clsInvoiceHeaderDetails">
                                                Ship Method: ' . $row[23] . '
                                                Currency Type: ' . $row[25] . '<br>  
                                            </div>    
                                        </div>
                                </div>
                                <hr>
                                ';

                        //create lines header
                        $strHTML = $strHTML . '<div><table>' .
                                '<tr class="clsReportDetail">' .
                                '    <th>Line Nbr/PO Line Nbr</th>' . 
                                '    <th>Item ID</th>' . 
                                '    <th>Description</th>' .
                                '    <th>UOM</th>' .
                                '    <th>Shipped Qty</th>' .
                                '    <th>Unit Price</th>' .
                                '    <th>Disc %</th>' .
                                '    <th>VAT</th>' .
                                '    <th>Line Value</th>' .
                                '<tr>' .
                                '     <td colspan="100%" style="box-shadow: inset 0 -2px 0 white;">' .
                                '        &nbsp' .
                                '     </td>' .
                                '</tr>';

                        //calulate qty * price minus disc and previous plus vat and vat on its own
                        $fltVAT = $row[29];
                        $fltDisc = $row[45];
                        $fltUnitPrice = $row[44];           
                        $fltTotal = $fltUnitPrice * $row[43];
                        //minus discount if any
                        $fltTotal = $fltTotal - ($fltUnitPrice * $fltDisc);
                        //add vat if any
                        $fltTotal = $fltTotal + $fltVAT;
                        //reset invoice totals as new invoice
                        $fltInvoiceTotalValue = 0;
                        $fltInvoiceTotalVAT = 0;
                        //add to invoice totals
                        $fltInvoiceTotalValue = $fltInvoiceTotalValue + $fltTotal;
                        $fltInvoiceTotalVAT = $fltInvoiceTotalVAT + $fltVAT;
                        //add to grand totals                       
                        //add to grand totals
                        $fltGrandTotalValue = $fltGrandTotalValue + $fltTotal;
                        $fltGrandTotalVAT = $fltGrandTotalVAT + $fltVAT;

                        //add first row 
                        $strHTML = $strHTML . '<tr class="clsReportDetail">' .
                                '<td>' . $row[35] . '/' . $row[36] . '</td>' . 
                                '<td>' . $row[37] . 
                                '<td>' . $row[38] .
                                '<td>' . $row[40] .
                                '<td>' . $row[43] .
                                '<td>' . funcFormatCurrency($row[44], '£') . '</td>' .
                                '<td>' . $row[45] . '%' . 
                                '<td>' . funcFormatCurrency($row[29], '£') . '</td>' .
                                '<td>' . funcFormatCurrency($fltTotal, '£') . '</td>' .
                                '</tr>';
             
                        //set current invoice ID             
                        $strInvoiceID = $row[0];                                                        
                   }  
                   else
                   {
                        //calulate qty * price minus disc and previous plus vat and vat on its own
                        $fltVAT = $row[29];
                        $fltDisc = $row[45];
                        $fltUnitPrice = $row[44];           
                        $fltTotal = $fltUnitPrice * $row[43];
                        //minus discount if any
                        $fltTotal = $fltTotal - ($fltUnitPrice * $fltDisc);
                        //add vat if any
                        $fltTotal = $fltTotal + $fltVAT;
                        //add to invoice totals
                        $fltInvoiceTotalValue = $fltInvoiceTotalValue + $fltTotal;
                        $fltInvoiceTotalVAT = $fltInvoiceTotalVAT + $fltVAT;                        
                        //add to grand totals
                        $fltGrandTotalValue = $fltGrandTotalValue + $fltTotal;
                        $fltGrandTotalVAT = $fltGrandTotalVAT + $fltVAT;

                        //add lines to existing invoice
                        $strHTML = $strHTML . '<tr class="clsReportDetail">' .
                                '<td>' . $row[35] . '/' . $row[36] . '</td>' . 
                                '<td>' . $row[37] . 
                                '<td>' . $row[38] .
                                '<td>' . $row[40] .
                                '<td>' . $row[43] .
                                '<td>' . funcFormatCurrency($row[44], '£') . '</td>' .
                                '<td>' . $row[45] . '%' . 
                                '<td>' . funcFormatCurrency($row[29], '£') . '</td>' .
                                '<td>' . funcFormatCurrency($fltTotal, '£') . '</td>' .
                                '</tr>';     
                   } 
                }               
            }
 
            //add final invoice totals as footer
            $strHTML = $strHTML . '</div></table><hr>
                    <div class="clsReportLocalTotals">
                        Total Vat For Invoice  : ' . funcFormatCurrency($fltInvoiceTotalVAT , '£') . '<br>
                        Total Value Of Invoice :  ' . funcFormatCurrency($fltInvoiceTotalValue , '£') . '
                    </div>
                    <div><br><br>/div>
                    <hr>
                    ';
            //show grand totals
            $strHTML = $strHTML . '</div></table>
                    <br><br><br><br>
                    <hr>
                    <div class="clsReportGrandTotals">
                        Total Vat For All Invoices  : ' . funcFormatCurrency($fltGrandTotalVAT , '£') . '<br>
                        Total Value Of All Invoices : ' . funcFormatCurrency($fltGrandTotalValue , '£') . '
                    </div>
                    <hr>
                 </body>
            ';
funcWriteLog(CNST_STR_ERRORLOG, null, 'HTML post loop ' . $strHTML);                
             return false;
       }
       else {
           return false;
       }
      } catch (PDOException $e) {
            funcWriteLog(CNST_STR_ERRORLOG, $e, 'Invoice Reprint Error');
            return true;
    }
  }
};



function funcGetInvoicesList()
{
/*
    Created 09/11/2026 By Roger Williams

    gets invoices for customer returns as select list items HTML


*/
    global $strHTML;
    global $pdo;
    global $strCustomerID;

    $sql = "
        SELECT DISTINCT ARM.ARM_InvoiceID FROM ARM
        INNER JOIN IVM ON (ARM.ARM_InvoiceID = IVM.IVM_InvoiceID)
        WHERE ARM.ARM_CustomerID = :customerid AND IVM.IVM_InvoiceAmt > 0 
        ORDER BY ARM.ARM_InvoiceID DESC;
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
funcWriteLog(CNST_STR_ERRORLOG, null, 'HTML: ' . $strHTML);
             return false;
        }
        else
        {
            return false;
        }         
    } 
    catch (PDOException $e) 
    {
            funcWriteLog(CNST_STR_ERRORLOG, $e, 'invoices list Error');
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
$strInvoice = trim($data['invoice'] ?? '');

funcWriteLog(CNST_STR_ERRORLOG, null, 'invoice: ' . $data['invoice']);  
$blnOpenCreditMemo = $data['creditmemoopen'];

if ($blnOpenCreditMemo === "true")
    $blnOpenCreditMemo = true;
else
    $blnOpenCreditMemo = false;

if ($strReportType === '') {
    http_response_code(400);

    echo json_encode([
        'status' => false,
        'error' => 'Report Type Is Required'
    ]);

    exit;
}

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

   case 'invoiceslist':
        if (funcGetInvoicesList()) $intErrorCount++;
        break;        
        
    case 'invoices':
        if (funcGetInvoices()) $intErrorCount++;
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
