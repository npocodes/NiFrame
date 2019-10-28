<?php
/*
  Purpose:  Mod Package Driver File - NiFrame
  
  FILE:     The Mod Package driver file handles mod specific use-case
            scenarios (modes) that require Administrative permissions such as: 
            installing a new mod package, removing packages, viewing packages, etc...
            
  Author:   Nathan Poole - github/npocodes
  Date:     February 2015
*/
//Include the common file
require_once('common.php');

//Require User Login
if(!($_USER->UnPack())){ header("location: login.php"); die(); }

//Require ACP Permission
if(!($_USER->Permitted('ACP') || $_USER->ID() == 1)){ header("location: login.php"); die(); }
  
//set the default HTML file to use
$T_FILE = 'message.html';

//set the default redirect
$T_VAR['REDIRECT'] = '10;mod.php?search';

//Set default message
$T_VAR['MSG'] = '';

//////////////////////////////
//## Package [View] Mode? ##//
//////////////////////////////
/*
  Should list the details of the mod packages 
  install file, also see Install Mode:
  
    Name of MOD
    Name of Author/Company
    Version #
    Installed/Not Installed
    #of DB Tables
    .. Configuration Entries
    .. Class Files
    .. Driver Files
    .. HTML Files
    .. Menu Items
    .. CSS Files?
    .. JS Files?
    .. Content Files?
*/


