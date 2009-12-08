<?php
// $Id$
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2003.
// =======================================================================

define('mediashareAccessRequirementView',       1);
define('mediashareAccessRequirementEditAlbum',  2);
define('mediashareAccessRequirementEditMedia',  4);
define('mediashareAccessRequirementAddAlbum',   8);
define('mediashareAccessRequirementAddMedia',   16);
define('mediashareAccessRequirementEditAccess', 128);

define('mediashareAccessRequirementAddSomething',  mediashareAccessRequirementAddAlbum | mediashareAccessRequirementAddMedia);

define('mediashareAccessRequirementEditSomething', mediashareAccessRequirementEditAlbum | mediashareAccessRequirementEditMedia | mediashareAccessRequirementAddAlbum | mediashareAccessRequirementAddMedia);

define('mediashareAccessRequirementViewSomething', mediashareAccessRequirementView | mediashareAccessRequirementEditSomething);

// =======================================================================
// URL helpers
// =======================================================================
function mediashareGetIntUrl($param, &$args, $default)
{
    $i = isset($args[$param]) ? $args[$param] : FormUtil::getPassedValue($param);

    if ($i == '') {
        $i = $default;
    }

    return (int) $i;
}

function mediashareGetBoolUrl($param, &$args, $default)
{
    $i = (array_key_exists($param, $args) ? $args[$param] : FormUtil::getPassedValue($param));

    if ($i == '') {
        $i = $default;
    } else {
        $i = ($i == '0' ? false : true);
    }

    return $i;
}

function mediashareGetStringUrl($param, &$args, $default = null)
{
    $s = (array_key_exists($param, $args) ? $args[$param] : FormUtil::getPassedValue($param));

    if ($s == '') {
        $s = $default;
    }

    return $s;
}

// =======================================================================
// Access control
// =======================================================================
function mediashareGetAccessAPI()
{
    if (file_exists('modules/mediashare/localaccessapi.php')) {
        require_once ('modules/mediashare/localaccessapi.php');
    } else {
        require_once ('modules/mediashare/accessapi.php');
    }

    return mediashareCreateAccessAPI();
}

function mediashareAccessAlbum($albumId, $access, $viewKey = null)
{
    return pnModAPIFunc('mediashare', 'user', 'hasAlbumAccess',
                        array('albumId' => $albumId,
                              'access'  => $access,
                              'viewKey' => $viewKey));
}

function mediashareAccessItem($mediaId, $access, $viewKey = null)
{
    return pnModAPIFunc('mediashare', 'user', 'hasItemAccess',
                        array('mediaId' => $mediaId,
                              'access'  => $access,
                              'viewKey' => $viewKey));
}

function mediashareAccessUserRealName()
{
    $accessApi = mediashareGetAccessAPI();

    return $accessApi->hasUserRealNameAccess();
}

function mediashareAddAccess(&$render, $album)
{
    $albumId = $album['id'];

    // Fetch access settings for this album
    $access = pnModAPIFunc('mediashare', 'user', 'getAlbumAccess', array('albumId' => $albumId));
    if ($access === false) {
        return false;
    }

    // Fetch access settings for parent album
    if ($albumId > 1) {
        $parentAccess = pnModAPIFunc('mediashare', 'user', 'getAlbumAccess', array('albumId' => $album['parentAlbumId']));
    } else {
        $parentAccess = 0;
    }

    if ($parentAccess === false) {
        return false;
    }

    $access = array(
        'hasEditAlbumAccess'  => ($access & mediashareAccessRequirementEditAlbum) != 0,
        'hasEditMediaAccess'  => ($access & mediashareAccessRequirementEditMedia) != 0 && $album['allowMediaEdit'],
        'hasAddAlbumAccess'   => ($access & mediashareAccessRequirementAddAlbum) != 0,
        'hasAddMediaAccess'   => ($access & mediashareAccessRequirementAddMedia) != 0 && $album['allowMediaEdit'],
        'hasEditAccessAccess' => ($access & mediashareAccessRequirementEditAccess) != 0,
        'hasAnyEditAccess'    => ($access & mediashareAccessRequirementEditSomething) != 0,
        'hasParentAccess'     => ($parentAccess & mediashareAccessRequirementEditSomething) != 0
    );

    $render->assign('access', $access);

    return true;
}

// =======================================================================
// Other
// =======================================================================
function mediashareStripKeywords($keywords)
{
    return preg_replace('/[^\w ]/i', '', $keywords);
}

function mediashareEnsureFolderExists($parentFolderID, $folders, $folderOffset)
{
    // End of recursion?
    if ($folderOffset == sizeof($folders) - 1) {
        return $parentFolderID;
    }

    $folderTitle = $folders[$folderOffset];

    // Get ID of existing folder
    list ($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $foldersTable  = $pntable['mediashare_albums'];
    $foldersColumn = & $pntable['mediashare_albums_column'];

    $sql = "SELECT $foldersColumn[id]
              FROM $foldersTable
             WHERE $foldersColumn[parentAlbumId] = '" . DataUtil::formatForStore($parentFolderID) . "'
               AND $foldersColumn[title] = '" . DataUtil::formatForStore($folderTitle) . "'";

    $result = $dbconn->execute($sql);

    if ($dbconn->errorNo() != 0) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('common.EnsureFolderExists', 'Could not retrieve the folder information.'), $dom), 404);
    }

    // No ID => folder does not exist. Create it.
    if ($result->EOF) {
        if (!($folderID = pnModAPIFunc('mediashare', 'edit', 'addAlbum', array('parentAlbumId' => $parentFolderID, 'title' => $folderTitle, 'description' => '', 'keywords' => '', 'summary' => '')))) {
            return false;
        }
    } else {
        $folderID = $result->fields[0];
    }

    // Recursive to ensure sub-folders exists
    return mediashareEnsureFolderExists($folderID, $folders, $folderOffset + 1);
}

// Convert media item reference to name of virtual file system manager
function mediashareGetVFSHandlerName($fileref)
{
    if (substr($fileref, 0, 6) == 'vfsdb/') {
        return 'db'; // Stored in database
    } else {
        return 'fsdirect'; // Stored in local file system
    }
}

function mediashareLoadLightbox()
{
    if (file_exists('javascript/ajax/prototype.js')) {
        // Use Zikula scripts if available
        $scripts = array('javascript/ajax/prototype.js',
                         'javascript/ajax/scriptaculous.js?load=effects,dragdrop,builder',
                         'javascript/ajax/lightbox.js');
        PageUtil::addVar('stylesheet', 'javascript/ajax/lightbox/lightbox.css');
        PageUtil::addVar('javascript', $scripts);

    } else {
        $scripts = array('modules/mediashare/lightbox/js/prototype.js',
                         'modules/mediashare/lightbox/js/scriptaculous.js?load=effects',
                         'modules/mediashare/lightbox/js/lightbox.js');
        PageUtil::addVar('stylesheet', 'modules/mediashare/lightbox/css/lightbox.css');
        PageUtil::addVar('javascript', $scripts);
    }
}
