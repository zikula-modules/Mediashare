<?php
// $Id: pneditapi.php,v 1.57 2008/06/21 18:45:44 jornlind Exp $
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2005.
// =======================================================================

require_once("modules/mediashare/common-edit.php");


// =======================================================================
// Add/edit albums
// =======================================================================


function mediashare_editapi_addAlbum(&$args)
{
  // Check basic access (but don't do fine grained Mediashare access check)
  if (!pnSecAuthAction(0, 'mediashare::', '::', ACCESS_EDIT))
    return mediashareErrorAPI(__FILE__, __LINE__, _MSNOAUTH);

  // Set defaults
  if (!array_key_exists('ownerId', $args))
    $args['ownerId'] = pnUserGetVar('uid');
  if (!isset($args['template'])) // Include null test
    $args['template'] = pnModGetVar('mediashare', 'defaultAlbumTemplate');

  // Parse extapp URL and add extapp data
  if (!mediashare_editapi_extappLocateApp($args))
    return false;

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $albumsTable = $pntable['mediashare_albums'];
  $albumsColumn = &$pntable['mediashare_albums_column'];

    // FIXME: what if not logged in - how about 'owner' ???

  $thumbnailSize = (int)pnModGetVar('mediashare', 'thumbnailSize');

  $sql = "INSERT INTO $albumsTable (            
            $albumsColumn[ownerId],
            $albumsColumn[createdDate],
            $albumsColumn[title],
            $albumsColumn[keywords],
            $albumsColumn[summary],
            $albumsColumn[description],
            $albumsColumn[template],
            $albumsColumn[parentAlbumId],
            $albumsColumn[thumbnailSize],
            $albumsColumn[viewKey],
            $albumsColumn[extappURL],
            $albumsColumn[extappData])
          VALUES (
            " . (int)$args['ownerId'] . ",
            NOW(),
            '" . pnVarPrepForStore($args['title']) . "',
            '" . pnVarPrepForStore($args['keywords']) . "',
            '" . pnVarPrepForStore($args['summary']) . "',
            '" . pnVarPrepForStore($args['description']) . "',
            '" . pnVarPrepForStore($args['template']) . "',
            " . (int)$args['parentAlbumId'] . ",
            '" . $thumbnailSize . "',
            round(rand()*9000000000000 + 1000000000000),
            '" . pnVarPrepForStore($args['extappURL']) . "',
            '" . pnVarPrepForStore($args['extappData']) . "')";
  
  $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"Create album" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  $newAlbumId = $dbconn->insert_ID();

  $ok = pnModAPIFunc('mediashare', 'edit', 'updateNestedSetValues');
  if ($ok === false)
    return false;

  $ok = pnModAPIFunc('mediashare', 'edit', 'setDefaultAccess', 
                     array('albumId' => $newAlbumId));
  if ($ok === false)
    return false;

  pnModCallHooks('item', 'create', "album-$newAlbumId", array('module' => 'mediashare',
                                                              'albumId' => $newAlbumId));

  $ok = pnModAPIFunc('mediashare', 'edit', 'updateKeywords',
                     array('itemId'   => $newAlbumId,
                           'type'     => 'album',
                           'keywords' => $args['keywords']));
  if ($ok === false)
    return false;

  $ok = pnModAPIFunc('mediashare', 'edit', 'fetchExternalImages',
                     array('albumId' => $newAlbumId));
  if ($ok === false)
    return false;

  return $newAlbumId;
}


function mediashare_editapi_updateNestedSetValues(&$args)
{
  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  if (true)
  {
    $sql = "call mediashareUpdateNestedSetValues()";
    $dbconn->execute($sql);
    if ($dbconn->errorNo() != 0)
      return mediashareErrorAPI(__FILE__, __LINE__, '"calling mediashareUpdateNestedSetValues()" failed: ' . $dbconn->errorMsg() . " while executing: $sql");
    return true;
  }
  else
  {
    $albumId = 0;
    $count = 0;
    $level = 0;

    return mediashareUpdateNestedSetValues_Rec($albumId, $level, $count, $dbconn, $pntable);
  }
}


function mediashareUpdateNestedSetValues_Rec($albumId, $level, &$count, &$dbconn, &$pntable)
{
  $albumId = (int)$albumId;

  $albumsTable = $pntable['mediashare_albums'];
  $albumsColumn = &$pntable['mediashare_albums_column'];

  $left = $count++;

  $sql = "SELECT $albumsColumn[id]
          FROM $albumsTable
          WHERE $albumsColumn[parentAlbumId] = $albumId";

  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"mediashareUpdateNestedSetValues_Rec" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  for (; !$result->EOF; $result->MoveNext())
  {
    $subAlbumId = $result->fields[0];

    mediashareUpdateNestedSetValues_Rec($subAlbumId, $level+1, $count, $dbconn, $pntable);
  }

  $result->Close();

  $right = $count++;

  $sql = "UPDATE $albumsTable
          SET $albumsColumn[nestedSetLeft] = $left,
              $albumsColumn[nestedSetRight] = $right,
              $albumsColumn[nestedSetLevel] = $level
          WHERE $albumsColumn[id] = $albumId";

  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"mediashareUpdateNestedSetValues_Rec" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  return true;
}


function mediashare_editapi_updateAlbum(&$args)
{
  // Check access
  if (!pnSecAuthAction(0, 'mediashare::', '::', ACCESS_EDIT))
    return mediashareErrorAPI(__FILE__, __LINE__, _MSNOAUTH);

  $albumId = (int)$args['albumId'];

  // Parse extapp URL and add extapp data
  if (!mediashare_editapi_extappLocateApp($args))
    return false;

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $albumsTable = $pntable['mediashare_albums'];
  $albumsColumn = &$pntable['mediashare_albums_column'];

  if (isset($args['template']))
    $templateSql = "$albumsColumn[template] = '" . pnVarPrepForStore($args['template']) . "',";
  else
    $templateSql = '';

    // FIXME: what if not logged in - how about 'owner' ???

  $thumbnailSize = (int)pnModGetVar('mediashare', 'thumbnailSize');

  $sql = "UPDATE $albumsTable SET
            $albumsColumn[title] = '" . pnVarPrepForStore($args['title']) . "',
            $albumsColumn[keywords] = '" . pnVarPrepForStore($args['keywords']) . "',
            $albumsColumn[summary] = '" . pnVarPrepForStore($args['summary']) . "',
            $albumsColumn[description] = '" . pnVarPrepForStore($args['description']) . "',
            $templateSql
            $albumsColumn[extappURL] = '" . pnVarPrepForStore($args['extappURL']) . "',
            $albumsColumn[extappData] = '" . pnVarPrepForStore($args['extappData']) . "'
          WHERE $albumsColumn[id] = $albumId";
  
  $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"Update album" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  $ok = pnModAPIFunc('mediashare', 'edit', 'updateKeywords',
                     array('itemId'   => $albumId,
                           'type'     => 'album',
                           'keywords' => $args['keywords']));
  if ($ok === false)
    return false;

  $ok = pnModAPIFunc('mediashare', 'edit', 'fetchExternalImages',
                     array('albumId' => $albumId));
  if ($ok === false)
    return false;

  return true;
}


