<?php
/*
  Purpose:  NiFrame
  
  File:     Room Class File
  
  Author:   Nathan Poole - github/npocodes
           
  Date:     July 2019
     
  Updated:  
           
	[ !! This Class Utilizes a configuration file !! ]
*/
//require_once('inc/classes/class.nerror.php');
//require_once('inc/classes/class.dbAccess.php');

//++++++++++++++++++++//
//++ THE ROOM CLASS ++//
//++++++++++++++++++++//
/*
  The Room class provides all base room attributes
  as well as associated room methods.
*/
class room extends nerror {

//-------------------------------//
      //%% Attributes %%//
//-------------------------------//
  protected $room_ID;				//The ID Number
	protected $room_name;			//The Name
	protected $room_capacity;	//The Max Room Capacity
	protected $room_building;	//Building ID this room belongs to.
	protected $amenityList;		//List of available amenities (ID, Name)


//-------------------------------------------//
            //## Methods ##//
//-------------------------------------------//

  ///////////////////
  /// Constructor ///
  ///////////////////
  /*
    This constructor will initialize attributes to their default values and if provided with a 
    room_ID will retrieve the associated information and update the attributes to match.
  */
  function __construct($roomID = 0) 
  {
    //Initialize all default values...
    parent::__construct();
    
    //Specific Attributes
    $this->room_ID = 0;						//The ID Number
		$this->room_name = 'unknown';	//The Name
		$this->room_capacity = 0;			//The Room Max Capacity
		$this->room_building = 0;			//The Building ID
    $this->amenityList = array();	//List of Amenity Objects
		
    //Check for provided ID
    if($roomID != 0)
    {
      //Get details
      $this->Initialize($roomID);
    }
  }
  //End Constructor Method
  
  ///////////////////
  /// GET Methods ///
  ///////////////////
  public function ID(){ RETURN $this->room_ID; }
	public function Name(){ RETURN $this->room_name; }
	public function Capacity(){ RETURN $this->room_capacity; }
	public function BuildingID(){ RETURN $this->room_building; }
  
	//Get a specific Amenity Object
	public function Amenity($ID)
	{
		$amenities = $this->AmenityList();
		foreach($amenities as $amenity)
		{
			if($ID == $amenity->ID())
			{
				RETURN $amenity;
			}
		}
		
		RETURN false;//Not Found
	}
	
	//------------//
	//- ADD ROOM -//
	//------------//
  public function Add($name, $buildingID, $capacity = 10)
  {
 		//Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //Create link to database
    $DB = new dbAccess();
    if($DB->Link())
    { 
      //Format the Room data into field/value pairs
      $fieldList = array($CONFIG['RoomName_col'], $CONFIG['RoomBuilding_col'], $CONFIG['RoomCapacity_col']);
      $valueList = array($name, $buildingID, $capacity);
      
      //Attempt to Inject the new building into the database
      if($DB->Inject($CONFIG['Room_Table'], $fieldList, $valueList))
      {
        //Success return the new ID
        $DB->Sever();
        RETURN $DB->InjectID();
        
      }else{ $this->LogError($this->error = 'Injection Failure -- room::Add()'); $DB->Sever(); }
    }else{ $this->LogError($this->error = 'No Data Link! -- room::Add()'); }
    
    RETURN false;
  }

	//--------------//
	//- ALTER ROOM -//
	//--------------//
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
      $whereLoc = $CONFIG['RoomID_col'].'='.$this->room_ID;
      
