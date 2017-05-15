<?php
/*
  Purpose:  Item Driver File - NiFrame Inventory Module
  
  FILE:     The Item driver file handles Item module specific use-case
            scenarios such as: viewing an item profile, searching items, etc...
            (See itemA.php driver for admin specific use-cases: create, edit, etc...)
            
  Author:   Nathan Poole - github/npocodes
  Date:     Janurary 2015
  Updated:  
*/
//Include the common file
require_once('common.php');

//Include ItemMod specific class files
require_once('inc/classes/class.item.php');

//Require User Login
if(!($_USER->UnPack())){ header("location: login.php"); }

//set the default HTML file to use
$T_FILE = 'message.html';

//Set default message
$T_VAR['MSG'] = '';


//Check for [View] Item Mode
if(isset($_INPUT['view']))
{
  //Verify user is not a guest
  if($_USER->ID() != 0)
  {
    //Set the page name
    $T_VAR['PAGE_NAME'] = 'Item View';
    
    //Check for a provided Item ID
    if(isset($_INPUT['iid']) && $_INPUT['iid'] > 0)
    {
      //Set template to use Item view HTML
      $T_FILE = 'itemView.html';
      
      //Create new Item object using ID provided
      $Item = new item($_INPUT['iid']);
      
      //Set template variables using item data
      $T_VAR['ITEM_ID']       = $Item->ID();
      $T_VAR['ITEM_TYPE']      = $Item->Type();
      $T_VAR['ITEM_NAME']     = $Item->Name();
      $T_VAR['ITEM_MAKER']    = $Item->Maker();
      $T_VAR['ITEM_VALUE']    = $Item->Value();
      $T_VAR['ITEM_COST']     = $Item->Cost();
      $T_VAR['ITEM_PRICE']    = $Item->Price();
      $T_VAR['ITEM_UNITS']    = $Item->Units();
      $T_VAR['ITEM_ONHAND']   = $Item->OnHand();
      $T_VAR['ITEM_CONSUME']  = $Item->Consume();
      $T_VAR['ITEM_PERISH']   = $Item->Perish();
      $T_VAR['ITEM_WEIGHT']   = $Item->Weight();
      $T_VAR['ITEM_SIZE']     = $Item->Size();
      $T_VAR['ITEM_COLOR']    = $Item->Color();
      
    }//Else No IID 
  }//Else no user
}


//Check for [Search] Items mode
//This mode also doubles for item 
//management view if user is Admin
if(isset($_INPUT['search']))
{
  //Set which HTML file to use
  $T_FILE = 'itemSearch.html';
  
  //Set the page name
  $T_VAR['PAGE_NAME'] = 'Item Search';
  
  //Check for a provided needle
  $needle = (isset($_INPUT['needle']) && !(empty($_INPUT['needle']))) ? $_INPUT['needle'] : '';
  
  //Check for beginning or ending flag
  // 0 = begins with, 1 = ends with
  // ex ( 0, needle_ , matches: needles, needles and pie, needle-juice, needle(w/e))
  $BoE = (isset($_INPUT['boe'])) ? $_INPUT['boe'] : 'none';
  
  //Check for a provided search filter option or set a default
  $filter = (isset($_INPUT['filter'])) ? $_INPUT['filter'] : 'name';
  
  //Create an empty Item object to use for the search
  $Item = new item();
  
  //Perform the item search
  if($itemID_List = $Item->Search($filter, $needle, $BoE))
  {    
    //Foreach item, Build a "sub" Template with itemRow.html
    //and combine each row to make a list
    $T_VAR['ITEM_LIST'] = '';
    foreach($itemID_List as $itemID)
    {
      //Verify each var used is reset that needs to be
      $tItem = null; //tmp item var
      $iVars = array(); //tmp holder for Item unique vars
      $iHTML = ''; //Item unique HTML template compilation
      
      //Use ID to create a new tmp Item Obj
      $tItem = new item($itemID);
      
      //Set which HTML file to use
      $iFile = 'itemRow.html';
      
      //Set actual template Variables
      $T_VAR['ITEM_ID']       = $tItem->ID();
      $T_VAR['ITEM_NAME']     = $tItem->Name();
      $T_VAR['ITEM_MAKER']    = $tItem->Maker();
      $T_VAR['ITEM_VALUE']    = $tItem->Value();
      $T_VAR['ITEM_COST']     = $tItem->Cost();
      $T_VAR['ITEM_PRICE']    = $tItem->Price();
      $T_VAR['ITEM_UNITS']    = $tItem->Units();
      $T_VAR['ITEM_ONHAND']   = $tItem->OnHand();
      $T_VAR['ITEM_CONSUME']  = $tItem->Consume();
      $T_VAR['ITEM_PERISH']   = $tItem->Perish();
      $T_VAR['ITEM_WEIGHT']   = $tItem->Weight();
      $T_VAR['ITEM_SIZE']     = $tItem->Size();
      $T_VAR['ITEM_COLOR']    = $tItem->Color();
      
      //Build each template, using no header/footer, no path modifier, and returning the HTML
      $iHTML = BuildTemplate($iFile, $iVars, $T_COND, true, null, true);
      
      //Add the row to the list, spacing is just for formatting the code
      $T_VAR['ITEM_LIST'] .= '      '.$iHTML.PHP_EOL;
      
    }//End Item Loop
    $T_COND[] = 'NO_RESULT';//Remove NO_RESULT html
  }
  else
  {
    //Search Failure
    $T_COND[] = 'RESULT';
    $T_VAR['MSG'] = 'No Results!';
  }
}


//Build the template (LAST LINE OF ALL MAIN DRIVERS)
BuildTemplate($T_FILE, $T_VAR, $T_COND);
?>