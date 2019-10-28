<?php
/*
  Purpose:  User Driver File - NiFrame
  
  FILE:     The user driver file handles user module specific use-case
            scenarios such as: A user: editing own profile, searching other users, etc...
            (See userA.php driver in the ACP for admin specific use-cases)
            
  Author:   Nathan Poole - github/npocodes
  Date:     July 2014
  Updated:  6/16/2017
*/
//small change
//Include the common file
require_once('common.php');

//Require User Login
if(!($_USER->UnPack())){ header("location: login.php"); }

//set the default HTML file to use
$T_FILE = 'message.html';

//Set default message
$T_VAR['MSG'] = '';

//Check for [Update(edit)] Mode
if(isset($_INPUT['edit']))
{
  //Verify current User is not a guest
  if($_USER->ID() != 0)
  {
    //Set the page name
    $T_VAR['PAGE_NAME'] = 'User Edit';
    
    //Determine if self editing or if ADMIN editing another user (must be ADMIN to edit others)
    //Is there a user id? Is the user id not zero? Does current user have permission? If not, force self edit
    if(isset($_INPUT['uid']) && $_INPUT['uid'] !=0)
    {
      //We have been given a user ID for the user we wish to edit
      //Is current user permitted to edit others? 
      if($_USER->Permitted('ACP'))
      {
        //Current user is an admin
        //Remove display only elements
        $T_COND[] = 'DISPLAY';
        
        //Stop admins from editing root user unless they are root user.
        if($_INPUT['uid'] != 1)
        {
          //UID does not equal root user..
          //allow the edit
          $eUser = new user($_INPUT['uid']);
        }
        else
        {
          //Force them to edit themselves.
          $eUser = $_USER;
        }
      }
      else
      {
        //Not admin, cannot edit others.
        $eUser = $_USER;
      }
    }
    else
    {
      //If user is admin remove display only elements
      if($_USER->Permitted('ACP'))
      {
        $T_COND[] = 'DISPLAY';
      }
      
      //Self edit
      $eUser = $_USER;
    }
    
    //Check for form submission
    if(isset($_INPUT['submit']))
    {
      //Discard unneeded data
      $skipList = array('submit', 'uid', 'edit', 'confPass');
      foreach($_INPUT as $key => $value)
      {
        if(!(in_array($key, $skipList)))
        {
          $data[$key] = $value;
        }
      }
      
      //Attempt to update user, then respond and redirect
      $T_VAR['MSG'] = ($eUser->Update($data)) ? $eUser->Name(0).' has been updated!' : 'Failed to update '.$eUser->Name(0);
      $T_VAR['REDIRECT'] = '3;url=user.php?edit&uid='.$eUser->ID();
    }
    else
    {
      //Set template to use the user edit HTML
      //and the user schedule html file
      $T_FILE = 'userEdit.html';

      //Set up some Template variables
      $T_VAR['USER_ID']      = $eUser->ID();
      $T_VAR['USER_NAME']    = $eUser->Name();
      $T_VAR['USER_NICK_NAME'] = $eUser->NickName();
			$T_VAR['USER_AVATAR'] = $eUser->Avatar();
      $T_VAR['USER_EMAIL']   = $eUser->Email();
      $T_VAR['USER_PHONE']   = $eUser->Phone();
      $T_VAR['USER_TYPE_ID']   = $eUser->Type();
      $T_VAR['USER_TYPE_NAME'] = $eUser->Type(1);
      $T_VAR['USER_STATUS_NAME'] = $eUser->Status(1);
      
      //Split up the name into separate parts
      $T_VAR['USER_FIRST_NAME'] = $eUser->Name(0);
      $T_VAR['USER_MID_NAME']   = $eUser->Name(1);
      $T_VAR['USER_LAST_NAME']  = $eUser->Name(2);
      
      //Retrieve any possible User-Defined User Attributes
      $attrIndex = $eUser->AttrIndex();
      if(!($attrIndex === false))
      {
        foreach($attrIndex as $attr)
        {
          //Only take those attributes that are
          //ranked for profile viewing.
          //0 = normal, 1 = normal+Join, 2 = Join Only
          if($attr['rank'] < 2)
          {
            $T_VAR['USER_ATTR_NAME'][] = $attr['name'];
            $T_VAR['USER_ATTR_LABEL'][] = $attr['label'];
            $T_VAR['USER_ATTR_VTYPE'][] = $attr['vType'];
          }
        }
        
        //Set the HTML repeat COND for the attr HTML
        $T_COND[] = '!USER_ATTRS'.count($T_VAR['USER_ATTR_NAME']);
        
        //Retrieve the User specific values for those attributes
        $attrList = $eUser->AttrList();
        if(!($attrList === false))
        {
          foreach($attrList as $name => $value)
          {
            //Only take values that we have names/labels for
            if(in_array($name, $T_VAR['USER_ATTR_NAME']))
            {
              $T_VAR['USER_ATTR_VALUE'][] = $value;
            }
          }
        }else{ $T_VAR['USER_ATTR_VALUE'] = array_fill(0, count($T_VAR['USER_ATTR_NAME']), ''); }
      }else{ $T_COND[] = 'USER_ATTR_LIST'; }
      
      
      //Get list of user Types
      $typeList = $eUser->TypeList();
      
      //Create repeat condition for the type options
      $T_COND[] = '!USER_TYPE_OPTIONS'.count($typeList);
      
      //Create User Type List and associated data
      //Automatically select *this users type
      $x=0;
      foreach($typeList as $type)
      {
        $T_VAR['USER_TYPE_IDS'][$x] = $type[0];
        $T_VAR['USER_TYPE_NAMES'][$x] = $type[1];
        
        //Initialize each element to empty string
        $T_VAR['USER_TYPE_SELECTED'][$x] = ($eUser->Type() == $type[0]) ? 'SELECTED' : '';
        $x++;
      }
      
      //Get list of user Statuses
      $statusList = $eUser->StatusList();
      
      //Create repeat condition for the status options
      $T_COND[] = '!USER_STATUS_OPTIONS'.count($statusList);
      
      //Create User Status List and associated data
      //Automatically select *this users status
      $x=0;
      foreach($statusList as $status)
      {
        $T_VAR['USER_STATUS_IDS'][$x] = $status[0];
        $T_VAR['USER_STATUS_NAMES'][$x] = $status[1];
        
        //Initialize each element to empty string
        $T_VAR['USER_STATUS_SELECTED'][$x] = ($eUser->Status() == $status[0]) ? 'SELECTED' : '';
        
        $x++;
      }
      
    }
  }else{ $T_VAR['MSG'] = 'No User!'; }
}


