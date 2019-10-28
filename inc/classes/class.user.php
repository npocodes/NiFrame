<?php
/*
  Purpose:  NiFrame
  
  File:     User Class File
  
  Author:   Nathan Poole - github/npocodes
           
  Date:     July 2014
     
  Updated:  2/01/2019
           
	[ !! This Class Utilizes a configuration file !! ]
*/
//require_once('inc/classes/class.error.php');
//require_once('inc/classes/class.dbAccess.php');
//require_once('inc/classes/class.attr.php');

//++++++++++++++++++++//
//++ THE USER CLASS ++//
//++++++++++++++++++++//
/*
  User class provides all base user attributes
  as well as associated user methods.
*/
class user extends attr {

//-------------------------------//
      //%% Attributes %%//
//-------------------------------//
  protected $user_ID;         //The users ID
  protected $user_type;       //User Type: (ID, Name)
  protected $user_status;     //User Status: (ID, Name)
  protected $user_name;       //User Full Name (Comprised of First and Last Name)
  protected $user_nick;       //User Nickname (Screen/Display Name)
	protected $user_avatar;			//User Avatar (URL)
  protected $user_email;      //User Email Address
  protected $user_phone;      //User Phone Number
  protected $user_carrier;    //User Phone Service Carrier: (ID, Name)
	protected $user_joined;			//User Join date (timestamp)
	protected $user_lastLogin;	//User Last Login (timestamp)
  protected $userPermList;    //List of user's permissions based on userType  
  protected $userTypeList;    //List of possible user types
  protected $userStatusList;  //List of possible user statuses
  protected $typePermList;    //Full list of user type permissions (all types all permissions)

  
//-------------------------------------------//
            //## Methods ##//
//-------------------------------------------//

