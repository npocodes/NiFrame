<?php
/*
  Project:  NiFrame
  
  Author:   Nathan Poole - github/npocodes
           
  Date:     Feb 2015
  
  Updated:  A
  
  File:     This file is an attribute class file. Use this class to "extend"
            attributes to "things"" such as: users, items, anything that has
            attributes...This class will provide handling the dynamic attributes.
     
  Note-     It is assumed that the table structure follows: tableName = className, thing_ID = className_ID
              ex: tableName = user, thing_ID = user_ID, where class name = "user"... or 
              ex: tableName = item, thing_ID = item_ID, where class name = "item", etc...
*/
//require_once('inc/classes/class.nerror.php');
//require_once('inc/classes/class.dbAccess.php');

class attr extends nerror {
  
  private $attrE; //Flag saying whether attrs exist yet
  protected $attrList; //List of unique name=>value pairs
  protected $attrIndex; //Index of attribute information (ex: attr Name, Label, Rank)
  
  
	//!!!!!!!!!!!!!!!!!!!//
	//!!! CONSTRUCTOR !!!//
	//!!!!!!!!!!!!!!!!!!!//
  function __construct($ID = 0)
  { 
 		//Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //First Initialize Defaults
    $this->attrE = true; //Assume they do exist until we can't find them
    $this->attrList = false;//No attribute list has been found yet
    $this->attrIndex = false;//No attribute index has been found yet
    
    //Attempt to retrieve the attribute index
    $this->attrIndex = $this->AttrIndex();
    
    //Initialize attrList if ID given
    if($ID > 0)
    {
      $this->attrList = $this->AttrList($ID);
    }
  }//END CONSTRUCTOR
  
  
  /////////////////////////
  //# Add New Attribute #//
  /////////////////////////
  /*
    Adds a new attribute to *this class within
    the system.
    
    ACCEPTS:
      $name     - required, the back-end name of the attribute (ex: userName)
      $label    - required, the front-end (display) name of the attribute (ex: User's Name) 
      $rank     - optional, a number that can be used to sort attributes into separate groups
      $default  - optional, the default value the attribute should use
      $vType    - optional, the verification type to use for form inputs
      
      -note- multiple attributes can be added by passing arrays as arguments
      
    RETURNS: True | False  
  */
  public function AddAttr($name, $label, $rank = 0, $default = 'none', $vType = "text")
  {
 		//Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //Get the current child classes name
    //we will use it to locate the DB information
    $className = get_class($this);
    
    //Force arrays
    $name = (is_array($name)) ? $name : array($name);
    $label = (is_array($label)) ? $label : array($label);
    $rank = (is_array($rank)) ? $rank : array($rank);
    $default = (is_array($default)) ? $default : array($default);
    $vType = (is_array($vType)) ? $vType : array($vType);
    
    //Equalize the input data elements
    for($i=0; $i < count($name); $i++)
    {
      //Fill with default values if values are missing
      $label[$i] = (isset($label[$i]) && !(empty($label[$i]))) ? $label[$i] : 'Unknown';
      $rank[$i] = (isset($rank[$i]) && !(empty($rank[$i]))) ? $rank[$i] : 0;
      $default[$i] = (isset($default[$i]) && !(empty($default[$i]))) ? $default[$i] : 'none';
      $vType[$i] = (isset($vType[$i]) && !(empty($vType[$i]))) ? $vType[$i] : 'text';
      
      //Add attribute data to values list for Attr_Index Table
      $values[$i] =  array($name[$i], $label[$i], $rank[$i], $vType[$i]);
      
      //Handle database type variable
      //Set Data type for the chosen verification type
      switch($vType[$i])
      {
        case 'color':
          //#FFFFFF (hex codes)
          $dType[$i] = "varchar(7)";
        break;
        
        case 'date':
          //A simple date
          $dType[$i] = "date";
        break;
        
        case 'time':
          //A timestamp (converted to time by system)
          $dType[$i] = "timestamp";
        break;
        
        case 'email':
          //An email address
          $dType[$i] = "varchar(64)";
        break;
        
        case 'month':
        case 'day':
          //Number for a month or day
          $dType[$i] = "int(2)";
        break;

        case 'year':
          //Number for a year 4+
          $dType[$i] = "int(5)";
        break;
        
        case 'tel':
          //A telephone number (w/out formatting)
          $dType[$i] = "varchar(20)";
        break;
        
        case 'number':
          //An integer number
          $dType[$i] = "int(11)";
        break;
        
        case 'basic':
          //Simple basic line of text
          $dType[$i] = "varchar(64)";
        break;
        
        default:
          //Text
          $dType[$i] = "text";//"varchar(255)";
          $default[$i] = '';
        break;
      }//END vType switch
    }
        
    //Create a dbAccess object
    $DB = new dbAccess();
    
    //Try to link to the DB
    if($DB->Link())
    {
      //Attempt to add the new Attribute
      //First check to see if we need to
      //create the DB tables to hold the data
      if(!($this->attrE))
      {   
        //Prep the fields list for Attr Index Table
        $fieldList = array(
          "`attr_ID` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique Identifier'",
          "`name` varchar(32) NOT NULL COMMENT 'Attribute Name'",
          "`label` varchar(32) NOT NULL DEFAULT 'Unknown' COMMENT 'Attribute Display Label'",
          "`rank` int(11) NOT NULL DEFAULT '0' COMMENT 'For special sorting'",
          "`vType` varchar(32) NOT NULL DEFAULT 'text' COMMENT 'Attribute Verification Type'",
          "PRIMARY KEY (`attr_ID`)",
          "UNIQUE KEY `name` (`name`)"
        );
        
        //Attempt to create the Attr Index Table
        if($DB->NewTable($className.'_attr_index', $fieldList))
        {
          //Index Table Created successfully!

          //Generate the complete field list
          $fieldList2 = array("`".$className."_ID` int(11) NOT NULL DEFAULT '0' COMMENT '".$className." Unique Identifier'");
          
          //Cycle each name given
          for($i=0; $i < count($name); $i++)
          {
            //handle x var to account for manual addition of field
            $x = $i + 1;
            
            //Add attribute to the field list for Attr Values Table
            $fieldList2[$x] = "`".$name[$i]."` ".$dType[$i]." NOT NULL DEFAULT '".$default[$i]."'";
            
          }//End Attribute name loop
          $fieldList2[] = "PRIMARY KEY (`".$className."_ID`)";
          
          //Attempt to create the Attr Values Table
          if($DB->NewTable($className.'_attrs', $fieldList2))
          {
            //Attr Values Table created successfully!
            
            //Now we must add the newly created attributes to the Attr
            //Index Table for *this class before they will become usable.
            $fields = array("name", "label", "rank", "vType");
            $sCount = 0;
            foreach($values as $valueSet)
            {
              //Attempt to add the data to the table
              if($DB->Inject($className.'_attr_index', $fields, $valueSet))
              {
                $sCount++;
              }
            }
            
            //Verify data was added to index table successfully
            if($sCount == count($values))
            {
              //Success!!
              $DB->Sever();
              RETURN true;
              
            }else{ $this->LogError("Failed to Inject Attr Index Data - attr::AddAttr()"); }
          }else{ $this->LogError("Failed to create ".$className." Attr Values Table - attr::AddAttr()"); }
        }else{ $this->LogError("Failed to create ".$className." Attr Index - attr::AddAttr()"); }
      }
      else
      {
        //Attribute Tables already exist, just add new attribute data
        //First Mod Attr Value Table to add new Attribute field
        //2nd Add new Attr data to the Attr Index Table, FIN
        
        //ModTable($tableName, $what, $colName, $type = 'varchar(32)', $value = "none")
        if($DB->ModTable($className.'_attrs', "ADD", $name, $dType, $default))
        {
          //Success!!
          
          //Now we must add the newly created attributes to the Attr
          //Index Table for *this class before they will become usable.
          $fields = array("name", "label", "rank", "vType");
          $sCount = 0;
          foreach($values as $valueSet)
          {
            //Attempt to add the data to the table
            if($DB->Inject($className.'_attr_index', $fields, $valueSet))
            {
              $sCount++;
            }
          }
          
          //Verify data was added to index table successfully
          if($sCount == count($values))
          {
            //Success!!
            $DB->Sever();
            RETURN true;
          }
        }else{ $this->LogError("ModTable failure - attr::AddAttr()"); }
      }//End TableCheck
      $DB->Sever();
    }else{ $this->LogError("Failed to Link to Database - attr:AddAttr()"); }
    
    //Failure
    RETURN false;
  }//END ADD Attribute method
  public function AddAttribute($name, $label, $rank = 0, $default='none', $vType = "text"){ RETURN $this->AddAttr($name, $label, $rank, $default, $vType); }
  
  
  //////////////////////
  //# Edit Attribute #//
  //////////////////////
  /*
    Allows the changing of attribute label and/or rank only
    Modifying any other attribute data could lead to data
    corruption. Create new attribute if needed.
    
    WARNING!: This method does not update unique values
  */
  public function EditAttr($name, $nLabel, $nRank = null)
  {
    //Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //Verify Attributes Exist
    if($this->attrE)
    {
      //Get the current child classes name
      //we will use it to locate the DB information
      $className = get_class($this);
      
      $DB = new dbAccess();
      if($DB->Link())
      {
        $fields = array("label");
        $values = array($nLabel);
        
        //Check if we need to include the rank field in this refresh
        if($nRank != null){ $fields[1] = "rank"; $values[1] = $nRank; }
        
        //Attempt to update the database data for this attribute
        if($DB->Refresh($className.'_attr_index', $fields, $values, $whereLoc))
        {
          //Success!!
          $DB->Sever();
          RETURN true;
        }
        else
        {
          //Verify Failure
          if($DB->Error() == "0 rows affected")
          {
            //Pass along that no errors happened
            //just that no changes were made
            $this->error = "no changes made";
            $DB->Sever();
            RETURN true;
            
          }else{ $this->LogError("Refresh Failure - attr::EditAttr()"); }
        }
        $DB->Sever();
      }else{ $this->LogError("Database Link Failure - attr::EditAttr()"); }
    }else{ $this->error = "No Attributes Exist"; }
    
    //Failure
    RETURN false;
  }
  public function EditAttribute($name, $nLabel, $nRank = null){ RETURN $this->EditArr($name, $nLabel, $nRank); }
  
  
  ////////////////////////
  //# Remove Attribute #//
  ////////////////////////
  /*
    Removes the attribute/s from the attribute index table associated
    with *this class, then modifies the attribute values table associated
    with *this class and drops the attributes from the table.
    (WARNING!: All unique values associated with attribute will be lost!)
  
    ACCEPTS:
      $name - the name or array of names of attributes to remove
    
    RETURNS: True | False
  */
  public function RemoveAttr($name)
  {
 		//Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //Verify Attributes Exist
    if($this->attrE)
    {
      //Get the current child classes name
      //we will use it to locate the DB information
      $className = get_class($this);
      
      //Force $name to an array to simplify things
      //and allow for both single and multi entries
      $names = (is_array($name)) ? $name : array($name);
      
      //Connect to the Database
      $DB = new dbAccess();
      if($DB->Link())
      {
        $kill = 0;//The Kill Count
        for($i=0; $i < count($names); $i++)
        {
          //Target specific Attr Index table entry
          $whereLoc = "name=".$names[$i];
          
          //Attempt to remove said entry
          if($DB->Kill($className."_attr_index", $whereLoc))
          {
            //Removed!!
            $kill++;
          }
          
          //Verify Kills
          if($kill == count($names))
          { 
            //ModTable($tableName, $what, $colName, $type = 'varchar(32)', $value = "none")
            if($DB->ModTable($className.'_attrs', "DROP", $names))
            {
              //Success!!
              $DB->Sever();
              RETURN true;
              
            }else{ $this->LogError("ModTable failure - attr::RemoveAttr()"); }
          }else{ $this->LogError("Table row kill failures - attr::RemoveAttr()"); }
        }//End Kill Loop
        $DB->Sever();
      }else{ $this->LogError("Database Link failure - attr::RemoveAttr()"); }
    }else{ $this->error = "No Attributes Exist"; }
    
    //Failure
    RETURN false;
    
  }//END Remove Attribute Method
  public function KillAttr($name){ RETURN $this->RemoveAttr($name); }
  public function DropAttr($name){ RETURN $this->RemoveAttr($name); }
  public function RemoveAttribute($name){ RETURN $this->RemoveAttr($name); }
  

