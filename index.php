<?php
/*
  Purpose:  Index Driver File - NiFrame
  
  FILE:     The Index driver file handles the request for the main site
            page, a.k.a. the Portal or Home page. This is usually the most
            basic of driver files so it may be a good file to copy when 
            making new Drivers!
            
  Author:   Nathan Poole - github/npocodes
  Date:     July 2014
*/
//Include the common file
require_once('common.php');

//Set what HTML files to use
$T_FILE = 'index_body.html';

//Create some HTML Variables
$T_VAR['PAGE_NAME'] = 'Guest Portal';

//Build the template
BuildTemplate($T_FILE, $T_VAR, $T_COND);
?>