  ///////////////////
  /// Constructor ///
  ///////////////////
  /*
    This constructor will initialize attributes to their default values and if provided with a 
    user ID will retrieve the associated user information and update the attributes to match.
  */
  function __construct($userID = 0) 
  {
    //Initialize all default values...
    parent::__construct();
    
    //User Specific Attributes
    $this->user_ID = 0;                         //The users ID
    $this->user_type = array(0, 'unknown');     //User Type: (ID, Name)
    $this->user_status = array(0, 'unknown');   //User Status: (ID, Name)
    $this->user_name = 'unknown';               //Users Full Name (First Middle Last)
    $this->user_nick = 'unknown';               //Users Nick Name (Screen name)
		$this->user_avatar = '#';										//Users Avatar (URL)
    $this->user_email = 'unknown';              //Users Email Address
    $this->user_phone = 0;                      //Users Phone Number
    $this->user_carrier = array(0, 'unknown');  //User Phone Carrier: (ID, Name)
		$this->user_joined = 0;											//User Join Date (timestamp)
		$this->user_lastLogin = 0;									//User Last Login (timestamp)
    $this->userPermList = array();              //List of user's permissions 
                                                //ex: Array(PermName, true | false)
    
    //General Attributes
    $this->userTypeList = array();              //List of possible user types
    $this->userStatusList = array();            //List of possible user statuses
    $this->typePermList = array();              //Full list of user Type permissions
    
    //Check for provided user_ID
    if($userID != 0)
    {
      //Get user details
      $this->Initialize($userID);
    }
  }
  //End Constructor Method
	
  
  ///////////////////
  /// GET Methods ///
  ///////////////////
  public function ID(){ RETURN $this->user_ID; }
  public function Email(){ RETURN $this->user_email; }
	public function Avatar(){ RETURN $this->user_avatar; }
  public function Phone($i = null){ RETURN ($i == null || $i < 1) ? $this->user_phone : $this->user_carrier[($i-1)]; }//(Phone#, CarrierID, CarrierName)
  public function Type($index = 0){ RETURN $this->user_type[$index]; }
  public function Status($index = 0){ RETURN $this->user_status[$index]; }
  public function JoinDate(){ RETURN $this->user_joined; }//{Timestamp}
	public function LastLogin(){ RETURN $this->user_lastLogin; }//{Timestamp}
	
  
  ///////////////////
  /// Name Method ///
  ///////////////////
  /*
    Given an index number (0-2) for a total of 3 possible name elements, this method will return
    the corresponding name portion. If a 'null' value or value outside the range is given then the 
    full name is returned. examples: 
      Name(), First M. Last
      Name(0), FirstName
      Name(1), MiddleName
      Name(2), LastName
      Name(purple), First M. Last
      Name(3), First M. Last
  */
  public function Name($index = null) 
  {
    //Allow indexing of the name parts
    if(!($index === null) && ($index >= 0 && $index <= 2))
    {
      //Split the name into parts
      $tmpName = explode(' ', $this->user_name);
      
      //Retrieve only the specific portion
      //unknown portions are returned 'unknown'
      $name = trim($tmpName[$index]);

      //catch no name case
      $name = (empty($name)) ? 'unknown' : $name;
    }
    else
    {
      //Whole name, strip out 'unknown' portions
      $name = str_replace('unknown', '', $this->user_name);
    }
    //RETURN the name!!!lol sigh
    RETURN $name;
  }
  
  
  //////////////////////////
  /// Nick Name Method/s ///
  //////////////////////////
  public function Nick(){ RETURN $this->user_nick; }
  public function Nickname(){ RETURN $this->Nick(); }
  public function ScreenName(){ RETURN $this->Nick(); }
  public function DisplayName(){ RETURN $this->Nick(); }

  
  //----------------//
  //- Login Method -//
  //----------------//
  /*
    This method logs the user into the system by hashing the pass provided by the user
    and matching it to the hash stored in the database associated with the userEmail provided.
  */
  final public function Login($userNE, $userPass, $remember = false)
  {
		//Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //First create a dbAccess object
    $DB = new dbAccess();
    
    //Try to link to the DB
    if($DB->Link())
    {
      //Scramble and Cook the given password, then unscramble it
      //This gives us the hash value for the password
      $hashPass = $this->UnScramble($this->Scramble($userPass, true));
      
      //Create a where clause using the Login 
      //Identifier given to narrow our search
      $whereLoc = array();//Make it an array.
      switch(LOG_NE)
      {
        //Check Only Nickname
        case 2:
          $whereLoc[0] = $CONFIG['UserNickName_col'].'='.$userNE;
        break;
        
        //Check Only Email
        case 1:
          $whereLoc[0] = $CONFIG['UserEmail_col'].'='.$userNE;
        break;
        
        //Check Both Email and Nickname
        default:
          $whereLoc[0] = $CONFIG['UserEmail_col'].'='.$userNE;
          $whereLoc[1] = $CONFIG['UserNickName_col'].'='.$userNE;
        break;
      }
      
      //try to login using each whereLoc, if first is a failure
      for($try = 0; $try < count($whereLoc); $try++)
      {
        //Now try to Snatch the stored info associated with the identifier provided by the user
        //pass, status, id
        $fieldList = array($CONFIG['UserPass_col'], $CONFIG['UserStatus_col'], $CONFIG['UserID_col']);
        if($DB->Snatch($CONFIG['User_Table'], $fieldList, $whereLoc[$try]))
        {
          //Request the results from the dbAccess Obj
          $data = $DB->Result();

          //Compare password hashes
          if($hashPass == $this->UnScramble($data[$CONFIG['UserPass_col']]))
          {
            //Verify the user account is not in-active(0) && not banned(2)
            if($data[$CONFIG['UserStatus_col']] != 0 && $data[$CONFIG['UserStatus_col']] != 2)
            {
              //Regenerate the session id, carrying over any session data.
              //This will help secure against session hi-jacking.
              session_regenerate_id(true);
              
              //Set the ID for this user
              $this->user_ID = $data[$CONFIG['UserID_col']];
              
              //Initialize this Object with the users data
              if($this->Initialize())
              {
                //Check if the user wishes to be remembered.
                if($remember)
                {
                  //%% Create Cookie Key
                  /*
                    Cookies for logging in is already a pretty decent security risk, IMO.
                    Cookies set through this system	last only 1 day, do not contain a password at any time,
                    all information used is hashed and salted heavily, and strict domain/protocol is enabled.
                  */
                  //Make the base key using unique (non-sensitive) user details to make a hash, hashes are made using
                  //the scramble function with cook option and then unscrambling it to obtain the hash.
                  $baseKey = $this->UnScramble($this->Scramble($this->user_ID.$this->user_type.$this->user_name, true));
                  
                  //Imprint the base key with the current time in seconds
                  //This adds an extra bit of security since the time imprinted
                  //will likely never be the same.
                  $rawKey = time().$baseKey.time();
                  
                  //Get the domain, Removing www if it exists to prevent bugs
                  $patt = '/^[wW]{3}[\.]{1}$/';//matches www. or WWW. or wWw. etc..
                  $domain = preg_replace($patt, ".", $_SERVER['HTTP_HOST']);
                  
                  //Create the cookie key(token), Cookie Key = scrambled RawKey
                  //Again this increases the security since the actual key is encrypted.
                  /* Lasts 24 hrs */
                  setcookie("YEKC", $this->Scramble($rawKey), time()+60*60*24*1, '/', $domain, false, true);
                  
                  //Finally store the rawKey in the database for future reference.
                  //The rawKey is NOT encrypted in the DB, if the DB is some how compromised
                  //and a hacker obtains the rawKey, it still cannot be used to login
                  //through the remember method since remember expects the key to be encrypted.
                  $whereLoc = $CONFIG['UserID_col'].'='.$this->user_ID;
									$fieldList = array($CONFIG['UserCode_col'], $CONFIG['UserLastLogin_col']);
									$valueList = array($rawKey, time());
                  if(!($DB->Refresh($CONFIG['User_Table'], $fieldList, $valueList, $whereLoc)))
                  {
                    $this->LogError($DB->Error());
                  }
                }//End Check Remember
								else
								{
									//Record the login timestamp
									$whereLoc = $CONFIG['UserID_col'].'='.$this->user_ID;
									if(!($DB->Refresh($CONFIG['User_Table'], $CONFIG['UserLastLogin_col'], time(), $whereLoc)))
									{
										$this->LogError($DB->Error());
									}
								}
                
                //Sever DB Link
                $DB->Sever();
                
                //Pack the user data back into 
                //the session for future reference
                $this->Pack();
                
                //Login Successful!
                RETURN true;
              }
            }
            else
            {
              //User Not Active!
              //Log error to file and store for use.
              $this->LogError($this->error = 'User '.$userNE.' is not Active!');
              break;//User credentials are fine but user is not active, don't try with Nickname now
              //break is redundant if fail is on Nickname try..
            }
          }
          else
          {
            //Passwords do not match!
            //Log error to file and store for use.
            $this->error = 'Invalid Password!';
            $this->LogError($this->error.' - '.$userNE.' - '.gethostbyname($_SERVER['REMOTE_ADDR']));
            break;//User failed to give good password using email, don't try with Nickname now
            //break is redundant if fail is on Nickname try.. 
          }
        }
        else
        {
          //User was not found!
          //Log error to file and store for use.
          $this->error = 'The user '.$userNE.' was not found!'.PHP_EOL;
          //$this->LogError('User not found! -- user::Login()');
          //Run again using Nickname as user identifier.
        }   
      }//Login Identifier Loop END
      $DB->Sever();
    }
    else
    {
      //No DB Link
      //Log error to file and store for use.
      $this->error = 'No Data Link!';
      $this->LogError('No Data Link! -- user::Login()');
    }
    //Failure
    RETURN false;
  }
  //END Login

  
  //-------------------//
  //- Remember Method -//
  //-------------------//
  /*
    This method uses a unique userCode stored within a cookie on the users
    machine to validate them by matching the unique code to the userCode stored
    in the database. The associated user is then logged into the system.
  */
  final public function Remember($cKey)
  {
		//Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
		
		//Connect to DB
		$DB = new dbAccess();
		if($DB->Link())
		{
			//Find the User Associated to the key provided		
			$decoded = $this->UnScramble($cKey);
			$whereLoc = $CONFIG['UserCode_col']."=".$decoded;
			
      //Attempt to find the user
			if($DB->Snatch($CONFIG['User_Table'], '*', $whereLoc))
			{
        //Get the results
        $Result = $DB->Result();
        
				//User Located..Log them in
				//############################//
				//## START NEW USER SESSION ##//
				//############################//
				//Start a fresh session	for the user.
				session_regenerate_id(true);
				
        //Get the ID for this user
        $this->user_ID = $Result[$CONFIG['UserID_col']];
        
        //Initialize this Object with the users data
        if($this->Initialize()) 
        {
          //Verify user is active
          if($this->user_status != 0)
          {
            /* REMEMBER THE USER AGAIN */
            //See LOGIN method for descriptions
            $baseKey = $this->UnScramble($this->Scramble($this->user_ID.$this->user_type.$this->user_name, true));
            $rawKey = time().$baseKey.time();
            
            //Get the domain, Removing www if it exists to prevent bugs
            $patt = '/^[wW]{3}[\.]{1}$/';//matches www. or WWW. or wWw. etc..
            $domain = preg_replace($patt, ".", $_SERVER['HTTP_HOST']);
            
            /* Lasts 24 hrs */
            setcookie("YEKC", $this->Scramble($rawKey), time()+60*60*24*1, '/', $domain, false, true);
            
            //Store the key for later
            $whereLoc = $CONFIG['UserID_col'].'='.$this->user_ID;
						$fieldList = array($CONFIG['UserCode_col']. $CONFIG['UserLastLogin_col']);
						$valueList = array($rawKey, time());
            if(!($DB->Refresh($CONFIG['User_Table'], $fieldList, $valueList, $whereLoc)))
            {
              $this->LogError($DB->Error());
            }
              
            //Sever DB Link
            $DB->Sever();
            
            //Pack the user data back into 
            //the session for future reference
            $this->Pack();
            
            //Login Successful!
            RETURN true;
            
          }else{ $this->error = 'User is not Active'; }
        }else{ $this->LogError('Initialization failure -- user::Remember()'); }
			}else{ if($DB->Error() != '0 results found'){$this->LogError('Snatch failure -- user::Remember()'.PHP_EOL.$DB->LastQuery());} }
      $DB->Sever();//Sever DB Link
		}else{ $this->LogError('No Data Link! -- user::Remember()'); }
    
    //Failure
    RETURN false;
  }
  //END Remember
  
  
  //-----------------//
  //- Logout Method -//
  //-----------------//
  /*
    This method logs the user out of the system 
    by deleting the users session data and any cookies.
  */
  final public function Logout()
  {
		//Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
		//Kill Cookie Key
		setcookie("YEKC", "", time()+60*60*24*0, '/', "", false, false);
		
		//Clear Cookie Key from the DB
		$DB = new dbAccess();
		if($DB->Link())
		{	
			//Create junk value to store in code column
			$junk = $this->Scramble('empty'.rand());
      
      //Isolate the user we want to target with where clause
			$whereLoc = $CONFIG['UserID_col']."=".$this->user_ID;
      
      //Refresh the database with the junk key
			$Removed = $DB->Refresh($CONFIG['User_Table'], $CONFIG['UserCode_col'], $junk, $whereLoc);
			if(!($Removed))
			{
				//Failed to update Ckey, log the error
				$this->LogError("Failed to update Ckey :".$DB->Error());
        
        //Sever the DB Link
				$DB->Sever();
				RETURN false;
			}
      $DB->Sever();
		}
    else
    {
      //No DB Link
      $this->LogError($DB->Error()); 
      RETURN false;
    }

		//Kill the session cookie
		setcookie("PHPSESSID", "", 0, '/', "", false, false);
		
		//Kill All $_SESSION Variables
		unset($_SESSION);
		
		//Regenerate the session ID for good measure
		session_regenerate_id(true);
		
		//Destroy the user session
		session_destroy();
		
		//Return successful
		RETURN true;
  }
  //END Logout

  
  //-------------------//
  //- Make Key Method -//
  //-------------------//
  //Generates User key
  final public function MakeKey($email = 'unknown')
  {
		//Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //Verify we have an email to work with
    $uEmail = ($email == 'unknown') ? $this->Email() : $email;
    if($uEmail == 'unknown'){ RETURN false; }
    
    //Create new dbAccess object
    //and establish a link to DB
    $DB = new dbAccess();
    if($DB->Link())
    {
      //Validate email and get UserID
      $whereLoc = $CONFIG['UserEmail_col'].'='.$uEmail;
      if($DB->Snatch($CONFIG['User_Table'], $CONFIG['UserID_col'], $whereLoc))
      {
        //Retrieve data
        $data = $DB->Result();
        $uID = $data[$CONFIG['UserID_col']];
        if($this->user_ID == 0){ $this->user_ID = $uID; }//For convenience save this information
        
        //create the forgot key and urlencode it
        $raw_uKey = $this->UnScramble($this->Scramble(time().$uID.$CONFIG['SiteName'].$uEmail.time(), true));

        $encoded_uKey = urlencode($raw_uKey.'__'.$uID);
        
        //Add the raw key to the users data
        $whereLoc = $CONFIG['UserID_col'].'='.$uID;
        if($DB->Refresh($CONFIG['User_Table'], $CONFIG['UserCode_col'], $raw_uKey, $whereLoc))
        {
          //return the uKey
          $DB->Sever();//Sever DB Link
          RETURN $encoded_uKey;
          
        }else{ if($DB->Error() != '0 rows affected'){ $this->LogError('Refresh Failure -- user::MakeyKey()'); } }
      }else{ if($DB->Error() != '0 results found'){ $this->LogError('Snatch Failure -- user::MakeyKey()'); } }
      $DB->Sever();//Sever DB Link
    }else{ $this->LogError('No Data Link! -- user::MakeKey()'); }
    
    //Failure
    RETURN false;
  }
  
 
  //-------------------//
  //- Validate Method -//
  //-------------------//
  //validates User Key
  final public function Validate($encoded_uKey)
  {
		//Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //ukeys have the users ID appended to the ends
    //and they are scrambled and urlencoded
    $decoded_uKey = urldecode($encoded_uKey);
    $uKey_array = explode('__', $decoded_uKey);
    $uKey = $uKey_array[0];
    $this->user_ID = $uKey_array[1];

    $DB = new dbAccess();
    if($DB->Link())
    {
      $whereLoc = $CONFIG['UserID_col'].'='.$this->user_ID;
      if($DB->Snatch($CONFIG['User_Table'], $CONFIG['UserCode_col'], $whereLoc))
      {
        $data = $DB->Result();
        $db_uKey = $data[$CONFIG['UserCode_col']];
        
        //Compare key values
        if($uKey == $db_uKey)
        {
          //Initialize this user object
          if($this->Initialize())
          {
            //Success!!
            $DB->Sever();
            RETURN true;
          }
        }
      }else{ $this->LogError('Snatch failure -- user::Validate()'); }
      $DB->Sever();//Sever DB Link
    }else{ $this->LogError('No Data Link! -- user::Validate()'); }
    
    //Failure
    RETURN false;
  }
  

