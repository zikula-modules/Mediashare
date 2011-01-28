<?php
// $Id$
//
// Mediashare by Jorn Lind-Nielsen (C)
//

require_once 'modules/mediashare/common-edit.php';

/**
 * Add/edit albums
 */
function mediashare_editapi_addAlbum(&$args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    // Check basic access (but don't do fine grained Mediashare access check)
    if (!SecurityUtil::checkPermission('mediashare::', '::', ACCESS_EDIT)) {
        return LogUtil::registerPermissionError();
    }
    // Set defaults
    // FIXME: what if not logged in - how about 'owner' ???
    if (!isset($args['ownerId'])) {
        $args['ownerId'] = pnUserGetVar('uid');
    }
    if (!isset($args['template']) || empty($args['template'])) {
        // Include null test
        $args['template'] = pnModGetVar('mediashare', 'defaultAlbumTemplate', 'Standard');
    }

    // Parse extapp URL and add extapp data
    if (!mediashare_editapi_extappLocateApp($args)) {
        return false;
    }

    Loader::loadClass('RandomUtil');

    $album = array(
        'ownerId'       => (int)$args['ownerId'],
        'createdDate'   => DateUtil::getDatetime(),
        'title'         => $args['title'],
        'keywords'      => $args['keywords'],
        'summary'       => $args['summary'],
        'description'   => $args['description'],
        'template'      => $args['template'],
        'parentAlbumId' => (int)$args['parentAlbumId'],
        'thumbnailSize' => (int)pnModGetVar('mediashare', 'thumbnailSize'),
        'viewKey'       => RandomUtil::getInteger(100000000, 999999999, true)*10000 + RandomUtil::getInteger(1, 9999),
        'extappURL'     => $args['extappURL'],
        'extappData'    => $args['extappData']
    );

    $album = DBUtil::insertObject($album, 'mediashare_albums', 'id');

    if ($album === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('editapi.addAlbum', 'Could not add the new album.'), $dom));
    }

    if (!pnModAPIFunc('mediashare', 'edit', 'updateNestedSetValues')) {
        return false;
    }

    if (!pnModAPIFunc('mediashare', 'edit', 'setDefaultAccess', array('albumId' => $album['id']))) {
        return false;
    }

    pnModCallHooks('item', 'create', "album-{$album['id']}", array('module' => 'mediashare', 'albumId' => $album['id']));

    if (!pnModAPIFunc('mediashare', 'edit', 'updateKeywords', array('itemId' => $album['id'], 'type' => 'album', 'keywords' => $args['keywords']))) {
        return false;
    }

    if (!pnModAPIFunc('mediashare', 'edit', 'fetchExternalImages', array('albumId' => $album['id']))) {
        return false;
    }

    return $album['id'];
}

// FIXME any parameter?
function mediashare_editapi_updateNestedSetValues()
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    // FIXME any method to detect MySQL version?
    // MySQL switch
    // MySQL 5 (true) - Use stored procedure mediashareUpdateNestedSetValues
    // MySQL 4 (false) - use PHP
    if (false) {
        $result = DBUtil::executeSQL("CALL mediashareUpdateNestedSetValues()");

        if ($result === false) {
            return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('editapi.updateNestedSetValues', 'Calling mediashareUpdateNestedSetValues() failed.'), $dom));
        }

        return true;
    } else {
        $albumId = 1;
        $count = 0;
        $level = 0;

        return mediashareUpdateNestedSetValues_Rec($albumId, $level, $count);
    }
}

function mediashareUpdateNestedSetValues_Rec($albumId, $level, &$count)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $pntable      = &pnDBGetTables();
    $albumsColumn = $pntable['mediashare_albums_column'];

    $left = $count++;

    $where     = "$albumsColumn[parentAlbumId] = '".(int)$albumId."'";
    $subalbums = DBUtil::selectFieldArray('mediashare_albums', 'id', $where);

    if ($subalbums === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('editapi.mediashareUpdateNestedSetValues_Rec', 'Could not retrieve the subalbums.'), $dom));
    }

    foreach ($subalbums as $subAlbumId) {
        mediashareUpdateNestedSetValues_Rec($subAlbumId, $level + 1, $count);
    }

    $right = $count++;

    $album = array(
        'id'             => $albumId,
        'nestedSetLeft'  => $left,
        'nestedSetRight' => $right,
        'nestedSetLevel' => $level
    );
    $album = DBUtil::updateObject($album, 'mediashare_albums', '', 'id');

    if ($album === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('editapi.mediashareUpdateNestedSetValues_Rec', 'Could not update the album.'), $dom));
    }

    return true;
}

function mediashare_editapi_updateAlbum(&$args)
{
    // Check access
    if (!SecurityUtil::checkPermission('mediashare::', '::', ACCESS_EDIT)) {
        return LogUtil::registerPermissionError();
    }

    $dom = ZLanguage::getModuleDomain('mediashare');

    // Parse extapp URL and add extapp data
    if (!mediashare_editapi_extappLocateApp($args)) {
        return false;
    }

    $albumId = (int)$args['albumId'];

    // FIXME: what if not logged in - how about 'owner' ???
    $album = array(
        'id'          => $albumId,
        'title'       => $args['title'],
        'keywords'    => $args['keywords'],
        'summary'     => $args['summary'],
        'description' => $args['description'],
        'extappURL'   => isset($args['extappURL'])  ? $args['extappURL']  : '',
        'extappData'  => isset($args['extappData']) ? $args['extappData'] : ''
    );

    if (isset($args['template'])) {
        $album['template'] = $args['template'];
    }

    $album = DBUtil::updateObject($album, 'mediashare_albums', '', 'id');

    if ($album === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('editapi.updateAlbum', 'Could not update the album.'), $dom));
    }

    if (!pnModAPIFunc('mediashare', 'edit', 'updateKeywords', array('itemId' => $albumId, 'type' => 'album', 'keywords' => $args['keywords']))) {
        return false;
    }

    if (!pnModAPIFunc('mediashare', 'edit', 'fetchExternalImages', array('albumId' => $albumId))) {
        return false;
    }

    return true;
}

function mediashare_editapi_deleteAlbum($args)
{
    // Check access
    if (!SecurityUtil::checkPermission('mediashare::', '::', ACCESS_EDIT)) {
        return LogUtil::registerPermissionError();
    }

    $dom = ZLanguage::getModuleDomain('mediashare');

    $albumId = (int)$args['albumId'];

    if ($albumId == 1) {
        return LogUtil::registerError(__('You cannot delete the top album', $dom));
    }

    if (!pnModAPIFunc('mediashare', 'edit', 'updateAccessSettings', array('albumId' => $albumId, 'access' => array()))) {
        return false;
    }

    if (!mediashareDeleteAlbumRec($albumId)) {
        return false;
    }

    if (!pnModAPIFunc('mediashare', 'edit', 'updateNestedSetValues')) {
        return false;
    }

    if (!pnModAPIFunc('mediashare', 'edit', 'updateKeywords', array('itemId' => $albumId, 'type' => 'album', 'keywords' => ''))) {
        return false;
    }

    return true;
}

