<?php
/*
  Purpose:  User Administration Driver File
            Ni Framework - www.NativeInventions.com
  
  FILE:     The User Administration driver file handles user specific use-case
            scenarios (modes) that require Administrative permissions such as: 
            creating a new user, deleting users, editing permissions, etc...
            
            (Shared modes like "User Edit" are in base driver 'user.php')
            
  Author:   Nathan M. Poole - nathan@nativeinventions.com
  Date:     July 2014
*/
//Include the common file
require_once('common.php');

//Require User Login
if(!($_USER->UnPack())){ header("location: login.php"); die(); }

//Require ACP Privileges
if(!($_USER->Permitted('ACP') || $_USER->ID() == 1)){ header("location: index.php"); die(); }

//set the default HTML file to use
$T_FILE = 'message.html';

//Set default message
$T_VAR['MSG'] = '';


//Check for [Create(add) User] Mode
if(isset($_INPUT['add']))
{
  $T_VAR['PAGE_NAME'] = 'Add User';
  
  //Check for form submission
  if(isset($_INPUT['submit']))
  {
    //Verify email has been given
    if(isset($_INPUT[$CONFIG['UserEmail_col']]) && !(empty($_INPUT[$CONFIG['UserEmail_col']])))
    {
      //Verify password has been given
      if(isset($_INPUT[$CONFIG['UserPass_col']]) && !(empty($_INPUT[$CONFIG['UserPass_col']])))
      {
        //Verify user type has been given
        if(isset($_INPUT[$CONFIG['UserType_col']]) && !(empty($_INPUT[$CONFIG['UserType_col']])))
        {
          //Create list of data names to skip
          $skipList = array(
            'submit', 
            'add', 
            'confPass',
            $CONFIG['UserEmail_col'],
            $CONFIG['UserPass_col'],
            $CONFIG['UserType_col']
          );
          
          //Cycle each input element
          foreach($_INPUT as $key => $value)
          {
            if(!(in_array($key, $skipList)))
            {
              $data[$key] = $value;
            }
          }
     
          //Attempt to create the new user
          if($_USER->Create($_INPUT[$CONFIG['UserEmail_col']], $_INPUT[$CONFIG['UserPass_col']], $_INPUT[$CONFIG['UserType_col']], $data))
          {
            //Success
            $T_VAR['MSG'] = 'User created successfully!';
          }
          else{ $T_VAR['MSG'] = $User->Error(); }
        }else{ $T_VAR['MSG'] = 'No user type has been given'; }
      }else{ $T_VAR['MSG'] =  'No password has been given'; }
    }else{ $T_VAR['MSG'] = 'No user email has been given'; }
    
    //Set the redirect
    $T_VAR['REDIRECT'] = '3;url=userA.php?add';
  }
  else
  {
    //Get list of user Types
    $typeList = $_USER->TypeList();
    
    //Count how many types we have
    $typeCount = count($typeList);
    
    //Create HTML repeat condition
    $T_COND[] = '!USER_TYPE_OPTS'.$typeCount;
    
    //Cycle through each type (backwards!)
    //This gives us the list from Client->Admin
    //instead of Admin->Client..
    $x=0;//forward counter
    for($i=($typeCount-1); $i >= 0; $i--)
    {
      $T_VAR['USER_TYPE_OPT_VALUE'][$x] = $typeList[$i][0];
      $T_VAR['USER_TYPE_OPT_NAME'][$x] = $typeList[$i][1];
      
      $T_VAR['UTYPE_SELECT'][$x] = ($typeList[$i][0] == 3) ? 'SELECTED' : '';
      
      $x++;//Increment forward counter
    }
  
    //Display the add user form
    $T_FILE = 'userAdd.html';
  }
}


//Check for [Remove(del) User] Mode
if(isset($_INPUT['del']))
{
  //Determine if User ID provided
  if(isset($_INPUT['uid']) && $_INPUT['uid'] != 0)
  {
    //Set the HTML file to use
    $T_FILE = 'message.html';
    
    //Create the user object for this user
    $kUser = new user($_INPUT['uid']);
    
    //Verify user is not root user
    if($kUser->ID() != 1)
    {
      //Go in for the Kill, then respond and redirect
      $T_VAR['MSG'] = ($kUser->Kill()) ? $kUser->Name(0).' has been killed!' : 'Failed to kill '.$kUser->Name(0);
    }
    else{ $T_VAR['MSG'] = 'The root user cannot be deleted!'; }
  }else{ $T_VAR['MSG'] = 'A user ID number must be provided!'; }
  
  //Finally set the message REDIRECT
  $T_VAR['REDIRECT'] = '3;url=user.php?search';
}


