/*======================================================================*\
|| #################################################################### ||
|| # DBSEO: Search Engine Optimisation Tool 		         	      # ||
|| # ---------------------------------------------------------------- # ||
|| # Copyright ©2013 DragonByte Technologies 			      		  # ||
|| # https://www.dragonbyte-tech.com - https://www.dragonbyte-tech.net  # ||
|| # All Rights Reserved.                                             # ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------------------------------------------------------- # ||
|| # http://www.vbulletin.org/forum/showthread.php?t=           	  # ||
|| #################################################################### ||
\*======================================================================*/

/*======================================================================*\
|| Table of contents                                                    ||
||  1. License                                                          ||
||  2. Requirements                                                     ||
||  3. First Time Installation / Upgrade 	                      		||
||  4. Available Commands		 	                        			||
\*======================================================================*/


/*======================================================================*\
|| 1. License                                                           ||
\*======================================================================*/

DBSEO is released under the All Rights Reserved licence.
You may not redistribute the package in whole or significant part.
All copyright notices must remain unchanged and visible.
You may provide phrase .xml files for other languages on any site,
but you may not provide the full product .xml file - only the phrases.


/*======================================================================*\
|| 2. Requirements                                                      ||
\*======================================================================*/

DBSEO requires at least vBulletin 4.0.2 (vB4) or vBulletin 3.8.x (vB3)
It will not function correctly in vBulletin 4.0.1 because of a bug in that version.


/*======================================================================*\
|| 3. First Time Installation / Upgrade                                 ||
\*======================================================================*/

1. Upload all files from the "upload" folder to your forums directory.

2. Import the product-dbtech_dbseo.xml file from the "XML" folder at
AdminCP -> Plugins & Products -> Manage Products -> Add/Import Product

3. Edit your .htaccess file and add the following to your .htaccess file:


	RewriteEngine On

	# If you are having problem with "None Could Be Negotiated" errors in Apache, uncomment this to turn off MultiViews
	# Options -MultiViews

	RewriteCond %{QUERY_STRING} !dbseourl=
	RewriteCond %{REQUEST_URI} !(admincp/|dbseocp/|modcp/|cron|mobiquo|forumrunner|api\.php|reviewpost/|classifieds/|photopost/)
	RewriteRule ^(.*\.php)$ dbseo.php?dbseourl=$1 [L,QSA]

	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteCond %{REQUEST_FILENAME} !-d
	RewriteCond %{REQUEST_FILENAME} !/(admincp|dbseocp|modcp|clientscript|cpstyles|images|reviewpost|classifieds|photopost)/
	RewriteRule ^(.+)$ dbseo.php [L,QSA]


4. (vB4 Only) Go to AdminCP -> Settings -> Options -> Friendly URLs
and set the "URL Type" to "Standard URLs".

5. (vB4 Only) Go to AdminCP -> Settings -> Options -> Friendly URLs
and set the "Canonical URL" enforcement to "Off"

6. That's it! You can start editing settings and setting usergroup permissions.

7. The Admin controls can be found at /dbseocp/ - NOT in the AdminCP!