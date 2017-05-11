<?php
/*
	[ dbAccess Class ] [5/30/2012]
	CORTEX by Native Inventions < www.NativeInventions.com >
	Author: Nathan < nathan@nativeinventions.com >
	
	This class provides simple interactions with MySQL DB, as well as, 
	better control over database connections. Read the commenting below
	for more information.
	[ !! This class utilizes a configuration file !! ]
  
  !!WARNING!! - THIS CLASS UTILIZES A DEPRICATED(PHP 5.5) SET OF MYSQL 
                PHP FUNCTIONS AND MUST BE UPDATED TO USE THE NEW SET
                http://php.net/manual/en/function.mysql-query.php
*/
//require_once('inc/classes/class.error.php');

class dbAccess extends error {
	
	//Declare Misc Variables
	private $Linked;	  //MySQL connection flag [Holds Link Identifier if connection is Alive]
	private $Result;	  //Holds query results
	private $InjectID;	//Holds last insert auto incremented ID
  private $DB_SQL;	  //Holds most recent SQL statement.(nifty for debugging)!
	
	//!!!!!!!!!!!!!!!!!!!//
	//!!! CONSTRUCTOR !!!//
	//!!!!!!!!!!!!!!!!!!!//
	/*
    Intializes default values
		
		ACCEPTS: void
		
		RETURNS: void
	*/
	function dbAccess()
	{
		//Initialize properties
		$this->DB_SQL = null;
		$this->Linked = false;
		$this->Result = null;
		$this->InjectID = 0;
	}
	
	
	//#^^ GET QUERY RESULT ^^#//
	/*
		RETURNS the result from
		the last query run
	*/
	public function Result(){return $this->Result;}
	
	//#^^ GET LAST QUERY ^^#//
	public function LastQuery(){ return $this->DB_SQL;}
	
	//#^^ GET INJECT ID ^^#//
	public function InjectID(){return $this->InjectID;}
	
	//#^^ GET LINK IDENTIFIER ^^#//
	public function LinkID(){return $this->Linked;}
	
	
	//%^^ MySQL Connect Method ^^%//
	/*
		Establishes a connection to the DB
		RETURNS: true/false
	*/
	public function Link() 
	{
		//##^^ Retrieve configuration details ^^##//
		$CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
		
		//Check if already connected
		if(!$this->Linked)
		{
			//Connect to DB
			$this->Linked = mysql_connect($this->Decrypt($CONFIG['DB_Host']), $this->Decrypt($CONFIG['DB_User']), $this->Decrypt($CONFIG['DB_Pass']), true);
			
			//Check if connected
			if($this->Linked != false)
			{
				//Autoselect the default DB name given the in configuration file
				//this can be switched later by the user if wish to use the same function
				if($this->SelectDB())
				{
					RETURN true;
				}
			}
      else
      {
        $this->error = 'Database link failure';
        $this->LogError('Database link failure -- dbAccess::Link()');
        $this->LogError(mysql_error());
      }
		}
		else
		{
			RETURN true;
			//Still Linked-
		}
    
    RETURN false;
	}//END Link
	
  
	//%^^ MySQL Select Database Method ^^%//
	/*	
		Selects a database. If the database
		does not exist this method will attempt
		to create the database.
		
		RETURNS : True/False
	*/
	public function SelectDB($DB_NAME = null)
	{
		//##^^ Retrieve configuration details ^^##//
		$CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false;}

		//If no database name given use the default in configuration file
    $DB_NAME = ($DB_NAME == null) ? $this->Decrypt($CONFIG['DB_Name']) : $DB_NAME;
		
