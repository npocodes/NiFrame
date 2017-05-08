<?php
/*
  Purpose:  Installer Start File - Ni Framework - www.NativeInventions.com
  
  FILE:     The Installer Start file begins the installation of the NiFramework
            
  Author:   Nathan M. Poole - nathan@nativeinventions.com
  Date:     July 2014
*/
//Start/Resume the PHP session
session_start();

//Check for the configuration and constants files
if(!(file_exists("../inc/config.ini")) || !(file_exists("../inc/const.ini")))
{
  //Missing either of these files means the system is not "installed".
  //Try to delete both files just to start fresh.
  @unlink("../inc/config.ini");
  @unlink("../inc/const.ini");
  
  //Confirm with the wizard that this file
  //has been run by setting a session variable
  $_SESSION['install'] = true;
  
  //Begin the Installer Guide
  header("Location: wizard.php");
  die();
}
else
{
  //Both files exist system is installed
  //Remove *this install directory.
}
?>