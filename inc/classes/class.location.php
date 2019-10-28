<?php
/*
  Purpose:  NiFrame
  
  File:     Location Class File
  
  Author:   Nathan Poole - github/npocodes
           
  Date:     July 2019
     
  Updated:  
           
	[ !! This Class Utilizes a configuration file !! ]
*/
//require_once('inc/classes/class.nerror.php');
//require_once('inc/classes/class.dbAccess.php');
//require_once('inc/classes/class.address.php');

//++++++++++++++++++++++++//
//++ THE LOCATION CLASS ++//
//++++++++++++++++++++++++//
/*
  The Location class provides all base location attributes
  as well as associated location methods.
*/
class location extends nerror {

//-------------------------------//
      //%% Attributes %%//
//-------------------------------//
  protected $location_ID;				//The ID Number
  protected $location_name;			//Location Name
	protected $location_address;	//The Address Object
	protected $location_building;	//The Building Object
	protected $location_room;			//The Room Object
	protected $addressList;				//Array of Address Objects



//-------------------------------------------//
            //## Methods ##//
//-------------------------------------------//

  ///////////////////
  /// Constructor ///
  ///////////////////
  /*
    This constructor will initialize attributes to their default values and if provided with a 
    tourney_ID will retrieve the associated tournament information and update the attributes to match.
  */
  function __construct($locID = 0) 
  {
    //Initialize all default values...
    parent::__construct();
    
    //User Specific Attributes
    $this->location_ID = 0;													//The ID Number
		$this->location_name = 'unknown';								//Name of the Location
		$this->location_address = null;									//The Address Object
		$this->location_building = null;								//The Building Object
		$this->location_room = null;										//The Room Object
		$this->addressList = array();										//Array of Address Objects
		
    //Check for provided location_ID
    if($locID != 0)
    {
      //Get location details
      $this->Initialize($locID);
    }
  }
  //End Constructor Method
  
  ///////////////////
  /// GET Methods ///
  ///////////////////
  public function ID(){ RETURN $this->location_ID; }
	public function Name(){ RETURN $this->location_name; }
	public function Building(){ RETURN $this->location_building; } //(Object)
	public function Room(){ RETURN $this->location_room; } //(Object)
  
	//Get Address Object
	public function Address($ID = null)
	{
		if($ID != null)
		{
			$addresses = $this->AddressList();
			foreach($addresses as $address)
			{
				if($ID == $address->ID())
				{
					RETURN $address;
				}
			}
		}
		else
		{
			RETURN $this->location_address;
		}

		RETURN false;//Not Found
	}	
	
