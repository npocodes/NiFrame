/********************************************************
*	Ni Verification File - v1.0
*	Language: JavaScript
*	Author: Nathan < github/npocodes >
*	Date:	6/11/2012	
*	Description:
*		This file can be used to verify form input and is based
*		off the input types of HTML5
*
*		However, due to the severe lack of support for these
*		HTML5 input types we will need to check every single
*		"new" input type. Normally if the type is not supported
*		the browsers return its type as text. However FF and chrome
*		have decided to "disable" some of their "supported" types
*		so the type returns as if it is supported when it really is not.
*		//IE, FF, Chrome, Android
********************************************************/
/* 
Window.onload handler 
Written by: Simon Willison[http://www.webreference.com/programming/javascript/onloads/index.html]

Example usage: addLoadEvent(myFunc(var1,var2,etc));

[Nathan]
USE THIS function if you need to fire a JavaScript on page load.
*/
function addLoadEvent(func) { 
  var oldonload = window.onload; 
  if (typeof window.onload != 'function') { 
    window.onload = func; 
  } else { 
    window.onload = function() { 
      if (oldonload) { 
        oldonload(); 
      } 
      func(); 
    } 
  } 
}

//in_array function
function in_array(needle, array)
{
	var arrayCount = array.length;
	for(var i=0; i < arrayCount; i++)
	{
		if(array[i] == needle)
		{
			return true;
		}
	}
	return false;
}

//added for convenience, this can display or hide
//the option elements for multi option inputs
function enableOpts(inputId, divId)
{
  //The id value of the input element
  inputId = (typeof inputId !== 'undefined') ? inputId : 'myInput';
  
  //The id value of the option wrap element
  divId = (typeof divId !== 'undefined') ? divId : 'myOpts';
  
	inputValue = document.getElementById(inputId).value;
	opWrapElem = document.getElementById(divId);
	
  //If the input element's value is zero
  //then we assume its on a dummy option
  if(inputValue != 0)
  {
    //Show option inputs
    opWrapElem.style.visibility = 'visible';
    opWrapElem.style.display = 'table'; 
  }
  else
  {
    //Hide Option inputs
    opWrapElem.style.visibility = 'hidden';
    opWrapElem.style.display = 'none';  
  }
}
/********************************************************
*	Ni getElementsByClass Script - v1.0
*	Language: JavaScript
*	Author: Nathan < nathan@nativeinventions.com >
*	Date:	8/01/2012	
*	Description:
*		This script creates a new method for the document
*		object that enables coders to retrieve an array of
*		HTML elements within the HTML document body by the
*		value held by the class attribute.
*		
********************************************************/
/*/ !!! Get Elements By Class Name function !!!! /*/
document.getElementsByClass = function(needle) {

	//Initialize the node return List
	var rList = new Array();

	//Retrieve list of ALL nodes within the body
	var nList = document.body.childNodes;

	//check nodes for elements with the attribute "class" and needle
	rList = checkElements(nList, 'class', needle);
	
	//return the list of elements found
	return rList;
}
// checkElements method [recursive method]
/*
	Expects array of [node objects] the [attribute] to check
	and the [value] the attribute should hold.
	
	Essentially you could use this recursive method
	to create a function to search for any elements attribute
	and any attribute value.. ie (getElementByTitle) etc..
*/
function checkElements(Nodes, Attr, Needle)
{
	//Intialize the return list array
	var retList = new Array();
	
	//How many nodes were given to check?
	var nodeCount = Nodes.length;
	
	//alert("Checking "+ nodeCount +" nodes..");
	var na = 1;
	for(var n = 0; n < nodeCount; n++)
	{
		//Check to see if the node given is an element
		if(Nodes[n].nodeType == 1)
		{
			//This node is an element
			var nodeLabel = Nodes[n].nodeName;
			
			//Check if this node has attributes
			if(Nodes[n].hasAttributes())
			{
				nodeLabel = Nodes[n].nodeName + " " + Nodes[n].attributes[0].nodeName + "=" + Nodes[n].attributes[0].value;
				//alert("Node " + na + " is " + nodeLabel);
				
				//How many does it have?
				var attrCount = Nodes[n].attributes.length;
				//alert(nodeLabel + " has " + attrCount + " attributes");
				
				//cycle attrs
				for(var i=0; i < attrCount; i++)
				{
					if(Nodes[n].attributes[i].nodeName == Attr)
					{
						if(Nodes[n].attributes[i].value == Needle)
						{
							//This node is an element and it has a class attribute
							//the value of the class attribute matches the needle
							//add this node to the retList.
							retList[retList.length] = Nodes[n];
						}
						else
						{
							//alert(Nodes[n].attributes[i].value + " != " + Needle);
						}
					}
					else
					{
						//alert(Nodes[n].attributes[i].nodeName + " != " + Attr);
					}
				}
			}
		
			//Now that we have checked the Nodes attributes for class attribute, we need to see if it has children
			if(Nodes[n].hasChildNodes())
			{
				//Pass this nodes children recursively to this function
				var childList = Nodes[n].childNodes;
				
				//alert(nodeLabel + " has " + childList.length + " children..");
				
				var tempList = checkElements(childList, Attr, Needle);
				var tempCount = tempList.length;
				if(tempCount == 0)
				{
					//alert(nodeLabel + " did not have any elements that match");
				}
				else
				{
					for(var z=0; z < tempCount; z++)
					{
						//Foreach node returned, add those nodes to the retList
						retList[retList.length] = tempList[z];
					}
				}
			}
			else
			{
				//alert(nodeLabel + " does not have children");
			}
		}
		else
		{
			//alert("Node " + na + " is nodeType:" + Nodes[n].nodeType + " and not an element");
		}
		na++;
	}
	
	//Return the list of nodes found
	return retList;
}


