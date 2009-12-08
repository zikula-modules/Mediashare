<?php
// $Id$
//
// Mediashare by Jorn Lind-Nielsen (C)
//

require_once 'modules/mediashare/common-edit.php';
require_once 'modules/mediashare/elfisk/elfisk_common.php';

/**
 * View album in edit-mode
 */
function mediashare_edit_view($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $albumId = mediashareGetIntUrl('aid', $args, 1);
    $page    = mediashareGetIntUrl('page', $args, 0);
    $showAll = mediashareGetBoolUrl('showall', $args, false);

    // Check access
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementEditSomething, '')) {
        return LogUtil::registerPermissionError();
    }

    if (!pnUserLoggedIn()) {
        return LogUtil::registerError(__('You must be logged in to use this feature', $dom));
    }

    // Check multi-edit buttons
    $selectedMediaId = FormUtil::getPassedValue('selectedMediaId');
    if ((isset($_POST['multiedit_x']) || isset($_POST['multidelete_x']) || isset($_POST['multimove_x'])) && count($selectedMediaId) > 0) {
        $mediaIdList = implode(',', $selectedMediaId);
        if (isset($_POST['multiedit_x'])) {
            $func = 'multieditmedia';
        } else if (isset($_POST['multidelete_x'])) {
            $func = 'multideletemedia';
        } else {
            $func = 'multimovemedia';
        }

        return pnRedirect(pnModUrl('mediashare', 'edit', $func, array('mid' => $mediaIdList, 'aid' => $albumId)));
    }

    // Fetch current album
    if (!($album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId)))) {
        return false;
    }
    if ($album === true) {
        return LogUtil::registerError(__('Unknown album.', $dom));
    }

    // Fetch subalbums
    if (!($subAlbums = pnModAPIFunc('mediashare', 'user', 'getSubAlbums', array('albumId' => $albumId, 'access' => mediashareAccessRequirementEditSomething)))) {
        return false;
    }

    // Fetch media items
    if (!($items = pnModAPIFunc('mediashare', 'user', 'getMediaItems', array('albumId' => $albumId)))) {
        return false;
    }

    // Build the output
    $render = & pnRender::getInstance('mediashare', false);

    $render->assign('album', $album);
    $render->assign('subAlbums', $subAlbums);
    $render->assign('mediaItems', $items);
    $render->assign('thumbnailSize', pnModGetVar('mediashare', 'thumbnailSize'));

    if (!mediashareAddAccess($render, $album)) {
        return false;
    }

    return $render->fetch('mediashare_edit_view.html');
}

/**
 * Add / edit album
 */
global $mediashare_albumFields;
$mediashare_albumFields = array('title'       => array('type' => 'string'),
                                'keywords'    => array('type' => 'string'),
                                'summary'     => array('type' => 'string'),
                                'description' => array('type' => 'string'),
                                'template'    => array('type' => 'string'),
                                'extappURL'   => array('type' => 'string'));

function mediashare_edit_addalbum($args)
{
    $albumId = mediashareGetIntUrl('aid', $args, 1);

    // Check access
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementAddAlbum, '')) {
        return LogUtil::registerPermissionError();
    }
    if (isset($_POST['cancelButton'])) {
        return pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
    }
    if (isset($_POST['saveButton'])) {
        return mediashareAddAlbum($args);
    }

    // Get parent album info (ignore unknown parent => this means we add a top most album)
    if (!($album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId)))) {
        return false;
    }

    // Build the output
    $render = & pnRender::getInstance('mediashare', false);

    $render->assign('album', $album);
    $render->assign('template', pnModGetVar('mediashare', 'defaultAlbumTemplate'));
    $render->assign('disableTemplateOverride', pnModGetVar('mediashare', 'allowTemplateOverride') ? false : true);

    return $render->fetch('mediashare_edit_addalbum.html');
}

