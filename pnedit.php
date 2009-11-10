<?php
// $Id: pnedit.php,v 1.44 2008/06/19 19:35:25 jornlind Exp $
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2005.
// =======================================================================


require_once 'modules/mediashare/common-edit.php';
require_once 'modules/mediashare/elfisk/elfisk_common.php';
require_once 'modules/mediashare/elfisk/elfiskRender.class.php';

// =======================================================================
// View album in edit-mode
// =======================================================================


function mediashare_edit_view($args)
{
    $dom = ZLanguage::getModuleDomain('Mediashare');

    $albumId = mediashareGetIntUrl('aid', $args, 1);
    $page = mediashareGetIntUrl('page', $args, 0);
    $showAll = mediashareGetBoolUrl('showall', $args, false);

    // Check access
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementEditSomething, '')) {
        return mediashareErrorPage(__FILE__, __LINE__, __('You do not have access to this feature', $dom));
    }

    if (!pnUserLoggedIn()) {
        return mediashareErrorPage(__FILE__, __LINE__, __('You must be logged in to use this feature', $dom));
    }

    if (!pnModAPILoad('mediashare', 'user')) {
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare user API');
    }

    // Check multi-edit buttons
    $selectedMediaId = pnVarCleanFromInput('selectedMediaId');
    if ((isset($_POST['multiedit_x']) || isset($_POST['multidelete_x']) || isset($_POST['multimove_x'])) && count($selectedMediaId) > 0) {
        $mediaIdList = implode(',', $selectedMediaId);
        if (isset($_POST['multiedit_x'])) {
            $func = 'multieditmedia';
        } else if (isset($_POST['multidelete_x'])) {
            $func = 'multideletemedia';
        } else {
            $func = 'multimovemedia';
        }

        $url = pnModUrl('mediashare', 'edit', $func, array('mid' => $mediaIdList, 'aid' => $albumId));
        pnRedirect($url);
        return true;
    }

    // Fetch current album


    $album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId));
    if ($album === false) {
        return mediashareErrorAPIGet();
    }
    if ($album === true) {
        return mediashareErrorPage(__FILE__, __LINE__, 'Unknown album');
    }
    // Fetch subalbums


    $subAlbums = pnModAPIFunc('mediashare', 'user', 'getSubAlbums', array('albumId' => $albumId, 'access' => mediashareAccessRequirementEditSomething));
    if ($subAlbums === false) {
        return mediashareErrorAPIGet();
    }
    // Fetch media items


    $items = pnModAPIFunc('mediashare', 'user', 'getMediaItems', array('albumId' => $albumId));
    if ($items === false) {
        return mediashareErrorAPIGet();
    }
    $render = new elfiskRender('mediashare');
    $render->caching = false;
    $render->assign('album', $album);
    $render->assign('subAlbums', $subAlbums);
    $render->assign('mediaItems', $items);
    $render->assign('thumbnailSize', pnModGetVar('mediashare', 'thumbnailSize'));
    if (!mediashareAddAccess($render, $album)) {
        return mediashareErrorAPIGet();
    }
    return $render->fetch('mediashare_edit_view.html');
}

// =======================================================================
// Add / edit album
// =======================================================================


global $mediashare_albumFields;
$mediashare_albumFields = array('title' => array('type' => 'string'), 'keywords' => array('type' => 'string'), 'summary' => array('type' => 'string'), 'description' => array('type' => 'string'), 'template' => array('type' => 'string'), 'extappURL' => array('type' => 'string'));

function mediashare_edit_addalbum($args)
{
    $dom = ZLanguage::getModuleDomain('Mediashare');

    $albumId = mediashareGetIntUrl('aid', $args, 1);

    // Check access
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementAddAlbum, '')) {
        return mediashareErrorPage(__FILE__, __LINE__, __('You do not have access to this feature', $dom));
    }
    if (isset($_POST['saveButton'])) {
        return mediashareAddAlbum($args);
    }
    if (isset($_POST['cancelButton'])) {
        pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
        return true;
    }

    if (!pnModAPILoad('mediashare', 'user')) {
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare user API');
    }

    // Get parent album info (ignore unknown parent => this means we add a top most album)
    $album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId));
    if ($album === false) {
        return mediashareErrorAPIGet();
    }

    $render = new elfiskRender('mediashare');

    $render->caching = false;
    $render->assign('album', $album);
    $render->assign('template', pnModGetVar('mediashare', 'defaultAlbumTemplate'));
    $render->assign('disableTemplateOverride', pnModGetVar('mediashare', 'allowTemplateOverride') ? false : true);

    return $render->fetch('mediashare_edit_addalbum.html');
}