function mediashare_editapi_deleteAlbum(&$args)
{
    // Check access
  if (!pnSecAuthAction(0, 'mediashare::', '::', ACCESS_EDIT))
    return mediashareErrorAPI(__FILE__, __LINE__, _MSNOAUTH);

  if (!pnModAPILoad('mediashare', 'user'))
    return mediashareErrorAPI(__FILE__, __LINE__, 'Failed to load Mediashare user API');

  $albumId = (int)$args['albumId'];

  if ($albumId == 1)
    return mediashareErrorAPI(__FILE__, __LINE__, 'You cannot delete the top album');

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $albumsTable = $pntable['mediashare_albums'];
  $albumsColumn = &$pntable['mediashare_albums_column'];
  $mediaTable = $pntable['mediashare_media'];
  $mediaColumn = &$pntable['mediashare_media_column'];

  $ok = pnModAPIFunc('mediashare', 'edit', 'updateAccessSettings',
                     array('albumId' => $albumId,
                           'access'  => array()));
  if ($ok === false)
    return false;

  $ok = mediashareDeleteAlbumRec($dbconn, $albumsTable, $albumsColumn, $mediaTable, $mediaColumn, $albumId);

  $ok = $ok && pnModAPIFunc('mediashare', 'edit', 'updateNestedSetValues');
  if ($ok === false)
    return false;

  $ok = pnModAPIFunc('mediashare', 'edit', 'updateKeywords',
                     array('itemId'   => $albumId,
                           'type'     => 'album',
                           'keywords' => ''));
  if ($ok === false)
    return false;

  return true;
}


function mediashareDeleteAlbumRec(&$dbconn, $albumsTable, &$albumsColumn, $mediaTable, &$mediaColumn, $albumId)
{
    // Get album info 
  $album = pnModAPIFunc('mediashare', 'user', 'getAlbum',
                        array( 'albumId' => $albumId ));
  if ($album === false)
    return mediashareErrorAPIGet();

    // Fetch and delete sub-abums

  $sql = "SELECT $albumsColumn[id]
          FROM $albumsTable
          WHERE $albumsColumn[parentAlbumId] = $albumId";

  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"Delete album" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  $albumIds = array();
  for (; !$result->EOF; $result->MoveNext())
  {
    $albumIds[] = $result->fields[0];
  }
  $result->Close();

  foreach ($albumIds as $subAlbumId)
    if (mediashareDeleteAlbumRec($dbconn, $albumsTable, $albumsColumn, $mediaTable, $mediaColumn, $subAlbumId) === false)
      return false;

    // Fetch and delete media items

  $sql = "SELECT $mediaColumn[id]
          FROM $mediaTable
          WHERE $mediaColumn[parentAlbumId] = $albumId";

  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"Delete album" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  $mediaIds = array();
  for (; !$result->EOF; $result->MoveNext())
  {
    $mediaIds[] = $result->fields[0];
  }
  $result->Close();

  foreach ($mediaIds as $mediaId)
  {
    $ok = pnModAPIFunc('mediashare', 'edit', 'deleteMediaItem',
                       array('mediaId' => $mediaId) );
    if ($ok === false)
      return false;
  }

    // Delete album

  $sql = "DELETE FROM $albumsTable
          WHERE $albumsColumn[id] = $albumId";
  
  $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"Delete album" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  pnModCallHooks('item', 'delete', "album-$albumId", array('module' => 'mediashare',
                                                           'albumId' => $albumId));

  return true;
}


// =======================================================================
// Move album
// =======================================================================

function mediashare_editapi_moveAlbum(&$args)
{
  // Check access
  if (!pnSecAuthAction(0, 'mediashare::', '::', ACCESS_EDIT))
    return mediashareErrorAPI(__FILE__, __LINE__, _MSNOAUTH);

  $albumId = (int)$args['albumId'];
  $dstAlbumId = (int)$args['dstAlbumId'];

  if ($albumId == 1)
    return mediashareErrorAPI(__FILE__, __LINE__, 'Cannot move top album');

  if ($albumId == $dstAlbumId)
    return mediashareErrorAPI(__FILE__, __LINE__, 'Cannot move album to self');

  if ($dstAlbumId == 0)
    return mediashareErrorAPI(__FILE__, __LINE__, 'Cannot move album outsite root album');

  $album = pnModAPIFunc('mediashare', 'user', 'getAlbum',
                         array('albumId' => $albumId));
  if ($album === false)
    return false;

  $dstAlbum = pnModAPIFunc('mediashare', 'user', 'getAlbum',
                           array('albumId' => $dstAlbumId));
  if ($dstAlbum === false)
    return false;

  if (   !mediashareAccessAlbum($albumId, mediashareAccessRequirementEditAccess, '')
      || !mediashareAccessAlbum($dstAlbumId, mediashareAccessRequirementAddAlbum, ''))
    return mediashareErrorAPI(__FILE__, __LINE__, _MSNOAUTH);

  $isChild = pnModAPIFunc('mediashare', 'edit', 'isChildAlbum',
                          array('albumId' => $dstAlbumId,
                                'parentAlbumId' => $albumId));
  if ($isChild === true)
    return mediashareErrorAPI(__FILE__, __LINE__, 'Cannot move album below self');

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $albumsTable = $pntable['mediashare_albums'];
  $albumsColumn = &$pntable['mediashare_albums_column'];

  $sql = "UPDATE $albumsTable 
          SET $albumsColumn[parentAlbumId] = $dstAlbumId
          WHERE $albumsColumn[id] = $albumId";
  
  $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"Move album" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  $ok = pnModAPIFunc('mediashare', 'edit', 'updateNestedSetValues');
  if ($ok === false)
    return false;

  return true;
}


function mediashare_editapi_isChildAlbum(&$args)
{
  if (!pnModAPILoad('mediashare', 'user'))
    return mediashareErrorAPI(__FILE__, __LINE__, 'Failed to load Mediashare user API');

  $albumId = (int)$args['albumId'];
  $parentAlbumId = (int)$args['parentAlbumId'];

  $album = pnModAPIFunc('mediashare', 'user', 'getAlbum',
                         array('albumId' => $albumId));
  if ($album === false)
    return false;

  $parentAlbum = pnModAPIFunc('mediashare', 'user', 'getAlbum',
                              array('albumId' => $parentAlbumId));
  if ($parentAlbum === false)
    return false;

  return    $parentAlbum['nestedSetLeft'] < $album['nestedSetLeft']
         && $parentAlbum['nestedSetRight'] > $album['nestedSetRight'];
}