function mediashareAddAlbum($args)
{
    if (!SecurityUtil::confirmAuthKey()) {
        return LogUtil::registerAuthidError();
    }

    $parentAlbumId = mediashareGetIntUrl('aid', $args, 1);

    $newAlbumID = pnModAPIFunc('mediashare', 'edit', 'addAlbum',
                               array('title' => FormUtil::getPassedValue('title'),
                                     'keywords' => FormUtil::getPassedValue('keywords'),
                                     'summary' => FormUtil::getPassedValue('summary'),
                                     'description' => FormUtil::getPassedValue('description'),
                                     'template' => FormUtil::getPassedValue('template'),
                                     'extappURL' => FormUtil::getPassedValue('extappURL'),
                                     'parentAlbumId' => $parentAlbumId));

    if ($newAlbumID === false) {
        return false;
    }

    return pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $newAlbumID)));
}

function mediashare_edit_editalbum($args)
{
    $albumId = mediashareGetIntUrl('aid', $args, 1);

    if (isset($_POST['cancelButton'])) {
        return pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
    }
    if (isset($_POST['saveButton'])) {
        return mediashareUpdateAlbum($args);
    }

    // Check access
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementEditAlbum, '')) {
        return LogUtil::registerPermissionError();
    }

    // Get album info
    if (!($album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId, 'enableEscape' => false)))) {
        return false;
    }

    // Build the output
    $render = & pnRender::getInstance('mediashare', false);

    $render->assign('album', $album);
    $render->assign($album);
    $render->assign('disableTemplateOverride', pnModGetVar('mediashare', 'allowTemplateOverride') ? false : true);

    return $render->fetch('mediashare_edit_editalbum.html');
}

function mediashareUpdateAlbum($args)
{
    if (!SecurityUtil::confirmAuthKey()) {
        return LogUtil::registerAuthidError();
    }

    $albumId = mediashareGetIntUrl('aid', $args, 1);

    // Check access
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementEditAlbum, '')) {
        return LogUtil::registerPermissionError();
    }

    global $mediashare_albumFields;
    $values = elfisk_decodeInput($mediashare_albumFields);

    if (!pnModAPIFunc('mediashare', 'edit', 'updateAlbum', $values + array('albumId' => $albumId))) {
        return false;
    }

    return pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
}

function mediashare_edit_deleteAlbum($args)
{
    $albumId = mediashareGetIntUrl('aid', $args, 1);

    if (isset($_POST['cancelButton'])) {
        return pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
    }
    if (isset($_POST['okButton'])) {
        return mediashareDeleteAlbum($args);
    }

    // Check access
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementEditAlbum, '')) {
        return LogUtil::registerPermissionError();
    }

    // Get album info
    if (!($album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId)))) {
        return false;
    }

    // Build the output
    $render = & pnRender::getInstance('mediashare', false);

    $render->assign('album', $album);

    return $render->fetch('mediashare_edit_deletealbum.html');
}

function mediashareDeleteAlbum($args)
{
    if (!SecurityUtil::confirmAuthKey()) {
        return LogUtil::registerAuthidError();
    }

    $albumId = mediashareGetIntUrl('aid', $args, 1);

    // Check access
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementEditAlbum, '')) {
        return LogUtil::registerPermissionError();
    }

    // Get album info
    if (!($album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId)))) {
        return false;
    }

    if (!pnModAPIFunc('mediashare', 'edit', 'deleteAlbum', array('albumId' => $albumId))) {
        return false;
    }

    return pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $album['parentAlbumId'])));
}

/**
 * Move album
 */
function mediashare_edit_movealbum($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $albumId = mediashareGetIntUrl('aid', $args, 1);

    if ($albumId == 1) {
        return LogUtil::registerError(__('Cannot move top album.', $dom));
    }
    // Check access
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementEditAccess, '')) {
        return LogUtil::registerPermissionError();
    }

    if (isset($_POST['cancelButton'])) {
        return pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
    }
    if (isset($_POST['saveButton'])) {
        return mediashareUpdateMoveAlbum($args);
    }

    // Fetch current album
    if (!($album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId)))) {
        return false;
    }

    // Build the output
    $render = & pnRender::getInstance('mediashare', false);

    $render->assign('album', $album);

    return $render->fetch('mediashare_edit_movealbum.html');
}

function mediashareUpdateMoveAlbum($args)
{
    $albumId    = mediashareGetIntUrl('aid', $args, 1);
    $dstAlbumId = mediashareGetIntUrl('daid', $args, 1);

    if (!pnModAPIFunc('mediashare', 'edit', 'moveAlbum', array('albumId' => $albumId, 'dstAlbumId' => $dstAlbumId))) {
        return false;
    }

    return pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
}