function mediashareDeleteAlbumRec($albumId)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    // Get album info
    if (!($album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId)))) {
        return false;
    }

    $pntable = &pnDBGetTables();

    $albumsColumn = $pntable['mediashare_albums_column'];
    $mediaColumn  = $pntable['mediashare_media_column'];

    $where     = "$albumsColumn[parentAlbumId] = '".(int)$albumId."'";
    $subalbums = DBUtil::selectFieldArray('mediashare_albums', 'id', $where);

    if ($subalbums === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('editapi.mediashareDeleteAlbumRec', 'Could not delete the album.'), $dom));
    }

    foreach ($subalbums as $subAlbumId) {
        if (mediashareDeleteAlbumRec($subAlbumId) === false) {
            return false;
        }
    }

    $where  = "$mediaColumn[parentAlbumId] = '".(int)$albumId."'";
    $medias = DBUtil::selectFieldArray('mediashare_media', 'id', $where);

    if ($medias === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('editapi.mediashareDeleteAlbumRec', 'Could not select the album.'), $dom));
    }

    foreach ($medias as $mediaId) {
        if (!pnModAPIFunc('mediashare', 'edit', 'deleteMediaItem', array('mediaId' => $mediaId))) {
            return false;
        }
    }

    $where  = "$albumsColumn[id] = '".(int)$albumId."'";
    $result = DBUtil::deleteWhere('mediashare_albums', $where);

    if ($result === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('editapi.mediashareDeleteAlbumRec', 'Could not delete the album.'), $dom));
    }

    pnModCallHooks('item', 'delete', "album-$albumId", array('module' => 'mediashare', 'albumId' => $albumId));

    return true;
}

/**
 * Move album
 */
function mediashare_editapi_moveAlbum($args)
{
    // Check access
    if (!SecurityUtil::checkPermission('mediashare::', '::', ACCESS_EDIT)) {
        return LogUtil::registerPermissionError();
    }

    $dom = ZLanguage::getModuleDomain('mediashare');

    $albumId    = (int)$args['albumId'];
    $dstAlbumId = (int)$args['dstAlbumId'];

    if ($albumId == 1) {
        return LogUtil::registerError(__('Cannot move top album', $dom));
    }

    if ($albumId == $dstAlbumId) {
        return LogUtil::registerError(__('Cannot move album to self', $dom));
    }

    if ($dstAlbumId == 0) {
        return LogUtil::registerError(__('Cannot move album outsite root album', $dom));
    }

    // Process
    if (!($album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId)))) {
        return false;
    }

    if (!($dstAlbum = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $dstAlbumId)))) {
        return false;
    }

    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementEditAccess, '') ||
        !mediashareAccessAlbum($dstAlbumId, mediashareAccessRequirementAddAlbum, '')) {
        return LogUtil::registerPermissionError();
    }

    $isChild = pnModAPIFunc('mediashare', 'edit', 'isChildAlbum',
                            array('albumId'       => $dstAlbumId,
                                  'parentAlbumId' => $albumId));

    if ($isChild === true) {
        return LogUtil::registerError(__('Cannot move album below self', $dom));
    }

    $album = array(
        'id' => $albumId,
        'parentAlbumId' => $dstAlbumId
    );
    $album = DBUtil::updateObject($album, 'mediashare_albums', '', 'id');

    if ($album === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('editapi.moveAlbum', 'Could not move the album.'), $dom));
    }

    if (!pnModAPIFunc('mediashare', 'edit', 'updateNestedSetValues')) {
        return false;
    }

    return true;
}

function mediashare_editapi_isChildAlbum($args)
{
    $albumId       = (int)$args['albumId'];
    $parentAlbumId = (int)$args['parentAlbumId'];

    if (!($album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId)))) {
        return false;
    }

    if (!($parentAlbum = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $parentAlbumId)))) {
        return false;
    }

    return $parentAlbum['nestedSetLeft'] < $album['nestedSetLeft'] && $parentAlbum['nestedSetRight'] > $album['nestedSetRight'];
}

/**
 * Adding media items
 */

/**
 * addMediaItem
 * This function adds a single media item to Mediashare's repository.
 *
 * @params $args['albumId'] int The ID of the album in which the media items should be added.
 * @params $args['mediaFilename'] string The full path to the media file on the local file system. Mediashare takes a copy of this and expects the caller to remove the input file after use.
 * @params $args['filename'] string Expected filename for the media file being added.
 * @params $args['mimeType'] string
 * @params $args['ownerId'] int Optional user id for the file
 * @params $args['fileSize'] int
 *
 * @return mixed Returns array on success and false on error.
 */
