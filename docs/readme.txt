Mediashare (C) Jorn Wildt 2005
==============================

Mediashare is a Zikula based gallery that enables you to share your images,
videos, flash files and much more on the internet.

Mediashare is a complete rewrite of the code for Photoshare, and it is my hope 
that it will be faster, easier to use, and easier to modify and extend than
Photoshare ever was.

Jorn Wildt


REQUIREMENTS
============
* Zikula 1.x (or PostNuke version .76 or never)
* MySQL 4.x
* PHP's GD library for image manipulation.
* Rewrite URLs enabled in .htaccess files if you want to store media files
  in the database. This requires an Apache web server (although some plugins
  for IIS are said to exists - I do not know them myself).


FEATURE LIST
============
* Runs on Zikula systems with SAFE_MODE and OPEN_BASEDIR enabled 
  (this allows Mediashare to be installed on sites with quite 
   restricted PHP access).
* Edit albums and organize albums in albums.
* Automatic thumbnail creation as well as preview images.
* Supports many multimedia file formats
  - gif, png, jpg
  - Flash
  - QuickTime
  - Windows Media Player
  - RealPlayer
  - PDF
* Detailed access control.
* E-mail invitations with special links for access to locked albums
* Browsing by keywords (or tags - a'la www.flickr.com).
* Compatible with Gallery Remote Protocol 
  (see http://gallery.menalto.com/wiki/Gallery_Remote)
* Reordering of files by drag and drop.
* Browse albums and view items in slideshows.
* Zikula block for random media items display
* Overview page with latest albums, media files, most used keywords and more.
* File upload size restrictions (both single file as well a grand total).
* Files can be stored on local file system as well as in the database
* Modern tableless HTML design and use of Lightbox JavaScript library
* Zikula search enabled.
* Zikula API compatible.
* Zikula pnRender based (thereby templatable).
* Zikula hooks aware.
* Can be used from other modules (like the standard News and Pagesetter)
* Imports from Photoshare


UPGRADE
=======

To 3.4.2
* Do a normal upgrade
* If you are using MySQL 5.x then open the file pneditapi.php and search for 
  "MySQL switch" and then change (false) to (true). This will improve 
  performance of album creation by using stored procedures instead of PHP for
  nested set values calculation.

From 2.1.1 to 2.1.2
* Do a normal Zikula module upgrade.

From any version to 2.1.1
* Do a normal Zikula module upgrade.
* Goto Mediashare's admin panel (the plugin section) and "scan for plugins".


INSTALLATION
============
Start the same way as width any other Zikula module:

1) Copy the files into Zikula's "modules" directory.

2) Go to Zikula's module admin page and regenerate the list.

3) Install and activate the Mediashare module.

4) Try to create a new album - if it fails with the message
   "calling mediashareUpdateNestedSetValues() failed" then you probably have
   MySQL version 4.x - and Mediashare works best with MySQL 5.x. To fix this,
   open file pneditapi.php and search for "MySQL switch" and then 
   change (true) to (false).

If you are using short URLs then you may have to add the line below to your 
.htaccess file. This ensures media files are found in the right place.

  RewriteRule ^mediashare/.*/mediashare/(.+) mediashare/$1


CONFIGURATION
=============

1) Decide if you want to store media files on the local file system or in 
   the database.

   Normal user should store files in the local file system. This is much
   faster but slightly less secure. If you really need 100% bullet proof
   access control then store files in the database (see the section on
   security).

   Storing files in the database can make backup easier (no need to backup any
   thing else than the database) and it may be the only way to setup Mediashare
   on multiple web servers using the same database. But it slows down performance
   considerably.

1.1) Files on the local file system

     Create a directory named "mediashare" in the main Zikula directory. Make
     sure this directory is writable by the webserver. This will be the place for
     all the files that you upload.

     For SAFE_MODE reasons you may have to set the GID or UID bits on this 
     directory. Don't ask me why - but my webserver didn't work without.

     Go to Mediashare's admin page and write the full path to this directory as
     the "Media upload dir.".