//Check for [Create(add) UserType(Group) Permission] Mode
if(isset($_INPUT['addPerm']))
{
  //Set the Page Name
  $T_VAR['PAGE_NAME'] = 'Add User Permission';
  
  //Set the default HTML file to use
  $T_FILE = 'message.html';
  
  //Check for Form Submission
  if(isset($_INPUT['submit']))
  {
    if(isset($_INPUT['uPermName']))
    {
      if($_USER->NewPerm($_INPUT['uPermName']))
      {
        $T_VAR['MSG'] = 'User Permission created successfully!';
        
      }else{$T_VAR['MSG'] = 'Unable to create new user permission!';}
    }else{$T_VAR['MSG'] = 'You must provide a name for the user type!';}
    
    //Finally set the message REDIRECT
    $T_VAR['REDIRECT'] = '3;url=userA.php?addPerm';
  }
  else
  {
    //Show the Add Permission Form.
    $T_FILE = 'userAddPerm.html';
    
    $permList = $_USER->Permitted();
    if($permList !== false)
    {
      $T_COND[] = '!USER_PERMS'.count($permList);
      foreach($permList as $permName => $permValue)
      {
        //Get List of User Permissions
        $T_VAR['PERM_NAME'][] = $permName;
      }
    }else{ $T_COND[] = 'PERM_LIST'; }
  }
}


//Check for [Remove(del) UserType(Group) Permission] Mode
if(isset($_INPUT['delPerm']))
{
  //Set the Page Name
  $T_VAR['PAGE_NAME'] = 'Remove User Permission';
  
  //Set the default HTML file to use
  $T_FILE = 'message.html';
  
  //Check for Form Submission
  if(isset($_INPUT['submit']))
  {
    if(isset($_INPUT['uPermName']))
    {
      if($_USER->NewPerm($_INPUT['uPermName']))
      {
        $T_VAR['MSG'] = 'User Permission created successfully!';
        
      }else{$T_VAR['MSG'] = 'Unable to create new user permission!';}
    }else{$T_VAR['MSG'] = 'You must provide a name for the user type!';}
    
    //Finally set the message REDIRECT
    $T_VAR['REDIRECT'] = '3;url=userA.php?addPerm';
  }
  else
  {
    //Show the Add Permission Form.
    $T_FILE = 'userAddPerm.html';
    
    $permList = $_USER->Permitted();
    if($permList !== false)
    {
      $T_COND[] = '!USER_PERMS'.count($permList);
      foreach($permList as $permName => $permValue)
      {
        //Get List of User Permissions
        $T_VAR['PERM_NAME'][] = $permName;
      }
    }else{ $T_COND[] = 'PERM_LIST'; }
  }
}