function mediashare_editapi_addMediaItem($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    if (!isset($args['albumId'])) {
        return LogUtil::registerError(__f('Missing [%1$s] in \'%2$s\'', array('albumId', 'editapi.addMediaItem'), $dom));
    }

    $albumId = (int)$args['albumId'];

    // Calculate title
    $mediaTitle = $args['title'];
    if ($mediaTitle == '') {
        $mediaTitle = $args['filename'];
        if (!(($p = strrpos($mediaTitle, '.')) === false)) {
            // Strip trailing extension
            $mediaTitle = substr($mediaTitle, 0, $p);
        }
    }
    $mediaTitle = str_replace('_', ' ', $mediaTitle);

    // Check upload limits
    if (!($userInfo = pnModAPIFunc('mediashare', 'edit', 'getUserInfo'))) {
        return false;
    }

    if (!isset($args['ignoreSizeLimits']) || !$args['ignoreSizeLimits']) {
        $fileSize = $args['fileSize'];

        if ($fileSize > $userInfo['mediaSizeLimitSingle']) {
            return LogUtil::registerError(DataUtil::formatForDisplay($mediaTitle).': '.__('Media file too big', $dom));
        }

        if ($fileSize + $userInfo['totalCapacityUsed'] > $userInfo['mediaSizeLimitTotal']) {
            return LogUtil::registerError(__('Media file too big - total quota would be exceeded', $dom));
        }
    }

    // Find a media handler
    if (!($handlerInfo = pnModAPIFunc('mediashare', 'mediahandler', 'getHandlerInfo', array('mimeType' => $args['mimeType'], 'filename' => $args['filename'])))) {
        return false;
    }

    $handlerName = $handlerInfo['handlerName'];

    // Make sure we use sanitized results from the database (like "image/pjpeg" => "image/jpeg")
    $args['mimeType'] = $handlerInfo['mimeType'];
    $args['fileType'] = $handlerInfo['fileType'];

    // Build the media handler
    $handlerApi = "media_{$handlerName}";
    $handler    = pnModAPIFunc('mediashare', $handlerApi, 'buildHandler');

    // Ask media handler to generate thumbnail (with unique filename) and preview images
    $tmpDir = pnModGetVar('mediashare', 'tmpDirName');
    if (($thumbnailFilename = tempnam($tmpDir, 'Preview')) === false) {
        return LogUtil::registerError(__f("Failed to create the thumbnail file in '%s'.", 'editapi.addMediaItem', $dom));
    }

    // Create the unique filename for the preview with tempnam
    if (($previewFilename = tempnam($tmpDir, 'Preview')) === false) {
        @unlink($thumbnailFilename);
        return LogUtil::registerError(__f("Failed to create the preview file in '%s'.", 'editapi.addMediaItem', $dom));
    }

    $thumbnailSize = (int)pnModGetVar('mediashare', 'thumbnailSize');

    $previews = array(
        array('outputFilename' => $thumbnailFilename,
              'imageSize'      => $thumbnailSize,
              'isThumbnail'    => true),
        array('outputFilename' => $previewFilename,
              'imageSize'      => (int)pnModGetVar('mediashare', 'previewSize'),
              'isThumbnail'    => false)
    );

    $previewResult = $handler->createPreviews($args, $previews);
    if ($previewResult === false) {
        @unlink($thumbnailFilename);
        @unlink($previewFilename);
        return false;
    }

    // Get virtual file system handler
    $vfsHandlerName = pnModGetVar('mediashare', 'vfs');
    $vfsHandlerApi = "vfs_$vfsHandlerName";
    if (!pnModAPILoad('mediashare', $vfsHandlerApi)) {
        @unlink($thumbnailFilename);
        @unlink($previewFilename);
        return LogUtil::registerError(__f('Missing [%1$s] in \'%2$s\'', array($vfsHandlerApi, 'editapi.addMediaItem'), $dom));
    }

    if (!($vfsHandler = pnModAPIFunc('mediashare', $vfsHandlerApi, 'buildHandler'))) {
        return false;
    }

    // Store thumbnail, preview, and original in virtual file system
    $baseFileRef = $vfsHandler->getNewFileReference();
    $previewResult[0]['baseFileRef'] = $baseFileRef;
    $previewResult[0]['fileMode']    = 'tmb';
    $previewResult[1]['baseFileRef'] = $baseFileRef;
    $previewResult[1]['fileMode']    = 'pre';
    $previewResult[2]['baseFileRef'] = $baseFileRef;
    $previewResult[2]['fileMode']    = 'org';

    $result = array();

    if (($thumbnailFileRef = $vfsHandler->createFile($thumbnailFilename, $previewResult[0])) === false) {
        @unlink($thumbnailFilename);
        @unlink($previewFilename);
        return false;
    }

    $previewResult[0]['fileRef'] = $result['thumbnailFileRef'] = $thumbnailFileRef;

    if (($originalFileRef = $vfsHandler->createFile($args['mediaFilename'], $previewResult[2])) === false) {
        $vfsHandler->deleteFile($thumbnailFileRef);
        @unlink($thumbnailFilename);
        @unlink($previewFilename);
        return false;
    }

    $previewResult[2]['fileRef'] = $result['originalFileRef'] = $originalFileRef;

    if (!isset($previewResult[1]['useOriginal']) || !(bool)$previewResult[1]['useOriginal']) {
        if (($previewFileRef = $vfsHandler->createFile($previewFilename, $previewResult[1])) === false) {
            $vfsHandler->deleteFile($thumbnailFileRef);
            $vfsHandler->deleteFile($originalFileRef);
            $vfsHandler->deleteFile($previewFileRef);
            @unlink($thumbnailFilename);
            @unlink($previewFilename);
            return false;
        }
        $previewResult[1]['fileRef'] = $result['previewFileRef'] = $previewFileRef;
    } else {
        $previewResult[1]['fileRef'] = $result['previewFileRef'] = $originalFileRef;
    }

    $id = pnModAPIFunc('mediashare', 'edit', 'storeMediaItem',
                       array('title'        => $mediaTitle,
                             'keywords'     => $args['keywords'],
                             'description'  => $args['description'],
                             'ownerId'      => isset($args['ownerId']) ? $args['ownerId'] : pnUserGetVar('uid'),
                             'albumId'      => $albumId,
                             'mediaHandler' => $handlerName,
                             'thumbnail'    => $previewResult[0],
                             'preview'      => $previewResult[1],
                             'original'     => $previewResult[2]));

    if ($id === false) {
        $vfsHandler->deleteFile($thumbnailFileRef);
        $vfsHandler->deleteFile($previewFileRef);
        $vfsHandler->deleteFile($originalFileRef);
        @unlink($thumbnailFilename);
        @unlink($previewFilename);
        return false;
    }

    pnModCallHooks('item', 'create', "media-$id", array('module' => 'mediashare', 'mediaId' => $id));

    @unlink($thumbnailFilename);
    @unlink($previewFilename);

    if (!pnModAPIFunc('mediashare', 'edit', 'ensureMainAlbumId', array('albumId' => $albumId, 'mediaId' => $id))) {
        // Don't clean up, just report error. Upload actually worked.
        return false;
    }

    $result['message'] = "$mediaTitle: " . __('Media item was added', $dom);
    $result['mediaId'] = $id;

    return $result;
}

function mediashare_editapi_storeMediaItem(&$args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $albumId = (int)$args['albumId'];

    if (!isset($args['ownerId'])) {
        $args['ownerId'] = pnUserGetVar('uid');
    }

		$position = mediashareGetNewPosition($albumId);
  if ($position === false)
    return false;

  $thumbnailId = pnModAPIFunc('mediashare', 'edit', 'registerMediaItem', $args['thumbnail']);
  if ($thumbnailId === false)
    return false;

  $previewId = pnModAPIFunc('mediashare', 'edit', 'registerMediaItem', $args['preview']);
  if ($previewId === false)
    return false;

  $originalId = pnModAPIFunc('mediashare', 'edit', 'registerMediaItem', $args['original']);
  if ($originalId === false)
    return false;
		
		


    $media = array(
        'ownerId'       => (int)$args['ownerId'],
        'createdDate'   => DateUtil::getDatetime(),
        'title'         => $args['title'],
        'keywords'      => mediashareStripKeywords($args['keywords']),
        'description'   => $args['description'],
        'parentAlbumId' => $albumId,
        'position'      => $position,
        'mediaHandler'  => $args['mediaHandler'],
        'thumbnailId'   => $thumbnailId,
        'previewId'     => $previewId,
        'originalId'    => $originalId
    );

    $media = DBUtil::insertObject($media, 'mediashare_media', 'id');

    if ($media === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('editapi.storeMediaItem', __('Could not insert the media item.', $dom)), $dom));
    }

    if (!pnModAPIFunc('mediashare', 'edit', 'updateKeywords', array('itemId' => $media['id'], 'type' => 'media', 'keywords' => $args['keywords']))) {
        return false;
    }

    return $media['id'];
}

