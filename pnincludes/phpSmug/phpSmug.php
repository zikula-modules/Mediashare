<?php 
/** 
 * phpSmug - phpSmug is a PHP wrapper class for the SmugMug API. The intention 
 *		     of this class is to allow PHP application developers to quickly 
 *			 and easily interact with the SmugMug API in their applications, 
 *			 without having to worry about the finer details of the API.
 *
 * @author Colin Seymour <lildood@gmail.com>
 * @version 1.0.10
 * @package phpSmug
 *
 * Released under GNU Lesser General Public License (http://www.gnu.org/copyleft/lgpl.html)
 *
 * For more information about the class and upcoming tools and toys using it,
 * visit {@link http://www.lildude.co.uk/projects/phpsmug/}.
 *
 *     For installation and usage instructions, open the README.txt file 
 *	   packaged with this class. If you don't have a copy, you can refer to the 
 * 	   documentation at:
 * 
 *          {@link http://www.lildude.co.uk/projects/phpsmug/docs/}
 * 
 *     or grab a copy of the README.txt from:
 * 
 *			{@link http://dev.lildude.co.uk/phpSmug/browser/tag/phpSmug-1.0.7/README.txt?format=raw}
 *
 *     Please raise a ticket for any problems encountered with this class at:
 * 
 *			{@link http://dev.lildude.co.uk/phpSmug/newticket}
 *
 * phpSmug is based on phpFlickr 2.1.0 ({@link http://www.phpflickr.com}) by Dan Coulter
 *
 *
 **/

/** 
 * Decide which include path delimiter to use.  Windows should be using a semi-colon
 * and everything else should be using a colon.  If this isn't working on your system,
 * comment out this if statement and manually set the correct value into $path_delimiter.
 * 
 * @var string
 **/
$path_delimiter = (strpos(__FILE__, ':') !== false) ? ';' : ':';

/**
 * This will add the packaged PEAR files into the include path for PHP, allowing you
 * to use them transparently.  This will prefer officially installed PEAR files if you
 * have them.  If you want to prefer the packaged files (there shouldn't be any reason
 * to), swap the two elements around the $path_delimiter variable.  If you don't have
 * the PEAR packages installed, you can leave this like it is and move on.
 **/
ini_set('include_path', ini_get('include_path') . $path_delimiter . dirname(__FILE__) . '/PEAR');

/**
 * phpSmug - all of the phpSmug functionality is provided in this class
 *
 * @package phpSmug
 **/
class phpSmug {
	var $version = '1.0.10';
    var $APIKey;
	var $PHP = 'http://api.smugmug.com/hack/php/1.2.0/';
	var $PHPS = 'https://api.smugmug.com/hack/php/1.2.0/';
	var $Upload = 'http://upload.smugmug.com';     
	var $req;
    var $response;
    var $parsed_response;
    var $cache = FALSE;
    var $cache_db = NULL;
    var $cache_table = NULL;
    var $cache_dir = NULL;
    var $cache_expire = NULL;
    var $die_on_error;
    var $error_code;
    var $error_msg;
	var $SessionID;
	var $AppName;
	var $loginType;
	
	
	/**
     * When your database cache table hits this many rows, a cleanup
     * will occur to get rid of all of the old rows and cleanup the
     * garbage in the table.  For most personal apps, 1000 rows should
     * be more than enough.  If your site gets hit by a lot of traffic
     * or you have a lot of disk space to spare, bump this number up.
     * You should try to set it high enough that the cleanup only
     * happens every once in a while, so this will depend on the growth
     * of your table.
     *
     * @var integer
     */
    var $max_cache_rows = 1000;
	
	/**
	 * Constructor to set up a phpSmug instance.
	 * 
	 * 	The Application Name (AppName) is not obligatory, but it helps 
	 * SmugMug diagnose any problems users of your application may encounter.
	 * If you're going to use this, please use a string and include your
	 * version number and URL as follows.
	 * For example "My Cool App/1.0 (http://my.url.com)"
	 *
	 * The API Key must be set before any calls can be made.  You can
     * get your own at http://www.smugmug.com/hack/apikeys
	 *
	 * @return void
	 * @param string $APIKey SmugMug API key. You can get your own from http://www.smugmug.com/hack/apikeys
	 * @param stirng|null $AppName The name and version of your applicaion in the form "AppName/version (URI)" e.g. "My Cool App/1.0 (http://my.url.com)".  This isn't obligatory, but it SmugMug diagnose any problems users of your application may encounter.
	 * @param boolean|null $die_on_error Cause phpSmug to die when an error is encountered
	 **/
    function phpSmug($APIKey, $AppName = NULL, $die_on_error = FALSE) {
        $this->APIKey = $APIKey;
        $this->die_on_error = $die_on_error;

		// Set the Application Name
		$this->AppName = (strlen($AppName)>0) ?  $AppName : 'Unknown Application';

        // All calls to the API are done via the POST method using the PEAR::HTTP_Request package.
        require_once 'HTTP/Request.php';
        $this->req =& new HTTP_Request();
        $this->req->setMethod(HTTP_REQUEST_METHOD_POST);
		$this->req->addHeader("User-Agent", "$this->AppName using phpSmug/$this->version");
    }
	
