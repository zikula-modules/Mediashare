<?php


/**
 * A set of utility fields and functions for working with the Lightweight PHP Picasa API.  
 *
 * @author Cameron Hinkle
 */

class Cam_Util_PictureUtil {

	/* A commonly used base for forming query URLs.  Replace YOUR_USERNAME_HERE with the Picasa username for the default account. */
	static public $BASE_QUERY_URL = 'http://picasaweb.google.com/data/feed/api/user/YOUR_USERNAME_HERE';

	/* Your Picasa username.  Replace the value with the Picasa username for the default Picasa account. */
	static public $USER = 'YOUR_USERNAME_HERE';


	/**
	 * Returns a relative URL used for building a query string to retrieve an album.
	 * 
	 * @param	album	The id number for the album to build the query string for.
	 * @return	A string for builing a query string to request an album from Picasa.
	 */
	static public function getAlbumUrl ($album) {
		return "/pictures/album/".$album;
	}


	/**
	 * Returns part of a query string for requesting an album.
	 *
	 * @param	albumid		The id number of the album that the image is in.
	 * @param	imageid		The id number of the image to build the query string for.
	 * @return	A string that can be used to build a query string for requesting an image from Picasa.
	 */	

	static public function getImageUrl ($albumid, $imageid) {
		return "/pictures/album/".$albumid."/image/".$imageid;
	}


	/**
	 * Constructs a URL to the largest displayable version of a Picasa image.
	 *
	 * @param	contenturl	The $content field of a Picasa_Image object.
	 * @return	A string holding a URL to the image that can be used within an <img> tag.
	 */

	public function getLargeVersion($contentUrl) {
		$lastslash = strrpos($contentUrl,'/');
		return substr($contentUrl,0,$lastslash).'/s800'.substr($contentUrl,$lastslash);
	}


} 