function mediashareAddAlbum($args)
{
    $dom = ZLanguage::getModuleDomain('Mediashare');

    if (!pnSecConfirmAuthKey()) {
        return mediashareErrorPage(__FILE__, __LINE__, __('Unknown authentication key: you cannot submit the same form twice.', $dom));
    }

    $parentAlbumId = mediashareGetIntUrl('aid', $args, 1);

    if (!pnModAPILoad('mediashare', 'edit')) {
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare edit API');
    }
    $newAlbumID = pnModAPIFunc('mediashare', 'edit', 'addAlbum', array(
        'title' => pnVarCleanFromInput('title'),
        'keywords' => pnVarCleanFromInput('keywords'),
        'summary' => pnVarCleanFromInput('summary'),
        'description' => pnVarCleanFromInput('description'),
        'template' => pnVarCleanFromInput('template'),
        'extappURL' => pnVarCleanFromInput('extappURL'),
        'parentAlbumId' => $parentAlbumId));
    if ($newAlbumID === false) {
        return mediashareErrorAPIGet();
    }
    pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $newAlbumID)));
    return true;
}

function mediashare_edit_editalbum($args)
{
    $dom = ZLanguage::getModuleDomain('Mediashare');

    $albumId = mediashareGetIntUrl('aid', $args, 1);

    if (isset($_POST['saveButton'])) {
        return mediashareUpdateAlbum($args);
    }
    if (isset($_POST['cancelButton'])) {
        pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
        return true;
    }

    // Check access
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementEditAlbum, ''))
        return mediashareErrorPage(__FILE__, __LINE__, __('You do not have access to this feature', $dom));

    if (!pnModAPILoad('mediashare', 'user'))
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare user API');

    // Get album info
    $album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId, 'enableEscape' => false));
    if ($album === false) {
        return mediashareErrorAPIGet();
    }

    $render = new elfiskRender('mediashare');

    $render->caching = false;
    $render->assign('album', $album);
    $render->assign($album);
    $render->assign('disableTemplateOverride', pnModGetVar('mediashare', 'allowTemplateOverride') ? false : true);

    return $render->fetch('mediashare_edit_editalbum.html');
}

function mediashareUpdateAlbum($args)
{
    $dom = ZLanguage::getModuleDomain('Mediashare');
    if (!pnSecConfirmAuthKey()) {
        return mediashareErrorPage(__FILE__, __LINE__, __('Unknown authentication key: you cannot submit the same form twice.', $dom));
    }

    $albumId = mediashareGetIntUrl('aid', $args, 1);

    // Check access
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementEditAlbum, '')) {
        return mediashareErrorPage(__FILE__, __LINE__, __('You do not have access to this feature', $dom));
    }

    if (!pnModAPILoad('mediashare', 'edit')) {
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare edit API');
    }

    global $mediashare_albumFields;
    $values = elfisk_decodeInput($mediashare_albumFields);

    $ok = pnModAPIFunc('mediashare', 'edit', 'updateAlbum', $values + array('albumId' => $albumId));
    if ($ok === false) {
        return mediashareErrorAPIGet();
    }

    pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
    return true;
}

function mediashare_edit_deleteAlbum($args)
{
    $dom = ZLanguage::getModuleDomain('Mediashare');
    $albumId = mediashareGetIntUrl('aid', $args, 1);

    if (isset($_POST['okButton'])) {
        return mediashareDeleteAlbum($args);
    }

    if (isset($_POST['cancelButton'])) {
        pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
        return true;
    }

    // Check access
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementEditAlbum, '')) {
        return mediashareErrorPage(__FILE__, __LINE__, __('You do not have access to this feature', $dom));
    }
    if (!pnModAPILoad('mediashare', 'user')) {
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare user API');
    }
    // Get album info
    $album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId));
    if ($album === false) {
        return mediashareErrorAPIGet();
    }

    $render = new elfiskRender('mediashare');

    $render->caching = false;
    $render->assign('album', $album);

    return $render->fetch('mediashare_edit_deletealbum.html');
}

