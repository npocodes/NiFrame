<?php
/*
  Project:  NiFrame
  
  Author:   Nathan Poole - github/npocodes
           
  Date:     Jan 2014
  
  Updated:  A
  
  File:     This file is an error interface file use this class as a parent 
            class to provide your class with error handling methods
*/
/*
	[ Template Class ] [5/30/2012]
	CORTEX by Native Inventions < www.NativeInventions.com >
	Author: Nathan < nathan@nativeinventions.com >
	
	This class allows easy handling of all HTML files. Allows use of multiple HTML files or a single HTML file. 
  Optionally you can even Set variables and plug those values into the template, as well as set conditional 
  HTML statements. Please read method	comments below for further details.
	
	[ !! This Class Utilizes a configuration file !! ]
*/
//require_once('inc/classes/class.error.php');

class template extends error {
	
	private $stylName;  //The name of the style used(no typo!)
  private $headPath;  //Hold the path to HTML head/foot files
	private $htmlPath;  //Holds the path to HTML files
	private $fileList;  //List of files used for the full HTML
	private $varList;   //List of variables used in the files
	private $condList;  //List of HTML conditions	
	private $HTML = ""; //Holds the compiled HTML
	
	//Constructor
	/*
		[ACCEPTS] - String (Name of the style to use)
		[RETURNS] - Void
	*/
	function __construct($stylName = 'myStyle')
	{
		//set stylName
		$this->stylName = $stylName;
		
    //Set default HTML header/footer path
    //and the default HTML path
    $this->headPath = $this->htmlPath = "style/".$stylName."/html/";
	}
	//END CONSTRUCTOR
  
  
	//%%^^ HTML PATH [SET] Method^^%%//
	/*
		Accepts a path modifier to html files
		Example usage:
			$tpl->SetPath('acp');
				will give us a total path of:	'style/myStyle/html/acp/'
        
		(ALL html must be within the style's HTML dir)
	*/
	public function SetPath($path, $rootHead = false)
	{
		$this->htmlPath = "style/".$this->stylName."/html/".$path."/";
    if(!($rootHead)){ $this->headPath = $this->htmlPath; }
	}
	

