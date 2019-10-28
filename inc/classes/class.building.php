<?php
/*
  Purpose:  NiFrame
  
  File:     Building Class File
  
  Author:   Nathan Poole - github/npocodes
           
  Date:     July 2019
     
  Updated:  
           
	[ !! This Class Utilizes a configuration file !! ]
*/
//require_once('inc/classes/class.nerror.php');
//require_once('inc/classes/class.dbAccess.php');
//require_once('inc/classes/class.amenity.php');
//require_once('inc/classes/class.room.php');


//++++++++++++++++++++++++//
//++ THE BUILDING CLASS ++//
//++++++++++++++++++++++++//
/*
  The Building class provides all base attributes
  as well as associated methods.
*/
class building extends nerror {

//-------------------------------//
      //%% Attributes %%//
//-------------------------------//
  protected $building_ID;				//The ID Number
	protected $building_name;			//The Name
	protected $building_address;	//The Address ID
	protected $roomList;					//List of available rooms (objects)
	protected $amenityList;				//List of available amenities (ID, Name)

//-------------------------------------------//
            //## Methods ##//
//-------------------------------------------//

  ///////////////////
  /// Constructor ///
  ///////////////////
  /*
    This constructor will initialize attributes to their default values and if provided with a 
    address_ID will retrieve the associated information and update the attributes to match.
  */
  function __construct($buildingID = 0) 
  {
    //Initialize all default values...
    parent::__construct();
    
    //Specific Attributes
    $this->building_ID = 0;						//The ID Number
		$this->building_name = 'unknown';	//The Name
		$this->building_address = 0;			//The Address ID
    $this->roomList = array();				//List of available room (objects)
		$this->amenityList = array();			//List of available amenities (ID, Name)
		
    //Check for provided ID
    if($buildingID != 0)
    {
      //Get details
      $this->Initialize($buildingID);
    }
  }
  //End Constructor Method
  
  ///////////////////
  /// GET Methods ///
  ///////////////////
  public function ID(){ RETURN $this->building_ID; }
	public function Name(){ RETURN $this->building_name; }
	public function AddressID(){ RETURN $this->building_address; }
  
	//Get a specific Room Object
	public function Room($ID)
	{
		$rooms = $this->RoomList();
		foreach($rooms as $room)
		{
			if($ID == $room->ID())
			{
				RETURN $room;
			}
		}
		
		RETURN false;//Not Found
	}
	
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

	//----------------//
	//- ADD BUILDING -//
	//----------------//
  public function Add($name, $addressID)
  {
 		//Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //Create link to database
    $DB = new dbAccess();
    if($DB->Link())
    { 
      //Format the Building data into field/value pairs
      $fieldList = array($CONFIG['BuildingName_col'], $CONFIG['BuildingAddress_col']);
      $valueList = array($name, $addressID);
      
      //Attempt to Inject the new building into the database
      if($DB->Inject($CONFIG['Building_Table'], $fieldList, $valueList))
      {
        //Success return the new ID
        $DB->Sever();
        RETURN $DB->InjectID();
        
      }else{ $this->LogError($this->error = 'Injection Failure -- building::Add()'); $DB->Sever(); }
    }else{ $this->LogError($this->error = 'No Data Link! -- building::Add()'); }
    
    RETURN false;
  }
	
	//------------------//
	//- ALTER BUILDING -//
	//------------------//
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
      //Isolate the specific building
      $whereLoc = $CONFIG['BuildingID_col'].'='.$this->building_ID;
      
