<?php
/* Last updated with phpSmug 1.0.2
 *
 * This example file shows you how to get a list of public albums for a 
 * particular SmugMug user, using their nickname.
 *
 * You'll want to replace:
 * - <API KEY> with one provided by SmugMug: http://www.smugmug.com/hack/apikeys 
 * - <APP NAME/VER (URL)> with your application name, version and URL
 * - <NICKNAME> with a SmugMug nickname.
 *
 * The <APP NAME/VER (URL)> is NOT required, but it's encouraged as it will
 * allow SmugMug diagnose any issues users may have with your application if
 * they request help on the SmugMug forums.
 */
require_once("phpSmug.php");

$f = new phpSmug("<API KEY>", "<APP NAME/VER (URL)>");

$f->login_anonymously();

$albums = $f->albums_get('<NICKNAME>');

foreach ($albums as $album) {
	echo $album['Title']." (ID: ".$album['id'].")<br />";
}    

?>