1.2) Files in the database

     Add this line to your .htaccess file:

     RewriteRule ^mediashare/(vfsdb/[a-z0-9]+-[a-z]+\.[a-z]+)$ index.php?module=mediashare&type=vfs_db&func=dump&ref=$1 [PT]

     This converts /mediashare/vfsdb/xxxx file references to a Zikula 
     function call for Mediashare.

2) Make sure the "Temporary dir." points to a writable directory. This 
   directory will be used by Mediashare when converting and resizing media 
   files.



PERMISSIONS
===========

     Group          Component       Instance   Permission
     Users          mediashare::    .*         edit
     All groups     mediashare::    .*         read

This will give all users access to viewing published albums, and "Editors"
access to create albums.



UNINSTALL
=========
First uninstall like any other Zikula module. Then manually delete all media
files from the "mediashare" directory.



USAGE
=====
The very short guide is: 

- Create albums and start uploading media files into these.

- Browse albums and view their images in slideshows.


The album browsing URL is:

 http://somewhere/index.php?module=mediashare

The editing URL is:

  http://somewhere/index.php?module=Mediashare&type=edit&func=view&aid=1

Image URLs are:

  http://somewhere/mediashare/xxx

Where xxx is a random identifier (see the security chapter below)


You can also use [mediashare] in Zikula's menu - this leads to the 
top album.


USING WITH GALLERY REMOTE
=========================
The Menalto Gallery project has a Java based image uploader which can be used
together with Mediashare (tested with Gallery Remote 1.5). You can download 
the uploader here:

  http://gallery.menalto.com/wiki/Gallery_Remote


Install the uploader on your local machine and start it. First thing to do is
to add a URL for your Mediashare interface. USE THE STANDALONE GALLERY TYPE!
The URL is:

  http://YOURHOST/index.php?module=mediashare&type=remote&func=main&

BE VERY CAREFULL TO REMEMBER THE LAST AMPERSAND!

Now you are ready to go. That was easy :-)

This interface can probably also be used with some of the Gallery screensaver
programs (untested).


IMPORTING FROM PHOTOSHARE
=========================
You can import from Photoshare and remove Photoshare's copy of all 
images while still keeping the old URLs alive. Medishare will create a 
table that maps from Photoshare image URLs to Mediashare URLs.

1) Go to Mediashare's admin page and follow the import instructions. 
Albums are imported in alphabetical order. If the import fails, you will 
need to remove the file or album causing the problem - the next to be 
imported.

2) Backup Photoshare, it's datafiles, and the database.

3) Do NOT remove or deactivate the Photoshare module from the admin 
panel. Simply replace the file "photoshare/pnshow.php" with the new copy 
in "mediashare/photoshare/pnshow.php"

4) You can now remove Photoshare's image directory (if the images were 
stored in the file system) or the images from the Photoshare tables 
within the Zikula database. From your preferred database management 
tool, you can remove the (prefix)_photoshare_images table, remove the 
pn_imagedata and pn_thumbnaildata. If you are using MySQL's command 
line, you need to "alter table (prefix)_photoshare_images drop column 
pn_imagedata;" then "alter table (prefix)_photoshare_images drop column 
pn_thumbnaildata;".


MODIFYING
=========

-- Themes --
You can modify the looks by adding your own theme specific templates (as with
any other pnRender based module).

Just copy any of the files from "modules/mediashare/pntemplates/..." to 
"themes/YourTheme/templates/modules/mediashare/..." and edit the copies.

-- Templates --
The album viewer, thumbnail list, and the slideshow viewer supports templates 
selected by album. The templates are located in 
"modules/mediashare/pntemplates/Frontend/TemplateName/...". Add one "TemplateName" 
directory for each template you need and place the following files in it:

   album.html      - album viewer template
   thumbnails.html - thumbnail viewer template
   slideshow.html  - slideshow template
   head.html       - header template included in the slideshow viewer (for CSS and scripting)