  //-------------------//
  //- CREATE LOCATION -//
  //-------------------//
  /*
    This method uses the provided data to create a new location.
    RETURNS: location_ID or false
  */ 
  public function Create($name, $data)
  {
 		//Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //Create link to database
    $DB = new dbAccess();
    if($DB->Link())
    { 
      //Format the location data into field/value pairs
      $fieldList = array($CONFIG['LocationName_col']);
      $valueList = array($name);
      if($data != null)
      {
        foreach($data as $key => $value)
        {
          $fieldList[] = $key;
          $valueList[] = $value;
        }
      }
      
      //Attempt to Inject the new location into the database
      if($DB->Inject($CONFIG['Location_Table'], $fieldList, $valueList))
      {
        //Success return the new location ID
        $DB->Sever();
        RETURN $DB->InjectID();
        
      }else{ $this->LogError($this->error = 'Injection Failure -- location::Create()'); $DB->Sever(); }
    }else{ $this->LogError($this->error = 'No Data Link! -- location::Create()'); }
    
    RETURN false;
  }  
  
  
  //------------------//
  //- ALTER LOCATION -//
  //------------------//
  /*
    This method uses the provided data formatted in fieldName => value pairs
    to update the data associated to this location in the database.
  */ 
  public function Alter($data)
  {
 		//Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    if($data === false){ RETURN false;}//A problem occurred
    
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
      //Isolate the specific location
      $whereLoc = $CONFIG['LocationID_col'].'='.$this->location_ID;
      
      //Try to update the database
      if($DB->Refresh($CONFIG['Location_Table'], $fieldList, $valueList, $whereLoc))
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
          
        }else{ $this->LogError(($this->error = $DB->Error()).' -- location::Update() '); }
      }
      $DB->Sever();//Sever DB Link
    }else{ $this->LogError($this->error = 'No Data Link! -- location::Update()'); }
    
    RETURN false;
  }
  //END Alter method
  
	
	//-------------------//
	//-- KILL LOCATION --//
	//-------------------//
	public function Kill()
	{
    //Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //Create link to the database
    $DB = new dbAccess();
    if($DB->Link())
    {
      //Attempt to destroy the location record from the database
      $whereLoc = $CONFIG['LocationID_col'].'='.$this->location_ID;
      if($DB->Kill($CONFIG['Location_Table'], $whereLoc))
      {
        //Success
        $DB->Sever();
        RETURN true;
        
      }else{ $this->LogError($this->error = 'Kill Failure! -- location::Kill()'); }
      $DB->Sever();
    }else{ $this->LogError($this->error = 'No Data Link! -- location::Kill()'); }
    
    //Failure
    RETURN false;		
	}
	//END Kill Method
	
	
	//----------------//
	//- Address List -//
	//----------------//
	//Returns array of available Addresses as Address objects
	public function AddressList($refresh = false)
	{
		//Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }

    //Check if data already exists
		//If specific true or refresh true then retrieve from DB
    if(empty($this->addressList) || $refresh)
    {
      //Create database link
      $DB = new dbAccess();
      if($DB->Link())
      {
				//Snatch Data from DB
				if($DB->Snatch($CONFIG['Address_Table'], $CONFIG['AddressID_col']))
				{
					//Retrieve Results
					$data = $DB->Result();

					if(isset($data[0]))
					{
						//Multi
						//Create a new address object for each ID we retrieved.
						foreach($data as $row)
						{
							$this->addressList[] = new address($row[$CONFIG['AddressID_col']]);
						}
					}
					else
					{
						//Single
						$this->addressList[] = new address($data[$CONFIG['AddressID_col']]);
					}
        }else{ $this->LogError('Snatch failure -- location::AddressList()'); }
        $DB->Sever();//Sever DB Link
      }else{ $this->LogError('No Data Link! -- location::AddressList()'); }
    }
    
    //Success!?!
    RETURN (empty($this->addressList)) ? false : $this->addressList;
	}
	
	
  //---------------------//
  //- Initialize Method -//
  //---------------------//
  /*
    This method uses the provided location_ID to gather the associated location data 
    from the database and initialize the object instance with the location details.
  */  
  final protected function Initialize($locID = 0)
  {
		//Get Required Configuration Variables
		$CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
		//Set the location ID of this object 
		//equal to the one provided (or 0)
		$this->location_ID = ($this->location_ID != 0) ? $this->location_ID : $locID;
    
    //verify location has ID
    if($this->location_ID != 0)
    {
      //Get the location data!
      $DB = new dbAccess();
      if($DB->Link())
      {
        $whereLoc = $CONFIG['LocationID_col'].'='.$this->location_ID;
        if($DB->Snatch($CONFIG['Location_Table'], '*', $whereLoc))
        {
          //Request the results
          $data = $DB->Result();
         
          //Initialize Object with DB data
					//Already have location ID!
          $this->location_name  = $data[$CONFIG['LocationName_col']];
					
					//Create the Address Object
					$this->location_address = new address($data[$CONFIG['LocationAddress_col']]);
					
					//Get the Building Object
					$this->location_building = $this->location_address->Building($data[$CONFIG['LocationBuilding_col']]);
					
					//Get the Room Object
					$this->location_room = $this->location_building->Room($data[$CONFIG['LocationRoom_col']]);
					
					//Success!
					$DB->Sever();
					RETURN true;

        }else{ if($DB->Error() != '0 results found'){ $this->LogError('Snatch Failure -- location::Initialize()'); }}
        $DB->Sever();//Sever DB Link
      }else{ $this->LogError('No Data Link -- location::Initialize()'); }
    }else{ $this->LogError("NO ID! -- location::Initialize()");}
    //Failure
    RETURN false;
  }
  //END Initialize
}
//END USER CLASS

?>