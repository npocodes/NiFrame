<?php
/*
  Purpose:  Item Administration Driver File
            Ni Framework - www.NativeInventions.com
  
  FILE:     The Item Administration driver file handles item specific use-case
            scenarios (modes) that require Administrative permissions such as: 
            creating a new user, deleting users, editing permissions, etc...
            
            (Shared modes like "User Edit" are in base driver 'user.php')
            
  Author:   Nathan M. Poole - nathan@nativeinventions.com
  Date:     Janurary 2015
*/
//Include the common file
require_once('common.php');

//Include ItemMod specific class files
require_once('inc/classes/class.item.php');

//Require User Login
if(!($_USER->UnPack())){ header("location: login.php"); die(); }

//Require ACP Privileges
if(!($_USER->Permitted('ACP') || $_USER->ID() == 1)){ header("location: index.php"); die(); }

//set the default HTML file to use
$T_FILE = 'message.html';

//Set default message
$T_VAR['MSG'] = '';


//Check for [Create(add) Item] Mode
if(isset($_INPUT['add']))
{
  //Set the Page Name
  $T_VAR['PAGE_NAME'] = 'Add Item';
  
  //create an empty item object
  $Item = new item();
  
  //Check for form submission
  if(isset($_INPUT['submit']))
  {
    //Verify a Type has been selected for this Item
    if(isset($_INPUT['itemType']))
    {
      //Verify a Name has been give for this Item
      if(isset($_INPUT['itemName']))
      {
        //Search the input for extra data by filtering 
        //out anything we know we do not want and/or need
        $filter = array('add', 'submit', 'itemType', 'itemName');
        $data = null;//holder for found input data
        foreach($_INPUT as $key => $value)
        {
          if(!(in_array($key, $filter)))
          {
            $data[$key] = $value;
          }
        }
        
        //Attempt to create the item
        if($Item->Create($_INPUT['itemType'], $_INPUT['itemName'], $data))
        {
          //Success!!
          $T_VAR['MSG'] = 'Item '.$_INPUT['itemName'].' created successfully!';
          
        }else{ $T_VAR['MSG'] = 'Failed to create new Item!'; }
      }else{ $T_VAR['MSG'] = 'You must supply a Name for this Item.'; }
    }else{ $T_VAR['MSG'] = 'You must choose the Type for this Item.'; }
    
    //Set the redirect
    $T_VAR['REDIRECT'] = '3;url=itemA.php?add';
  }
  else
  {
    
    //Get list of Item Types
    $typeList = $Item->TypeList();
    
    //Count how many types we have
    $typeCount = count($typeList);
    
    //Create HTML repeat condition
    $T_COND[] = '!ITEM_TYPE_OPTS'.$typeCount;
    
    //Cycle through each type (backwards!)
    //This gives us the list from Client->Admin
    //instead of Admin->Client..
    $x=0;//forward counter
    for($i=($typeCount-1); $i >= 0; $i--)
    {
      $T_VAR['ITEM_TYPE_OPT_VALUE'][$x] = $typeList[$i][0];
      $T_VAR['ITEM_TYPE_OPT_NAME'][$x] = $typeList[$i][1];
      
      $T_VAR['ITYPE_SELECT'][$x] = ($typeList[$i][0] == 3) ? 'SELECTED' : '';//???
      
      $x++;//Increment forward counter
    }
  
    //Display the add item form
    $T_FILE = 'itemAdd.html';
  }
}


