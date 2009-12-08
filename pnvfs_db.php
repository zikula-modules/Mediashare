<?php
// $Id$
//
// Mediashare by Jorn Lind-Nielsen (C)
//


require_once 'modules/mediashare/common.php';

function mediashare_vfs_db_dump()
{
    $fileref = $_GET['ref'];

    // Retrieve image information
    if (!($media = pnModAPIFunc('mediashare', 'vfs_db', 'getMedia', array('fileref' => $fileref)))) {
        return false;
    }

    // Check access
    if (!mediashareAccessAlbum($media['albumId'], mediashareAccessRequirementView, null)) {
        return LogUtil::registerPermissionError();
    }

    // Some Mediashare users have reported this to make their setup work. The buffer may contain something
    // due to a buggy template or block
    while (@ob_end_clean());

    if (pnConfigGetVar('UseCompression') == 1) {
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
    if (get_magic_quotes_gpc()) {
        $cachedETag = stripslashes($cachedETag);
    }
    if ((empty($cachedDate) || $lastModifiedDate == $cachedDate) && '"' . $currentETag . '"' == $cachedETag) {
        header("HTTP/1.1 304 Not Modified");
        header("Status: 304 Not Modified");
        header("Expires: " . date('D, d M Y H:i:s T', time() + 180 * 24 * 3600)); // My PHP insists on Expires in 1981 as default!
        header('Pragma: cache'); // My PHP insists on putting a pragma "no-cache", so this is an attempt to avoid that
        header('Cache-Control: public');
        header("ETag: \"$media[modifiedDate]\"");
        return true;
    }

    header("Expires: " . date('D, d M Y H:i:s T', time() + 180 * 24 * 3600)); // My PHP insists on Expires in 1981 as default!
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
