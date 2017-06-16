<?php
/*
  Purpose:  System Administration Driver File - NiFrame
  
  FILE:     The System Administration driver file contains modes that allow
            users with ACP privileges to manage system settings such as:
            setting the default Time Zone, Time Format 12/24hr, Style, etc...
            
  Author:   Nathan Poole - github/npocodes
  Date:     July 2014
*/
//Include the common file
require_once('common.php');

//Require User Login
if(!($_USER->UnPack())){ header("location: login.php"); die(); }

//set the default HTML file to use
$T_FILE = 'message.html';

//Set default message
$T_VAR['MSG'] = '';


//Check for [Settings] Mode
if(isset($_INPUT['settings']))
{
  //Set the Page Name
  $T_VAR['PAGE_NAME'] = 'System Settings';
  
  //Set the default redirect for this Mode
  $T_VAR['REDIRECT'] = '3;url=index.php';

  //Require Admin Privileges
  if($_USER->Permitted('ACP') || $_USER->ID() == 1)
  {
    //Check for form submission
    if(isset($_INPUT['submit']))
    {
      //Alter redirect back to settings page.
      $T_VAR['REDIRECT'] = '3;url=sysA.php?settings';
      
      //Check for Login Acceptance setting (logNE)
      if(isset($_INPUT['logNE']) && $_INPUT['logNE'] != LOG_NE)
      {
        $fileData = file_get_contents('inc/const.php');
        if($fileData !== false)
        {
          //Attempt to update the data
          $replace = 'define("LOG_NE", "'.LOG_NE.'");';
          $with = 'define("LOG_NE", "'.$_INPUT['logNE'].'");';
          $newData = str_replace($replace, $with, $fileData);
          if(file_put_contents('inc/const.php', $newData) !== false)
          {
            //Verify changes occurred
            $reRead = file_get_contents('inc/const.php');
            if($newData == $reRead)
            {
              //Success
              switch($_INPUT['logNE'])
              {
                //Nickname only
                case 2:
                  $T_VAR['MSG'] = 'Login Identifier now accepts <b>only</b> Nicknames.<br>';
                break;
                
                //Email Only
                case 1:
                  $T_VAR['MSG'] = 'Login Identifier now accepts <b>only<b> Emails.<br>';
                break;
                
                //Both Email + Nickname
                default:
                  $T_VAR['MSG'] = 'Login Identifier now accepts both Emails + Nicknames.<br>';
                break;
              }
            }else{ $T_VAR['MSG'] = 'Failed to update login acceptance level!'; }              
          }else{ $T_VAR['MSG'] = 'Failed to write new login acceptance data!'; }
        }else{ $T_VAR['MSG'] = 'Unable to update login acceptance data!'; }
      }//End Login Acceptance Option
      
      if(isset($_INPUT['recaptcha']) && $_INPUT['recaptcha'] != RECAPTCHA)
      {
        $fileData = file_get_contents('inc/const.php');
        if($fileData !== false)
        {
          //Attempt to update the data
          $replace = 'define("RECAPTCHA", "'.RECAPTCHA.'");';
          $with = 'define("RECAPTCHA", "'.$_INPUT['recaptcha'].'");';
          $newData = str_replace($replace, $with, $fileData);
          if(file_put_contents('inc/const.php', $newData) !== false)
          {
            //Verify changes occurred
            $reRead = file_get_contents('inc/const.php');
            if($newData == $reRead)
            {
              //SUCCESS MSG!!
              switch($_INPUT['recaptcha'])
              {
                //RECAPTCHA is disabled
                case 1:
                  $T_VAR['MSG'] .= ''.PHP_EOL.'reCAPTCHA is now enabled.<br>';
                break;
                
                default:
                  $T_VAR['MSG'] .= ''.PHP_EOL.'reCAPTCHA is now disabled.<br>';
                break;
              }
            }else{ $T_VAR['MSG'] = 'Failed to update recaptcha data!'; }  
          }else{ $T_VAR['MSG'] = 'Failed to write new recaptcha data!'; }
        }else{ $T_VAR['MSG'] = 'Unable to update login acceptance data!'; }
      }//End Recaptcha Enable/Disable option
      
    }
    else
    {
      //Select the currently set acceptance level
      $T_VAR['SELECT_BOTH']   = (LOG_NE == 0) ? 'SELECTED' : '';
      $T_VAR['SELECT_EMAIL']  = (LOG_NE == 1) ? 'SELECTED' : '';
      $T_VAR['SELECT_NICK']   = (LOG_NE == 2) ? 'SELECTED' : '';
      
      //Check the currently set reCAPTCHA option on/off
      $T_VAR['RECAPTCHA_OFF'] = (RECAPTCHA == 0) ? 'SELECTED' : '';
      $T_VAR['RECAPTCHA_ON']  = (RECAPTCHA == 1) ? 'SELECTED' : '';
      
      //Display the settings form
      $T_FILE = 'settings.html';
    }
  }
}