//Check for [View] User Mode
if(isset($_INPUT['view']))
{
  //Verify user is not a guest
  if($_USER->ID() != 0)
  {
    //Set the page name
    $T_VAR['PAGE_NAME'] = 'User View';
    
    //Check for a provided User ID
    if(isset($_INPUT['uid']) && $_INPUT['uid'] > 0)
    {
      //Set template to use user view HTML
      $T_FILE = 'userView.html';
      
      //Create new user object using ID provided
      $user = new user($_INPUT['uid']);
      
      //Set template variables using user data
      $T_VAR['USER_ID'] = $user->ID();
      $T_VAR['USER_NAME'] = $user->Name();
      $T_VAR['USER_EMAIL'] = $user->Email();
			$T_VAR['USER_AVATAR'] = $user->Avatar();
			$T_VAR['USER_NICKNAME'] = $user->Nickname();
      $T_VAR['USER_PHONE'] = $user->Phone();
      $T_VAR['USER_FIRST_NAME'] = $user->Name(0);
      $T_VAR['USER_LAST_NAME'] = $user->Name(1);
      $T_VAR['USER_STATUS'] = $user->Status(1); //Name of status
      $T_VAR['USER_TYPE'] = $user->Type(1); //Name of Type
      
      //Retrieve any possible User-Defined User Attributes
      $attrIndex = $user->AttrIndex();
      if(!($attrIndex === false))
      {
        foreach($attrIndex as $attr)
        {
          //Only take those attributes that are ranked for profile 
          //viewing. 0 = normal, 1 = normal+Join, 2 = Join Only
          if($attr['rank'] < 2)
          {
            //We only need the Label for the template here...
            //but we also need to reference the list of attr names
            $attrNameList[] = $attr['name'];
            $T_VAR['USER_ATTR_LABEL'][] = $attr['label'];
          }
        }
        
        //Set the HTML repeat COND for the attr HTML
        $T_COND[] = '!USER_ATTRS'.count($T_VAR['USER_ATTR_LABEL']);
        
        //Retrieve the User specific values for those attributes
        $attrList = $user->AttrList();
        if(!($attrList === false))
        {
          foreach($attrList as $name => $value)
          {
            //Only take values that we have labels for
            if(in_array($name, $attrNameList))
            {
              //We only need the Value here...
              $T_VAR['USER_ATTR_VALUE'][] = $value;
            }
          }
        }else{ $T_VAR['USER_ATTR_VALUE'] = array_fill(0, count($T_VAR['USER_ATTR_LABEL']), ''); }
      }else{ $T_COND[] = 'USER_ATTR_LIST'; }      
      
    }//Else No UID 
  }//Else no user
}


