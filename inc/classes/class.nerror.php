<?php
/*
  Project:  NiFrame
  
  Author:   Nathan Poole - github/npocodes
           
  Date:     Jan 2014
  
  Updated:  1-30-2019
     
  File:     This file is an error interface file use this class as a parent 
            class to provide your class with error handling methods
*/

class nerror {

  //Error dump string
  protected $error;
  
  ///////////////
  //CONSTRUCTOR//
  ///////////////
  function __construct() {
    $this->error = '';
  }
  
  
  ////////////////////////
  //# Get Error Method #//
  ////////////////////////
  /*
    RETURNS the error dump string
  */
  final public function Error(){ RETURN $this->error; }
  
  
  ////////////////////////
  //# Log Error Method #//
  ////////////////////////
  /*
    This function writes an error message to the error log file named 'error_log' with no extension given, this is very
    common in many hosted servers so in most cases we are appending the error message to the same log for the site
    that the hosted server uses, keeping all your error reports in one log file.
  */
  final protected function LogError($msg, $fileName = 'error_log')
  {
    switch(E_REPORT)
    {
      case true:
        //Display the error in the browser
        echo($msg.PHP_EOL);
      
      //Fall through intended
      default:
        //Append the error message to the system error log
        if(empty($msg)){ RETURN false; }
        $errFile = fopen($fileName, 'a') or die("Failed to open ".$fileName);
        $tMsg = $msg.' -- '.date("H:i:s").' -- '. date("Y-m-d");
        fwrite($errFile, $tMsg.PHP_EOL);
        fclose($errFile);
      break;
    }//End Switch
    RETURN true;
  }
  //End Log Error Method
}
?>