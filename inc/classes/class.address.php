<?php
/*
  Purpose:  NiFrame
  
  File:     Address Class File
  
  Author:   Nathan Poole - github/npocodes
           
  Date:     July 2019
     
  Updated:  
           
	[ !! This Class Utilizes a configuration file !! ]
*/
//require_once('inc/classes/class.nerror.php');
//require_once('inc/classes/class.dbAccess.php');
///require_once('inc/classes/class.building.php');

//+++++++++++++++++++++++//
//++ THE ADDRESS CLASS ++//
//+++++++++++++++++++++++//
/*
  The Address class provides all base address attributes
  as well as associated address methods.
*/
class address extends nerror {

//-------------------------------//
      //%% Attributes %%//
//-------------------------------//
  protected $address_ID;				//The ID Number
	protected $address_country;		//The country name
	protected $address_state;			//The State name/abbreviation
	protected $address_city;			//The City Name
	protected $address_street;		//The Street Name
	protected $address_number;		//The Address Number
	protected $address_zip;				//The ZipCode
	protected $buildingList;			//List of available buildings (objects)


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
  function __construct($addressID = 0) 
  {
    //Initialize all default values...
    parent::__construct();
    
    //Specific Attributes
    $this->address_ID = 0;							//The ID Number
		$this->address_country = 'unknown';	//The Country Name
		$this->address_state = 'unknown';		//The State Name/Abbreviation
		$this->address_city = 'unknown';		//The City Name
		$this->address_street = 'unknown';	//The Street Name
		$this->address_number = 0;					//The Address Number
		$this->address_zip = 0;							//The ZipCode
		$this->buildingList = array();			//List of available buildings (objects)
    
    //Check for provided address_ID
    if($addressID != 0)
    {
      //Get details
      $this->Initialize($addressID);
    }
  }
  //End Constructor Method
  
  ///////////////////
  /// GET Methods ///
  ///////////////////
  public function ID(){ RETURN $this->address_ID; }
	public function Country(){ RETURN $this->address_country; }
	public function State(){ RETURN $this->address_state; }
	public function City(){ RETURN $this->address_city; }
	public function Street(){ RETURN $this->address_street; }
	public function AddressNum(){ RETURN $this->address_number; }
	public function Zipcode(){ RETURN $this->address_zip; }
	public function Full($country = false){
		$retString = $this->address_number.' '.$this->address_street.', '.$this->address_city.', '.$this->address_state.' '.$this->address_zip;
		if($country){ $retString .= ' - '.$this->address_country; }
		RETURN $retString;
	}
  
	//Get a specific Building Object
	public function Building($ID)
	{
		$buildings = $this->BuildingList();
		foreach($buildings as $building)
		{
			if($ID == $building->ID())
			{
				RETURN $building;
			}
		}
		
		RETURN false;//Not Found
	}
	
	//------------------//
	//- CREATE ADDRESS -//
	//------------------//
	// Country, State, City, Street, Number, Zipcode
  public function Create($data)
  {
 		//Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //Create link to database
    $DB = new dbAccess();
    if($DB->Link())
    {
			//Split keyed array into fields and values
			$fieldList = array();
			$valueList = array();
			foreach($data as $key => $value)
			{
				$fieldList[] = $key;
				$valueList[] = $value;
			}
      
      //Attempt to Inject into the database
      if($DB->Inject($CONFIG['Address_Table'], $fieldList, $valueList))
      {
        //Success return the new ID
        $DB->Sever();
        RETURN $DB->InjectID();
        
      }else{ $this->LogError($this->error = 'Injection Failure -- address::AddAddress()'); $DB->Sever(); }
    }else{ $this->LogError($this->error = 'No Data Link! -- address::AddAddress()'); }
    
    RETURN false;
  }

	
	//-----------------//
	//- ALTER ADDRESS -//
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
      //Isolate the specific address
      $whereLoc = $CONFIG['AddressID_col'].'='.$this->address_ID;
      