//Check for [Update(edit)] Mode
if(isset($_INPUT['edit']))
{
  //Set the page name
  $T_VAR['PAGE_NAME'] = 'Item Edit';
    
  //Verify an Item ID has been provided
  if(isset($_INPUT['iid']) && $_INPUT['iid'] != 0)
  {
    //Create the item Object using the provided ID
    $eItem = new item($_INPUT['iid']);
  
    //Check for form submission
    if(isset($_INPUT['submit']))
    {
      //Discard unneeded data
      $skipList = array('submit', 'iid', 'edit');
      foreach($_INPUT as $key => $value)
      {
        if(!(in_array($key, $skipList)))
        {
          $data[$key] = $value;
        }
      }
      
      //Attempt to update Item, then respond and redirect
      $T_VAR['MSG'] = ($eItem->Update($data)) ? 'Item '.$eItem->Name().' has been updated! '.$eItem->Error() : 'Failed to update Item '.$eItem->Name();
      $T_VAR['REDIRECT'] = '3;url=itemA.php?edit&iid='.$eItem->ID();
    }
    else
    {
      //Set template to use the Item edit HTML
      $T_FILE = 'itemEdit.html';

      //Set up some Template variables
      $T_VAR['ITEM_ID']       = $Item->ID();
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
      
      //Get list of Item Types
      $typeList = $eItem->TypeList();
      
      //Create repeat condition for the type options
      $T_COND[] = '!ITEM_TYPE_OPTIONS'.count($typeList);
      
      //Create Item Type List and associated data
      //Automatically select *this Items type
      $x=0;
      foreach($typeList as $type)
      {
        $T_VAR['ITEM_TYPE_IDS'][$x] = $type[0];
        $T_VAR['ITEM_TYPE_NAMES'][$x] = $type[1];
        
        //Initialize each element to empty string
        $T_VAR['ITEM_TYPE_SELECTED'][$x] = ($eItem->Type() == $type[0]) ? 'SELECTED' : '';
        $x++;
      }
      
      //Get list of Item Makers
      $makerList = $eItem->MakerList();
      
      //Create repeat condition for the Maker options
      $T_COND[] = '!ITEM_MAKER_OPTIONS'.count($makerList);
      
      //Create Item Maker List and associated data
      //Automatically select *this Items Maker
      $x=0;
      foreach($makerList as $maker)
      {
        $T_VAR['ITEM_MAKER_IDS'][$x] = $maker[0];
        $T_VAR['ITEM_MAKER_NAMES'][$x] = $maker[1];
        
        //Initialize each element to empty string
        $T_VAR['ITEM_MAKER_SELECTED'][$x] = ($eItem->Maker() == $maker[0]) ? 'SELECTED' : '';
        
        $x++;
      }
      
    }
  }else{ $T_VAR['MSG'] = 'You must supply an Item ID'; }
}


//Check for [Remove(del) Item] Mode
if(isset($_INPUT['del']))
{
  //Determine if Item ID provided
  if(isset($_INPUT['iid']) && $_INPUT['iid'] != 0)
  {
    //Set the HTML file to use
    $T_FILE = 'message.html';
    
    //Create the item object for this item
    $kItem = new item($_INPUT['iid']);
    
    //Go in for the Kill, then respond and redirect
    $T_VAR['MSG'] = ($kItem->Kill()) ? $kItem->Name().' has been destroyed!' : 'Failed to destroy '.$kItem->Name();
    
  }else{ $T_VAR['MSG'] = 'An Item ID number must be provided!'; }
  
  //Finally set the message REDIRECT
  $T_VAR['REDIRECT'] = '3;url=item.php?search';
}


//Check for [Add ItemType] Mode
if(isset($_INPUT['addType']))
{
  //Set the Page Name
  $T_VAR['PAGE_NAME'] = 'Add Item Type';
  
  //Set the default HTML file to use
  $T_FILE = 'message.html';
  
  //Create an empty item object to use
  $Item = new item();  
  
  //Check for form submission
  if(isset($_INPUT['submit']))
  {
    //Verify a Type Name has been provided
    if(isset($_INPUT[$CONFIG['ItemTypeName_col']]))
    { 
      //Attempt to create the new Item Type
      if($Item->NewType($_INPUT[$CONFIG['ItemTypeName_col']]))
      {
        $T_VAR['MSG'] = 'Item Type'.$_INPUT['ItemTypeName_col'].' created successfully!';
        
      }else{$T_VAR['MSG'] = 'Unable to create new Item Type!';}
    }else{$T_VAR['MSG'] = 'You must provide a Name for the Item Type!';}
    
    //Finally set the message REDIRECT
    $T_VAR['REDIRECT'] = '3;url=itemA.php?addType';
  }
  else
  {
    //Show the Add Type Form.
    $T_FILE = 'itemAddType.html';
    
    $typeList = $Item->TypeList();
    if($typeList !== false)
    {
      $T_COND[] = '!ITEM_TYPES'.count($typeList);
      foreach($typeList as $type)
      {
        //Get List of Item Types
        $T_VAR['ITEM_TYPE_ID'][] = $type[0];
        $T_VAR['ITEM_TYPE_NAME'][] = $type[1];
      }
    }else{ $T_COND[] = 'TYPE_LIST'; }
  }
}