//Check for [Search] Users Mode
//This mode also doubles for user 
//management view if user is Admin
if(isset($_INPUT['search']))
{
  //Set which HTML file to use
  $T_FILE = 'userSearch.html';
  
  //Set the page name
  $T_VAR['PAGE_NAME'] = 'User Search';
  
  //Check for a provided needle
  $needle = (isset($_INPUT['needle']) && !(empty($_INPUT['needle']))) ? $_INPUT['needle'] : '';
  
  //Check for beginning or ending flag
  // 0 = begins with, 1 = ends with
  // ex ( 0, needle_ , matches: needles, needles and pie, needle-juice, needle(w/e))
  $BoE = (isset($_INPUT['boe'])) ? $_INPUT['boe'] : 'none';
  
  //Check for a provided search filter option or set a default
  $filter = (isset($_INPUT['filter'])) ? $_INPUT['filter'] : 'email';
  
  //Perform the user search
  if($userID_List = $_USER->Search($filter, $needle, $BoE))
  {    
    //Foreach user, Build a "sub" Template with userRow.html
    //and combine each row to make a list
    $T_VAR['USER_LIST'] = '';
    foreach($userID_List as $userID)
    {
      //Verify each var used is reset that needs to be
      $User = null; //tmp user var
      $uVars = array(); //tmp holder for user unique vars
      $uHTML = ''; //user unique HTML template compilation
      
      //Use the ID to create user Obj
      $User = new user($userID);
      
      //Set which HTML file to use
      $uFile = 'userRow.html';
      
      //Set actual template Variables
      $uVars['USER_ID'] = $User->ID();
      $uVars['USER_NAME'] = $User->Name();
      $uVars['USER_FIRST_NAME'] = $User->Name(0);
      $uVars['USER_MID_NAME'] = $User->Name(1);
      $uVars['USER_LAST_NAME'] = $User->Name(2);
      $uVars['USER_NICK_NAME'] = $User->Nickname();
      $uVars['USER_TYPE_NAME'] = $User->Type(1);
      $uVars['USER_STATUS_NAME'] = $User->Status(1);
      $uVars['USER_EMAIL'] = $User->Email();
      $uVars['USER_PHONE'] = $User->Phone();
      
      //Build each template, using no header/footer, no path modifier, and returning the HTML
      $uHTML = BuildTemplate($uFile, $uVars, $T_COND, true, null, true);
      
      //Add the row to the list, spacing is just for formatting the code
      $T_VAR['USER_LIST'] .= '      '.$uHTML.PHP_EOL;
      
    }//End User Loop
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