<?php
// $Id$
//
// Mediashare by Jorn Lind-Nielsen (C) 2003.
//

define('mediashareAccessRequirementView',       1);
define('mediashareAccessRequirementEditAlbum',  2);
define('mediashareAccessRequirementEditMedia',  4);
define('mediashareAccessRequirementAddAlbum',   8);
define('mediashareAccessRequirementAddMedia',   16);
define('mediashareAccessRequirementEditAccess', 128);

define('mediashareAccessRequirementAddSomething',  mediashareAccessRequirementAddAlbum | mediashareAccessRequirementAddMedia);

define('mediashareAccessRequirementEditSomething', mediashareAccessRequirementEditAlbum | mediashareAccessRequirementEditMedia | mediashareAccessRequirementAddAlbum | mediashareAccessRequirementAddMedia);

define('mediashareAccessRequirementViewSomething', mediashareAccessRequirementView | mediashareAccessRequirementEditSomething);


/**
 * URL helpers
 */
function mediashareGetIntUrl($param, $args, $default)
{
    $i = isset($args[$param]) ? $args[$param] : FormUtil::getPassedValue($param);

    if ($i == '') {
        $i = $default;
    }

    return (int)$i;
}

function mediashareGetBoolUrl($param, $args, $default)
{
    $i = isset($args[$param]) ? $args[$param] : FormUtil::getPassedValue($param);

    if ($i == '') {
        $i = $default;
    } else {
        $i = $i == '0' ? false : true;
    }

    return $i;
}

function mediashareGetStringUrl($param, $args, $default = null)
{
    $s = isset($args[$param]) ? $args[$param] : FormUtil::getPassedValue($param);

    if ($s == '') {
        $s = $default;
    }

    return $s;
}

/**
 * Access control
 */
function mediashareGetAccessAPI()
{
    if (file_exists('modules/mediashare/localaccessapi.php')) {
        require_once 'modules/mediashare/localaccessapi.php';
    } else {
        require_once 'modules/mediashare/accessapi.php';
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

function mediashareAddAccess($album)
{
    $albumId = $album['id'];

    // Fetch access settings for this album
    if (($access = pnModAPIFunc('mediashare', 'user', 'getAlbumAccess', array('albumId' => $albumId))) === false) {
        return false;
    }

    // Fetch access settings for parent album
    $parentAccess = 0;
    if ($albumId > 1) {
        $parentAccess = pnModAPIFunc('mediashare', 'user', 'getAlbumAccess', array('albumId' => $album['parentAlbumId']));
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

    return $access;
}

/**
 * Other
 */
function mediashareStripKeywords($keywords)
{
    return preg_replace('/[^\w ]/i', '', $keywords);
}

function mediashareEnsureFolderExists($parentFolderID, $folders, $folderOffset)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    // End of recursion?
    if ($folderOffset == sizeof($folders) - 1) {
        return $parentFolderID;
    }

    $folderTitle = $folders[$folderOffset];

    // Get ID of existing folder
    $pntable = &pnDBGetTables();

    $foldersTable  = $pntable['mediashare_albums'];
    $foldersColumn = $pntable['mediashare_albums_column'];

    $sql = "SELECT $foldersColumn[id]
              FROM $foldersTable
             WHERE $foldersColumn[parentAlbumId] = '" . DataUtil::formatForStore($parentFolderID) . "'
               AND $foldersColumn[title] = '" . DataUtil::formatForStore($folderTitle) . "'";

    $result = DBUtil::executeSQL($sql);

    if ($result === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('common.EnsureFolderExists', 'Could not retrieve the folder information.'), $dom), 404);
    }

    // No ID => folder does not exist. Create it.
    if (!$result) {
        if (!($folderID = pnModAPIFunc('mediashare', 'edit', 'addAlbum', array('parentAlbumId' => $parentFolderID, 'title' => $folderTitle, 'description' => '', 'keywords' => '', 'summary' => '')))) {
            return false;
        }
    } else {
        $folderID = DBUtil::marshallObjects($result, array('id'));
        $folderID = (int)$folderID[0]['id'];
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