//Check for [Edit UserType(Group) Permissions] Mode
if(isset($_INPUT['typePerm']))
{
  //Set the page name
  $T_VAR['PAGE_NAME'] = 'User Type Permissions';

  //Set Default file and redirect
  //template variables
  $T_FILE = 'message.html';
  $T_VAR['REDIRECT'] = '3;url=userA.php?typePerm';
  
  //Check for form submission
  if(isset($_INPUT['submit']))
  {
    //Process the submission
    if(isset($_INPUT['uType']) && !(empty($_INPUT['uType'])))
    {
      //We know for which type we are updating permissions...
      //now which permissions??
      
      //Filter out known inputs, that 
      //we don't need to go to the db
      $filter = array('typePerm', 'submit', 'uType');
      foreach($_INPUT as $key => $value)
      {
        if(!(in_array($key, $filter)))
        {
          //Add remaining values to data array
          //the only stuff left should be permission
          //name/value combinations
          $data[$key] = $value;
        }
      }//end data filtering
      
      //Add in empty values for remaining permissions
      $dibsList = $_USER->Dibs();
      $tmpList = array_keys($data);
      foreach($dibsList as $typePerm => $value)
      {
        if(!(in_array($typePerm, $tmpList)))
        {
          $data[$typePerm] = 0;
        }
      }
      
      //Attempt to update the permissions
      if($_USER->PermUpdate($_INPUT['uType'], $data))
      {
        //Success!!
        $T_VAR['MSG'] = 'Permissions Updated Successfully!!';
      }else{ $T_VAR['MSG'] = 'Permission Update failed!'; }
    }else{ $T_VAR['MSG'] = 'No User Type found!'; }
  }
  else
  {
    //Show the Edit Type Permissions Form
    $T_FILE = 'userTypePerm.html';
    
    //Retrieve a full list of permissions
    $permList = $_USER->PermList();
    if($permList !== false)
    {
      //Format the data for the template
      $t = 0;
      foreach($permList as $typePerm)
      {
        $p = 0;
        foreach($typePerm as $key => $value)
        {
          switch($key)
          {
            case $CONFIG['UserTypeID_col']:
              $T_VAR['TYPE_ID'][$t] = $value;
            break;
            
            case $CONFIG['UserTypeName_col']:
              $T_VAR['TYPE_NAME'][$t] = $value;
            break;
            
            default:
              $T_VAR['PERM_NAME'][$t][$p] = $key;
              $T_VAR['PERM_VALUE'][$t][$p] = $value;
              //Convenience template vars for the value
              $T_VAR['PERM_CHECKED'][$t][$p] = ($value != 1) ? '' : 'CHECKED';
              $T_VAR['PERM_SELECTED'][$t][$p] = ($value != 1) ? '' : 'SELECTED';
              $p++;//Increment the permission counter
            break;
          }
        }
        $t++;//Increment the type counter
      }//END MAIN LOOP
      
      //Create the conditions
      $T_COND[] = '~TYPES'.$t;
      $T_COND[] = '!PERMS'.$p;
    }else{ $T_COND[] = 'PERM_LIST'; }
  }
}//END Edit Type Permissions Mode


//Check for [Add UserType] Mode
if(isset($_INPUT['addType']))
{
  //Set the Page Name
  $T_VAR['PAGE_NAME'] = 'Add User Type';
  
  //Set the default HTML file to use
  $T_FILE = 'message.html';
  
  //Check for Form submission
  if(isset($_INPUT['submit']))
  {
    if(isset($_INPUT[$CONFIG['UserTypeName_col']]))
    {
      if($_USER->NewType($_INPUT[$CONFIG['UserTypeName_col']]))
      {
        $T_VAR['MSG'] = 'User Type created successfully!';
        
      }else{$T_VAR['MSG'] = 'Unable to create new user type!';}
    }else{$T_VAR['MSG'] = 'You must provide a name for the user type!';}
    
    //Finally set the message REDIRECT
    $T_VAR['REDIRECT'] = '3;url=userA.php?addType';
  }
  else
  {
    //Show the Add Type Form.
    $T_FILE = 'userAddType.html';
    
    $typeList = $_USER->TypeList();
    if($typeList !== false)
    {
      $T_COND[] = '!USER_TYPES'.count($typeList);
      foreach($typeList as $type)
      {
        //Get List of User Types
        $T_VAR['USER_TYPE_ID'][] = $type[0];
        $T_VAR['USER_TYPE_NAME'][] = $type[1];
      }
    }else{ $T_COND[] = 'TYPE_LIST'; }
  }
}


//Check for [Remove(del) UserType] Mode
if(isset($_INPUT['delType']))
{
  //Determine if UserTypeID provided and that UserTypeID
  //is not 0-3, these types cannot be deleted
  if(isset($_INPUT['uType']) && $_INPUT['uType'] > 3)
  {
    //Set the HTML file to use
    $T_FILE = 'message.html';
    
    //Default failure message
    $T_VAR['MSG'] = "Failed to remove User Type(Group)!";
    
    //Connect to the DB and remove this type from the Database
    $DB = new dbAccess();
    if($DB->Link())
    {
      //Attempt to remove the UserType Row from the table
      if($DB->Kill($CONFIG['UserType_Table'], $CONFIG['UserTypeID_col'].'='.$_INPUT['uType']))
      {
        //Success!!
        $T_VAR['MSG'] = "User Type(Group) has been removed successfully.";
        
      }//else Kill Failure
    }//else DB Link Failure
  }else{ $T_VAR['MSG'] = (isset($_INPUT['uType'])) ? "The User Type ID provided cannot be removed!" : "You must provide a User Type ID!"; }
  
  //Finally set the message REDIRECT
  $T_VAR['REDIRECT'] = '3;url=userA.php?typePerm';
}


