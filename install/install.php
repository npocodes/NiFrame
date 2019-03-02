<?php
/*
Project:  NiFrame (NiFramework)

Author:   Nathan Poole - github/npocodes
   
Date:     April 2015

Updated:  1-30-2019

File:     This file processes the data gathered from  the user by the wizard. If the user has selected
          an Automatic installation than only Database and Common information was obtained from the user.
          All other values will be set to default values.
*/
session_start();

////////////////////
//%%^^ STEP 1 ^^%%//
////////////////////
//% Get some basic information

//Get the server name
$HOSTNAME = $_SERVER['HTTP_HOST'];

//Determine DIR Hierarchy
$DIRLIST = explode('/', $_SERVER['SCRIPT_NAME']);

//Start Building SCRIPTPATH
$SCRIPTPATH = $HOSTNAME;

//Loop Directories and
//Fin Building SCRIPTPATH
//(Skipping last two ie it skips [/install/install.php])
$DIRCOUNT = count($DIRLIST);
for($i = 0; $i < $DIRCOUNT-2; $i++)
{
  //Verify not blank entry
  if(!(empty($DIRLIST[$i])))
  {
    $SCRIPTPATH .= "/".$DIRLIST[$i];
  }
}

//Verify the index file and install check has been done
if(isset($_SESSION['install']) && $_SESSION['install'] === true)
{
  //Begin an Output Buffer; we'll gather it and store it into
  //an installation log file rather than printing it to the screen.
  ob_start();

  ////////////////////
  //%%^^ STEP 2 ^^%%//
  ////////////////////
  //% Connect to the database
  echo("Establishing Connection to the database...<br>");
  $Linked = mysqli_connect($_POST['DBserv'], $_POST['DBuser'], $_POST['DBpass']);
  if($Linked)
  {
    echo("<span style='color:green;'>Successfully Established Database connection...</span><br><br>");
  }
  else
  {
    echo("<span style='color:red;'>Error unable to connect to database:</span> <span style='color:blue'>".mysqli_error($Linked)."</span><br>");
    echo("Exiting...");
    exit();
  }

  //Select the Database to use
  echo("Attempting to select your database...<br>");
  $Selected = mysqli_select_db($Linked, $_POST['DBname']);
  if($Selected)
  {
    echo("<span style='color:green;'>Successfully selected the database...</span><br><br>");
  }
  else
  {
    echo("<span style='color:red;'>Error unable to select the database:</span> <span style='color:blue'>".mysqli_error($Linked)."</span><br>");
    echo("Attempting to create the database...<br>");
    $NEW_DB = mysqli_query($Linked, "CREATE DATABASE ".$_POST['DBname']);
    if($NEW_DB !== false)
    {
      echo("<span style='color:green;'>Successfully created the database...</span><br><br>");
    }
    else
    {
      echo("<span style='color:red;'>Error unable to create the database:</span> <span style='color:blue'>".mysqli_error($Linked)."</span><br>");
      echo("Exiting...<br><br>");
      echo("Please take note that if you are using a hosted server that uses a c-panel, you must create the db through your control panel.<br>Then enter the name of the database you created during installation.");
      exit();
    }
  }

  ////////////////////
  //%%^^ STEP 3 ^^%%//
  ////////////////////
  //% Build the queries to use for the CMS default database
  echo("Attempting to build database tables...<br>");
  /*
  This method works nicely to obtain the SQL Code
  from a slightly modded dump file but it wont allow
  us to add table prefix to the table names this way

  $SQLFILE = file("cortex_vanilla.sql");
  */
  //we'll set it up manually for now..
  //later we will actually read the dump files
  //and automate this process better.

  //Hash and Encrypt the root users password
  //DEPRECATED~!
  //$hashPass = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($_POST['cmnEIVS']), hash('whirlpool', //$_POST['rootPass']), MCRYPT_MODE_CBC, md5(md5($_POST['cmnEIVS']))));
  
  //These values must get saved to decrypt
  $ekey = $_POST['cmnEIVS'];
  $cipher = 'aes-256-cbc';
  $eivs = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));
  //EIVS must be saved in hex mode [bin2hex/hex2bin]
  
  //Hash and Encrypt the root users password
  $hashPass = base64_encode(openssl_encrypt(hash('whirlpool', $_POST['rootPass']), $cipher, crypt($ekey, 'ni'), $options=0, $eivs));
  
  //Manually set up queries... Table/Column Names can be changed later manually
  //They must also be changed in the configuration file.
  $SQLFILE = array(		
  //Create the User table
  "DROP TABLE IF EXISTS `".$_POST['DBprefix']."user`;",
  "CREATE TABLE IF NOT EXISTS `".$_POST['DBprefix']."user` (`user_ID` bigint(20) NOT NULL AUTO_INCREMENT, `userType_ID` bigint(20) NOT NULL DEFAULT '3', `userStatus_ID` bigint(20) NOT NULL DEFAULT '0', `userFirstName` varchar(64) NOT NULL DEFAULT 'unknown', `userMidName` varchar(64) NOT NULL DEFAULT 'unknown', `userLastName` varchar(64) NOT NULL DEFAULT 'unknown', `userNickName` varchar(32) NOT NULL DEFAULT 'unknown', `userEmail` varchar(64) NOT NULL DEFAULT 'unknown', `userPhone` varchar(16) NOT NULL DEFAULT '(555)-555-5555', `carrier_ID` bigint(20) NOT NULL DEFAULT '0', `userTZ` varchar(64) NOT NULL DEFAULT 'UTC', `userPass` varchar(256) NOT NULL DEFAULT 'unknown', `userCode` varchar(256) NOT NULL DEFAULT 'unknown', PRIMARY KEY (`user_ID`)) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;",
  "INSERT INTO `".$_POST['DBprefix']."user` (`userType_ID`, `userStatus_ID`, `userFirstName`, `userMidName`, `userLastName`, `userNickName`, `userEmail`, `userPhone`, `carrier_ID`, `userTZ`, `userPass`, `userCode`) VALUES (1, 1, '".$_POST['firstName']."', '".$_POST['midName']."', '".$_POST['lastName']."', '".$_POST['nickName']."', '".$_POST['rootEmail']."', '(555)-555-5555', 0, 'UTC', '".$hashPass."', 'junk');",
  //^^ Update Insert statement with form data!!! before using this code!

  //Create the User Type (Permission) table
  "DROP TABLE IF EXISTS `".$_POST['DBprefix']."userType`;",
  "CREATE TABLE IF NOT EXISTS `".$_POST['DBprefix']."userType` (`userType_ID` bigint(20) NOT NULL AUTO_INCREMENT, `userTypeName` varchar(32) NOT NULL DEFAULT 'NoTitle' COMMENT 'Name of the User Type/Group', `acp` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'NiFrame Admin Permission', PRIMARY KEY (`userType_ID`)) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='User Types/Groups and Permissions' AUTO_INCREMENT=1 ;",
  "INSERT INTO `".$_POST['DBprefix']."userType` (`userTypeName`, `acp`) VALUES ('Administrator', 1), ('Staff', 0), ('User', 0);",

  //Create the User Status table
  "DROP TABLE IF EXISTS `".$_POST['DBprefix']."userStatus`;",
  "CREATE TABLE IF NOT EXISTS `".$_POST['DBprefix']."userStatus` (`userStatus_ID` bigint(20) NOT NULL AUTO_INCREMENT, `userStatusName` varchar(32) NOT NULL DEFAULT 'NoTitle' COMMENT 'Name of the User Status', PRIMARY KEY (`userStatus_ID`)) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='User Statuses' AUTO_INCREMENT=1 ;",
  "INSERT INTO `".$_POST['DBprefix']."userStatus` (`userStatusName`) VALUES ('Active'), ('Banned');", //Status '0' is in-active...

  //Create the Phone Carrier table, not implemented yet...
  "DROP TABLE IF EXISTS `".$_POST['DBprefix']."phoneCarrier`;",
  "CREATE TABLE IF NOT EXISTS `".$_POST['DBprefix']."phoneCarrier` (`carrier_ID` bigint(20) NOT NULL AUTO_INCREMENT, `carrierName` varchar(32) NOT NULL DEFAULT 'NoTitle' COMMENT 'Name of the Phone Carrier company', `carrierSMS` varchar(64) NOT NULL DEFAULT 'unknown' COMMENT 'Phone Carrier SMS address', PRIMARY KEY (`carrier_ID`)) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='Phone Carrier+SMS Reference' AUTO_INCREMENT=1 ;",

  //EoT
  //All other tables will be created by class methods or mod installs.
  //Or people...
  );


  ////////////////////
  //%%^^ STEP 4 ^^%%//
  ////////////////////
  //% Begin running database queries and monitoring results
  $SQL = '';
  $Left = count($SQLFILE); //Num of tables/queries left to run
  $Total = $Left; //Total Num of tables/queries to run
  $Built = 0; //Num of tables/queries already run
  foreach($SQLFILE as $LINE)
  {
    $SQL = $LINE;
    $Success = mysqli_query($Linked, $SQL);
    $Built++;
    if($Success)
    {
      $Left--;
      echo("<span style='color:green;'>Successfully completed database query ".$Built.":</span><br>");
    }
    else
    {	
      echo("<span style='color:red;'>Error while executing database query ".$Built.":</span> <span style='color:blue'>".mysqli_error($Linked)."</span><br>");
    }
  }

  if($Left == 0)
  {
    echo("<span style='color:green;'>Successfully completed database tables...</span><br><br>");
  }
  else
  {
    echo("<span style='color:red;'>Error creating database tables:</span> <span style='color:blue'>".mysqli_error($Linked)."</span><br>");
    echo("Exiting...");
    exit();
  }
  mysqli_close($Linked);


  ////////////////////
  //%%^^ STEP 5 ^^%%//
  ////////////////////
  //% Create the default Configuration data and plug in the gathered user values

  //Encrypt the DB credentials before adding them to the configuration file
  //DEPRECATED~!
  /*
  $e_dbServ = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($_POST['cmnEIVS']), $_POST['DBserv'], MCRYPT_MODE_CBC, md5(md5($_POST['cmnEIVS']))));
  $e_dbUser = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($_POST['cmnEIVS']), $_POST['DBuser'], MCRYPT_MODE_CBC, md5(md5($_POST['cmnEIVS']))));
  $e_dbPass = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($_POST['cmnEIVS']), $_POST['DBpass'], MCRYPT_MODE_CBC, md5(md5($_POST['cmnEIVS']))));
  $e_dbName = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($_POST['cmnEIVS']), $_POST['DBname'], MCRYPT_MODE_CBC, md5(md5($_POST['cmnEIVS']))));
  */
  
  //Encrypt the DB credentials before adding them to the configuration file
  $e_dbServ = base64_encode(openssl_encrypt($_POST['DBserv'], $cipher, crypt($ekey, 'ni'), $options=0, $eivs));
  $e_dbUser = base64_encode(openssl_encrypt($_POST['DBuser'], $cipher, crypt($ekey, 'ni'), $options=0, $eivs));
  $e_dbPass = base64_encode(openssl_encrypt($_POST['DBpass'], $cipher, crypt($ekey, 'ni'), $options=0, $eivs));
  $e_dbName = base64_encode(openssl_encrypt($_POST['DBname'], $cipher, crypt($ekey, 'ni'), $options=0, $eivs));

  $RAWCONFIG = ('
;/# $$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$ #/
;/# $$$$$$ NiFramework - CONFIGURATION FILE $$$$$$ #/
;/# $$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$ #/
;
;Please note that editing of this file is highly discouraged
;unless you are a very advanced user. This file is generated 
;during installation and changing any values could result in 
;a system failure. (Data will not be lost)
;
;//#####################//
;//### COMMON VALUES ###//
;//#####################//
[Common]
;//Set Website Short Name
;//Default Value: [ SiteName = somesite ]
SiteName = "'.$_POST['siteName'].'"
;
;//Set Website Contact Email
;//Default Value: [ ContactEmail = admin@somesite.com ]
ContactEmail = '.$_POST['rootEmail'].'
;
;
[reCAPTCHA]
;//Set reCAPTCHA Public Key
PublicKey = "'.$_POST['pubKey'].'"
;
;//Set reCAPTCHA Secret Key
SecretKey = "'.$_POST['privKey'].'"
;
;
;//############################//
;//### DATABASE CREDENTIALS ###//
;//############################//
[Database]
;
;	#Note# - You cannot change these database values once they are set.
;
;//Database Server Host
DB_Host = "'.$e_dbServ.'"
;
;//Database User
DB_User = "'.$e_dbUser.'"
;
;//Database User Pass
DB_Pass = "'.$e_dbPass.'"
;
;//Database Name
DB_Name = "'.$e_dbName.'"
;
;//Table Prefix
DB_Prefix = '.$_POST['DBprefix'].'
;
;
;//############################//
;//### USER TABLE & COLUMNS ###//
;//############################//
[User]
;//User Table Name
User_Table = user
;
;//User ID Column
UserID_col = user_ID
;
;//User Type Column
UserType_col = userType_ID
;
;//User First Name Column
UserFirstName_col = userFirstName
;
;//User Middle Name Column
UserMidName_col = userMidName
;
;//User Last Name Column
UserLastName_col = userLastName
;
;//User Nick Name Column (Screen/Display Name)
UserNickName_col = userNickName
;
;//User Email Column
UserEmail_col = userEmail
;
;//User Phone Column
UserPhone_col = userPhone
;
;//User Phone Carrier
UserCarrier_col = carrier_ID
;
;//User Time Zone
UserTimezone_col = userTZ
;
;//User Pass Column
UserPass_col = userPass
;
;//User Status Column
UserStatus_col = userStatus_ID
;
;//User Code Column
UserCode_col = userCode
;
;
;//User Type [Table]
UserType_Table = userType
;
;//User Type ID Column
UserTypeID_col = userType_ID
;
;//User Type Name Column
UserTypeName_col = userTypeName
;
;
;//User Status [Table]
UserStatus_Table = userStatus
;
;//User Status ID Column
UserStatusID_col = userStatus_ID
;
;//User Status Name Column
UserStatusName_col = userStatusName
;
;
;//#####################################//
;//### PHONE CARRIER TABLE & COLUMNS ###//
;//#####################################//
[Phone]
;//Phone Carrier [Table]
Carrier_Table = phoneCarrier
;
;//Phone Carrier ID Column
CarrierID_col = carrier_ID
;
;//Phone Carrier Name Column
CarrierName_col = carrierName
;
;//Phone Carrier SMS Column
CarrierSMS_col = carrierSMS
;
;
;END of Configuration
');

  //Create the Configuration File
  echo("Attempting to generate configuration file...<br>");
  $FileName = "../inc/config.ini";
  if(file_exists($FileName))
  {
    unlink($FileName);
  }

  $FileHandle = fopen($FileName, 'wb') or die("<span style='color:red;'>Error opening configuration file...</span><br><br>");
  $Generated = fwrite($FileHandle, trim($RAWCONFIG));
  if($Generated)
  {
    echo("<span style='color:green;'>Successfully generated configuration file...</span><br><br>");
  }
  else
  {
    echo("<span style='color:red;'>Error creating configuration file...</span><br><br>");
  }

  fclose($FileHandle);
  if(file_exists("../inc/config.ini") && $Generated != False)
  {
    echo("<span style='color:green;'>Successfully verified configuration file generation...</span><br><br>");

    ////////////////////
    //%%^^ STEP 6 ^^%%//
    ////////////////////
    //% Create the Constants File
    echo("Attempting to generate constants...<br>");

    //Check for missing reCAPTCHA data
    //if missing, set reCAPTCHA feature to disabled.
    $recaptcha_value = ((isset($_POST['privKey']) && $_POST['privKey'] != '') && (isset($_POST['pubKey']) && $_POST['pubKey'] != '')) ? 1 : 0;


    //Create the raw constant file data
    $RAWCONST = ('
<?php
/*
Do not change Left hand values
*/
//Setting this to true will display errors 
//in the browser (for dev purposes) 
define("E_REPORT", false);    

//Define Configuration Path
define("CONFIG_PATH", "inc/config.ini");

//Define default style to use
define("STYLE", "NiStyle");

//Define default userTypeID (groupID)
define("USER", 3);

//Define Login Acceptance Level, [0-2]
// 0 = Both Email and Nickname accepted
// 1 = Only Email accepted
// 2 = Only Nickname accepted
define("LOG_NE", "0");

//Define recaptcha on/off
// 0 = recaptcha disabled
// 1 = recaptcha enabled
define("RECAPTCHA", "'.$recaptcha_value.'");

//EIVS (Encryption Initialization Vector String)
//EKEY (Encryption Key)
//!!DO NOT CHANGE THESE CONSTANTS OR VALUES!!
define("EIVS", "'.bin2hex($eivs).'");
define("EKEY", "'.$ekey.'");

?>
');//END RAWCONST

    $FileHandle = fopen("../inc/const.php", 'wb') or die("<span style='color:red;'>Error generating constants file...</span><br><br>");
    $Generated = fwrite($FileHandle, trim($RAWCONST));
    if($Generated)
    {
      echo("<span style='color:green;'>Successfully generated constants file...</span><br><br>");
    }
    else
    {
      echo("<span style='color:red;'>Error creating constants file...</span><br><br>");
    }
    fclose($FileHandle);

    //Verify Success
    if(file_exists("../inc/const.php") && $Generated != False)
    {
      echo("<br>");
      echo("<span style='color:green;'>Installation has finished successfully!.</span><br>");

      ////////////////////
      //%%^^ STEP 7 ^^%%//
      ////////////////////
      //Generate a log File and clean-up
      $log = fopen("../inc/installLog.html", 'wb') or die("<span style='color:red;'>Error generating installation log file...</span><br><br>");
      $logged = fwrite($log, trim(ob_get_contents()));
      fclose($log);//close the log file
      ob_end_flush();//stop monitoring output


      echo("Cleaning up...<br>");
      //# recursively clean up installation files
      function rrmdir($dir) 
      {
        foreach(glob($dir . '/*') as $file) 
        {
          if(is_dir($file))
          {
            rrmdir($file);
          }
          else
          {
            if(unlink($file))
            {
              echo("<span style='color:green;'>Successfully cleaned up: $file</span><br>");
            }
            else
            {
              echo("<span style='color:red;'>Error cleaning up: $file</span><br>");
            }
          }
        }
        if(rmdir($dir))
        {
          echo("<span style='color:green;'>Successfully cleaned up: $dir</span><br>");
        }
        else
        {
          echo("<span style='color:red;'>Error cleaning up: $dir</span><br>");
        }
      }
      //Invoke the method
      //rrmdir("../install");
      
      echo "Redirecting to homepage...<br>"
      //echo("<meta http-equiv='refresh' content='20;url=http://".$SCRIPTPATH."'><br><br>");

      session_destroy();
      exit();			
    }
  }

  echo("<br>");
  echo("<span style='color:red;'>Installation has failed review errors and try again.</span>");

  //Generate A log File
  $log = fopen("../installLog.html", 'wb');
  $logged = fwrite($log, ob_get_contents());
  fclose($log);//close the log file
  ob_end_flush();//stop buffering the output
}
else
{
  //Redirect to the index file
  header("Location: index.php");
}
?>