function mediashare_editapi_registerMediaItem($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $media = array(
        'fileRef'  => $args['fileRef'],
        'mimeType' => $args['mimeType'],
        'width'    => $args['width'],
        'height'   => $args['height'],
        'bytes'    => $args['bytes']
    );

    $media = DBUtil::insertObject($media, 'mediashare_mediastore', 'id');
    
    if ($media === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('editapi.registerMediaItem', __('Could not insert the media item.', $dom)), $dom));
    }

    return $media['id'];
}

function mediashareGetNewPosition($albumId)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $pntable     = &pnDBGetTables();
    $mediaColumn = $pntable['mediashare_media_column'];

    $where = "$mediaColumn[parentAlbumId] = $albumId";
    $max   = DBUtil::selectFieldMax('mediashare_media', 'position', 'MAX', $where);
    
    if ($max === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('editapi.mediashareGetNewPosition', 'Could not get the max position.'), $dom));
    }

    return empty($max) ? 0 : $max + 1;
}

function mediashare_editapi_ensureMainAlbumId($args)
{
    // Check access
    if (!SecurityUtil::checkPermission('mediashare::', '::', ACCESS_EDIT)) {
        return LogUtil::registerPermissionError();
    }

    $dom = ZLanguage::getModuleDomain('mediashare');

    // Argument check
    if (!isset($args['albumId'])) {
        return LogUtil::registerError(__f('Missing [%1$s] in \'%2$s\'', array('albumId', 'editapi.ensureMainAlbumId'), $dom));
    }
    if (!isset($args['mediaId'])) {
        return LogUtil::registerError(__f('Missing [%1$s] in \'%2$s\'', array('mediaId', 'editapi.ensureMainAlbumId'), $dom));
    }

    $forceUpdate = isset($args['forceUpdate']) && $args['forceUpdate'] ? true : false;

    $pntable      = &pnDBGetTables();
    $albumsColumn = $pntable['mediashare_albums_column'];

    $where = '';
    if (!$forceUpdate) {
        $where = "$albumsColumn[mainMediaId] IS NULL";
    }

    $album = array(
        'id' => (int)$args['albumId'],
        'mainMediaId' => (int)$args['mediaId']
    );

    $album = DBUtil::updateObject($album, 'mediashare_albums', $where, 'id');

    if ($album === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('editapi.ensureMainAlbumId', 'Could not ensure the main media for the album.'), $dom));
    }

    return true;
}

/**
 * Update media item
 */
function mediashare_editapi_updateItem(&$args)
{
    // Check access
    if (!SecurityUtil::checkPermission('mediashare::', '::', ACCESS_EDIT)) {
        return LogUtil::registerPermissionError();
    }

    $dom = ZLanguage::getModuleDomain('mediashare');

    $media = array(
        'id'          => (int)$args['mediaId'],
        'title'       => $args['title'],
        'keywords'    => $args['keywords'],
        'description' => $args['description']
    );

    $media = DBUtil::updateObject($media, 'mediashare_media', '', 'id');

    if ($media === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('editapi.updateItem', 'Could not update the media item.'), $dom));
    }

    if (!pnModAPIFunc('mediashare', 'edit', 'updateKeywords', array('itemId' => (int)$args['mediaId'], 'type' => 'media', 'keywords' => $args['keywords']))) {
        return false;
    }

    if (!pnModAPIFunc('mediashare', 'edit', 'updateItemFileUpload', $args)) {
        return false;
    }

    return true;
}