//Check for [Add Status] Mode
if(isset($_INPUT['addStatus']))
{
  //Set the Page Name
  $T_VAR['PAGE_NAME'] = 'Add User Status';
  
  //Set the default HTML file to use
  $T_FILE = 'message.html';
  
  //Check for Form submission
  if(isset($_INPUT['submit']))
  {
    if(isset($_INPUT[$CONFIG['UserStatusName_col']]))
    {
      if($_USER->NewStatus($_INPUT[$CONFIG['UserStatusName_col']]))
      {
        $T_VAR['MSG'] = 'User Status created successfully!';
        
      }else{$T_VAR['MSG'] = 'Unable to create new user status!';}
    }else{$T_VAR['MSG'] = 'You must provide a name for the user status!';}
    
    //Finally set the message REDIRECT
    $T_VAR['REDIRECT'] = '3;url=userA.php?addStatus';
  }
  else
  {
    //Show the Add Status Form.
    $T_FILE = 'userAddStatus.html';
    
    $statusList = $_USER->StatusList();
    if($statusList !== false)
    {
      $T_COND[] = '!USER_STATUSES'.count($statusList);
      foreach($statusList as $status)
      {
        //Get List of User Types
        $T_VAR['USER_STATUS_ID'][] = $status[0];
        $T_VAR['USER_STATUS_NAME'][] = $status[1];
      }
    }else{ $T_COND[] = 'STATUS_LIST'; }
  }
}


//Check for [Add Attr] Mode
if(isset($_INPUT['addAttr']))
{
  //Set the Page Name
  $T_VAR['PAGE_NAME'] = 'Add User Attributes';
  
  //Set the default HTML file to use
  $T_FILE = 'message.html';
  
  //Check for Form submission
  if(isset($_INPUT['submit']))
  {
    //Verify an Attr Data has been given
    if(isset($_INPUT['attrName']))
    {
      if(isset($_INPUT['attrLabel']))
      {
        //AddAttr($name, $label, $rank = 0, $default = 'none', $vType = "text")
        $rank = (isset($_INPUT['attrRank'])) ? $_INPUT['attrRank'] : '0';
        $default = (isset($_INPUT['attrDefault'])) ? $_INPUT['attrDefault'] : 'none';
        $vType = (isset($_INPUT['attrVtype'])) ? $_INPUT['attrVtype'] : 'text';
        
        //Attempt to Add the Attribute
        if($_USER->AddAttr($_INPUT['attrName'], $_INPUT['attrLabel'], $rank, $default, $vType))
        {
          //Success!!
          $T_VAR['MSG'] = 'User Attribute created successfully!';
          
        }else{$T_VAR['MSG'] = 'Unable to create new user attribute!';}        
      }else{$T_VAR['MSG'] = 'You must provide a label for the user attribute!';}
    }else{$T_VAR['MSG'] = 'You must provide a name for the user attribute!';}
    
    //Finally set the message REDIRECT
    $T_VAR['REDIRECT'] = '3;url=userA.php?addAttr';
  }
  else
  {
    //Show the Add Status Form.
    $T_FILE = 'userAddAttr.html';
    
    //Retrieve the Attribute Index
    $attrIndex = $_USER->AttrIndex();

    //If one exists list the attributes found
    if($attrIndex != false)
    {
      $T_COND[] = '!USER_ATTRS'.count($attrIndex);
      foreach($attrIndex as $attr)
      {
        //Get List of Attr Types
        $T_VAR['USER_ATTR_ID'][] = $attr['attr_ID'];
        $T_VAR['USER_ATTR_NAME'][] = $attr['name'];
        $T_VAR['USER_ATTR_LABEL'][] = $attr['label'];
        $T_VAR['USER_ATTR_RANK'][] = ($attr['rank'] == 0) ? 'Normal' : (($attr['rank'] == 1) ? 'Join+Profile' : 'Join Only');
        $T_VAR['USER_ATTR_VTYPE'][] = $attr['vType'];
      }
    }else{ $T_COND[] = 'ATTR_LIST'; }
  }
}


//Build the template (LAST LINE OF ALL MAIN DRIVERS)
//In this file we alter the style path to "acp" sub-directory
//And attach true to the path string to signal using root header/footer
//Removing ",true" will make the template use header/footer from "acp" dir
BuildTemplate($T_FILE, $T_VAR, $T_COND, false, 'acp,true');
?>