////////////////////////////////
//## Package [Install] Mode ##//
////////////////////////////////
/*
  Given the name of a Mod Package, this mode will attempt to
  locate the installer ".xml" file for the package and from it
  install the following items (if stated):
  
    Database Information (requires an ".sql" file containing queries [1per line])
    Configuration Data
    (Constants?)
    Class Files
    Driver Files
    HTML Files (Including sub directories)
    Menu Items
    CSS Files
    JavaScript Files
    Content Files
*/
if(isset($_INPUT['install']))
{
  //Verify a package has been selected for installation.
  if(isset($_INPUT['name']))
  {
    $T_VAR['PAGE_NAME'] = 'Mod Installer';
    
    $package = $_INPUT['name'];
    
    //Attempt to write package tables to DB, if any...
    //First check for an SQL file containing queries
    $sqlPassed = true;//In case no SQL exists
    if(file_exists('mods/'.$package.'/'.$package.'.sql'))
    {
      $sqlPassed = false;
      //Read the package SQL file into an array. Each line of
      //the SQL file should be a single SQL query, so that $queryList
      //results in an array with each element an SQL query
      $queryList = file('mods/'.$package.'/'.$package.'.sql');
      
      //create a dbAccess object so we can run the queries
      $DB = new dbAccess();
      if($DB->Link())
      {
        $fail = array();
        $error = array();
        $f = 0;
        foreach($queryList as $query)
        {
          //We need to audit the queries in order to locate any that might be creating new tables. When 
          //found we need to locate the table name within the query and attach the systems table prefix,
          //if we don't do this, the system will not see the tables and the mod won't work.
          $newQuery = $query;
          if(!(stripos($query, 'CREATE TABLE') === false) || !(stripos($query, 'DROP TABLE') === false))
          {
            //Found a create table or drop table query... update the query by
            //adding the system table prefix to the table name as well as the mod package name,
            //so that mods don't accidently overwrite system data. (nif_modName_tableName)
            $qPatt = '/`.+?`/s';
            preg_match($qPatt, $query, $qMatch);
            $packagePrefix = $package.'_';
            $newTblName = '`'.$CONFIG['DB_Prefix'].$packagePrefix.trim(str_replace('`', '', $qMatch[0])).'`';
            $newQuery = str_replace($qMatch[0], $newTblName, $query);
          }
          //Run the query
          $result = $DB->RawQuery($newQuery);
          $fail[$f] = ($result[0] === false) ? true : false;
          $error[$f] = ($result[0] === false) ? $DB->Error() : '';
          $f++;
        }

        //Check for failures
        if(in_array('true', $fail))
        {
          for($i=0; $i < $f; $i++)
          {
            $x = $i+1;
            if($fail[$i] == true)
            {
              $T_VAR['MSG'] .= 'Query #'.$x.' has failed: '.$error[$i].'<br>'.PHP_EOL;
            }
            else
            {
              $T_VAR['MSG'] .= 'Query #'.$x.' was successful!<br>'.PHP_EOL;
            }
          }
        }else{ $sqlPassed = true; }
      }else{ $T_VAR['MSG'] = 'Database Link Failure!<br>'.PHP_EOL; }
    }//end sql check

    //Verify sql check success
    if($sqlPassed)
    {
      //Attempt to load the package install file
      $modXML = simplexml_load_file('mods/'.$package.'/'.$package.'.xml');
      if(!($modXML === false))
      {
        //Package Install File Loaded...
        
        //Attempt to write config data to the config file, if any exist
        $configPassed = true;//In case no config data exists or no problems occur
        if(isset($modXML->config) && !(empty($modXML->config)))
        {
          //get the main config file contents
          $mainConfig = file_get_contents('inc/config.ini');
          
          //Get the configuration data from the XML file
          $xmlConfig = $modXML->config;
          
          //Begin formatting config variables/values
          //to be added to the configuration file
          $conString =';//### '.$package.' TABLE & COLUMNS ###//
['.$package.']'.PHP_EOL;

          //Loop through each combo and add it to the string
          $c=0;//loop counter
          foreach($xmlConfig as $configV)
          {
            //Empty configuration names and values are not 
            //allowed, config is a failure if this happens
            if($configV == '' || $configV['name'] == ''){ $configPassed = false; }
            
            //verify the config won't overwrite anything by
            //searching the config global keys for matches
            //typecast $configV['name'] to string (avoids warnings)
            if(!(array_key_exists((string)$configV['name'], $CONFIG)))
            {
              //Add extra format spacing if we hit more than one table
              if($c > 0 && $configV['type'] == 'table'){ $conString .= ';'.PHP_EOL; }
              
              //Create comment if none exists, and prefix table names with packageName
              //this is to ensure that system and other mod DB information is kept safe
              $tComment = (empty($configV['comment'])) ? $package.' '.$configV['name'] : $configV['comment'];
              $tValue = ($configV['type'] == 'table') ? $package.'_'.$configV : $configV;
              
              //Add Line Break before next element, but
              //only if its not the very first element.
              if($c > 0){ $conString .= ';'.PHP_EOL; }
              
              //Add The Configuration Variable/Value Pair to the main Configuration String
              $conString .= ';//'.$tComment.PHP_EOL.$configV['name'].' = "'.$tValue.'"'.PHP_EOL;
              
              $c++;//only count when we add an element
            }
          }//End mod config. element loop

          //Finish off the main Configuration string, adding an 
          //"End PackageName" marker for later removal and adding
          //back in the end of config marker for the next mod
          $conString .= ';//End '.$package.PHP_EOL.';'.PHP_EOL.';'.PHP_EOL.';END of Configuration';
          
          //Finally we need to add the mod's newly formatted
          //configuration data to the system configuration file
          //make sure we didn't have a failure...
          if($configPassed)
          {
            $newMainConfig = str_replace(';END of Configuration', $conString, $mainConfig);
            file_put_contents('inc/config.ini', $newMainConfig);
          }
          
        }//end config check

        //Verify config check passed
        if($configPassed)
        {
          //Attempt to copy the mod package files to the proper locations
          //CONSTANTS!!(add)

          //Classes
          if(isset($modXML->classf) && !(empty($modXML->classf)))
          {
            foreach($modXML->classf as $classf)
            {
              //Don't allow mods to overwrite system classes...
              $safetyFilter = array(
                'class.attr.php',
                'class.dbAccess.php',
                'class.error.php',
                'class.template.php',
                'class.user.php',
                '.htaccess',
                'index.html'
              );
              
              if(!(in_array($classf, $safetyFilter)))
              {
                //All is well, do the copy!
                $tmpFile = file_get_contents('mods/'.$package.'/'.$classf);
                file_put_contents('inc/classes/'.$classf, $tmpFile);
                $tmpFile = '';                
              }
            }
          }//else no classes found

          //Drivers
          if(isset($modXML->driver) && !(empty($modXML->driver)))
          {
            //Don't allow mods to overwrite system drivers...
            $safetyFilter = array('common.php','index.php','login.php','mod.php','sysA.php','user.php','userA.php');
            foreach($modXML->driver as $driver)
            {
              if(!(in_array($driver, $safetyFilter)))
              {
                $tmpFile = file_get_contents('mods/'.$package.'/'.$driver);
                file_put_contents($driver, $tmpFile);
                $tmpFile = '';                
              }
            }
          }//else no drivers found
          
          //HTML Files (Should we add these files to all styles we can find??)
          //(Or would it be better to some how make mod style files independent and can/should we?)
          if(isset($modXML->file) && !(empty($modXML->file)))
          {
            //Don't allow mods to overwrite crucial system html files (we can't/won't protect them all...)
            $safetyFilter = array('header.html', 'footer.html', 'index.html', 'login.html', 'register.html', 'message.html');
            foreach($modXML->file as $file)
            {
              if(!(in_array($file, $safetyFilter)))
              {
                $tmpFile = file_get_contents('mods/'.$package.'/'.$file);
                if(isset($file['type']) && $file['type'] != '')
                {
                  file_put_contents('style/'.STYLE.'/html/'.$file['type'].'/'.$file, $tmpFile);
                }
                else
                {
                  file_put_contents('style/'.STYLE.'/html/'.$file, $tmpFile);
                }
                //Clear the tmpFile
                $tmpFile = '';                
              }
            }
          }//else no HTML files

          //CSS Files
          if(isset($modXML->sheet) && !(empty($modXML->sheet)))
          {
            //Retrieve the main style file "style.css"
            //as array where each array element is a line of the file
            $styleFile = file_get_contents('style/'.STYLE.'/style.css');
            $importString = '';
            foreach($modXML->sheet as $sheet)
            {
              //Format the new import statement and append to import list string
              $importString .= "@import url('sheets/".$sheet."');".PHP_EOL;
              
              //Copy the mod CSS file to the current style's CS sheet dir
              $sheetC = file_get_contents('mods/'.$package.'/'.$sheet);
              file_put_contents('style/'.STYLE.'/sheets/'.$sheet, $sheetC);
            }
            //Finish off the import string by add back in the comment marker
            //which also marks the end of the import list.
            $importString .= "/*";
            
            //Add the sheet import statement to the main style file
            $newStyleFile = str_replace('/*', $importString, $styleFile);
            file_put_contents('style/'.STYLE.'/style.css', $newStyleFile);   
          }//else no sheets found
      
          //JavaScript Files
          if(isset($modXML->jsFile) && !(empty($modXML->jsFile)))
          {
            foreach($modXML->jsFile as $jsFile)
            {
              $tmpFile = file_get_contents('mods/'.$package.'/'.$jsFile);
              file_put_contents('inc/js/'.$jsFile, $tmpFile);
              $tmpFile = '';
            }
          }//else no jsFiles found
            
          //Content Files
          if(isset($modXML->content) && !(empty($modXML->content)))
          {
            //Don't allow mods to overwrite the menu or index files
            $safetyFilter = array('menu.html', 'index.html');
            foreach($modXML->content as $content)
            {
              if(!(in_array($content, $safetyFilter)))
              {
                $tmpFile = file_get_contents('mods/'.$package.'/'.$content);
                file_put_contents('content/'.$content, $tmpFile);
                $tmpFile = '';                
              }
            }
          }//else no contents found

          //Create Menu
          $menuPassed = true;//In case no menu items exist
          if(isset($modXML->menu))
          {
            //Locate place holder
            //Replace place holders with menu data
            //replace place holder with new data
            //append new place holder
            $menuPassed = false;
            
            //First retrieve the menu.html content file
            $menuHTML = file_get_contents('content/menu.html');
            
            //Use regular expression to locate the menu link place holder/s 
            $patt = '/<!-- LINK_TPL \/\/-->.+?<!-- FIN LINK_TPL \/\/-->/s';
            $match = array();
            if(preg_match($patt, $menuHTML, $match))
            {
              //Link Template found!
              
              //Look for a sub template
              $patt2 = '/<!-- SUB_LINK_TPL \/\/-->.+?<!-- FIN SUB_LINK_TPL \/\/-->/s';
              $match2 = array();
              if(preg_match($patt2, $match[0], $match2))
              {
                //Sub Link Template found!
                //Set the Main Label
                $needle = '{%MAIN_LABEL}';
                $replacer = (isset($modXML->menu->label)) ? $modXML->menu->label : $package;
                $modMenuHTML = str_replace($needle, $replacer, $match[0]);
                
                //Create the Sub-Menu
                $modSubMenuHTML = '';
                if(isset($modXML->menu->sub[0]))
                {
                  foreach($modXML->menu->sub as $sub)
                  {
                    $needles = array('{%SUB_LINK}', '{%SUB_LABEL}', '<!-- SUB_LINK_TPL //-->', '<!-- FIN SUB_LINK_TPL //-->');
                    $replacers = array($sub->link, $sub->label, '', '');
                    $modSubMenuHTML .= rtrim(str_replace($needles, $replacers, $match2[0])).PHP_EOL;
                  }
                }
                else
                {
                  //Single Sub menu
                  $needles = array('{%SUB_LINK}', '{%SUB_LABEL}');
                  $replacers = array($sub->link, $sub->label);
                  $modSubMenuHTML = rtrim(str_replace($needles, $replacers, $match2[0]));
                }
                
                //Now MERGE! the modMenuHTML with the modSubMenuHTML by
                //replacing the Sub Menu Template HTML with the modSubMenuHTML
                $newMenuHTML = preg_replace($patt2, $modSubMenuHTML, $modMenuHTML);
                
                //Remove the "LINK_TPL" tags from the HTML, and replace them
                //with new tags using the MOD Package's name (for removal)
                $needles = array('<!-- LINK_TPL //-->', '<!-- FIN LINK_TPL //-->');
                $replacers = array('<!-- '.$package.' //-->', '<!-- FIN '.$package.' //-->');
                $newMenuHTML = str_replace($needles, $replacers, $newMenuHTML);
                
                //Attach a new copy of the Menu Template HTML to the end of the
                //new Menu HTML, so this process can be done again later.
                $newMenuHTML = $newMenuHTML.$match[0];
                
                //Now replace the old Menu Template HTML from the main menu HTML
                //with the newly created menu HTML, and rewrite the menu content file
                //with the changes.
                $newMenuHTML = preg_replace($patt, $newMenuHTML, $menuHTML);
                file_put_contents('content/menu.html', $newMenuHTML);
                
                //Menu Success!
                $menuPassed = true;
                
              }//else, No Sub Link Template found...(Menu with no Links? Y?)
            }//else, No Menu Link Template found in the menu...
          }//End Menu Check
          
          //Verify Menu Check passed
          if($menuPassed)
          {
            //Create NiFrame.txt file in modPackage directory to signal that it
            //has been installed, write the current date to the file.
            $iData = $CURR_DATE.' '.$CURR_TIME.' '.$CURR_TZONE;
            file_put_contents('mods/'.$package.'/NiFrame.txt', $iData);
            if(file_exists('mods/'.$package.'/NiFrame.txt'))
            {
              //INSTALL FINISHED SUCCESSFULLY!!!
              $T_VAR['MSG'] = 'Mod Package: '.$package.' installed successfully!';
            }
          }else{ $T_VAR['MSG'] = 'Failed to install '.$package.' menu, no menu template!<br>'.PHP_EOL; }
        }else{ $T_VAR['MSG'] = 'Failed to write '.$package.' configuration data!<br>'.PHP_EOL; }
      }else{ $T_VAR['MSG'] = 'Failed to load '.$package.'.xml install file!<br>'.PHP_EOL; }
    }else{ $T_VAR['MSG'] .= 'Failed to write '.$package.' SQL data!<br>'.PHP_EOL; }
  }else{ $T_VAR['MSG'] = 'You must select a mod package to install!'; }
}//END INSTALL MODE