function mediashare_editapi_updateItemFileUpload(&$args)
{
    // Ignore empty uploads
    if (!isset($args['fileSize']) || $args['fileSize'] == 0) {
        return true;
    }

    $dom = ZLanguage::getModuleDomain('mediashare');

    $mediaId        = (int)$args['mediaId'];
    $uploadFilename = $args['uploadFilename'];

    // Fetch media data - we need it to locate the media files
    if (!($mediaItem = pnModAPIFunc('mediashare', 'user', 'getMediaItem', array('mediaId' => $mediaId)))) {
        return false;
    }

    // Must store items in the same way always (ignore current VFS settings)
    $vfsHandlerName = mediashareGetVFSHandlerName($mediaItem['thumbnailRef']);

    // Check upload limits
    if (!($userInfo = pnModAPIFunc('mediashare', 'edit', 'getUserInfo'))) {
        return false;
    }

    if (!isset($args['ignoreSizeLimits']) || !$args['ignoreSizeLimits']) {
        $fileSize = $args['fileSize'];

        if ($fileSize > $userInfo['mediaSizeLimitSingle']) {
            return LogUtil::registerError($args['filename'].': '.__('Media file too big', $dom));
        }
        if ($fileSize + $userInfo['totalCapacityUsed'] > $userInfo['mediaSizeLimitTotal']) {
            return LogUtil::registerError(__('Media file too big - total quota would be exceeded', $dom));
        }
    }

    // Find a media handler
    if (!($handlerInfo = pnModAPIFunc('mediashare', 'mediahandler', 'getHandlerInfo', array('mimeType' => $args['mimeType'], 'filename' => $args['filename'])))) {
        return false;
    }

    $handlerName = $handlerInfo['handlerName'];

    // Make sure we use sanitized results from the database (like "image/pjpeg" => "image/jpeg")
    $args['mimeType'] = $handlerInfo['mimeType'];
    $args['fileType'] = $handlerInfo['fileType'];

    // Load media handler
    $handlerApi = "media_$handlerName";

    $handler = pnModAPIFunc('mediashare', $handlerApi, 'buildHandler');

    // For OPEN_BASEDIR reasons we move the uploaded file to an accessible place
    // MUST remember to remove it afterwards!!!

    // Create and check tmpfilename
    $tmpDir = pnModGetVar('mediashare', 'tmpDirName');
    if (($tmpFilename = tempnam($tmpDir, 'Upload_')) === false) {
        return LogUtil::registerError(__f("Unable to create a temporary file in '%s'", $tmpDir, $dom).' - '.__('(uploading image)', $dom));
    }

    if (is_uploaded_file($uploadFilename)) {
        if (move_uploaded_file($uploadFilename, $tmpFilename) === false) {
            unlink($tmpFilename);
            return LogUtil::registerError(__f('Unable to move uploaded file from \'%1$s\' to \'%2$s\'', array($uploadFilename, $tmpFilename), $dom).' - '.__('(uploading image)', $dom));
        }
    } else {
        if (!copy($uploadFilename, $tmpFilename)) {
            unlink($tmpFilename);
            return LogUtil::registerError(__f('Unable to copy the file from \'%1$s\' to \'%2$s\'', array($uploadFilename, $tmpFilename), $dom).' - '.__('(adding image)', $dom));
        }
    }

    $args['mediaFilename'] = $tmpFilename;

    // Check mimetypes/media-handler - must be the same (cannot allow the target of a <img src="..."/> to change to a Flash file!)
    if ($mediaItem['mediaHandler'] != $handlerName) {
        return LogUtil::registerError(__('New media type does not match the existing.', $dom));
    }

    // Ask media handler to generate thumbnail and preview files
    if (($thumbnailFilename = tempnam($tmpDir, 'Preview')) === false) {
        @unlink($tmpFilename);
        return LogUtil::registerError(__f("Failed to create the thumbnail file in '%s'.", 'editapi.updateItemFileUpload', $dom));
    }

    if (($previewFilename = tempnam($tmpDir, 'Preview')) === false) {
        @unlink($tmpFilename);
        @unlink($thumbnailFilename);
        return LogUtil::registerError(__f("Failed to create the preview file in '%s'.", 'editapi.updateItemFileUpload', $dom));
    }

    $thumbnailSize = (int)pnModGetVar('mediashare', 'thumbnailSize');

    $previews = array(
        array('outputFilename' => $thumbnailFilename,
              'imageSize'      => $thumbnailSize,
              'isThumbnail'    => true),
        array('outputFilename' => $previewFilename,
              'imageSize'      => (int)pnModGetVar('mediashare', 'previewSize'),
              'isThumbnail'    => false)
    );

    $previewResult = $handler->createPreviews($args, $previews);
    if ($previewResult === false) {
        @unlink($tmpFilename);
        @unlink($thumbnailFilename);
        @unlink($previewFilename);
        return false;
    }

    // Get virtual file system handler

    // Must store items in the same way always (ignore current VFS settings)
    $vfsHandlerName = mediashareGetVFSHandlerName($mediaItem['thumbnailRef']);

    $vfsHandlerApi = "vfs_$vfsHandlerName";
    if (!pnModAPILoad('mediashare', $vfsHandlerApi)) {
        @unlink($tmpFilename);
        @unlink($thumbnailFilename);
        @unlink($previewFilename);
        return LogUtil::registerError(__f('Missing [%1$s] in \'%2$s\'', array($vfsHandlerApi, 'editapi.updateItemFileUpload'), $dom));
    }

    $vfsHandler = pnModAPIFunc('mediashare', $vfsHandlerApi, 'buildHandler');

    // FIXME better step handling
    // Update thumbnail, preview, and original in virtual file system
    if ($vfsHandler === false) {
        @unlink($tmpFilename);
        @unlink($thumbnailFilename);
        @unlink($previewFilename);
        return false;
    }

    if ($vfsHandler->updateFile($mediaItem['thumbnailRef'], $thumbnailFilename) === false) {
        @unlink($tmpFilename);
        @unlink($thumbnailFilename);
        @unlink($previewFilename);
        return false;
    }

    if ($vfsHandler->updateFile($mediaItem['previewRef'], $previewFilename) === false) {
        @unlink($tmpFilename);
        @unlink($thumbnailFilename);
        @unlink($previewFilename);
        return false;
    }

    if ($vfsHandler->updateFile($mediaItem['originalRef'], $tmpFilename) === false) {
        @unlink($tmpFilename);
        @unlink($thumbnailFilename);
        @unlink($previewFilename);
        return false;
    }

    // Clean up
    @unlink($tmpFilename);
    @unlink($thumbnailFilename);
    @unlink($previewFilename);

    // Update media info
    $previewResult[0]['storageId'] = $mediaItem['thumbnailId'];
    $previewResult[1]['storageId'] = $mediaItem['previewId'];
    $previewResult[2]['storageId'] = $mediaItem['originalId'];

    if (!pnModAPIFunc('mediashare', 'edit', 'updateMediaStorage', $previewResult[0])) {
        LogUtil::registerError(__('There was a problem updating the media storage.', $dom));
    }
    if (!pnModAPIFunc('mediashare', 'edit', 'updateMediaStorage', $previewResult[1])) {
        LogUtil::registerError(__('There was a problem updating the media storage.', $dom));
    }
    if (!pnModAPIFunc('mediashare', 'edit', 'updateMediaStorage', $previewResult[2])) {
        LogUtil::registerError(__('There was a problem updating the media storage.', $dom));
    }

    return true;
}

function mediashare_editapi_updateMediaStorage($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $storage = array(
        'id'     => (int)$args['storageId'],
        'width'  => (int)$args['width'],
        'height' => (int)$args['height'],
        'bytes'  => (int)$args['bytes']
    );

    $storage = DBUtil::updateObject($storage, 'mediashare_mediastore', '', 'id');

    if ($storage === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('editapi.updateMediaStorage', 'Could not update the storage.'), $dom));
    }

    return true;
}

function mediashare_editapi_recalcItem($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $mediaId = $args['mediaId'];

    if (!($item = pnModAPIFunc('mediashare', 'user', 'getMediaItem', array('mediaId' => $mediaId)))) {
        return false;
    }

    $tmpDir = pnModGetVar('mediashare', 'tmpDirName');
    if (($tmpFilename = tempnam($tmpDir, 'recalc_')) === false) {
        return LogUtil::registerError(__("Unable to create a temporary file in '%s'", $tmpDir, $dom).' - '.__('(regenerating image)', $dom));
    }

    if (!pnModAPIFunc('mediashare', 'edit', 'copyMediaData', array('mediaId' => $item['id'], 'dstFilename' => $tmpFilename))) {
        return false;
    }

    $ok = pnModAPIFunc('mediashare', 'edit', 'updateItemFileUpload',
                       array('fileSize' => $item['originalBytes'],
                             'mediaId'  => $mediaId,
                             'uploadFilename' => $tmpFilename,
                             'ignoreSizeLimits' => true,
                             'filename' => null,
                             'mimeType' => $item['originalMimeType']));

    unlink($tmpFilename);

    return $ok ? true : false;
}