function mediashareDeleteAlbum($args)
{
    $dom = ZLanguage::getModuleDomain('Mediashare');
    if (!pnSecConfirmAuthKey()) {
        return mediashareErrorPage(__FILE__, __LINE__, __('Unknown authentication key: you cannot submit the same form twice.', $dom));
    }

    $albumId = mediashareGetIntUrl('aid', $args, 1);

    // Check access
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementEditAlbum, '')) {
        return mediashareErrorPage(__FILE__, __LINE__, __('You do not have access to this feature', $dom));
    }

    if (!pnModAPILoad('mediashare', 'user')) {
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare user API');
    }
    // Get album info
    $album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId));
    if ($album === false) {
        return mediashareErrorAPIGet();
    }
    if (!pnModAPILoad('mediashare', 'edit')) {
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare edit API');
    }
    $ok = pnModAPIFunc('mediashare', 'edit', 'deleteAlbum', array('albumId' => $albumId));
    if ($ok === false) {
        return mediashareErrorAPIGet();
    }
    pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $album['parentAlbumId'])));
    return true;
}

// =======================================================================
// Move album
// =======================================================================


function mediashare_edit_movealbum($args)
{
    $dom = ZLanguage::getModuleDomain('Mediashare');

    $albumId = mediashareGetIntUrl('aid', $args, 1);

    if ($albumId == 1) {
        return mediashareErrorPage(__FILE__, __LINE__, 'Cannot move top album');
    }
    // Check access
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementEditAccess, ''))
        return mediashareErrorPage(__FILE__, __LINE__, __('You do not have access to this feature', $dom));

    if (isset($_POST['saveButton'])) {
        return mediashareUpdateMoveAlbum($args);
    }

    if (isset($_POST['cancelButton'])) {
        pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
        return true;
    }

    if (!pnModAPILoad('mediashare', 'user')) {
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare user API');
    }
    // Fetch current album


    $album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId));
    if ($album === false) {
        return mediashareErrorAPIGet();
    }

    $render = new elfiskRender('mediashare');
    $render->caching = false;
    $render->assign('album', $album);

    return $render->fetch('mediashare_edit_movealbum.html');
}

function mediashareUpdateMoveAlbum($args)
{
    $albumId = mediashareGetIntUrl('aid', $args, 1);
    $dstAlbumId = mediashareGetIntUrl('daid', $args, 1);

    if (!pnModAPILoad('mediashare', 'edit')) {
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare user API');
    }
    $ok = pnModAPIFunc('mediashare', 'edit', 'moveAlbum', array('albumId' => $albumId, 'dstAlbumId' => $dstAlbumId));
    if ($ok === false) {
        return mediashareErrorAPIGet();
    }
    pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
    return true;
}

// =======================================================================
// Add / edit media items
// =======================================================================


