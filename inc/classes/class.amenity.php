<?php
/*
  Purpose:  NiFrame
  
  File:     Amenity Class File
  
  Author:   Nathan Poole - github/npocodes
           
  Date:     July 2019
     
  Updated:  
           
	[ !! This Class Utilizes a configuration file !! ]
*/
//require_once('inc/classes/class.nerror.php');
//require_once('inc/classes/class.dbAccess.php');

//+++++++++++++++++++++++//
//++ THE AMENITY CLASS ++//
//+++++++++++++++++++++++//
/*
  The Amenity class provides all base attributes
  as well as associated methods.
*/
class amenity extends nerror {

//-------------------------------//
      //%% Attributes %%//
//-------------------------------//
  protected $amenity_ID;		//The ID Number
	protected $amenity_name;	//The Name
	protected $amenityList;		//List of current amenities

//-------------------------------------------//
            //## Methods ##//
//-------------------------------------------//

  ///////////////////
  /// Constructor ///
  ///////////////////
  /*
    This constructor will initialize attributes to their default values and if provided with an 
    ID will retrieve the associated information and update the attributes to match.
  */
  function __construct($amenityID = 0) 
  {
    //Initialize all default values...
    parent::__construct();
    
    //Specific Attributes
    $this->amenity_ID = 0;						//The ID Number
		$this->amenity_name = 'unknown';	//The Name
		$this->amenityList = array();			//List of current amenities
		
    //Check for provided ID
    if($amenityID != 0)
    {
      //Get details
      $this->Initialize($amenityID);
    }
  }
  //End Constructor Method
  
  ///////////////////
  /// GET Methods ///
  ///////////////////
  public function ID(){ RETURN $this->amenity_ID; }
	public function Name(){ RETURN $this->amenity_name; }
  
	//----------------//
	//- AMENITY LIST -//
	//----------------//
	public function AmenityList($refresh = false)
  {
		//Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }

    //Check if data already exists
    if(empty($this->amenityList) || $refresh)
    {
      //Create database link
      $DB = new dbAccess();
      if($DB->Link())
      {
        //Snatch data from DB
        if($DB->Snatch($CONFIG['Amenity_Table']))
        {
          //Retrieve the results
          $data = $DB->Result();
          
          //Check for multi result
          if(isset($data[0]))
          {
            //cycle each result
            foreach($data as $type)
            {
              $this->amenityList[] = array($type[$CONFIG['AmenityID_col']], $type[$CONFIG['AmenityName_col']]);
            }
          }
          else
          {
            //Single result
            $this->amenityList[] = array($data[$CONFIG['AmenityID_col']], $data[$CONFIG['AmenityName_col']]);
          }
        }else{ if($DB->Error() != '0 results found'){ $this->LogError('Snatch failure -- amenity::AmenityList()'); }}
        $DB->Sever();//Sever DB Link
      }else{ $this->LogError('No Data Link! -- amenity::AmenityList()'); }
    }
    
    //Success!?!
    RETURN (empty($this->amenityList)) ? false : $this->amenityList;
  }
	
	//------------------//
	//- CREATE AMENITY -//
	//------------------//
  public function Create($name)
  {
 		//Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //Create link to database
    $DB = new dbAccess();
    if($DB->Link())
    { 
      //Format the data into field/value pairs
      $fieldList = array($CONFIG['AmenityName_col']);
      $valueList = array($name);
      
      //Attempt to Inject the new building into the database
      if($DB->Inject($CONFIG['Amenity_Table'], $fieldList, $valueList))
      {
        //Success return the new ID
        $DB->Sever();
        RETURN $DB->InjectID();
        
      }else{ $this->LogError($this->error = 'Injection Failure -- amenity::Create()'); $DB->Sever(); }
    }else{ $this->LogError($this->error = 'No Data Link! -- amenity::Create()'); }
    
    RETURN false;
  }

	//-----------------//
	//- ALTER AMENITY -//
	//-----------------//
  public function Alter($data)
  {
 		//Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //Split keyed array into fields and values
    $fieldList = array();
    $valueList = array();
    foreach($data as $key => $value)
    {
      //Verify values are not empty
      if(!(empty($value)))
      {
				$fieldList[] = $key;
				$valueList[] = $value;
      }
    }
    
    //Connect to the database
    $DB = new dbAccess();
    if($DB->Link())
    {
      //Isolate the specific record
      $whereLoc = $CONFIG['AmenityID_col'].'='.$this->amenity_ID;
      
      //Try to update the database
      if($DB->Refresh($CONFIG['Amenity_Table'], $fieldList, $valueList, $whereLoc))
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
          
        }else{ $this->LogError(($this->error = $DB->Error()).' -- amenity::Alter() '); }
      }
      $DB->Sever();//Sever DB Link
    }else{ $this->LogError($this->error = 'No Data Link! -- amenity::Alter()'); }
    
    RETURN false;
  }

	
	//----------------//
	//- KILL AMENITY -//
	//----------------//
	//REMOVE FROM AMENITY LISTS!!!!
  public function Kill()
  {
    //Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //Create link to the database
    $DB = new dbAccess();
    if($DB->Link())
    {
      //Attempt to destroy the building record from the database
      $whereLoc = $CONFIG['AmenityID_col'].'='.$this->amenity_ID;
      if($DB->Kill($CONFIG['Amenity_Table'], $whereLoc))
      {
        //Success
        $DB->Sever();
        RETURN true;
        
      }else{ $this->LogError($this->error = 'Kill Failure! -- amenity::Kill()'); }
      $DB->Sever();
    }else{ $this->LogError($this->error = 'No Data Link! -- amenity::Kill()'); }
    
    //Failure
    RETURN false;
  }
	
	
  //---------------------//
  //- Initialize Method -//
  //---------------------//
  /*
    This method uses the provided ID to gather the associated data from the
    database and initialize the object instance with the details.
  */  
  final protected function Initialize($amenityID = 0)
  {
		//Get Required Configuration Variables
		$CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
		//Set the ID of this object equal to the one provided (or 0)
		$this->amenity_ID = ($this->amenity_ID != 0) ? $this->amenity_ID : $amenityID;
    
    //verify ID available
    if($this->amenity_ID != 0)
    {
      //Get the data!
      $DB = new dbAccess();
      if($DB->Link())
      {
        $whereLoc = $CONFIG['AmenityID_col'].'='.$this->amenity_ID;
        if($DB->Snatch($CONFIG['Amenity_Table'], '*', $whereLoc))
        {
          //Request the results
          $data = $DB->Result();
         
          //Initialize Object with DB data
					//Already have ID!
					$this->amenity_name = $data[$CONFIG['AmenityName_col']];
					
        }else{ if($DB->Error() != '0 results found'){ $this->LogError('Snatch Failure -- amenity::Initialize()'); }}
        $DB->Sever();//Sever DB Link
      }else{ $this->LogError('No Data Link -- amenity::Initialize()'); }
    }else{ $this->LogError("NO ID! -- amenity::Initialize()");}
    //Failure
    RETURN false;
  }
  //END Initialize
}
//END AMENITY CLASS

?>