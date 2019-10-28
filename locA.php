<?php
/*
  Purpose:  Location Administration Driver File - NiFrame
  
  FILE:     The Location Administration driver file handles location specific use-case
            scenarios (modes) that require Administrative permissions such as: 
            creating a new location, deleting locations, editing address, buildings, rooms, etc...
            
  Author:   Nathan Poole - github/npocodes
  Date:     July 2019
	Updated:	
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

//Check for [Add Location] Mode
if(isset($_INPUT['add']))
{
	//Set the Page Name
	$T_VAR['PAGE_NAME'] = 'Add Address';

	//Set the default HTML file to use
	$T_FILE = 'message.html';
	
  //Check for Form submission
  if(isset($_INPUT['submitBtn']))
  {
		$success = false;
		if(isset($_INPUT[$CONFIG['LocationName_col']]) && !(empty($_INPUT[$CONFIG['LocationName_col']])))
		{
			if(isset($_INPUT['aid']) && $_INPUT['aid'] != 0 && $_INPUT['aid'] != '')
			{ 
				$data[$CONFIG['LocationAddress_col']] = $_INPUT['aid']; 
				if(isset($_INPUT['bid']) && !(empty($_INPUT['bid']))){ $data[$CONFIG['LocationBuilding_col']] = $_INPUT['bid']; }
				if(isset($_INPUT['rid']) && !(empty($_INPUT['rid']))){ $data[$CONFIG['LocationRoom_col']] = $_INPUT['rid']; }
				$location = new location();
				if($location->Create($_INPUT[$CONFIG['LocationName_col']], $data))
				{
					$T_VAR['MSG'] = 'Successfully created new location record!';
					$success = true;
					
				}else{ $T_VAR['MSG'] = 'Failed to create new location!'; }				
			}else{ $T_VAR['MSG'] = 'You must choose an address for this location!'; }
		}else{ $T_VAR['MSG'] = 'You must provide a name for this location!'; }
		
    //Set the message REDIRECT (remember input)
		$rString = '3;url=locA.php?add';
		if(!($success))
		{
			if(isset($_INPUT[$CONFIG['LocationName_col']])){ $rString .= '&'.$CONFIG['LocationName_col'].'='.$_INPUT[$CONFIG['LocationName_col']]; }
			if(isset($_INPUT['aid'])){ $rString .= '&aid='.$_INPUT['aid']; }
			if(isset($_INPUT['bid'])){ $rString .= '&bid='.$_INPUT['bid']; }
			if(isset($_INPUT['rid'])){ $rString .= '&rid='.$_INPUT['rid']; }
		}
    $T_VAR['REDIRECT'] = $rString;	
	}
	else
	{
		//Display Form
		$T_FILE = 'locAdd.html';
		
		//Set default message
		$T_VAR['MSG'] = 'Create Location';
		
		//INPUT HOLD OVER
		$T_VAR['NAME'] = (isset($_INPUT[$CONFIG['LocationName_col']])) ? $_INPUT[$CONFIG['LocationName_col']] : '';
		$aid = (isset($_INPUT['aid']) && !(empty($_INPUT['aid']))) ? $_INPUT['aid'] : '';
		$bid = (isset($_INPUT['bid']) && !(empty($_INPUT['bid']))) ? $_INPUT['bid'] : '';
		
		//Get list of current location records
		$DB = new dbaccess();
		if($DB->Link())
		{
			if($DB->Snatch($CONFIG['Location_Table'], $CONFIG['LocationID_col']))
			{
				$data = $DB->Result();
        if(isset($data[0]))
				{
					foreach($data as $row)
					{
						$location = new location($row[$CONFIG['LocationID_col']]);
						$T_VAR['LOCATION_ID'][] = $location->ID();
						$T_VAR['LOCATION_NAME'][] = $location->Name();
						$T_VAR['LOCATION_ADDRESS'][] = $location->Address()->Full();
						$T_VAR['LOCATION_BUILDING'][] = $location->Building()->Name();//Name
						$T_VAR['LOCATION_ROOM'][] = $location->Room()->Name();//Name
					}
				}
				else
				{
					//Single Row
					$location = new location($data[$CONFIG['LocationID_col']]);
					$T_VAR['LOCATION_ID'][] = $location->ID();
					$T_VAR['LOCATION_NAME'][] = $location->Name();
					$T_VAR['LOCATION_ADDRESS'][] = $location->Address()->Full();
					$T_VAR['LOCATION_BUILDING'][] = $location->Building()->Name();
					$T_VAR['LOCATION_ROOM'][] = $location->Room()->Name();
				}
				
				//Count # of Locations
				$count = count($T_VAR['LOCATION_ID']);
				$T_COND[] = '!LOCATIONS'.$count;
				
			}else{ $T_COND[] = 'LOCATION_LIST'; }//Nothing to display
		}else{ $T_COND[] = 'LOCATION_LIST'; }//Nothing to display
		
		//Get a list of addresses
		$location = new location();
		$addressList = $location->AddressList();
		if($addressList !== false)
		{
			foreach($addressList as $address)
			{
				$T_VAR['ADDRESS_ID'][] = $address->ID();
				$T_VAR['ADDRESS_FULL'][] = $address->Full();
				$T_VAR['SELECTED_A'][] = ($aid != '' && $aid == $address->ID()) ? 'SELECTED' : '';
			}

			//Count # of Addresses
			$count = count($addressList);
			$T_COND[] = '!ADDRESSES'.$count;//REPEAT COND
			
		}else{ $T_COND[] = 'ADDRESS_LIST'; }//No Address options

		//Check for an address ID, get buildings list
		if($aid != '')
		{
			//Create list of buildings associated with given address
			$address = new address($aid);
			$buildingList = $address->BuildingList();//Specific to address
			if($buildingList !== false)
			{
				foreach($buildingList as $building)
				{
					$T_VAR['BUILDING_ID'][] = $building->ID();
					$T_VAR['BUILDING_NAME'][] = $building->Name();
					$T_VAR['SELECTED_B'][] = ($bid != '' && $bid == $building->ID()) ? 'SELECTED' : '';
				}

				//Count # of Buildings
				$count = count($buildingList);
				$T_COND[] = '!BUILDINGS'.$count;//REPEAT COND
				
			}else{ $T_COND[] = 'BUILDING_LIST'; }//No Building options
		}else{ $T_COND[] = 'BUILDING_LIST'; }//No Buliding options
		
		//Check for a building ID, get rooms list
		if($bid != '')
		{
			//Create list of rooms associated with given building
			//$address = new address($aid);
			$roomList = $address->Building($bid)->RoomList();//Specific to building
			if($roomList !== false)
			{
				foreach($roomList as $room)
				{
					$T_VAR['ROOM_ID'][] = $room->ID();
					$T_VAR['ROOM_NAME'][] = $room->Name();
					$T_VAR['ROOM_CAPACITY'][] = $room->Capacity();
				}
				
				//Count # of Rooms
				$count = count($roomList);
				$T_COND[] = '!ROOMS'.$count;//REPEAT COND
				
			}else{ $T_COND[] = 'ROOM_LIST'; }//No Room options
		}else{ $T_COND[] = 'ROOM_LIST'; }//No Room options
	}
}


//Check for [Edit Location] Mode
if(isset($_INPUT['edit']))
{
  //Set the Page Name
  $T_VAR['PAGE_NAME'] = 'Edit Location';
  
  //Set the default HTML file to use
  $T_FILE = 'message.html';
 
  //Check for Form submission
  if(isset($_INPUT['submit']))
  {
		//Require Location ID
		if(isset($_INPUT['lid']) && !(empty($_INPUT['lid'])))
		{
			//Create an location object to use
			$location = new location($_INPUT['lid']);
			
			//Isolate only the input relevant to location
			foreach($_INPUT as $key => $value)
			{
				$skipList = array('submit', 'edit', 'lid');
				if(!(in_array($key, $skipList)))
				{
					$data[$key] = $value;
				}
			}
			
			//Attempt to alter the record.
			if($location->Alter($data))
			{
				$T_VAR['MSG'] = 'Successfully updated location record!';
				
			}else{ $T_VAR['MSG'] = 'Failed to alter location record!'; }
		}else{ $T_VAR['MSG'] = 'You must provide a location ID!'; }
	
    //Finally set the message REDIRECT
    $T_VAR['REDIRECT'] = '3;url=locA.php?add';
	}
}


//Check for [Remove(del) Location] Mode
if(isset($_INPUT['del']))
{
  //Determine if ID provided
  if(isset($_INPUT['lid']) && $_INPUT['lid'] != 0)
  {
    //Set the HTML file to use
    $T_FILE = 'message.html';
    
    //Create the object to use
    $location = new location($_INPUT['lid']);
    
		//Go in for the Kill, then respond and redirect
		$T_VAR['MSG'] = ($location->Kill()) ? 'Location ID:'.$location->ID().' has been killed!' : 'Failed to kill Location ID:'.$location->ID();
		
  }else{ $T_VAR['MSG'] = 'A Location ID number must be provided!'; }
  
  //Finally set the message REDIRECT
  $T_VAR['REDIRECT'] = '3;url=locA.php?add';
}


//Check for [Add Address] Mode
if(isset($_INPUT['addAddress']))
{
  //Set the Page Name
  $T_VAR['PAGE_NAME'] = 'Add Address';
  
  //Set the default HTML file to use
  $T_FILE = 'message.html';

	//Create an address object to use
	$address = new address();
 
  //Check for Form submission
  if(isset($_INPUT['submit']))
  {
		//REQUIRED DATA {Number, Street, City, State}
		if(isset($_INPUT[$CONFIG['AddressNumber_col']]) && !(empty($_INPUT[$CONFIG['AddressNumber_col']])))
		{
			if(isset($_INPUT[$CONFIG['AddressStreet_col']]) && !(empty($_INPUT[$CONFIG['AddressStreet_col']])))
			{
				if(isset($_INPUT[$CONFIG['AddressCity_col']]) && !(empty($_INPUT[$CONFIG['AddressCity_col']])))
				{
					if(isset($_INPUT[$CONFIG['AddressState_col']]) && !(empty($_INPUT[$CONFIG['AddressState_col']])))
					{
						//Isolate only the input relevant to address creation
						foreach($_INPUT as $key => $value)
						{
							$skipList = array('submit', 'addAddress');
							if(!(in_array($key, $skipList)))
							{
								$data[$key] = $value;
							}
						}
						
						//Attempt to create the new address
						if($address->Create($data))
						{
							
							$T_VAR['MSG'] = 'Successfully created new address record!';
							
						}else{ $T_VAR['MSG'] = 'Failed to create new address record!'; }
					}else{ $T_VAR['MSG'] = 'You must provide the state!'; }
				}else{ $T_VAR['MSG'] = 'You must provide a city name!'; }
			}else{ $T_VAR['MSG'] = 'You must provide a street name!'; }
		}else{ $T_VAR['MSG'] = 'You must provide an address number!'; }
		
    //Finally set the message REDIRECT
    $T_VAR['REDIRECT'] = '3;url=locA.php?addAddress';
  }
  else
  {
    //Show the Add Address Form.
    $T_FILE = 'addressAdd.html';

		//Set default message
		$T_VAR['MSG'] = 'Create Address';
    
		//Connect to the DB and get List of all known Addresses
    $DB = new dbAccess();
    if($DB->Link())
    {
      //Attempt to snatch the addresses from the db
      if($DB->Snatch($CONFIG['Address_Table']))
      {
        $data = $DB->Result();
        if(isset($data[0]))
				{
					foreach($data as $row)
					{
						//Multiple Rows
						$T_VAR['ADDRESS_ID'][] = $row[$CONFIG['AddressID_col']];
						$T_VAR['ADDRESS_NUM'][] = $row[$CONFIG['AddressNumber_col']];
						$T_VAR['ADDRESS_STREET'][] = $row[$CONFIG['AddressStreet_col']];
						$T_VAR['ADDRESS_CITY'][] = $row[$CONFIG['AddressCity_col']];
						$T_VAR['ADDRESS_STATE'][] = $row[$CONFIG['AddressState_col']];
						$T_VAR['ADDRESS_ZIP'][] = $row[$CONFIG['AddressZip_col']];
						$T_VAR['ADDRESS_COUNTRY'][] = $row[$CONFIG['AddressCountry_col']];
					}
				}
				else
				{
					//Single Row
					$T_VAR['ADDRESS_ID'][] = $data[$CONFIG['AddressID_col']];
					$T_VAR['ADDRESS_NUM'][] = $data[$CONFIG['AddressNumber_col']];
					$T_VAR['ADDRESS_STREET'][] = $data[$CONFIG['AddressStreet_col']];
					$T_VAR['ADDRESS_CITY'][] = $data[$CONFIG['AddressCity_col']];
					$T_VAR['ADDRESS_STATE'][] = $data[$CONFIG['AddressState_col']];
					$T_VAR['ADDRESS_ZIP'][] = $data[$CONFIG['AddressZip_col']];
					$T_VAR['ADDRESS_COUNTRY'][] = $data[$CONFIG['AddressCountry_col']];
				}
				
				//Count # of Addresses
				$count = count($T_VAR['ADDRESS_ID']);
				$T_COND[] = '!ADDRESSES'.$count;
				
      }else{ $T_COND[] = 'ADDRESS_LIST'; }//Nothing to display
    }else{ $T_COND[] = 'ADDRESS_LIST'; }//Nothing to display
  }
}


//Check for [Edit Address] Mode
if(isset($_INPUT['editAddress']))
{
  //Set the Page Name
  $T_VAR['PAGE_NAME'] = 'Edit Address';
  
  //Set the default HTML file to use
  $T_FILE = 'message.html';
 
  //Check for Form submission
  if(isset($_INPUT['submit']))
  {
		//Require Address ID
		if(isset($_INPUT['aid']) && !(empty($_INPUT['aid'])))
		{
			//Create an address object to use
			$address = new address($_INPUT['aid']);
			
			//Isolate only the input relevant to address
			foreach($_INPUT as $key => $value)
			{
				$skipList = array('submit', 'editAddress', 'aid');
				if(!(in_array($key, $skipList)))
				{
					$data[$key] = $value;
				}
			}
			
			//Attempt to alter the address record.
			if($address->Alter($data))
			{
				$T_VAR['MSG'] = 'Successfully altered address record!';
				
			}else{ $T_VAR['MSG'] = 'Failed to alter address record!'; }
		}else{ $T_VAR['MSG'] = 'You must provide an address ID!'; }
	
    //Finally set the message REDIRECT
    $T_VAR['REDIRECT'] = '3;url=locA.php?addAddress';
	}
}


//Check for [Remove(del) Address] Mode
if(isset($_INPUT['delAddress']))
{
  //Determine if ID provided
  if(isset($_INPUT['aid']) && $_INPUT['aid'] != 0)
  {
    //Set the HTML file to use
    $T_FILE = 'message.html';
    
    //Create the address object to use
    $address = new address($_INPUT['aid']);
    
		//Go in for the Kill, then respond and redirect
		$T_VAR['MSG'] = ($address->Kill()) ? 'Address ID:'.$address->ID().' has been killed!' : 'Failed to kill Address ID:'.$address->ID();
		
  }else{ $T_VAR['MSG'] = 'An Address ID number must be provided!'; }
  
  //Finally set the message REDIRECT
  $T_VAR['REDIRECT'] = '3;url=locA.php?addAddress';
}


//Check for [Add Building] Mode
if(isset($_INPUT['addBuilding']))
{
	//Set the Page Name
	$T_VAR['PAGE_NAME'] = 'Add Building';
	
	//Set the default HTML file to use
	$T_FILE = 'message.html';
		
	//Verify we have an addressID
	if(isset($_INPUT['aid']) && !(empty($_INPUT['aid'])))
	{
		//Create a building object to use
		$building = new building();
		
		//Create Address ID template variable
		$T_VAR['ADDRESS_ID'] = $_INPUT['aid'];
		
		//Check for Form submission
		if(isset($_INPUT['submit']))
		{
			if(isset($_INPUT[$CONFIG['BuildingName_col']]) && !(empty($_INPUT[$CONFIG['BuildingName_col']])))
			{
				//Attempt to create the new building
				if($building->Add($_INPUT[$CONFIG['BuildingName_col']], $_INPUT['aid']))
				{
					
					$T_VAR['MSG'] = 'Successfully created new building record!';
					
				}else{ $T_VAR['MSG'] = 'Failed to create new building record!'; }
			}else{ $T_VAR['MSG'] = 'You must provide a building Name!'; }
			
			//Finally set the message REDIRECT
			$T_VAR['REDIRECT'] = '3;url=locA.php?addBuilding&aid='.$_INPUT['aid'];
		}
		else
		{
			//Show the Add Building Form.
			$T_FILE = 'buildingAdd.html';
			
			//Create an address object to use
			$address = new address($_INPUT['aid']);
			
			//Get Full Address for Display
			$T_VAR['MSG'] = $address->Full();
			
			//Get list of buildings from address object
			$buildingList = $address->BuildingList();
			if($buildingList != false)
			{
				foreach($buildingList as $building)
				{
					$T_VAR['BUILDING_ID'][] = $building->ID();
					$T_VAR['BUILDING_NAME'][] = $building->Name();
				}
				
				//Count # of Buildings
				$count = count($buildingList);
				
				//Create HTML repeat condition
				$T_COND[] = '!BUILDINGS'.$count;
				
			}else{ $T_COND[] = 'BUILDING_LIST'; }//Nothing to display
		}
	}
	else
	{ 
		$T_VAR['MSG'] = 'You must provide an address ID number!';
		
		//Redirect back to address maganagement
		$T_VAR['REDIRECT'] = '3;url=locA.php?addAddress';	
	}
}


//Check for [Edit Building] Mode
if(isset($_INPUT['editBuilding']))
{
  //Set the Page Name
  $T_VAR['PAGE_NAME'] = 'Edit Building';
  
  //Set the default HTML file to use
  $T_FILE = 'message.html';
 
  //Check for Form submission
  if(isset($_INPUT['submit']))
  {
		//Require Building ID
		if(isset($_INPUT['bid']) && !(empty($_INPUT['bid'])))
		{
			//Create an building object to use
			$building = new building($_INPUT['bid']);

			//Format the data for the function
			$data[$CONFIG['BuildingName_col']] = $_INPUT[$CONFIG['BuildingName_col']];
			
			//Attempt to alter the address record.
			if($building->Alter($data))
			{
				$T_VAR['MSG'] = 'Successfully altered building record!';
				
			}else{ $T_VAR['MSG'] = 'Failed to alter building record!'; }
			
			//Set the message REDIRECT
			$T_VAR['REDIRECT'] = '3;url=locA.php?addBuilding&aid='.$building->AddressID();
		}
		else
		{ 
			$T_VAR['MSG'] = 'A Building ID number must be provided!';
			
			//Set the message REDIRECT 
			//(if given address ID adjust redirect)
			$T_VAR['REDIRECT'] = '3;url=locA.php?addAddress';
			if(isset($_INPUT['aid']) && !(empty($_INPUT['aid'])))
			{ 
				$T_VAR['REDIRECT'] = '3;url=locA.php?addBuilding&aid='.$_INPUT['aid']; 
			}	
		}
	}//else{ SHOW EDIT FORM }
}


//Check for [Remove(del) Building] Mode
if(isset($_INPUT['delBuilding']))
{
  //Determine if ID provided
	if(isset($_INPUT['bid']) && $_INPUT['bid'] != 0)
	{
		//Set the HTML file to use
		$T_FILE = 'message.html';
		
		//Create the building object to use
		$building = new building($_INPUT['bid']);
		
		//Go in for the Kill, then respond and redirect
		$T_VAR['MSG'] = ($building->Kill()) ? 'Building ID:'.$_INPUT['bid'].' has been killed!' : 'Failed to kill Building ID:'.$_INPUT['bid'];

		//Set the message REDIRECT
		$T_VAR['REDIRECT'] = '3;url=locA.php?addBuilding&aid='.$building->AddressID();
	}
	else
	{ 
		$T_VAR['MSG'] = 'A Building ID number must be provided!';
	
		//Set the message REDIRECT 
		//(if given address ID adjust redirect)
		$T_VAR['REDIRECT'] = '3;url=locA.php?addAddress';
		if(isset($_INPUT['aid']) && !(empty($_INPUT['aid'])))
		{ 
			$T_VAR['REDIRECT'] = '3;url=locA.php?addBuilding&aid='.$_INPUT['aid']; 
		}
	}
}


//Check for [Add Room] Mode
if(isset($_INPUT['addRoom']))
{
	//Set the Page Name
	$T_VAR['PAGE_NAME'] = 'Add Room';
	
	//Set the default HTML file to use
	$T_FILE = 'message.html';
		
	//Verify we have a buildingID
	if(isset($_INPUT['bid']) && !(empty($_INPUT['bid'])))
	{
		//Create a room  and building object to use
		$room = new room();
		$building = new building($_INPUT['bid']);
		
		//Create template Building ID variable
		$T_VAR['BUILDING_ID'] = $_INPUT['bid'];
	
		//Check for Form submission
		if(isset($_INPUT['submit']))
		{
			if(isset($_INPUT[$CONFIG['RoomName_col']]) && !(empty($_INPUT[$CONFIG['RoomName_col']])))
			{
				$capacity = (isset($_INPUT[$CONFIG['RoomCapacity_col']])) ? $_INPUT[$CONFIG['RoomCapacity_col']] : null;
				
				//Attempt to create the new address
				if($room->Add($_INPUT[$CONFIG['RoomName_col']], $_INPUT['bid'], $capacity))
				{
					
					$T_VAR['MSG'] = 'Successfully created new room record!';
					
				}else{ $T_VAR['MSG'] = 'Failed to create new room record!'; }
			}else{ $T_VAR['MSG'] = 'You must provide a room Name!'; }
			
			//Finally set the message REDIRECT
			$T_VAR['REDIRECT'] = '3;url=locA.php?addRoom&aid='.$building->AddressID().'&bid='.$building->ID();
		}
		else
		{
			//Show the Add Room Form.
			$T_FILE = 'roomAdd.html';
			
			//Create address object to use
			$address = new address($building->AddressID());
			
			//Get Full Address and Building Name for Display
			$T_VAR['ADDRESS_FULL'] = $address->Full();
			$T_VAR['BUILDING_NAME'] .= $building->Name();
			
			//Get list of rooms from building object for display
			$roomList = $building->RoomList();
			if($roomList != false)
			{
				foreach($roomList as $room)
				{
					$T_VAR['ROOM_ID'][] = $room->ID();
					$T_VAR['ROOM_NAME'][] = $room->Name();
					$T_VAR['ROOM_CAPACITY'][] = $room->Capacity();
				}
				
				//Get the room count
				$count = count($roomList);
				
				//Create HTML repeat condition
				$T_COND[] = '!ROOMS'.$count;
				
			}else{ $T_COND[] = 'ROOM_LIST'; }//Nothing to display
			
			//Get List of all possible Amenities for adding amenity to building
			$amenity = new amenity();
			$amenityList = $amenity->AmenityList();
			if($amenityList)
			{
				foreach($amenityList as $amenity)
				{
					$T_VAR['AMENITY_ID'][] = $amenity[0];
					$T_VAR['AMENITY_NAME'][] = $amenity[1];
				}
				
				//Get the amenity count
				$count = count($amenityList);
				
				//Create HTML repeat condition
				$T_COND[] = '!AMENITIES'.$count;				
				
				//Get List of current Building Amenities for display
				$amenityList = $building->AmenityList();
				if($amenityList != false)
				{
					foreach($amenityList as $amenity)
					{
						$T_VAR['BUILDING_AMENITY_ID'][] = $amenity->ID();
						$T_VAR['BUILDING_AMENITY_NAME'][] = $amenity->Name();
					}
					
					//Get the amenity count
					$count = count($amenityList);
					
					//Create HTML repeat condition
					$T_COND[] = '!BUILDING_AMENITIES'.$count;
					
				}else{ $T_COND[] = 'BUILDING_AMENITY_LIST'; }//Nothing to display
			
			}else{ $T_COND[] = 'AMENITY_LIST'; }//Nothing to display
		}
	}
	else
	{
		$T_VAR['MSG'] = 'You must provide a building ID number!';
		
		//Set the message REDIRECT 
		//(if given address ID adjust redirect)
		$T_VAR['REDIRECT'] = '3;url=locA.php?addAddress';
		if(isset($_INPUT['aid']) && !(empty($_INPUT['aid'])))
		{ 
			$T_VAR['REDIRECT'] = '3;url=locA.php?addBuilding&aid='.$_INPUT['aid']; 
		}
	}
}


//Check for [Edit Room] Mode
if(isset($_INPUT['editRoom']))
{
  //Set the Page Name
  $T_VAR['PAGE_NAME'] = 'Edit Room';
  
  //Set the default HTML file to use
  $T_FILE = 'message.html';
 
  //Check for Form submission
  if(isset($_INPUT['submit']))
  {
		//Require Room ID
		if(isset($_INPUT['rid']) && !(empty($_INPUT['rid'])))
		{
			//Create a room object to use
			$room = new room($_INPUT['rid']);

			//Create building object to use
			$building = new building($room->BuildingID());
			
			//Format the input
			$skipList = array('submit', 'editRoom', 'rid', 'bid', 'aid');
			foreach($_INPUT as $key => $value)
			{
				if(!(in_array($key, $skipList)))
				{
					$data[$key] = $value;
				}
			}
			
			//Attempt to alter the room record.
			if($room->Alter($data))
			{
				$T_VAR['MSG'] = 'Successfully altered room record!';
				
			}else{ $T_VAR['MSG'] = 'Failed to alter room record!'; }
			
			//Set the message REDIRECT
			$T_VAR['REDIRECT'] = '3;url=locA.php?addRoom&aid='.$building->AddressID().'&bid='.$room->BuildingID();
		}
		else
		{
			$T_VAR['MSG'] = 'You must provide a room ID!';
			
			//Set the message REDIRECT 
			//(if given address and/or building ID adjust redirect)
			$T_VAR['REDIRECT'] = '3;url=locA.php?addAddress';
			if(isset($_INPUT['aid']) && !(empty($_INPUT['aid'])))
			{ 
				$T_VAR['REDIRECT'] = '3;url=locA.php?addBuilding&aid='.$_INPUT['aid']; 
				if(isset($_INPUT['bid']) && !(empty($_INPUT['bid'])))
				{ 
					$T_VAR['REDIRECT'] = '3;url=locA.php?addRoom&aid='.$_INPUT['aid'].'&bid='.$_INPUT['bid']; 
				}
			}
		}
	}//else{ SHOW EDIT FORM }
}


//Check for [Remove(del) Room] Mode
if(isset($_INPUT['delRoom']))
{
  //Determine if ID provided
	if(isset($_INPUT['rid']) && $_INPUT['rid'] != 0)
	{
		//Set the HTML file to use
		$T_FILE = 'message.html';
		
		//Create the objects to use
		$room = new room($_INPUT['rid']);
		$building = new building($room->BuildingID());
		
		//Go in for the Kill, then respond and redirect
		$T_VAR['MSG'] = ($room->Kill()) ? 'Room ID:'.$_INPUT['rid'].' has been killed!' : 'Failed to kill Room ID:'.$_INPUT['rid'];
		
		//Set the message REDIRECT
		$T_VAR['REDIRECT'] = '3;url=locA.php?addRoom&aid='.$building->AddressID().'&bid='.$building->ID();		
	}
	else
	{
		$T_VAR['MSG'] = 'You must provide a room ID!';
		
		//Set the message REDIRECT 
		//(if given address and/or building ID adjust redirect)
		$T_VAR['REDIRECT'] = '3;url=locA.php?addAddress';
		if(isset($_INPUT['aid']) && !(empty($_INPUT['aid'])))
		{ 
			$T_VAR['REDIRECT'] = '3;url=locA.php?addBuilding&aid='.$_INPUT['aid']; 
			if(isset($_INPUT['bid']) && !(empty($_INPUT['bid'])))
			{ 
				$T_VAR['REDIRECT'] = '3;url=locA.php?addRoom&aid='.$_INPUT['aid'].'&bid='.$_INPUT['bid']; 
			}
		}
	}	
}


//Check for [Create Amenity] Mode
if(isset($_INPUT['createAmenity']))
{
	//Set the Page Name
	$T_VAR['PAGE_NAME'] = 'Create Amenity';
	
	//Set the default HTML file to use
	$T_FILE = 'message.html';
	
	//Create new amenity object to use
	$amenity = new amenity();
	
	//Check for form submission
	if(isset($_INPUT['submit']))
	{
		//Verify Name was given
		if(isset($_INPUT[$CONFIG['AmenityName_col']]) && !(empty($_INPUT[$CONFIG['AmenityName_col']])))
		{
			//Attempt to create the new amenity
			if($amenity->Create($_INPUT[$CONFIG['AmenityName_col']]))
			{
				//Success
				$T_VAR['MSG'] = 'Successfully created new Amenity!';
				
			}else{ $T_VAR['MSG'] = 'Failed to create new Amenity!'; }			
		}else{ $T_VAR['MSG'] = 'You must provide an Amenity Name!'; }

		//REDIRECT
		$T_VAR['REDIRECT'] = '3;url=locA.php?createAmenity';
	}
	else
	{
		//DISPLAY CREATE AMENITY FORM
		$T_FILE = 'amenityCreate.html';
		
		//Get List of current amenities
		$amenityList = $amenity->AmenityList();
		if($amenityList)
		{
			foreach($amenityList as $row)
			{
				$T_VAR['AMENITY_ID'][] = $row[0];
				$T_VAR['AMENITY_NAME'][] = $row[1];
			}
			
			//Get number of amenities
			$count = count($amenityList);
			
			//Create HTML repeat condition
			$T_COND[] = '!AMENITIES'.$count;
			
		}else{ $T_COND[] = 'AMENITY_LIST'; }//Nothing to Display
	}
}


//Check for [Edit Amenity] Mode
if(isset($_INPUT['editAmenity']))
{
  //Set the Page Name
  $T_VAR['PAGE_NAME'] = 'Edit Amenity';
  
  //Set the default HTML file to use
  $T_FILE = 'message.html';
 
  //Check for Form submission
  if(isset($_INPUT['submit']))
  {
		//Require Amenity ID
		if(isset($_INPUT['amid']) && !(empty($_INPUT['amid'])))
		{
			//Create an amenity object to use
			$amenity = new amenity($_INPUT['amid']);
			
			if(isset($_INPUT[$CONFIG['AmenityName_col']]) && !(empty($_INPUT[$CONFIG['AmenityName_col']])))
			{
				//Format the data for the function
				$data[$CONFIG['AmenityName_col']] = $_INPUT[$CONFIG['AmenityName_col']];
				
				//Attempt to alter the room record.
				if($amenity->Alter($data))
				{
					$T_VAR['MSG'] = 'Successfully altered room record!';
					
				}else{ $T_VAR['MSG'] = 'Failed to alter room record!'; }				
			}else{ $T_VAR['MSG'] = 'You must provide an Amenity Name!'; }
		}else{ $T_VAR['MSG'] = 'You must provide an Amenity ID!'; }
		
		//Set the message REDIRECT 
		$T_VAR['REDIRECT'] = '3;url=locA.php?createAmenity';
		
	}//else{ SHOW EDIT FORM }
}


//Check for [Delete Amenity] Mode
if(isset($_INPUT['delAmenity']))
{
  //Determine if ID provided
	if(isset($_INPUT['amid']) && $_INPUT['amid'] != 0)
	{
		//Set the HTML file to use
		$T_FILE = 'message.html';
		
		//Create the object to use
		$amenity = new amenity($_INPUT['amid']);
		
		//Go in for the Kill, then respond and redirect
		$T_VAR['MSG'] = ($amenity->Kill()) ? 'Amenity: '.$amenity->Name().' has been killed!' : 'Failed to kill Amenity: '.$amenity->Name();
		
	}else{ $T_VAR['MSG'] = 'You must provide an Amenity ID!'; }
	
	//Set the message REDIRECT
	$T_VAR['REDIRECT'] = '3;url=locA.php?createAmenity';
}


//Check for [Add Amenity] Mode
if(isset($_INPUT['addAmenity']))
{
	//Set the Page Name
	$T_VAR['PAGE_NAME'] = 'Create Amenity';
	
	//Set the default HTML file to use
	$T_FILE = 'message.html';
	
	if(isset($_INPUT['submit']))
	{
		//Verify that an Amenity ID is provided
		if(isset($_INPUT['amid']) && !(empty($_INPUT['amid'])))
		{
			//Verify a roomID or buildingID is given.
			if(isset($_INPUT['bid']) && !(empty($_INPUT['bid'])))
			{
				//Adding to building
				$building = new building($_INPUT['bid']);
				if($building->AddAmenity($_INPUT['amid']))
				{
					//Success!
					$T_VAR['MSG'] = 'Successfully added Amenity to '.$building->Name().'.';
					
				}else{ $T_VAR['MSG'] = 'Failed to add Amenity to '.$building->Name().'!'; }
				
				//Set the message REDIRECT
				$T_VAR['REDIRECT'] = '3;url=locA.php?addRoom&aid='.$building->AddressID().'&bid='.$building->ID();
			}
			elseif(isset($_INPUT['rid']) && !(empty($_INPUT['rid'])))
			{
				//Adding to room
				$room = new room($_INPUT['rid']);
				$building = new building($room->BuildingID());
				if($room->AddAmenity($_INPUT['amid']))
				{
					//Success!
					$T_VAR['MSG'] = 'Successfully added Amenity to '.$room->Name().'.';
					
				}else{ $T_VAR['MSG'] = 'Failed to add Amenity to '.$room->Name().'!'; }
				
				//Set the message REDIRECT
				$T_VAR['REDIRECT'] = '3;url=locA.php?addAmenity&aid='.$building->AddressID().'&bid='.$building->ID().'&rid='.$room->ID();			
			}
			else
			{
				$T_VAR['MSG'] = 'You must provide a Room ID or Building ID!';
				$T_VAR['REDIRECT'] = '3;url=locA.php?addAddress';
			}
		}
		else
		{
			//NO AMENITY ID
			$T_VAR['MSG'] = 'You must provide an Amenity ID!';
			$T_VAR['REDIRECT'] = '3;url=locA.php?addAddress';
		}		
	}
	else
	{
		//Verify a Room ID is provided
		if(isset($_INPUT['rid']) && !(empty($_INPUT['rid'])))
		{
			//DISPLAY ADD AMENITY FORM
			$T_FILE = 'amenityAdd.html';			
			
			//Create some objects to use
			$room = new room($_INPUT['rid']);
			$building = new building($room->BuildingID());
			$address = new address($building->AddressID());
			$_amenity = new amenity();
			
			//Some Template Variables
			$T_VAR['ROOM_NAME'] = $room->Name();
			$T_VAR['ROOM_ID'] = $room->ID();
			$T_VAR['BUILDING_NAME'] = $building->Name();
			$T_VAR['ADDRESS_FULL'] = $address->Full();
			
			//Get list of all possible amenities
			$amenityList = $_amenity->AmenityList();
			if($amenityList)
			{
				foreach($amenityList as $amenity)
				{
					$T_VAR['AMENITY_ID'][] = $amenity[0];
					$T_VAR['AMENITY_NAME'][] = $amenity[1];
				}

				//Get the amenity count
				$count = count($amenityList);
				
				//Create HTML repeat condition
				$T_COND[] = '!AMENITIES'.$count;				
				
			}else{ $T_COND[] = 'AMENITY_LIST'; }//None to display
			
			//Get list of Amenities specific to the room
			$amenityList = $room->AmenityList();
			if($amenityList)
			{
				foreach($amenityList as $amenity)
				{
					$T_VAR['ROOM_AMENITY_ID'][] = $amenity->ID();
					$T_VAR['ROOM_AMENITY_NAME'][] = $amenity->Name();
				}

				//Get the amenity count
				$count = count($amenityList);
				
				//Create HTML repeat condition
				$T_COND[] = '!ROOM_AMENITIES'.$count;
				
			}else{ $T_COND[] = 'ROOM_AMENITY_LIST'; }//None to display
			
			//redirect?
			$T_VAR['REDIRECT'] = '3;url=locA.php?addAmenity&rid='.$room->ID();
		}
		else
		{
			$T_VAR['MSG'] = 'You must provide a Room ID or Building ID!';
			$T_VAR['REDIRECT'] = '3;url=locA.php?addAddress';
		}		
	}
}


//Check for [Remove Amenity] Mode
if(isset($_INPUT['removeAmenity']))
{
  //Determine if ID provided
	if(isset($_INPUT['amid']) && $_INPUT['amid'] != 0)
	{
		//Set the HTML file to use
		$T_FILE = 'message.html';
			
		$amenity = new amenity($_INPUT['amid']);
		
		//Verify a Room ID or Building ID is provided
		if(isset($_INPUT['bid']) && !(empty($_INPUT['bid'])))
		{
			//REMOVE FROM BUILDING
			$building = new building($_INPUT['bid']);
			
			//Go in for the Kill, then respond and redirect
			$T_VAR['MSG'] = ($building->KillAmenity($_INPUT['amid'])) ? 'Amenity: '.$amenity->Name().' has been removed from '.$building->Name().'!' : 'Failed to remove Amenity from '.$building->Name();
			
			//Set the message REDIRECT
			$T_VAR['REDIRECT'] = '3;url=locA.php?addRoom&aid='.$building->AddressID().'&bid='.$building->ID();
		}
		elseif(isset($_INPUT['rid']) && !(empty($_INPUT['rid'])))
		{
			//REMOVE FROM ROOM
			$room = new room($_INPUT['rid']);
			
			//Go in for the Kill, then respond and redirect
			$T_VAR['MSG'] = ($room->KillAmenity($_INPUT['amid'])) ? 'Amenity: '.$amenity->Name().' has been removed from '.$room->Name().'!' : 'Failed to remove Amenity from '.$room->Name();
			
			//Set the message REDIRECT
			$T_VAR['REDIRECT'] = '3;url=locA.php?addAmenity&rid='.$room->ID();
		}
		else
		{
			//MUST PROVIDE ROOM OR BUILDING ID!
			$T_VAR['MSG'] = 'A Room ID or Building ID must be provided!';
			
			//Set the message REDIRECT
			$T_VAR['REDIRECT'] = '3;url=locA.php?addAddress';					
		}		
	}
	else
	{ 
		$T_VAR['MSG'] = 'You must provide an Amenity ID!';
		
		//Set the message REDIRECT
		$T_VAR['REDIRECT'] = '3;url=locA.php?addAddress';
	}
}


//Build the template (LAST LINE OF ALL MAIN DRIVERS)
//In this file we alter the style path to "acp" sub-directory
//And attach true to the path string to signal using root header/footer
//Removing ",true" will make the template use header/footer from "acp" dir
//which is useful for creating alternate menu for administration links.
BuildTemplate($T_FILE, $T_VAR, $T_COND, false, 'acp');
?>