function mediashare_edit_addmedia($args)
{
    $dom = ZLanguage::getModuleDomain('Mediashare');

    $albumId = mediashareGetIntUrl('aid', $args, 1);
    $sourceName = mediashareGetStringUrl('source', $args);

    if (!pnModAPILoad('mediashare', 'user')) {
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare user API');
    }
    if (!pnModAPILoad('mediashare', 'sources')) {
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare sources API');
    }
    // Check access
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementAddMedia, '')) {
        return mediashareErrorPage(__FILE__, __LINE__, __('You do not have access to this feature', $dom));
    }
    // Get parent album info (ignore unknown parent => this means we add to a top most album)
    $album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId));
    if ($album === false) {
        return mediashareErrorAPIGet();
    }
    // Get media sources
    $sources = pnModAPIFunc('mediashare', 'sources', 'getSources');
    if ($sources === false) {
        return mediashareErrorAPIGet();
    }
    if (count($sources) == 0) {
        return mediashareErrorPage(__FILE__, __LINE__, __('No media sources found. You need to go to the admin part and make a scan for media sources.', $dom));
    }
    if ($sourceName == '') {
        $sourceName = $sources[0]['name'];
    }
    // Find current source
    $source = null;
    foreach ($sources as $s) {
        if ($s['name'] == $sourceName) {
            $source = $s;
        }
    }

    $selectedSourceFile = "source_{$sourceName}";
    if (!pnModLoad('mediashare', $selectedSourceFile)) {
        return mediashareErrorPage(__FILE__, __LINE__, "Failed to load Mediashare $selectedSourceFile file");
    }

    $sourceHtml = pnModFunc('mediashare', $selectedSourceFile, 'view');
    if ($sourceHtml === false || $sourceHtml === true) {
        return $sourceHtml;
    }

    $render = new elfiskRender('mediashare');

    $render->caching = false;
    $render->assign('album', $album);
    $render->assign('sources', $sources);
    $render->assign('selectedSource', $source);
    $render->assign('selectedSourceName', $sourceName);
    $render->assign('selectedSourceFile', $selectedSourceFile);
    $render->assign('sourceHtml', $sourceHtml);

    return $render->fetch('mediashare_edit_addmedia.html');
}

global $mediashare_itemFields;
$mediashare_itemFields = array('title' => array('type' => 'string'), 'keywords' => array('type' => 'string'), 'description' => array('type' => 'string'));

function mediashare_edit_edititem($args)
{
    $dom = ZLanguage::getModuleDomain('Mediashare');
    $mediaId = mediashareGetIntUrl('mid', $args, 0);

    $item = pnModAPIFunc('mediashare', 'user', 'getMediaItem', array('mediaId' => $mediaId, 'enableEscape' => false));
    if ($item === false) {
        return mediashareErrorAPIGet();
    }

    $albumId = $item['parentAlbumId'];

    if (isset($_GET['back']) && $_GET['back'] == 'browse') {
        $backUrl = pnModURL('mediashare', 'user', 'browse', array('aid' => $albumId, 'mid' => $mediaId));
    } else {
        $backUrl = pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId));
    }

    if (isset($_POST['saveButton'])) {
        return mediashareUpdateItem($args, $backUrl);
    }

    if (isset($_POST['cancelButton'])) {
        pnRedirect($backUrl);
        return true;
    }

    if (!pnModAPILoad('mediashare', 'user')) {
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare user API');
    }

    // Do late access check so we can get "unknown item" error message from 'getMediaItem'
    if (!mediashareAccessItem($mediaId, mediashareAccessRequirementEditMedia, '')) {
        return mediashareErrorPage(__FILE__, __LINE__, __('You do not have access to this feature', $dom));
    }

    $album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $item['parentAlbumId']));
    if ($album === false) {
        return mediashareErrorAPIGet();
    }

    $render = new elfiskRender('mediashare');

    $render->caching = false;
    $render->assign($item);
    $render->assign('item', $item);
    $render->assign('album', $album);

    return $render->fetch('mediashare_edit_edititem.html');
}

function mediashareUpdateItem($args, $backUrl)
{
    $dom = ZLanguage::getModuleDomain('Mediashare');
    if (!pnSecConfirmAuthKey()) {
        return mediashareErrorPage(__FILE__, __LINE__, __('Unknown authentication key: you cannot submit the same form twice.', $dom));
    }

    $albumId = mediashareGetIntUrl('aid', $args, 1);
    $mediaId = mediashareGetIntUrl('mid', $args, 0);

    // Check access
    if (!mediashareAccessItem($mediaId, mediashareAccessRequirementEditMedia, '')) {
        return mediashareErrorPage(__FILE__, __LINE__, __('You do not have access to this feature', $dom));
    }

    if (!pnModAPILoad('mediashare', 'edit')) {
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare edit API');
    }

    global $mediashare_itemFields;
    $values = elfisk_decodeInput($mediashare_itemFields);
    $uploadInfo = $_FILES["upload"];
    $width = pnVarCleanFromInput("width");
    $height = pnVarCleanFromInput("height");

    if (isset($uploadInfo['error']) && $uploadInfo['error'] != 0 && $uploadInfo['name'] != '') {
        return mediashareErrorPage(__FILE__, __LINE__, $uploadInfo['name'] . ': ' . mediashareUploadErrorMsg($uploadInfo['error']));
    }

    $ok = pnModAPIFunc('mediashare', 'edit', 'updateItem', $values + array(
        'mediaId' => $mediaId,
        'uploadFilename' => $uploadInfo['tmp_name'],
        'fileSize' => $uploadInfo['size'],
        'filename' => $uploadInfo['name'],
        'mimeType' => $uploadInfo['type'],
        'width' => $width,
        'height' => $height));
    if ($ok === false) {
        return mediashareErrorAPIGet();
    }

    pnRedirect($backUrl);
    return true;
}