      //Try to update the database
      if($DB->Refresh($CONFIG['Room_Table'], $fieldList, $valueList, $whereLoc))
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
          
        }else{ $this->LogError(($this->error = $DB->Error()).' -- room::Alter() '); }
      }
      $DB->Sever();//Sever DB Link
    }else{ $this->LogError($this->error = 'No Data Link! -- room::Alter()'); }
    
    RETURN false;
  }

	
	//-------------//
	//- KILL ROOM -//
	//-------------//
	//Removes locations and amenityList
  public function Kill()
  {
    //Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //Create link to the database
    $DB = new dbAccess();
    if($DB->Link())
    {
			//Remove ALL amenities from this room
			$whereLoc = $CONFIG['AmenityListRoom_col'].'='.$this->room_ID;
			$killed = $DB->Kill($CONFIG['AmenityList_Table'], $whereLoc);
			$result = $DB->Result();
			if(!($killed) && $result != '0 rows affected')
			{
				$this->LogError('Failed to remove amenities -- room::Kill()');
			}
			else
			{
				//Remove any location records associated with this room
				$whereLoc = $CONFIG['LocationRoom_col'].'='.$this->room_ID;
				$killed = $DB->Kill($CONFIG['Location_Table'], $whereLoc);
				$result = $DB->Result();
				if(!($killed) && $result != '0 rows affected')
				{
					$this->LogError('Failed to remove locations -- room::Kill()');
				}
				else
				{
					//Attempt to destroy the room record from the database
					$whereLoc = $CONFIG['RoomID_col'].'='.$this->room_ID;
					$killed = $DB->Kill($CONFIG['Room_Table'], $whereLoc);
					$result = $DB->Result();
					if(!($killed) && $result != '0 rows affected')
					{
						//Failure
						$this->LogError('Kill Failure! -- room::Kill()');
					}
					else
					{
						//Success
						$DB->Sever();
						RETURN true;				
					}
				}
			}
      $DB->Sever();//Sever DB Connection
    }else{ $this->LogError($this->error = 'No Data Link! -- room::Kill()'); }
    
    //Failure
    RETURN false;
  }	
	
	
	//----------------//
	//- Amenity List -//
	//----------------//
	//Retrieves List of Amenities for *this room
	//If refresh == true, ignores cached data and retrieves from DB
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
				//Snatch Data from DB
				$whereLoc = $CONFIG['AmenityListRoom_col'].'='.$this->room_ID;
				if($DB->Snatch($CONFIG['AmenityList_Table'], $CONFIG['AmenityListAmenity_col'], $whereLoc))
				{
					//Retrieve Results
					$data = $DB->Result();
					
          //Check for multi result
          if(isset($data[0]))
          {
            //cycle each result
            foreach($data as $row)
            {
              $this->amenityList[] = new amenity($row[$CONFIG['AmenityListAmenity_col']]);
            }
          }
          else
          {
            //Single result
            $this->amenityList[] = new amenity($data[$CONFIG['AmenityListAmenity_col']]);
          }
        }else{ if($DB->Error() != '0 results found'){ $this->LogError('Snatch failure -- room::AmenityList()'); }}
        $DB->Sever();//Sever DB Link
      }else{ $this->LogError('No Data Link! -- room::AmenityList()'); }
    }
    
    //Success!?!
    RETURN (empty($this->amenityList)) ? false : $this->amenityList;				
	}
	
	
	//---------------//
	//- Add Amenity -//
	//---------------//
	//Add Amenity to Amenity List
	public function AddAmenity($amenityID)
  {
 		//Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //Create link to database
    $DB = new dbAccess();
    if($DB->Link())
    { 
      //Format the data into field/value pairs
      $fieldList = array($CONFIG['AmenityListAmenity_col'], $CONFIG['AmenityListRoom_col']);
      $valueList = array($amenityID, $this->room_ID);
      
      //Attempt to Inject into the database
      if($DB->Inject($CONFIG['AmenityList_Table'], $fieldList, $valueList))
      {
        //Success return the new ID
        $DB->Sever();
        RETURN $DB->InjectID();
        
      }else{ $this->LogError($this->error = 'Injection Failure -- room::AddAmenity()'); $DB->Sever(); }
    }else{ $this->LogError($this->error = 'No Data Link! -- room::AddAmenity()'); }
    
    RETURN false;
  }
		

	//----------------//
	//- KILL AMENITY -//
	//----------------//
	//Remove Amenity From AmenityList
  public function KillAmenity($amenityID)
  {
    //Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //Create link to the database
    $DB = new dbAccess();
    if($DB->Link())
    {
			//Attempt to destroy the record from the database
			$whereLoc = array(
				$CONFIG['AmenityListAmenity_col'].'='.$amenityID,
				$CONFIG['AmenityListRoom_col'].'='.$this->room_ID
			);
			
			if($DB->Kill($CONFIG['AmenityList_Table'], $whereLoc))
			{
				//Success
				$DB->Sever();
				RETURN true;
				
			}else{ $this->LogError($this->error = 'Kill Failure! -- room::KillAmenity()'); }
      $DB->Sever();
    }else{ $this->LogError($this->error = 'No Data Link! -- room::KillAmenity()'); }
    
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
  final protected function Initialize($roomID = 0)
  {
		//Get Required Configuration Variables
		$CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
		//Set the ID of this object equal to the one provided (or 0)
		$this->room_ID = ($this->room_ID != 0) ? $this->room_ID : $roomID;
    
    //verify ID available
    if($this->room_ID != 0)
    {
      //Get the data!
      $DB = new dbAccess();
      if($DB->Link())
      {
        $whereLoc = $CONFIG['RoomID_col'].'='.$this->room_ID;
        if($DB->Snatch($CONFIG['Room_Table'], '*', $whereLoc))
        {
          //Request the results
          $data = $DB->Result();
         
          //Initialize Object with DB data
					//Already have address ID!
					$this->room_name = $data[$CONFIG['RoomName_col']];
					$this->room_building = $data[$CONFIG['RoomBuilding_col']];
					$this->room_capacity = $data[$CONFIG['RoomCapacity_col']];
					
        }else{ if($DB->Error() != '0 results found'){ $this->LogError('Snatch Failure -- room::Initialize()'); }}
        $DB->Sever();//Sever DB Link
      }else{ $this->LogError('No Data Link -- room::Initialize()'); }
    }else{ $this->LogError("NO ID! -- room::Initialize()");}
    //Failure
    RETURN false;
  }
  //END Initialize
}
//END ROOM CLASS

?>