function mediashare_editapi_copyMediaData($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $mediaId     = $args['mediaId'];
    $dstFilename = $args['dstFilename'];

    if (!($item = pnModAPIFunc('mediashare', 'user', 'getMediaItem', array('mediaId' => $mediaId)))) {
        return false;
    }

    // os/bzylk90bxza3l1wkru7mfxyepqyqmb-org.jpg
    $originalRef = $item['originalRef'];

    if (substr($originalRef, 0, 5) == 'vfsdb') {
        // Fetch and save from database
        if (!($media = pnModAPIFunc('mediashare', 'vfs_db', 'getMedia', array('fileref' => $originalRef)))) {
            return false;
        }
        if (($f = fopen($dstFilename, 'w')) === false) {
            return LogUtil::registerError(__("Failed to open '%s' for write.", $dstFilename, $dom));
        }
        fwrite($f, $media['data']);
        fclose($f);

    } else {
        // Copy from disk
        $originalRef = pnModAPIFunc('mediashare', 'user', 'getRelativeMediadir').$originalRef;
        if (!copy($originalRef, $dstFilename)) {
            return LogUtil::registerError(__f('Unable to copy the file from \'%1$s\' to \'%2$s\'', array($originalRef, $dstFilename), $dom));
        }
    }

    return true;
}

/**
 * Delete media item
 */
function mediashare_editapi_deleteMediaItem($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $mediaId = (int)$args['mediaId'];

    if (!($item = pnModAPIFunc('mediashare', 'user', 'getMediaItem', array('mediaId' => $mediaId)))) {
        return false;
    }

    $albumId  = (int)$item['parentAlbumId'];
    $position = (int)$item['position'];

    if (!($album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId)))) {
        return false;
    }

    // Get virtual file system handler
    $vfsHandlerName = mediashareGetVFSHandlerName($item['thumbnailRef']);

    if (!($vfsHandler = pnModAPIFunc('mediashare', "vfs_$vfsHandlerName", 'buildHandler'))) {
        return false;
    }

    if ($vfsHandler->deleteFile($item['thumbnailRef']) === false) {
        return LogUtil::registerError(__f("Failed to delete media item.", $dom).' '.__('%1$s\'s thumbnail (%2$s).', array($mediaId, $item['thumbnailId']), $dom));
    }

    if ($vfsHandler->deleteFile($item['previewRef']) === false) {
        return LogUtil::registerError(__f("Failed to delete media item.", $dom).' '.__('%1$s\'s preview (%2$s).', array($mediaId, $item['previewId']), $dom));
    }

    if ($vfsHandler->deleteFile($item['originalRef']) === false) {
        return LogUtil::registerError(__f("Failed to delete media item.", $dom).' '.__('%1$s\'s original (%2$s).', array($mediaId, $item['originalId']), $dom));
    }

    $result = DBUtil::deleteObjectByID('mediashare_media', $mediaId, 'id');

    if ($result === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('editapi.deleteMediaItem', 'Could not delete the media item.'), $dom));
    }

    pnModCallHooks('item', 'delete', "media-$mediaId", array('module' => 'mediashare', 'mediaId' => $mediaId));

    // Ensure correct position of the remaining items
    $pntable     = &pnDBGetTables();
    $mediaColumn = $pntable['mediashare_media_column'];

    $sql = "UPDATE $pntable[mediashare_media]
               SET $mediaColumn[position] = $mediaColumn[position] - 1
             WHERE $mediaColumn[parentAlbumId] = '$albumId'
               AND $mediaColumn[position] > '$position'";

    $result = DBUtil::executeSQL($sql);

    if ($result === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('editapi.deleteMediaItem', 'Could not delete the media item.'), $dom));
    }

    // Remove keyword references
    if (!pnModAPIFunc('mediashare', 'edit', 'updateKeywords', array('itemId' => $mediaId, 'type' => 'media', 'keywords' => ''))) {
        return false;
    }

    $storageTable  = $pntable['mediashare_mediastore'];
    $storageColumn = $pntable['mediashare_mediastore_column'];

    // Delete storage
    $sql = "DELETE FROM $storageTable
                  WHERE $storageColumn[id] IN ($item[thumbnailId],$item[previewId],$item[originalId])";

    $result = DBUtil::executeSQL($sql);

    if ($result === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('editapi.deleteMediaItem', 'Could not delete the storage.'), $dom));
    }

    // Update main album item
    if ($album['mainMediaId'] == $mediaId) {
        if (!pnModAPIFunc('mediashare', 'edit', 'setMainItem', array('albumId' => $albumId, 'mediaId' => null))) {
            return false;
        }
    }

    return true;
}

/**
 * Move media item
 */
function mediashare_editapi_moveMediaItem($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $media = array(
        'id'            => (int)$args['mediaId'],
        'parentAlbumId' => (int)$args['albumId']
    );
    $media = DBUtil::updateObject($media, 'mediashare_media', '', 'id');

    if ($media === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('editapi.moveMediaItem', 'Could not move the media item.'), $dom));
    }

    // Check main media item
    if (!($album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => (int)$args['albumId'])))) {
        return false;
    }

    if ($album['mainMediaId'] == (int)$args['mediaId']) {
        $album = array(
            'id'          => (int)$args['albumId'],
            'mainMediaId' => null
        );
        $album = DBUtil::updateObject($album, 'mediashare_albums', '', 'id');

        if ($album === false) {
            return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('editapi.moveMediaItem', 'Could not move the media item.'), $dom));
        }
    }

    return true;
}

/**
 * Main media item
 */
function mediashare_editapi_setMainItem($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $album = array(
        'id'          => (int)$args['albumId'],
        'mainMediaId' => $args['mediaId']
    );

    $album = DBUtil::updateObject($album, 'mediashare_albums', '', 'id');

    if ($album === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('editapi.setMainItem', 'Could not set the main media item.'), $dom));
    }

    return true;
}

/**
 * Arrange items
 */
function mediashare_editapi_arrangeAlbum($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $albumId = (int)$args['albumId'];

    foreach ((array)$args['seq'] as $k => $mediaId)
    {
        $media = array(
            'id'            => $mediaId,
            'parentAlbumId' => (int)$args['albumId'],
            'position'      => $k
        );

        $media = DBUtil::updateObject($media, 'mediashare_media', '', 'id');

        if ($media === false) {
            return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('editapi.arrangeAlbum', 'Could not arrange the album.'), $dom));
        }
    }

    return true;
}

/**
 * User info
 */
