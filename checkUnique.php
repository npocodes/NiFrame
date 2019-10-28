<?php
/*
  Purpose:  Check Unique File - NiFrame
  
  FILE:     Handles AJAX requests for nickname and email verifications.
            
  Author:   Nathan Poole - github/npocodes
  Date:     July 2019
*/
//Include the common file
require_once('common.php');

//Verify an email or nickname is provided
if(isset($_INPUT['email']) && !(empty($_INPUT['email'])))
{
	$whereLoc = $CONFIG['UserEmail_col'].'='.$_INPUT['email'];
}
else if(isset($_INPUT['nickname']) && !(empty($_INPUT['nickname'])))
{
	$whereLoc = $CONFIG['UserNickName_col'].'='.$_INPUT['nickname'];
}
else
{
	LogError("No Input detected -- checkUnique.php");
	echo("no input");
	exit();
}

//Connect to the database
$DB = new dbaccess();
if($DB->Link())
{
	$found = $DB->Snatch($CONFIG['User_Table'], $CONFIG['UserID_col'], $whereLoc);
	if($found)
	{
		//ALREADY IN USE!!
		echo("in-use");
	}
	else
	{
		//NOT IN USE!! --Success!
		echo("not in-use");
	}
}
else
{
	LogError("No Data Link -- checkUnique.php");
	echo("no link");
}
?>