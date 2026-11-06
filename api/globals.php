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

?>