// =======================================================================
// Adding media items
// =======================================================================

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
function mediashare_editapi_addMediaItem(&$args)
{
  if (!array_key_exists('albumId', $args))
    return mediashareErrorAPI(__FILE__, __LINE__, 'Missing albumId in mediashare_editapi_addMediaItem');  

  $albumId = (int)$args['albumId'];

  // Calculate title
  $mediaTitle = $args['title'];
  if ($mediaTitle == '')
  {
    $mediaTitle = $args['filename'];
    if (!(($p=strrpos($mediaTitle,'.')) === false))  // Strip trailing extension
      $mediaTitle = substr($mediaTitle,0,$p);
  }
  $mediaTitle = str_replace('_', ' ', $mediaTitle);

  // Check upload limits

  $userInfo = pnModAPIFunc('mediashare', 'edit', 'getUserInfo');
  if ($userInfo === false)
    return mediashareErrorAPIGet();

  if (!array_key_exists('ignoreSizeLimits',$args)  ||  !$args['ignoreSizeLimits'])
  {
    $fileSize = $args['fileSize'];

    if ($fileSize > $userInfo['mediaSizeLimitSingle'])
      return mediashareErrorAPI(null, null, "$mediaTitle: " . _MSMEDIAITEMTOOBIG);  
    
    if ($fileSize + $userInfo['totalCapacityUsed'] > $userInfo['mediaSizeLimitTotal'])
      return mediashareErrorAPI(__FILE__, __LINE__, _MSMEDIAITEMEXEEDQUOTA);
  }

  // Find a media handler

  if (!pnModAPILoad('mediashare', 'mediahandler'))
    return mediashareErrorAPI(__FILE__, __LINE__, 'Failed to load Mediashare mediahandler API');

  $handlerInfo = pnModAPIFunc('mediashare', 'mediahandler', 'getHandlerInfo',
                              array('mimeType' => $args['mimeType'],
                                    'filename' => $args['filename']));
  if ($handlerInfo === false)
    return false;

  $handlerName = $handlerInfo['handlerName'];

  // Make sure we use sanitized results from the database (like "image/pjpeg" => "image/jpeg")
  $args['mimeType'] = $handlerInfo['mimeType'];
  $args['fileType'] = $handlerInfo['fileType'];


  // Load media handler

  $handlerApi = "media_$handlerName";
  if (!pnModAPILoad('mediashare', $handlerApi))
    return mediashareErrorAPI(__FILE__, __LINE__, "Missing '$handlerApi' handler in mediashare_editapi_addMediaItem");

  $handler = pnModAPIFunc('mediashare', $handlerApi, 'buildHandler');


  // Ask media handler to generate thumbnail and preview images

  $tmpDir = pnModGetVar('mediashare', 'tmpDirName');
  if (($thumbnailFilename = tempnam($tmpDir, 'Preview')) === false)
    return mediashareErrorAPI(__FILE__, __LINE__, "Failed to create thumbnail filename in mediashare_editapi_addMediaItem");

  if (($previewFilename = tempnam($tmpDir, 'Preview')) === false)
  {
    @unlink($thumbnailFilename);
    return mediashareErrorAPI(__FILE__, __LINE__, "Failed to create preview filename in mediashare_editapi_addMediaItem");
  }

  $thumbnailSize = (int)pnModGetVar('mediashare', 'thumbnailSize');

  $previews = array( array('outputFilename' => $thumbnailFilename,
                           'imageSize'      => $thumbnailSize,
                           'isThumbnail'    => true),
                     array('outputFilename' => $previewFilename,
                           'imageSize'      => (int)pnModGetVar('mediashare', 'previewSize'),
                           'isThumbnail'    => false) );

  $previewResult = $handler->createPreviews($args, $previews);
  if ($previewResult === false)
  {
    @unlink($thumbnailFilename);
    @unlink($previewFilename);
    return false;
  }

  // Get virtual file system handler
  $vfsHandlerName = pnModGetVar('mediashare', 'vfs');
  $vfsHandlerApi = "vfs_$vfsHandlerName";
  if (!pnModAPILoad('mediashare', $vfsHandlerApi))
  {
    @unlink($thumbnailFilename);
    @unlink($previewFilename);
    return mediashareErrorAPI(__FILE__, __LINE__, "Missing '$vfsHandlerApi' in mediashare_editapi_addMediaItem");
  }

  $vfsHandler = pnModAPIFunc('mediashare', $vfsHandlerApi, 'buildHandler');
  if ($vfsHandler === false)
    return false;

  // Store thumbnail, preview, and original in virtual file system

  $baseFileRef = $vfsHandler->getNewFileReference();
  $previewResult[0]['baseFileRef'] = $baseFileRef;
  $previewResult[0]['fileMode'] = 'tmb';
  $previewResult[1]['baseFileRef'] = $baseFileRef;
  $previewResult[1]['fileMode'] = 'pre';
  $previewResult[2]['baseFileRef'] = $baseFileRef;
  $previewResult[2]['fileMode'] = 'org';

  $result = array();

  if (($thumbnailFileRef=$vfsHandler->createFile($thumbnailFilename, $previewResult[0])) === false)
  {
    @unlink($thumbnailFilename);
    @unlink($previewFilename);
    return false;
  }
  $previewResult[0]['fileRef'] = $result['thumbnailFileRef'] = $thumbnailFileRef;

  if (($originalFileRef=$vfsHandler->createFile($args['mediaFilename'],$previewResult[2])) === false)
  {
    $vfsHandler->deleteFile($thumbnailFileRef);
    $vfsHandler->deleteFile($previewFileRef);
    @unlink($thumbnailFilename);
    @unlink($previewFilename);
    return false;
  }
  $previewResult[2]['fileRef'] = $result['originalFileRef'] = $originalFileRef;

  if (!array_key_exists('useOriginal',$previewResult[1]) || !(bool)$previewResult[1]['useOriginal'])
  {
    if (($previewFileRef=$vfsHandler->createFile($previewFilename, $previewResult[1])) === false)
    {
      $vfsHandler->deleteFile($thumbnailFileRef);
      @unlink($thumbnailFilename);
      @unlink($previewFilename);
      return false;
    }
    $previewResult[1]['fileRef'] = $result['previewFileRef'] = $previewFileRef;
  }
  else
    $previewResult[1]['fileRef'] = $result['previewFileRef'] = $originalFileRef;


  $id = pnModAPIFunc('mediashare', 'edit', 'storeMediaItem',
                     array('title'        => $mediaTitle,
                           'keywords'     => $args['keywords'],
                           'description'  => $args['description'],
                           'ownerId'      => array_key_exists('ownerId',$args) ? $args['ownerId'] : pnUserGetVar('uid'),
                           'albumId'      => $albumId,
                           'mediaHandler' => $handlerName,
                           'thumbnail'    => $previewResult[0],
                           'preview'      => $previewResult[1],
                           'original'     => $previewResult[2]));
  if ($id === false)
  {
    $vfsHandler->deleteFile($thumbnailFileRef);
    $vfsHandler->deleteFile($previewFileRef);
    $vfsHandler->deleteFile($originalFileRef);
    @unlink($thumbnailFilename);
    @unlink($previewFilename);
    return false;
  }

  pnModCallHooks('item', 'create', "media-$id", array('module' => 'mediashare',
                                                      'mediaId' => $id));

  @unlink($thumbnailFilename);
  @unlink($previewFilename);

  $ok = pnModAPIFunc('mediashare', 'edit', 'ensureMainAlbumId',
                     array('albumId' => $albumId,
                           'mediaId' => $id));
  if ($ok === false)
  {
    // Don't clean up, just report error. Upload actually worked.
    return false; 
  }

  $result['message'] = "$mediaTitle: " . _MSADDEDMEDIAITEM;
  $result['mediaId'] = $id;

  return $result;
}


