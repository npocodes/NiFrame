<?php
/*
  Purpose:  Login Driver File - NiFrame
  
  FILE:     The Login driver file handles requests such as: forgot,
            reset, login, logout...
            
  Author:   Nathan Poole - github/npocodes
  Date:     July 2014
  Updated:  Aug 2019
*/
//Include the common file
require_once('common.php');

//First verify the user is not already logged in
if($_USER->ID() == 0)
{
  //Check if user forgot login details
  if(isset($_INPUT['forgot']))
  {
    //Set the page name
    $T_VAR['PAGE_NAME'] = 'User Forgot';
    
    //Show the forgot form
    $T_FILE = 'userForgot.html';
    
    //check for submission
    if(isset($_INPUT['submit']))
    {
      if(isset($_INPUT[$CONFIG['UserEmail_col']]))
      {
        $uEmail = $_INPUT[$CONFIG['UserEmail_col']];
        
        //Create a user key to send to the users email
        $uKey = $_USER->MakeKey($uEmail);
        if($uKey !== false)
        {
          
          //Prepare an email
          $TO = $uEmail;
          $T_VAR['FROM'] = $FROM = $CONFIG['ContactEmail'];
          $SUBJECT = $CONFIG['SiteName']." - Verification Email";
        
          $mFile = 'forgot.html';//Email file to use
          $T_VAR['USER_KEY'] = $uKey;
          $T_VAR['USER_EMAIL'] = $uEmail;
          $MSG = BuildTemplate($mFile, $T_VAR, $T_COND, true, 'email', true);
        
          $HEADER = "From: $FROM\r\n";
          $HEADER .= "Content-type: text/html\r\n";
          $mailed = mail($TO,$SUBJECT,$MSG,$HEADER); //User Mail method cannot be used here, user is a "guest".
          if($mailed)
          {
            $T_FILE = 'message.html';//Use message file now
            $T_VAR['MSG'] = 'An E-mail has been sent containing a reset link.<br> Follow the link to reset your password.';
            $T_VAR['REDIRECT'] = '3;url=index.php';
          }
        }else{ $T_VAR['MSG'] = 'Unable to determine user, contact '.$CONFIG['ContactEmail']; }
      }else{ $T_VAR['MSG'] = 'You must provide an email address!'; }
    }else{ $T_VAR['MSG'] = ''; }
  }
  else if(isset($_INPUT['reset']))
  {
    //RESET MODE!!
    
    //Set the page name
    $T_VAR['PAGE_NAME'] = 'User Reset';
    
    //Set default HTML file to use
    $T_FILE = 'message.html';
    
    //Check for a user key
    if(isset($_INPUT['uKey']) || isset($_SESSION['uKey']))
    {
      //determine if using session key or a provided key
      $_SESSION['uKey'] = $uKey = (isset($_SESSION['uKey'])) ? $_SESSION['uKey'] : $_INPUT['uKey'];
      
      //validate uKey
      if($_USER->Validate($uKey))
      {
        //Show reset form
        $T_FILE = 'userReset.html';
        
        //handle reset form
        if(isset($_INPUT['submit']))
        {
          if(isset($_INPUT[$CONFIG['UserPass_col']]))
          {
            //isolate the data to use for update
            $data[$CONFIG['UserPass_col']] = $_INPUT[$CONFIG['UserPass_col']];
            
            //attempt the update
            if($_USER->Update($data))
            {
              $T_FILE = 'message.html';//Use Message file now
              $T_VAR['MSG'] = 'Your password has been reset, you may now login.';
              $T_VAR['REDIRECT'] = '3;url=login.php';
              
              //Clear the uKey from the session
              unset($_SESSION['uKey']);
              
            }else{$T_VAR['MSG'] = 'Password update failure, please contact '.$CONFIG['ContactEmail'];}
          }else{ $T_VAR['MSG'] = 'You must supply a new password'; }
        }else{ $T_VAR['MSG'] = ''; }
      }else{ $T_VAR['MSG'] = 'The user key is invalid, please contact '.$CONFIG['ContactEmail']; }
    }else{ $T_VAR['MSG'] = 'You must provide a user key'; }
    //END RESET MODE
  }
  else if(isset($_INPUT['join']))
  {
    //JOIN MODE!!
    
    $T_VAR['PAGE_NAME'] = 'Registration';
    
    //Set default HTML file to use
    $T_FILE = 'message.html';
    
    //Set default Redirect
    $T_VAR['REDIRECT'] = '5;url=login.php?join';
    
    //Check for form submission
    if(isset($_INPUT['submit']))
    {
      //First before anything check ReCAPTCHA
      //Unless its been disabled...
      if(RECAPTCHA)
      {
				//RECAPTCHA is enabled, check reCAPTCHA v2
				$verifySite = 'https://www.google.com/recaptcha/api/siteverify';
				$verifyData['secret'] = $CONFIG['SecretKey'];
				$verifyData['response'] = $_INPUT['g-recaptcha-response'];
				$verifyOpts['http']['method'] = 'POST';
				$verifyOpts['http']['content'] = http_build_query($verifyData);
				$verifyContext = stream_context_create($verifyOpts);
				$verify = file_get_contents($verifySite, false, $verifyContext);
				$captcha_valid = json_decode($verify);
				$validCaptcha = $captcha_valid->success;
      }
      else
      {
        //RECAPTCHA is disabled...
        //proceed as if its valid
        $validCaptcha = true;
      }
      
      if($validCaptcha)
      {
        //ReCAPTCHA VALID or disabled...
        //Verify email has been given
        if(isset($_INPUT[$CONFIG['UserEmail_col']]) && !(empty($_INPUT[$CONFIG['UserEmail_col']])))
        {
          //Verify password has been given
          if(isset($_INPUT[$CONFIG['UserPass_col']]) && !(empty($_INPUT[$CONFIG['UserPass_col']])))
          {
            //Create list of input names to skip
            $skipList = array(
              'submit', 
              'join', 
              'confPass',
              $CONFIG['UserEmail_col'],
              $CONFIG['UserPass_col'],
              'g-recaptcha-response'
            );
            
            //Cycle each input element
            foreach($_INPUT as $key => $value)
            {
              if(!(in_array($key, $skipList)))
              {
                $data[$key] = $value;
              }
            }
       
            //Attempt to create the new user account
            $newUserID = $_USER->Create($_INPUT[$CONFIG['UserEmail_col']], $_INPUT[$CONFIG['UserPass_col']], USER, $data);
            if($newUserID)
            {
              //Success, A user has been created now.  
              //Change redirection to the index
              $T_VAR['REDIRECT'] = '3;url=index.php';
              
              //Create a new user object for this user
              $NewUser = new user($newUserID);
              
              //Get a key for this user to use for email verification
              $uKey = $NewUser->MakeKey($NewUser->Email());
              if($uKey !== false)
              {
                //Choose what HTML file to use for the message
                $mFile = 'verify.html';//Message() method automatically
                //looks in the "email" directory for these files.
								
								//Create message template variables
                $mVar['SCRIPT_PATH'] = $SCRIPT_PATH;
                $mVar['SITE_NAME'] = $CONFIG['SiteName'];
                $mVar['USER_NAME'] = $NewUser->Name();
                $mVar['USER_EMAIL'] = $NewUser->Email();
                $mVar['USER_PASS'] = $_INPUT[$CONFIG['UserPass_col']];
                $mVar['USER_KEY'] = $uKey;
                $mVar['CONTACT_EMAIL'] = $CONFIG['ContactEmail'];
                
                //Finally try to send the user a verification email
                if($NewUser->Message($mFile, $mVar, null, 'Account Activation'))
                {
                  //Report success to the user.
                  $T_VAR['MSG'] = 'User created successfully!<br>';
									$T_VAR['MSG'] .= 'An activation link has been sent to your email.';
                }
                else
                {
                  //Failed to send email to user
                  $T_VAR['MSG'] = 'Unable to send account activation email! Please contact Admin!';
                }
              }else{ $T_VAR['MSG'] = 'Failed to make Verification Key! Please contact Admin!'; }
            }else{ $T_VAR['MSG'] = $_USER->Error(); }
          }else{ $T_VAR['MSG'] =  'No password has been given'; }
        }else{ $T_VAR['MSG'] = 'No user email has been given'; }
      }
      else
      { 
        $T_VAR['MSG'] = 'The ReCAPTCHA entered was in-valid, try again.';
        
        //Store the recaptcha error
				$_SESSION['reCAPTCHA_Error'] = $captcha_valid->error-codes;
      }
    }
    else
    { 
      // SHOW REGISTRATION FORM!
      
      $T_FILE = 'register.html';//Registration form 
      $T_VAR['MSG'] = 'Joining is Free! Just provide a few details about yourself below to create your user account.';
      
      //Attempt to add in user-defined attributes, if available
      //Retrieve any possible User-Defined User Attributes
      $attrIndex = $_USER->AttrIndex();
			$attrCount = 0;
      if(!($attrIndex === false))
      {
        foreach($attrIndex as $attr)
        {
          //Only take those attributes that are
          //ranked for Joining.
          //0 = normal, 1 = normal+Join, 2 = Join Only
          if($attr['rank'] == 1 || $attr['rank'] == 2)
          {
            $T_VAR['USER_ATTR_NAME'][] = $attr['name'];
            $T_VAR['USER_ATTR_LABEL'][] = $attr['label'];
            $T_VAR['USER_ATTR_VTYPE'][] = $attr['vType'];
						$attrCount++;
          }
        }
        
        //Set the HTML repeat COND for the attr HTML
        $T_COND[] = '!USER_ATTRS'.$attrCount;
        
      }else{ $T_COND[] = 'USER_ATTR_LIST'; }      
    }
    //END JOIN MODE
  }
  else if(isset($_INPUT['act']))
  {
    //ACTIVATION MODE!!
    
    //Set the page name
    $T_VAR['PAGE_NAME'] = 'Account Activation';
    
    //Set HTML display file to use
    $T_FILE = 'message.html';
    $T_VAR['REDIRECT'] = '3;url=index.php';
    
    //Check for user's activation key
    if(isset($_INPUT['uKey']) && !(empty($_INPUT['uKey'])))
    {
      //Attempt to validate the user key given
      if($_USER->Validate($_INPUT['uKey']))
      {
        //Key has been validated
        //Update the users status to Active
        $data[$CONFIG['UserStatus_col']] = 1;
        if($_USER->Update($data))
        {
          //User's Account has been Activated!!
          $T_VAR['MSG'] = 'Account Activated successfully!';
          $T_VAR['REDIRECT'] = '3;url=login.php';
          
        }else{ $T_VAR['MSG'] = 'Failed to Update user data! Please contact the Admin.'; }
      }else{ $T_VAR['MSG'] = 'Activation Key is not valid!'; }
    }else{ $T_VAR['MSG'] =  'Unable to locate the Activation Key!'; }
    //END ACTIVATION MODE
  }
  else
  {
    //LOGIN MODE!!
    
    //Set the page name
    $T_VAR['PAGE_NAME'] = 'User Login';
    
    //Set template to Display Login Form
    $T_FILE = 'login.html';
    
    //Check for form submission
    if(isset($_INPUT['submit']))
    {   
      //Form Submitted, check for required arguments
      if(isset($_INPUT['userNE']) && !(empty($_INPUT['userNE'])))
      {
        if(isset($_INPUT['user_pass']) && !(empty($_INPUT['user_pass'])))
        {
          //Try to login the user
          $remember = (isset($_INPUT['RememberMe'])) ? $_INPUT['RememberMe'] : false;
          if($_USER->Login($_INPUT['userNE'], $_INPUT['user_pass'], $remember))
          { 
            //Login success, inform the user 
            $T_FILE = 'message.html'; //Override login display for message display
            
            $T_VAR['MSG'] = 'Welcome back, '.$_USER->Name(0);//Only using the first name
            
            //Figure out where they should go now
            //based on their permissions.
            switch($_USER->Type())
            {
              //Admin
              case 1:
                $T_VAR['REDIRECT'] = '3;url=index.php';
              break;
              
              //Tech
              case 2:
                $T_VAR['REDIRECT'] = '3;url=index.php';
              break;
              
              //User/Guest
              default:
                  $T_VAR['REDIRECT'] = '3;url=index.php';
              break;
            }
          }else{ $T_VAR['MSG'] = $_USER->Error(); }
        }else{ $T_VAR['MSG'] = 'User Password is required!'; }
      }else{ $T_VAR['MSG'] = 'User Email is required!'; }
    }else{ $T_VAR['MSG'] = ''; }
    //END LOGIN MODE
  }
  //END MODE CHAIN
}
else
{
  //LOGOUT MODE!!
  
  //Set the page name
  $T_VAR['PAGE_NAME'] = 'User Logout';
  
  //Set HTML display file to use
  $T_FILE = 'message.html';
  
  //User is logged in, log them out.
  $T_VAR['MSG'] = ($_USER->Logout()) ? 'Come back soon!' : $_USER->Error();
  $T_VAR['REDIRECT'] = '3;url=index.php';
  
  //END LOGOUT MODE
}

//Build the template (LAST LINE OF ALL MAIN DRIVERS)
BuildTemplate($T_FILE, $T_VAR, $T_COND);
?>