//ScriptOn function
addLoadEvent(ScriptOn);
/*
	Enables the opposite of HTML's " <noscript></noscript> " tag
	By enabling the visibility of the scriptOn css class
*/
function ScriptOn()
{
	var elements = document.getElementsByClass("scriptOn");
	//alert("ScriptOn found " + elements.length + " elements");
	
	for(var i=0; i < elements.length; i++)
	{
		elements[i].style.display = 'block';
		elements[i].style.visibility = 'visible';
	}
}


addLoadEvent(detectFormInputs);
/*
	This function searches the current HTML and	attempts to locate input fields that require
	validation..and sets a listener on the element, when the onChange event is fired by the browser the
	"delegateValidation" function is invoked, which will analyze the input firing the event and
	invoke the proper verification method for it. If a "select" element is found this function attempts
	to locate the title attribute of the select element. If the title attribute is found the value is
	then used to automatically set the "defaultSelected" value of the select element.
*/
function detectFormInputs(){
	
	//Look for form elements on the page
	//finds the correct method for each browser
	var ArrayOfForms = (document.all) ? document.all.tags("form") : document.getElementsByTagName("body")[0].getElementsByTagName("form");
	
	//How many did we find?
	var FormCount = ArrayOfForms.length;
  
	//For each form on the page
	for(var i=0; i < FormCount; i++)
	{	
		//Get list of elements in the form
		var elem = ArrayOfForms[i].elements;
		
		//For each ELEMENT in the form
		for(var z=0; z < elem.length; z++)
		{
			//Determine the type of element in the form
			if(elem[z].type == "select-one")
			{
				//FIX SELECT INPUTS
				var OptionCount = elem[z].options.length;
				//Foreach option
				for(var x=0; x < OptionCount; x++)
				{
					if(elem[z].options[x].value == elem[z].title)
					{
						//We have to set this in three places since these
						//damn browsers can't agree on nothing
						elem[z].options[x].defaultSelected = true;
						elem[z].options[x].selected = true;
						elem[z].selectedIndex = x;
					}
					
					//alert("Option " + z + "-" + x + " defaultSelected = " + elem[z].options[x].defaultSelected);
				}
			}
			else
			{
				//For each input within each form, create an "onChange" listener
				//but only for relevant input types
				switch(elem[z].type)
				{
					case'button':
					case'checkbox':
					case'file':
					case'hidden':
					case'image':
					case'radio':
					case'reset':
					case'submit':
						//Skip this input element
						//alert("Element was skipped!, type: "+ ArrayOfInputs[z].type);
						//Types listed in these cases are standard and supported by all major
						//browsers and they do not require validation except special reasons, etc..
					break;
					
					default:
						//Attach Listener to text/pass/unknown input type
						elem[z].addEventListener('change', function(e){delegateValidation(this);}, false);
						
						//Get the first password input and remember it
            if(!remPassElem)
            {
              var remPassElem = (elem[z].type == 'password') ? elem[z] : null;
						}
            
						//Listen for confPass entry
						if(elem[z].id)
						{
							if(elem[z].id == 'confPass')
							{
								//pass along the first password input for comparison
								elem[z].addEventListener('change', function(e){delegateValidation(this, remPassElem);}, false);
                //alert(remPassElem[i].name);
							}
						}
					break;
				}
			}
		}
	}
}


