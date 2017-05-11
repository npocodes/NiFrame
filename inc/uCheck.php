<?php
/*
  Project: Ni Framework
  
  Author: Nathan M. Poole ( nathan@nativeinventions.com )
         http://nativeinventions.com/
         
  File: The User Check driver file handles detection and
        unpacking of any user objects stored in the session.
        It also sets the user Permission based HTML conditions.
*/
//Include class files
require_once('inc/classes/class.user.php');

//First we need to create an empty(guest) user object
//to gain access to the user methods. (Users unpack themselves)
$_USER = new user();

//Now that we have a guest user we can try to unpack them.
//See the UnPack method description for more details on how.
if($_USER->UnPack())
{
  // USER IS NOW LOGGED IN
  
  //Create USER_NAME template var
  $T_VAR['MAIN_USER_NAME'] = $_USER->Name();
  
  //Retrieve User Permissions based on userType
  //and set conditions accordingly...
  $UserPermissions = $_USER->Permitted();
  foreach($UserPermissions as $permName => $permValue)
  {
    //If the user does not have permission for the given permission
    //Name then, convert permission name to upper-case and create an
    //HTML condition using its name. Any code meant for that permission
    //will be conditioned out of the HTML before display
    if(!($permValue)){ $T_COND[] = strToUpper($permName); }
  }
  
  //Additional Conditions for non-guest situations
  //add new conditions to the right hand side.
	array_push($T_COND, 'LOGOUT');
	
  //Repack user for transport in the session
	//
	//	This is not required on all server builds.
	//	Comment out if it causes problems on your server
	//
	$_USER->Pack();
}
else
{
  //No User found
  $T_VAR['MAIN_USER_NAME'] = "Guest";
  
  //Retrieve User Permissions based on userType
  //and set conditions accordingly...
  $UserPermissions = $_USER->Permitted();
  foreach($UserPermissions as $permName => $permValue)
  {
    //If the user does not have permission for the given permission
    //Name then, convert permission name to upper-case and create an
    //HTML condition using its name. Any code meant for that permission
    //will be conditioned out of the HTML before display
    if(!($permValue)){ $T_COND[] = strToUpper($permName); }
  }
  
  //Additional Conditions for guest situations
  //add new conditions to the right hand side.
  array_push($T_COND, 'LOGIN');
  
  /*
    //FORCED LOGIN - disabled, drivers must force login themselves
  //Drivers needed to login or see portal
  //should not affect the redirection.
  $safeList = array('index.php', 'login.php', 'calendar.php');

  //Send them to the portal
  if(!(in_array($CURR_FILE, $safeList)))
  {
    //header('Location: index.php'); //Comment line to force login
    header('Location: login.php'); //Uncomment line to force login
  }
  */
}
?>