/**
 * Add / edit media items
 */
function mediashare_edit_addmedia($args)
{
    $albumId    = mediashareGetIntUrl('aid', $args, 1);
    $sourceName = mediashareGetStringUrl('source', $args);

    // Check access
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementAddMedia, '')) {
        return LogUtil::registerPermissionError();
    }

    $dom = ZLanguage::getModuleDomain('mediashare');

    // Get parent album info (ignore unknown parent => this means we add to a top most album)
    if (!($album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId)))) {
        return false;
    }

    // Get media sources
    if (!($sources = pnModAPIFunc('mediashare', 'sources', 'getSources'))) {
        return false;
    }
    if (count($sources) == 0) {
        return LogUtil::registerError(__('No media sources found. You need to go to the admin panel and perform a scan for media sources.', $dom));
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

    $selectedSourceFile = DataUtil::formatForStore("source_{$sourceName}");
    if (!pnModLoad('mediashare', $selectedSourceFile)) {
        return LogUtil::registerError("Failed to load Mediashare $selectedSourceFile file");
    }

    $sourceHtml = pnModFunc('mediashare', $selectedSourceFile, 'view');
    if ($sourceHtml === false || $sourceHtml === true) {
        return $sourceHtml;
    }

    // Build the output
    $render = & pnRender::getInstance('mediashare', false);

    $render->assign('album', $album);
    $render->assign('sources', $sources);
    $render->assign('selectedSource', $source);
    $render->assign('selectedSourceName', $sourceName);
    $render->assign('selectedSourceFile', $selectedSourceFile);
    $render->assign('sourceHtml', $sourceHtml);

    return $render->fetch('mediashare_edit_addmedia.html');
}

global $mediashare_itemFields;
$mediashare_itemFields = array('title'       => array('type' => 'string'),
                               'keywords'    => array('type' => 'string'),
                               'description' => array('type' => 'string'));

function mediashare_edit_edititem($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $mediaId = mediashareGetIntUrl('mid', $args, 0);

    if (!($item = pnModAPIFunc('mediashare', 'user', 'getMediaItem', array('mediaId' => $mediaId, 'enableEscape' => false)))) {
        return false;
    }

    $albumId = $item['parentAlbumId'];

    if (isset($_GET['back']) && $_GET['back'] == 'browse') {
        $backUrl = pnModURL('mediashare', 'user', 'browse', array('aid' => $albumId, 'mid' => $mediaId));
    } else {
        $backUrl = pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId));
    }

    if (isset($_POST['cancelButton'])) {
        return pnRedirect($backUrl);
    }
    if (isset($_POST['saveButton'])) {
        return mediashareUpdateItem($args, $backUrl);
    }

    // Do late access check so we can get "unknown item" error message from 'getMediaItem'
    if (!mediashareAccessItem($mediaId, mediashareAccessRequirementEditMedia, '')) {
        return LogUtil::registerPermissionError();
    }

    if (!($album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $item['parentAlbumId'])))) {
        return false;
    }

    // Build the output
    $render = & pnRender::getInstance('mediashare', false);

    $render->assign($item);
    $render->assign('item', $item);
    $render->assign('album', $album);

    return $render->fetch('mediashare_edit_edititem.html');
}

function mediashareUpdateItem($args, $backUrl)
{
    if (!SecurityUtil::confirmAuthKey()) {
        return LogUtil::registerAuthidError();
    }

    $albumId = mediashareGetIntUrl('aid', $args, 1);
    $mediaId = mediashareGetIntUrl('mid', $args, 0);

    // Check access
    if (!mediashareAccessItem($mediaId, mediashareAccessRequirementEditMedia, '')) {
        return LogUtil::registerPermissionError();
    }

    global $mediashare_itemFields;
    $values     = elfisk_decodeInput($mediashare_itemFields);
    $uploadInfo = $_FILES['upload'];
    $width      = FormUtil::getPassedValue('width');
    $height     = FormUtil::getPassedValue('height');

    if (isset($uploadInfo['error']) && $uploadInfo['error'] != 0 && $uploadInfo['name'] != '') {
        return LogUtil::registerError(DataUtil::formatForDisplay($uploadInfo['name']).': '.mediashareUploadErrorMsg($uploadInfo['error']));
    }

    $ok = pnModAPIFunc('mediashare', 'edit', 'updateItem', $values +
                       array('mediaId'  => $mediaId,
                             'uploadFilename' => $uploadInfo['tmp_name'],
                             'fileSize' => $uploadInfo['size'],
                             'filename' => $uploadInfo['name'],
                             'mimeType' => $uploadInfo['type'],
                             'width'    => $width,
                             'height'   => $height));

    return $ok ? pnRedirect($backUrl) : false;
}