	/**
	 * Function enables caching.
	 *
	 * @return void
	 * @param string $type The type of cache to use. It must be either "db" (for database caching) or "fs" (for filesystem).
	 * @param string $connection When using type "db", this must be a PEAR::DB connection string eg. "mysql://user:password@server/database".  When using type "fs", this must be a folder that the web server has write access to. Use absolute paths for best results.  Relative paths may have unexpected behavior when you include this.  They'll usually work, you'll just want to test them.
	 * @param integer|null $cache_expire Cache timeout in seconds. This defaults to 3600 seconds (1 hour) if not specified.
	 * @param string|null $table If using type "db", this is the database table name that will be used.  Defaults to "smugmug_cache".
	 **/
	function enableCache($type, $connection, $cache_expire = 3600, $table = 'smugmug_cache') {
        if ($type == 'db') {
            require_once 'DB.php';
            $db =& DB::connect($connection);
			if (PEAR::isError($db)) {
				if ($this->die_on_error) {
					die($db->getMessage());
				} else {
					$this->error_code = -1;
					$this->error_msg = $db->getMessage();
					$this->cache = FALSE;
					return false;
				}
            }

            /*
             * If high performance is crucial, you can easily comment
             * out this query once you've created your database table.
             */
            $db->query("
                CREATE TABLE IF NOT EXISTS `$table` (
                    `request` CHAR( 35 ) NOT NULL ,
                    `response` LONGTEXT NOT NULL ,
                    `expiration` DATETIME NOT NULL ,
                    INDEX ( `request` )
                ) TYPE = MYISAM");

            if ($db->getOne("SELECT COUNT(*) FROM $table") > $this->max_cache_rows) {
                $db->query("DELETE FROM $table WHERE expiration < DATE_SUB(NOW(), INTERVAL $cache_expire second)");
                $db->query('OPTIMIZE TABLE ' . $this->cache_table);
            }

            $this->cache = 'db';
            $this->cache_db = $db;
            $this->cache_table = $table;
        } elseif ($type == 'fs') {
	            $this->cache = 'fs';
	            $connection = realpath($connection);
	            $this->cache_dir = $connection;
	            if ($dir = @opendir($this->cache_dir)) {
					if (is_writeable($this->cache_dir)) {
	                	while ($file = readdir($dir)) {
	                    	if (substr($file, -2) == '.cache' && ((filemtime($this->cache_dir . '/' . $file) + $cache_expire) < time()) ) {
	                        	unlink($this->cache_dir . '/' . $file);
	                    	}
	                	}
					} else {
						if ($this->die_on_error) {
							die("Cache Directory \"".$this->cache_dir."\" is not writeable.  Please set the appropriate permissions.");
						} else {
							$this->error_code = -3;
							$this->error_msg = "Cache Directory \"".$this->cache_dir."\" is not writeable.  Please set the appropriate permissions.";
							$this->cache = FALSE;
							return false;
						}
					}
	            } else {
					if($this->die_on_error) {
						die("Cache Directory \"".$this->cache_dir."\" doesn't exist or is not readable.  Please create this directory and set appropriate permissions.");
					} else {
						$this->error_code = -4;
						$this->error_msg = "Cache Directory \"".$this->cache_dir."\" doesn't exist or is not readable.  Please create this directory and set appropriate permissions.";
						$this->cache = FALSE;
						return false;
					}
				}
	        }
        $this->cache_expire = $cache_expire;
    }

	/**
	 * 	Checks the database or filesystem for a cached result to the request.
	 *
	 * @return string|false Unparsed serialized PHP, or false
	 * @param array $request Request to the SmugMug created by one of the later functions in phpSmug.
	 **/
    function getCached($request) {
		$request['SessionID'] = ''; // Unset SessionID
		$reqhash = md5(serialize($request).$this->loginType);
		$expire = (strpos($request['method'], 'login.with')) ? 21600 : $this->cache_expire;
        if ($this->cache == 'db') {
            $result = $this->cache_db->getOne("SELECT response FROM " . $this->cache_table . " WHERE request = ? AND DATE_SUB(NOW(), INTERVAL " . (int) $expire . " SECOND) < expiration", $reqhash);
            if (!empty($result)) {
                return $result;
            }
        } elseif ($this->cache == 'fs') {
            $file = $this->cache_dir . '/' . $reqhash . '.cache';
            if (file_exists($file) && ((filemtime($file) + $expire) > time()) ) {
					return file_get_contents($file);
			}
       	}
        return false;
    }

	/**
	 * Caches the unparsed serialized PHP of a request
	 *
	 * @return null|false
	 * @param array $request Request to the SmugMug created by one of the later functions in phpSmug.
	 * @param string $response
	 **/
    function cache($request, $response) {
		$request['SessionID'] = ''; // Unset SessionID
        $reqhash = md5(serialize($request).$this->loginType);
        if ($this->cache == 'db') {
            if ($this->cache_db->getOne("SELECT COUNT(*) FROM {$this->cache_table} WHERE request = '$reqhash'")) {
                $sql = "UPDATE " . $this->cache_table . " SET response = ?, expiration = ? WHERE request = ?";
                $this->cache_db->query($sql, array($response, strftime("%Y-%m-%d %H:%M:%S"), $reqhash));
            } else {
                $sql = "INSERT INTO " . $this->cache_table . " (request, response, expiration) VALUES ('$reqhash', '" . str_replace("'", "\'", $response) . "', '" . strftime("%Y-%m-%d %H:%M:%S") . "')";
                $this->cache_db->query($sql);
            }
        } elseif ($this->cache == "fs") {
            $file = $this->cache_dir . "/" . $reqhash . ".cache";
            $fstream = fopen($file, "w");
            $result = fwrite($fstream,$response);
            fclose($fstream);
            return $result;
        }
        return false;
    }

	/**
	 *  Clears the cache.
	 *
	 * @return string|false
	 * @since 1.0.10
	 **/
    function clearCache() {
   		if ($this->cache == 'db') {
	    	$result = $this->cache_db->query("TRUNCATE " . $this->cache_table);
	    	if (!empty($result)) {
	        	return $result;
	    	}
	   	} elseif ($this->cache == 'fs') {
	       	if ($dir = @opendir($this->cache_dir)) {
	           	while ($file = readdir($dir)) {
					if ($file == '.' || $file == '..') { 
						continue;
					} else {
						$result = unlink($this->cache_dir . '/' . $file);
					} 
	           	}
				return $result;
	       	}
	   	}
		return false;
	}

	/**
	 * 	Sends a request to SmugMug's PHP endpoint via POST. If we're calling
	 *  one of the login.with* methods, we'll use the HTTPS end point to ensure
	 *  things are secure by default
	 *
	 * @return string Serialized PHP response from SmugMug, or an error.
	 * @param string $command SmugMug API command to call in the request
	 * @param array|null $args Array of arguments that form the API call
	 * @param boolean|null $nocache Set whether the call should be cached or not.
	 **/
	function request($command, $args = array(), $nocache = FALSE) {
		$this->req->clearPostData();
        
		if (strpos($command, 'login.with')) {
			$this->req->setURL($this->PHPS);
		} else {
        	$this->req->setURL($this->PHP);
		}
		if (substr($command,0,8) != "smugmug.") {
            $command = "smugmug." . $command;
        }

        // Process arguments, including method and login data.
        $args = array_merge(array("method" => $command, "APIKey" => $this->APIKey), $args);
        ksort($args);
        if (!($this->response = $this->getCached($args)) || $nocache) {
            foreach ($args as $key => $data) {
                $auth_sig .= $key . $data;
                $this->req->addPostData($key, $data);
            }
            
           //Send Requests
            if (!PEAR::isError($this->req->sendRequest())) {
                $this->response = $this->req->getResponseBody();
            } else {
				if ($this->die_on_error) {
					die($this->req->getMessage());
				} else {
					$this->error_code = -5;
					$this->error_msg = ($this->req->getMessage != null ? $this->req->getMessage() : 'No request object');
					return false;
				}
            }
        }

		$this->parsed_response = unserialize($this->response);
        if ($this->parsed_response['stat'] == 'fail') {
            if ($this->die_on_error) die("The SmugMug API returned the following error: #{$this->parsed_response['code']} - {$this->parsed_response['message']}");
            else {
                $this->error_code = $this->parsed_response['code'];
                $this->error_msg = $this->parsed_response['message'];
                $this->parsed_response = FALSE;
            }
        } else {
            $this->error_code = FALSE;
            $this->error_msg = FALSE;
            $this->cache($args, $this->response);
        }
        return $this->response;
    }
	
	/**
	 * Set a proxy for all phpSmug calls
	 *
	 * @return void
	 * @param string $server Proxy server in the form http://server
	 * @param integer $port Proxy server port
	 **/
    function setProxy($server, $port) {
        $this->req->setProxy($server, $port);
    }

	/**
	 * Returns the error code of the last call.
	 *
	 * @return integer|false An error code if an error occured, else false
	 **/
    function getErrorCode() {
		return $this->error_code;
    }

	/**
	 * Returns the error message of the last call.
	 *
	 * @return string|false An error message if an error occured, else false
	 **/
    function getErrorMsg() {
		return $this->error_msg;
    }
	
	/*
     * These functions are the direct implementations of SmugMug calls.
     * For method documentation, including arguments, visit the address
     * included in a comment in the function.
	 */
	
	/**
	 * @return array|false
	 * @uses request
	 * @link http://wiki.smugmug.com/display/SmugMug/smugmug.login.withPassword+1.2.0
	 **/
	function login_withPassword($EmailAddress, $Password) {
		$this->loginType = 'authd';
		$this->request('smugmug.login.withPassword', array("EmailAddress" => $EmailAddress, "Password" => $Password));
		$this->SessionID = $this->parsed_response['Login']['Session']['id'];
		return $this->parsed_response ? $this->parsed_response['Login'] : FALSE;
	}
	
	/**
	 * @return array|false
	 * @uses request
	 * @link http://wiki.smugmug.com/display/SmugMug/smugmug.login.withHash+1.2.0
	 **/
	function login_withHash($UserID, $PasswordHash) {
		$this->loginType = 'authd';
		$this->request('smugmug.login.withHash', array("UserID" => $UserID, "PasswordHash" => $PasswordHash));
		$this->SessionID = $this->parsed_response['Login']['Session']['id'];
		return $this->parsed_response ? $this->parsed_response['Login'] : FALSE;
	}
	
	/**
	 * @return array|false 
	 * @uses request
	 * @link http://wiki.smugmug.com/display/SmugMug/smugmug.login.anonymously+1.2.0
	 **/
	function login_anonymously() {
		$this->loginType = 'anon';
		$this->request('smugmug.login.anonymously');
		$this->SessionID = $this->parsed_response['Login']['Session']['id'];
		return $this->parsed_response ? $this->parsed_response['Login'] : FALSE;
	}
	
	/**
	 * @return void
	 * @uses request
	 * @link http://wiki.smugmug.com/display/SmugMug/smugmug.logout+1.2.0
	 **/
	function logout() {
		$this->request('smugmug.logout', array("SessionID" => $this->SessionID));
		return $this->parsed_response ? $this->parsed_response['Logout']['Successful'] : FALSE;
	}
	
	/**
	 * @return array|false
	 * @uses request
	 * @link http://wiki.smugmug.com/display/SmugMug/smugmug.users.getTree+1.2.0
	 **/
	function users_getTree($NickName = NULL, $Heavy = FALSE, $SitePassword = NULL) {
		$this->request('smugmug.users.getTree', array("SessionID" => $this->SessionID, "NickName" => $NickName, "Heavy" => $Heavy, "SitePassword" => $SitePassword));
		return $this->parsed_response ? $this->parsed_response['Categories'] : FALSE;
	}

	/**
	 * @return array|false
	 * @uses request
	 * @link http://wiki.smugmug.com/display/SmugMug/smugmug.users.getTransferStats+1.2.0
	 **/
	function users_getTransferStats($Month, $Year) {
		$this->request('smugmug.users.getTransferStats', array("SessionID" => $this->SessionID, "Month" => intval($Month), "Year" => intval($Year)));
		return $this->parsed_response ? $this->parsed_response['Albums'] : FALSE;
	}
	
	/**
	 * @return array|false
	 * @uses request
	 * @link http://wiki.smugmug.com/display/SmugMug/smugmug.albums.get+1.2.0
	 **/	
	function albums_get($NickName = NULL, $Heavy = FALSE, $SitePassword = NULL) {
        $this->request('smugmug.albums.get', array("SessionID" => $this->SessionID, "NickName" => $NickName, "Heavy" => $Heavy, "SitePassword" => $SitePassword));
        return $this->parsed_response ? $this->parsed_response['Albums'] : FALSE;
    }
	
	/**
	 * @return array|false
	 * @uses request
	 * @link http://wiki.smugmug.com/display/SmugMug/smugmug.albums.getInfo+1.2.0
	 **/
	function albums_getInfo($AlbumID, $AlbumKey, $Password = NULL, $SitePassword = NULL) {
	    $this->request('smugmug.albums.getInfo', array("SessionID" => $this->SessionID, "AlbumID" => intval($AlbumID), "AlbumKey" => $AlbumKey, "Password" => $Password, "SitePassword" => $SitePassword));
        return $this->parsed_response ? $this->parsed_response['Album'] : FALSE;
    }
	
	/**
	 * @return array|false
	 * @uses request
	 * @link http://wiki.smugmug.com/display/SmugMug/smugmug.albums.getStats+1.2.0
	 **/
	function albums_getStats($AlbumID, $Month, $Year, $Heavy = FALSE) {
		$this->request('smugmug.albums.getStats', array("SessionID" => $this->SessionID, "AlbumID" => intval($AlbumID), "Month" => intval($Month), "Year" => intval($Year), "Heavy" => $Heavy));
		return $this->parsed_response ? $this->parsed_response['Album'] : FALSE;
	}
	
	/**
	 * 	I've broken away from the standard format for this function as there
	 * 	are just soooo many optional parameters. To pass the optional
	 * 	parameters, use an associative array for all the options you wish
	 * 	to set.
	 * 
	 * 	For example:
	 * 	    $NewAlbumID = $f->albums_create($SessionID, $Title, $CategoryID, array("AlbumTemplateID"=>"5", "SubCategoryID"=>"20", "Keywords"=>"cat,pets,dog")); 
	 * 		
	 * 	See the API page for the full list of optional parameters
	 *  
	 * @return array|false
	 * @uses request
	 * @link http://wiki.smugmug.com/display/SmugMug/smugmug.albums.create+1.2.0
	 **/
	function albums_create($Title, $CategoryID, $OptArgs = NULL) {
		$this->request('smugmug.albums.create', array_merge(array("SessionID" => $this->SessionID, "Title" => $Title, "CategoryID" => $CategoryID), $OptArgs));
		return $this->parsed_response ? $this->parsed_response['Album'] : FALSE;
	}
	
	/**
	 * 	I've broken away from the standard format for this function as there
	 *  are just soooo many optional parameters. See {@link albums_create()} for more details.
	 * 
	 * @return string|false
	 * @uses request
	 * @link http://wiki.smugmug.com/display/SmugMug/smugmug.albums.changeSettings+1.2.0
	 **/
	function albums_changeSettings($AlbumID, $OptArgs = NULL) {
		$this->request('smugmug.albums.changeSettings', array_merge(array("SessionID" => $this->SessionID, "AlbumID" => $AlbumID), $OptArgs));
		return $this->parsed_response ? $this->parsed_response['stat'] : FALSE;
	}
	
	/**
	 * @return string|false
	 * @uses request
	 * @link http://wiki.smugmug.com/display/SmugMug/smugmug.albums.reSort+1.2.0
	 **/
	function albums_reSort($AlbumID, $By, $Direction) {
		/*  */
		$this->request('smugmug.albums.reSort', array("SessionID" => $this->SessionID, "AlbumID" => $AlbumID, "By" => $By, "Direction" => $Direction));
		return $this->parsed_response ? $this->parsed_response['stat'] : FALSE;
	}
	
	/**
	 * @return string|false
	 * @uses request
	 * @link http://wiki.smugmug.com/display/SmugMug/smugmug.albums.delete+1.2.0
	 **/
	function albums_delete($AlbumID) {
		$this->request('smugmug.albums.delete', array("SessionID" => $this->SessionID, "AlbumID" => $AlbumID));
		return $this->parsed_response ? $this->parsed_response['stat'] : FALSE;
	}
	
	/**
	 * @return array|false
	 * @uses request
	 * @link http://wiki.smugmug.com/display/SmugMug/smugmug.albumtemplates.get +1.2.0
	 **/
	function albumtemplates_get() {
		$this->request('smugmug.albumtemplates.get', array("SessionID" => $this->SessionID));
		return $this->parsed_response ? $this->parsed_response['AlbumTemplates'] : FALSE;
	}
	
	/**
	 * @return array|false
	 * @uses request
	 * @link http://wiki.smugmug.com/display/SmugMug/smugmug.images.get+1.2.0
	 **/
	function images_get($AlbumID, $AlbumKey, $Heavy = FALSE, $Password = NULL, $SitePassword = NULL) {
		$this->request('smugmug.images.get', array("SessionID" => $this->SessionID, "AlbumID" => intval($AlbumID), "AlbumKey" => $AlbumKey, "Heavy" => $Heavy, "Password" => $Password, "SitePassword" => $SitePassword));
		return $this->parsed_response ? $this->parsed_response['Images'] : FALSE;
	}
	
	/**
	 * NOTE: Whilst the API page details various options for the TemplateID, 
	 * they don't seem to have any effect.  The AlbumURL always remains the 
	 * same. It's probably of no use other than to the actual SmugMug site at 
	 * the moment. It's been implemented anyway.
	 * 
	 * @return array|false
	 * @uses request
	 * @link http://wiki.smugmug.com/display/SmugMug/smugmug.images.getURLs+1.2.0
	 **/
	function images_getURLs($ImageID, $ImageKey, $TemplateID = NULL, $Password = NULL, $SitePassword = NULL) {
		$this->request('smugmug.images.getURLs', array("SessionID" => $this->SessionID, "ImageID" => intval($ImageID), "ImageKey" => $ImageKey, "TemplateID" => intval($TemplateID), "Password" => $Password, "SitePassword" => $SitePassword));
		return $this->parsed_response ? $this->parsed_response['Image'] : FALSE;
	}
	
	/**
	 * @return array|false
	 * @uses request
	 * @link http://wiki.smugmug.com/display/SmugMug/smugmug.images.getInfo+1.2.0
	 **/
	function images_getInfo($ImageID, $ImageKey, $Password = NULL, $SitePassword = NULL) {
		$this->request('smugmug.images.getInfo', array("SessionID" => $this->SessionID, "ImageID" => intval($ImageID), "ImageKey" => $ImageKey, "Password" => $Password, "SitePassword" => $SitePassword));
		return $this->parsed_response ? $this->parsed_response['Image'] : FALSE;
	}
	
	/**
	 * @return array|false
	 * @uses request
	 * @link http://wiki.smugmug.com/display/SmugMug/smugmug.images.getEXIF+1.2.0
	 **/
	function images_getEXIF($ImageID, $ImageKey, $Password = NULL, $SitePassword = NULL) {
		$this->request('smugmug.images.getEXIF', array("SessionID" => $this->SessionID, "ImageID" => intval($ImageID), "ImageKey" => $ImageKey, "Password" => $Password, "SitePassword" => $SitePassword));
		return $this->parsed_response ? $this->parsed_response['Image'] : FALSE;
	}
	
	/**
	 * @return boolean
	 * @uses request
	 * @link http://wiki.smugmug.com/display/SmugMug/smugmug.images.changeSettings+1.2.0
	 **/
	function images_changeSettings($ImageID, $AlbumID = NULL, $Caption = NULL, $Keywords = NULL) {
		$this->request('smugmug.images.changeSettings', array("SessionID" => $this->SessionID, "ImageID" => $ImageID, "AlbumID" => $AlbumID, "Caption" => $Caption, "Keywords" => $Keywords));
		return $this->parsed_response ? $this->parsed_response['Image'] : FALSE;
	}
	
	/**
	 * @return boolean
	 * @uses request
	 * @link http://wiki.smugmug.com/display/SmugMug/smugmug.images.changePosition+1.2.0
	 **/
	function images_changePosition($ImageID, $Position) {
		$this->request('smugmug.images.changePosition', array("SessionID" => $this->SessionID, "ImageID" => $ImageID, "Position" => $Position));
		return $this->parsed_response ? $this->parsed_response['Image'] : FALSE;
	}
	
	/**
	 * 	I break away from the standard API here as recommended by SmugMug at
	 * {@link http://wiki.smugmug.com/display/SmugMug/smugmug.images.upload+1.2.0}.
	 *
	 * I've chosen to go with the HTTP PUT method as it is quicker, simpler
	 * and more reliable than using the API or POST methods.
	 * 
	 * @return array|false
	 * @uses request
	 * @link http://smugmug.jot.com/WikiHome/API/Uploading 
	 **/
	function images_upload($AlbumID, $File, $Caption = NULL, $Keywords = NULL, $Latitude = NULL, $Longitude = NULL, $Altitude = NULL, $ImageID = NULL) {
		$fp = fopen ($File, "r");
		$data = '';
		while (!feof($fp)) {
		  $data .= fread($fp, 8192);
		}
		fclose($fp);

		$upload_req =& new HTTP_Request();
        $upload_req->setMethod(HTTP_REQUEST_METHOD_PUT);
		$upload_req->setHttpVer(HTTP_REQUEST_HTTP_VER_1_1);
		$upload_req->clearPostData();
		
		$FileName = basename($File);

		/* For some reason things go a bit TU when I set this - I think it's a problem with the HTTP::Request
		$upload_req->addHeader("Content-Length", $ContentLength); */
		$upload_req->addHeader("User-Agent", "$this->AppName using phpSmug/$this->version");
		$upload_req->addHeader("Content-MD5", md5_file($File));
		$upload_req->addHeader("X-Smug-SessionID", $this->SessionID);
		$upload_req->addHeader("X-Smug-Version", $this->version);
		$upload_req->addHeader("X-Smug-ResponseType", "PHP");
		$upload_req->addHeader("X-Smug-AlbumID", $AlbumID);
		$upload_req->addHeader("Connection", "keep-alive");
		$upload_req->addHeader("X-Smug-Filename", $FileName); // This is actually optional, but we may as well use what we're given
		
		/* Optional Headers */
		(isset($ImageID)) ? $upload_req->addHeader("X-Smug-ImageID", $ImageID) : false;
		(isset($Caption)) ? $upload_req->addHeader("X-Smug-Caption", $Caption) : false;
		(isset($Keywords)) ? $upload_req->addHeader("X-Smug-Keywords", $Keywords) : false;
		(isset($Latitude)) ? $upload_req->addHeader("X-Smug-Latitude", $Latitude) : false;
		(isset($Longitude)) ? $upload_req->addHeader("X-Smug-Longitude", $Longitude) : false;
		(isset($Altitude)) ? $upload_req->addHeader("X-Smug-Altitude", $Altitude) : false;

		$upload_req->setURL($this->Upload . "/".$FileName);

		$result = $upload_req->setBody($data);

	    if (PEAR::isError($result)) {
			if ($this->die_on_error) {
		        die($result->getMessage());
			} else {
				$this->error_code = -6;
				$this->error_msg = $result->getMessage();
				return false;
			}
	    }

		// Send Requests
	    if (!PEAR::isError($upload_req->sendRequest())) {
	        $this->response = $upload_req->getResponseBody();
	    } else {
			if ($this->die_on_error) {
			        die($upload_req->getMessage());
			} else {
					$this->error_code = -7;
					$this->error_msg = $upload_req->getMessage();
					return false;
			}
	    }
	
		$this->parsed_response = unserialize($this->response);
        if ($this->parsed_response['stat'] == 'fail') {
            if ($this->die_on_error) die("The SmugMug API returned the following error: #{$this->parsed_response['code']} - {$this->parsed_response['message']}");
            else {
                $this->error_code = $this->parsed_response['code'];
                $this->error_msg = $this->parsed_response['message'];
                $this->parsed_response = FALSE;
            }
        } else {
            $this->error_code = FALSE;
            $this->error_msg = FALSE;
        }
		return $this->parsed_response ? $this->parsed_response['Image'] : FALSE;
	}
	
	/**
	 * @return array|false
	 * @uses request
	 * @link http://wiki.smugmug.com/display/SmugMug/smugmug.images.uploadFromURL+1.2.0
	 **/
	function images_uploadFromURL($AlbumID, $URL, $Caption = NULL, $Keywords = NULL, $Latitude = NULL, $Longitude = NULL, $Altitude = NULL, $ByteCount = NULL, $MD5Sum = NULL) {
		$this->request('smugmug.images.uploadFromURL', array("SessionID" => $this->SessionID, "AlbumID" => $AlbumID, "URL" => $URL, "Caption" => $Caption, "Keywords" => $Keywords, "Latitude" => $Latitude, "Longitude" => $Longitude, "Altitude" => $Altitude, "ByteCount" => $ByteCount, "MD5Sum" => $MD5Sum));
		return $this->parsed_response ? $this->parsed_response['Image'] : FALSE;
	}
	
	/**
	 * @return string|false
	 * @uses request
	 * @link http://wiki.smugmug.com/display/SmugMug/smugmug.images.delete+1.2.0
	 **/
	function images_delete($ImageID) {
		$this->request('smugmug.images.delete', array("SessionID" => $this->SessionID, "ImageID" => intval($ImageID)));
		return $this->parsed_response ? $this->parsed_response['stat'] : FALSE;	
	}
	
	/**
	 * @return array|false
	 * @uses request
	 * @link http://wiki.smugmug.com/display/SmugMug/smugmug.images.getStats+1.2.0
	 **/
	function images_getStats($ImageID, $Month) {
		$this->request('smugmug.images.getStats', array("SessionID" => $this->SessionID, "ImageID" => intval($ImageID), "Month" => intval($Month)));
		return $this->parsed_response ? $this->parsed_response['Image'] : FALSE;
	}
	
	/**
	 * @return array|false
	 * @uses request
	 * @link http://wiki.smugmug.com/display/SmugMug/smugmug.categories.get+1.2.0
	 **/
	function categories_get($NickName = NULL, $SitePassword = NULL) {
		$this->request('smugmug.categories.get', array("SessionID" => $this->SessionID, "NickName" => $NickName, "SitePassword" => $SitePassword));
		return $this->parsed_response ? $this->parsed_response['Categories'] : FALSE;
	}
	
	/**
	 * @return integer|false
	 * @uses request
	 * @link http://wiki.smugmug.com/display/SmugMug/smugmug.categories.create+1.2.0
	 **/
	function categories_create($Name) {
		$this->request('smugmug.categories.create', array("SessionID" => $this->SessionID, "Name" => $Name));
		return $this->parsed_response ? $this->parsed_response['Category']['id'] : FALSE;
	}
	
	/**
	 * @return string|false
	 * @uses request
	 * @link http://wiki.smugmug.com/display/SmugMug/smugmug.categories.delete+1.2.0
	 **/
	function categories_delete($CategoryID) {
		$this->request('smugmug.categories.delete', array("SessionID" => $this->SessionID, "CategoryID" => $CategoryID));
		return $this->parsed_response ? $this->parsed_response['stat'] : FALSE;
	}
	
	/**
	 * @return string|false
	 * @uses request
	 * @link http://wiki.smugmug.com/display/SmugMug/smugmug.categories.rename+1.2.0
	 **/
	function categories_rename($CategoryID, $Name) {
		$this->request('smugmug.categories.rename', array("SessionID" => $this->SessionID, "CategoryID" => $CategoryID, "Name" => $Name));
		return $this->parsed_response ? $this->parsed_response['stat'] : FALSE;
	}
	
	/**
	 * @return array|false
	 * @uses request
	 * @link http://wiki.smugmug.com/display/SmugMug/smugmug.subcategories.get+1.2.0
	 **/
	function subcategories_get($CategoryID, $NickName = NULL, $SitePassword = NULL) {
		$this->request('smugmug.subcategories.get', array("SessionID" => $this->SessionID, "CategoryID" => intval($CategoryID), "NickName" => $NickName, "SitePassword" => $SitePassword));
		return $this->parsed_response ? $this->parsed_response['SubCategories'] : FALSE;
	}

	/**
	 * @return array|false
	 * @uses request
	 * @link http://wiki.smugmug.com/display/SmugMug/smugmug.subcategories.getAll+1.2.0
	 **/
	function subcategories_getAll($NickName = NULL, $SitePassword = NULL) {
		$this->request('smugmug.subcategories.getAll', array("SessionID" => $this->SessionID, "NickName" => $NickName, "SitePassword" => $SitePassword));
		return $this->parsed_response ? $this->parsed_response['SubCategories'] : FALSE;
	}
	
	/**
	 * @return integer|false
	 * @uses request
	 * @link http://wiki.smugmug.com/display/SmugMug/smugmug.subcategories.create+1.2.0
	 **/
	function subcategories_create($Name, $CategoryID) {
		$this->request('smugmug.subcategories.create', array("SessionID" => $this->SessionID, "Name" => $Name, "CategoryID" => $CategoryID));
		return $this->parsed_response ? $this->parsed_response['Subcategory']['id'] : FALSE;
	}
	
	/**
	 * @return string|false
	 * @uses request
	 * @link http://wiki.smugmug.com/display/SmugMug/smugmug.subcategories.delete+1.2.0
	 **/
	function subcategories_delete($SubCategoryID) {
		$this->request('smugmug.subcategories.delete', array("SessionID" => $this->SessionID, "SubCategoryID" => $SubCategoryID));
		return $this->parsed_response ? $this->parsed_response['stat'] : FALSE;
	}
	
	/**
	 * @return string|false
	 * @uses request
	 * @link http://wiki.smugmug.com/display/SmugMug/smugmug.subcategories.rename+1.2.0
	 **/
	function subcategories_rename($SubCategoryID, $Name) {
		$this->request('smugmug.subcategories.rename', array("SessionID" => $this->SessionID, "Name" => $Name, "SubCategoryID" => $SubCategoryID));
		return $this->parsed_response ? $this->parsed_response['stat'] : FALSE;
	}
}

?>