/*
	This function handles the verification
	of input types that are not supported by
	browsers but require validation
*/
function delegateValidation(Element, confElem) {		
	
	//alert("Delegating Validation");
	//Create the supported types list.
	var typeList = Array();
	typeList[0] = 'color';
	typeList[1] = 'date';
	typeList[2] = 'time';
	typeList[3] = 'email';
	typeList[4] = 'month';
	typeList[5] = 'day';
	typeList[6] = 'year';
	typeList[7] = 'tel';
	typeList[8] = 'number';
	typeList[9] = 'basic';
	
	//Will hold the decided validation type
	//setting to type for now unless alt is found
	var validationType = Element.type;
	
	//Switch validation method based on type
	//if type == text verify this is the validation
	//type by comparing it with the alt attribute.
	//which should be set with the name of the type
	//desired by the dev. If they dont match then use
	//the type given in the alt attribute.
	if(validationType == "text")
	{
		//Check for alternate validation type
		if(Element.alt == "text" || !(in_array(Element.alt, typeList)))
		{
			//alternate validation confirms text validation is wanted
			//OR no known alternate type was given in the alt attribute
			//we will validate as text
			validationType = "text";
		}
		else
		{
			//Alternate validation type was found
			validationType = Element.alt;
		}
	}

	//Double verify that the validationType is supported
	if(in_array(validationType, typeList))
	{
		//Type is supported.. do nothing
		validationType = validationType;
	}
	else
	{
		//Else type is not supported or is text
		validationType = "text";
	}
	
	//Run the validation process for this input
	if(confElem)
	{
		niveri(Element, validationType, confElem);
	}
	else
	{
		niveri(Element, validationType);
	}
}