-- Scriptaculous and Lightbox scripts --
Mediashare ships with it's own copy of the lightbox script used for the cool
image viewer effects. If you want to use another instance of the library then
copy the template "mediashare_include_lightbox.html" to your theme template
directory and modify it to suit your needs.

-- Access --
You can implement your completely own access control mechanism by copying
"accessapi.php" to "localaccessapi.php" and then modify that. No documentation
supplied but feel free to contact me if needed.


INTEGRATING MEDIASHARE WITH OTHER MODULES
=========================================
In order to use Mediashare's media item selector from other modules you must
do the following:

1) Make sure the "mediashare/pnjavascript/finditem.js" JavaScript file is
   included in the web page from which the media selector is opened. This 
   script supplies a function named "mediashareFindItem" which opens the media
   selector window when called.

   Example:

     <script type="text/javascript" 
             src="modules/mediashare/pnjavascript/finditem.js"></script>


2) Specify an HTML "id" attribute on the input element that Mediashare should
   paste the media tag or URL into. The actual ID is up to you. The input
   element may either be a text input or a textarea element (even works with
   the Xinha/HtmlArea editor).

   Examples:

     <textarea id="articleTextarea"></textarea>

     <input type="text" id="mediaURL"/>


3) Add a button (or other clickable element) with an "onclick" handler that
   calls mediashareFindItem(inputId, selectorURL). The "inputId" parameter 
   holds the ID of the input from step (2). The selector URL holds the URL to
   Mediashare's media selector window. You can get this URL from PHP/Zikula
   by calling:

     pnModUrl('mediashare', 'external', 'finditem', 
              array('url' => 'relative', 'mode' => 'url'));

   or

     pnModUrl('mediashare', 'external', 'finditem', 
              array('url' => 'relative', 'mode' => 'html'));

   The "url" and "mode" arguments specifies how the media selector should paste
   the result back into the external input. If "url" is "relative" then only
   the relative part of the media URL is pasted - otherwise the full absolute
   path is used. If "mode" is "html" then a complete media tag is pasted, 
   otherwise only the URL is pasted.

   In addition to "url" and "mode" you can set "onlymine=1" which will restrict
   the selection to albums of the current user.

   Example:

     <input type="button" value="Insert media item" 
            onclick="mediashareFindItem('articleTextarea',
                                        'http://...&url=relative&mode=html')">

4) Make sure to include the following on the pages that contains medias:

    <script type="text/javascript" src="modules/mediashare/pnjavascript/view.js"></script>

   This ensure Mediashare's popup windows works.


Mediashare does also have a media item selector for pnForms in .8. Contact me
directly if you are interested.


SECURITY
========
Mediashare bases it's security on three elements:

  1) Zikula's permission system

  2) Mediashare's access control

  3) Unguessable media file names.

1 - Users need READ access to view any item and EDIT access to add anything.
    See the install instructions in the beginning of this document.

2 - Mediashare handles view/add/edit access for both albums and media items.

3 - The last point is for performance reasons, but it depends on the setup. It
is only used when storing media files in the local file system.

Photoshare depended on Zikula's permissions when showing a single image. 
This meant loading both the Zikula API and the Mediashare API each time an 
image was displayed, and then streaming the image data through PHP. This 
really slowed things down.

Mediashare uses another technique (when storing files in the local file 
system): all images are stored in the filesystem in a place where the 
webserver can access them (in the "mediashare" directory). This means 
all items are accessible to everybody! 

To avoid everybody accessing the files we name them randomly with filenames 
that should not be possible to guess by outsiders. You will only get these 
filenames if you are able to browse the Mediashare albums containing them (and
thereby serving links to them).

This is *not* a completely bullet proof solution - but the best compromise
between performance and security. If you need more security then store the
images in the database.


PLUGINS
=======
Mediashare uses a plugin framework for managing the various file formats. The
plugins have not yet been documented - and won't until somebody asks for it. 
Please check the files pnmedia_XXXapi.php for examples.
