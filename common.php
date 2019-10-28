<?php
/*
  Purpose:  Common Driver File - NiFrame
  
  FILE:     The Common driver file is the main driver from which
            all other driver files are able to plug into the system.
            It should be included as the very first step of any other
            driver file. This file is what pulls the system together
            and allows new drivers to talk with the system seamlessly,
            essentially serving as the frameworks interface for drivers.
            
  Author:   Nathan Poole - github/npocodes
  Date:     July 2014
  Updated:  1-30-2019
*/
//Start/Resume the PHP session
session_start();

//%^^ Start Page load timer
$loadTime = microtime();
$loadTime = explode(" ", $loadTime);
$loadTime = $loadTime[1] + $loadTime[0];
$_SESSION['Ni_start'] = $loadTime;
//Load Timer finishes inside template class
//Just before display/return of HTML

//Get some basic environment information
$HOST_NAME = $_SERVER['HTTP_HOST']; //Get the server name

//Isolate the domain name from sub domains
$Patt = '/^[wW]{3}[\.]{1}/';//matches www. or WWW. or wWw. etc..
$DOMAIN_NAME = preg_replace($Patt, "", $HOST_NAME);

//Start Building SCRIPTPATH
$SCRIPT_PATH = $HOST_NAME;

//Loop Directories and Fin Building SCRIPTPATH
//(Skip last entry, it is the file name)
$dirList = explode('/', $_SERVER['SCRIPT_NAME']);//Determine DIR Hierarchy
$dirCount = count($dirList); //Count the dirList
for($i = 0; $i < $dirCount-1; $i++){ if(!(empty($dirList[$i]))){ $SCRIPT_PATH .= "/".$dirList[$i]; } }

//Get Current File name
$CURR_FILE = $dirList[$dirCount-1];

//Get Users IP
$CURR_IP = gethostbyname($_SERVER['REMOTE_ADDR']);

//Get Extra User Information
$CURR_PC = gethostbyaddr($_SERVER['REMOTE_ADDR']);