function mediashare_editapi_getUserInfo()
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $user = (int)pnUserGetVar('uid');

    $pntable = &pnDBGetTables();

    $mediaTable    = $pntable['mediashare_media'];
    $mediaColumn   = $pntable['mediashare_media_column'];
    $storageTable  = $pntable['mediashare_mediastore'];
    $storageColumn = $pntable['mediashare_mediastore_column'];

    $sql = "SELECT SUM($storageColumn[bytes])
              FROM $mediaTable
         LEFT JOIN $storageTable original
                ON original.$storageColumn[id] = $mediaColumn[originalId]
             WHERE $mediaColumn[ownerId] = '$user'";

    $result = DBUtil::executeSQL($sql);

    if ($result === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('editapi.getUserInfo', 'Could not retrieve the user information.'), $dom));
    }

    $limitTotal = pnModGetVar('mediashare', 'mediaSizeLimitTotal');

    $totalCapacityUsed = DBUtil::marshallObjects($result, array('used'));
    $totalCapacityUsed = $totalCapacityUsed[0]['used'];

    $user = array('totalCapacityUsed'    => $totalCapacityUsed,
                  'totalCapacityLeft'    => ($totalCapacityUsed > $limitTotal ? 0 : $limitTotal - $totalCapacityUsed),
                  'mediaSizeLimitSingle' => pnModGetVar('mediashare', 'mediaSizeLimitSingle'),
                  'mediaSizeLimitTotal'  => $limitTotal);

    return $user;
}

/**
 * Keywords update
 */
function mediashare_editapi_updateKeywords($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $itemId   = (int)$args['itemId'];
    $type     = DataUtil::formatForStore($args['type']);
    $keywords = mediashareStripKeywords($args['keywords']);

    $pntable        = &pnDBGetTables();
    $keywordsColumn = $pntable['mediashare_keywords_column'];

    // First remove existing keywords
    $where  = "$keywordsColumn[itemId] = '$itemId' AND $keywordsColumn[type] = '$type'";
    $result = DBUtil::deleteWhere('mediashare_keywords', $where);

    if ($result === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('editapi.updateKeywords', 'Could not update the keywords.'), $dom));
    }

    // Split keywords string into keywords array
    $keywordsArray = preg_split('/[\s,]+/', $keywords);

    // Insert new keywords
    $keywordsArray = array_filter($keywordsArray); // strip empty keywords

    foreach ($keywordsArray as $keyword)
    {
        $keyword = array(
            'itemId'  => $itemId,
            'type'    => $type,
            'keyword' => $keyword
        );

        $keyword = DBUtil::insertObject($keyword, 'mediashare_keywords', 'itemId');

        if ($keyword === false) {
            return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('editapi.updateKeywords', 'Could not insert the keywords.'), $dom));
        }
    }

    return true;
}

/**
 * Access
 */
function mediashare_editapi_getAccessSettings($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $albumId = (int)$args['albumId'];

    $pntable = &pnDBGetTables();

    $accessTable      = $pntable['mediashare_access'];
    $accessColumn     = $pntable['mediashare_access_column'];
    $membershipTable  = $pntable['group_membership'];
    $membershipColumn = $pntable['group_membership_column'];
    $groupsTable      = $pntable['groups'];
    $groupsColumn     = $pntable['groups_column'];

    if (strpos($membershipColumn['gid'], 'group_membership') === false) {
        $sql = "   SELECT mbr.$membershipColumn[gid],
                          grp.$groupsColumn[name],
                CASE WHEN ISNULL($accessColumn[access]) THEN 0 ELSE $accessColumn[access] END
                     FROM $membershipTable mbr
               INNER JOIN $groupsTable grp
                       ON grp.$groupsColumn[gid] = mbr.$membershipColumn[gid]
                LEFT JOIN $accessTable
                       ON $accessColumn[groupId] = mbr.$membershipColumn[gid]
                      AND $accessColumn[albumId] = '$albumId'";
    } else {
        $sql = "   SELECT $membershipColumn[gid],
                          $groupsColumn[name],
                CASE WHEN ISNULL($accessColumn[access]) THEN 0 ELSE $accessColumn[access] END
                     FROM $membershipTable
               INNER JOIN $groupsTable
                       ON $groupsColumn[gid] = $membershipColumn[gid]
                LEFT JOIN $accessTable
                       ON $accessColumn[groupId] = $membershipColumn[gid]
                      AND $accessColumn[albumId] = '$albumId'";
    }

    $sql .= "
          UNION

                   SELECT -1,
                          '" . __('Everybody', $dom) . "',
                CASE WHEN ISNULL($accessColumn[access]) THEN 0 ELSE $accessColumn[access] END
                     FROM $accessTable
                    WHERE $accessColumn[groupId] = -1
                      AND $accessColumn[albumId] = '$albumId'";

    $result = DBUtil::executeSQL($sql);

    if ($result === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('editapi.getAccessSettings', 'Could not retrieve the access settings.'), $dom));
    }

    $colArray = array('groupId', 'groupName', 'access');
    $groups   = DBUtil::marshallObjects($result, $colArray, true, 'groupId');

    if (!isset($groups[-1])) {
        $groups[] = array('groupId'         => -1,
                          'groupName'       => __('Everybody', $dom),
                          'access'          => 0);
    }

    // select post process
    foreach (array_keys($groups) as $k)
    {
        $groups[$k]['accessView']      = ($groups[$k]['access'] & mediashareAccessRequirementView) != 0;
        $groups[$k]['accessEditAlbum'] = ($groups[$k]['access'] & mediashareAccessRequirementEditAlbum) != 0;
        $groups[$k]['accessEditMedia'] = ($groups[$k]['access'] & mediashareAccessRequirementEditMedia) != 0;
        $groups[$k]['accessAddAlbum']  = ($groups[$k]['access'] & mediashareAccessRequirementAddAlbum) != 0;
        $groups[$k]['accessAddMedia']  = ($groups[$k]['access'] & mediashareAccessRequirementAddMedia) != 0;
    }

    return $groups;
}

function mediashare_editapi_updateAccessSettings($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    // First remove existing access entries
    $result = DBUtil::deleteObjectByID('mediashare_access', (int)$args['albumId'], 'albumId');

    if ($result === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('editapi.updateAccessSettings', 'Could not delete the access registries.'), $dom));
    }

    foreach ((array)$args['access'] as $accessRow)
    {
        $access = ($accessRow['accessView'] ? mediashareAccessRequirementView : 0)
                | ($accessRow['accessEditAlbum'] ? mediashareAccessRequirementEditAlbum : 0)
                | ($accessRow['accessEditMedia'] ? mediashareAccessRequirementEditMedia : 0)
                | ($accessRow['accessAddAlbum'] ? mediashareAccessRequirementAddAlbum : 0)
                | ($accessRow['accessAddMedia'] ? mediashareAccessRequirementAddMedia : 0);

        $record = array(
            'groupId' => (int)$accessRow['groupId'],
            'albumId' => (int)$args['albumId'],
            'access' => $access
        );

        $result = DBUtil::insertObject($record, 'mediashare_access', 'id');

        if ($result === false) {
            return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('editapi.updateAccessSettings', 'Could not insert the access registry.'), $dom));
        }
    }

    return true;
}

