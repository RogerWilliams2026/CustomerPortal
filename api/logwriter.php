<?php

/*
  
  Created 30/10/2026 By Roger Williams

  global error/general log log writer

  replaces old method:
 
  error_log('Last YTD global vars: ' . $intOffset . ' ' . $strSummaryHTML . PHP_EOL,3,'/var/www/WebApp/public/phperrors.log');   

*/

function funcDeleteLog(string $strLogFile)
{
  try
  {  
     //delete log file  
     unlink($strLogFile);
  }
 catch (PDOException $e) {
    exit;
  }
};


function funcWriteLog(string $strLogFile, $exception, string $strErrorMessage)
{

try
{   
  //if $exception is a PDOException class use its properties for finer detail  
  if (!is_null($exception))
  {
      error_log(print_r($strErrorMessage, true) . PHP_EOL,3,$strLogFile); 
      error_log(print_r($exception->getFile(), true) . PHP_EOL,3,$strLogFile);      
      error_log(print_r($exception->getCode(), true) . PHP_EOL,3,$strLogFile);   
      error_log(print_r($exception->getLine(), true) . PHP_EOL,3,$strLogFile);         
  }
  else
    {
        //no PDOException? print passed error message
        error_log(print_r($strErrorMessage, true) . PHP_EOL,3,$strLogFile);      
    }
} catch (PDOException $e) {
    exit;
  }
};


?>