////////////////////////////////
//## Package [Update] Mode? ##//
////////////////////////////////


//////////////////////////////////////
//## Package [Remove/Delete] Mode ##//
//////////////////////////////////////
/*
  Removes all modifications made by an
  installed Mod Package. (Reverses Install)
*/
if(isset($_INPUT['remove']))
{
  //Verify a package has been selected for removal.
  if(isset($_INPUT['name']))
  {
    $T_VAR['PAGE_NAME'] = 'Mod Remover';
    
    $package = $_INPUT['name'];
    
    //First off locate the mod package install 
    //file ".xml" and attempt to load it.
    $modXML = simplexml_load_file('mods/'.$package.'/'.$package.'.xml');
    if(!($modXML === false))
    {
      //First check for and remove the menu items so the mod features 
      //can't be accessed. This ensures the mod is disabled even if 
      //complications in removal occur later in the removal process.
      if(isset($modXML->menu))
      {
        //Retrieve the menu.html content file
        $menuHTML = file_get_contents('content/menu.html');
        
        //Use regular expression to locate the menu items
        //then simply remove everything within and including
        //the markers named after the mod package
        $patt = '/<!-- '.$package.' \/\/-->.+?<!-- FIN '.$package.' \/\/-->/s';
        $newMenuHTML = preg_replace($patt, '', $menuHTML);
        
        //rewrite the Menu content file with changes
        file_put_contents('content/menu.html', $newMenuHTML);
      }
      
      //Remove HTML files
      if(isset($modXML->file))
      {
        foreach($modXML->file as $hFile)
        {
          if(isset($hFile['type']) && $hFile['type'] != '')
          {
            unlink('style/'.STYLE.'/html/'.$hFile['type'].'/'.$hFile);
          }
          else
          {
            unlink('style/'.STYLE.'/html/'.$hFile);
          }
        }
      }
      
      //Remove CSS files
      if(isset($modXML->sheet))
      {
        //Retrieve the main style file "style.css"
        //as array where each array element is a line of the file
        $styleFile = file('style/'.STYLE.'/style.css');
        
        foreach($modXML->sheet as $sheet)
        {
          //Remove import statement from main style file by
          //rewriting style file line by line and ignoring
          //the entire line matching the import statement. 
          //(this avoids lingering line spaces)
          $newStyleFile = array();
          foreach($styleFile as $line)
          {
            //@import url('sheets/item.css');
            if(trim($line) != "@import url('sheets/".$sheet."');")
            {
              $newStyleFile[] = $line;
            }
          }
          //Update the org styleFile data with the changes so
          //that we can do the same for the next css file, we will
          //then only have to rewrite the main style file once
          $styleFile = $newStyleFile;
          
          //Remove the style sheet
          unlink('style/'.STYLE.'/sheets/'.$sheet);
        }
        //Finally rewrite main style file with all changes
        file_put_contents('style/'.STYLE.'/style.css', $styleFile);
      }
      
      //Remove JS files
      if(isset($modXML->jsFile))
      {
        //Catch JS files that would kill system JS files and skip them.
        $safetyFilter = array('niveri.js');
        foreach($modXML->jsFile as $jsFile)
        {
          unlink('inc/js/'.$jsFile);
        }
      }

      //Remove content files
      if(isset($modXML->content))
      {
        //Catch content files that would kill system content files and skip them.
        $safetyFilter = array('menu.html', 'index.html');
        foreach($modXML->content as $conFile)
        {
          if(!(in_array($conFile, $safetyFilter)))
          {
            unlink('content/'.$conFile);
          }
        }
      }
      
      //Remove Drivers
      if(isset($modXML->driver))
      {
        //Catch drivers that would kill system drivers and skip them.
        $safetyFilter = array('common.php','index.php','login.php','mod.php','sysA.php','user.php','userA.php');
        foreach($modXML->driver as $driver)
        {
          if(!(in_array($driver, $safetyFilter)))
          {
            unlink($driver);
          }
        }
      }
      
      //Remove Classes
      if(isset($modXML->classf))
      {
        //Catch classes that would kill system classes and skip them.
        $safetyFilter = array('class.attr.php','class.dbAccess.php','class.error.php','class.template.php','class.user.php','.htaccess','index.html');
        foreach($modXML->classf as $classf)
        {
          if(!(in_array($classf, $safetyFilter)))
          {
            unlink('inc/classes/'.$classf);
          }
        }
      }

      //Remove Database Tables and/or Configuration Data
      $dbRemoved = true;//Incase there isn't any

      if(isset($modXML->config))
      {
        //Set dbRemoved to false now that we know the data exists
        $dbRemoved = false;
        
        //Read the configuration file in
        $sysIniFile = file_get_contents('inc/config.ini');
        
        //Create a needle to search for the configuration data
        //and replace it with two lines of ";" symbol
        $patt = '/\;'.PHP_EOL.'\;'.PHP_EOL.'\;\/\/\#\#\# '.$package.' TABLE \& COLUMNS \#\#\#\/\/.+?\;\/\/End '.$package.PHP_EOL.';'.PHP_EOL.';'.PHP_EOL.'/s';
        $newSysIniFile = preg_replace($patt, ';'.PHP_EOL.';'.PHP_EOL, $sysIniFile);

        //Create a backup of original ini file using a time stamp 
        //and the name of the mod package in the file 
        //(An automatic recovery may be created to use these)
        file_put_contents('inc/config-'.time().'-'.$package.'.bakup', $sysIniFile);
        
        //rewrite the system ini file with the changes
        file_put_contents('inc/config.ini', $newSysIniFile);
        
        //Look through the config combos for table types
        //use the table name values to remove the tables from the database
        $DB = new dbAccess();
        if($DB->Link())
        {
          $killFail = 0;
          foreach($modXML->config as $configV)
          {
            if($configV['type'] == 'table')
            {
              //Remove the table from the DB
              $tblName = $package.'_'.$configV;
              if(!($DB->Kill($tblName, null, "DROP")))
              {
                $killFail++;
              }
            }
          }
          $DB->Sever();
          if($killFail == 0)
          {
            //Successfully removed db information
            $dbRemoved = true;
            
          }else{ $T_VAR['MSG'] = 'Failed removing '.$package.' database!<br>'.PHP_EOL; }
        }else{ $T_VAR['MSG'] = 'Database Link Failure!<br>'.PHP_EOL; }
      }
      else
      {
        //Check for a .sql file then, just in case, and
        //remove the database information that way.
        if(file_exists('mods/'.$package.'/'.$package.'.sql'))
        {
          //Read file into array of lines, so that each line is a query
          $sqlFile = file('mods/'.$package.'/'.$package.'.sql');
          $DB = new dbAccess();
          if($DB->Link())
          {
            $killFail = 0;
            foreach($sqlFile as $query)
            {
              if(!(stripos($query, 'CREATE TABLE') === false) || !(stripos($query, 'DROP TABLE') === false))
              {
                //Found Table query, retrieve the table name
                //using a regular expression match where the 
                //first match found will be the table name.
                $qPatt = '/`.+?`/s';
                preg_match($qPatt, $query, $qMatch);
                $tblName = $package.'_'.trim(str_replace('`', '', $qMatch[0]));
                if(!($DB->Kill($tblName, null, "DROP")))
                {
                  $killFail++;
                }
              }
            }
            $DB->Sever();
            
            if($killFail == 0)
            {
              //Successfully removed db information
              $dbRemoved = true;
              
            }else{ $T_VAR['MSG'] = 'Failed removing '.$package.' database!<br>'.PHP_EOL; }
          }else{ $T_VAR['MSG'] = 'Database Link Failure!<br>'.PHP_EOL; }
        }//Else no DB info found, skip...
      }
      
      //Finish up here...
      if($dbRemoved)
      {
        //Retrieve the "installed" marker file contents
        $niFile = file_get_contents('mods/'.$package.'/NiFrame.txt');
        
        //Get Current removal Date/Time data
        $rData = $CURR_DATE.' '.$CURR_TIME.' '.$CURR_TZONE;
        
        //Add current date/time data to the niFile contents
        $bakNiFile = $niFile.PHP_EOL.$rData.PHP_EOL;
        
        //Create backup file to store install/remove date/time record
        file_put_contents('mods/'.$package.'/NiFrame-'.time().'.bakup', $bakNiFile);
        
        //Remove the old NiFile
        unlink('mods/'.$package.'/NiFrame.txt');
        
        //Successfully removed Mod Package
        $T_VAR['MSG'] = 'Mod Package '.$package.' removed successfully!<br>'.PHP_EOL;        
      }
      
    }else{ $T_VAR['MSG'] = 'Unable to locate '.$package.' installer file!'; }  
  }else{ $T_VAR['MSG'] = 'You must select a mod package to remove!'; }  
}