//Check for [Remove(del) ItemType] Mode
if(isset($_INPUT['delType']))
{
  //Determine if ItemTypeID provided
  if(isset($_INPUT['iType']))
  {
    //Set the HTML file to use
    $T_FILE = 'message.html';
    
    //Create empty item object to use
    $Item = new item();
    
    //Attempt to remove the Item Type
    if($Item->RemoveType($_INPUT['iType']))
    {
      //Success!!
      $T_VAR['MSG'] = 'Item Type has been removed successfully!';
      
    }else{ $T_VAR['MSG'] = "Failed to remove Item Type!";  }
  }else{ $T_VAR['MSG'] = "You must provide an Item Type ID!"; }
  
  //Finally set the message REDIRECT
  $T_VAR['REDIRECT'] = '3;url=itemA.php?addType';
}


//Check for [Add ItemMaker] Mode
if(isset($_INPUT['addMaker']))
{
  //Set the Page Name
  $T_VAR['PAGE_NAME'] = 'Add Item Manufacturer';
  
  //Set the default HTML file
  $T_FILE = 'message.html';
  
  //Create an empty item object to use
  $Item = new item();  
  
  //Check for form submission
  if(isset($_INPUT['submit']))
  {
    //Verify a Maker Name has been provided
    if(isset($_INPUT[$CONFIG['ItemMakerName_col']]))
    { 
      //Attempt to create the new Item Maker
      if($Item->NewMaker($_INPUT[$CONFIG['ItemMakerName_col']]))
      {
        $T_VAR['MSG'] = 'Item Manufacturer '.$_INPUT['ItemMakerName_col'].' created successfully!';
        
      }else{$T_VAR['MSG'] = 'Unable to create new Item Manufacturer!';}
    }else{$T_VAR['MSG'] = 'You must provide a Name for the Item Manufacturer!';}
    
    //Finally set the message REDIRECT
    $T_VAR['REDIRECT'] = '3;url=itemA.php?addMaker';
  }
  else
  {
    //Show the Add Maker Form.
    $T_FILE = 'itemAddMaker.html';
    
    $makerList = $Item->MakerList();
    if($makerList !== false)
    {
      $T_COND[] = '!ITEM_MAKERS'.count($makerList);
      foreach($makerList as $maker)
      {
        //Get List of Item Maker
        $T_VAR['ITEM_MAKER_ID'][] = $maker[0];
        $T_VAR['ITEM_MAKER_NAME'][] = $maker[1];
      }
    }else{ $T_COND[] = 'MAKER_LIST'; }
  }
}


//Check for [Remove(del) ItemMaker] Mode
if(isset($_INPUT['delMaker']))
{
  //Determine if ItemMakerID provided
  if(isset($_INPUT['iMaker']))
  {
    //Set the HTML file to use
    $T_FILE = 'message.html';
    
    //Create empty item object to use
    $Item = new item();
    
    //Attempt to remove the Item Maker
    if($Item->RemoveMaker($_INPUT['iMaker']))
    {
      //Success!!
      $T_VAR['MSG'] = 'Item Manufacturer has been removed successfully!';
      
    }else{ $T_VAR['MSG'] = "Failed to remove Item Manufacturer!";  }
  }else{ $T_VAR['MSG'] = "You must provide an Item Manufacturer ID!"; }
  
  //Finally set the message REDIRECT
  $T_VAR['REDIRECT'] = '3;url=itemA.php?addMaker';
}


//Build the template (LAST LINE OF ALL MAIN DRIVERS)
//In this file we alter the style path to ACP sub-directory
//And attach true to the path string to signal using root header/footer
//Removing ",true" will make the template use header/footer from acp dir
BuildTemplate($T_FILE, $T_VAR, $T_COND, false, 'acp,true');
?>