function mediashare_edit_deleteitem($args)
{
    $mediaId = mediashareGetIntUrl('mid', $args, 0);
    $albumId = mediashareGetIntUrl('aid', $args, 1);

    // Check access
    if (!mediashareAccessItem($mediaId, mediashareAccessRequirementEditMedia, '')) {
        return LogUtil::registerPermissionError();
    }

    if (isset($_POST['cancelButton'])) {
        return pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
    }
    if (isset($_POST['okButton'])) {
        return mediashareDeleteItem($args);
    }

    if (!($item = pnModAPIFunc('mediashare', 'user', 'getMediaItem', array('mediaId' => $mediaId)))) {
        return false;
    }

    if (!($album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $item['parentAlbumId'])))) {
        return false;
    }

    // Build the output
    $render = & pnRender::getInstance('mediashare', false);

    $render->assign('item', $item);
    $render->assign('album', $album);

    return $render->fetch('mediashare_edit_deleteitem.html');
}

function mediashareDeleteItem($args)
{
    if (!SecurityUtil::confirmAuthKey()) {
        return LogUtil::registerAuthidError();
    }

    $albumId = mediashareGetIntUrl('aid', $args, 1);
    $mediaId = mediashareGetIntUrl('mid', $args, 0);

    if (!pnModAPIFunc('mediashare', 'edit', 'deleteMediaItem', array('mediaId' => $mediaId))) {
        return false;
    }

    return pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
}

function mediashare_edit_multieditmedia($args)
{
    if (!SecurityUtil::checkPermission('mediashare::', '::', ACCESS_EDIT)) {
        return LogUtil::registerPermissionError();
    }

    $albumId    = mediashareGetIntUrl('aid', $args, 1);
    $mediaIdStr = FormUtil::getPassedValue('mid');

    $mediaIdList = explode(',', $mediaIdStr);

    if (isset($_POST['cancelButton'])) {
        return pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
    }
    if (isset($_POST['saveButton'])) {
        return mediashareMultiUpdateItems();
    }

    if (!($items = pnModAPIFunc('mediashare', 'user', 'getMediaItems', array('mediaIdList' => $mediaIdList, 'access' => mediashareAccessRequirementEditMedia, 'enableEscape' => false)))) {
        return false;
    }

    // Build the output
    $render = & pnRender::getInstance('mediashare', false);

    $render->assign('items', $items);
    $render->assign('albumId', $albumId);

    return $render->fetch('mediashare_edit_multieditmedia.html');
}

function mediashareMultiUpdateItems()
{
    if (!SecurityUtil::confirmAuthKey()) {
        return LogUtil::registerAuthidError();
    }

    $albumId = mediashareGetIntUrl('aid', $args, 1);

    $mediaIds = FormUtil::getPassedValue('mediaId');
    foreach ($mediaIds as $mediaId)
    {
        $mediaId = (int)$mediaId;

        $title       = FormUtil::getPassedValue("title-$mediaId");
        $keywords    = FormUtil::getPassedValue("keywords-$mediaId");
        $description = FormUtil::getPassedValue("description-$mediaId");

        // Check access
        if (!mediashareAccessItem($mediaId, mediashareAccessRequirementEditMedia, '')) {
            return LogUtil::registerPermissionError();
        }

        if (!pnModAPIFunc('mediashare', 'edit', 'updateItem', array('mediaId' => $mediaId, 'title' => $title, 'keywords' => $keywords, 'description' => $description))) {
            return false;
        }
    }

    return pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
}