function mediashare_editapi_storeMediaItem(&$args)
{
  $albumId = (int)$args['albumId'];

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $mediaTable = $pntable['mediashare_media'];
  $mediaColumn = &$pntable['mediashare_media_column'];

  if (!array_key_exists('ownerId', $args))
    $args['ownerId'] = pnUserGetVar('uid');

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

  $sql = "INSERT INTO $mediaTable (
            $mediaColumn[ownerId],
            $mediaColumn[createdDate],
            $mediaColumn[title],
            $mediaColumn[keywords],
            $mediaColumn[description],
            $mediaColumn[parentAlbumId],
            $mediaColumn[position],
            $mediaColumn[mediaHandler],
            $mediaColumn[thumbnailId],
            $mediaColumn[previewId],
            $mediaColumn[originalId])
          VALUES (
            " . (int)$args['ownerId'] . ",
            NOW(),
            '" . pnVarPrepForStore($args['title']) . "',
            '" . pnVarPrepForStore(mediashareStripKeywords($args['keywords'])) . "',
            '" . pnVarPrepForStore($args['description']) . "',
            " . $albumId . ",
            " . $position . ",
            '" . pnVarPrepForStore($args['mediaHandler']) . "',
            '" . pnVarPrepForStore($thumbnailId) . "',
            '" . pnVarPrepForStore($previewId) . "',
            '" . pnVarPrepForStore($originalId) . "')";
  
  $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"Insert media item" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  $newMediaId = $dbconn->insert_ID();

  $ok = pnModAPIFunc('mediashare', 'edit', 'updateKeywords',
                     array('itemId'   => $newMediaId,
                           'type'     => 'media',
                           'keywords' => $args['keywords']));
  if ($ok === false)
    return false;

  return $newMediaId;
}


function mediashare_editapi_registerMediaItem(&$args)
{
  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $mediaTable = $pntable['mediashare_mediastore'];
  $mediaColumn = &$pntable['mediashare_mediastore_column'];

  $sql = "INSERT INTO $mediaTable (            
            $mediaColumn[fileRef],
            $mediaColumn[mimeType],
            $mediaColumn[width],
            $mediaColumn[height],
            $mediaColumn[bytes])
          VALUES (
            '" . pnVarPrepForStore($args['fileRef']) . "',
            '" . pnVarPrepForStore($args['mimeType']) . "',
            '" . pnVarPrepForStore($args['width']) . "',
            '" . pnVarPrepForStore($args['height']) . "',
            '" . pnVarPrepForStore($args['bytes']) . "')";
  
  $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"Insert media item" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  $id = $dbconn->insert_ID();

  return $id;
}


function mediashareGetNewPosition($albumId)
{
  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $mediaTable = $pntable['mediashare_media'];
  $mediaColumn = &$pntable['mediashare_media_column'];

  $sql = "SELECT MAX($mediaColumn[position])
          FROM $mediaTable
          WHERE $mediaColumn[parentAlbumId] = $albumId";

  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"GetNewPosition" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  $position = $result->fields[0];
  $result->Close();

  return $position == null ? 0 : $position+1;
}


function mediashare_editapi_ensureMainAlbumId($args)
{
    // Argument check
  if (!isset($args['albumId']))
    return mediashareErrorAPI(__FILE__, __LINE__, 'Missing albumId in mediashare_userapi_ensureMainAlbumId');
  if (!isset($args['mediaId']))
    return mediashareErrorAPI(__FILE__, __LINE__, 'Missing mediaId in mediashare_userapi_ensureMainAlbumId');

  $forceUpdate = isset($args['forceUpdate']) && $args['forceUpdate'];

    // Check access
  if (!pnSecAuthAction(0, 'mediashare::', '::', ACCESS_EDIT))
    return mediashareErrorAPI(__FILE__, __LINE__, _MSNOAUTH);

  $albumId = (int)$args['albumId'];
  $mediaId = (int)$args['mediaId'];

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $albumsTable   = $pntable['mediashare_albums'];
  $albumsColumn  = $pntable['mediashare_albums_column'];

  $sql = "UPDATE $albumsTable
            SET $albumsColumn[mainMediaId] = $mediaId
          WHERE $albumsColumn[id] = $albumId";

  if (!$forceUpdate)
    $sql .= " AND $albumsColumn[mainMediaId] IS NULL";
  
  //echo "<pre>$sql</pre>"; exit(0);
  $dbconn->execute($sql);
  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"getFirstItemInAlbum" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  return true;
}


// =======================================================================
// Update media item
// =======================================================================

function mediashare_editapi_updateItem(&$args)
{
  // Check access
  if (!pnSecAuthAction(0, 'mediashare::', '::', ACCESS_EDIT))
    return mediashareErrorAPI(__FILE__, __LINE__, _MSNOAUTH);

  $mediaId = (int)$args['mediaId'];

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $mediaTable = $pntable['mediashare_media'];
  $mediaColumn = &$pntable['mediashare_media_column'];

  $sql = "UPDATE $mediaTable SET
            $mediaColumn[title] = '" . pnVarPrepForStore($args['title']) . "',
            $mediaColumn[keywords] = '" . pnVarPrepForStore($args['keywords']) . "',
            $mediaColumn[description] = '" . pnVarPrepForStore($args['description']) . "'
          WHERE $mediaColumn[id] = $mediaId";
  
  $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"Update media" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  $ok = pnModAPIFunc('mediashare', 'edit', 'updateKeywords',
                     array('itemId'   => $mediaId,
                           'type'     => 'media',
                           'keywords' => $args['keywords']));
  if ($ok === false)
    return false;

  $ok = pnModAPIFunc('mediashare', 'edit', 'updateItemFileUpload', $args);
  if ($ok === false)
    return false;

  return true;
}


