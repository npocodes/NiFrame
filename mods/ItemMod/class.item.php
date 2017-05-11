<?php
/*
  Purpose: NiFrame Inventory Module
  
   Author: Nathan M. Poole ( nathan@nativeinventions.com )
           http://nativeinventions.com/
           
     Date: Janurary 2015
     
     Last Update: 5-10-2017
     
     File: Item class file
           
	[ !! This Class Utilizes a configuration file !! ]
*/
//////////////////
//&& Includes &&//
//////////////////
require_once('inc/classes/class.dbAccess.php');
require_once('inc/classes/class.error.php');

//++++++++++++++++++++//
//++ THE ITEM CLASS ++//
//++++++++++++++++++++//
/*
  Item class provides handling for all base 
  item attributes as well as associated methods.
*/
class item extends error {

//-------------------------------//
      //%% Attributes %%//
//-------------------------------//
  protected $item_ID;       //The Items ID
  protected $item_type;     //The type of Item: (ID, Name)
  protected $item_name;     //The Name of the Item
  protected $item_maker;    //The manufacturer of the Item: (ID, Name)
  
  protected $item_value;    //The actual value of the item (per unit)
  protected $item_cost;     //The amount paid to obtain the item (per unit)
  protected $item_price;    //The amount required to let go(sell) the item (per unit)
  
  protected $item_units;    //The number of units in total of the Item
  protected $item_onHand;   //The amount of Item units available for use  
  protected $item_consume;  //Flag to determine if units are consumed on use
  protected $item_perish;   //The date an Item will expire (rot, warranty ends, etc...)
  
  protected $item_weight;   //The weight of the Item (per unit)
  protected $item_size;     //The X-Y-Z dimensions of the Item (per unit)
  protected $item_color;    //The color of the item (Array, primary color first)
  
  protected $itemTypeList;  //Holds list of Item Type (ID,Name) combinations (convenience)
  protected $itemMakerList; //Holds list of Item Maker(ID,Name) combinations (convenience)
  
  
//-------------------------------------------//
            //## Methods ##//
//-------------------------------------------//

  ///////////////////
  /// Constructor ///
  ///////////////////
  /*
    This constructor will initialize attributes to their default values and if provided with an 
    Item ID, will retrieve the associated item information from the database and update attributes.
  */
  function item($itemID = 0) 
  {
    //Initialize all default values...
    
    //Item Specific Attributes
    $this->item_ID      = 0;                    //The Items ID
    $this->item_type    = array(0, 'unknown');  //The type of Item: (ID, Name) 
    $this->item_name    = 'unknown';            //The Name of the Item
    $this->item_maker   = array(0, 'unknown');  //The manufacturer of the Item: (ID, Name)
    
    $this->item_value   = 0;                    //The actual value of the item (per unit)
    $this->item_cost    = 0;                    //The amount paid to obtain the item (per unit)
    $this->item_price   = 0;                    //The amount required to let go(sell) the item (per unit)
    
    $this->item_units   = 0;                    //The number of units in total of the Item
    $this->item_onHand  = 0;                    //The amount of Item units available for use  
    $this->item_consume = 0;                    //Flag to determine if units are consumed on use
    $this->item_perish  = 0;                    //The date an Item will expire (rot, warranty ends, etc...)
    
    $this->item_weight  = 0;                    //The weight of the Item (per unit)
    $this->item_size    = array(0, 0, 0);       //The X-Y-Z dimensions of the Item (per unit)
    $this->item_color   = array('none');        //The color of the item (Array, primary color first)
    
    $this->itemTypeList = array();
    $this->itemMakerList = array();
    
    //Check for provided item_ID
    if($itemID != 0)
    {
      //Get item details
      $this->Initialize($itemID);
    }
  }
  //End Constructor Method
  
  
  ///////////////////
  /// GET Methods ///
  ///////////////////
  public function ID(){ RETURN $this->item_ID; }
  public function Type($index = 0) { RETURN $this->item_type[$index]; }
  public function Name(){ RETURN $this->item_name; }
  public function Model(){ RETURN $this->Name(); }
  public function Maker($index = 0){ RETURN $this->item_maker[$index]; }
  
  public function Value(){ RETURN $this->item_value; }
  public function Cost(){ RETURN $this->item_cost; }
  public function Price(){ RETURN $this->item_price; }
  