  //-----------------//
  //- Create Method -//
  //-----------------//
  /*
    This method uses the provided data to create a new user.
    E-mail, password, and userTypeID are required. Email and Nickname
		are subject to uniqueness verification.
    RETURNS: userID or false
  */ 
  public function Create($email, $password, $userTypeID, $data)
  {
 		//Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //Create link to database
    $DB = new dbAccess();
    if($DB->Link())
    {
      //Verify the email given doesn't already exist
      $whereLoc = $CONFIG['UserEmail_col'].'='.$email;
      if($DB->Snatch($CONFIG['User_Table'], $CONFIG['UserEmail_col'], $whereLoc))
      {
        //Email Exists
        $this->error = $email.' already exists!';
        $DB->Sever();
        RETURN false;
      }
      
			//If Given, Verify the nick name given doesn't already exist
			if(isset($data[$CONFIG['UserNickName_col']]))
			{
				$whereLoc = $CONFIG['UserNickName_col'].'='.$data[$CONFIG['UserNickName_col']];
				if($DB->Snatch($CONFIG['User_Table'], $CONFIG['UserNickName_col'], $whereLoc))
				{
					//Nickname Exists
					$this->error = $data[$CONFIG['UserNickName_col']].' already exists!';
					$DB->Sever();
					RETURN false;
				}
			}
			
			//If Given, Move the avatar img to the users img directory and
			//delete all other files in temp dir, alter value to match.
			if(isset($data[$CONFIG['UserAvatar_col']]))
			{
				//Make sure the file exists
				if(file_exists($data[$CONFIG['UserAvatar_col']]))
				{
					//Rebuild the filename
					$farray = explode('/', $data[$CONFIG['UserAvatar_col']]);
					$filename = end($farray);//The name of the file
					$ext = end(explode('.', $filename));//The file extension
					$newName = uniqid(rand());
					$newURL = "imgs/users/".$newName.".".$ext;
					
					//Move to the users avatar directory
					rename($data[$CONFIG['UserAvatar_col']], $newURL);
					
					//Set the new URL value to be put in the DB
					$data[$CONFIG['UserAvatar_col']] = $newURL;
					
					//Empty the temp dir of all files matching the session_id prefix
					$fileList = glob('imgs/users/temp/'.session_id().'_*');
					foreach($fileList as $trashFile)
					{
						if(is_file($trashFile))
						{
							unlink($trashFile);
						}
					}
				}
			}

      //Format the user data into field/value pairs
      $fieldList = array($CONFIG['UserEmail_col'], $CONFIG['UserPass_col'], $CONFIG['UserType_col'], $CONFIG['UserJoin_col']);
      $valueList = array($email, $this->Scramble($password, true), $userTypeID, time());
      if($data != null)
      {
        foreach($data as $key => $value)
        {
          $fieldList[] = $key;
          $valueList[] = $value;
        }
      }

      //Attempt to Inject the new user into the database
      if($DB->Inject($CONFIG['User_Table'], $fieldList, $valueList))
      {
        //Success return the new users ID
        $DB->Sever();
        RETURN $DB->InjectID();
        
      }else{ $this->LogError($this->error = 'Injection Failure -- user::Create()'); $DB->Sever(); }
    }else{ $this->LogError($this->error = 'No Data Link! -- user::Create()'); }
    
    RETURN false;
  }  
  
  
  //-----------------//
  //- Update Method -//
  //-----------------//
  /*
    This method uses the provided data formatted in fieldName => value pairs
    to update the data associated to this user in the database. Email and Nickname
		updates are subject to uniqueness verification.
		//Check for new avatar and delete old
  */ 
  public function Update($data)
  {
 		//Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //Before anything else, pass the $data parameter through the parent
    //UpdateValues() method, it will update the attr unique values and
    //then return an array of data not used, the remaining data should
    //be specific to the user class.
    $data = $this->UpdateValues($data, $this->user_ID);
    if($data === false){ RETURN false;}//A problem occurred
		
		//If Given, Move the avatar img to the users img directory and
		//delete all other files in temp dir, alter value to match.
		if(isset($data[$CONFIG['UserAvatar_col']]))
		{
			//Make sure the file exists
			if(file_exists($data[$CONFIG['UserAvatar_col']]))
			{
				//Rebuild the filename
				$farray = explode('/', $data[$CONFIG['UserAvatar_col']]);
				$filename = end($farray);//The name of the file
				$ext = end(explode('.', $filename));//The file extension
				$newName = uniqid(rand());
				$newURL = "imgs/users/".$newName.".".$ext;
				
				//Move to the users avatar directory
				rename($data[$CONFIG['UserAvatar_col']], $newURL);
				
				//Set the new URL value to be put in the DB
				$data[$CONFIG['UserAvatar_col']] = $newURL;
				
				//Empty the temp dir of all files matching the session_id prefix
				$fileList = glob('imgs/users/temp/'.session_id().'_*');
				foreach($fileList as $trashFile)
				{
					if(is_file($trashFile))
					{
						unlink($trashFile);
					}
				}
			}
		}
		
    //Split keyed array into fields and values
    $fieldList = array();
    $valueList = array();
    foreach($data as $key => $value)
    {
      //Verify values are not empty
      if(!(empty($value)))
      {
        //Skip root user userType changes
        if(!($this->user_ID == 1 && $key == $CONFIG['UserTypeID_col']))
        {
          $fieldList[] = $key;
          $valueList[] = ($key == $CONFIG['UserPass_col'] && !(empty($value))) ? $this->Scramble($value, true) : $value;
          //^ If the key is for the user password and the value is not empty, scramble & cook the value
        }
      }
    }
    
    //Connect to the database
    $DB = new dbAccess();
    if($DB->Link())
    {
			//If Given, verify email does not already exist
			if(isset($data[$CONFIG['UserEmail_col']]))
			{
				$whereLoc = array(
					$CONFIG['UserEmail_col'].'='.$data[$CONFIG['UserEmail_col']],
					$CONFIG['UserID_col'].'!='.$this->user_ID
				);
				if($DB->Snatch($CONFIG['User_Table'], $CONFIG['UserEmail_col'], $whereLoc))
				{
					//Email provided already exists for a different user!
					$this->error = $data[$CONFIG['UserEmail_col']]. ' already exists for another user'.
					$DB->Sever();
					RETURN false;
				}
			}
			
			//If Given, verify nickname does not already exist
			if(isset($data[$CONFIG['UserNickName_col']]))
			{
				$whereLoc = array(
					$CONFIG['UserNickName_col'].'='.$data[$CONFIG['UserNickName_col']],
					$CONFIG['UserID_col'].'!='.$this->user_ID
				);
				if($DB->Snatch($CONFIG['User_Table'], $CONFIG['UserNickName_col'], $whereLoc))
				{
					//Email provided already exists for a different user!
					$this->error = $data[$CONFIG['UserNickName_col']]. ' already exists for another user'.
					$DB->Sever();
					RETURN false;
				}
			}			
			
      //Isolate the specific user
      $whereLoc = $CONFIG['UserID_col'].'='.$this->user_ID;
      
      //Try to update the database
      if($DB->Refresh($CONFIG['User_Table'], $fieldList, $valueList, $whereLoc))
      {
          //Data updated
          $DB->Sever();
          RETURN true;
      }
      else
      {
        //Check if the Refresh failed due
        //to no changes being made and not an error
        if($DB->Error() == '0 rows affected')
        { 
          //Set the "error" in the error attribute
          $this->error = 'No changes made';
          
          //Return successful
          $DB->Sever();
          RETURN true;
          
        }else{ $this->LogError(($this->error = $DB->Error()).' -- user::Update() '); }
      }
      $DB->Sever();//Sever DB Link
    }else{ $this->LogError($this->error = 'No Data Link! -- user::Update()'); }
    
    RETURN false;
  }
  //END Update method
  
  
  //---------------//
  //- Kill Method -//
  //---------------//
  /*
    This method kills *this user by removing the 
    associated record from the database.
		//Also needs to remove the users avatar!
  */
  public function Kill()
  {
    //Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //Kill off any possible user Attrs
    $this->KillValues($this->user_ID);
    
		//Try to delete the users avatar
		if(file_exists($this->user_avatar))
		{
			unlink($this->user_avatar);
		}
		
    //Create link to the database
    $DB = new dbAccess();
    if($DB->Link())
    {
      //Attempt to destroy the user record from the database
      $whereLoc = $CONFIG['UserID_col'].'='.$this->user_ID;
      if($DB->Kill($CONFIG['User_Table'], $whereLoc))
      {
        //Success
        $DB->Sever();
        RETURN true;
        
      }else{ $this->LogError($this->error = 'Kill Failure! -- user::Kill()'); }
      $DB->Sever();
    }else{ $this->LogError($this->error = 'No Data Link! -- user::Kill()'); }
    
    //Failure
    RETURN false;
  }
  //END Kill method

  
  //-----------------//
  //- Search method -//
  //-----------------//
  /*
    This method searches the database in order to try and locate
    a specific or multiple possible users based on a given "needle",
    paired with a filter option such as "email" etc..
    (supports partial needles)
    
    ACCEPTS: 
      $filter   - <string> The specific user attribute to use as a search 
                focus in order to reduce the number of results returned.
                FilterList:
                  - type      - The user's Type(Group), ID or Name accepted 
                  - firstName - The user's First Name
                  - lastName  - The user's Last Name
                  - status    - The user's Status, ID or Name accepted
                  - phone     - The user's Phone number
                  - email     - (default) The user's Email address
      
      $needle   - <string> A word, phrase, number, or partial of any of the previous 
                that, when paired with a $filter, is used to reduce the number of 
                results returned.
      
      $BoE_flag - <bool> A boolean flag that determines whether to place the "wild" symbol
                at the Beginning or End of the provided %needle. (For partial $needles).
                Options:
                  - 0 - Begins with $needle, wild symbol comes after needle
                  - 1 - Ends with $needle, wild symbol comes before needle
                  - none - Default value, needle between two wild symbols
                  
    RETURNS: [Success] - Array of user IDs that matched the search parameters given
             [Failure] - False
  */
  public function Search($filter, $needle, $BoE_flag = 'none')
  {
    //Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //Attempt to establish a Database link
    $DB = new dbAccess();
    if($DB->Link())
    {
      //Determine needle's wild symbol placement
      $BoE[0] = ($BoE_flag == 1) ? "%" : "";
      $BoE[1] = ($BoE_flag == 1) ? "" : "%";
    
      //Used to swap out where clauses later if needed...
      $whereLoc2 = null;
      
      //Determine the filter
      switch($filter)
      {
        case 'type':
          $filter = $CONFIG['UserType_col'];
          
          //Determine if user searching by "type name"
          if(!(is_numeric($needle)))
          {
            //User is searching by the name of the user type, we need to find the ID 
            //to the name first and then create a where clause for each result found
            //then we can search the users table...
            $whereLoc = (isset($needle)) ? $CONFIG['UserTypeName_col'].' LIKE '.rtrim($BoE[0].$needle.$BoE[1]) : null;
            if($DB->Snatch($CONFIG['UserType_Table'], $CONFIG['UserTypeID_col'], $whereLoc))
            {
              $data = $DB->Result();
              if(isset($data[0]))
              {
                //Multiple Results...
                $whereLoc2 = array();
                $uTypeCount  = count($data);
                for($i=0; $i < $uTypeCount; $i++)
                {
                  $whereLoc2[$i] = $filter.'='.$data[$i][$CONFIG['UserTypeID_col']];  
                }
                
                //Now add the "|" (OR) modifier to the beginning
                //of the array to tell the Snatch method to concatenate
                //the Where clauses with OR rather than AND
                array_unshift($whereLoc2, "|");
              }
              else
              {
                //Single Result
                $whereLoc2 = $filter.'='.$data[$CONFIG['UserTypeID_col']];
              }
            }
          }
        break;
        
        case 'firstName':
          $filter = $CONFIG['UserFirstName_col'];
        break;
        
        case 'lastName':
          $filter = $CONFIG['UserLastName_col'];
        break;
        
        case 'status':
          $filter = $CONFIG['UserStatus_col'];
          
          //Determine if user searching by "type name"
          if(!(is_numeric($needle)))
          {
            //User is searching by the name of the user status, we need to find the ID 
            //to the name first and then create a where clause for each result found
            //then we can search the users table for those status IDs...
            $whereLoc = $CONFIG['UserStatusName_col'].' LIKE '.rtrim($BoE[0].$needle.$BoE[1]);
            if($DB->Snatch($CONFIG['UserStatus_Table'], $CONFIG['UserStatusID_col'], $whereLoc))
            {
              $data = $DB->Result();
              if(isset($data[0]))
              {
                //Multiple Results...
                $whereLoc2 = array();
                $uStatusCount  = count($data);
                for($i=0; $i < $uStatusCount; $i++)
                {
                  $whereLoc2[$i] = $filter.'='.$data[$i][$CONFIG['UserStatusID_col']];  
                }
                
                //Now add the "|" (OR) modifier to the beginning
                //of the array to tell the Snatch method to concatenate
                //the Where clauses with OR rather than AND
                array_unshift($whereLoc2, "|");
              }
              else
              {
                //Single Result
                $whereLoc2 = $filter.'='.$data[$CONFIG['UserStatusID_col']];
              }
            }
          }          
        break;
        
        case 'phone':
          $filter = $CONFIG['UserPhone_col'];
        break;
        
        default:
          $filter = $CONFIG['UserEmail_col'];
        break;
      }
      
      //Get a list of ALL users (IDs only), matching the needle
      $whereLoc = (isset($needle)) ? $filter.' LIKE '.rtrim($BoE[0].$needle.$BoE[1]) : null;
      $whereLoc = ($whereLoc2 != null) ? $whereLoc2 : $whereLoc;

      if($DB->Snatch($CONFIG['User_Table'], $CONFIG['UserID_col'], $whereLoc))    
      {
        $data = $DB->Result();
        $uList = array();
        if(isset($data[0]))
        {
          foreach($data as $key => $row)
          {
            $uList[] = $row[$CONFIG['UserID_col']];
          }
        }
        else
        {
          $uList[] = $data[$CONFIG['UserID_col']];
        }
        
        $DB->Sever();
        RETURN $uList;//List of users found!
      }
      $DB->Sever();
    }
    
    //Failure
    RETURN false;
  }
  //END Search method
  
  
  //--------------------------//
  //- Permission List Method -//
  //--------------------------//
  /*
     RETURNS: The entire table of permissions 
              for each available UserType.
  */
  public function PermList()
  {
		//Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }

    //Check if data already exists
    if(empty($this->typePermList))
    {
      //Create database link
      $DB = new dbAccess();
      if($DB->Link())
      {
        //Snatch Type data from DB
        if($DB->Snatch($CONFIG['UserType_Table']))
        {
          //Retrieve the results
          //$data[0] => Array('TypeID' => value, 'TypeName' => value, 'ACP' => value, <etc>...)
          //In this case result should always be an array ie data[0] should exist
          $data = $DB->Result();
          
          //cache the data
          $this->typePermList = $data;
        
        }else{ $this->LogError('Snatch failure -- user::Permitted()'); }
        $DB->Sever();//Sever DB Link
      }else{ $this->LogError('No Data Link! -- user::Permitted()'); }
    }
    
    //Success!?!
    RETURN (empty($this->typePermList)) ? false : $this->typePermList;
    
  }//END Permission List Method
  //Convenience Methods
  public function PermissionList(){ RETURN $this->PermList(); }
  public function PrivilegeList(){ RETURN $this->PermList(); }
  public function DibsList(){ RETURN $this->PermList(); }
  
  
  //--------------------//
  //- Permitted Method -//
  //--------------------//
  /*
    ACCEPTS: $permName - name of a specific permission (case is in-sensitive)
    RETURNS: true | false | array of permission data
    
    If given a specific permission name this method will return true if this user
    has permission or false if they do not have permission. If no specific permission
    name is given, the entire permission list for this users type is returned. If there
    is no userID or userTypeID present then all permissions are returned false.
  */
  public function Permitted($permName = null)
  {
		//Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //Check if data already exists
    if(empty($this->userPermList))
    {
      //Create database link
      $DB = new dbAccess();
      if($DB->Link())
      {
        //Snatch UserType data from DB
        $whereLoc = ($this->user_type[0] == 0) ? $CONFIG['UserTypeID_col'].'=1' : $CONFIG['UserTypeID_col'].'='.$this->user_type[0];
        if($DB->Snatch($CONFIG['UserType_Table'], '*', $whereLoc))
        {
          //Retrieve the results
          //There should be only 1 result...
          //(data[0] should not exist)
          $data = $DB->Result();
          
          //Remake the list w/out the userTypeID and name
          //and store it for later references
          foreach($data as $colName => $colValue)
          {
            if($colName != $CONFIG['UserTypeID_col'] && $colName != $CONFIG['UserTypeName_col'])
            {
              //If no user_ID or userType_ID is present then replace all values with 0 or false
              //This will allow this method to be used to gather just Permission names when user 
              //data is not available. Such as when we need to set template conditions and we 
              //have a guest user w/o an ID.
              $this->userPermList[$colName] = ($this->user_type[0] == 0 || $this->user_ID == 0) ? 0 : $colValue;
            }
          }
        }else{ $this->LogError('Snatch failure -- user::Permitted()'); }
        $DB->Sever();//Sever DB Link
      }else{ $this->LogError('No Data Link! -- user::Permitted()'); }
    }
    
    //Check if requesting specific information
    if($permName != null)
    {
      //Return true/false if this user isPermitted or not...
      RETURN ($this->userPermList[strtolower($permName)]) ? true : false;
    }
    
    //If no specific permission data is requested, return the whole list
    //unless no data is available, in which case return false
    RETURN (empty($this->userPermList)) ? false : $this->userPermList;
    
  }//END Permitted Method
  //Convenience Methods
  public function Dibs($permName = null){ RETURN $this->Permitted($permName); }
  public function Privileges($permName = null){ RETURN $this->Permitted($permName); }
  public function Permissions($permName = null){ RETURN $this->Permitted($permName); }
  public function Permission($permName = null){ RETURN $this->Permitted($permName); }
  
  
  //-------------------------//
  //- New Permission Method -//
  //-------------------------// 
  //Creates new user permission
  public function NewPermission($pName)
  {
    //Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //Create link to the database
    $DB = new dbAccess();
    if($DB->Link())
    {
      //In order to add a new permission we have to modify the 
      //userType table and add a new bool column to it using 
      //the permission name that was given to us.
      if($DB->ModTable($CONFIG['UserType_Table'], 'ADD', strtolower($pName), 'bool', 0))
      {
        //Now update the table data so that ADMIN users have access to the new permission
        $DB->Refresh($CONFIG['UserType_Table'], strtolower($pName), 1, $CONFIG['UserTypeID_col'].'=1');
        
        //Sever the DB Link
        $DB->Sever();
        
        //Success!!
        RETURN true;
        
      }else{ $this->LogError('ModTable Failure -- user::NewPermission()'); }
      $DB->Sever();//Sever the DB Link
    }else{ $this->LogError('No Data Link! -- user::NewPermission()'); }
    
    //Failure
    RETURN false;
  }
  //Convenience Methods
  public function NewPerm($pName){ RETURN $this->NewPermission($pName); }
  public function NewDibs($pName){ RETURN $this->NewPermission($pName); }

  
  //---------------------------//
  //- Alter Permission Method -//
  //---------------------------//
  //Changes the name of the permission
  public function AlterPerm($pName, $newName)
  {
    //Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //Verify permission name is not "ACP", this permission cannot be altered
    if(strtolower($pName) == "acp"){ $this->error = "ACP permission cannot be altered!"; RETURN false; }
    
    //Create link to the database
    $DB = new dbAccess();
    if($DB->Link())
    {
      //In order to alter a permission we have to modify the 
      //userType table and alter the column for it using 
      //the permission names that were given to us.
      if($DB->ModTable($CONFIG['UserType_Table'], 'RENAME', strtolower($pName), $newName))
      {
        //Sever the DB Link
        $DB->Sever();
        
        //Success!!
        RETURN true;
        
      }else{ $this->LogError('ModTable Failure -- user::AlterPerm()'); }
      $DB->Sever();//Sever DB Link
    }else{ $this->LogError('No Data Link! -- user::AlterPerm()'); }
    
    //Failure
    RETURN false;
  }
 
 
  //----------------------------//
  //- Remove Permission Method -//
  //----------------------------//
  //Removes user permission
  public function RemovePerm($pName)
  {
    //Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //Create link to the database
    $DB = new dbAccess();
    if($DB->Link())
    {
      //In order to remove a permission we have to modify the 
      //userType table and drop the column for it using 
      //the permission name that was given to us.
      if($DB->ModTable($CONFIG['UserType_Table'], 'DROP', strtolower($pName)))
      {
        //Sever DB Link
        $DB->Sever();
        
        //Success!!
        RETURN true;
        
      }else{ $this->LogError('ModTable Failure -- user::RemovePerm()'); }
      $DB->Sever();//Sever DB Link      
    }else{ $this->LogError('No Data Link! -- user::RemovePerm()'); }
    
    //Failure
    RETURN false;
  }
  //Convenience methods
  public function DeletePerm($pName){ RETURN $this->RemovePerm($pName); }
  public function KillPerm($pName)  { RETURN $this->RemovePerm($pName); }
  public function RemoveDibs($pName){ RETURN $this->RemovePerm($pName); }
  public function DeleteDibs($pName){ RETURN $this->RemovePerm($pName); }
  public function KillDibs($pName)  { RETURN $this->RemovePerm($pName); }
  
  
  //----------------------------//
  //- Permission Update Method -//
  //----------------------------//
  /*
    This method updates the permission table
    in order to add or remove access to a specific
    permission for a specific user type
    
    ACCEPTS:  $typeD - The Name or ID for the user type to update
              $pData - Keyed Array of 'PermName' => value, combinations.
                       ex: $pData['acp'] = 1;
                       
    RETURNS: true | false
  */
  public function PermUpdate($typeD, $pData)
  {
    //Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }

    //Determine if $typeData is a name or an ID
    //Type cast integer for added measure if ID
    $typeData = (is_numeric($typeD)) ? (int) $typeD : $typeD;
    $typeField = (is_int($typeData)) ? $CONFIG['UserTypeID_col'] : $CONFIG['UserTypeName_col'];
    
    //Create link to the database
    $DB = new dbAccess();
    if($DB->Link())
    {
      //Split apart the keys and values and
      //format them for the refresh method
      foreach($pData as $pName => $pValue)
      {
        $fields[] = strtolower($pName);
        $values[] = $pValue;
      }
      $whereLoc = $typeField.'='.$typeData;
      if($DB->Refresh($CONFIG['UserType_Table'], $fields, $values, $whereLoc))
      {
        //Sever the DB Link
        $DB->Sever();
        
        //Success!!
        RETURN true;
        
      }
      else if($DB->Error() == '0 rows affected')
      { 
         //Sever the DB Link
         $DB->Sever();
        
         //Success!!
         RETURN true;
         
      }else{ $this->LogError('Refresh failure! -- user::PermUpdate'); }
      $DB->Sever();//Sever DB Link      
    }else{ $this->LogError('No Data Link! -- user::PermUpdate()'); }
    
    //Failure
    RETURN false;
  }
  
  
  //--------------------//
  //- Type List Method -//
  //--------------------// 
  //Get list of available types - (ID, Name)
  public function TypeList()
  {
		//Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }

    //Check if data already exists
    if(empty($this->userTypeList))
    {
      //Create database link
      $DB = new dbAccess();
      if($DB->Link())
      {
        //Snatch UserType data from DB
        $fieldList = array($CONFIG['UserTypeID_col'], $CONFIG['UserTypeName_col']);//Ignore permission data
        if($DB->Snatch($CONFIG['UserType_Table'], $fieldList))
        {
          //Retrieve the results
          $data = $DB->Result();
          
          //Check for multi result
          if(isset($data[0]))
          {
            //cycle each result
            foreach($data as $type)
            {
              $this->userTypeList[] = array($type[$CONFIG['UserTypeID_col']], $type[$CONFIG['UserTypeName_col']]);
            }
          }
          else
          {
            //Single result
            $this->userTypeList[] = array($data[$CONFIG['UserTypeID_col']], $data[$CONFIG['UserTypeName_col']]);
          }
        }else{ $this->LogError('Snatch failure -- user::UserTypes()'); }
        $DB->Sever();//Sever DB Link
      }else{ $this->LogError('No Data Link! -- user::UserTypes()'); }
    }
    
    //Success!?!
    RETURN (empty($this->userTypeList)) ? false : $this->userTypeList;
  }
  
  
  //-------------------//
  //- New Type Method -//
  //-------------------// 
  //Creates new user type
  public function NewType($name)
  {
    //Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //Create link to the database
    $DB = new dbAccess();
    if($DB->Link())
    {
      //Attempt to add the new user type
      if($DB->Inject($CONFIG['UserType_Table'], $CONFIG['UserTypeName_col'], $name))
      {
        //Get the new types ID
        $injectID = $DB->InjectID();
        
        //Sever DB connection
        $DB->Sever();
        
        //Return the new types ID
        RETURN $injectID;
        
      }else{ $this->LogError('Injection failure -- user::NewType()'); }
      $DB->Sever();
    }else{ $this->LogError('No Data Link! -- user::NewType()'); }
    
    RETURN false;
  }

  
  //---------------------//
  //- Alter Type Method -//
  //---------------------// 
  //Changes name of user Type
  public function AlterType($typeD, $newName)
  {
    //Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
 
    //Determine if $typeData is a name or an ID
    //Type cast integer for added measure if ID
    $typeData = (is_numeric($typeD)) ? (int) $typeD : $typeD;
    $fieldName = (is_int($typeData)) ? $CONFIG['UserTypeID_col'] : $CONFIG['UserTypeName_col'];
    
    //Create link to the database
    $DB = new dbAccess();
    if($DB->Link())
    {
      //Attempt to alter the type name
      $whereLoc = $fieldName.'='.$typeData;
      if($DB->Refresh($CONFIG['UserType_Table'], $CONFIG['UserTypeName_col'], $newName, $whereLoc))
      {
        //Sever DB Link
        $DB->Sever();
        
        //Success!!
        RETURN true;
        
      }else{ $this->LogError('Refresh failure -- user::AlterType()'); }
      $DB->Sever();
    }else{ $this->LogError('No Data Link! -- user::AlterType()'); }
    
    //Failure
    RETURN false;
  }
  
  
  //----------------------//
  //- Remove Type Method -//
  //----------------------// 
  //Remove user type
  public function RemoveType($typeD)
  {
    //Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
 
    //Determine if $typeData is a name or an ID
    //Type cast integer for added measure if ID
    $typeData = (is_numeric($typeD)) ? (int) $typeD : $typeD;
    $fieldName = (is_int($typeData)) ? $CONFIG['UserTypeID_col'] : $CONFIG['UserTypeName_col'];
    
    //Create link to the database
    $DB = new dbAccess();
    if($DB->Link())
    {
      //Attempt to add the new user type
      $whereLoc = $fieldName.'='.$typeData;
      if($DB->Kill($CONFIG['UserType_Table'], $whereLoc))
      {
        //Sever DB Link
        $DB->Sever();
        
        //Success!!
        RETURN true;
        
      }else{ $this->LogError('Kill failure -- user::RemoveType()'); }
      $DB->Sever();
    }else{ $this->LogError('No Data Link! -- user::RemoveType()'); }
    
    //Failure
    RETURN false;
  }
  //Convenience Methods
  public function KillType($typeData){ RETURN $this->RemoveType($typeData); }
  public function DeleteType($typeData){ RETURN $this->RemoveType($typeData); }
  
  
  //----------------------//
  //- Status List Method -//
  //----------------------//   
  //Get list of available statuses
  public function StatusList()
  {
		//Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }

    //Check if data already exists
    if(empty($this->userStatusList))
    {
      //Create database link
      $DB = new dbAccess();
      if($DB->Link())
      {
        //Snatch UserType data from DB
        if($DB->Snatch($CONFIG['UserStatus_Table']))
        {
          //Retrieve the results
          $data = $DB->Result();
          
          //Check for multi result
          if(isset($data[0]))
          {
            //cycle each result
            foreach($data as $type)
            {
              $this->userStatusList[] = array($type[$CONFIG['UserStatusID_col']], $type[$CONFIG['UserStatusName_col']]);
            }
          }
          else
          {
            //Single result
            $this->userStatusList[] = array($data[$CONFIG['UserStatusID_col']], $data[$CONFIG['UserStatusName_col']]);
          }
        }else{ $this->LogError('Snatch failure -- user::StatusList()'); }
        $DB->Sever();//Sever DB Link
      }else{ $this->LogError('No Data Link! -- user::StatusList()'); }
    }
    
    //Success!?!
    RETURN $this->userStatusList;
  }
  
 
  //---------------------//
  //- New Status Method -//
  //---------------------// 
  //Creates new user status
  public function NewStatus($name)
  {
    //Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //Create link to the database
    $DB = new dbAccess();
    if($DB->Link())
    {
      //Attempt to add the new user type
      if($DB->Inject($CONFIG['UserStatus_Table'], $CONFIG['UserStatusName_col'], $name))
      {
        //Get the new types ID
        $injectID = $DB->InjectID();
        
        //Sever DB Link
        $DB->Sever();
        
        //Return the new types ID
        RETURN $injectID;
        
      }else{ $this->LogError('Injection failure -- user::NewStatus()'); }
      $DB->Sever();
    }else{ $this->LogError('No Data Link! -- user::NewStatus()'); }
    
    RETURN false;
  }
  
  
  //-----------------------//
  //- Alter Status Method -//
  //-----------------------//
  //Change user Status Name
  public function AlterStatus($statusD, $newName)
  {
    //Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
 
    //Determine if $statusData is a name or an ID
    //Type cast integer for added measure if ID
    $statusData = (is_numeric($statusD)) ? (int) $statusD : $statusD;
    $fieldName = (is_int($statusData)) ? $CONFIG['UserStatusID_col'] : $CONFIG['UserStatusName_col'];
    
    //Create link to the database
    $DB = new dbAccess();
    if($DB->Link())
    {
      //Attempt to alter the status name
      $whereLoc = $fieldName.'='.$statusData;
      if($DB->Refresh($CONFIG['UserStatus_Table'], $CONFIG['UserStatusName_col'], $newName, $whereLoc))
      {
        //Sever DB Link
        $DB->Sever();
        
        //Success!!
        RETURN true;
        
      }else{ $this->LogError('Refresh failure -- user::AlterStatus()'); }
      $DB->Sever();
    }else{ $this->LogError('No Data Link! -- user::AlterStatus()'); }
    
    //Failure
    RETURN false;
  }
  
  
  //------------------------//
  //- Remove Status Method -//
  //------------------------//
  //Remove user Status
  public function RemoveStatus($statusD)
  {
    //Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
 
    //Determine if $statusData is a name or an ID
    //Type cast integer for added measure if ID
    $statusData = (is_numeric($statusD)) ? (int) $statusD : $statusD;
    $fieldName = (is_int($statusData)) ? $CONFIG['UserStatusID_col'] : $CONFIG['UserStatusName_col'];
    
    //Verify status is not a default status!
    
    //Create link to the database
    $DB = new dbAccess();
    if($DB->Link())
    {
      //Attempt to kill the user status
      $whereLoc = $fieldName.'='.$statusData;
      if($DB->Kill($CONFIG['UserStatus_Table'], $whereLoc))
      {
        //Sever DB Link
        $DB->Sever();
        
        //Success!!
        RETURN true;
        
      }else{ $this->LogError('Kill failure -- user::RemoveStatus()'); }
      $DB->Sever();
    }else{ $this->LogError('No Data Link! -- user::RemoveStatus()'); }
    
    //Failure
    RETURN false;
  }
  //Convenience Methods
  public function KillStatus($statusData){ RETURN $this->RemoveStatus($statusData); }
  public function DeleteStatus($statusData){ RETURN $this->RemoveStatus($statusData); }
  
 
  //------------------//
  //- Message Method -//
  //------------------//
  /*
    Sends an email to *this user's email address.
    Cannot be used by guest users.
		ACCEPTS:
			$mFile = single or array of html files to use for the email
			$mVars = single or array of template variables to be plugged into HTML
			$mConds = condition tags to be used in creating the email HTML template
			$subject = The message subject to be used for the email
		
		RETURNS: TRUE | FALSE
  */
  public function Message($mFile, $mVars = null, $mConds = null, $subject = null)
  {
    //Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //Requires: userName, and Email address at min!
    if($this->Name() != 'unknown' && $this->Email() != 'unknown')
    { 
      $Tpl = new template(STYLE);
      $Tpl->SetPath('email');//Force the template to look 
      //within the email sub-directory for the html files
      if($Tpl->SetFiles($mFile, true))
      {
        if($Tpl->SetVars($mVars))
        {
          if($Tpl->SetConditions($mConds))
          {
            $eMsg = $Tpl->Compile(true);
            if($eMsg !== false)
            {
              // To send HTML mail, the Content-type header must be set
              $headers  = 'MIME-Version: 1.0' . "\r\n";
              $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

              // Additional headers
              $headers .= 'From: '.$CONFIG['SiteName'].' <'.$CONFIG['ContactEmail'].'>' . "\r\n";

              $eSubj = (empty($subject)) ? 'A message from '.$CONFIG['SiteName'] : $subject;
              
              //Attempt to send the message
              if(mail($this->Email(), $eSubj, $eMsg, $headers))
              {
              
                //Success!!
                RETURN true;
                
              }else{ $this->LogError('Mail failure -- user::Message()'); }
            }else{ $this->LogError('Compilation failure -- user::Message()'); }
          }else{ $this->LogError('SetConditions failure -- user::Message()'); } 
        }else{ $this->LogError('SetVars failure -- user::Message()'); }  
      }else{ $this->LogError('SetFiles failure -- user::Message()'); } 
    }else{ $this->LogError('User\'s Name and/or Email is missing. -- user::Message()'); }
    
    //Failure
    RETURN false;
    
  }//END Message Method
  
  
  //------------------//
  //- TextMsg Method -//
  //------------------//
  /*
    Sends a TextMsg to *this users phone
  */  
  public function TextMsg($mFile, $mVars = null, $mConds = null)
  {
    RETURN false;
  }

  
  //-----------------//
	//- UnPack Method -//
  //-----------------//
	/*
		This function checks for user information stored in the session.
		If that is not available it will also check for a cookie key and 
    remember the user information. Takes no arguments.
		
		RETURNS: [TRUE] | [FALSE]
	*/
	final public function Unpack()
	{	
		//Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //Look for User object in the session
		if(isset($_SESSION['RESU'.md5($CONFIG['SiteName'])]))
		{	
      //Found it!, Split the time from the session value
      $sValue = explode('-:-', $_SESSION['RESU'.md5($CONFIG['SiteName'])]);
      
      //Now get the difference in time from now
      $tDiff = time() - $sValue[0];
      
      //Verify the time is within the 2 hour limit
      if($tDiff < (2*60*60))
      {
        //The value is within limits so decode and unserialize it
        $userObj = unserialize(base64_decode($sValue[1]));
        
        //Get the user ID
        $this->user_ID = $userObj->ID();
        
        //Initialize the object with updated info
        if($this->Initialize())
        {
          RETURN true;
        }
      }
		}
    else if(isset($_COOKIE['YEKC']))
		{	
      //Found a Cookie
			if($this->Remember($_COOKIE['YEKC']))
			{
        //Cookie Key Accepted!
				RETURN true;
			}
			else
			{
				//Key was not accepted
				RETURN false;
			}
		}
    RETURN false;
	}//End UnPack Method
  
  
  //---------------//
  //- Pack Method -//
  //---------------//
	/*
		This method will serialize this object and place 
    it into the session to be unpacked later.
		
		RETURNS: [TRUE] | [FALSE]
	*/
	final public function Pack()
	{
		//Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
		
    //Get the time in secs 2 hours from now
    $hfn = time() + (2*60*60);
		$_SESSION['RESU'.md5($CONFIG['SiteName'])] = $hfn.'-:-'.base64_encode(serialize($this));
		if(!(empty($_SESSION['RESU'.md5($CONFIG['SiteName'])])))
		{
			RETURN true;
		}
		else
		{
			$this->LogError("Unable to store session data!");
			RETURN false;
		}
	}//End Pack Method

  
  //----------------------------//
  //- AttrList Override Method -//
  //----------------------------// 
  public function AttrList($ID = 0)
  {
    if($this->user_ID != 0)
    {
      //Success?
      RETURN parent::AttrList($this->user_ID);
    }
    
    //Failure
    RETURN false;
  }
  