function mediashare_editapi_updateItemFileUpload(&$args)
{
  // Ignore empty uploads
  if (!isset($args['fileSize']) || $args['fileSize'] == 0)
    return true;

  $mediaId = (int)$args['mediaId'];
  $uploadFilename = $args['uploadFilename'];

  // Fetch media data - we need it to locate the media files
  $mediaItem = pnModAPIFunc('mediashare', 'user', 'getMediaItem',
                            array('mediaId' => $mediaId));
  if ($mediaItem === false)
    return false;

  // Must store items in the same way always (ignore current VFS settings)
  $vfsHandlerName = mediashareGetVFSHandlerName($mediaItem['thumbnailRef']);

  // Check upload limits

  $userInfo = pnModAPIFunc('mediashare', 'edit', 'getUserInfo');
  if ($userInfo === false)
    return false;

  if (!array_key_exists('ignoreSizeLimits',$args)  ||  !$args['ignoreSizeLimits'])
  {
    $fileSize = $args['fileSize'];

    if ($fileSize > $userInfo['mediaSizeLimitSingle'])
      return mediashareErrorAPI(null, null, "$args[filename]: " . _MSMEDIAITEMTOOBIG);  
    
    if ($fileSize + $userInfo['totalCapacityUsed'] > $userInfo['mediaSizeLimitTotal'])
      return mediashareErrorAPI(__FILE__, __LINE__, _MSMEDIAITEMEXEEDQUOTA);
  }


  // Find a media handler

  if (!pnModAPILoad('mediashare', 'mediahandler'))
    return mediashareErrorAPI(__FILE__, __LINE__, 'Failed to load Mediashare mediahandler API');

  $handlerInfo = pnModAPIFunc('mediashare', 'mediahandler', 'getHandlerInfo',
                              array('mimeType' => $args['mimeType'],
                                    'filename' => $args['filename']));
  if ($handlerInfo === false)
    return false;

  $handlerName = $handlerInfo['handlerName'];

  // Make sure we use sanitized results from the database (like "image/pjpeg" => "image/jpeg")
  $args['mimeType'] = $handlerInfo['mimeType'];
  $args['fileType'] = $handlerInfo['fileType'];


  // Load media handler

  $handlerApi = "media_$handlerName";
  if (!pnModAPILoad('mediashare', $handlerApi))
    return mediashareErrorAPI(__FILE__, __LINE__, "Missing '$handlerApi' handler in mediashare_editapi_addMediaItem");

  $handler = pnModAPIFunc('mediashare', $handlerApi, 'buildHandler');


  // For OPEN_BASEDIR reasons we move the uploaded file to an accessible place
  // MUST remember to remove it afterwards!!!

    // Create and check tmpfilename
  $tmpDir = pnModGetVar('mediashare', 'tmpDirName');
  if (($tmpFilename = tempnam($tmpDir, 'Upload_')) === false)
    return mediashareErrorAPI(__FILE__, __LINE__, "Unable to create tmpFilename in '$tmpDir' (uploading image)");

  if (is_uploaded_file($uploadFilename))
  {
    if (move_uploaded_file($uploadFilename, $tmpFilename) === false)
    {
      unlink($tmpFilename);
      return mediashareErrorAPI(__FILE__, __LINE__, "Unable to move uploaded file from '$uploadFilename' to '$tmpFilename' (uploading image)");
    }
  }
  else
  {
    if (!copy($uploadFilename, $tmpFilename))
    {
      unlink($tmpFilename);
      return mediashareErrorAPI(__FILE__, __LINE__, "Unable to copy file from '$uploadFilename' to '$tmpFilename' (adding image)");
    }
  }

  $args['mediaFilename'] = $tmpFilename;

  // Check mimetypes/media-handler - must be the same (cannot allow the target of a <img src="..."/> to change to a Flash file!)

  if ($mediaItem['mediaHandler'] != $handlerName)
    return  mediashareErrorAPI(__FILE__, __LINE__, "New media type does not match the existing");

  // Ask media handler to generate thumbnail and preview files

  if (($thumbnailFilename = tempnam($tmpDir, 'Preview')) === false)
  {
    @unlink($tmpFilename);
    return mediashareErrorAPI(__FILE__, __LINE__, "Failed to create thumbnail filename in updateItemFileUpload");
  }

  if (($previewFilename = tempnam($tmpDir, 'Preview')) === false)
  {
    @unlink($tmpFilename);
    @unlink($thumbnailFilename);
    return mediashareErrorAPI(__FILE__, __LINE__, "Failed to create preview filename in updateItemFileUpload");
  }

  $thumbnailSize = (int)pnModGetVar('mediashare', 'thumbnailSize');

  $previews = array( array('outputFilename' => $thumbnailFilename,
                           'imageSize'      => $thumbnailSize,
                           'isThumbnail'    => true),
                     array('outputFilename' => $previewFilename,
                           'imageSize'      => (int)pnModGetVar('mediashare', 'previewSize'),
                           'isThumbnail'    => false) );

  $previewResult = $handler->createPreviews($args, $previews);
  if ($previewResult === false)
  {
    @unlink($tmpFilename);
    @unlink($thumbnailFilename);
    @unlink($previewFilename);
    return false;
  }

  // Get virtual file system handler

  // Must store items in the same way always (ignore current VFS settings)
  $vfsHandlerName = mediashareGetVFSHandlerName($mediaItem['thumbnailRef']);

  $vfsHandlerApi = "vfs_$vfsHandlerName";
  if (!pnModAPILoad('mediashare', $vfsHandlerApi))
  {
    @unlink($tmpFilename);
    @unlink($thumbnailFilename);
    @unlink($previewFilename);
    return mediashareErrorAPI(__FILE__, __LINE__, "Missing '$vfsHandlerApi' in mediashare_editapi_addMediaItem");
  }

  $vfsHandler = pnModAPIFunc('mediashare', $vfsHandlerApi, 'buildHandler');

  // Update thumbnail, preview, and original in virtual file system

  if ($vfsHandler === false)
  {
    @unlink($tmpFilename);
    @unlink($thumbnailFilename);
    @unlink($previewFilename);
    return false;
  }

  if ($vfsHandler->updateFile($mediaItem['thumbnailRef'], $thumbnailFilename) === false)
  {
    @unlink($tmpFilename);
    @unlink($thumbnailFilename);
    @unlink($previewFilename);
    return false;
  }

  if ($vfsHandler->updateFile($mediaItem['previewRef'], $previewFilename) === false)
  {
    @unlink($tmpFilename);
    @unlink($thumbnailFilename);
    @unlink($previewFilename);
    return false;
  }

  if ($vfsHandler->updateFile($mediaItem['originalRef'], $tmpFilename) === false)
  {
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
  
  //var_dump($previewResult); exit(0);
  $previewResult[0]['storageId'] = $mediaItem['thumbnailId'];
  $previewResult[1]['storageId'] = $mediaItem['previewId'];
  $previewResult[2]['storageId'] = $mediaItem['originalId'];

  if (pnModAPIFunc('mediashare', 'edit', 'updateMediaStorage',
                   $previewResult[0]) === false)
    return false;
  if (pnModAPIFunc('mediashare', 'edit', 'updateMediaStorage',
                   $previewResult[1]) === false)
    return false;
  if (pnModAPIFunc('mediashare', 'edit', 'updateMediaStorage',
                   $previewResult[2]) === false)
    return false;

  return true;
}


function mediashare_editapi_updateMediaStorage($args)
{
  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $storageTable  = $pntable['mediashare_mediastore'];
  $storageColumn = $pntable['mediashare_mediastore_column'];

  $sql = "UPDATE $storageTable SET
            $storageColumn[width] = " . (int)$args['width'] . ",
            $storageColumn[height] = " . (int)$args['height'] . ",
            $storageColumn[bytes] = " . (int)$args['bytes'] . "
          WHERE $storageColumn[id] = " . (int)$args['storageId'];
  
  $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, 'Update storage failed: ' . $dbconn->errorMsg() . " while executing: $sql");
  
  return true;
}


function mediashare_editapi_recalcItem($args)
{
  $mediaId = $args['mediaId'];

  $item = pnModAPIFunc('mediashare', 'user', 'getMediaItem',
                       array('mediaId' => $mediaId));
  if ($item === false)
    return false;

  $tmpDir = pnModGetVar('mediashare', 'tmpDirName');
  if (($tmpFilename = tempnam($tmpDir, 'recalc_')) === false)
    return mediashareErrorAPI(__FILE__, __LINE__, "Unable to create tmpFilename in '$tmpDir' (regenerating image)");

  $ok = pnModAPIFunc('mediashare', 'edit', 'copyMediaData',
                     array('mediaId' => $item['id'],
                           'dstFilename' => $tmpFilename));
  if ($ok === false)
    return false;


  $ok = pnModAPIFunc('mediashare', 'edit', 'updateItemFileUpload',
                     array('fileSize' => $item['originalBytes'],
                           'mediaId' => $mediaId,
                           'uploadFilename' => $tmpFilename,
                           'ignoreSizeLimits' => true,
                           'filename' => null,
                           'mimeType' => $item['originalMimeType']));
  unlink($tmpFilename);
  if ($ok === false)
    return false;

  return true;
}


