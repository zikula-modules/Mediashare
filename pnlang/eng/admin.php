<?php
// ------------------------------------------------------------------------------------
// Translation for PostNuke Mediashare module
// Translation by: Jorn Wildt
// ------------------------------------------------------------------------------------

require_once('modules/mediashare/pnlang/eng/common.php');

define('_MSALLOWTEMPLATEOVERRIDE', 'Allow template override in albums?');
define('_MSAPIKEYFLICKR', 'Flickr API key');
define('_MSAPIKEYSMUGMUG', 'SmugMug API key');
define('_MSAPIKEYPHOTOBUCKET', 'Photobucket API key');
define('_MSAPIKEYPICASA', 'Picasa API key');
define('_MSAPPLYGLOBALTEMPLATE', 'Apply globally');
define('_MSAPPLYGLOBALTEMPLATECONFIRM', 'Overwrite all album templates');
define('_MSDEFAULTALBUMTEMPLATE', 'Default album template');
define('_MSDEFAULTSLIDESHOWTEMPLATE', 'Default slideshow template');
define('_MSDIRNOTWRITABLE', 'Cannot write to this directory.');
define('_MSGENERAL', 'General');
define('_MSGENERALSETUP', 'General setup');
define('_MSIMPORT', 'Import');
define('_MSMEDIADIR', 'Media upload dir.');
define('_MSMEDIADIRHELP', "This is where your media files are stored. Make sure it corresponds to a directory named 'mediashare' in PostNuke's top directory, and make sure it is writable by the webserver.");
define('_MSMEDIAHANDLERS', 'Media handlers');
define('_MSMEDIAHANDLERSINFO', 'The list below shows the available media handlers. These plugins creates thumbnails and takes care of displaying the various media items you add.');
define('_MSMEDIASOURCES', 'Media sources');
define('_MSMEDIASOURCESINFO', 'The list below shows the available media sources. These plugins implements the various ways you can add media to your albums.');
define('_MSMODULEDIR', 'Current module dir.');
define('_MSOPENBASEDIR', 'Open-base dir (PHP restriction)');
define('_MSPLUGINS', 'Plugins');
define('_MSPREVIEWSIZE', 'Preview size (pixels)');
define('_MSSCANFORPLUGINS', 'Scan for plugins');
define('_MSSINGLEALLOWEDSIZE', 'Max. size of a single image (kb)');
define('_MSTOTALALLOWEDSIZE', 'Max. size of all images for a single user (kb)');
define('_MSTHUMBNAILSIZE', 'Thumbnail size (pixels)');
define('_MSTMPDIR', 'Temporary dir.');
define('_MSTMPDIRHELP', "This is a directory that Mediashare uses for temporary storage when uploading files. Make sure it is writable by the webserver.");
define('_MSVFSDBSELECTION', 'Files in database');
define('_MSVFSDBSELECTIONHELP', 'Storing files in the database increases security and makes it possible to use multiple web servers at the cost of performance.');
define('_MSVFSDIRECTSELECTION', 'Files on local file system');
define('_MSVFSDIRECTSELECTIONHELP', 'Storing files in the local file system improves performance at the cost of some security and the ability to use multiple web servers.');
define('_MSSHARPEN', 'Enable thumbnail sharpening');
define('_MSSHARPENHELP', 'Sharpening of thumbnails and previews improves image quality but uses a lot of CPU resources');
define('_MSTHUMBNAILSTART', 'Show thumbnails');
define('_MSTHUMBNAILSTARTHELP', 'The default album display can either be a thumbnail overview or a one-picture display');

define('_MSREC_PAGETITLE', 'Regenerate thumbnails and previews');
define('_MSREC_INTRO', 'Regenerating all thumbnails and previews takes a good deal of time. This feature uses JavaScript to regenerate one file at a time without running into PHP\'s execution time limits. The iframe to the right is used for communication with the server. You can follow the progress in both the iframe and in the checkbox list below.');
define('_MSREC_RECALCULATE', 'Regenerate');

?>