	//Get Attribute By Name?
	public function GetAttr($name, $ID = 0)
	{
		if($this->user_ID != 0)
		{
			RETURN parent::GetAttr($name, $this->user_ID);
		}
		
		RETURN false;
	}
  
  //---------------------//
  //- Initialize Method -//
  //---------------------//
  /*
    This method uses the provided user_ID to gather the associated user data 
    from the database and initialize the object instance with the users details.
  */  
  final protected function Initialize($userID = 0)
  {
		//Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //Set the user ID of this object 
    //equal to the one provided (or 0)
    $this->user_ID = ($this->user_ID != 0) ? $this->user_ID : $userID;
    
    //verify user has ID
    if($this->user_ID != 0)
    {
      //Get the users data!
      $DB = new dbAccess();
      if($DB->Link())
      {
        $whereLoc = $CONFIG['UserID_col'].'='.$this->user_ID;
        if($DB->Snatch($CONFIG['User_Table'], '*', $whereLoc))
        {
          //Request the results
          $data = $DB->Result();
         
          //Initialize Object with DB data
          $this->user_name      = $data[$CONFIG['UserFirstName_col']].' '.$data[$CONFIG['UserMidName_col']].' '.$data[$CONFIG['UserLastName_col']];//User FullName
          $this->user_nick      = $data[$CONFIG['UserNickName_col']];//User's Nick Name
					$this->user_avatar		= (empty($data[$CONFIG['UserAvatar_col']])) ? 'imgs/no_thumb.jpg' : $data[$CONFIG['UserAvatar_col']];//User's Avatar (URL)
          $this->user_email     = $data[$CONFIG['UserEmail_col']];//User Email Address
          $this->user_phone     = $data[$CONFIG['UserPhone_col']];//User Phone Number
          $this->user_carrier[0]= $data[$CONFIG['UserCarrier_col']];//User Mobile Phone Carrier
          $this->user_type[0]   = $data[$CONFIG['UserType_col']];//User Type ID
          $this->user_status[0] = $data[$CONFIG['UserStatus_col']];//User Status ID
					$this->user_joined 		= $data[$CONFIG['UserJoin_col']];//User Join Date (timestamp)
					$this->user_lastLogin = $data[$CONFIG['UserLastLogin_col']];//User Last Login (timestamp)
          
          //Check for additional Attributes
          //$attrList = $this->AttrList();
          
          //Get the Type Name and Type List
          $typeList = $this->TypeList();
          foreach($typeList as $type)
          {
            if($type[0] == $this->user_type[0])
            {
              $this->user_type[1] = $type[1];
              break;
            }
          }
          
          //Get the Status Name and Status List
          $statusList = $this->StatusList();
          foreach($statusList as $status)
          {
            if($status[0] == $this->user_status[0])
            {
              $this->user_status[1] = $status[1];
              break;
            }
          }
          
          //Try to retrieve the phone carrier name
          if($DB->Snatch($CONFIG['Carrier_Table'], $CONFIG['CarrierName_col'], $CONFIG['CarrierID_col'].'='.$this->user_carrier[0]))
          {
            $result = $DB->Result();
            $this->user_carrier[1] = $result[$CONFIG['CarrierName_col']]; 
          }//Failed to get Carrier Name...
          
          //Success!
          $DB->Sever();
          RETURN true;
          
        }else{ if($DB->Error() != '0 results found'){ $this->LogError('Snatch Failure -- user::Initialize()'); }}
        $DB->Sever();//Sever DB Link
      }else{ $this->LogError('No Data Link -- user::Initialize()'); }
    }else{ $this->LogError("NO USER ID! -- user::Initialize()");}
    //Failure
    RETURN false;
  }
  //END Initialize
  
  
	//----------------------//
	//- Scramble Functions -//
	//----------------------//
	/*
		$string - string to be scrambled
		$cook - flag to intensify scrambling with hashing
		$type - type of hash to use for hashes, default is 'whirlpool'
		
		RETURNS: A scrambled string || A Scrambled String Hash
		
		-NOTE- If choosing to "cook" a string when scrambling then
		the string will be hashed prior to the scramble(encryption).
		If the string is hashed(cooked) then the original string that
		was scrambled CANNOT be retrieved. Hashing is not reversible!
	*/
	final protected function Scramble($string, $cook = false, $type = 'whirlpool')
	{	
		//If cook is set to true then hash the string
		$scrambled = ($cook) ? hash($type, $string) : $string;
		
		//Encrypt the scrambled string - DEPRECATED!
		//$scrambled = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5(EIVS), $scrambled, MCRYPT_MODE_CBC, md5(md5(EIVS))));
		
    //Encrypt the scrabled string
    $scrambled = base64_encode(openssl_encrypt($scrambled, 'aes-256-cbc', crypt(EKEY, 'ni'), $options=0, hex2bin(EIVS)));
		
    RETURN $scrambled;
	}
	//END Scramble Function
	
  //Un-Scrambles a scrambled string
  final protected function UnScramble($scrambled)
  {
    //DEPRECATED!
    //$unScrambled = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5(EIVS), base64_decode($scrambled), MCRYPT_MODE_CBC, md5(md5(EIVS))), "\0");
    
    $unScrambled = rtrim(openssl_decrypt(base64_decode($scrambled), 'aes-256-cbc', crypt(EKEY, 'ni'), $options=0, hex2bin(EIVS)), "\0");
    
    RETURN $unScrambled;
  }
  //End Un-Scramble Function 
}
//END USER CLASS

?>