function mediashare_edit_deleteitem($args)
{
    $dom = ZLanguage::getModuleDomain('Mediashare');
    $mediaId = mediashareGetIntUrl('mid', $args, 0);
    $albumId = mediashareGetIntUrl('aid', $args, 1);

    // Check access
    if (!mediashareAccessItem($mediaId, mediashareAccessRequirementEditMedia, '')) {
        return mediashareErrorPage(__FILE__, __LINE__, __('You do not have access to this feature', $dom));
    }

    if (isset($_POST['okButton'])) {
        return mediashareDeleteItem($args);
    }

    if (isset($_POST['cancelButton'])) {
        pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
        return true;
    }

    if (!pnModAPILoad('mediashare', 'user')) {
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare user API');
    }

    $item = pnModAPIFunc('mediashare', 'user', 'getMediaItem', array('mediaId' => $mediaId));
    if ($item === false) {
        return mediashareErrorAPIGet();
    }

    $album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $item['parentAlbumId']));
    if ($album === false) {
        return mediashareErrorAPIGet();
    }
    $render = new elfiskRender('mediashare');

    $render->caching = false;
    $render->assign('item', $item);
    $render->assign('album', $album);

    return $render->fetch('mediashare_edit_deleteitem.html');
}

function mediashareDeleteItem($args)
{
    $dom = ZLanguage::getModuleDomain('Mediashare');

    if (!pnSecConfirmAuthKey()) {
        return mediashareErrorPage(__FILE__, __LINE__, __('Unknown authentication key: you cannot submit the same form twice.', $dom));
    }

    $albumId = mediashareGetIntUrl('aid', $args, 1);
    $mediaId = mediashareGetIntUrl('mid', $args, 0);

    if (!pnModAPILoad('mediashare', 'edit')) {
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare edit API');
    }

    $ok = pnModAPIFunc('mediashare', 'edit', 'deleteMediaItem', array('mediaId' => $mediaId));
    if ($ok === false) {
        return mediashareErrorAPIGet();
    }

    pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
    return true;
}

function mediashare_edit_multieditmedia($args)
{
    $dom = ZLanguage::getModuleDomain('Mediashare');

    if (!pnSecAuthAction(0, 'mediashare::', '::', ACCESS_EDIT)) {
        return mediashareErrorPage(__FILE__, __LINE__, __('You do not have access to this feature', $dom));
    }
    $albumId = mediashareGetIntUrl('aid', $args, 1);
    $mediaIdStr = pnVarCleanFromInput('mid');

    $mediaIdList = explode(',', $mediaIdStr);

    if (isset($_POST['saveButton'])) {
        return mediashareMultiUpdateItems();
    }

    if (isset($_POST['cancelButton'])) {
        pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
        return true;
    }

    if (!pnModAPILoad('mediashare', 'user')) {
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare user API');
    }

    $items = pnModAPIFunc('mediashare', 'user', 'getMediaItems', array('mediaIdList' => $mediaIdList, 'access' => mediashareAccessRequirementEditMedia, 'enableEscape' => false));
    if ($items === false) {
        return mediashareErrorAPIGet();
    }

    $render = new elfiskRender('mediashare');

    $render->caching = false;
    $render->assign('items', $items);
    $render->assign('albumId', $albumId);

    return $render->fetch('mediashare_edit_multieditmedia.html');
}