///////////////////////////////
//## Package [Search] Mode ##//
///////////////////////////////
/*
  Locates mod packages found within the system's "mods" directory
  by checking all directories within it for an ".xml" file matching
  the name of its parent directory. See example .xml file for further
  requirements..
  
  Example: 
    NiFrame/mods/YourMod/             << The directory for the mod
    NiFrame/mods/YourMod/YourMod.xml  << The mod's install file
*/
if(isset($_INPUT['search']))
{
  $T_VAR['PAGE_NAME'] = 'Mod Search';
  
  //Read mods dir and find all available mod packages
  //Determine if package is installed already
  //Display all available mod packages as removable or installable
  $T_VAR['MOD_LIST'] = '';
  
  //Get a list of all the items within the "mods" directory.
  //we will weed out "." and ".." as well.
  $weeds = array('.', '..');
  $itemList = array_diff(scandir('mods', 1), $weeds);
  
  //Verify we found some items
  if(!(empty($itemList)))
  {  
    //The $itemList could contain directories that are not mod packages
    //and it could contain random files, we need to find ONLY mod packages.
    //We can do this by searching within each directory found for
    //a mod packages install file "modName.xml".
    $iPackages = array();//Holds list of installed packages
    $packages = array();//Holds list of un-installed packages
    foreach($itemList as $item)
    {
      //Verify the item is a directory
      if(is_dir('mods/'.$item))
      {
        //Scan the directory for "modName.xml", and remove weeds
        $tmpList = array_diff(scandir('mods/'.$item), $weeds);
        if(in_array($item.'.xml', $tmpList))
        {
          //While we are here lets look to see if this mod package is installed..
          //Check the $tmpList for NiFrame.txt, its a file that just tells the system 
          //if the mod package has already been installed and contains minor information
          //regarding when it was installed etc... see install mode for more details
          if(in_array('NiFrame.txt', $tmpList))
          {
            //Mod Package installed!
            $iPackages[] = $item;
          }
          else
          {
            //Mod Package not installed
            $packages[] = $item;
          }
        }
      }
    }//End loop
    
    //Now we can combine our package lists into one.
    //Later we will use the separate lists to mark
    //the package installed or not
    $packageList = array_merge($iPackages, $packages);
    
    //Verify we have some packages
    if(!(empty($packageList)))
    {
      //Cycle through each package and grab the
      //information needed to generate the HTML
      //for each package.
      foreach($packageList as $package)
      {
        //Set the HTML file to use for the sub-templates
        $mFile = 'modRow.html';
        
        //Load the Mod Package install file ex: ('mods/modName/modName.xml')
        $modXML = simplexml_load_file('mods/'.$package.'/'.$package.'.xml');
        
        //Verify we were able to load the XML
        if(!($modXML === false))
        {
          
          //Get the details
          $mVars['MOD_NAME']      = $modXML->name;
          $mVars['MOD_VERSION']   = $modXML->version;
          $mVars['MOD_AUTHOR']    = "<a href='".$modXML->author->link."'>".$modXML->author->name."</a>";
          $mVars['MOD_BRIEF']     = $modXML->brief;
          
          //Check if installed
          $mVars['MOD_INSTALLED'] = (in_array($package, $iPackages)) ? 'Yes' : 'No';
          $T_COND[] = (in_array($package, $iPackages)) ? 'INSTALL' : 'REMOVE';
          
          //Build the sub-template, no header/footer, path modifier, return the HTML
          $mHTML = BuildTemplate($mFile, $mVars, $T_COND, true, 'acp', true);
          
          //Add the HTML to the Mod List
          $T_VAR['MOD_LIST'] .= '      '.$mHTML.PHP_EOL;
        }//else unable to load XML file, skip...
      }//END Package Loop
      
      //Remove NO_RESULT html
      $T_COND[] = 'NO_RESULT';
    
    }else{ $T_COND[] = 'RESULT'; $T_VAR['MSG'] = 'No Results!';}
  }else{ $T_COND[] = 'RESULT'; $T_VAR['MSG'] = 'No Results!';}
  
  //Set the HTML file to use for main template
  $T_FILE = 'modSearch.html';
  
}//END Search Mode


//Build the template (LAST LINE OF ALL MAIN DRIVERS)
//In this file we alter the style path to "acp" sub-directory
//And attach true to the path string to signal using root header/footer
//Removing ",true" will make the template use header/footer from "acp" dir
BuildTemplate($T_FILE, $T_VAR, $T_COND, false, 'acp');
?>