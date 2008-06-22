<?php
// $Id: pnvfs_db.php,v 1.3 2007/06/24 14:05:44 jornlind Exp $
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2005.
// ----------------------------------------------------------------------
// For POST-NUKE Content Management System
// http://www.postnuke.com/
// ----------------------------------------------------------------------


require_once 'modules/mediashare/common.php';


function mediashare_vfs_db_dump()
{
  $fileref = $_GET['ref'];

    // Retrieve image information
  pnModAPILoad('mediashare', 'vfs_db');

  $media = pnModAPIFunc('mediashare', 'vfs_db', 'getMedia', array('fileref' => $fileref));
  if ($media === false)
    return mediashareErrorAPIGet();

    // Check access
  if (!mediashareAccessAlbum($media['albumId'], mediashareAccessRequirementView, null))
    return mediashareErrorPage(__FILE__, __LINE__, "You do not have access to this file");

    // Some Photoshare users have reported this to make their setup work. The buffer may contain something
    // due to a buggy template or block
  while (@ob_end_clean());

  if (pnConfigGetVar('UseCompression') == 1)
  {
    // With the "while (@ob_end_clean());" stuff above we are guranteed that no z-buffering is done
    // But(!) the "ob_start("ob_gzhandler");" made by pnAPI.php means a "Content-Encoding: gzip" is set.
    // So we need to reset this header since no compression is done
    header("Content-Encoding: identity");
  }

    // Check cached versus modified date

  $lastModifiedDate = date('D, d M Y H:i:s T', $media['modifiedDate']);
  $currentETag = $media['modifiedDate'];

  global $HTTP_SERVER_VARS;
  $cachedDate = (isset($HTTP_SERVER_VARS['HTTP_IF_MODIFIED_SINCE']) ? $HTTP_SERVER_VARS['HTTP_IF_MODIFIED_SINCE'] : null);
  $cachedETag = (isset($HTTP_SERVER_VARS['HTTP_IF_NONE_MATCH']) ? $HTTP_SERVER_VARS['HTTP_IF_NONE_MATCH'] : null);

    // If magic quotes are on then all query/post variables are escaped - so strip slashes to make a compare possible
    // - only cachedETag is expected to contain quotes
  if (get_magic_quotes_gpc())
    $cachedETag = stripslashes($cachedETag);

  if (    (empty($cachedDate) || $lastModifiedDate == $cachedDate)
      &&  '"'.$currentETag.'"' == $cachedETag)
  {
    header("HTTP/1.1 304 Not Modified");
    header("Status: 304 Not Modified");
    header("Expires: " . date('D, d M Y H:i:s T', time()+180*24*3600)); // My PHP insists on Expires in 1981 as default!
  	header('Pragma: cache'); // My PHP insists on putting a pragma "no-cache", so this is an attempt to avoid that
  	header('Cache-Control: public');
    header("ETag: \"$media[modifiedDate]\"");
    return true;
  }

  header("Expires: " . date('D, d M Y H:i:s T', time()+180*24*3600)); // My PHP insists on Expires in 1981 as default!
  header('Pragma: cache'); // My PHP insists on putting a pragma "no-cache", so this is an attempt to avoid that
  header('Cache-Control: public');
  header("ETag: \"$media[modifiedDate]\"");


    // Ensure correct content-type and a filename for eventual download
  header("Content-Type: $media[mimeType]");
  header("Content-Disposition: inline; filename=\"$media[title]\"");
  header("Last-Modified: $lastModifiedDate");

  header("Content-Length: " . strlen($media['data']));
  echo $media['data'];

  return true;
}