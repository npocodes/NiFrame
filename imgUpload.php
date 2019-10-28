<?php
/*
  Purpose:  Image Upload File - NiFrame
  
  FILE:     Handles processing for AJAX style image uploads
            
  Author:   Nathan Poole - github/npocodes
  Date:     August 2019
	Updated:	
*/
//Include the common file
require_once('common.php');

//Check for image file
$theImage = null;
if(isset($_FILES['imgFile']))
{
	//Set the target upload directory
	$target_dir = (isset($_INPUT['imgDir']) && is_dir($_INPUT['imgDir'])) ? $_INPUT['imgDir'].'/' : "imgs/";
	
	//Create the target url for the file (dir/fileName)
	//This name eventually needs to be made unique to avoid clashes.
	$uniqueName = uniqid(session_id()."_");//Prefix with the session_id so we can clear unused images later.
	$ext = pathinfo($_FILES["imgFile"]["name"])['extension'];
	$target_file = $target_dir.$uniqueName.'.'.$ext;
	
	//Get the file type information
	$mimeType = mime_content_type($_FILES["imgFile"]["tmp_name"]);
	$acceptedTypes = array("image/png", "image/jpeg", "image/gif", "image/bmp");
	if(in_array($mimeType, $acceptedTypes))
	{
		//Image file type accepted...
		//Verify image meets file size constraints
		if($_FILES["imgFile"]["size"] <= 200000)
		{
			//Image file is under size limit..
			//Verify the file doesn't already exist
			if(!(file_exists($target_file)))
			{
				//Image file does not already exist...
				if(move_uploaded_file($_FILES["imgFile"]["tmp_name"], $target_file))
				{
					//SUCCESS!
					echo("$target_file");
				}else{ echo("Upload Error"); }//ERROR MOVING IMAGE
			}else{ echo("File Exists"); }//FILE ALREADY EXISTS!
		}else{ echo("File Too Large"); }//FILE TOO LARGE!
	}else{ echo("Invalid File Type"); }//TYPE NOT ACCEPTED!
}else{ echo("No File"); }
?>