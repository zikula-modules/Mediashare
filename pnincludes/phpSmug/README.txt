phpSmug Class 1.0.10
Written by Colin Seymour
Project Homepage: http://www.lildude.co.uk/projects/phpSmug/
Released under GNU Lesser General Public License (http://www.gnu.org/copyleft/lgpl.html)

For more information about the class and upcoming tools and applications using 
it, visit http://www.lildude.co.uk/projects/phpsmug/ .

phpSmug is a PHP wrapper class for the SmugMug API and is based on work done by
Dan Coulter in phpFlickr (http://www.phpflickr.com).

All of the 1.2.0 API methods have been implemented in phpSmug 1.0.x.  You can 
see a full list and documentation at: http://smugmug.jot.com/WikiHome/1.2.0


Installation:

    Copy the files from the installation package into a folder on your
    server.  They need to be readable by your web server.  You can put 
    them into an include folder defined in your php.ini file, if you 
    like, though it's not required. 

Usage:
    
    To use phpSmug, all you have to do is include the file in your PHP scripts
	and create an instance.  For example:
	
    	require_once("phpSmug/phpSmug.php");
		$f = new phpSmug();

    The constructor takes three arguments:
    -   $APIKey - Required

		This is the API key you have obtained for your application from
		http://www.smugmug.com/hack/apikeys 
        
    -   $AppName - Optional
		Default: NULL
		
		This is the name, version and URL of the application you have built
		using the phpSmug. There is no required format, but something like:
		
			"My Cool App/1.0 (http://my.url.com)"
			
		... would be very useful.
		
		Whilst this isn't obligatory, it is recommended as it helps SmugMug
		identify the application that is calling the API in the event one of
		your users reports a problem on the SmugMug forums.
    
    -   $die_on_error - Optional
		Default: FALSE
		
		This takes a boolean value and determines whether the class will die 
		(aka cease operation) if the API returns an error statement.  Every 
		method will return false if the API returns an error.  You can access 
        error messages using the getErrorCode() and getErrorMsg() methods.

		Alternatively, set this to TRUE to display the errors returned by the 
		API. This is only recommended during the development stages of your
		application.

    To call a method, remove the "smugmug." part of the name and replace 
    any fullstops with underscores. For example, instead of smugmug.images.get, 
	you would call $f->images_get().

    Remember: The function names ARE case sensitive.
    
    All functions have their arguments implemented in the list order on 
    their documentation page (a link to which is included with each 
    method in the phpSmug class). The only variation is the SessionID does not
	need to be passed to the methods as it's automatically set when you establish
	a session.

	The only exceptions to this are albums_create(), albums_changeSettings(), 
	and images_upload().

	albums_create() and albums_changeSettings() have far too many possible 
	optional parameters. To pass the optional parameters, use an associative 
	array for all the options you wish to set.
	
	   For example:
	   $optArgs = array("AlbumTemplateID"=>"5", 
						"SubCategoryID"=>"20", 
						"Keywords"=>"cat;pets;dog");
	   $NewAlbumID = $f->albums_create($Title, $CategoryID, $optArgs); 
	
	See the API page for the full list of optional parameters
	
	images_upload() does not use the API for uploading, but instead HTTP PUT as
	recommended by SmugMug at http://smugmug.jot.com/WikiHome/API/Uploading
	
	HTTP PUT has been chosen as it's quicker, easier to use and more reliable
	than the other methods.
	
	
    
Authentication:

	You must use a login method to query SmugMug.  This sets up your session ID
	required for interaction with the API.
	
	login_anonymously() takes no arguments and will allow you to access any
	public gallery or image.
	
    If you wish to access private albums and images, upload or change settings,
    you will need to login using either login_withPassword() or login_withHash().

	Both methods will use HTTPS/SSL to ensure your username and password information
	is encrypted.

	login_withHash() is probably the most secure method as your email and 
	password can not be determined from the hash.  However, in order to obtain
	the hash, you need to login at least once using login_withPassword().
	

Caching:

	Caching can be very important to a project as it can drastically improve 
	the performance of your application. 
	
	phpSmug has caching functionality that can cache data to a database or 
	files, you just need to enable it.
	
	It is recommended that caching is enabled immediately after a new phpSmug
	instance is created, and before any other phpSmug methods are called.
	
	To enable caching, use the enableCache() function.
	
	The enableCache() function takes 4 arguments:
	
		- $type - Required
		  This is "db" for database or "fs" for filesystem.
		
		- $connection - Required
		  The value for this depends on which caching store you will be using
		  as defined by $type.
		
		  If you're using a database (db), then this a PEAR::DB connection 
		  string, for example:
		 	mysql://user:password@server/database
		
		  If you're using the local filesystem, this is the folder/directory
		  that the web server has write access to. This directory must already
		  exist.
		
		  Use absolute paths for best results as relative paths may have 
		  unexpected behaviour. They'll usually work, you'll just want to test
		  them.
		
		  You may not want to allow the world to view the files that are 
		  created during caching.  If you want to hide this information, either 
		  make sure that your permissions are set correctly, or prevent the 
		  webserver from displaying *.cache files.  
		
		  In Apache, you can specify this in the configuration files or in a 
		  .htaccess file with the following directives:

			<FilesMatch "\.cache$">
			   Deny from all
			</FilesMatch>

		  Alternatively, you can specify a directory that is outside of the 
		  web server's document root.
		
		- $cache_expire - Optional
		  Default: 3600
		
		  This is the maximum age of each cache entry in seconds.
		
		- $table - Option
		  Default: smugmug_cache
		
		  This is the database table name that will be used for storing the
		  cached data.  This is only applicable for database (db) caching.
		
		  If the table does not exist, phpSmug will attempt to create it.
		
	If you have caching enabled, and you make changes, it's a good idea to call
	clearCache() to refresh the cache so your changes are reflected immediately.
		
        
Uploading:

	Uploading is very easy.  You can either upload an image from your local
	system, or from a location on the web using the images_upload() function.
	
	You can use this function for both local and remote files as 
	images_uploadFromURL() just calls images_upload() anyway.
	
	In order to upload, you will need to have logged into SmugMug and have the
	album ID of the album you wish to upload to.
	
	Then it's just a matter of calling the method with the various optional
	parameters.
	
	For example, upload a local file using:
	
		images_upload(123456, "/path/to/image.jpg");
		
    or from a remote site using:

		images_upload(123456, "http://my.site.com/image.jpg");
		
		OR
		
		images_uploadFromURL(123456, "http://my.site.com/image.jpg");
		
	You can find a list of optional parameters, like caption and keywords on
	the API documentation page.
	
	
Replacing Photos:	

	Replacing photos is identical to uploading.  The only difference is you 
	need to specify the Image ID of the image you wish to replace.
        

Other Notes:
    1.  Many of the methods have optional arguments.  For these, I have 
		implemented them in the same order that the SmugMug API documentation 
		lists them. PHP allows for optional arguments in function calls, but 
		if you want to use the third optional argument, you have to fill in the 
		others to the left first. You can use the NULL value in the place of an 
		actual argument.  For example:
        
			$f->images_changeSettings($ImageID, NULL, $Caption);
			
		This will change just the image's caption.  NULL here is in place of 
		specifying the album ID. As no album ID is specified, the image will 
		remain in the same album.
			
    2.  Some people will need to use phpSmug from behind a proxy server.  You
		can use the setProxy() method to set the appropriate proxy settings.
		For example:
			$f = new phpSmug("<API KEY>");
			$f->setProxy("proxy_server", "8080");
			
		All your calls will then pass through the specified proxy on the 
		specified port.
		
		
And that's all folks.

Keep up to date on developments and enhancements to phpSmug on the project page
at http://www.lildude.co.uk/projects/phpsmug/ .

If you encounter any problems with phpSmug, please feel free to log a ticket
at http://dev.lildude.co.uk/phpSmug/newticket .

Oh, and by all means, please feel free to show your appreciation for phpSmug 
by making a small donation via the link at http://www.lildude.co.uk/projects/phpsmug/ .

This document is a compendium of all the documentation available from the project
page at http://www.lildude.co.uk/projects/phpsmug/docs/ .

Change History:

1.0.10 - 3 Mar '08
		-	Re-introduced anonymous method call logging. (Ticket #18)
		-	enableCache() now checks cache directory is writeable (Ticket #19)
		-	die_on_error is now acknowledged consistently. If die_on_error is NOT
			set, it's up to the application developer to check getErrorCode and
			getErrorMessage after each method call. (Ticket #20)
		-	Added a clearCache() function to easily empty the cache. (Ticket #22)
		-	Stopped caching the results of failed method calls when die_on_error
			is set. (Ticket #23)

1.0.9 - 26 Feb '08
		- 	Removed extra "}" (Ticket #17) - Thanks Erik Anderson for pointing 
		  	this one out

1.0.8 - 23 Feb '08
		-	Removed PHP 4 support. 
		-	Fixed typo in cache() apostrophe escaping when using "db" as the
			as the cache store (Ticket #15).
		-	Stopped caching anonymous method calls (Ticket #16).
		-	Start taking into account the 6 hour SessionID inactivity timeout.

1.0.7 - 10 Feb '08
		- 	Forgot to take into account the ImageKey returned by image_upload()
			and image_uploadFromURL(), and AlbumKey returned by album_create().
			These functions now return an array holding the ID and key. 
			Thanks to devbobo for pointing this out.

1.0.6 - 9 Feb '08
		-	Implemented support for the new security changes detailed at
			http://www.dgrin.com/showthread.php?t=83919
		- 	Documented phpSmug using phpDoc notation.

1.0.5 - 27 Jan '08
		-	All login.with* methods now use HTTPS to ensure login information
			is encrypted.

1.0.4 - 4 Jan '08
		-	Changing caching to ensure more consistent caching (Ticket #11)
		-	Changed "response" database table field type to LONGTEXT to cater
			for larger amounts of data. (Ticket #12)
		-	Fixed issue where apostrophes in cached data are not escaped
			correctly (Ticket #13)

1.0.3 - 17 Dec '07
		-	Corrected albums_get() argument order to align with that documented in
			the API docs, thus resolving an issue with the example.php. (Ticket #9)
		-	Corrected users_getTree() argument order too.

1.0.2 - 12 Oct '07
		-	Made images_uploadFromURL() use the API method and not mine
		
1.0.1 - 6 Oct '07
		-	Initial release