      //Try to update the database
      if($DB->Refresh($CONFIG['Address_Table'], $fieldList, $valueList, $whereLoc))
      {
          //Data updated
					$this->error = $DB->Result();
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
          
        }else{ $this->LogError(($this->error = $DB->Error()).' -- address::AlterAddress() '); }
      }
      $DB->Sever();//Sever DB Link
    }else{ $this->LogError($this->error = 'No Data Link! -- address::AlterAddress()'); }
    
    RETURN false;
  }	
	
	
  //----------------//
  //- KILL ADDRESS -//
  //----------------//
  /*
    This method kills the address by removing the 
    associated record from the database. Along with
		associated location, building, room and amenityList records.
  */
  public function Kill()
  {
    //Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //Create link to the database
    $DB = new dbAccess();
    if($DB->Link())
    {
			//Get all associated Building IDs for this location
			$whereLoc = $CONFIG['BuildingAddress_col'].'='.$this->address_ID;
			$snatched = $DB->Snatch($CONFIG['Building_Table'], $CONFIG['BuildingID_col'], $whereLoc);
			$result = $DB->Result();
			if(!($snatched) && $result != '0 results found')
			{
				//Failure
				$this->LogError('Building Snatch Failure -- address::Kill()');
			}
			else
			{
				//Force result to array of results
				$data = (!(isset($result[0]))) ? array($result) : $result;
				foreach($data as $row)
				{
					//Get All Rooms for current building
					$whereLoc = $CONFIG['RoomBuilding_col'].'='.$row[$CONFIG['BuildingID_col']];
					$snatched = $DB->Snatch($CONFIG['Room_Table'], $CONFIG['RoomID_col'], $whereLoc);
					$result = $DB->Result();
					if(!($snatched) && $result != '0 results found')
					{
						//Failure
						$this->LogError('Room Snatch Failure -- address::Kill()');
						
						//STOP - Avoid data fracture
						$DB->Sever();
						RETURN false;
					}
					else
					{
						//Force result to array of results
						$data2 = (!(isset($result[0]))) ? array($result) : $result;
						foreach($data2 as $row2)
						{
							//Remove ALL amenities from current room
							$whereLoc = $CONFIG['AmenityListRoom_col'].'='.$row2[$CONFIG['RoomID_col']];
							$killed = $DB->Kill($CONFIG['AmenityList_Table'], $whereLoc);
							$result = $DB->Result();
							if(!($killed) && $result != '0 rows affected')
							{
								//Failure
								$this->LogError('Failed to remove amenities from room:'.$row2[$CONFIG['RoomID_col']].'  -- address::Kill()');
								
								//STOP - Avoid data fracture
								$DB->Sever();
								RETURN false;
							}
						}//END ROOM LOOP
						
						//Remove ALL rooms from current building
						$whereLoc = $CONFIG['RoomBuilding_col'].'='.$row[$CONFIG['BuildingID_col']];
						$killed = $DB->Kill($CONFIG['Room_Table'], $whereLoc);
						$result = $DB->Result();
						if(!($killed) && $result != '0 rows affected')
						{
							//Failure
							$this->LogError('Failed to remove rooms from building:'.$row[$CONFIG['BuildingID_col']].' -- address::Kill()');
							
							//STOP - Avoid data fracture
							$DB->Sever();
							RETURN false;
						}
						
						//Remove ALL amenities from current building
						$whereLoc = $CONFIG['AmenityListBuilding_col'].'='.$row[$CONFIG['BuildingID_col']];
						$killed = $DB->Kill($CONFIG['AmenityList_Table'], $whereLoc);
						$result = $DB->Result();
						if(!($killed) && $result != '0 rows affected')
						{
							//Failure
							$this->LogError('Failed to remove building amenities -- address::Kill()');
							
							//STOP - Avoid data fracture
							$DB->Sever();
							RETURN false;
						}
					}
				}//END BUILDING LOOP
				
				//Remove ALL buildings from this address
				$whereLoc = $CONFIG['BuildingAddress_col'].'='.$this->address_ID;
				$killed = $DB->Kill($CONFIG['Building_Table'], $whereLoc);
				$result = $DB->Result();
				if(!($killed) && $result != '0 rows affected')
				{
					//Failure
					$this->LogError('Failed to remove buildings -- address::Kill()');
				}
				else
				{
					//Remove ALL location records utilizing this address
					$whereLoc = $CONFIG['LocationAddress_col'].'='.$this->address_ID;
					$killed = $DB->Kill($CONFIG['Location_Table'], $whereLoc);
					$result = $DB->Result();
					if(!($killed) && $result != '0 rows affected')
					{
						//Failure
						$this->LogError('Location Kill Failure! -- address::Kill()');
					}
					else
					{
						//Attempt to destroy the address record from the database
						$whereLoc = $CONFIG['AddressID_col'].'='.$this->address_ID;
						$killed = $DB->Kill($CONFIG['Address_Table'], $whereLoc);
						$result = $DB->Result();
						if(!($killed) && $result != '0 rows affected')
						{
							//Failure
							$this->LogError('Kill Failure! -- address::Kill()');
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
			//Sever DB Connection
      $DB->Sever();
    }else{ $this->LogError($this->error = 'No Data Link! -- address::KillAddress()'); }
    
    //Failure
    RETURN false;
  }
  

	//-----------------//
	//- Building List -//
	//-----------------//
	//If refresh == true, ignores cached data and retrieves from DB
	public function BuildingList($refresh = false)
	{
		//Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }

    //Check if data already exists
		//If specific true or refresh true then retrieve from DB
    if(empty($this->buildingList) || $refresh)
    {
      //Create database link
      $DB = new dbAccess();
      if($DB->Link())
      {
				//Snatch Data from DB
				$whereLoc = $CONFIG['BuildingAddress_col'].'='.$this->address_ID;
				if($DB->Snatch($CONFIG['Building_Table'], $CONFIG['BuildingID_col'], $whereLoc))
				{
					//Retrieve Results
					$data = $DB->Result();
					
          //Check for multi result
          if(isset($data[0]))
          {
            //cycle each result
            foreach($data as $row)
            {
              $this->buildingList[] = new building($row[$CONFIG['BuildingID_col']]);
            }
          }
          else
          {
            //Single result
            $this->buildingList[] = new building($data[$CONFIG['BuildingID_col']]);
          }
        }else{ if($DB->Error() != '0 results found'){ $this->LogError('Snatch failure -- address::BuildingList()'); }}
        $DB->Sever();//Sever DB Link
      }else{ $this->LogError('No Data Link! -- address::BuildingList()'); }
    }
    
    //Success!?!
    RETURN (empty($this->buildingList)) ? false : $this->buildingList;				
	}
	

  //---------------------//
  //- Initialize Method -//
  //---------------------//
  /*
    This method uses the provided ID to gather the associated data from the
    database and initialize the object instance with the details.
  */  
  final protected function Initialize($addressID = 0)
  {
		//Get Required Configuration Variables
		$CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
		//Set the address ID of this object 
		//equal to the one provided (or 0)
		$this->address_ID = ($this->address_ID != 0) ? $this->address_ID : $addressID;
    
    //verify address has ID
    if($this->address_ID != 0)
    {
      //Get the data!
      $DB = new dbAccess();
      if($DB->Link())
      {
        $whereLoc = $CONFIG['AddressID_col'].'='.$this->address_ID;
        if($DB->Snatch($CONFIG['Address_Table'], '*', $whereLoc))
        {
          //Request the results
          $data = $DB->Result();
         
          //Initialize Object with DB data
					//Already have address ID!
					$this->address_country = $data[$CONFIG['AddressCountry_col']];
					$this->address_state = $data[$CONFIG['AddressState_col']];
					$this->address_city = $data[$CONFIG['AddressCity_col']];
					$this->address_street = $data[$CONFIG['AddressStreet_col']];
					$this->address_number = $data[$CONFIG['AddressNumber_col']];
					$this->address_zip = $data[$CONFIG['AddressZip_col']];
					
        }else{ if($DB->Error() != '0 results found'){ $this->LogError('Snatch Failure -- address::Initialize()'); }}
        $DB->Sever();//Sever DB Link
      }else{ $this->LogError('No Data Link -- address::Initialize()'); }
    }else{ $this->LogError("NO ID! -- address::Initialize()");}
    //Failure
    RETURN false;
  }
  //END Initialize
}
//END ADDRESS CLASS

?>