  /////////////////////
  //# Update Values #//
  /////////////////////
  /*
    The Update Values method will take an array of name => value pairs and
    if the array contains pairs matching attributes in the Attr Index Table
    those pairs are updated in the Attr Values Table. Any remaining pairs will 
    be returned for further processing, unless an error occurs in which False is  
    returned. If no data remains and no error occurs True is returned.
  
    ACCEPTS:
      $data - Array of key=>value pairs ex: Array("attrName" => "attrValue")
              example with real values, ex: Array("userName" => "Felix", "userAge" => "28")
      
      $ID   - The Unique ID number for the "thing" in question
              ex: the ID number of a User or of an Item
      
    RETURNS: True, on success
             Array, if any of the data was not used, it is returned
             False, is only returned when an error occurs.
    
  */
  public function UpdateValues($data, $ID)
  {
    //Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //Get the current child classes name
    //we will use it to locate the DB information
    $className = get_class($this);
    
    //First verify that the data given contains
    //attrs, by referencing the attribute index.
    if($this->attrIndex === false)
    {
      //No Values to update... No provided data is an Attr...
      //Success, consider it done and return the data.
      RETURN $data;
    }
    else
    {
      //Initialize some variables
      $attrData = array();//Data elements that are Attrs
      $retData = array();//Data elements to return
      
      //Get list of Attribute names to reference
      foreach($this->attrIndex as $attr)
      {
        $attrIndex[] = $attr['name'];
      }
      
      //For each data element
      foreach($data as $name => $value)
      {
        if(in_array($name, $attrIndex))
        {
          //We found an Attr data element!
          //Put it into the attr array
          $attrData[$name] = $value;         
        }
        else
        {
          //The data element is not an Attr
          //put it into the return array
          $retData[$name] = $value;          
        }
      }//End Data Loop
      
      //At this point the data has been split in two parts
      //attrData and retData, we need only update the attrData
      //the retData will be returned for further processing.
      
      //First Verify if there was any Attr data
      if(!(empty($attrData)))
      {
        $DB = new dbAccess();
        if($DB->Link())
        {
          //Split up the attribute data into fields and values
          $tmp = array_split($attrData);
          $fields = $tmp[0];
          $values = $tmp[1];
          
          //Now.. does the unique thing's row exist yet?
          //If it does not than an Injection is required
          //If it does exist than a Refresh is required
          
          //Prepend the ID column and values to the front of each list.
          //this takes the place of a reg whereLoc statement
          array_unshift($fields, $className.'_ID');
          array_unshift($values, $ID);
          
          //Set the "UPDATE" flag and Run the Inject method instead of Refresh        
          if($DB->Inject($className.'_attrs', $fields, $values, "UPDATE"))
          {
            //Success!!
            $DB->Sever();
            RETURN (empty($retData)) ? true : $retData;
          }
          else
          {
            //Verify Failure by error
            if($DB->Error() == '0 rows affected')
            {
              //No actual error
              $this->error = "no changes made";
              $DB->Sever();
              RETURN (empty($retData)) ? true : $retData;
              
            }else{ $this->LogError("Inject Update Failure - attr:UpdateValues()".PHP_EOL.$DB->LastQuery()); }
          }
          $DB->Sever();
        }else{ $this->LogError("Database Link Failure - attr:UpdateValues()"); }
      }else{ RETURN (empty($retData)) ? true : $retData; }
    }//End Attr Index Check
    
    //Failure
    RETURN false;
    
  }//END Update Attr Method
  
  
  /////////////////////
  //# Remove Values #//
  /////////////////////
  /*
    Removes any values associated with the 
    provided ID from the Attr Values Table
    
    ACCEPTS:
      $ID   - The Unique ID number for the "thing" in question
              ex: the ID number of a User or of an Item (user_ID, item_ID)      
    
    RETURNS: True | False
  */
  public function RemoveValues($ID)
  {
    //Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //Get the current child classes name
    //we will use it to locate the DB information
    $className = get_class($this);
    
    //Check if any attributes exist first
    if($this->attrIndex === false)
    {
      //Success!, none exist to delete
      //we assume this is the point of removal...
      RETURN true;
    }
    else
    {
      //Attributes exist find and remove their values for this ID
      $DB = new dbAccess();
      if($DB->Link())
      {
        $whereLoc = $className.'_ID='.$ID;
        if($DB->Kill($className.'_attrs', $whereLoc))
        {
          //Success!!
          $DB->Sever();
          RETURN true;
        }
        else
        {
          if($DB->Error() == '0 rows affected')
          {
            //No actual error occurred
            $this->error = "no changes made";
            $DB->Sever();
            RETURN true;
            
          }else{ $this->LogError("Kill Failure - attr::RemoveValues()"); } 
        }
        $DB->Sever();
      }else{ $this->LogError("Database Link Failure - attr::RemoveValues()"); }
    }//End Attr Index Check
    
    //Failure
    RETURN false;
  }
  public function KillValues($ID){ RETURN $this->RemoveValues($ID); }
  public function DropValues($ID){ RETURN $this->RemoveValues($ID); }
  

