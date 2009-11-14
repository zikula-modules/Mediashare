<?php
require_once 'Picasa.php';
require_once 'Cam/Util/PictureUtil.php';
require_once 'Picasa/Image.php';
require_once 'Picasa/ImageCollection.php';

/**
 * Helper functions for fetching information from Picasa's image service.  Uses the
 * Lightweight PHP Picasa API.  See {@link http://www.cameronhinkle.com/} for more information.
 *
 * @author Cameron Hinkle
 */ 

class Cam_Service_PictureService {

 
public function __construct() {
}


/**
 * Retrieves a specified image from a specified Picasa album.
 * 
 * @param string $albumid   A string holding the id number for the album that the image is in.
 * @param string $imageid   A string holding the id number for the image to return.
 * @return {@link Picasa_Image}  A Picasa_Image object representing the requested image or null if it is not found.
 */
 
public function getImageFromAlbum ($albumid,$imageid) {
	return new Picasa_Image(Cam_Util_PictureUtil::$BASE_QUERY_URL.'/albumid/'.$albumid.'/photoid/'.$imageid);
}


/**
 * Retrieves an entire Picasa album.
 * 
 * @param	albumid		A string holding the id number for the album to retrieve.
 * @return	A Picasa_Album representing the requested album.  All images in the album
 *		will be populated into the oject.  Returns null if the album was not found.
 */
 
public function getAlbumFromId ($albumid) {
	return new Picasa_Album(Cam_Util_PictureUtil::$BASE_QUERY_URL.'/albumid/'.$albumid);
}


/**
 * Retrieves all albums associated with the default account specified in the Cam_Util_PictureUtil class.
 *
 * @return	A Picasa_Account object representing the default account.  The Account object will have
 * 		a Picasa_Album object for each album in the account, but the album objects that are 
 *		generated will not be populated with the images inside each album.  This is done for
 *		speed reasons.  Returns null if the account is not found.
 */

public function getAlbumsForDefaultAccount() {
	return new Picasa_Account(Cam_Util_PictureUtil::$BASE_QUERY_URL.'?kind=album');
}


/**
 * Retrieves a specified number of images from the default account, ordered by the upload date.
 *
 * @param	num	The number of images to return.
 * @return	A Picasa_ImageCollection object containing the most recently uploaded images.  If the 
 * 		num value is greater than the total number of images in the account, all images from
 *		the account are returned within the Picasa_ImageCollection object.
 */

public function getRecentPosts($num) {
	$queryUrl = Cam_Util_PictureUtil::$BASE_QUERY_URL.'?kind=photo';

	if ($num != null) {
		$queryUrl = $queryUrl.'&max-results='.$num;
	}
	return new Picasa_ImageCollection($queryUrl);

}


/** 
 * Randomly selects images to retrieve from the default account.
 *
 * @param	sizeofpool	The number of images to pull from.  The larger the number, the poorer
 *				the performance of the function.  However, the larger the number, the
 *				less chance of returning two identical images.
 * @param	numberofimages	The number of images to return.
 * @return	An array of Picasa_Image objects.
 */

public function getRandomImages($sizeofpool=100,$numberofimages=1) {
	// Seed the random number
	srand((double)microtime()*1000000); 

	// Get the most recent posts
	$poolCollection = $this->getRecentPosts($sizeofpool);
	$pool = $poolCollection->getImages();

	$images = array();

	for ($i=0;$i<$numberofimages;$i++) {
		$selected = rand(0,$sizeofpool-1);
		$images[$i] = $pool[$selected];
	}

	return $images;
}


/**
 * Does the same thing as getRandomImages, but only selects images where the image is wider than it is tall.
 * This is useful for display purposes.  See an example of where this function is used on the homepage of
 * http://www.cameronhinkle.com/.  
 * 
 * In order to make sure the width restriction doesn't cause an infinite loop, a threshold is set at 
 * twice the value of the numberofimages paramerter.  In other words, this function will look for wide 
 * images until it has found the value specified in $numberofimages OR until it encounters a tall
 * image $numberofimages * 2 times.  For this reason, this method _is not guarenteed to return an array
 * the size of $numberofimages_.  It may be less, you should do bounds checking on the returned array.
 *
 * @param	sizeofpool	The number of images to pull from.  The larger the number, the poorer
 *				the performance of the function.  However, the larger the number, the
 *				less chance of returning two identical images.
 * @param	numberofimages	The number of images to return.
 * @return	An array of Picasa_Image objects.
 */

public function getRandomWideImages($sizeofpool=100,$numberofimages=1) {
	srand((double)microtime()*1000000); 

	$poolCollection = $this->getRecentPosts($sizeofpool);
	$pool = $poolCollection->getImages();

	$images = array();
	
	/* Count the number of times a tall image is encountered and stop the loop after hitting
         * a number of tall images.  The number that is chosen to stop at is twice the number 
 	 * of requested images.  In case there are no wide images, this still stops the loop.
         */
	$misses = 0;
	for ($i=0;$i<$numberofimages && $misses<$numberofimages*2;$i++) {
		$selected = rand(0,$sizeofpool-1);
		if ((int) $pool[$selected]->getWidth() > (int) $pool[$selected]->getHeight()) {
			$images[$i] = $pool[$selected];
		} else {
			$i = $i - 1;
			$misses++;
		}
	}

	return $images;
}
	
}

