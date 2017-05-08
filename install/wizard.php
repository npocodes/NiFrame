<?php
session_start();
/*
	NiFrame Installation Wizard File
	
	This file acts as a wizard process that guides the users through the installation 
	process. The wizard will gather the required information from the user and pass it 
  along to the install file.
	
*/
//Get the error class.
//Get the template class so we can make HTML pages.
require_once("../inc/classes/class.error.php");
require_once("../inc/classes/class.template.php");

//Declare the VARLIST
$VARLIST = array();

//Before anything is done we should
//verify that index file has been run
if(isset($_SESSION['install']) && $_SESSION['install'] === true)
{
	//%^^ Display the install form
  $FILELIST = array("cmn.html", "db.html");
	
  //Set the page name Tpl Var
  $VARLIST['PAGE_NAME'] = 'NiFrame Auto-Installer';
  
	//Make/Compile the template
	$Tpl = new template("NiStyle");
	$FileSet = $Tpl->SetFiles($FILELIST);
	$VarSet = $Tpl->SetVars($VARLIST);
	if(!($Tpl->Compile()))
	{
		echo($Tpl->Error());
	}
}
else
{
  //Redirect to the index
  header("Location: index.php");
}
?>