function mediashare_edit_multideletemedia($args)
{
    if (!SecurityUtil::checkPermission('mediashare::', '::', ACCESS_EDIT)) {
        return LogUtil::registerPermissionError();
    }

    $albumId    = mediashareGetIntUrl('aid', $args, 1);
    $mediaIdStr = FormUtil::getPassedValue('mid');

    $mediaIdList = explode(',', $mediaIdStr);

    if (isset($_POST['cancelButton'])) {
        return pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
    }
    if (isset($_POST['saveButton'])) {
        return mediashareMultiDeleteMedia();
    }

    if (!($items = pnModAPIFunc('mediashare', 'user', 'getMediaItems', array('mediaIdList' => $mediaIdList, 'access' => mediashareAccessRequirementEditMedia)))) {
        return false;
    }

    // Build the output
    $render = & pnRender::getInstance('mediashare', false);

    $render->assign('items', $items);
    $render->assign('albumId', $albumId);
    $render->assign('thumbnailSize', pnModGetVar('mediashare', 'thumbnailSize'));

    return $render->fetch('mediashare_edit_multideletemedia.html');
}

function mediashareMultiDeleteMedia()
{
    if (!SecurityUtil::confirmAuthKey()) {
        return LogUtil::registerAuthidError();
    }

    $albumId = mediashareGetIntUrl('aid', $args, 1);

    $mediaIds = FormUtil::getPassedValue('mediaId');
    foreach ($mediaIds as $mediaId)
    {
        $mediaId = (int)$mediaId;

        // Check access (mediaId is from URL and need not all be from same album)
        if (!mediashareAccessItem($mediaId, mediashareAccessRequirementEditMedia, '')) {
            return LogUtil::registerPermissionError();
        }

        if (!pnModAPIFunc('mediashare', 'edit', 'deleteMediaItem', array('mediaId' => $mediaId))) {
            return false;
        }
    }

    return pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
}

function mediashare_edit_multimovemedia($args)
{
    if (!SecurityUtil::checkPermission('mediashare::', '::', ACCESS_EDIT)) {
        return LogUtil::registerPermissionError();
    }

    $albumId    = mediashareGetIntUrl('aid', $args, 1);
    $mediaIdStr = FormUtil::getPassedValue('mid');

    $mediaIdList = explode(',', $mediaIdStr);

    if (isset($_POST['cancelButton'])) {
        return pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
    }
    if (isset($_POST['saveButton'])) {
        return mediashareMultiMoveMedia();
    }

    if (!($items = pnModAPIFunc('mediashare', 'user', 'getMediaItems', array('mediaIdList' => $mediaIdList, 'access' => mediashareAccessRequirementEditMedia)))) {
        return false;
    }

    // Build the output
    $render = & pnRender::getInstance('mediashare', false);

    $render->assign('items', $items);
    $render->assign('albumId', $albumId);
    $render->assign('thumbnailSize', pnModGetVar('mediashare', 'thumbnailSize'));

    return $render->fetch('mediashare_edit_multimovemedia.html');
}

function mediashareMultiMoveMedia()
{
    if (!SecurityUtil::confirmAuthKey()) {
        return LogUtil::registerAuthidError();
    }

    $albumId = mediashareGetIntUrl('newalbumid', $args, 1);

    $mediaIds = FormUtil::getPassedValue('mediaId');
    foreach ($mediaIds as $mediaId)
    {
        $mediaId = (int)$mediaId;

        // Check access (mediaId is from URL and need not all be from same album)
        if (!mediashareAccessItem($mediaId, mediashareAccessRequirementEditMedia, '')) {
            return LogUtil::registerPermissionError();
        }

        if (!pnModAPIFunc('mediashare', 'edit', 'moveMediaItem', array('mediaId' => $mediaId, 'albumId' => $albumId))) {
            return false;
        }
    }

    return pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
}

/**
 * Set main item
 */
function mediashare_edit_setmainitem($args)
{
    $albumId = mediashareGetIntUrl('aid', $args, 1);
    $mediaId = mediashareGetIntUrl('mid', $args, 0);

    // Check access
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementEditAlbum, '')) {
        return LogUtil::registerPermissionError();
    }

    if (!pnModAPIFunc('mediashare', 'edit', 'setMainItem', array('albumId' => $albumId, 'mediaId' => $mediaId))) {
        return false;
    }

    return pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
}