function mediashare_editapi_getAccessGroups($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $groups = DBUtil::selectFieldArray('groups', 'name', '', 'name ASC', false, 'gid');

    if ($groups === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('editapi.getAccessGroups', 'Could not retrieve the groups information.'), $dom));
    }

    $result = array();
    $result[] = array('groupId'   => -1,
                      'groupName' => __('Everybody', $dom));

    foreach ($groups as $gid => $gname) {
        $result[] = array('groupId'   => $gid,
                          'groupName' => $gname);
    }

    return $result;
}

function mediashare_editapi_setDefaultAccess($args)
{
    $albumId          = (int)$args['albumId'];
    $usersMayAddAlbum = (bool)isset($args['usersMayAddAlbum']) ? isset($args['usersMayAddAlbum']) : false;

    $access = array(
        array('groupId'         => -1,
              'accessView'      => true,
              'accessEditAlbum' => false,
              'accessEditMedia' => false,
              'accessAddAlbum'  => false,
              'accessAddMedia'  => false),
        array('groupId'         => pnModGetVar('Groups', 'defaultgroup', 1),
              'accessView'      => false,
              'accessEditAlbum' => false,
              'accessEditMedia' => false,
              'accessAddAlbum'  => $usersMayAddAlbum,
              'accessAddMedia'  => false));

    if (!pnModAPIFunc('mediashare', 'edit', 'updateAccessSettings', array('albumId' => $albumId, 'access' => $access))) {
        return false;
    }

    return true;
}

/**
 * External applications
 */
function mediashare_editapi_extappGetApps($args)
{
    $apps = array();

    // Scan for application APIs
    $files = FileUtil::getFiles('modules/mediashare', false, true, 'php', 'f');
    foreach ($files as $file) {
        if (preg_match('/^pnextapp_([-a-zA-Z0-9_]+)api.php$/', $file, $matches)) {
            $apps[] = $matches[1];
        }
    }

    return $apps;
}

function mediashare_editapi_extappLocateApp(&$args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $url = $args['extappURL'];
    if (empty($url)) {
        return true;
    }

    $args['extappData'] = null;

    $appNames = mediashare_editapi_extappGetApps($args);

    $ok = false;
    foreach ($appNames as $appName) {
        $data = pnModAPIFunc('mediashare', "extapp_$appName", 'parseURL', array('url' => $url));
        if ($data != null) {
            $args['extappData'] = array('appName' => $appName, 'data' => $data);
            $ok = true;
            break;
        }
    }

    $args['extappData'] = !empty($args['extappData']) ? serialize($args['extappData']) : '';

    if (!$ok) {
        return LogUtil::registerError(__f('Unrecognized URL %s', array('url' => DataUtil::formatForDisplay($url)), $dom));
    }

    return true;
}

function mediashare_editapi_fetchExternalImages($args)
{
    if (!($album = pnModAPIFunc('mediashare', 'user', 'getAlbumObject', $args))) {
        return false;
    }

    $albumId = $album->albumId;

    // FIXME: don't get album, get extapp instead
    if (($mediaItems = $album->getMediaItems()) === false) {
        return false;
    }

    if (($existingMediaItems = pnModAPIFunc('mediashare', 'user', 'getMediaItems', array('albumId' => $albumId))) === false) {
        return false;
    }

    $existingMediaItemsMap = array();
    foreach ($existingMediaItems as $item) {
        if ($item['mediaHandler'] == 'extapp') {
            $existingMediaItemsMap[$item['originalRef']] = 1;
        }
    }

    $mainMediaItemId = null;
    foreach ($mediaItems as $item)
    {
        if ($item['mediaHandler'] == 'extapp') {
            if (!isset($existingMediaItemsMap[$item['originalRef']])) {
                $thumbnail = array('fileRef'  => $item['thumbnailRef'],
                                   'mimeType' => $item['thumbnailMimeType'],
                                   'width'    => $item['thumbnailWidth'],
                                   'height'   => $item['thumbnailHeight'],
                                   'bytes'    => $item['thumbnailBytes']);

                $preview = array('fileRef'    => $item['previewRef'],
                                 'mimeType'   => $item['previewMimeType'],
                                 'width'      => $item['previewWidth'],
                                 'height'     => $item['previewHeight'],
                                 'bytes'      => $item['previewBytes']);

                $original = array('fileRef'   => $item['originalRef'],
                                  'mimeType'  => $item['originalMimeType'],
                                  'width'     => $item['originalWidth'],
                                  'height'    => $item['originalHeight'],
                                  'bytes'     => $item['originalBytes']);

                $newItem = array('albumId'      => $albumId,
                                 'thumbnail'    => $thumbnail,
                                 'preview'      => $preview,
                                 'original'     => $original,
                                 'title'        => $item['title'],
                                 'keywords'     => $item['keywords'],
                                 'description'  => $item['description'],
                                 'mediaHandler' => $item['mediaHandler']);

                if (!($id = pnModAPIFunc('mediashare', 'edit', 'storeMediaItem', $newItem))) {
                    return false;
                }
                if ($mainMediaItemId === null) {
                    $mainMediaItemId = $id;
                }
            }

            // Unset to indicate that we found this in extapp items
            unset($existingMediaItemsMap[$item['originalRef']]);
        }
    }

    foreach ($existingMediaItems as $item)
    {
        if ($item['mediaHandler'] == 'extapp') {
            if (isset($existingMediaItemsMap[$item['originalRef']])) {
                // Was not found in extapp items - remove it
                if (!pnModAPIFunc('mediashare', 'edit', 'deleteMediaItem', array('mediaId' => $item['id']))) {
                    return false;
                }
            }
        }
    }

    // Set main item
    // Fetch again to see what is available
    if (($existingMediaItems = pnModAPIFunc('mediashare', 'user', 'getMediaItems', array('albumId' => $albumId))) === false) {
        return false;
    }

    if (count($existingMediaItems) > 0) {
        if (!pnModAPIFunc('mediashare', 'edit', 'ensureMainAlbumId', array('albumId' => $albumId, 'mediaId' => $existingMediaItems[0]['id'], 'forceUpdate' => true))) {
            return false;
        }
    }

    return true;
}