function mediashareMultiUpdateItems()
{
    $dom = ZLanguage::getModuleDomain('Mediashare');

    if (!pnSecConfirmAuthKey()) {
        return mediashareErrorPage(__FILE__, __LINE__, __('Unknown authentication key: you cannot submit the same form twice.', $dom));
    }

    $albumId = mediashareGetIntUrl('aid', $args, 1);

    if (!pnModAPILoad('mediashare', 'edit')) {
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare edit API');
    }

    $mediaIds = pnVarCleanFromInput('mediaId');
    foreach ($mediaIds as $mediaId) {
        $mediaId = (int) $mediaId;

        $title = pnVarCleanFromInput("title-$mediaId");
        $keywords = pnVarCleanFromInput("keywords-$mediaId");
        $description = pnVarCleanFromInput("description-$mediaId");

        // Check access
        if (!mediashareAccessItem($mediaId, mediashareAccessRequirementEditMedia, '')) {
            return mediashareErrorPage(__FILE__, __LINE__, __('You do not have access to this feature', $dom));
        }

        $ok = pnModAPIFunc('mediashare', 'edit', 'updateItem', array('mediaId' => $mediaId, 'title' => $title, 'keywords' => $keywords, 'description' => $description));
        if ($ok === false) {
            return mediashareErrorAPIGet();
        }
        //echo "$itemId: $title, $keywords, $description. ";
    }

    pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
    return true;
}

function mediashare_edit_multideletemedia($args)
{
    $dom = ZLanguage::getModuleDomain('Mediashare');

    if (!pnSecAuthAction(0, 'mediashare::', '::', ACCESS_EDIT)) {
        return mediashareErrorPage(__FILE__, __LINE__, __('You do not have access to this feature', $dom));
    }
    $albumId = mediashareGetIntUrl('aid', $args, 1);
    $mediaIdStr = pnVarCleanFromInput('mid');

    $mediaIdList = explode(',', $mediaIdStr);

    if (isset($_POST['saveButton'])) {
        return mediashareMultiDeleteMedia();
    }
    if (isset($_POST['cancelButton'])) {
        pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
        return true;
    }

    if (!pnModAPILoad('mediashare', 'user')) {
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare user API');
    }
    $items = pnModAPIFunc('mediashare', 'user', 'getMediaItems', array('mediaIdList' => $mediaIdList, 'access' => mediashareAccessRequirementEditMedia));
    if ($items === false) {
        return mediashareErrorAPIGet();
    }
    $render = new elfiskRender('mediashare');

    $render->caching = false;
    $render->assign('items', $items);
    $render->assign('albumId', $albumId);
    $render->assign('thumbnailSize', pnModGetVar('mediashare', 'thumbnailSize'));

    return $render->fetch('mediashare_edit_multideletemedia.html');
}

function mediashareMultiDeleteMedia()
{
    $dom = ZLanguage::getModuleDomain('Mediashare');

    if (!pnSecConfirmAuthKey()) {
        return mediashareErrorPage(__FILE__, __LINE__, __('Unknown authentication key: you cannot submit the same form twice.', $dom));
    }
    $albumId = mediashareGetIntUrl('aid', $args, 1);

    if (!pnModAPILoad('mediashare', 'edit')) {
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare edit API');
    }
    $mediaIds = pnVarCleanFromInput('mediaId');
    foreach ($mediaIds as $mediaId) {
        $mediaId = (int) $mediaId;

        // Check access (mediaId is from URL and need not all be from same album)
        if (!mediashareAccessItem($mediaId, mediashareAccessRequirementEditMedia, '')) {
            return mediashareErrorPage(__FILE__, __LINE__, __('You do not have access to this feature', $dom));
        }
        $ok = pnModAPIFunc('mediashare', 'edit', 'deleteMediaItem', array('mediaId' => $mediaId));
        if ($ok === false) {
            return mediashareErrorAPIGet();
        }
    }

    pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
    return true;
}

