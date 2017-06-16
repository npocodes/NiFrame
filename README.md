# NiFrame
NiFrame is a Custom Web Application Framework/CMS. NiFrame is designed to serve as a starting place
for developers to create custom websites or applications. All the common features for all websites
and web apps is included. Developers simply create or install third party modules to extend the 
functionality.


## Ni Framework - Version 1.0


### Overview:

NiFrame is a web application (or website) starter system. 
Simply install NiFrame to your web server and follow the installation wizard. The system is designed so 
that features can be added with as little as a new "driver" file or as many class, driver, and HTML 
files as needed. HTML is separated from PHP so HTML files can be combined to form new pages or 
"conditioned" to remove or add HTML as needed. Driver files can even be constructed or modified 
to work with FLASH style GUIs or AJAX(JQUERY) style HTML pages with very little effort. After installing 
NiFrame the following system features and functionality are available to you: 


### Proposed System and Functionality:
 
#### System Installer:
  * System Install Wizard ✓ 
    + System Files - Automatically generates required system files on your server. ✓
    + MySQL Data - Automatically builds system data tables and default values into your MySQL database. ✓
    + Configuration - Choose how to configure the System for specific needs. ⚠
    + Mod Selector - Choose which mods to install with the system. ⚠

  * System Un-Installer - Removes all NiFrame system files and database tables.⚒


#### Style (Template) System:
  * HTML File Merging - Combine HTML files to create different pages! ✓
  * Variable Injection - Change page data based on user and/or situation! ✓
  * Content Includes - Maintain a single content set or swap content while changing styles! ✓
  * Dynamic HTML Conditioning - Remove, Repeat and/or Nested Repeat HTML with dynamic values!! ✓
  * Automatic form detection and Input Validation for HTML5 inspired types on all browsers! ✓
  * Alternate GUI Support - Use Flash as your user interface instead of an HTML template! ⚠


#### User System: 
  * Session Handling & Data Security ✓
  * User Types(Groups)✓ 
  * User Type Based Permissions✓ 
  * User Statuses (Active, Banned, Misc...)✓ 
  * Registration System ✓
  * Basic Profiles ✓
  * Action Stalking (tracks what users do and when) ⚠
  * Phone SMS Fishing ⚠


#### User-Defined Attribute Support:
  * Custom Labels - Label attributes how you want to! ✓
  * Attribute Ranks - Adding a rank to an attribute allows you to choose where they show up! ✓
    + (ex: User Profile, Registration, Both, etc...)
  * Input Verification - Assign a verification type to your attribute/s for form inputs! ✓
  * Default Values - Assign default values to your attributes! ✓
  * Interface Design - Mod Devs, add user-defined attributes to any "thing" classes with ease! ✓
    + (A "thing" has attributes..items, users, etc...) 


#### Inventory Module: ⚠
  * Product Inventory
  * Product Display and Search
  * Service Inventory
  * Service Display and Search


#### Event Scheduling Module: ⚠
  * Event Calendar
  * Event Creation and Registration
  * User Availability Schedules
  * User Event Schedules
  * Event Support: Users, Items, Locations


#### Administrative Control Panel (ACP):
  * System Settings: ⚒ 
    + Default Time: 12hr | 24hr formats, Time Zones ⚒
    + Disable - Enable reCAPTCHA feature ✓
    + Select | De-select | Remove Styles ✓
      - (change entire website layout, look and content with 1 click!) 

  * User Management: ✓ 
    + User Settings (Self-Edit) ✓
    + Search | Create | Edit | Remove Users ✓
    + Create | Edit | Remove Types(Groups) ✓
    + Create | Edit | Remove Type(Group) Permissions ✓
    + Create | Edit | Remove Statuses ✓
    + Add | Remove Permission Access by User Type ✓

  * Inventory Management: ⚠
  * Event Management: ⚠
  
  * Mod Support: ⚒ 
    + Search Mod Packages ✓
    + Mod Package View ⚒
    + Mod Package Installer ✓
    + Mod Package Remover ⚒ 
    + Mod Package Updater ⚒

#### Tools Used:
* Programming Languages: PHP v5, SQL, HTML5, CSS3, JavaScript
* Server Platform: Hosted Server Running: Apache, PHP, and MySQL
* Tools Used: Notepad++, FileZilla, phpMyAdmin, Adobe Photoshop, Windows
* Browser Support: Internet Explorer, Fire Fox, Chrome, Android (latest versions)

