<?php
// $Id$
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2003.
// =======================================================================


define('mediashareAccessRequirementView', 1);
define('mediashareAccessRequirementEditAlbum', 2);
define('mediashareAccessRequirementEditMedia', 4);
define('mediashareAccessRequirementAddAlbum', 8);
define('mediashareAccessRequirementAddMedia', 16);
define('mediashareAccessRequirementEditAccess', 128);

define('mediashareAccessRequirementAddSomething', mediashareAccessRequirementAddAlbum | mediashareAccessRequirementAddMedia);

define('mediashareAccessRequirementEditSomething', mediashareAccessRequirementEditAlbum | mediashareAccessRequirementEditMedia | mediashareAccessRequirementAddAlbum | mediashareAccessRequirementAddMedia);

define('mediashareAccessRequirementViewSomething', mediashareAccessRequirementView | mediashareAccessRequirementEditSomething);

// =======================================================================
// URL helpers
// =======================================================================


function mediashareGetIntUrl($param, &$args, $default)
{
    $i = (array_key_exists($param, $args) ? $args[$param] : pnVarCleanFromInput($param));

    if ($i == '') {
        $i = $default;
    }

    return (int) $i;
}

function mediashareGetBoolUrl($param, &$args, $default)
{
    $i = (array_key_exists($param, $args) ? $args[$param] : pnVarCleanFromInput($param));

    if ($i == '') {
        $i = $default;
    } else {
        $i = ($i == '0' ? false : true);
    }

    return $i;
}

function mediashareGetStringUrl($param, &$args, $default = null)
{
    $s = (array_key_exists($param, $args) ? $args[$param] : pnVarCleanFromInput($param));

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
    if (!pnModAPILoad('mediashare', 'user')) {
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare user API');
    }

    return pnModAPIFunc('mediashare', 'user', 'hasAlbumAccess', array('albumId' => $albumId, 'access' => $access, 'viewKey' => $viewKey));
}

function mediashareAccessItem($mediaId, $access, $viewKey = null)
{
    if (!pnModAPILoad('mediashare', 'user')) {
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare user API');
    }

    return pnModAPIFunc('mediashare', 'user', 'hasItemAccess', array('mediaId' => $mediaId, 'access' => $access, 'viewKey' => $viewKey));
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
        return mediashareErrorAPIGet();
    }

    $access = array(
        'hasEditAlbumAccess' => ($access & mediashareAccessRequirementEditAlbum) != 0,
        'hasEditMediaAccess' => ($access & mediashareAccessRequirementEditMedia) != 0 && $album['allowMediaEdit'],
        'hasAddAlbumAccess' => ($access & mediashareAccessRequirementAddAlbum) != 0,
        'hasAddMediaAccess' => ($access & mediashareAccessRequirementAddMedia) != 0 && $album['allowMediaEdit'],
        'hasEditAccessAccess' => ($access & mediashareAccessRequirementEditAccess) != 0,
        'hasAnyEditAccess' => ($access & mediashareAccessRequirementEditSomething) != 0,
        'hasParentAccess' => ($parentAccess & mediashareAccessRequirementEditSomething) != 0);

    $render->assign('access', $access);

    return true;
}

// =======================================================================
// Error handling
// =======================================================================


function mediashareErrorPage($file, $line, $msg)
{
    if ($file == null || !pnSecAuthAction(0, 'mediashare::', '', ACCESS_ADMIN)) {
        $text = $msg;
    } else {
        $text = "$file($line): $msg";
    }

    $text = pnVarPrepForDisplay($text);

    $smarty = new pnRender('mediashare');
    $smarty->caching = false;
    $smarty->assign('errorMessage', $text);
    return $smarty->fetch('mediashare_error.html');
}

function mediashareErrorAPI($file, $line, $msg, $setSession = true)
{
    global $mediashareErrorMessageAPI;

    if ($file == null || !pnSecAuthAction(0, 'mediashare::', '', ACCESS_ADMIN)) {
        $mediashareErrorMessageAPI = $msg;
    } else {
        $mediashareErrorMessageAPI = "$file($line): $msg";
    }

    if ($setSession) {
        pnSessionSetVar('errormsg', $mediashareErrorMessageAPI);
    }

    return false;
}

function mediashareErrorAPIGet()
{
    global $mediashareErrorMessageAPI;

    $smarty = new pnRender('mediashare');
    $smarty->caching = false;
    $smarty->assign('errorMessage', $mediashareErrorMessageAPI);
    return $smarty->fetch('mediashare_error.html');
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

    $foldersTable = $pntable['mediashare_albums'];
    $foldersColumn = & $pntable['mediashare_albums_column'];

    $sql = "SELECT
		            $foldersColumn[id]
		          FROM
		            $foldersTable
		          WHERE $foldersColumn[parentAlbumId] = '" . pnVarPrepForStore($parentFolderID) . "'
		            AND $foldersColumn[title] = '" . pnVarPrepForStore($folderTitle) . "'";

    $result = $dbconn->execute($sql);

    if ($dbconn->errorNo() != 0) {
        return mediashareErrorPage(__FILE__, __LINE__, "'EnsureFolderExists' failed while executing: $sql");
    }

    // No ID => folder does not exist. Create it.
    if ($result->EOF) {
        $folderID = pnModAPIFunc('mediashare', 'edit', 'addAlbum', array('parentAlbumId' => $parentFolderID, 'title' => $folderTitle, 'description' => '', 'keywords' => '', 'summary' => ''));

        if (folderID === false) {
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
        // Use PN .8 scripts
        $scripts = array('javascript/ajax/prototype.js', 'javascript/ajax/scriptaculous.js?load=effects,dragdrop,builder', 'javascript/ajax/lightbox.js');
        PageUtil::addVar('stylesheet', 'javascript/ajax/lightbox/lightbox.css');
        PageUtil::addVar('javascript', $scripts);
    } else {
        $scripts = array('modules/mediashare/lightbox/js/prototype.js', 'modules/mediashare/lightbox/js/scriptaculous.js?load=effects', 'modules/mediashare/lightbox/js/lightbox.js');
        PageUtil::addVar('stylesheet', 'modules/mediashare/lightbox/css/lightbox.css');
        PageUtil::addVar('javascript', $scripts);
    }
}