      //Try to update the database
      if($DB->Refresh($CONFIG['Building_Table'], $fieldList, $valueList, $whereLoc))
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
          
        }else{ $this->LogError(($this->error = $DB->Error()).' -- building::Alter() '); }
      }
      $DB->Sever();//Sever DB Link
    }else{ $this->LogError($this->error = 'No Data Link! -- building::Alter()'); }
    
    RETURN false;
  }
	

	//-----------------//
	//- KILL BUILDING -//
	//-----------------//
	//Will also remove associated locations, rooms and amenities.
  public function Kill()
  {
    //Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //Create link to the database
    $DB = new dbAccess();
    if($DB->Link())
    {
			//Get All Rooms for this building
			$whereLoc = $CONFIG['RoomBuilding_col'].'='.$this->building_ID;
			$snatched = $DB->Snatch($CONFIG['Room_Table'], $CONFIG['RoomID_col'], $whereLoc);
			$result = $DB->Result();
			if(!($snatched) && $result != '0 results found')
			{
				//Failure
				$this->LogError('Room Snatch Failure -- building::Kill()');
			}
			else
			{
				//Force result to array of results
				$data = (!(isset($result[0]))) ? array($result) : $result;
				foreach($data as $row)
				{
					//Remove ALL amenities from current room
					$whereLoc = $CONFIG['AmenityListRoom_col'].'='.$row[$CONFIG['RoomID_col']];
					$killed = $DB->Kill($CONFIG['AmenityList_Table'], $whereLoc);
					$result = $DB->Result();
					if(!($killed) && $result != '0 rows affected')
					{
						//Failure
						$this->LogError('Failed to remove amenities from room:'.$row[$CONFIG['RoomID_col']].'  -- building::Kill()');
						
						//STOP! - Avoid data fracture
						$DB->Sever();
						RETURN false;
					}
				}
				
				//Remove ALL rooms from this building
				$whereLoc = $CONFIG['RoomBuilding_col'].'='.$this->building_ID;
				$killed = $DB->Kill($CONFIG['Room_Table'], $whereLoc);
				$result = $DB->Result();
				if(!($killed) && $result != '0 rows affected')
				{
					//Failure
					$this->LogError('Failed to remove rooms -- building::Kill()');
				}
				else
				{
					//Remove ALL amenities from current building
					$whereLoc = $CONFIG['AmenityListBuilding_col'].'='.$this->building_ID;
					$killed = $DB->Kill($CONFIG['AmenityList_Table'], $whereLoc);
					$result = $DB->Result();
					if(!($killed) && $result != '0 rows affected')
					{
						//Failure
						$this->LogError('Failed to remove building amenities -- building::Kill()');
					}
					else
					{
						//Remove any location records associated with this building
						$whereLoc = $CONFIG['LocationBuilding_col'].'='.$this->building_ID;
						$killed = $DB->Kill($CONFIG['Location_Table'], $whereLoc);
						$result = $DB->Result();
						if(!($killed) && $result != '0 rows affected')
						{
							//Failure
							$this->LogError('Failed to remove locations -- building::Kill()');
						}
						else
						{
							//Attempt to destroy the building record from the database
							$whereLoc = $CONFIG['BuildingID_col'].'='.$buildingID;
							$killed = $DB->Kill($CONFIG['Building_Table'], $whereLoc);
							$result = $DB->Result();
							if(!($killed) && $result != '0 rows affected')
							{
								//Failure
								$this->LogError('Kill Failure! -- building::Kill()');
							}
							else
							{
								//Success
								$DB->Sever();
								RETURN true;				
							}							
						}
					}
				}
			}
      $DB->Sever();//Sever the DB connection
    }else{ $this->LogError($this->error = 'No Data Link! -- building::Kill()'); }
    
    //Failure
    RETURN false;
  }
	

	//-------------//
	//- Room List -//
	//-------------//
	//If refresh == true, ignores cached data and retrieves from DB
	public function RoomList($refresh = false)
	{
		//Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }

    //Check if data already exists
    if(empty($this->roomList) || $refresh)
    {
      //Create database link
      $DB = new dbAccess();
      if($DB->Link())
      {
				//Snatch Data from DB
				$whereLoc = $CONFIG['RoomBuilding_col'].'='.$this->building_ID;
				if($DB->Snatch($CONFIG['Room_Table'], $CONFIG['RoomID_col'], $whereLoc))
				{
					//Retrieve Results
					$data = $DB->Result();
					
          //Check for multi result
          if(isset($data[0]))
          {
            //cycle each result
            foreach($data as $row)
            {
              $this->roomList[] = new room($row[$CONFIG['RoomID_col']]);
            }
          }
          else
          {
            //Single result
            $this->roomList[] = new room($data[$CONFIG['RoomID_col']]);
          }
        }else{ if($DB->Error() != '0 results found'){ $this->LogError('Snatch failure -- building::RoomList()'.$DB->Error()); }}
        $DB->Sever();//Sever DB Link
      }else{ $this->LogError('No Data Link! -- building::RoomList()'); }
    }
    
    //Success!?!
    RETURN (empty($this->roomList)) ? false : $this->roomList;				
	}
		
	
	//----------------//
	//- Amenity List -//
	//----------------//
	//Retrieves List of Amenities for *this building
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
				$whereLoc = $CONFIG['AmenityListBuilding_col'].'='.$this->building_ID;
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
        }else{ if($DB->Error() != '0 results found'){ $this->LogError('Snatch failure -- building::AmenityList()'); }}
        $DB->Sever();//Sever DB Link
      }else{ $this->LogError('No Data Link! -- building::AmenityList()'); }
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
      $fieldList = array($CONFIG['AmenityListAmenity_col'], $CONFIG['AmenityListBuilding_col']);
      $valueList = array($amenityID, $this->building_ID);
      
      //Attempt to Inject into the database
      if($DB->Inject($CONFIG['AmenityList_Table'], $fieldList, $valueList))
      {
        //Success return the new ID
        $DB->Sever();
        RETURN $DB->InjectID();
        
      }else{ $this->LogError($this->error = 'Injection Failure -- building::AddAmenity()'); $DB->Sever(); }
    }else{ $this->LogError($this->error = 'No Data Link! -- building::AddAmenity()'); }
    
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
				$CONFIG['AmenityListBuilding_col'].'='.$this->building_ID
			);
			
			if($DB->Kill($CONFIG['AmenityList_Table'], $whereLoc))
			{
				//Success
				$DB->Sever();
				RETURN true;
				
			}else{ $this->LogError($this->error = 'Kill Failure! -- building::KillAmenity()'); }
      $DB->Sever();
    }else{ $this->LogError($this->error = 'No Data Link! -- building::KillAmenity()'); }
    
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
  final protected function Initialize($buildingID = 0)
  {
		//Get Required Configuration Variables
		$CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
		//Set the ID of this object equal to the one provided (or 0)
		$this->building_ID = ($this->building_ID != 0) ? $this->building_ID : $buildingID;
    
    //verify ID available
    if($this->building_ID != 0)
    {
      //Get the data!
      $DB = new dbAccess();
      if($DB->Link())
      {
        $whereLoc = $CONFIG['BuildingID_col'].'='.$this->building_ID;
        if($DB->Snatch($CONFIG['Building_Table'], '*', $whereLoc))
        {
          //Request the results
          $data = $DB->Result();
         
          //Initialize Object with DB data
					//Already have ID!
					$this->building_name = $data[$CONFIG['BuildingName_col']];
					$this->building_address = $data[$CONFIG['BuildingAddress_col']];
					
        }else{ if($DB->Error() != '0 results found'){ $this->LogError('Snatch Failure -- building::Initialize()'); }}
        $DB->Sever();//Sever DB Link
      }else{ $this->LogError('No Data Link -- building::Initialize()'); }
    }else{ $this->LogError("NO ID! -- building::Initialize()");}
    //Failure
    RETURN false;
  }
  //END Initialize
}
//END BUILDING CLASS

?>