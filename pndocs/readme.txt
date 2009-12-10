
  MEDIASHARE
  MEDIA SHARING AND GALLERY MODULE FOR ZIKULA
  Mediashare (C) Jorn Wildt

    Mediashare is a Zikula based gallery that enables you to share your images,
    videos, flash files and much more on the internet.

    I hope you find this application usefull for your website.

    Jorn Wildt


  REQUIREMENTS

    * Zikula 1.x
    * MySQL 4.x (MySQL 5.x performs better)
    * PHP's GD library for image manipulation.
    * Rewrite URLs enabled in .htaccess files if you want to store media files
      in the database. This requires an Apache web server (although some plugins
      for IIS are said to exists - I do not know them myself).


  FEATURE LIST

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
    * Browsing by keywords (or tags - a'la Flickr).
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