function mediashare_edit_multimovemedia($args)
{
    $dom = ZLanguage::getModuleDomain('Mediashare');

    if (!pnSecAuthAction(0, 'mediashare::', '::', ACCESS_EDIT)) {
        return mediashareErrorPage(__FILE__, __LINE__, __('You do not have access to this feature', $dom));
    }
    $albumId = mediashareGetIntUrl('aid', $args, 1);
    $mediaIdStr = pnVarCleanFromInput('mid');

    $mediaIdList = explode(',', $mediaIdStr);

    if (isset($_POST['saveButton'])) {
        return mediashareMultiMoveMedia();
    }
    if (isset($_POST['cancelButton'])) {
        pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
        return true;
    }

    if (!pnModAPILoad('mediashare', 'user')) {
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare user API');
    }

    $items = pnModAPIFunc('mediashare', 'user', 'getMediaItems', array('mediaIdList' => $mediaIdList, 'access' => mediashareAccessRequirementEditMedia));
    if ($items === false) {
        return mediashareErrorAPIGet();
    }

    $render = new elfiskRender('mediashare');

    $render->caching = false;
    $render->assign('items', $items);
    $render->assign('albumId', $albumId);
    $render->assign('thumbnailSize', pnModGetVar('mediashare', 'thumbnailSize'));

    return $render->fetch('mediashare_edit_multimovemedia.html');
}

function mediashareMultiMoveMedia()
{
    $dom = ZLanguage::getModuleDomain('Mediashare');

    if (!pnSecConfirmAuthKey()) {
        return mediashareErrorPage(__FILE__, __LINE__, __('Unknown authentication key: you cannot submit the same form twice.', $dom));
    }
    $albumId = mediashareGetIntUrl('newalbumid', $args, 1);

    if (!pnModAPILoad('mediashare', 'edit')) {
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare edit API');
    }
    $mediaIds = pnVarCleanFromInput('mediaId');
    foreach ($mediaIds as $mediaId) {
        $mediaId = (int) $mediaId;

        // Check access (mediaId is from URL and need not all be from same album)
        if (!mediashareAccessItem($mediaId, mediashareAccessRequirementEditMedia, '')) {
            return mediashareErrorPage(__FILE__, __LINE__, __('You do not have access to this feature', $dom));
        }
        $ok = pnModAPIFunc('mediashare', 'edit', 'moveMediaItem', array('mediaId' => $mediaId, 'albumId' => $albumId));
        if ($ok === false) {
            return mediashareErrorAPIGet();
        }
    }

    pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
    return true;
}

// =======================================================================
// Set main item
// =======================================================================


function mediashare_edit_setmainitem($args)
{
    $dom = ZLanguage::getModuleDomain('Mediashare');
    $albumId = mediashareGetIntUrl('aid', $args, 1);
    $mediaId = mediashareGetIntUrl('mid', $args, 0);

    // Check access
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementEditAlbum, '')) {
        return mediashareErrorPage(__FILE__, __LINE__, __('You do not have access to this feature', $dom));
    }
    if (!pnModAPILoad('mediashare', 'edit')) {
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare edit API');
    }
    $ok = pnModAPIFunc('mediashare', 'edit', 'setMainItem', array('albumId' => $albumId, 'mediaId' => $mediaId));
    if ($ok === false) {
        return mediashareErrorAPIGet();
    }
    pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
    return true;
}

// =======================================================================
// Arrange items
// =======================================================================


function mediashare_edit_arrange($args)
{
    $dom = ZLanguage::getModuleDomain('Mediashare');

    $albumId = mediashareGetIntUrl('aid', $args, 1);

    // Check access
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementAddMedia | mediashareAccessRequirementEditMedia, '')) {
        return mediashareErrorPage(__FILE__, __LINE__, __('You do not have access to this feature', $dom));
    }
    if (isset($_POST['saveButton'])) {
        return mediashareArrangeAlbum($args);
    }
    if (isset($_POST['cancelButton'])) {
        pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
        return true;
    }

    if (!pnUserLoggedIn()) {
        return mediashareErrorPage(__FILE__, __LINE__, __('You must be logged in to use this feature', $dom));
    }
    if (!pnModAPILoad('mediashare', 'user')) {
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare user API');
    }
    // Fetch current album


    $album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId));
    if ($album === false) {
        return mediashareErrorAPIGet();
    }
    if ($album === true) {
        return mediashareErrorPage(__FILE__, __LINE__, 'Unknown album');
    }
    // Fetch media items


    $items = pnModAPIFunc('mediashare', 'user', 'getMediaItems', array('albumId' => $albumId));
    if ($items === false) {
        return mediashareErrorAPIGet();
    }

    $render = new elfiskRender('mediashare');

    $render->caching = false;
    $render->assign('album', $album);
    $render->assign('mediaItems', $items);

    return $render->fetch('mediashare_edit_arrange.html');
}