		//# Select the Database
		$DB_Select = @mysql_select_db($DB_NAME, $this->Linked);
		if($DB_Select)
		{
			//Database found and selected.
			return true;
		}
		else
		{
			//# Try to create the Database
			$NEW_DB = @mysql_query("CREATE DATABASE ".$DB_NAME, $this->Linked);
			if($NEW_DB)
			{
				//Database created successfully
				
				//Purge stale $DB_Select var
				$DB_Select = null;
				
				//# Select the DB
				$DB_Select = @mysql_select_db($DB_NAME, $this->Linked);
				if($DB_Select)
				{
					//Database found and selected.
					return true;
				}
				else
				{
					//Failed to select DB
          $this->error = 'Failed to select database!';
					$this->LogError('Failed to select database! -- dbAccess::SelectDB()'.PHP_EOL.mysql_error());
					return false;
				}
			}
			
			//Failed to create DB
			$this->error = mysql_error();
			return false;
		}
	}
	
  
  //%^^ MySQL Table Exists Method ^^%//
  public function TableExists($table)
  {
    //##^^ Retrieve configuration details ^^##//
    $CONFIG = @parse_ini_file(CONFIG_PATH);
    if($CONFIG === false){ RETURN false; }      

		//Store SQL query in object place holder
		//for debugging references.
    $SQL = "SHOW TABLES LIKE '".$CONFIG['DB_Prefix'].$table."'";
		$this->DB_SQL = $SQL;
    
		//Run the QUERY
		$Query = mysql_query($SQL, $this->Linked);
    
    if(mysql_num_rows($Query) > 0)
    {
      RETURN true;
    }
    
    //Failure
    RETURN false;
  }

  
	//%^^ MySQL Select Method ^^%//
	/*
		ACCEPTS : TABLENAME | FIELD | WHERE (clause) | ORDERBY | DISTINCT (clause)
		RETURNS : [True] | [False]
		Populates Result with the data snatched from the database
	*/
	public function Snatch($table, $field = '*', $where = null, $order = null, $distinct = false, $limit = 0)
	{
		//##^^ Retrieve configuration details ^^##//
		$CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
		
		//Purge Stale Results/Errors
		$this->Result = null;
		$this->error = null;
		
		//Begin SQL string
		$SQL = "SELECT ";
		
		if($distinct)
		{
			$SQL .= "DISTINCT ";
		}
		
		//Process $field values/value
		if(is_array($field))
		{	
			//Loop threw column values and append to query
			for($i = 0; $i < count($field); $i++)
			{
				if($i == 0)
				{
					$SQL .= "`".$field[$i]."`";
				}
				else
				{
					$SQL .= ", `".$field[$i]."`";
				}
			}
		}
		else
		{
			//Single Value Given
			if($field == '*')
			{
				$SQL .= $field;
			}
			else
			{
				$SQL .= "`".$field."`";
			}
		}
		
		//Add fields to string
		$SQL .= " FROM `".$CONFIG['DB_Prefix'].$table."`";
		
		//Where expression given?
		if($where != null)
		{
			//Begin the WHERE statement
			$SQL .= " WHERE ";
			
			//check for multi results
			if(is_array($where))
			{
        //Before we do anything we need to check
        //if the first array element is a "|" (or)
        //symbol, if so we concat the where clauses
        //with ORs instead of ANDs...
        $concatSym = " AND ";//default
        if($where[0] === "|")
        {
          $concatSym = " OR ";
          //now we need to shift off the first element
          array_shift($where);
        }
        
        //Now count the where clauses
        //and cycle through each one
				$wCount = count($where);
				for($i=0; $i < $wCount; $i++)
				{
					//Concat the where clauses
					if($i > 0)
					{
            //Do we use AND or OR??
            
						$SQL .= $concatSym;
					}
					
					//Standard or between clause?
          $match = array();
					if((preg_match('/>=/', $where[$i], $match) || preg_match('/<=/', $where[$i], $match)) || (preg_match('/=/', $where[$i], $match) || (preg_match('/>/', $where[$i], $match) || preg_match('/</', $where[$i], $match))))
					{
						//echo("found comparison operator in '".$where[$i]."'<br>");
						
						//Break apart the clause and format for DB
						//Purifying the where value
						$temp = explode($match[0], $where[$i]);
						$tmpWhere = "`".$temp[0]."`".$match[0]."'".$this->Purify($temp[1])."'";
						$SQL .= $tmpWhere;
					}
					else if(preg_match('/BETWEEN/i', $where[$i]))
					{
            //echo("Found a between where statement in '".$where[$i]."'<br>");
            
            //Break apart the clause and format for DB
            //Purifying the range values
            $temp = explode(" ", $where[$i]);
            //`colname` BETWEEN 'val1' AND 'val2'
            /*
              temp[1] = BETWEEN
              temp[3] = AND
            */
            $tmpWhere = "`".$temp[0]."` BETWEEN '".$this->Purify($temp[2])."' AND '".$this->Purify($temp[4])."'";
            $SQL .= $tmpWhere;
					}
          else
          {
            //Check for a LIKE clause
            if(preg_match('/LIKE/i', $where[$i]))
            {
              $temp = explode(" ", $where[$i]);
              //$temp[0] = col name
              //$temp[1] = LIKE
              //$temp[2] = search pattern
              $tmpWhere = "`".$temp[0]."` LIKE '".$this->Purify($temp[2])."'";
              $SQL .= $tmpWhere;
            }
          }
				}
			}
			else
			{
				//Handle single where expression
        $match = array();
				if((preg_match('/>=/', $where, $match) || preg_match('/<=/', $where, $match)) || (preg_match('/=/', $where, $match) || (preg_match('/>/', $where, $match) || preg_match('/</', $where, $match))))
				{
					//echo("found comparison operator symbol in '".$where."'<br>");
					//Break apart the clause and format for DB
					//Purifying the where value
					$temp = explode($match[0], $where);
					$tmpWhere = "`".$temp[0]."`".$match[0]."'".$this->Purify($temp[1])."'";
					$SQL .= $tmpWhere;
				}
				else if(preg_match('/BETWEEN/i', $where))
				{
					//Check for a BETWEEN clause
					
					//Break apart the clause and format for DB
					//Purifying the range values
					$temp = explode(" ", $where);
					//`colname` BETWEEN 'val1' AND 'val2'
					/*
						temp[1] = BETWEEN
						temp[3] = AND
					*/
					$tmpWhere = "`".$temp[0]."` BETWEEN '".$this->Purify($temp[2])."' AND '".$this->Purify($temp[4])."'";
					$SQL .= $tmpWhere;
				}
				else
				{
					//Check for a LIKE clause
					if(preg_match('/LIKE/i', $where))
					{
						$temp = explode(" ", $where);
						//$temp[0] = col name
						//$temp[1] = LIKE
						//$temp[2] = search pattern
						$tmpWhere = "`".$temp[0]."` LIKE '".$this->Purify($temp[2])."'";
						$SQL .= $tmpWhere;
					}
				}
			}
		}//End where expressions
		
		//Order expression?
		if($order != null)
		{
			$SQL .= " ORDER BY ".$order;
		}
    
    //Limiter?
    if($limit !== 0)
    {
      $SQL .= " LIMIT ".$limit;
    }
    
		//Store SQL query in object place holder
		//for debugging references.
		$this->DB_SQL = $SQL;
    
		//Run the QUERY
		$Query = @mysql_query($SQL, $this->Linked);
		if($Query)
		{
			//Count Results
			$NumResults = mysql_num_rows($Query);
			if($NumResults >= 1)
			{
				for($i = 0; $i < $NumResults; $i++)
				{
					$temp = mysql_fetch_array($Query);
					$key = array_keys($temp);
				
					for($z = 0; $z < count($key); $z++)
					{
						if(!is_int($key[$z]))
						{
							if($NumResults > 1)
							{
								$this->Result[$i][$key[$z]] = htmlspecialchars_decode($temp[$key[$z]], ENT_QUOTES);
							}
							else if($NumResults < 1)
							{
								$this->Result = null;
							}
							else
							{
								$this->Result[$key[$z]] = htmlspecialchars_decode($temp[$key[$z]], ENT_QUOTES);
							}
						}
					}
				}
				RETURN true;
			}
			else
			{
				$this->error = "$NumResults results found";
				RETURN false;
			}
		}
		else
		{   
			$this->LogError($this->error = mysql_error($this->Linked));
			RETURN false;
		}
	}//END Snatch
	
  
	//%^^ MySQL [select] JOIN Method ^^%//
	/*
		ACCEPTS	:
			[TABLES]		= array
			[LINKCOLUMN]	= string
			[WHERE]			= string(where expression) - (optional: default = null)
			[FIELDLIST]		= string, array, 2D_array - (optional: default = "*")
			[METHOD]		= "INNER", "LEFT", "RIGHT", "FULL" - (optional: default = "LEFT")
		
		RETURNS : 
			[True]
			[False]
			
		Populates Result with the data gathered from the tables in the array
		matching the join method and linker column given.
	*/
	public function Gather($tables, $linkColumn, $where = null, $fieldList = "*", $method = "LEFT")
	{
  	//##^^ Retrieve configuration details ^^##//
		$CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
    //Purge Stale Results/Errors
		$this->Result = null;
		$this->error = null;
		
    //Verify that $tables is an array and not empty
		if((!(is_array($tables))) || empty($tables))
		{
			//1 or zero tables given
			//this function only supports
			//multiple tables..
			$this->error = "Gather method supports multiple table queries only!.. Try the Snatch method!";
			RETURN false;
		}
		else
		{
			//#** Table is verified as array
      
			//count how many tables we are joining
			$tableCount = count($tables);
			
      //Quickly Prefix all table names before we get started
      for($i=0; $i < $tableCount; $i++)
      {
        $tables[i] = $CONFIG['DB_Prefix'].$tables[$i];
      }
      
			//Figure out what kinda data we were given for the field
			//list and build the SQL string for the fields
			if(is_array($fieldList))
			{
				//#** Multiple Fields Detected
				
				$fieldListCount = count($fieldList);
				
				//We do this because we don't want the loop to run for each table if there
				//are only enough fieldlists for one loop run. Since a single fieldlist
				//was given we default to prefixing the first tables name to it.
				$dynTableCount = (!(isset($fieldList[0]))) ? 1 : $tableCount;
				
				//For each table
				for($i=0; $i < $dynTableCount; $i++)
				{
					//check for 2D array
					if(!(isset($fieldList[0])))
					{
						//#** Array Detected
						$fieldList1D = $fieldList;
					}
					else
					{
						//#** 2D Array Detected
						//Each dimension is matched with
						//each table
						$fieldList1D = $fieldList[$i];
					}
					
					//Count how many fields in this list
					$fCount = count($fieldList1D);
          
					//Foreach fieldlist
					for($n=0; $n < $fCount; $n++)
					{
						if($n == 0 && $i == 0)
						{
							$fieldListSQL .= $tables[$i].".".$fieldList1D[$n];
						}
						else
						{
							$fieldListSQL .= ", ".$tables[$i].".".$fieldList1D[$n];
						}
					}
				}
			}
			else
			{
				//#** FieldList is a string
				
				//check for * symbol
				if($fieldList == '*')
				{
					//#** All fields detected.
					$fieldListSQL = "* ";
				}
				else
				{
					//#** Single Field Detected
					$fieldListSQL = $tables[0].".".$fieldList." ";
				}
			}
		}
		//TABLES AND FIELDS HAVE BEEN 
		//VERIFIED AND ARE READY TO GO
		
		//Begin the SQL statement
		$SQL = "SELECT ".$fieldListSQL." FROM ".$tables[0];
		
		//Switch JOIN METHOD
		switch($method)
		{
			//Inner Join
			case'INNER':
			case'inner':
				$JOIN = "INNER";
			break;
			
			//Right Join
			case'RIGHT':
			case'right':
				$JOIN = "RIGHT";
			break;
			
			//Full Join
			case'FULL':
			case'full':
				$JOIN = "FULL";
			break;			
			
			//Default is a LEFT Join
			default:
				$JOIN = "LEFT";
			break;
		}
		
		//Create a JOIN statement per table, skipping the zero table
		for($i=0; $i < $tableCount; $i++)
		{
			if($i != 0)
			{
				$SQL .= " ".$JOIN." JOIN ".$tables[$i]." ON ".$tables[0].".".$linkColumn."=".$tables[$i].".".$linkColumn;
			}				
		}
		
		//Where expression given?
		if($where != null)
		{
			//Begin the WHERE statement
			$SQL .= " WHERE ";
			
			//check for multi results
			if(is_array($where))
			{
				$wCount = count($where);
				for($i=0; $i < $wCount; $i++)
				{
					//Concat the where clauses
					if($i > 0)
					{
						$SQL .= " AND ";
					}
					
					//Standard or between clause?
					if(preg_match('/=/', $where[$i]))
					{
						//echo("found equal symbol in '".$where[$i]."'<br>");
						
						//Break apart the clause and format for DB
						//Purifying the where value
						$temp = explode('=', $where[$i]);
						$tmpWhere = $temp[0]."='".$this->Purify($temp[1])."'";
						$SQL .= $tmpWhere;
					}
					else
					{
						//echo("Did not find equal symbol in '".$where[$i]."'");
						//Check for a between statement
						if(preg_match('/BETWEEN/i', $where[$i]))
						{
							//echo("Found a between where statement in '".$where[$i]."'<br>");
							
							//Break apart the clause and format for DB
							//Purifying the range values
							$temp = explode(" ", $where[$i]);
							//`colname` BETWEEN 'val1' AND 'val2'
							/*
								temp[1] = BETWEEN
								temp[3] = AND
							*/
							$tmpWhere = $temp[0]." BETWEEN '".$this->Purify($temp[2])."' AND '".$this->Purify($temp[4])."'";
							$SQL .= $tmpWhere;
						}
					}
				}
			}
			else
			{
				//Standard or between clause?
				if(preg_match('/=/', $where))
				{
					//echo("found equal symbol in '".$where."'<br>");
					
					//Break apart the clause and format for DB
					//Purifying the where value
					$temp = explode('=', $where);
					$tmpWhere = $temp[0]."='".$this->Purify($temp[1])."'";
					$SQL .= $tmpWhere;
				}
				else
				{
					//echo("Did not find equal symbol in '".$where[$i]."'");
					//Check for a between statement
					if(preg_match('/BETWEEN/i', $where))
					{
						//echo("Found a between where statement in '".$where."'<br>");
						
						//Break apart the clause and format for DB
						//Purifying the range values
						$temp = explode(" ", $where);
						//`colname` BETWEEN 'val1' AND 'val2'
						/*
							temp[1] = BETWEEN
							temp[3] = AND
						*/
						$tmpWhere = $temp[0]." BETWEEN '".$this->Purify($temp[2])."' AND '".$this->Purify($temp[4])."'";
						$SQL .= $tmpWhere;
					}
				}
			}
		}//End where expressions
		//FINISHED COMPILING SQL QUERY
		
		//Store SQL query in object place holder
		//for debugging references.
		$this->DB_SQL = $SQL;
		
		//Run the QUERY
		$Query = @mysql_query($SQL, $this->Linked);
		if($Query)
		{
			//Count Results
			$NumResults = mysql_num_rows($Query);
			if($NumResults >= 1)
			{
				for($i = 0; $i < $NumResults; $i++)
				{
					$temp = mysql_fetch_array($Query);
					$key = array_keys($temp);
				
					for($z = 0; $z < count($key); $z++)
					{
						if(!is_int($key[$z]))
						{
							if($NumResults > 1)
							{
								$this->Result[$i][$key[$z]] = htmlspecialchars_decode($temp[$key[$z]], ENT_QUOTES);
							}
							else if($NumResults < 1)
							{
								$this->Result = null;
							}
							else
							{
								$this->Result[$key[$z]] = htmlspecialchars_decode($temp[$key[$z]], ENT_QUOTES);
							}
						}
					}
				}
				RETURN true;
			}
			else
			{
				$this->error = " $NumResults results found.<br>";
				RETURN false;
			}
		}
		else
		{
			$this->error = " Query Failed: ".mysql_error($this->Linked);
			RETURN false;
		}
	}
	
  
  //--------------------------------------------------
  //!! WRITE A SCATTER METHOD !!//
  /*
    This method would be much like the "Gather"
    method in this class however it would be the
    inject equivalent
    
    Gather is to Snatch   as   Scatter is to Inject
  */
  //--------------------------------------------------
  
  
	//%^^ MySQL Insert Method ^^%//
	/*
		RETURNS : true/false
		Populates Result with number of affected rows.
	*/
	public function Inject($table, $column, $value, $flag = 'default') 
	{
		//##^^ Retrieve configuration details ^^##//
		$CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
		
		//Purge Stale Results/Errors
		$this->Result = null;
		$this->error = null;
		
		//^^ Start Building SQL string ^^//
    
    //Create switch to handle flags
    switch($flag)
    {
      //An "Empty" insert, one that uses no cols/values
      //and uses default col values
      case'empty':
      case'EMPTY':
        $SQL = "INSERT INTO `".$CONFIG['DB_Prefix'].$table."` DEFAULT VALUES";
      break;
    
      //Delayed Insert
      case'delayed':
      case'DELAYED':
        $SQL = "INSERT DELAYED `".$CONFIG['DB_Prefix'].$table."`";
      break;
      
      //Normal Insert
      default:
        $SQL = "INSERT `".$CONFIG['DB_Prefix'].$table."`";
      break;
    }
		
    if(strtolower($flag) != 'empty')
    {
      //Process $column values/value
      if(is_array($column))
      { 
        $SQL .= " (";
        
        //Loop threw column values and append to query
        for($i = 0; $i < count($column); $i++)
        {
          if($i == 0)
          {
            $SQL .= "`".$column[$i]."`";
          }
          else
          {
            $SQL .= ", `".$column[$i]."`";
          }
          
          //Create second array for possible Update clause
          $dValues[$i] = "`".$column[$i]."`='".$value[$i]."'"; 
        }
        
        $SQL .= ")";
      }
      else
      {
        //Single column value Given
        $SQL .= " (`".$column."`)";
        
        //No Need to handle possible update clause here
      }
      
      //Process $value values/value
      if(is_array($value))
      {
        $SQL .= " VALUES ('";
        
        for($i = 0; $i < count($value); $i++)
        {
          if($i == 0)
          {
            $SQL .= $this->Purify($value[$i]);
          }
          else
          {
            $SQL .= "', '".$this->Purify($value[$i]);
          }
          //Possible Update Clause already handled in column section
        }
        
        $SQL .= "')";
      }
      else
      {
        $SQL .= " VALUES ('".$this->Purify($value)."')";
      }
		}
    
    //Update if Exists flag given?
    if(strtolower($flag) == 'update')
    {
      //Remove first entry of $dValues, its the primary key
      //we don't need to include it for the update clause
      array_shift($dValues);
      
      //Format the extra Update clause
      $dValueSQL = (count($dValues) > 1) ? implode(', ', $dValues) : $dValues[0];
      $SQL .= " ON DUPLICATE KEY UPDATE ".$dValueSQL;
    }
    
		//Store SQL query in object place holder
		//for debugging references.
		$this->DB_SQL = $SQL;
		
		//Run the Query
		$Query = @mysql_query($SQL, $this->Linked);// or die ($this->error = mysql_error());
		if($Query)
		{
			//Store Result Information
			$this->Result = mysql_affected_rows($this->Linked);
			
			//Store the ID given to the new insert
			$this->InjectID = mysql_insert_id($this->Linked);
			
			RETURN true;
		}
		else
		{
			$this->LogError($this->error = mysql_error());
			RETURN false;
		}
	}
	
  
	//%^^ MySQL Update Method ^^%//
	/*
		RETURNS : true | false
		Populates Result with number of affected rows on success
		Populates Error with number of affected rows on failure
	*/
	public function Refresh($table, $set, $setValue, $where = null)
	{
		//##^^ Retrieve configuration details ^^##//
		$CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
		
		//Purge Stale Results/Errors
		$this->Result = null;
		$this->error = null;
		
		//Begin SQL string
		$SQL = "UPDATE `".$CONFIG['DB_Prefix'].$table."` SET ";
		
		//Check if $set is an array
		//and if it has values
		$setCount = count($set);
		$setValueCount = count($setValue);
		if(is_array($set) && is_array($setValue))
		{
			//Loop through set conditions
			for($i = 0; $i < $setCount; $i++)
			{
				if($i == 0)
				{
					$SQL .= "`".$set[$i]."`='".$this->Purify($setValue[$i])."'";
				}
				else
				{
					$SQL .= ", `".$set[$i]."`='".$this->Purify($setValue[$i])."'";
				}
			}
		}
		else
		{
			//single value for $set
			$SQL .= "`".$set."`='".$this->Purify($setValue)."'";
		}
		
		//Where expression given?
		if($where != null)
		{
			//Begin the WHERE statement
			$SQL .= " WHERE ";
			
			//check for multi results
			if(is_array($where))
			{
				$wCount = count($where);
				for($i=0; $i < $wCount; $i++)
				{
					//Concat the where clauses
					if($i > 0)
					{
						$SQL .= " AND ";
					}
					
					//Standard or between clause?
					if(preg_match('/=/', $where[$i]))
					{
						//echo("found equal symbol in '".$where[$i]."'<br>");
						
						//Break apart the clause and format for DB
						//Purifying the where value
						$temp = explode('=', $where[$i]);
						$tmpWhere = "`".$temp[0]."`='".$this->Purify($temp[1])."'";
						$SQL .= $tmpWhere;
					}
					else
					{
						//echo("Did not find equal symbol in '".$where[$i]."'");
						//Check for a between statement
						if(preg_match('/BETWEEN/i', $where[$i]))
						{
							//echo("Found a between where statement in '".$where[$i]."'<br>");
							
							//Break apart the clause and format for DB
							//Purifying the range values
							$temp = explode(" ", $where[$i]);
							//`colname` BETWEEN 'val1' AND 'val2'
							/*
								temp[1] = BETWEEN
								temp[3] = AND
							*/
							$tmpWhere = "`".$temp[0]."` BETWEEN '".$this->Purify($temp[2])."' AND '".$this->Purify($temp[4])."'";
							$SQL .= $tmpWhere;
						}
					}
				}
			}
			else
			{
				//Standard or between clause?
				if(preg_match('/=/', $where))
				{
					//echo("found equal symbol in '".$where."'<br>");
					
					//Break apart the clause and format for DB
					//Purifying the where value
					$temp = explode('=', $where);
					$tmpWhere = "`".$temp[0]."`='".$this->Purify($temp[1])."'";
					$SQL .= $tmpWhere;
				}
				else
				{
					//echo("Did not find equal symbol in '".$where[$i]."'");
					//Check for a between statement
					if(preg_match('/BETWEEN/i', $where))
					{
						//echo("Found a between where statement in '".$where."'<br>");
						
						//Break apart the clause and format for DB
						//Purifying the range values
						$temp = explode(" ", $where);
						//`colname` BETWEEN 'val1' AND 'val2'
						/*
							temp[1] = BETWEEN
							temp[3] = AND
						*/
						$tmpWhere = "`".$temp[0]."` BETWEEN '".$this->Purify($temp[2])."' AND '".$this->Purify($temp[4])."'";
						$SQL .= $tmpWhere;
					}
				}
			}
		}//End where expressions
		
		//Store SQL query in object place holder
		//for debugging references.
		$this->DB_SQL = $SQL;
		
		//Run the Query
		$Query = @mysql_query($SQL, $this->Linked);
		if($Query)
		{
			//Check for affected rows
			$AfctRows = mysql_affected_rows($this->Linked);
			if($AfctRows > 0)
			{
				$this->Result = $AfctRows;
				RETURN true;
			}
			else
			{
				$this->error = $AfctRows." rows affected"; //[SQL] = ".$SQL;
				RETURN false;
			}
		}
		else
		{
			$this->LogError($this->error = mysql_error($this->Linked));
			RETURN false;
		}
	}
	
  
	//%^^ MySQL Create New Table Method ^^%//
	/*
		ACCEPTS: TableName(string), FieldList(array|string|null)
		RETURNS: true | false
		
		Creates a new empty table using the name provided.
	*/
	public function NewTable($tableName, $fieldList = null)
	{
    //##^^ Retrieve configuration details ^^##//
		$CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
		$SQL = "CREATE TABLE IF NOT EXISTS ".$CONFIG['DB_Prefix'].$tableName;
		if($fieldList != null)
		{
			if(is_array($fieldList))
			{
				$SQL .= " (";
				for($i=0; $i < count($fieldList); $i++)
				{
					if($i == 0)
					{
						$SQL .= $fieldList[$i];
					}
					else
					{
						$SQL .= ", ".$fieldList[$i];
					}
				}
				$SQL .= ")";
			}
			else
			{
				$SQL .= "(".$fieldList.")";
			}
		}
		//SQL Finished
		//Store SQL query in object place holder
		//for debugging references.
		$this->DB_SQL = $SQL;
		
		//Run the query
		$Query = @mysql_query($SQL, $this->Linked);
		if($Query)
		{
			RETURN true;
		}
		else
		{
			$this->LogError($this->error .= "Unable to create new table<br>".mysql_error($this->Linked));
			RETURN false;
		}
	}
	
  
	//%^^ MySQL Rename Table Method ^^%//
	/*
		Modifys database Table names
	*/
	public function RenameTable($tableName, $newTableName)
	{
    //##^^ Retrieve configuration details ^^##//
		$CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
		//RENAME TABLE mytable TO yourtable, etc..
		if(is_array($tableName) || is_array($newTableName))
		{
			$tCount = count($tableName);
			$ntCount = count($newTableName);
			if($tCount == $ntCount)
			{
				$SQL = "RENAME TABLE ";
				for($i=0; $i<$tCount; $i++)
				{
					if($i == 0)
					{
						$SQL .= $CONFIG['DB_Prefix'].$tableName[$i]." TO ".$CONFIG['DB_Prefix'].$newTableName[$i];
					}
					else
					{
						$SQL .= ", ".$CONFIG['DB_Prefix'].$tableName[$i]." TO ".$CONFIG['DB_Prefix'].$newTableName[$i];
					}
				}
			}
			else
			{
				$this->error = "Table name count mis-match!";
				RETURN false;
			}
		}
		else
		{
			$SQL = "RENAME TABLE ".$CONFIG['DB_Prefix'].$tableName." TO ".$CONFIG['DB_Prefix'].$newTableName;
		}
		
		$Query = @mysql_query($SQL, $this->Linked());
		if(!($Query)){$this->error = mysql_error($this->Linked);}
		echo("TableRename: ".$Query);
		exit();
	}
	
  
	//%^^ MySQL MOD Table Method
	/*
		Modifies database Table Columns/Fields
    
    tableName - The name of the database table to modify
    what      -
                TYPE    - Change a table field's datatype
                RENAME  - Rename a table field
                DROP    - Drop/Remove a table field/s
              * ADD     - Add a new field to the table
    
    colName - name/s of the column/field to modify (May only be array when using DROP or ADD)
    type - the data type to use for the database field value (May only be array when using ADD)
    value - the default value of a new column/field OR newName of field if using RENAME
            (May only be array when using ADD)
	*/
	public function ModTable($tableName, $what, $colName, $type = 'varchar(32)', $value = "none")
	{
    //##^^ Retrieve configuration details ^^##//
		$CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
		//Start the SQL statement
		$SQL = "ALTER TABLE ".$CONFIG['DB_Prefix'].$tableName;
    
		switch($what)
		{
			case'TYPE':
			case'type':
				//Change a fields datatype
				//(ALTER TABLE tName ALTER COLUMN cName datatype)
				$SQL .= " ALTER COLUMN ".$colName.' '.$type;
			break;
			
			case'RENAME':
			case'rename':
				//Rename a table field
				//(ALTER TABLE tName CHANGE COLUMN oldname newname datatype)
				$SQL .= " CHANGE COLUMN ".$colName." ".$value." ".$type;
			break;
			
			case'DROP':
			case'drop':
				//Drop/Remove a table field
				//(ALTER TABLE tName DROP COLUMN cName)
        if(is_array($colName))
        {
          for($i=0; $i < count($colName); $i++)
          {
            $SQL .= " DROP COLUMN ".$colName[$i];
          }
        }else{ $SQL .= " DROP COLUMN ".$colName; }
			break;
			
			default:
				//Add a new field to the table
				//(ALTER TABLE tName ADD cName datatype)
        if(is_array($colName))
        {
          //Create the ADD clauses for each colName
          for($i=0; $i < count($colName); $i++)
          {
            //Equalize the input data, just in case
            $type[$i] = (is_array($type) && !(empty($type[$i]))) ? $type[$i] : "varchar(32)";
            $value[$i] = (is_array($value) && !(empty($value[$i]))) ? $value[$i] : "none";
            
            $default = (strtolower($type[$i]) == 'text') ? '' : " DEFAULT '".$value[$i]."'";
            //Add the new ADD clause to the SQL statement
            $SQL .= rtrim(" ADD ".$colName[$i]." ".$type[$i]." NOT NULL".$default);
          }
        }
        else
        { 
          $default = (strtolower($type) == 'text') ? '' : " DEFAULT '".$value."'";
          $SQL .= rtrim(" ADD ".$colName." ".$type." NOT NULL".$default); 
        }
				//In this case value should actually be holding the default value for the new column
			break;
		}
		
		//Store SQL query in place holder
		//for debugging references.
		$this->DB_SQL = $SQL;
		
		$Query = @mysql_query($SQL, $this->Linked);
		if($Query)
		{
			RETURN true;
		}
		else
		{
			$this->LogError($this->error = "Failed to Mod table ".$CONFIG['DB_Prefix'].$tableName.PHP_EOL.mysql_error($this->Linked));
			RETURN false;
		}
	}
	
  
	//%^^ MySQL Delete Method ^^%//
	/*
		[Brief] - Deletes Tables/Table Rows & Databases
    
    $T_DB (req)      - the name of the table or database
    
    $where (opt/req) - where statement if targeting a row
                     (you must set this value as null when setting drop flags)
    
    $drop (opt)      - drop flag 
                     {options}: 
                      -> database - flag to drop a database
                      -> truncate - flag to truncate a named table (clears the table and resets auto increments)
                      -> clear    - flag to clear a table (default)
                      -> drop     - flag to drop a table
		
		[RETURNS] - True | False
	*/
	public function Kill($T_DB, $where = null, $drop = null)
	{
    //##^^ Retrieve configuration details ^^##//
		$CONFIG = @parse_ini_file(CONFIG_PATH);
		if($CONFIG === false){ RETURN false; }
    
		if($where == null && $drop == null)
		{
      //Do nothing! Mistake could be made!!
      //must specify what your doing to table
      //by adding drop flag or where clause!!
      RETURN false;
		}
		else if($where != null)
		{
			//Kill table rows
			$SQL = "DELETE FROM `".$CONFIG['DB_Prefix'].$T_DB."`";
			
			//Begin the WHERE statement
			$SQL .= " WHERE ";
			
			//check for multi results
			if(is_array($where))
			{
				$wCount = count($where);
				for($i=0; $i < $wCount; $i++)
				{
					//Concat the where clauses
					if($i > 0)
					{
						$SQL .= " AND ";
					}
					
					//Standard or between clause?
					if(preg_match('/=/', $where[$i]))
					{
						//echo("found equal symbol in '".$where[$i]."'<br>");
						
						//Break apart the clause and format for DB
						//Purifying the where value
						$temp = explode('=', $where[$i]);
						$tmpWhere = "`".$temp[0]."`='".$this->Purify($temp[1])."'";
						$SQL .= $tmpWhere;
					}
					else
					{
						//echo("Did not find equal symbol in '".$where[$i]."'");
						//Check for a between statement
						if(preg_match('/BETWEEN/i', $where[$i]))
						{
							//echo("Found a between where statement in '".$where[$i]."'<br>");
							
							//Break apart the clause and format for DB
							//Purifying the range values
							$temp = explode(" ", $where[$i]);
							//`colname` BETWEEN 'val1' AND 'val2'
							/*
								temp[1] = BETWEEN
								temp[3] = AND
							*/
							$tmpWhere = "`".$temp[0]."` BETWEEN '".$this->Purify($temp[2])."' AND '".$this->Purify($temp[4])."'";
							$SQL .= $tmpWhere;
						}
					}
				}
			}
			else
			{
				//Standard or between clause?
				if(preg_match('/=/', $where))
				{
					//echo("found equal symbol in '".$where."'<br>");
					
					//Break apart the clause and format for DB
					//Purifying the where value
					$temp = explode('=', $where);
					$tmpWhere = "`".$temp[0]."`='".$this->Purify($temp[1])."'";
					$SQL .= $tmpWhere;
				}
				else
				{
					//echo("Did not find equal symbol in '".$where[$i]."'");
					//Check for a between statement
					if(preg_match('/BETWEEN/i', $where))
					{
						//echo("Found a between where statement in '".$where."'<br>");
						
						//Break apart the clause and format for DB
						//Purifying the range values
						$temp = explode(" ", $where);
						//`colname` BETWEEN 'val1' AND 'val2'
						/*
							temp[1] = BETWEEN
							temp[3] = AND
						*/
						$tmpWhere = "`".$temp[0]."` BETWEEN '".$this->Purify($temp[2])."' AND '".$this->Purify($temp[4])."'";
						$SQL .= $tmpWhere;
					}
				}
			}//End where expressions
		}
		else
		{
      //This switch does not run if $drop is null
      //using "clear" as a dummy value can be help
      //to remember what is happening.
			switch($drop)
			{
				case'DATABASE':
				case 'database':
					//Drop a whole database
					$SQL = "DROP DATABASE ".$T_DB;
				break;
				
				case 'TRUNCATE':
				case 'truncate':
					//Clearing a table (diff way)
					$SQL = "TRUNCATE TABLE `".$CONFIG['DB_Prefix'].$T_DB."`";
				break;
				
        case 'DROP':
        case 'drop':
          //Drop the whole table
					$didDrop = true;
					$SQL = "DROP TABLE IF EXISTS `".$CONFIG['DB_Prefix'].$T_DB."`";
        break;
        
				default:
          //Clearing a table
          $SQL = "DELETE * FROM `".$CONFIG['DB_Prefix'].$T_DB."`";
			}
		}
		
		//Store SQL query in object place holder
		//for debugging references.
		$this->DB_SQL = $SQL;
		
		$Query = @mysql_query($SQL, $this->Linked);
		if($Query)
		{	
			$AfctRows = mysql_affected_rows($this->Linked);
			if($AfctRows > 0)
			{
				$this->LogError($this->error = $AfctRows.' rows affected');
				RETURN true;
			}
			else
			{
				//Return False unless this was a drop
				//query then it was successful
				if($didDrop)
				{
					RETURN true;
				}
				else
				{
					$this->LogError($this->error = $AfctRows.' rows affected');
					RETURN false;
				}
			}
		}
		else
		{
      $this->LogError("Drop Table Failure!?!");
			$this->LogError($this->error = mysql_error($this->Linked));
      $this->LogError($this->LastQuery());
			RETURN false;
		}
	}
	
  
	//%^^ MySQL RAW QUERY ^^%//
	/*
		This method is here for last resort purposes if one of the other methods in this class cannot
		get the job done you can resort to this method in order to get it done. However you must compile
		the SQL statements properly and clean the data before sending it. It is recommeded to use the other
		methods in the class.
		
		ACCEPTS: raw SQL query
		RETURNS: ARRAY (Result, Affected Rows)
	*/
	public function RawQuery($SQL)
	{
		//Store SQL query in object place holder
		//for debugging references.
		$this->DB_SQL = $SQL;
		$Query = @mysql_query($SQL, $this->Linked);
		
		//Create the return array
		$RetArray = array($Query, mysql_affected_rows($this->Linked));
		
		//Populate errors
		$this->error = mysql_error($this->Linked);
		
		RETURN $RetArray;
	}
	
  
	//%^^ MySQL Disconnect Method ^^%//
	/*
		RETURNS: true/false
	*/
	public function Sever()
	{
		//Purge Stale Errors
		$this->error = null;
		
		//Are we connected?
		if($this->Linked !== false)
		{	
			//Disconnect
			$Severed = @mysql_close($this->Linked);// or die($this->error = mysql_error());
			if($Severed)
			{
				//Link Severed-
				$this->Linked = false;
				return true;
			}
			else
			{
				$this->error = mysql_error();
				return false;
			}
		}
	}//END Sever
	
  
	//#^^ Input Cleaning Function ^^#//
	/*
		Cleans raw input, if link id is supplied the data will be
		prepped for DB entry, if Save option is set to true HTML will
		be converted rather than removed.
		
		ACCEPTS: [VALUE] = string, [LINK] = db connection id, [SAVE] = bool
		RETURNS: Clean user input
	*/
	private function Purify($value)
	{
		//TempDataHolder
		$tempvar = null;
		
		//Convert tags to ANCII CODE
		$tempvar = htmlspecialchars($value, ENT_QUOTES);
		$value = $tempvar;
			
		//Strip anything remaining
		$tempvar = strip_tags($value);
		$value = $tempvar;
	
		//PHP manual highly recommends this function
		//for any value being entered into a database
		$tempvar = mysql_real_escape_string($value, $this->Linked);
		$value = $tempvar;	
		
		RETURN $value;
	}

  
	/*
	private function encrypt($string) 
	{ 
		$encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5(EIVS), $string, MCRYPT_MODE_CBC, md5(md5(EIVS))));
		return $encrypted;
	}
	*/
	
	private function Decrypt($encrypted) 
	{ 
		$decrypted = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5(EIVS), base64_decode($encrypted), MCRYPT_MODE_CBC, md5(md5(EIVS))), "\0");
		RETURN $decrypted;
	}
  
}//END dbAccess
?>