//Verify System is Installed
if(!(file_exists('inc/const.php')) || !(file_exists('inc/config.ini')))
{
  header("Location: install/index.php");
  exit();
}
else
{
  //Get the Constants file
  require_once("inc/const.php");

  //Get the Configuration data, in sections
  $CONFIG = parse_ini_file(CONFIG_PATH, true);

  //Verify the configuration data
  if($CONFIG !== false)
  {
    //Check for corrupted values
    $x = 0; //Section counter
    foreach($CONFIG as $section => $secArray)
    { 
      $y = 0;//Variable counter
      foreach($secArray as $varName => $varValue)
      { 
        if(empty($varName) || preg_match('/^[0-9]+.*$/', $varName))
        {	
          //Bad Key Found!
          LogError("!!! CORRUPTED CONFIGURATION KEY - Section: $x, Variable: $y !!!");
          exit();
        }
        else if(!(isset($varValue)))
        {
          //Bad Value Found!
          LogError("!!! CORRUPTED CONFIGURATION VALUE - Section: $x, Variable: $y !!!");
          exit();	
        }
        else{/*NoCorruptedValues*/}
        
        $y++; //Increment Var counter
        
        //Add cleaned sections to the global, the result is a non-sectioned array
        if($section !== 'Database' || $varName == 'DB_Prefix'){ $cleanConfig[$varName] = $varValue; }
      }
      $x++; //Increment Section counter
    }
    //Set the configuration global 
    //equal to the cleaned version
    $CONFIG = $cleanConfig;
  }
  else
  {
    //Failed to parse the configuration file
    LogError("Configuration parse error detected"); 
    exit();
  }

	//Server Time References
	date_default_timezone_set(SERVER_TIMEZONE);//Set the timezone using const data.
	
	//Calculate UTC offset for set timezone
	$utcZ = new DateTimeZone('UTC');
	$utc = new DateTime('now', $utcZ);
	$offset_sec = timezone_offset_get(timezone_open(SERVER_TIMEZONE), $utc);
	$offset_hr = $offset_sec / (60 * 60);
	$offset_hr = (string)$offset_hr;//String conversion
	$CURR_TZ_OFFSET = (strpos($offset_hr, '-') === FALSE) ? '+'.$offset_hr : $offset_hr;	
	$CURR_TZONE = date_default_timezone_get();//Default Server TimeZone
	$dt = new DateTime();
	$CURR_TZ_ABBR = $dt->format('T');
	
	//Time / Date References
	$CURR_TIME = date("H:i:s"); //The Time
	$CURR_DATE = date("Y-m-d"); //The Date
	$FiveMinAgoSecs = time() - (5*60); //5 mins ago (in secs)
	$CURR_5AGO = date("H:i:s", $FiveMinAgoSecs); //The Time (5 min ago)
	//(Time 5 min ago can be used for activity detection)
	
  //Import our common class files
  require_once('inc/classes/class.nerror.php');
  require_once('inc/classes/class.template.php');
  require_once('inc/classes/class.dbAccess.php');
  require_once('inc/classes/class.attr.php');
  require_once('inc/classes/class.amenity.php');
	require_once('inc/classes/class.room.php');
	require_once('inc/classes/class.building.php');
	require_once('inc/classes/class.address.php');
	require_once('inc/classes/class.location.php');
	
  //Make gathering input easier by merging together POST & GET globals
  //$_REQUEST global includes cookies, we want to ignore them.
  //(POST has priority over GET)
  $_INPUT = array_merge($_GET, $_POST);

  //Finally we will declare/reset the template globals
  $T_FILE = array();
  $T_VAR = array();
  $T_COND = array();
  //$T_COND[] = $value, when setting conditions..!!Important
  //DON'T FORGET THE "[]"!!!!, see example below...
  //DEV-NOTE: rework condition usage to avoid potential cond wipe-outs
  
  //Set first condition to remove the menu template HTML
  //from the menu content file. This menu template HTML
  //is used only by the mod install feature to add new menu items
  //no matter what the HTML is like. See mod.php?install, menu section
  $T_COND[] = "LINK_TPL";
	
	//Check for ReCaptcha v2 enabled/disabled
	if(RECAPTCHA)
	{
		$T_VAR['RECAPTCHA_PUBLIC_KEY'] = $CONFIG['PublicKey'];
	}else{ $T_COND[] = 'RECAPTCHA'; }
	
	
  //Initialize some template variables
  //that are the most common
  $T_VAR['HOST_NAME'] = $HOST_NAME;
  $T_VAR['CURR_DATE'] = $CURR_DATE;
  $T_VAR['CURR_TIME'] = $CURR_TIME;
	$T_VAR['CURR_TZONE'] = $CURR_TZONE;
	$T_VAR['CURR_TZ_OFFSET'] = $CURR_TZ_OFFSET;
	$T_VAR['CURR_TZ_ABBR'] = $CURR_TZ_ABBR;
  $T_VAR['STYLE_PATH'] = 'style/'.STYLE;
  $T_VAR['SITE_NAME'] = $CONFIG['SiteName'];
  $T_VAR['SCRIPT_PATH'] = $SCRIPT_PATH;
	
  //////////////////////////////////
  //### BEGIN COMMON FUNCTIONS ###//
  //////////////////////////////////
  /* 
    [Build Template Function] 
    This function will create a template object and pass along the parameters
    needed to create and compile the HTML template. Each driver file may
    use this function for its HTML display needs.
  */
  function BuildTemplate($fileList, $varList, $conditions, $headless = false, $path = null, $ret = false)
  {	
    //Create New Template
    $tpl = new template(STYLE);

    //Check for HTML path modification
    if($path != null)
    {
      $tmp = explode(',', $path);
      if(!(isset($tmp[1]))){ $tmp[1] = false; }
      $tpl->SetPath($tmp[0], $tmp[1]);
    }

    //Set the File list
    $FileSet = $tpl->SetFiles($fileList, $headless);
    if($FileSet)
    {	
      //Set template Variables
      $VarsSet = $tpl->SetVars($varList);
      if($VarsSet)
      {
        //Set conditions
        $Conditioned = $tpl->SetConditions($conditions);
        if($Conditioned)
        {
          //Compile the template
          $Compiled = $tpl->Compile($ret);
          if(!($Compiled === false))
          {
            RETURN $Compiled; //This will be; true || the compiled HTML
          }
          else
          {
            $errMsg = "Unable to compile template display:".PHP_EOL;
            $errMsg .= $tpl->Error();          
          }
        }
        else
        {
          $errMsg = "Unable to set template conditions:".PHP_EOL;
          $errMsg .= $tpl->Error();
        }
      }
      else
      {
        $errMsg = "Unable to set template variables:".PHP_EOL;
        $errMsg = $tpl->Error();
      }
    }
    else
    {
      $errMsg = "Unable to set template files:".PHP_EOL;
      $errMsg = $tpl->Error();
    }
    
    //Log any errors
    LogError($errMsg);
  }

  /* 
    [Log Error Function]
    This function writes an error message to the error log file named 'error_log' with no extension given, this is very
    common in many hosted servers so in most cases we are appending the error message to the same log for the site
    that the hosted server uses, keeping all your error reports in one log file.
  */
  function LogError($msg, $fileName = 'error_log')
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

  /* 
    [Expel Function]
    This function will first determine if the path given is
    a directory, file, or symbolic link. Next if it is a file
    or a symbolic link then it is deleted, else if it is a dir
    then the dir is scanned and Expel() is run on each item,
    until all is removed and then the dir itself is removed.
  */
  function Expel($path = null)
  {
    if($path != null)
    {
      //Check if path is dir or file or symbolic link
      if(is_dir($path) && !(is_link($path)))
      {
        //Is Directory, scan it for files
        $dirList = scandir($path);
        if($dirList !== false)
        {
          foreach($dirList as $dirElement)
          {
            if($dirElement != '.' && $dirElement != '..')
            {
              Expel($path.'/'.$dirElement);
            }
          }
          //Finally delete this Dir
          rmdir($path);
        }
      }else if(!(is_link($path))){ unlink($path); }
      else{ if(!(unlink($path))){ rmdir($path); } }
    }
  }

  /* 
    [Array Split Function]
    Given an Array this function will return A multi-dimensional array with two elements.
    The first element '0' will contain an Array of the Keys of the given array.
    The second element '1' will contain an Array of the Values of the given array.
    Keys and Values will always have the same index #.
    Ex: 
        $MyArray = array("Key1" => "Value1", "Key2" => "Value2");
        $DualArray = array_split($MyArray);
        $KeyList    = $DualArray[0];   $KeyList[0] == "Key1";
        $ValueList  = $DualArray[1]; $ValueList[0] == "Value1";
  */
  function array_split($data)
  {
    $x = 0;//Counter to ensure accuracy
    $retArray[0] = array();//Array of Keys
    $retArray[1] = array();//Array of Values

    foreach($data as $key => $value)
    {
      $retArray[0][$x] = $key;
      $retArray[1][$x] = $value;
      $x++;
    }

    RETURN $retArray;
  }
  
  //Run the user permission check
  require_once('inc/uCheck.php');
	
  //User Common Marker
}
?>