	//%%^^ STYLES [GET] Method ^^%%//
  /*
    Retrieves a list of viable style names
    ACCEPTS: void
    RETURNS: array of style names | false on failure
  */
  public function GetStyles()
  {
    //Compile a list of available styles
    $styleList = array();
    $dirList = scandir('style');
    foreach($dirList as $key => $d)
    {
      //Skip dot dirs
      if($d != '.' && $d != '..')
      {
        //Verify its a directory
        if(is_dir('style/'.$d))
        {
          //Scan this dir and verify it has a style.css file
          $dirList2 = scandir('style/'.$d);
          foreach($dirList2 as $key2 => $f)
          {
            //Skip dot dirs
            if($f != '.' && $f != '..')
            {
              //Verify the file is a file
              if(is_file('style/'.$d.'/'.$f))
              {
                //Check if the name is "style.css"
                //we will also allow this to be named after the style
                if($f == $d.'.css' || $f == 'style.css')
                {
                  //$d was determined to be a style
                  //add it to the style name list
                  $styleList[] = $d;

                }//Skip, can't verify css file
              }//Skip Dir
            }//Skip Dots
          }//END style.css search Loop
        }
      }
    }//END Style search
    
    //Success!?!
    RETURN (empty($styleList)) ? false : $styleList;
  }
  
  
	//%%^^ SetFiles Function ^^%%//
	/*
		Sets file names to be used during html compilation. If headless is true then
    no "header.html" or "footer.html" files will be used in the compilation
	
		[ACCEPTS] - Array | String (single value)
              - TRUE | FALSE
              
		[RETURNS] - TRUE | FALSE
	*/
	public function SetFiles($fileArray, $headless = false)
	{
		if(is_array($fileArray))
		{
			//%^^ Using multiple files
			$tempList = array();
			$FileNum = count($fileArray);
			for($i = 0; $i < $FileNum; $i++)
			{
				$tempList[$i] = $this->htmlPath.$fileArray[$i];
			}
			
			//Check if using header and footer
			if($headless == true)
			{
				//Set the list
				$this->fileList = $tempList;
				
				RETURN true;
			}
			else
			{
				//Set header/footer files
				$this->fileList[] = $this->headPath."header.html";
				foreach($tempList as $FileName)
				{
					$this->fileList[] = $FileName;
				}
				$this->fileList[] = $this->headPath."footer.html";
				
				RETURN true;
			}
		}
		else if($fileArray != null)
		{
			//%^^ Single File sent
			
			//Check if using header and footer
			if($headless === true)
			{
				$this->fileList = $this->htmlPath.$fileArray;
				
				RETURN true;
			}
			else
			{
				$this->fileList = array($this->headPath."header.html", $this->htmlPath.$fileArray, $this->headPath."footer.html");
				
				RETURN true;
			}
		}
		else
		{
			$this->error = "File Array is empty.";
			RETURN false;
		}
	}//END SetFiles
	
  
	//%%^^ Set HTML Variables Function ^^%%//
	/*
		Sets HTML var/value pairs to be injected
    into the HTML during compilation
		
		[ACCEPTS] - Keyed Array('VarName' => 'VarValue')
		[RETURNS] - TRUE | FALSE
	*/
	public function SetVars($VarList)
	{		
		//Check if VarList is an array
		if(is_array($VarList))
		{
			//Count the array
			$VarNum = count($VarList);
			
			//Get key values
			$keys = array_keys($VarList);
			
			//Loop Through keys and make sure they 
			//are variable names. ex = '/EXPRESSION/';
			$Pattern = '/^[_A-Za-z]{1}[_a-zA-Z0-9]{1,}$/';
			for($i=0; $i<$VarNum; $i++)
			{
				$Match = preg_match($Pattern, $keys[$i]);
				if(!$Match)
				{
					//One of the keys is not a variable
					$this->error = "Invalid Key Found!: ".$keys[$i]."<br>Keys must be valid variable names";
					RETURN false;
				}else{/*echo("MATCH");*/}
			}
			//The Keys look like identifiers
			//Set the VarList
			$this->VarList = $VarList;
			
			RETURN true;
		}
		else
		{
			//Single Value
			//Value MUST be keyed array
			$this->error = "Variable List must be a keyed array!.";
			RETURN false;
		}
	}

  
	//%%^^ Set Conditions Function ^^%%//
	/*
		Sets HTML Condition Tags to be handled
		during the template compilation.
		
		!!WARNING!! The condition name supplied	will be REMOVED from the HTML compilation
		as default behaviour. See the FilterConditions function for to find out how to
    create your conditions.
		
		[ACCEPTS] - Array | String (single value)
		[RETURNS] - TRUE | FALSE
	*/
	public function SetConditions($condList)
	{
		//Check for empty values
		if(!(empty($condList)))
		{
			//Check for array
			if(is_array($condList))
			{
				//-Multi Value
				$CondNum = count($condList);
				
				for($i=0; $i<$CondNum; $i++)
				{
					$this->condList[$i] = $condList[$i];
				}
				
				//Make sure the values are there
				if(!(empty($this->condList)))
				{
					if(is_array($this->condList))
					{
						RETURN true;
					}
					else
					{
						$this->error = "Failed to set conditions array";
						RETURN false;
					}
				}
				else
				{
					$this->error = "Failed to set conditions";
					RETURN false;
				}
			}
			else
			{
				//-Single Value
				$this->condList = $condList;
				
				//Make sure the value is there
				if(empty($this->condList))
				{
					$this->error = "Failed to set condition";
					RETURN false;
				}
				else
				{
					RETURN true;
				}
			}
		}
		else
		{
			//Empty Value Found
			//No Conditions Set
			$this->error = "No conditions set";
			RETURN true;
		}
	}
	
  
	//%%^^ Function to Compile html ^^%%//
	/*
		Requires that FileList, Varlist, Conditions be set and then compiles the result. 
    You may opt to return or display the compiled html. The default behaviour is to
    display.
		
		[ACCEPTS] - Bool (TRUE | FALSE)
    
		[RETURNS] - TRUE | FALSE
              - HTML (if returning)
	*/
	public function Compile($RETURNING = false)
	{
		//Verify the HTML path was set before we start anything
		if((!(isset($this->htmlPath))) || empty($this->htmlPath))
		{
			$this->error = "No Style Path Found!";
			RETURN false;
		}
		
		//Purge Stale Errors
		$this->error = '';
		
		//Check if file is array
		if(is_array($this->fileList))
		{	
			//## Multiple Files
			
			//Count the number of files
			$FileNum = count($this->fileList);
			
			//Loop through files
			for($i = 0; $i < $FileNum; $i++)
			{	
				//Does the file exist?
				if(file_exists($this->fileList[$i]))
				{
					//Purge the HTML file
					$purgedHTML = $this->Purge(file_get_contents($this->fileList[$i]));
					
          //Append the purgedHTML to the HTML string
					$this->HTML .= $purgedHTML;

					//Makes sure the html files are not blank
					if($this->HTML == '')
					{
						$this->error = "Failed to purge HTML files: ".$this->fileList[$i];
						RETURN false;
					}
				}
				else
				{
					$this->error = $this->fileList[$i]." : Does not exist";
					RETURN false;
				}
			}
			
			//RUN CONDITIONAL FILTER HERE!!!
			$FilteredHTML = $this->FilterConditions($this->HTML);
			$this->HTML = $FilteredHTML;
			
			//TEST HERE FOR EVAL PROBLEMS
			//(ALSO CHECK SAME PLACE IN SINGLE FILE CASE)
			//echo($this->HTML);
			//exit();
			
      //Just before we extract the variable and display/return
      //the HTML add in the end time variables for the PageLoadTime
      if(isset($_SESSION['Ni_start']))
      {
        $time = microtime();
        $time = explode(" ", $time);
        $time = $time[1] + $time[0];
        $finish = $time;
        $startTime = (isset($_SESSION['Ni_start'])) ? $_SESSION['Ni_start'] : $finish;
        $totaltime = ($finish - $startTime);
        $TimeRound = round($totaltime, 3);
        $this->VarList['PAGELOADTIME'] = "Page generated in $TimeRound seconds.";
      }else{ $this->VarList['PAGELOADTIME'] = ''; }
      
			//Extract Variables
			$extracted = extract($this->VarList, EXTR_SKIP);
			if(count($extracted) >= 1)
			{
				//Page Variables Extracted
				//Evaluate Remaining
				eval("\$HTML = \"$this->HTML\";");
				
				//Check if Returning/Displaying
				if($RETURNING)
				{
					RETURN $HTML;//Return Compiled HTML
				}
				else
				{
					echo($HTML);//Display the page
					RETURN true;
				}
			}
			else
			{
				$this->error = "Failed to extract VarList variables";
				RETURN false;
			}
		}
		else if($this->fileList != null)
		{	
			//## Single File

			//Does the file exists?
			if(file_exists($this->fileList))
			{
				//Purge HTML file
				/*
					[Stops use of rogue php code in HTML files]
				*/
				$purgedHTML = $this->Purge(file_get_contents($this->fileList));
				$this->HTML = $purgedHTML;
				
				//Makes sure the html file is not blank
				if(empty($this->HTML))
				{
					$this->error = "Failed to purge html file";
					RETURN false;
				}
			}
			else
			{
				$this->error = $this->fileList.": Does not exist";
				RETURN false;
			}
			
			//RUN CONDITIONAL FILTER HERE!!!
			$FilteredHTML = $this->FilterConditions($this->HTML);
			$this->HTML = $FilteredHTML;
			
			//TEST HERE FOR EVAL PROBLEMS
			//(ALSO CHECK THE MULTI FILE CASE)
			//echo($this->HTML);
			//exit();
			
      //Just before we extract the variable and display/return
      //the HTML add in the end time variables for the PageLoadTime
      if(isset($_SESSION['Ni_start']))
      {
        $time = microtime();
        $time = explode(" ", $time);
        $time = $time[1] + $time[0];
        $finish = $time;
        $startTime = (isset($_SESSION['Ni_start'])) ? $_SESSION['Ni_start'] : $finish;
        $totaltime = ($finish - $startTime);
        $TimeRound = round($totaltime, 3);
        $this->VarList['PAGELOADTIME'] = "Page generated in $TimeRound seconds.";
      }else{ $this->VarList['PAGELOADTIME'] = ''; }
      
			//Extract Variables
			$extracted = extract($this->VarList, EXTR_SKIP);
			if(count($extracted) >= 1)
			{
				//Page Variables Extracted
				//Evaluate Remaining
        $HTML = '';
				eval("\$HTML = \"$this->HTML\";");
				
				//Check if Returning/Displaying
				if($RETURNING)
				{
					RETURN $HTML;//Return Compiled HTML
				}
				else
				{
					echo($HTML);//Display the page
				}
			}
			else
			{
				$this->error = "Failed to extract VarList variables";
				RETURN false;
			}
		}
		else
		{
			$this->error = "Unable to find FileList";
			RETURN false;
		}
	}//END Compile
	
  
	//##^^ Purge Function ^^##//
	/*
	#	This function handles the purging of php code and other misc symbols.
	#	It also activates the HTML variables so they work.
  
    [ACCEPTS] - String of HTML
    [RETURNS] - Purged HTML String || False
	*/
	final private function Purge($string)
	{
		//%^ BEGIN THE PURGE!
    
    //First before anything we need to get all of the content
    //so we need to read the string and replace any content 
    //include markers with their associated content so that
    //all the content is properly purged.
    $patt = '/\{\&[a-zA-Z][a-zA-Z0-9\.\_\-]{2,}\}/';// {&SOMEFILENAME}
    $cIncludes = preg_match_all($patt, $string, $matches);
    if($cIncludes != false)
    {
      //Matches have been located!!   
      //Clean up markers and get file names
      foreach($matches[0] as $key => $incMarker)
      {
        //Strip off the marker bits
        $bits = array('{&', '}');
        $includeFile = rtrim(str_replace($bits, '', $incMarker));
        
        $incData = file_get_contents('content/'.$includeFile);
        if($incData !== false)
        {
          //Replace the marker we found with its associated content
          $string = str_replace($incMarker, $incData, $string);
        }
      }//END INCLUDE LOOP
    }
    
    //% Create the CONFIG constants and inject them into the HTML
		$CONFIG = @parse_ini_file(CONFIG_PATH, true);//processing in sections
		if($CONFIG != false)
		{ 
			//Initialize Constant Needle List
			$cNeedles = array();
			$cFixes = array();
			
			//Get the section names
			$cKeys = array_keys($CONFIG);
			
			//Foreach section
			$i=0;//keeps track of needle/fix index
			foreach($cKeys as $Section)
			{
				//Make sure its not a blacklist section
				if($Section != "Database" && $Section != "reCAPTCHA")
				{
          //For each section
					foreach($CONFIG[$Section] as $varName => $value)
					{
						//Fix CONFIG Template variables
						$cNeedles[$i] = '{%'.$varName.'}';
						$cFixes[$i] = $value;
						$i++;//Increment index counter
					}
				}
			}

			//Now make the swaps
			$string = str_replace($cNeedles, $cFixes, $string);
		}
    
		//Set Needle Array
		//##WARNING - Add new needles/fixes to the beginning of the arrays
		//##WARNING - The order of these needles is highly volatile be very careful
		//			and verify your changes thoroughly
		$Needles = array('"', '<?', '?>', '{$', '$', '{%%', '{%');//
		
		//Set Replacement Array
		//##WARNING - You must match the order of fixes with the order of needles!!
		$Fixes = array('\"', '&#60;&#63;', '&#63;&#62;', '{&#36;', '&#36;', '{$$', '{$');
		
		$FxdStr = str_replace($Needles, $Fixes, $string);//NEEDLE FIX HAYSTACK
		
		if($FxdStr != '')
		{
			//Return the Purged String
			RETURN $FxdStr;
			
		}else{RETURN false;}
	}//END Purge
	
  
	//##^^ FilterConditions Function ^^##//
	/*
		This Function handles the beef work for HTML conditioning.
    A [default] condition is removed from the HTML and can be set using the following syntax:
        in php:   $CONDITIONS[] = CONDITION_NAME; this adds the condition to the list    
        in HTML:  <!-- CONDITION_NAME //--> some HTML code to be removed <!-- FIN CONDITION_NAME //-->
                or                  
                  <!-- CONDITION_NAME //-->
                    some HTML code to be removed
                  <!-- FIN CONDITION_NAME //-->
    
    [Modified] Conditions are the opposite of the default in that they will repeat
    the HTML code rather than remove it. The modified conditions can be set using
    the following syntax:
    
    Repeat Modifier [~] will repeat the conditioned HTML and replace
    the 1ST ONLY occurrence of %N each iteration with the repeat count, (0-n).
    ALL occurrences of %I will be replaced with the repeat count.
    
      in php: $CONDITIONS[] = ~CONDITION_NAME#  , where # is an actual positive number
      in HTML: <!-- ~CONDITION_NAME //--> This is the %Nth iteration of this HTML <!-- FIN ~CONDITION_NAME //-->
      
    Repeat Modifier [!] will repeat the conditioned HTML and replace ALL occurrences of %N
      in php: $CONDITIONS[] = ~CONDITION_NAME#  , where # is an actual positive number
      in HTML: <!-- !CONDITION_NAME //--> some repeated HTML code <!-- FIN !CONDITION_NAME //-->
      
    -NOTE- Both these modifiers can be combined to facilitate nested looping.. 
           Below is a simple example of this usage but if your clever you can
           think up a number of different combinations to suit your needs.
            Ex (Simple):
            <!-- ~Main_Loop //-->
              <!-- !Nested_Loop //-->
                {%VAR_NAME[%N][%N]}
                {%VAR2_NAME[%I][%N]}
              <!-- FIN !Nested_Loop //-->
            <!-- FIN ~Main_Loop //-->

    [ACCEPTS] - String of HTML
              - Array|String of conditions
    
    [RETURNS] - String of Filtered HTML || Unaltered String of HTML
	*/
	final public function FilterConditions($string, $conds = null)
	{	
		//tempHTML var
		$tempHTML = '';
		
    //check for conds argument
    $this->condList = ($conds == null) ? $this->condList : $conds;
    
		//!!ADD NEW CONDITION MODIFIER SYMBOLS HERE
		//Modification Symbol List
		$modList = array(
		
			'~',	/*	Repeat Modifier: 
							-Description: This modifier will repeat the condition changing only the first occurence of %N
							-syntax: ~ [CondName] [NumTimesToRepeat] 
							-example: ~MyCond42	(php)
							-example: ~MyCond	(HTML)
							This will repeat the condition the specified number of times in this case 42 times.
							consider: ($N=0; $N<42; $N++) so the output of numbers to %N is 0-41
							
							This Repeat modifier also acts as a combination of the two. Using %I html variable, so that
							all occurrences of %I will be changed. However you must first use %N.
						
						*Warning* - The repeat number is not expected in the HTML and would cause the condition to be removed.
						This is not a bug, its the reason for this functionality the HTML designer doesn't need to know how many
						times this will be repeated.. just that it will be repeated the number of times required for the functionality
						given by the corresponding processing file.
					*/
			
			'!',	/* Repeat Modifier2:
							-Description: This modifier will repeat the condition changing all occurrences of %N
							-syntax: ! [CondName] [NumTimesToRepeat] 
							-example: !MyCond42	(php)
							-example: !MyCond	(HTML)
							This will repeat the condition the specified number of times in this case 42 times.
							consider: ($N=0; $N<42; $N++) so the output of numbers to %N is 0-41
						
						*Warning* - The repeat number is not expected in the HTML and may cause the condition to be removed.
						This is not a bug, its the reason for this functionality the HTML designer doesn't need to know how many
						times this will be repeated.. just that it will be repeated the number of times required for the functionality
						given by the corresponding processing file.
					*/				
		////////////////////////////
		);//////////////////////////
		//Ends the mod list array.//
		////////////////////////////
		
		//Check for conditions to filter
		if(!(empty($this->condList)))
		{
			//Check for Multiple Conditions
			if(is_array($this->condList))
			{
				//%^^ Multiple Conditions found!!
				//print_r($this->condList);
				//exit();
				
				//Count how many
				$CondNum = count($this->condList);
				
				//Filter Each Condition
				for($i=0; $i < $CondNum; $i++)
				{	
					//Initialize Condition for filtering
					$COND = $this->condList[$i];
					
					//Check for modifiers
					$mcond = false;
					$xCOND = str_split($COND);
					
					//Check for and clean up Modified Conditions
					if(in_array($xCOND[0], $modList, true))
					{
						//echo("Condition Modifier Located!");
						//exit();
						
						$mcond = true;
						//%^^ This is a Mod Condition
						
						//!!ADD MODIFIER CLEAN UP CODE TO THIS SWITCH
						//Clean up the cond name
						switch($xCOND[0])
						{
							//Fall Through at first for the 2 repeats
							//this will allow us to use some of the same code 
							//for both of them as well as be able to separate it
							
							//Repeats
							case'~':
							case'!':
								//Looks at end of cond name for number of times to repeat
								$NumPat = '/[0-9]+$/s';
								if(preg_match($NumPat, $COND, $NumMatch))
								{
									$rNum = $NumMatch[0];
								}
								else
								{
									$rNum = 0;
								}
								
								//Pop the repeat number off the end
								//of the cond name replacing it with ''
								$cleanCOND = preg_replace($NumPat, '', $COND);
								
								//Now we need to trim off the whitespace left
								$COND = trim($cleanCOND);
								//echo($COND);
							break;
							
							default:
								//do nothing
								//The default behaviour removes the condition
						}
					}
					
					//The only thing we need to do before the code below happens
					//is manipulate the cond name if its a modified condition.
					//other wise we won't be able to find the tag in the string
					
					//Set Instances Flag
					$instanceExists = true;
					$instanceCount = 0;
          $RepeatedHTML = "";
					while($instanceExists)
					{	
						//Evaluate the pattern
						eval("\$Pattern = '/<!-- $COND \/\/-->.+?<!-- FIN $COND \/\/-->/s';");
						
						//Check if using modified condition
						if($mcond)
						{
							//!! ADD MODIFIER SPECIAL PROCESSING TO THIS SWITCH
							//Do something special for the specific mod condition
							switch($xCOND[0])
							{
								//Repeats
								//Please use this modifier as a reference when making new ones
								//to ensure your modifier works with the rest of this function
								case'~':
								case'!':
									//%^^ Generate replacement HTML for the given cond
									$Located = preg_match($Pattern, $string, $Match);
									if($Located)
									{
										//$Match[0] now holds the HTML we need to generate a 
										//replacement for this cond
										
										//Remove the COND tags from the match HTML
										//so that we don't keep searching for it while
										//checking for COND instances in the string
										eval("\$Pattern1 = '/<!-- $COND \/\/-->/s';");
										eval("\$Pattern2 = '/<!-- FIN $COND \/\/-->/s';");
										$cleanMatch = preg_replace($Pattern1, '', $Match[0]);
										$cleanMatch = preg_replace($Pattern2,'', $cleanMatch);
										//Clean up the match leaving tabs to keep html formatting
										$cleanMatch = trim($cleanMatch, "\t");
										
										//Create Replacement based off match found
										$RepeatedHTML = "";
										$tmpRepeated = "";
										for($N=0; $N<$rNum; $N++)
										{
											switch($xCOND[0])
											{	
												case'!':
													//Replace ALL occurences of %N with the value of the var N
													$RepeatedHTML .= str_replace('%N', $N, $cleanMatch);
												break;
												
												default:
													//Default repeat "~"
													
													//we do this incase HTML designers need a combo of the two repeats
													//we'll modify the match to do this
													//Replace ALL occurences of %I with the value of $N
													$tmpRepeated = str_replace('%I', $N, $cleanMatch);
													
													//Replace the first occurence of %N with the value of the var N
													$tmpRepeated = substr_replace($tmpRepeated, $N, strpos($tmpRepeated,'%N'), 2);
													
													//Attach the repeated code to the string
													$RepeatedHTML .= $tmpRepeated;
												break;
											}
											$RepeatedHTML .= PHP_EOL;//helps keep formatting	

										}//End Replacement Loop
									}
								break;//End REPEAT modifier
								
								default:
									//do nothing
							}
						}
						/////////////////////////////////////////
						//!! DO NOT MODIFY BEYOND THIS POINT !!//
						/////////////////////////////////////////
						
						//Check for special replacement from modifier
						$Replacer = ((!($mcond))) ? '' : $RepeatedHTML;
						
						//Search for the tag and replace it.
						$tempHTML = preg_replace($Pattern, $Replacer, $string, 1);
						
						//Compare the Strings
						//If the 1st string = the filtered string
						//Then no instance of the cond was found mark it so.
						if(strcmp($string, $tempHTML) == 0)
						{
							//The Strings match so no instances were found and replaced
							$instanceExists = false;
						}
						
						//FlippyFlopp the strings to run again w/changes
						$string = $tempHTML;
						
						$instanceCount++;
					}
				}
				
				RETURN $string;
			}
			else
			{
				//%^^ Single Condition found!!
				
				//Initialize Condition
				$COND = $this->condList;
				
				//Check for modifiers
				$mcond = false;
				$xCOND = str_split($COND);
				
				//Check for and clean up Modified Conditions
				if(in_array($xCOND[0], $modList, true))
				{
					//echo("Condition Modifier Located!");
					//exit();
					
					$mcond = true;
					//%^^ This is a Mod Condition
					//Clean up the cond name
					switch($xCOND[0])
					{
						//FallThrough at first for the row/column repeats
						//this will allow us to use some of the same code 
						//for both of them as well as be able to seperate it
						
						//Repeats
						case'~':
						case'!':
							//Looks at end of cond name for number of times to repeat
							$NumPat = '/[0-9]+?$/s';
							if(preg_match($NumPat, $COND, $NumMatch))
							{
								$rNum = $NumMatch[0];
							}
							else
							{
								$rNum = 0;
							}
							
							//Pop the repeat number off the end
							//of the cond name replacing it with ''
							$cleanCOND = substr_replace($COND, '', strpos($COND, $rNum), strlen($rNum));
							//Now we need to trim off the whitespace left
							$COND = trim($cleanCOND);
						break;
						
						default:
							//do nothing
							//The default behavior removes the condition
					}
				}
				
				//The only thing we need to do before the code below happens
				//is manipulate the cond name if its a modified condition.
				//other wise we won't be able to find the tag in the string
				
				//Set Instances Flag
				$instanceExists = true;
				$instanceCount = 0;
				while($instanceExists)
				{	
					eval("\$Pattern = '/<!-- $COND \/\/-->.+?<!-- FIN $COND \/\/-->/s';");
					//echo("Evaluated the Pattern");
					//exit();
					
					//Check if using modified condition
					if($mcond)
					{
						//!! ADD MODIFIER SPECIAL PROCESSING TO THIS SWITCH
						//Do something special for the specific mod condition
						switch($xCOND[0])
						{
							//Repeats
							//Please use this modifier as a reference when making new ones
							//to ensure your modifier works with the rest of this function
							case'~':
							case'!':
								//%^^ Generate replacement HTML for the given cond
								$Located = preg_match($Pattern, $string, $Match);
								if($Located)
								{
									//$Match[0] now holds the HTML we need to generate a 
									//replacement for this cond
									
									//Remove the COND tags from the match HTML
									//so that we don't keep searching for it while
									//checking for COND instances in the string
									eval("\$Pattern1 = '/<!-- $COND \/\/-->/s';");
									eval("\$Pattern2 = '/<!-- FIN $COND \/\/-->/s';");
									$cleanMatch = preg_replace($Pattern1, '', $Match[0]);
									$cleanMatch = preg_replace($Pattern2,'', $cleanMatch);
									//Clean up the match leaving tabs to keep html formatting
									$cleanMatch = trim($cleanMatch, "\t");
									
									//Create Replacement based off match found
									$RepeatedHTML = "";
									for($N=0; $N<$rNum; $N++)
									{
										switch($xCOND[0])
										{	
											case'!':
												//Replace ALL occurences of %N with the value of the var N
												$RepeatedHTML .= str_replace('%N', $N, $cleanMatch);
											break;
											
											default:
												//Replace the first occurence of %N with the value of the var N
												$RepeatedHTML .= substr_replace($cleanMatch, $N, strpos($cleanMatch,'%N'), 2);
											break;
										}
										$RepeatedHTML .= PHP_EOL;//helps keep formatting	
									}
								}
							break;//End REPEAT modifier
							
							default:
								//do nothing
						}
					}
					
					/////////////////////////////////////////
					//!! DO NOT MODIFY BEYOND THIS POINT !!//
					/////////////////////////////////////////
					
					//Check for special replacement from modifier
					$Replacer = ((!($mcond))) ? '' : $RepeatedHTML;
					
					//Search for the tag and replace it.
					$tempHTML = preg_replace($Pattern, $Replacer, $string, 1);
					
					//Compare the Strings
					//If the 1st string = the filtered string
					//Then no instance of the cond was found mark it so.
					if(strcmp($string, $tempHTML) == 0)
					{
						//The Strings match so no instances were found and replaced
						$instanceExists = false;
					}
					
					//FlippyFlopp the strings to run again w/changes
					$string = $tempHTML;
					
					$instanceCount++;
				}
				
				RETURN $string;
			}
		}
		else
		{
			//Empty Conditions
			//return "Unable to find conditions!";
			RETURN $string;
		}	
	}//END FilterConditions
	
}//END Template CLASS
?>