function mediashare_editapi_copyMediaData($args)
{
  $mediaId = $args['mediaId'];
  $dstFilename = $args['dstFilename'];

  $item = pnModAPIFunc('mediashare', 'user', 'getMediaItem',
                       array('mediaId' => $mediaId));
  if ($item === false)
    return false;

  // os/bzylk90bxza3l1wkru7mfxyepqyqmb-org.jpg
  // 
  $originalRef = $item['originalRef'];

  if (substr($originalRef, 0, 5) == 'vfsdb')
  {
    // Fetch and save from database
    $media = pnModAPIFunc('mediashare', 'vfs_db', 'getMedia', array('fileref' => $originalRef));
    if ($media === false)
      return false;

    if (($f=fopen($dstFilename,'w')) === false)
      return mediashareErrorAPI(__FILE__, __LINE__, "Failed to open $dstFilename for write");
    fwrite($f, $media['data']);
    fclose($f);
  }
  else
  {
    // Copy from disk
    if (!copy("mediashare/$originalRef", $dstFilename))
      return mediashareErrorAPI(__FILE__, __LINE__, "Failed to copy 'mediashare/$originalRef' to $dstFilename");
  }

  return true;
}


// =======================================================================
// Delete media item
// =======================================================================

function mediashare_editapi_deleteMediaItem(&$args)
{
  $mediaId = (int)$args['mediaId'];

  if (!pnModAPILoad('mediashare', 'user'))
    return mediashareErrorAPI(__FILE__, __LINE__, 'Failed to load Mediashare user API');

  $item = pnModAPIFunc('mediashare', 'user', 'getMediaItem',
                       array( 'mediaId' => $mediaId ));
  if ($item === false)
    return false;

  $albumId  = (int)$item['parentAlbumId'];
  $position = (int)$item['position'];

  $album = pnModAPIFunc('mediashare', 'user', 'getAlbum',
                        array( 'albumId' => $albumId ));
  if ($album === false)
    return false;

  // Get virtual file system handler
  $vfsHandlerName = mediashareGetVFSHandlerName($item['thumbnailRef']);
  $vfsHandlerApi = "vfs_$vfsHandlerName";
  if (!pnModAPILoad('mediashare', $vfsHandlerApi))
    return mediashareErrorAPI(__FILE__, __LINE__, "Missing '$vfsHandlerApi' in mediashare_editapi_deleteMediaItem");
  $vfsHandler = pnModAPIFunc('mediashare', $vfsHandlerApi, 'buildHandler');
  if ($vfsHandler === false)
    return false;

  if ($vfsHandler->deleteFile($item['thumbnailRef']) === false)
    return mediashareErrorAPI(__FILE__, __LINE__, "Failed to delete media item '$mediaId's thumbnail ($item[thumbnailId])");
  if ($vfsHandler->deleteFile($item['previewRef']) === false)
    return mediashareErrorAPI(__FILE__, __LINE__, "Failed to delete media item '$mediaId's preview ($item[previewId])");
  if ($vfsHandler->deleteFile($item['originalRef']) === false)
    return mediashareErrorAPI(__FILE__, __LINE__, "Failed to delete media item '$mediaId's original ($item[originalId])");

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $mediaTable = $pntable['mediashare_media'];
  $mediaColumn = &$pntable['mediashare_media_column'];

  // Remove media info

  $sql = "DELETE FROM $mediaTable
          WHERE $mediaColumn[id] = $mediaId";
  
  $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"Delete media" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  pnModCallHooks('item', 'delete', "media-$mediaId", array('module' => 'mediashare',
                                                           'mediaId' => $mediaId));

  // Ensure correct position of the remaining items

  $sql = "UPDATE $mediaTable 
          SET $mediaColumn[position] = $mediaColumn[position] - 1
          WHERE     $mediaColumn[parentAlbumId] = $albumId
                AND $mediaColumn[position] > $position";

  $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"Delete media" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  // Remove keyword references
  $ok = pnModAPIFunc('mediashare', 'edit', 'updateKeywords',
                     array('itemId'   => $mediaId,
                           'type'     => 'media',
                           'keywords' => ''));
  if ($ok === false)
    return false;

  $storageTable  = $pntable['mediashare_mediastore'];
  $storageColumn = $pntable['mediashare_mediastore_column'];

  // Delete storage
  $sql = "DELETE FROM $storageTable WHERE $storageColumn[id] IN ($item[thumbnailId],$item[previewId],$item[originalId])";
  $dbconn->execute($sql);
  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, 'Delete storage failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  // Update main album item
  if ($album['mainMediaId'] == $mediaId)
  {
    $ok = pnModAPIFunc('mediashare', 'edit', 'setMainItem',
                       array('albumId' => $albumId,
                             'mediaId' => null));
    if ($ok === false)
      return false;
  }

  return true;
}


// =======================================================================
// Move media item
// =======================================================================

function mediashare_editapi_moveMediaItem(&$args)
{
  $mediaId = (int)$args['mediaId'];
  $albumId = (int)$args['albumId'];

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $mediaTable = $pntable['mediashare_media'];
  $mediaColumn = &$pntable['mediashare_media_column'];
  $albumsTable = $pntable['mediashare_albums'];
  $albumsColumn = &$pntable['mediashare_albums_column'];

  $sql = "UPDATE $mediaTable
          SET $mediaColumn[parentAlbumId] = $albumId
          WHERE $mediaColumn[id] = $mediaId";

  $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"Move media item" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  // Check main media item
  $album = pnModAPIFunc('mediashare', 'user', 'getAlbum',
                         array('albumId' => $albumId));
  if ($album === false)
    return false;

  if ($album['mainMediaId'] == $mediaId)
  {
    $sql = "UPDATE $albumsTable
            SET $albumsColumn[mainMediaId] = null
            WHERE $albumsColumn[id] = $albumId";

    $dbconn->execute($sql);

    if ($dbconn->errorNo() != 0)
      return mediashareErrorAPI(__FILE__, __LINE__, '"Move media item" failed: ' . $dbconn->errorMsg() . " while executing: $sql");
  }

  return true;
}


// =======================================================================
// Main media item
// =======================================================================

function mediashare_editapi_setMainItem(&$args)
{
  $albumId = (int)$args['albumId'];
  $mediaId = $args['mediaId'];

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $albumsTable = $pntable['mediashare_albums'];
  $albumsColumn = &$pntable['mediashare_albums_column'];

  $sql = "UPDATE $albumsTable
          SET $albumsColumn[mainMediaId] = " . ($mediaId === null ? 'null' : (int)$mediaId) . "
          WHERE $albumsColumn[id] = $albumId";

  $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"Set main item" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  return true;
}


// =======================================================================
// Arrange items
// =======================================================================

