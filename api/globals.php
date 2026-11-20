<?php

/*
  
  Created 30/10/2026 By Roger Williams

  global vars for log writer

*/

const CNST_STR_ERRORLOG = '/var/www/WebApp/public/phperrors.log';


function funcFormatCurrency($fltAmount, string $strCurrencySymbol)
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

function funcFormatToUKDate($strDate)
{
/*
    Created 17/11/2026 By Roger Williams

    
    returns string with Uk date format first strips any time

*/
  
   if ($strDate === '')
   {
      throw new Exception('Empty date string supplied');
   }

   try
    {
        $strSpaces = strpos($strDate, ' ');

        if ( $strSpaces !== false)
        {    
            //strip time
            $strDate = substr($strDate,0, strpos($strDate, ' '));
            $strDate = trim($strDate);
        }

        $date = new DateTime($strDate);
    }
    catch (Throwable $ex)
    {
       funcWriteLog(CNST_STR_ERRORLOG, $ex, 'Date Conversion Error ' . $strDate);  
    }
    $date = $date->format('d/m/Y');
    return $date;
}

?>