  public function Units(){ RETURN $this->item_units; }
  public function OnHand(){ RETURN $this->item_onHand; }
  public function Consumed(){ RETURN $this->item_consume; }
  public function Perish(){ RETURN $this->item_perish; }
  public function Expire(){ RETURN $this->Perish(); }
  
  public function Weight(){ RETURN $this->item_weight; }
  public function Size($index = 0){ RETURN $this->item_size[$index]; } 
  public function Color($index = 0){ RETURN $this->item_color[$index]; }
    

  //!!!!!!!!!!!!!!!!!//
  //! Create Method !//
  //!!!!!!!!!!!!!!!!!//
  /*
    This method uses the provided data to create a new Item.
    ACCEPTS:
      $itemTypeID - The ID of the desired Item type *Required
      $itemName   - The given Name of the Item      *Required
      $data       - Array of other attributes ('Key' => 'Value')
    
    RETURNS: 
      [Success] Item ID
      [Failure] false
  */ 
  public function Create($itemTypeID, $itemName, $data = null)
  {
 		//Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //Create link to database
    $DB = new dbAccess();
    if($DB->Link())
    { 
      //Format the item data into field/value pairs
      $fieldList = array($CONFIG['ItemType_col'], $CONFIG['ItemName_col']);
      $valueList = array($itemTypeID, $itemName);
      if($data != null)
      {
        foreach($data as $key => $value)
        {
          $fieldList[] = $key;
          $valueList[] = $value;
        }
      }
      
      //Attempt to Inject the new item into the database
      if($DB->Inject($CONFIG['Item_Table'], $fieldList, $valueList))
      {
        //Success return the new items ID
        $DB->Sever();
        RETURN $DB->InjectID();
        
      }else{ $this->LogError($this->error = 'Injection Failure -- item::Create()'); $DB->Sever(); }
    }else{ $this->LogError($this->error = 'No Data Link! -- item::Create()'); }
    
    RETURN false;
  }  
  
  
  //!!!!!!!!!!!!!!!!!//
  //! Update Method !//
  //!!!!!!!!!!!!!!!!!//
  /*
    This method uses the provided data formatted in fieldName => value pairs
    to update the data associated to this Item in the database.
  */ 
  public function Update($data)
  {
 		//Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //Split keyed array into
    //fields and values
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
      //Isolate the specific Item
      $whereLoc = $CONFIG['ItemID_col'].'='.$this->item_ID;
      
      //Try to update the database
      if($DB->Refresh($CONFIG['Item_Table'], $fieldList, $valueList, $whereLoc))
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
          
        }else{ $this->LogError($this->error = $DB->Error()); }
      }
      $DB->Sever();//Sever DB Link
    }else{ $this->LogError($this->error = 'No Data Link! -- item::Update()'); }
    
    RETURN false;
  }
  //END Update method
  
  
  //!!!!!!!!!!!!!!!//
  //! Kill Method !//
  //!!!!!!!!!!!!!!!//
  /*
    This method kills *this Item by removing the 
    associated record from the database.
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
      //Attempt to clear the Item record from the database
      $whereLoc = $CONFIG['ItemID_col'].'='.$this->item_ID;
      if($DB->Kill($CONFIG['Item_Table'], $whereLoc))
      {
        //Success
        $DB->Sever();
        RETURN true;
        
      }else{ $this->LogError($this->error = 'Kill Failure! -- item::Kill()'); }
      $DB->Sever();
    }else{ $this->LogError($this->error = 'No Data Link! -- item::Kill()'); }
    
    //Failure
    RETURN false;
  }
  //END Kill method

  
  //!!!!!!!!!!!!!!!!!//
  //! Search method !//
  //!!!!!!!!!!!!!!!!!//
  /*
    This method searches the database in order to try and locate
    a specific or multiple possible Items based on a given "needle",
    paired with a filter option such as "Name" etc..
    (supports partial needles)
    
    ACCEPTS: 
      $filter   - <string> The specific item attribute to use as a search 
                focus in order to reduce the number of results returned.
                FilterList:
                  - ID        - The Item's Unique Identifier
                  - type      - The Item's Type(Group), ID or Name accepted 
                  - name      - The Item's Name
                  - maker     - The Item Manufacturer, ID or Name accepted
                  - value     - The Item's Value (decimal(13,4) in db)
                  - cost      - The Item's Cost (decimal(13,4) in db)  
                  - price     - The Item's Price (decimal(13,4) in db)
                  - consumable - Item's marked as consumable (boolean in db)
                  - perish    - The date of the Item's expiration (timestamp)
                  - weight    - The Item's Weight ( decimal(5,2) in db ..or float)
                  - size      - The Item's Size x, y, or z (x-y-z in db)
                  - color     - The Item's Color (Color1-Color2-Etc... in db)
      
      $needle   - <string> A word, phrase, number, or partial of any of the previous 
                that, when paired with a $filter, is used to reduce the number of 
                results returned.
      
      $BoE_flag - A flag that determines whether to place the "wild" symbol at the
                Beginning or End of the provided %needle. (For partial $needles).
                Options:
                  - 0 - Begins with $needle, wild symbol comes after needle
                  - 1 - Ends with $needle, wild symbol comes before needle
                  - 2 - No wild, find exact $needle
                  - none - Default value, needle between two wild symbols
                  
    RETURNS: [Success] - Array of item IDs that matched the search parameters given
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
      if($BoE_flag == 'none'){ $BoE = array('%','%'); }
      
      //Used to swap out where clauses later if needed...
      $whereLoc2 = null;
      
      //Determine the filter
      switch($filter)
      {
        case 'id':
          $filter = $CONFIG['ItemID_col'];
        break;
        
        case 'type':
          $filter = $CONFIG['ItemType_col'];
          
          //Determine if user searching by "type name"
          if(!(is_numeric($needle)))
          {
            //User is searching by the name of the Item type, we need to find the ID 
            //to the name first and then create a where clause for each result found
            //then we can search the items table...
            $whereLoc = (isset($needle)) ? $CONFIG['ItemTypeName_col'].' LIKE '.rtrim($BoE[0].$needle.$BoE[1]) : null;
            if($DB->Snatch($CONFIG['ItemType_Table'], $CONFIG['ItemTypeID_col'], $whereLoc))
            {
              $data = $DB->Result();
              if(isset($data[0]))
              {
                //Multiple Results...
                $whereLoc2 = array();
                $uTypeCount  = count($data);
                for($i=0; $i < $uTypeCount; $i++)
                {
                  $whereLoc2[$i] = $filter.'='.$data[$i][$CONFIG['ItemTypeID_col']];  
                }
                
                //Now add the "|" (OR) modifier to the beginning
                //of the array to tell the Snatch method to concatenate
                //the Where clauses with OR rather than AND
                array_unshift($whereLoc2, "|");
              }
              else
              {
                //Single Result
                $whereLoc2 = $filter.'='.$data[$CONFIG['ItemTypeID_col']];
              }
            }
          }
        break;
        
        case 'maker':
          $filter = $CONFIG['ItemMaker_col'];
          
          //Determine if user searching by "Maker Name"
          if(!(is_numeric($needle)))
          {
            //User is searching by the name of the Item Maker, we need to find the ID 
            //to the name first and then create a where clause for each result found
            //then we can search the items table for those Maker IDs...
            $whereLoc = $CONFIG['ItemMakerName_col'].' LIKE '.rtrim($BoE[0].$needle.$BoE[1]);
            if($DB->Snatch($CONFIG['ItemMaker_Table'], $CONFIG['ItemMakerID_col'], $whereLoc))
            {
              $data = $DB->Result();
              if(isset($data[0]))
              {
                //Multiple Results...
                $whereLoc2 = array();
                $iMakerCount  = count($data);
                for($i=0; $i < $iMakerCount; $i++)
                {
                  $whereLoc2[$i] = $filter.'='.$data[$i][$CONFIG['ItemMakerID_col']];  
                }
                
                //Now add the "|" (OR) modifier to the beginning
                //of the array to tell the Snatch method to concatenate
                //the Where clauses with OR rather than AND
                array_unshift($whereLoc2, "|");
              }
              else
              {
                //Single Result
                $whereLoc2 = $filter.'='.$data[$CONFIG['ItemMakerID_col']];
              }
            }
          }          
        break;
        
        case 'value':
          $filter = $CONFIG['ItemValue_col'];
        break;
        
        case 'cost':
          $filter = $CONFIG['ItemCost_col'];
        break;
        
        case 'price':
          $filter = $CONFIG['ItemPrice_col'];
        break;
        
        case 'consumable':
          $filter = $CONFIG['ItemConsume_col'];
        break;
        
        case 'perish':
          $filter = $CONFIG['ItemPerish_col'];
        break;
        
        case 'weight':
          $filter = $CONFIG['ItemWeight_col'];
        break;
        
        case 'size':
          $filter = $CONFIG['ItemSize_col'];
          //force default BoE placement
          $BoE = array('%', '%');
        break;
        
        case 'color':
          $filter = $CONFIG['ItemColor_col'];
          //force default BoE placement
          $BoE = array('%', '%');
        break;
        
        default:
          $filter = $CONFIG['ItemName_col'];
        break;
      }
      
      //Get a list of ALL items (IDs only), matching the needle
      $whereLoc = (isset($needle)) ? $filter.' LIKE '.rtrim($BoE[0].$needle.$BoE[1]) : null;
      $whereLoc = ($whereLoc2 != null) ? $whereLoc2 : $whereLoc;

      if($DB->Snatch($CONFIG['Item_Table'], $CONFIG['ItemID_col'], $whereLoc))    
      {
        $data = $DB->Result();
        $iList = array();
        if(isset($data[0]))
        {
          foreach($data as $key => $row)
          {
            $iList[] = $row[$CONFIG['ItemID_col']];
          }
        }
        else
        {
          $iList[] = $data[$CONFIG['ItemID_col']];
        }
        
        $DB->Sever();
        RETURN $iList;//List of items found!
      }
      $DB->Sever();
    }
    
    //Failure
    RETURN false;
  }
  //END Search method
  
  
  //####################//
  //# Type List Method #//
  //####################// 
  //Get list of available types - (ID, Name)
  public function TypeList()
  {
		//Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }

    //Check if data already exists
    if(empty($this->itemTypeList))
    {
      //Create database link
      $DB = new dbAccess();
      if($DB->Link())
      {
        //Snatch ItemType data from DB
        $fieldList = array($CONFIG['ItemTypeID_col'], $CONFIG['ItemTypeName_col']);
        if($DB->Snatch($CONFIG['ItemType_Table'], $fieldList))
        {
          //Retrieve the results
          $data = $DB->Result();
          
          //Check for multi result
          if(isset($data[0]))
          {
            //cycle each result
            foreach($data as $type)
            {
              $this->itemTypeList[] = array($type[$CONFIG['ItemTypeID_col']], $type[$CONFIG['ItemTypeName_col']]);
            }
          }
          else
          {
            //Single result
            $this->itemTypeList[] = array($data[$CONFIG['ItemTypeID_col']], $data[$CONFIG['ItemTypeName_col']]);
          }
        }else{ $this->LogError('Snatch failure -- item::TypeList()'); }
        $DB->Sever();//Sever DB Link
      }else{ $this->LogError('No Data Link! -- item::TypeList()'); }
    }
    
    //Success!?!
    RETURN $this->itemTypeList;
  }
  
  
  //###################//
  //# New Type Method #//
  //###################// 
  //Creates new item type
  public function NewType($name)
  {
    //Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //Create link to the database
    $DB = new dbAccess();
    if($DB->Link())
    {
      //Attempt to add the new item type
      if($DB->Inject($CONFIG['ItemType_Table'], $CONFIG['ItemTypeName_col'], $name))
      {
        //Get the new types ID
        $injectID = $DB->InjectID();
        
        //Sever DB connection
        $DB->Sever();
        
        //Return the new types ID
        RETURN $injectID;
        
      }else{ $this->LogError('Injection failure -- item::NewType()'); }
      $DB->Sever();
    }else{ $this->LogError('No Data Link! -- item::NewType()'); }
    
    RETURN false;
  }

  
  //######################//
  //# Rename Type Method #//
  //######################// 
  //Change item Type Name
  public function RenameType($typeD, $newName)
  {
    //Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
 
    //Determine if $typeData is a name or an ID
    //Type cast integer for added measure if ID
    $typeData = (is_numeric($typeD)) ? (int) $typeD : $typeD;
    $fieldName = (is_int($typeData)) ? $CONFIG['ItemTypeID_col'] : $CONFIG['ItemTypeName_col'];
    
    //Create link to the database
    $DB = new dbAccess();
    if($DB->Link())
    {
      //Attempt to alter the type name
      $whereLoc = $fieldName.'='.$typeData;
      if($DB->Refresh($CONFIG['ItemType_Table'], $CONFIG['ItemTypeName_col'], $newName, $whereLoc))
      {
        //Sever DB Link
        $DB->Sever();
        
        //Success!!
        RETURN true;
        
      }else{ $this->LogError('Refresh failure -- item::RenameType()'); }
      $DB->Sever();
    }else{ $this->LogError('No Data Link! -- item::RenameType()'); }
    
    //Failure
    RETURN false;
  }
  
  
  //######################//
  //# Remove Type Method #//
  //######################// 
  //Remove item type
  public function RemoveType($typeD)
  {
    //Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
 
    //Determine if $typeData is a name or an ID
    //Type cast integer for added measure if ID
    $typeData = (is_numeric($typeD)) ? (int) $typeD : $typeD;
    $fieldName = (is_int($typeData)) ? $CONFIG['ItemTypeID_col'] : $CONFIG['ItemTypeName_col'];
    
    //Create link to the database
    $DB = new dbAccess();
    if($DB->Link())
    {
      //Attempt to add the new item type
      $whereLoc = $fieldName.'='.$typeData;
      if($DB->Kill($CONFIG['ItemType_Table'], $whereLoc))
      {
        //Sever DB Link
        $DB->Sever();
        
        //Success!!
        RETURN true;
        
      }else{ $this->LogError('Kill failure -- item::RemoveType()'); }
      $DB->Sever();
    }else{ $this->LogError('No Data Link! -- item::RemoveType()'); }
    
    //Failure
    RETURN false;
  }
  //Convenience Methods
  public function KillType($typeData){ RETURN $this->RemoveType($typeData); }
  public function DeleteType($typeData){ RETURN $this->RemoveType($typeData); }
  
  
  //$$$$$$$$$$$$$$$$$$$$$//
  //$ Maker List Method $//
  //$$$$$$$$$$$$$$$$$$$$$//   
  //Get list of available makeres
  public function MakerList()
  {
		//Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }

    //Check if data already exists
    if(empty($this->itemMakerList))
    {
      //Create database link
      $DB = new dbAccess();
      if($DB->Link())
      {
        //Snatch ItemType data from DB
        if($DB->Snatch($CONFIG['ItemMaker_Table']))
        {
          //Retrieve the results
          $data = $DB->Result();
          
          //Check for multi result
          if(isset($data[0]))
          {
            //cycle each result
            foreach($data as $type)
            {
              $this->itemMakerList[] = array($type[$CONFIG['ItemMakerID_col']], $type[$CONFIG['ItemMakerName_col']]);
            }
          }
          else
          {
            //Single result
            $this->itemMakerList[] = array($data[$CONFIG['ItemMakerID_col']], $data[$CONFIG['ItemMakerName_col']]);
          }
        }else{ $this->LogError('Snatch failure -- item::MakerList()'); }
        $DB->Sever();//Sever DB Link
      }else{ $this->LogError('No Data Link! -- item::MakerList()'); }
    }
    
    //Success!?!
    RETURN $this->itemMakerList;
  }
  
 
  //$$$$$$$$$$$$$$$$$$$$//
  //$ New Maker Method $//
  //$$$$$$$$$$$$$$$$$$$$// 
  //Creates new item maker
  public function NewMaker($name)
  {
    //Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //Create link to the database
    $DB = new dbAccess();
    if($DB->Link())
    {
      //Attempt to add the new item type
      if($DB->Inject($CONFIG['ItemMaker_Table'], $CONFIG['ItemMakerName_col'], $name))
      {
        //Get the new types ID
        $injectID = $DB->InjectID();
        
        //Sever DB Link
        $DB->Sever();
        
        //Return the new types ID
        RETURN $injectID;
        
      }else{ $this->LogError('Injection failure -- item::NewMaker()'); }
      $DB->Sever();
    }else{ $this->LogError('No Data Link! -- item::NewMaker()'); }
    
    RETURN false;
  }
  
  
  //$$$$$$$$$$$$$$$$$$$$$$//
  //$ Alter Maker Method $//
  //$$$$$$$$$$$$$$$$$$$$$$//
  //Change item Maker Name
  public function RenameMaker($makerD, $newName)
  {
    //Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
 
    //Determine if $makerData is a name or an ID
    //Type cast integer for added measure if ID
    $makerData = (is_numeric($makerD)) ? (int) $makerD : $makerD;
    $fieldName = (is_int($makerData)) ? $CONFIG['ItemMakerID_col'] : $CONFIG['ItemMakerName_col'];
    
    //Create link to the database
    $DB = new dbAccess();
    if($DB->Link())
    {
      //Attempt to alter the maker name
      $whereLoc = $fieldName.'='.$makerData;
      if($DB->Refresh($CONFIG['ItemMaker_Table'], $CONFIG['ItemMakerName_col'], $newName, $whereLoc))
      {
        //Sever DB Link
        $DB->Sever();
        
        //Success!!
        RETURN true;
        
      }else{ $this->LogError('Refresh failure -- item::RenameMaker()'); }
      $DB->Sever();
    }else{ $this->LogError('No Data Link! -- item::RenameMaker()'); }
    
    //Failure
    RETURN false;
  }
  
  
  //$$$$$$$$$$$$$$$$$$$$$$$//
  //$ Remove Maker Method $//
  //$$$$$$$$$$$$$$$$$$$$$$$//
  //Remove item Maker
  public function RemoveMaker($makerD)
  {
    //Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
 
    //Determine if $makerData is a name or an ID
    //Type cast integer for added measure if ID
    $makerData = (is_numeric($makerD)) ? (int) $makerD : $makerD;
    $fieldName = (is_int($makerData)) ? $CONFIG['ItemMakerID_col'] : $CONFIG['ItemMakerName_col'];
    
    //Create link to the database
    $DB = new dbAccess();
    if($DB->Link())
    {
      //Attempt to add the new item maker
      $whereLoc = $fieldName.'='.$makerData;
      if($DB->Kill($CONFIG['ItemMaker_Table'], $whereLoc))
      {
        //Sever DB Link
        $DB->Sever();
        
        //Success!!
        RETURN true;
        
      }else{ $this->LogError('Kill failure -- item::RemoveMaker()'); }
      $DB->Sever();
    }else{ $this->LogError('No Data Link! -- item::RemoveMaker()'); }
    
    //Failure
    RETURN false;
  }
  //Convenience Methods
  public function KillMaker($makerData){ RETURN $this->RemoveMaker($makerData); }
  public function DeleteMaker($makerData){ RETURN $this->RemoveMaker($makerData); }

  
  //^^^^^^^^^^^^^^^^^^^^^//
  //^ Initialize Method ^//
  //^^^^^^^^^^^^^^^^^^^^^//
  /*
    This method uses the provided item_ID to gather the associated item data 
    from the database and initialize the object instance with the items details.
  */  
  final protected function Initialize($itemID = 0)
  {
		//Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //verify Item ID has been given
    if($itemID != 0)
    {
      //Get the items data!
      $DB = new dbAccess();
      if($DB->Link())
      {
        $whereLoc = $CONFIG['ItemID_col'].'='.$itemID;
        if($DB->Snatch($CONFIG['Item_Table'], '*', $whereLoc))
        {
          //Request the results
          $data = $DB->Result();
          
          //Initialize Object with DB data
          $this->item_ID        = $data[$config['ItemID_col']];
          $this->item_type[0]   = $data[$config['ItemType_col']];
          $this->item_name      = $data[$config['ItemName_col']];
          $this->item_maker[0]  = $data[$config['ItemMaker_col']];
          
          $this->item_value = $data[$config['ItemValue_col']];
          $this->item_cost  = $data[$config['ItemCost_col']];
          $this->item_price = $data[$config['ItemPrice_col']];
          
          $this->item_units   = $data[$config['ItemUnits_col']];
          $this->item_onHand  = $data[$config['ItemOnHand_col']];
          $this->item_consume = $data[$config['ItemConsume_col']]; 
          $this->item_perish  = $data[$config['ItemPerish_col']];
          
          $this->item_weight  = $data[$config['ItemWeight_col']];
          $this->item_size    = explode('-', $data[$config['ItemSize_col']]);
          $this->item_color   = explode('-', $data[$config['ItemColor_col']]);

          //Get the Type Name and Type List
          $typeList = $this->TypeList();
          foreach($typeList as $type)
          {
            if($type[0] == $this->item_type[0])
            {
              $this->item_type[1] = $type[1];
              break;
            }
          }
          
          //Get the Maker Name and Maker List
          $makerList = $this->MakerList();
          foreach($makerList as $maker)
          {
            if($maker[0] == $this->item_maker[0])
            {
              $this->item_maker[1] = $maker[1];
              break;
            }
          }
          
          //Success!
          $DB->Sever();
          RETURN true;
          
        }else{ if($DB->Error() != '0 results found'){ $this->LogError('Snatch Failure -- item::Initialize()'); }}
        $DB->Sever();//Sever DB Link
      }else{ $this->LogError('No Data Link -- item::Initialize()'); }
    }else{ $this->LogError("No Item ID! -- item::Initialize()");}
    
    //Failure
    RETURN false;
  }
  //END Initialize
}  
//END ITEM CLASS
?>