function mediashare_editapi_arrangeAlbum(&$args)
{
  $albumId = (int)$args['albumId'];
  $seq = $args['seq'];

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $mediaTable = $pntable['mediashare_media'];
  $mediaColumn = &$pntable['mediashare_media_column'];
  $albumsTable = $pntable['mediashare_albums'];
  $albumsColumn = &$pntable['mediashare_albums_column'];

  for ($i=0,$cou=count($seq); $i<$cou; ++$i)
  {
    $mediaId = (int)$seq[$i];

    $sql = "UPDATE $mediaTable
            SET $mediaColumn[position] = $i
            WHERE     $mediaColumn[id] = $mediaId
                  AND $mediaColumn[parentAlbumId] = $albumId"; // Include parent as permission check

    $dbconn->execute($sql);

    if ($dbconn->errorNo() != 0)
      return mediashareErrorAPI(__FILE__, __LINE__, '"ArrangeAlbum" failed: ' . $dbconn->errorMsg() . " while executing: $sql");
  }

  return true;
}


// =======================================================================
// User info
// =======================================================================

function mediashare_editapi_getUserInfo(&$args)
{
  $user = (int)pnUserGetVar('uid');

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $mediaTable    = $pntable['mediashare_media'];
  $mediaColumn   = $pntable['mediashare_media_column'];
  $storageTable  = $pntable['mediashare_mediastore'];
  $storageColumn = $pntable['mediashare_mediastore_column'];

  $sql = "SELECT
            SUM($storageColumn[bytes])
          FROM $mediaTable 
          LEFT JOIN $storageTable original
                    ON original.$storageColumn[id] = $mediaColumn[originalId]
          WHERE $mediaColumn[ownerId] = $user";
  
  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"GetUserInfo" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  $limitTotal = (int)pnModGetVar('mediashare', 'mediaSizeLimitTotal');

  $totalCapacityUsed = (int)$result->fields[0];
  $user = array('totalCapacityUsed'    => $totalCapacityUsed,
                'totalCapacityLeft'    => ($totalCapacityUsed > $limitTotal ? 0 : $limitTotal - $totalCapacityUsed),
                'mediaSizeLimitSingle' => (int)pnModGetVar('mediashare', 'mediaSizeLimitSingle'),
                'mediaSizeLimitTotal'  => $limitTotal);

  $result->Close();

  return $user;
}


// =======================================================================
// Keywords update
// =======================================================================

function mediashare_editapi_updateKeywords(&$args)
{
  $itemId   = (int)$args['itemId'];
  $type     = pnVarPrepForStore($args['type']);
  $keywords = mediashareStripKeywords($args['keywords']);

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $keywordsTable  = $pntable['mediashare_keywords'];
  $keywordsColumn = $pntable['mediashare_keywords_column'];

  // First remove existing keywords

  $sql = "DELETE FROM $keywordsTable
          WHERE $keywordsColumn[itemId] = $itemId AND $keywordsColumn[type] = '$type'";

  $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"updateKeywords" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  // Split keywords string into keywords array
  $keywordsArray = preg_split('/[\s,]+/', $keywords);
  
  // Insert new keywords
  
  foreach ($keywordsArray as $keyword)
  {
    if (!empty($keyword))
    {
      $sql = "INSERT INTO $keywordsTable
                ($keywordsColumn[itemId], $keywordsColumn[type], $keywordsColumn[keyword])
              VALUES ($itemId, '$type', '" . pnVarPrepForStore($keyword) . "')";

      $dbconn->execute($sql);
      if ($dbconn->errorNo() != 0)
        return mediashareErrorAPI(__FILE__, __LINE__, '"updateKeywords" failed: ' . $dbconn->errorMsg() . " while executing: $sql");
    }
  }

  return true;
}


// =======================================================================
// Access
// =======================================================================

function mediashare_editapi_getAccessSettings(&$args)
{
  $albumId = (int)$args['albumId'];

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $accessTable  = $pntable['mediashare_access'];
  $accessColumn = $pntable['mediashare_access_column'];
  $membershipTable  = $pntable['group_membership'];
  $membershipColumn = &$pntable['group_membership_column'];
  $groupsTable  = $pntable['groups'];
  $groupsColumn = &$pntable['groups_column'];

  if (strpos($membershipColumn['gid'], 'group_membership') === false)
  {
    $sql = "SELECT mbr.$membershipColumn[gid],
                   grp.$groupsColumn[name],
                   CASE WHEN ISNULL($accessColumn[access]) THEN 0 ELSE $accessColumn[access] END
            FROM $membershipTable mbr
            INNER JOIN $groupsTable grp
                  ON grp.$groupsColumn[gid] = mbr.$membershipColumn[gid]
            LEFT JOIN $accessTable
                 ON     $accessColumn[groupId] = mbr.$membershipColumn[gid]
                    AND $accessColumn[albumId] = $albumId";
  }
  else
  {
    $sql = "SELECT $membershipColumn[gid],
                   $groupsColumn[name],
                   CASE WHEN ISNULL($accessColumn[access]) THEN 0 ELSE $accessColumn[access] END
            FROM $membershipTable
            INNER JOIN $groupsTable
                  ON $groupsColumn[gid] = $membershipColumn[gid]
            LEFT JOIN $accessTable
                 ON     $accessColumn[groupId] = $membershipColumn[gid]
                    AND $accessColumn[albumId] = $albumId";
  }

  $sql .= "
          UNION

          SELECT -1,
                 '" . _MSEVERYBODY ."',
                 CASE WHEN ISNULL($accessColumn[access]) THEN 0 ELSE $accessColumn[access] END
          FROM $accessTable
          WHERE     $accessColumn[groupId] = -1
                AND $accessColumn[albumId] = $albumId";

  $dbresult = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"getAccessSettings" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  $result = array();
  $foundGroups = array();
  for (; !$dbresult->EOF; $dbresult->MoveNext())
  {
    $access = (int)$dbresult->fields[2];

    $result[] = array('groupId'         => (int)$dbresult->fields[0],
                      'groupName'       => $dbresult->fields[1],
                      'access'          => $access,
                      'accessView'      => ($access & mediashareAccessRequirementView) != 0,
                      'accessEditAlbum' => ($access & mediashareAccessRequirementEditAlbum) != 0,
                      'accessEditMedia' => ($access & mediashareAccessRequirementEditMedia) != 0,
                      'accessAddAlbum'  => ($access & mediashareAccessRequirementAddAlbum) != 0,
                      'accessAddMedia'  => ($access & mediashareAccessRequirementAddMedia) != 0);

    $foundGroups[(int)$dbresult->fields[0]] = true;
  }

  if (!array_key_exists(-1, $foundGroups))
  {
    $result[] = array('groupId'         => -1,
                      'groupName'       => _MSEVERYBODY,
                      'access'          => 0,
                      'accessView'      => false,
                      'accessEditAlbum' => false,
                      'accessEditMedia' => false,
                      'accessAddAlbum'  => false,
                      'accessAddMedia'  => false);
  }

  $dbresult->close();

  return $result;
}