/*
  This function performs the verification on
  the given element based on its type.
  Supported Types:
  
    color - matches HTML hex color codes ie: #FF0000 //case is insensitive
				    matches RGB values ie: 255,255,255 (3 values 0-255 or 000-255 seperated by a comma)
    
    date - matches: yyyy+(-/)mm(-/)dd, yyyy+(-/)m(-/)d
    
    time - matches: 
            24hr format
           hh:mm:ss or h:m:s
           hh:mm or h:m
    
            12hr format
           hh:mm:ss am\pm or h:m:s am\pm
           hh:mm am\pm or h:m am\pm
    
    email - matches XX+@XX+.XX+ , in simple terms it loosely matches emails
            'loosely' because it matches a standard format as a minimum
            Ex: (someOne@someplace.com, no emphasis on verifying TLD's like .com, .edu etc..)
            
    month - matches as a number (0-12) or (00-12) 
            full name of month(September) or abbreviated name of month (Sept)
					 (case is insensitive)
           
    day - matches a number (1-31) or (01-31)
    
    year - matches numbers only and must be at least 4 of them, YYYY. 
           See date description for limitation reasons.
    
    phone	- matches:
            X-(XXX)-XXX-XXXX
            (XXX)-XXX-XXXX
            X-XXX-XXX-XXXX
            XXX-XXX-XXXX
            XXXXXXXXXXX	(11) includes country code
            XXXXXXXXXX	(10) no country code
    
    basic - matches letters, numbers, and allowed special characters [_-]
            this pattern does not allow numbers at the beginning
            
    text - matches letters, numbers, allowed special chars[!@#$%^&*()_-+='":.,] and case is insensitive
*/
function niveri(Element, vType, confElem)
{
	//alert(Element.name +" will validate as "+ vType);
	
	//Will hold the validation pattern to use
	var Pattern = '';
  
  //Get Pointer to submit element of the current form
  var submitElement;
  var elementList = Element.form.elements;
  for(var z=0; z < elementList.length; z++)
  {
    if(elementList[z].type == 'submit')
    {
      submitElement = elementList[z];
    }
  }
	
	//switch verification pattern based on vType given, we will also set
	//a variable that will hold the title text to explain what input is expected
	//in case the user input is invalid \n equals a line break in the title pop-ups
	var titleTxt = '';
	switch(vType)
	{
		case'color':
			Pattern = /^([#]?[a-f0-9]{6}|[(]?[0-2][0-5]{2}[,]?[ ]*[0-2][0-5]{2}[,]?[ ]*[0-2][0-5]{2}[)]?)$/i;
			//Pattern = /^[#]?[a-f0-9]{6}$/i;
			//Pattern = /^[(]?[0-2][0-5]{2}[,]?[ ]*[0-2][0-5]{2}[,]?[ ]*[0-2][0-5]{2}[)]?$/i;
			/*
				matches HTML hex color codes ie: #FF0000 //case is insensitive
				matches RGB values ie: 255,255,255
			*/
			titleTxt = 'Accepts a color code.\nEx:[#111FFF or (255,255,255)]';			
		break;
		
		case'date':
			Pattern = /^[0-9]{4}[\-\/]{1}([0]?[1-9]{1}|[1]{1}[0-2]{1})[\-\/]{1}([0]?[1-9]{1}|[1-2]{1}[0-9]{1}|[3]{1}[0-1]{1})$/i;
			/*
				matches: yyyy+(-/)mm(-/)dd, yyyy+(-/)m(-/)d, yyyy+(-/)mm(-/)d, yyyy+(-/)m(-/)dd
			*/
			titleTxt = 'Accepts a date.\n Ex:[yyyy+(-/)mm(-/)dd, yyyy+(-/)m(-/)d]';
		break;
		
		case'time':
			//24hr Pattern = /^$/i;
			Pattern = /^(([0]?[1-9]{1}|[1]{1}[0-2]{1})[\:]{1}([0]?[0-9]{1}|[1-5]{1}[0-9]{1})([\:]{1}([0]?[0-9]{1}|[1-5]{1}[0-9]{1})|)[\s]*(am|pm)|([0]?[1-9]{1}|[1]{1}[0-9]{1}|[2]{1}[0-4]{1})[\:]{1}([0]?[1-9]{1}|[1-5]{1}[0-9]{1})([\:]{1}([0]{1,2}|[0]?[1-9]{1}|[1-5]{1}[0-9]{1})|)[\s]*)$/i;
			/*	
				matches: 24hr format
						 hh:mm:ss or h:m:s
						 hh:mm or h:m
			
					     12hr format
						 hh:mm:ss am\pm or h:m:s am\pm
						 hh:mm am\pm or h:m am\pm
			*/
			titleTxt = 'Accepts a time in 12 or 24 hour format.\n24hr format - Ex:[hh:mm:ss or h:m:s]\n12hr format - Ex:[hh:mm:ss am\pm or h:m:s am\pm]\nYou must include am or pm if using 12hr format.\nSeconds value is not required in either format.';
		break;
		
		case'email':
			Pattern = /^([w]{3}|)[a-z0-9\.\_\-]{2,}[\@]{1}[a-z0-9\.\_\-]{2,}$/i;
			/*
				matches: XX+@XX+.XX+
				//keeping this simple for now
			*/
			titleTxt = 'Accepts an email address.\nEx: [someperson@somesite.com]\nAllowed special characters: . - _';
		break;
		
		case'month':
			Pattern = /^([0]?[1-9]{1}|[1]{1}[0-2]{1}|january|february|march|arpil|may|june|july|august|september|october|november|december|jan|feb|mar|apr|may|jun|jul|aug|sept|oct|nov|dec)$/i;
			/*
				matches a month..
				in several ways.. 
					as a number 0-12 or 00-12 full name of month or abbreviated name of month
					(case is insensitive)
			*/
			titleTxt = 'Accepts month as a number. Ex: [00-12]\nLeading zeros are not required.\n\nAccepts name of month. Ex:[December, Dec]\nCase is irrelevant';
		break;
		
		case'day':
			Pattern = /^([0]?[1-9]{1}|[1-2]{1}[0-9]{1}|[3]{1}[0-1]{1})$/;
			/*
				matches a number 1-31 or 01-31
			*/
			titleTxt = 'Accepts a day as a number: [01-31].\nLeading zeros are not required';
		break;
		
		case'year':
			Pattern = /^[0-9]{4,}$/;
			/*
				Matches numbers only and must be atleast 4 of them
				see date pattern for limitation reasons
			*/
			titleTxt = 'Accepts a year. Ex:[2012]\nFormat must be atleast 4 digits.';
		break;
		
		
		case'tel':
			Pattern = /^(([0-9]{1}[\-]{1}|)([\(]{1}[0-9]{3}[\)]{1}|[0-9]{3})[\-]{1}[0-9]{3}[\-]{1}[0-9]{4}|[0-9]{10,11})$/i;
			/*
				matches:
				X-(XXX)-XXX-XXXX
				(XXX)-XXX-XXXX
				X-XXX-XXX-XXXX
				XXX-XXX-XXXX
				XXXXXXXXXXX	(11) includes country code
				XXXXXXXXXX	(10) no country code
			*/
			titleTxt = 'Accepts a telephone number.\nEx: [?]';
		break;
		
		case'number':
			Pattern = /^[\-]?[0-9]+$/i;
			//Matches numbers only
			//includes negatives
			titleTxt = 'Accepts numbers. Ex: [123]\nNegatives are allowed';
		break;
		
		case'basic':
			Pattern = /^[a-z]+[a-z0-9\_\-]+$/i;
			//matches letters, numbers, and these special characters [_-]
			//this pattern does not allow numbers at the beginning
			titleTxt = 'Accepts Alphanumerals. Ex:[abc123-_]\n(The first character must be a letter)';
		break;
	
		default:
			//Default validation is text validation
			
			Pattern = /^[a-z0-9\!\@\#\$\%\^\&\*\(\)\_\-\+\=\'\"\:\.\,\s]+$/i;
			//letters,numbers,allowed special chars[!@#$%^&*()_-+='":.,] and case is insensitive
      
			titleTxt = 'Accepts Alphanumerals. Ex:[abc123]\nPermitted special characters: !@#$%^&*()_-+=\'":.,';
		break;
	}//End the pattern switch
	
	//Verify that the input matches the validation type
	if(Element.value.match(Pattern))
	{
		//alert("Input is valid!");
		
		if(confElem)
		{
			if(Element.value == confElem.value)
			{
				//Change the inputs border color to green
				//in order to indicate its valid
				Element.style.border = "";
				Element.style.borderColor = "#00FF00";//green
				Element.title = 'Passwords Match!';
        
        //enable the submit btn
        submitElement.disabled = false;				
				
        return true;
			}
			else
			{
				Element.style.border = "2px dashed #FF0000";//red
				Element.title = 'Passwords do not match!';

        //disable the submit btn
        submitElement.disabled = true;
    
				return false;
			}
		}
		
		//Change the inputs border color to green
		//in order to indicate its valid
		Element.style.border = "";
		Element.style.borderColor = "#00FF00";//green
		
    //enable the submit btn
		submitElement.disabled = false;
		
		return true;
	}
	else if(Element.value != "")
	{
		//alert("Input is not valid! expecting type "+ vType);
		
		//Change the inputs border color to red
		//in order to indicate its invalid
		//Element.style.borderColor = "#FF0000";//red
		
		//And in this case we will make the border style
		//dashed so that it is more noticeable.
		Element.style.border = "2px dashed #FF0000";//red
		
		//We will also automatically add a title attribute value
		//that will describe to the user what type of data is expected
		//(as long as the title attribute is already empty...)
		if(Element.title == '')
		{
			//alert("This elements title attribute is null!");
			Element.title = titleTxt;
		}
		else
		{
			//alert("This elements title attribute is already in use!");
		}
		
		//disable the form's submit button
		submitElement.disabled = true;
		
		return false;
	}
	else
	{
		//No value in the input box
		//reset border if need be and return 
		//true because although no match was found
		//there is nothing in the input box
		Element.style.border = "";
		Element.style.borderColor = "";
    
    //enable the submit btn
		submitElement.disabled = false;
    
		return true;
	}
}