  //////////////////////
  //# Attribute List #//
  //////////////////////
  /*
    Retrieves attribute, name=>value pairs 
    from the Attr Values Table, if any are available
    
    RETURNS: Array of results on success | False on failure
  */
  public function AttrList($ID)
  {
 		//Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }

    //Check if this data already exists
    //to save ourselves some processing
    if($this->attrList != false)
    {
      RETURN $this->attrList;
    }
    
    //Verify Attributes Exist
    if($this->attrE)
    {    
      //Get the current child classes name
      //we will use it to locate the DB information
      $className = get_class($this);
      
      //First create a dbAccess object
      $DB = new dbAccess();
      
      //Try to link to the DB
      if($DB->Link())
      {
        //Attempt to Snatch unique data from Attr Values Table
        $whereLoc = $className.'_ID='.$ID;
        if($DB->Snatch($className.'_attrs', '*', $whereLoc))
        {
          //Retrieve the results
          $result = $DB->Result();
          
          //Success!
          $DB->Sever();
          RETURN $result;
          
        }else{ if($DB->Error() != '0 results found'){ $this->LogError("Snatch Failure - attr:AttrList()"); } }
        $DB->Sever();
      }else{ $this->LogError("Database Link Failure - attr:AttrList()"); }
    }else{ $this->error = "No Attributes Exist"; }
    
    //Failure
    RETURN false;
    
  }//END Attribute List Method
  
  
  ///////////////////////
  //# Attribute Index #//
  ///////////////////////
  /*
    Retrieves the Attribute Index Table data associated with *this class. 
    The Attr Index Table holds data to describe the user-defined 
    attributes (attrs). If a rank is provided, the method will return only
    attributes that match that rank#.
    
    ACCEPTS:
      $rank - the rank# of the attribute
    RETURNS: False | Array of Attr data
  */
  public function AttrIndex($rank = null)
  {
 		//Get Required Configuration Variables
    $CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //Check if this data already exists
    //to save ourselves some processing
    if($this->attrIndex != false)
    {
      $this->attrE = 1;//Attributes Exist!
      $retList = $this->attrIndex;
      if(!($rank === null))
      {
        foreach($this->attrIndex as $attr)
        {
          //Force rank to be an array
          $rank = (is_array($rank)) ? $rank : array($rank);
          
          //If the attribute rank matches a provided rank..
          //add the attribute to the ret list.
          if(in_array($attr['rank'])){ $retList[] = $attr; }
        }
      }
      RETURN $retList;
    }

    //Get the current child classes name
    //we will use it to locate the DB information
    $className = get_class($this);
    
    //First create a dbAccess object
    $DB = new dbAccess();
    
    //Try to link to the DB
    if($DB->Link())
    {
      //Check if the table exists
      if($DB->TableExists($className.'_attr_index'))
      {
        //Retrieve the Attribute Index Data
        if($DB->Snatch($className.'_attr_index'))
        {
          //Found Thing Attributes!
          $result = $DB->Result();
          
          //force array, $result[0] = array("name" => "someName", "label" => "someLabel", "rank" => #);
          if(!(isset($result[0]))){ $result = array($result); }
          
          //Set the Attribute Exists flag to true
          $this->attrE = 1;//Attributes Exist!
          $retList = $result;
          
          //Check if a rank was provided
          if(!($rank === null))
          {
            foreach($result as $attr)
            {
              //Force rank to be an array of ranks
              $rank = (is_array($rank)) ? $rank : array($rank);
              
              //If the attribute rank matches a provided rank..
              //add the attribute to the ret list.
              if(in_array($attr['rank'], $rank)){ $retList[] = $attr; }
            }
          }
          
          //Success, return the index!
          $DB->Sever();
          RETURN $retList; 
          
        }else{ $this->LogError("Snatch Failure - attr::AttrIndex()"); }
      }//else{ $this->LogError("Index Table Does not Exist - attr::AttrIndex()"); }
      
      //No Extra Attributes Exist Yet...
      $this->attrE = 0;
      $DB->Sever();
      
    }else{ $this->LogError("Database Link Failure - attr::attr()"); }
    
    //Failure
    RETURN false;
    
  }//END Attribute Index Method
  
}//END ATTR CLASS
?>