function mediashare_editapi_updateAccessSettings(&$args)
{
  $albumId = (int)$args['albumId'];
  $access = $args['access'];

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $accessTable  = $pntable['mediashare_access'];
  $accessColumn = $pntable['mediashare_access_column'];

  // First remove existing access entries
  $sql = "DELETE FROM $accessTable
          WHERE $accessColumn[albumId] = $albumId";

  $dbresult = $dbconn->execute($sql);
  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"updateAccessSettings" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  foreach ($access as $accessRow)
  {
    $a =   ($accessRow['accessView'] ? mediashareAccessRequirementView : 0)
         | ($accessRow['accessEditAlbum'] ? mediashareAccessRequirementEditAlbum : 0)
         | ($accessRow['accessEditMedia'] ? mediashareAccessRequirementEditMedia : 0)
         | ($accessRow['accessAddAlbum'] ? mediashareAccessRequirementAddAlbum : 0)
         | ($accessRow['accessAddMedia'] ? mediashareAccessRequirementAddMedia : 0);

    $groupId = (int)$accessRow['groupId'];

    // Then insert access row
    $sql = "INSERT INTO $accessTable
              ($accessColumn[groupId], $accessColumn[albumId], $accessColumn[access])
            VALUES
              ($groupId, $albumId, $a)";

    $dbresult = $dbconn->execute($sql);
    if ($dbconn->errorNo() != 0)
      return mediashareErrorAPI(__FILE__, __LINE__, '"updateAccessSettings" failed: ' . $dbconn->errorMsg() . " while executing: $sql");
  }

  return true;
}


function mediashare_editapi_getAccessGroups(&$args)
{
  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $groupsTable  = $pntable['groups'];
  $groupsColumn = &$pntable['groups_column'];

  $sql = "SELECT $groupsColumn[gid],
                 $groupsColumn[name]
          FROM $groupsTable";

  $dbresult = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"getAccessSettings" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  $result = array();
  for (; !$dbresult->EOF; $dbresult->MoveNext())
  {
    $result[] = array('groupId'   => (int)$dbresult->fields[0],
                      'groupName' => $dbresult->fields[1]);
  }

  $dbresult->close();

  $result[] = array('groupId'   => -1,
                    'groupName' => _MSEVERYBODY);

  return $result;
}


function mediashare_editapi_setDefaultAccess($args)
{
  $albumId = (int)$args['albumId'];
  $usersMayAddAlbum = (bool)isset($args['usersMayAddAlbum']) ? isset($args['usersMayAddAlbum']) : false;

  $access = 
    array
    (
      array('groupId' => -1,
            'accessView' => true,
            'accessEditAlbum' => false,
            'accessEditMedia' => false,
            'accessAddAlbum' => false,
            'accessAddMedia' => false),
      array('groupId' => 1, // Hopefully  1 = users
            'accessView' => false,
            'accessEditAlbum' => false,
            'accessEditMedia' => false,
            'accessAddAlbum' => $usersMayAddAlbum,
            'accessAddMedia' => false)
    );

  $ok = pnModAPIFunc('mediashare', 'edit', 'updateAccessSettings',
                     array('albumId' => $albumId,
                           'access'  => $access));
  if ($ok === false)
    return false;

  true;
}


// =======================================================================
// External applications
// =======================================================================

function mediashare_editapi_extappGetApps(&$args)
{
  $apps = array();

  // Scan for application APIs
  if ($dh = opendir("modules/mediashare"))
  {
    while (($filename=readdir($dh)) !== false)
    {
      if (preg_match('/^pnextapp_([-a-zA-Z0-9_]+)api.php$/', $filename, $matches))
      {
        $appName = $matches[1];
        $apps[] = $appName;
      }
    }

    closedir($dh);
  }

  return $apps;
}


function mediashare_editapi_extappLocateApp(&$args)
{
  $url = $args['extappURL'];
  if (empty($url))
    return true;

  $args['extappData'] = null;
  $ok = false;
  
  $appNames = mediashare_editapi_extappGetApps($args);
  foreach ($appNames as $appName)
  {
    $data = pnModAPIFunc('mediashare', "extapp_$appName", 'parseURL', array('url' => $url));
    if ($data != null)
    {
      $args['extappData'] = array('appName' => $appName, 'data' => $data);
      $ok = true;
      break;
    }
  }

  $args['extappData'] = serialize($args['extappData']);

  if (!$ok)
    return mediashareErrorAPI(__FILE__, __LINE__, pnMl('_MSUNRECOGNIZEDURL', array('url' => $url)));

  return true;
}


function mediashare_editapi_fetchExternalImages($args)
{
  $album = pnModAPIFunc('mediashare', 'user', 'getAlbumObject', $args);
  if ($album === false)
    return false;
  $albumId = $album->albumId;

  $mediaItems = $album->getMediaItems(); // FIXME: don't get album, get extapp instead
  if ($mediaItems === false)
    return false;

  $existingMediaItems = pnModAPIFunc('mediashare', 'user', 'getMediaItems', array('albumId' => $albumId));
  if ($existingMediaItems === false)
    return false;

  $existingMediaItemsMap = array();
  foreach ($existingMediaItems as $item)
    if ($item['mediaHandler'] == 'extapp')
      $existingMediaItemsMap[$item['originalRef']] = 1;

  $mainMediaItemId = null;
  foreach ($mediaItems as $item)
  {
    if ($item['mediaHandler'] == 'extapp')
    {
      if (!isset($existingMediaItemsMap[$item['originalRef']]))
      {
        $thumbnail = array('fileRef' => $item['thumbnailRef'],
                           'mimeType' => $item['thumbnailMimeType'],
                           'width' => $item['thumbnailWidth'],
                           'height' => $item['thumbnailHeight'],
                           'bytes' => $item['thumbnailBytes']);

        $preview = array('fileRef' => $item['previewRef'],
                         'mimeType' => $item['previewMimeType'],
                         'width' => $item['previewWidth'],
                         'height' => $item['previewHeight'],
                         'bytes' => $item['previewBytes']);

        $original = array('fileRef' => $item['originalRef'],
                          'mimeType' => $item['originalMimeType'],
                          'width' => $item['originalWidth'],
                          'height' => $item['originalHeight'],
                          'bytes' => $item['originalBytes']);

        $newItem = array('albumId' => $albumId,
                         'thumbnail' => $thumbnail,
                         'preview' => $preview,
                         'original' => $original,
                         'title' => $item['title'],
                         'keywords' => $item['keywords'],
                         'description' => $item['description'],
                         'mediaHandler' => $item['mediaHandler']);

        $id = pnModAPIFunc('mediashare', 'edit', 'storeMediaItem', $newItem);
        if ($id === false)
          return false;

        if ($mainMediaItemId === null)
          $mainMediaItemId = $id;
      }

      // Unset to indicate that we found this in extapp items
      unset($existingMediaItemsMap[$item['originalRef']]);
    }
  }

  foreach ($existingMediaItems as $item)
  {
    if ($item['mediaHandler'] == 'extapp')
    {
      if (isset($existingMediaItemsMap[$item['originalRef']]))
      {
        // Was not found in extapp items - remove it
        $ok = pnModAPIFunc('mediashare', 'edit', 'deleteMediaItem',
                           array('mediaId' => $item['id']));
        if ($ok === false)
          return false;
      }
    }
  }

  // Set main item
  
  // Fetch again to see what is available
  $existingMediaItems = pnModAPIFunc('mediashare', 'user', 'getMediaItems', array('albumId' => $albumId));
  if ($existingMediaItems === false)
    return false;

  if (count($existingMediaItems) > 0)
  {
    $ok = pnModAPIFunc('mediashare', 'edit', 'ensureMainAlbumId',
                       array('albumId' => $albumId,
                             'mediaId' => $existingMediaItems[0]['id'],
                             'forceUpdate' => true));

    if ($ok === false)
      return false;
  }

  return true;
}