//Check for [Style] Mode
if(isset($_INPUT['styles']))
{
  //Set the Page Name
  $T_VAR['PAGE_NAME'] = 'Manage Styles';
  
  //Set the default redirect for this Mode
  $T_VAR['REDIRECT'] = '3;url=index.php';
  
  //Require Admin Privileges
  if($_USER->Permitted('ACP') || $_USER->ID() == 1)
  {
    //Retrieve a list of available styles
    //using a template object
    $Tpl = new template(STYLE);
    $styleList = $Tpl->GetStyles();
    if($styleList !== false)
    {
      //Cycle the styleList and create
      //the HTML variables for the template
      $s = 0;
      foreach($styleList as $styleName)
      {
        //Add styleName to the Template var
        $T_VAR['STYLE_NAME'][$s] = $styleName;
        
        //Select or Check the default style
        $T_VAR['STYLE_SELECTED'][$s] = ($styleName == STYLE) ? 'SELECTED' : '';
        $T_VAR['STYLE_CHECKED'][$s] = ($styleName == STYLE) ? 'CHECKED' : '';
        
        $s++;
      }
      
      //Set the style repeat condition
      $T_COND[] = '!STYLE_OPTS'.count($styleList);
      
      //Check for form submission
      if(isset($_INPUT['submit']))
      {
        //PROCESS SUBMISSION
        if(isset($_INPUT['style']) && !(empty($_INPUT['style'])))
        {
          //Handle 2Btn input scenario (so you can set user friendly submit button text)
          $_INPUT['styles'] = (isset($_INPUT['killStyle'])) ? 'kill' : 'set';
          //If not using 2submit btns, just set "styles" = kill || set(default)
          
          //Determine what to do with the style
          if($_INPUT['styles'] != 'kill')
          {
            //SETTING NEW STYLE AS DEFAULT..
            //Read in the constants file
            $fileData = file_get_contents('inc/const.php');
            if($fileData !== false)
            {
              //Attempt to update the data
              $replace = 'define("STYLE", "'.STYLE.'");';
              $with = 'define("STYLE", "'.$_INPUT['style'].'");';
              $newData = str_replace($replace, $with, $fileData);
              if(file_put_contents('inc/const.php', $newData) !== false)
              {
                //Verify changes occurred
                $reRead = file_get_contents('inc/const.php');
                if($newData == $reRead)
                {
                  $T_VAR['MSG'] = 'Default style set as '.$_INPUT['style'].' successfully!';
                  
                }else{ $T_VAR['MSG'] = 'Failed to update default style!'; }              
              }else{ $T_VAR['MSG'] = 'Failed to write new style data!'; }
            }else{ $T_VAR['MSG'] = 'Unable to update default style!'; }
          }
          else
          {
            //REMOVING THE STYLE..
            //Verify that the style given is not the default style
            //If its not the default style than delete the style dir
            if(in_array($_INPUT['style'], $styleList))
            {
              if($_INPUT['style'] != STYLE)
              {
                //Remove the style directory using the common 
                //function "Expel()", then verify it is gone.
                Expel('style/'.$_INPUT['style']);
                if(!(file_exists('style/'.$_INPUT['style'])))
                {
                  //Success
                  $T_VAR['MSG'] = 'Style, '.$_INPUT['style'].', has been removed successfully!';
                  
                }else{ $T_VAR['MSG'] = 'Failed to remove style, '.$_INPUT['style'].'!'; }
              }else{ $T_VAR['MSG'] = STYLE.' is the current default style, it cannot be deleted!'; }
            }else{ $T_VAR['MSG'] = $_INPUT['style'].' does not exist!'; }
          }
        }else{ $T_VAR['MSG'] = 'No Style Name given!'; }
      }
      else
      {
        //Display the add user form
        $T_FILE = 'styles.html';
      }
    }else{ $T_VAR['MSG'] = "No Viable Styles found!"; }
  }else{ header("Location:index.php"); }
}


//Build the template (LAST LINE OF ALL MAIN DRIVERS)
//In this file we alter the style path to ACP sub-directory
//And attach true to the path string to signal using root header/footer
BuildTemplate($T_FILE, $T_VAR, $T_COND, false, 'acp,true');
?>