/**
 * Arrange items
 */
function mediashare_edit_arrange($args)
{
    $albumId = mediashareGetIntUrl('aid', $args, 1);

    // Check access
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementAddMedia | mediashareAccessRequirementEditMedia, '')) {
        return LogUtil::registerPermissionError();
    }

    if (isset($_POST['cancelButton'])) {
        return pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
    }
    if (isset($_POST['saveButton'])) {
        return mediashareArrangeAlbum($args);
    }

    $dom = ZLanguage::getModuleDomain('mediashare');

    if (!pnUserLoggedIn()) {
        return LogUtil::registerError(__('You must be logged in to use this feature', $dom));
    }

    // Fetch current album
    if (!($album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId)))) {
        return false;
    }
    if ($album === true) {
        return LogUtil::registerError(__('Unknown album.', $dom));
    }

    // Fetch media items
    if (!($items = pnModAPIFunc('mediashare', 'user', 'getMediaItems', array('albumId' => $albumId)))) {
        return false;
    }

    // Build the output
    $render = & pnRender::getInstance('mediashare', false);

    $render->assign('album', $album);
    $render->assign('mediaItems', $items);

    return $render->fetch('mediashare_edit_arrange.html');
}

function mediashareArrangeAlbum($args)
{
    if (!SecurityUtil::confirmAuthKey()) {
        return LogUtil::registerAuthidError();
    }

    $albumId = mediashareGetIntUrl('aid', $args, 1);
    $seq     = FormUtil::getPassedValue('seq');

    if (!pnModAPIFunc('mediashare', 'edit', 'arrangeAlbum', array('albumId' => $albumId, 'seq' => explode(',', $seq)))) {
        return false;
    }

    return pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
}

/**
 * Access edit
 */
function mediashare_edit_access($args)
{
    $albumId = mediashareGetIntUrl('aid', $args, 1);

    // Check access
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementEditAccess, '')) {
        return LogUtil::registerPermissionError();
    }

    if (isset($_POST['cancelButton'])) {
        return pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
    }
    if (isset($_POST['saveButton'])) {
        return mediashareUpdateAccess($args);
    }

    // Fetch current album
    if (!($album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId)))) {
        return false;
    }

    if (!($access = pnModAPIFunc('mediashare', 'edit', 'getAccessSettings', array('albumId' => $albumId)))) {
        return false;
    }

    // Build the output
    $render = & pnRender::getInstance('mediashare', false);

    $render->assign('album', $album);
    $render->assign('access', $access);
    $render->assign('accessSelected', 1);
    $render->assign('sendSelected', 0);
    $render->assign('listSelected', 0);

    return $render->fetch('mediashare_edit_access.html');
}

function mediashareUpdateAccess($args)
{
    if (!SecurityUtil::confirmAuthKey()) {
        return LogUtil::registerAuthidError();
    }

    $albumId = mediashareGetIntUrl('aid', $args, 1);

    if (!($groups = pnModAPIFunc('mediashare', 'edit', 'getAccessGroups'))) {
        return false;
    }

    $access = array();
    foreach ($groups as $group)
    {
        $accessView      = FormUtil::getPassedValue('accessView' . $group['groupId']) != null;
        $accessEditAlbum = FormUtil::getPassedValue('accessEditAlbum' . $group['groupId']) != null;
        $accessEditMedia = FormUtil::getPassedValue('accessEditMedia' . $group['groupId']) != null;
        $accessAddAlbum  = FormUtil::getPassedValue('accessAddAlbum' . $group['groupId']) != null;
        $accessAddMedia  = FormUtil::getPassedValue('accessAddMedia' . $group['groupId']) != null;

        $access[] = array('groupId'         => $group['groupId'],
                          'accessView'      => $accessView,
                          'accessEditAlbum' => $accessEditAlbum,
                          'accessEditMedia' => $accessEditMedia,
                          'accessAddAlbum'  => $accessAddAlbum,
                          'accessAddMedia'  => $accessAddMedia);
    }

    if (!pnModAPIFunc('mediashare', 'edit', 'updateAccessSettings', array('albumId' => $albumId, 'access' => $access))) {
        return false;
    }

    return pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
}