function mediashareArrangeAlbum($args)
{
    $dom = ZLanguage::getModuleDomain('Mediashare');

    if (!pnSecConfirmAuthKey()) {
        return mediashareErrorPage(__FILE__, __LINE__, __('Unknown authentication key: you cannot submit the same form twice.', $dom));
    }

    $albumId = mediashareGetIntUrl('aid', $args, 1);
    $seq = pnVarCleanFromInput('seq');

    if (!pnModAPILoad('mediashare', 'edit')) {
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare edit API');
    }

    $ok = pnModAPIFunc('mediashare', 'edit', 'arrangeAlbum', array('albumId' => $albumId, 'seq' => explode(',', $seq)));

    if ($ok === false) {
        return mediashareErrorAPIGet();
    }

    pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
    return true;
}

// =======================================================================
// Access edit
// =======================================================================


function mediashare_edit_access($args)
{
    $dom = ZLanguage::getModuleDomain('Mediashare');

    $albumId = mediashareGetIntUrl('aid', $args, 1);

    // Check access
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementEditAccess, '')) {
        return mediashareErrorPage(__FILE__, __LINE__, __('You do not have access to this feature', $dom));
    }
    if (isset($_POST['saveButton'])) {
        return mediashareUpdateAccess($args);
    }
    if (isset($_POST['cancelButton'])) {
        pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
        return true;
    }

    if (!pnModAPILoad('mediashare', 'user')) {
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare user API');
    }

    if (!pnModAPILoad('mediashare', 'edit')) {
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare edit API');
    }
    // Fetch current album


    $album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId));
    if ($album === false) {
        return mediashareErrorAPIGet();
    }

    $access = pnModAPIFunc('mediashare', 'edit', 'getAccessSettings', array('albumId' => $albumId));
    if ($access === false) {
        return mediashareErrorAPIGet();
    }
    $render = new elfiskRender('mediashare');
    $render->caching = false;
    $render->assign('album', $album);
    $render->assign('access', $access);
    $render->assign('accessSelected', 1);
    $render->assign('sendSelected', 0);
    $render->assign('listSelected', 0);

    return $render->fetch('mediashare_edit_access.html');
}

function mediashareUpdateAccess($args)
{
    $dom = ZLanguage::getModuleDomain('Mediashare');

    if (!pnSecConfirmAuthKey()) {
        return mediashareErrorPage(__FILE__, __LINE__, __('Unknown authentication key: you cannot submit the same form twice.', $dom));
    }
    $albumId = mediashareGetIntUrl('aid', $args, 1);

    if (!pnModAPILoad('mediashare', 'edit')) {
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare edit API');
    }

    $groups = pnModAPIFunc('mediashare', 'edit', 'getAccessGroups');
    if ($groups === false) {
        return mediashareErrorAPIGet();
    }

    $access = array();
    foreach ($groups as $group) {
        $accessView = pnVarCleanFromInput('accessView' . $group['groupId']) != null;
        $accessEditAlbum = pnVarCleanFromInput('accessEditAlbum' . $group['groupId']) != null;
        $accessEditMedia = pnVarCleanFromInput('accessEditMedia' . $group['groupId']) != null;
        $accessAddAlbum = pnVarCleanFromInput('accessAddAlbum' . $group['groupId']) != null;
        $accessAddMedia = pnVarCleanFromInput('accessAddMedia' . $group['groupId']) != null;

        $access[] = array('groupId' => $group['groupId'], 'accessView' => $accessView, 'accessEditAlbum' => $accessEditAlbum, 'accessEditMedia' => $accessEditMedia, 'accessAddAlbum' => $accessAddAlbum, 'accessAddMedia' => $accessAddMedia);
    }

    $ok = pnModAPIFunc('mediashare', 'edit', 'updateAccessSettings', array('albumId' => $albumId, 'access' => $access));
    if ($ok === false) {
        return mediashareErrorAPIGet();
    }
    pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
    return true;
}

