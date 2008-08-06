<?php
// $Id: pnuserapi.php,v 1.77 2008/06/22 14:10:29 jornlind Exp $
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2005.
// ----------------------------------------------------------------------
// For POST-NUKE Content Management System
// Copyright (C) 2002 by the PostNuke Development Team.
// http://www.postnuke.com/
// ----------------------------------------------------------------------
// LICENSE
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License (GPL)
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WIthOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// To read the license please visit http://www.gnu.org/copyleft/gpl.html
// =======================================================================

require_once("modules/mediashare/common.php");


// =======================================================================
// Album class definition
// =======================================================================

class MediashareBaseAlbum
{
  var $albumId;
  var $albumData;


  function getAlbumData()
  { 
    return $this->albumData;
  }


  function fixMainMedia($images)
  {
    $mainMediaId = $this->albumData['mainMediaId'];
    $mainMedia = null;
    for ($i=0,$cou=count($images); $i<$cou; ++$i)
    {
      $image = & $images[$i];
      if ($image['id'] == $mainMediaId)
        $mainMedia = $image;
    }
    $this->albumData['mainMediaItem'] = $mainMedia;
  }
}


class MediashareAlbum extends MediashareBaseAlbum
{
  function MediashareAlbum($albumId, $albumData)
  {
    $this->albumId = $albumId;
    $this->albumData = $albumData;
  }


  function parseURL($url)
  {
    return false;
  }


  function getMediaItems()
  {
    return mediashareGetMediaItemsData(array('albumId' => $this->albumId));
  }
}


function & mediashareGetAlbumInstance($albumId, $albumData)
{
  static $albumInstances = array();

  if (!isset($albumInstances[$albumId]))
  {
    if (empty($albumData['extappData']))
    {
      $albumInstances[$albumId] = & new MediashareAlbum($albumId, $albumData);
    }
    else
    {
      $data = $albumData['extappData'];
      $albumInstances[$albumId] = pnModAPIFunc('mediashare', "extapp_$data[appName]", 'getAlbumInstance',
                                               array('albumId' => $albumId, 'albumData' => $albumData));
    }
  }
  
  return $albumInstances[$albumId];
}


// =======================================================================
// Access
// =======================================================================

function mediashare_userapi_hasAlbumAccess($args)
{
  $albumId  = (int)$args['albumId'];
  $access   = (int)$args['access'];
  $viewKey  = $args['viewKey'];

  $accessApi = mediashareGetAccessAPI();

  return $accessApi->hasAlbumAccess($albumId, $access, $viewKey);
}


function mediashare_userapi_getAlbumAccess($args)
{
  $albumId  = (int)$args['albumId'];

  $accessApi = mediashareGetAccessAPI();

  return $accessApi->getAlbumAccess($albumId);
}


function mediashare_userapi_getAccessibleAlbumsSql($args)
{
  $albumId = isset($args['albumId']) ? (int)$args['albumId'] : null;
  $access  = (int)$args['access'];
  $field   = $args['field'];

  $accessApi = mediashareGetAccessAPI();

  return $accessApi->getAccessibleAlbumsSql($albumId, $access, $field);
}


function mediashare_userapi_hasItemAccess($args)
{
  $mediaId = $args['mediaId'];
  $access   = (int)$args['access'];
  $viewKey  = $args['viewKey'];

  $accessApi = mediashareGetAccessAPI();

  return $accessApi->hasItemAccess($mediaId, $access, $viewKey);
}


// =======================================================================
// Albums
// =======================================================================

function mediashare_userapi_getAlbum($args)
{
  $album = mediashare_userapi_getAlbumObject($args);
  if ($album === false)
    return $album;
  return $album->getAlbumData();
}


function & mediashare_userapi_getAlbumObject($args)
{
  $albumData = mediashare_userapi_getAlbumData($args);
  if ($albumData == false)
    return false;
  return mediashareGetAlbumInstance($albumData['id'], $albumData);
}


function mediashare_userapi_getAlbumData($args)
{
    // Argument check
  if (!isset($args['albumId']))
    return mediashareErrorAPI(__FILE__, __LINE__, 'Missing albumId in mediashare_userapi_getAlbum');

  $enableEscape = (isset($args['enableEscape']) ? $args['enableEscape'] : true);

    // Check access
  if (!pnSecAuthAction(0, 'mediashare::', '::', ACCESS_READ))
    return mediashareErrorAPI(__FILE__, __LINE__, _MSNOAUTH);

  $albumId        = (int)$args['albumId'];
  $countSubAlbums = array_key_exists('countSubAlbums', $args) ? $args['countSubAlbums'] : false;
  $ownerId        = (int)pnUserGetVar('uid');

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $albumsTable   = $pntable['mediashare_albums'];
  $albumsColumn  = $pntable['mediashare_albums_column'];

  $sql = "SELECT   $albumsColumn[id],
                   $albumsColumn[ownerId],
                   UNIX_TIMESTAMP($albumsColumn[createdDate]),
                   UNIX_TIMESTAMP($albumsColumn[modifiedDate]),
                   $albumsColumn[title],
                   $albumsColumn[summary],
                   $albumsColumn[description],
                   $albumsColumn[keywords],
                   $albumsColumn[template],
                   $albumsColumn[parentAlbumId],
                   $albumsColumn[viewKey],
                   $albumsColumn[mainMediaId],
                   $albumsColumn[thumbnailSize],
                   $albumsColumn[nestedSetLeft],
                   $albumsColumn[nestedSetRight],
                   $albumsColumn[nestedSetLevel],
                   $albumsColumn[extappURL],
                   $albumsColumn[extappData]
          FROM $albumsTable 
          WHERE    $albumsColumn[id] = $albumId";

  //echo "<pre>$sql</pre>\n"; exit(0);
  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"getAlbum" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  if ($result->EOF)
    return  mediashareErrorAPI(__FILE__, __LINE__, 'Unknown album (' . $albumId . ')');

  $album = array( 'id'              => $result->fields[0],
                  'ownerId'         => $result->fields[1],
                  'createdDate'     => strftime(_MSDATEFORMAT, $result->fields[2]),
                  'modifiedDate'    => strftime(_MSDATEFORMAT, $result->fields[3]),
                  'createdDateRaw'  => $result->fields[2],
                  'modifiedDateRaw' => $result->fields[3],
                  'title'         => $result->fields[4],
                  'summary'       => $result->fields[5],
                  'description'   => $result->fields[6],
                  'keywords'      => $result->fields[7],
                  'template'      => $result->fields[8],
                  'parentAlbumId' => $result->fields[9],
                  'viewKey'       => $result->fields[10],
                  'mainMediaId'   => ($result->fields[11] == null ? 0 : $result->fields[11]),
                  'thumbnailSize'  => $result->fields[12],
                  'nestedSetLeft'  => (int)$result->fields[13],
                  'nestedSetRight' => (int)$result->fields[14],
                  'nestedSetLevel' => (int)$result->fields[15],
                  'extappURL'      => $result->fields[16],
                  'extappData'     => unserialize($result->fields[17]),
                  'imageCount'     => 0 /* FIXME */);

  $result->Close();

  if ($album['mainMediaId'] > 0)
  {
    $mainMediaItem = pnModAPIFunc('mediashare', 'user', 'getMediaItem',
                                  array('mediaId' => $album['mainMediaId']));

    $album['mainMediaItem'] = $mainMediaItem;
  }
  else
    $album['mainMediaItem'] = null;

  mediashareAddKeywords($album);
  $album['allowMediaEdit'] = true;

  if ($enableEscape)
    mediashareEscapeAlbum($album, $albumId);

  return $album;
}


function mediashare_userapi_getAllAlbums($args)
{
  $args['recursively'] = true;
  return mediashare_userapi_getSubAlbums($args);
}


function mediashare_userapi_getSubAlbums($args)
{
  $albums = mediashare_userapi_getSubAlbumsData($args);
  for ($i=0,$cou=count($albums); $i<$cou; ++$i)
  {
    $album = mediashareGetAlbumInstance($albums[$i]['id'], $albums[$i]);
    $albums[$i] = $album->getAlbumData();
  }
  return $albums;
}


function mediashare_userapi_getSubAlbumsData($args)
{
    // Argument check
  if (!isset($args['albumId']))
    return mediashareErrorAPI(__FILE__, __LINE__, 'Missing albumId in mediashare_userapi_getSubAlbums');

    // Check access
  if (!pnSecAuthAction(0, 'mediashare::', '::', ACCESS_READ))
    return mediashareErrorAPI(__FILE__, __LINE__, _MSNOAUTH);

  $albumId = (int)$args['albumId'];
  $ownerId = (int)pnUserGetVar('uid');
  $recursively = (array_key_exists('recursively',$args) ? (bool)$args['recursively'] : false);
  $access = (array_key_exists('access',$args) ? (int)$args['access'] : 0xFF);
  $excludeAlbumId = (array_key_exists('excludeAlbumId',$args) ? (int)$args['excludeAlbumId'] : null);
  $onlyMine = (array_key_exists('onlyMine',$args) ? $args['onlyMine'] : false);

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $albumsTable   = $pntable['mediashare_albums'];
  $albumsColumn  = $pntable['mediashare_albums_column'];

  $accessibleAlbumSql = pnModAPIFunc('mediashare', 'user', 'getAccessibleAlbumsSql',
                                     array('albumId' => ($recursively ? null : $albumId),
                                           'access'  => $access,
                                           'field'   => $albumsColumn['id']));
  if ($accessibleAlbumSql === false)
    return false;

  if ($excludeAlbumId != null)
  {
    $excludeAlbum = pnModAPIFunc('mediashare', 'user', 'getAlbum',
                           array('albumId' => $excludeAlbumId));
    if ($excludeAlbum === false)
      return false;

    $excludeRestriction = "(    album.$albumsColumn[nestedSetLeft] < $excludeAlbum[nestedSetLeft]
                            OR album.$albumsColumn[nestedSetRight] > $excludeAlbum[nestedSetRight]) AND ";
  }
  else
  {
    $excludeRestriction = '';
  }

  $mineSql = '';
  if ($onlyMine)
    $mineSql = " album.$albumsColumn[ownerId] = $ownerId AND ";

  $sql = "SELECT   album.$albumsColumn[id],
                   album.$albumsColumn[ownerId],
                   UNIX_TIMESTAMP(album.$albumsColumn[createdDate]),
                   UNIX_TIMESTAMP(album.$albumsColumn[modifiedDate]),
                   album.$albumsColumn[title],
                   album.$albumsColumn[summary],
                   album.$albumsColumn[description],
                   album.$albumsColumn[keywords],
                   album.$albumsColumn[template],
                   album.$albumsColumn[parentAlbumId],
                   album.$albumsColumn[viewKey],
                   album.$albumsColumn[mainMediaId],
                   album.$albumsColumn[thumbnailSize],
                   album.$albumsColumn[nestedSetLeft],
                   album.$albumsColumn[nestedSetRight],
                   album.$albumsColumn[nestedSetLevel],
                   album.$albumsColumn[extappURL],
                   album.$albumsColumn[extappData]
          FROM $albumsTable album
          WHERE ($accessibleAlbumSql) AND $excludeRestriction $mineSql";

  if ($recursively)
  {
    $sql .= "1=1 ORDER BY album.$albumsColumn[nestedSetLeft], album.$albumsColumn[title]";
  }
  else
  {
    $sql .= "album.$albumsColumn[parentAlbumId] = $albumId
             ORDER BY album.$albumsColumn[title]";
  }

  //echo "<pre>$sql</pre>\n";
  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"getSubAlbums" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  $albums = array();
  for (; !$result->EOF; $result->MoveNext())
  {
    $album = array( 'id'            => $result->fields[0],
                    'ownerId'       => $result->fields[1],
                    'createdDate'   => strftime(_MSDATEFORMAT, $result->fields[2]),
                    'modifiedDate'  => strftime(_MSDATEFORMAT, $result->fields[3]),
                    'title'         => $result->fields[4],
                    'summary'       => $result->fields[5],
                    'description'   => $result->fields[6],
                    'keywords'      => $result->fields[7],
                    'template'      => $result->fields[8],
                    'parentAlbumId' => $result->fields[9],
                    'viewKey'       => $result->fields[10],
                    'mainMediaId'   => ($result->fields[11] == null ? -1 : $result->fields[11]),
                    'thumbnailSize'  => $result->fields[12],
                    'nestedSetLeft'  => (int)$result->fields[13],
                    'nestedSetRight' => (int)$result->fields[14],
                    'nestedSetLevel' => (int)$result->fields[15],
                    'extappURL'      => $result->fields[16],
                    'extappData'     => unserialize($result->fields[17]));

    if ($album['mainMediaId'] > 0) // FIXME: always fetch all main items?
    {
      $mainMediaItem = pnModAPIFunc('mediashare', 'user', 'getMediaItem',
                                    array('mediaId' => $album['mainMediaId']));

      $album['mainMediaItem'] = $mainMediaItem;
    }
    else
      $album['mainMediaItem'] = null;

    mediashareAddKeywords($album);

    mediashareEscapeAlbum($album, $albumId);

    $albums[] = $album;
  }

  $result->Close();

  return $albums;
}


function mediashare_userapi_getAlbumBreadcrumb($args)
{
    // Argument check
  if (!isset($args['albumId']))
    return mediashareErrorAPI(__FILE__, __LINE__, 'Missing albumId in mediashare_userapi_getAlbumBreadcrumb');

  $albumId = (int)$args['albumId'];

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $albumsTable  = $pntable['mediashare_albums'];
  $albumsColumn = $pntable['mediashare_albums_column'];

  $sql = "SELECT parentAlbum.$albumsColumn[id],
                 parentAlbum.$albumsColumn[title]
          FROM $albumsTable parentAlbum
          LEFT OUTER JOIN $albumsTable album
               ON     album.$albumsColumn[nestedSetLeft] >= parentAlbum.$albumsColumn[nestedSetLeft]
                  AND album.$albumsColumn[nestedSetRight] <= parentAlbum.$albumsColumn[nestedSetRight]
          WHERE album.$albumsColumn[id] = $albumId
          ORDER BY parentAlbum.$albumsColumn[nestedSetLeft]";

  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"getAlbumBreadcrumb" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  $breadcrumb = array();
  for (; !$result->EOF; $result->MoveNext())
  {
    $breadcrumb[] = array('id'    => $result->fields[0],
                          'title' => pnVarPrepForDisplay($result->fields[1]));
  }

  $result->Close();

  return $breadcrumb;
}


function mediashare_userapi_getAlbumList($args)
{
  $recordPos  = isset($args['recordPos']) ? (int)$args['recordPos'] : 0;
  $pageSize   = isset($args['pageSize']) ? (int)$args['pageSize'] : 5;
  $access     = isset($args['access']) ? $args['access'] : mediashareAccessRequirementView;

    // Check access
  if (!pnSecAuthAction(0, 'mediashare::', '::', ACCESS_READ))
    return mediashareErrorAPI(__FILE__, __LINE__, _MSNOAUTH);

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $albumsTable   = $pntable['mediashare_albums'];
  $albumsColumn  = $pntable['mediashare_albums_column'];

  $accessibleAlbumSql = pnModAPIFunc('mediashare', 'user', 'getAccessibleAlbumsSql',
                                     array('access'  => $access,
                                           'field'   => $albumsColumn['id']));
  if ($accessibleAlbumSql === false)
    return false;

  $sql = "SELECT   album.$albumsColumn[id],
                   album.$albumsColumn[ownerId],
                   UNIX_TIMESTAMP(album.$albumsColumn[createdDate]),
                   UNIX_TIMESTAMP(album.$albumsColumn[modifiedDate]),
                   album.$albumsColumn[title],
                   album.$albumsColumn[summary],
                   album.$albumsColumn[description],
                   album.$albumsColumn[keywords],
                   album.$albumsColumn[template],
                   album.$albumsColumn[parentAlbumId],
                   album.$albumsColumn[viewKey],
                   album.$albumsColumn[mainMediaId],
                   album.$albumsColumn[thumbnailSize],
                   album.$albumsColumn[nestedSetLeft],
                   album.$albumsColumn[nestedSetRight],
                   album.$albumsColumn[nestedSetLevel],
                   album.$albumsColumn[extappURL],
                   album.$albumsColumn[extappData]
          FROM $albumsTable album
          WHERE ($accessibleAlbumSql)
          ORDER BY album.$albumsColumn[createdDate] DESC";

  //echo "<pre>$sql</pre>\n";
  $result = $dbconn->selectLimit($sql, $pageSize, $recordPos);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"getAlbumList" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  $albums = array();
  for (; !$result->EOF; $result->MoveNext())
  {
    $album = array( 'id'            => $result->fields[0],
                    'ownerId'       => $result->fields[1],
                    'createdDate'   => strftime(_MSDATEFORMAT, $result->fields[2]),
                    'modifiedDate'  => strftime(_MSDATEFORMAT, $result->fields[3]),
                    'title'         => $result->fields[4],
                    'summary'       => $result->fields[5],
                    'description'   => $result->fields[6],
                    'keywords'      => $result->fields[7],
                    'template'      => $result->fields[8],
                    'parentAlbumId' => $result->fields[9],
                    'viewKey'       => $result->fields[10],
                    'mainMediaId'   => ($result->fields[11] == null ? -1 : $result->fields[11]),
                    'thumbnailSize'  => (int)$result->fields[12],
                    'nestedSetLeft'  => (int)$result->fields[13],
                    'nestedSetRight' => (int)$result->fields[14],
                    'nestedSetLevel' => (int)$result->fields[15],
                    'extappURL'      => $result->fields[16],
                    'extappData'     => unserialize($result->fields[17]));

    if ($album['mainMediaId'] > 0)
    {
      $mainMediaItem = pnModAPIFunc('mediashare', 'user', 'getMediaItem',
                                    array('mediaId' => $album['mainMediaId']));

      $album['mainMediaItem'] = $mainMediaItem;
    }
    else
      $album['mainMediaItem'] = null;

    mediashareAddKeywords($album);

    mediashareEscapeAlbum($album, $album['id']);

    $albums[] = $album;
  }

  $result->Close();

  return $albums;
}


function mediashare_userapi_getFirstItemIdInAlbum($args)
{
    // Argument check
  if (!isset($args['albumId']))
    return mediashareErrorAPI(__FILE__, __LINE__, 'Missing albumId in mediashare_userapi_getFirstItemInAlbum');

    // Check access
  if (!pnSecAuthAction(0, 'mediashare::', '::', ACCESS_READ))
    return mediashareErrorAPI(__FILE__, __LINE__, _MSNOAUTH);

  $albumId = (int)$args['albumId'];

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $albumsTable   = $pntable['mediashare_albums'];
  $albumsColumn  = $pntable['mediashare_albums_column'];
  $mediaTable    = $pntable['mediashare_media'];
  $mediaColumn   = $pntable['mediashare_media_column'];

  $sql = "SELECT $mediaColumn[id]
          FROM $mediaTable
          WHERE $mediaColumn[parentAlbumId] = $albumId
          ORDER BY $mediaColumn[createdDate] DESC";

  $result = $dbconn->selectLimit($sql, 1, 0);
  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"getFirstItemInAlbum" failed: ' . $dbconn->errorMsg() . " while executing: $sql");
  if ($result->EOF)
    return true;

  $id = $result->fields[0];

  $result->close();

  return $id;
}


// =======================================================================
// Media items
// =======================================================================

function mediashare_userapi_getMediaItem($args)
{
    // Argument check
  if (!isset($args['mediaId']))
    return mediashareErrorAPI(__FILE__, __LINE__, 'Missing mediaId in mediashare_userapi_getMediaItem');

  $enableEscape = (isset($args['enableEscape']) ? $args['enableEscape'] : true);

    // Check access
  if (!pnSecAuthAction(0, 'mediashare::', '::', ACCESS_READ))
    return mediashareErrorAPI(__FILE__, __LINE__, _MSNOAUTH);

  $mediaId = (int)$args['mediaId'];
  $ownerId = (int)pnUserGetVar('uid');

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $mediaTable    = $pntable['mediashare_media'];
  $mediaColumn   = $pntable['mediashare_media_column'];
  $storageTable  = $pntable['mediashare_mediastore'];
  $storageColumn = $pntable['mediashare_mediastore_column'];

  $sql = "SELECT   $mediaColumn[id],
                   $mediaColumn[ownerId],
                   UNIX_TIMESTAMP($mediaColumn[createdDate]),
                   UNIX_TIMESTAMP($mediaColumn[modifiedDate]),
                   $mediaColumn[title],
                   $mediaColumn[keywords],
                   $mediaColumn[description],
                   $mediaColumn[parentAlbumId],
                   $mediaColumn[position],
                   $mediaColumn[mediaHandler],
                   $mediaColumn[thumbnailId],
                   $mediaColumn[previewId],
                   $mediaColumn[originalId],
                   thumbnail.$storageColumn[fileRef],
                   thumbnail.$storageColumn[mimeType],
                   thumbnail.$storageColumn[width],
                   thumbnail.$storageColumn[height],
                   thumbnail.$storageColumn[bytes],
                   preview.$storageColumn[fileRef],
                   preview.$storageColumn[mimeType],
                   preview.$storageColumn[width],
                   preview.$storageColumn[height],
                   preview.$storageColumn[bytes],
                   original.$storageColumn[fileRef],
                   original.$storageColumn[mimeType],
                   original.$storageColumn[width],
                   original.$storageColumn[height],
                   original.$storageColumn[bytes]
          FROM $mediaTable 
          LEFT JOIN $storageTable thumbnail
                    ON thumbnail.$storageColumn[id] = $mediaColumn[thumbnailId]
          LEFT JOIN $storageTable preview
                    ON preview.$storageColumn[id] = $mediaColumn[previewId]
          LEFT JOIN $storageTable original
                    ON original.$storageColumn[id] = $mediaColumn[originalId]
          WHERE    $mediaColumn[id] = $mediaId";

  //echo "<pre>$sql</pre>\n"; exit(0);
  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"getMediaItem" failed: ' . $dbconn->errorMsg() . " while executing: $sql");
  if ($result->EOF)
    return null;

  $item = array( 'id'            => $result->fields[0],
                 'ownerId'       => $result->fields[1],
                 'createdDate'   => strftime(_MSDATEFORMAT, $result->fields[2]),
                 'modifiedDate'  => strftime(_MSDATEFORMAT, $result->fields[3]),
                 'title'         => $result->fields[4],
                 'keywords'      => $result->fields[5],
                 'description'   => $result->fields[6],
                 'caption'       => empty($result->fields[4]) ? $result->fields[6] : $result->fields[4],
                 'captionLong'   => empty($result->fields[6]) ? $result->fields[4] : $result->fields[6],
                 'parentAlbumId' => $result->fields[7],
                 'position'      => $result->fields[8],
                 'mediaHandler'  => $result->fields[9],
                 'thumbnailId'   => $result->fields[10],
                 'previewId'     => $result->fields[11],
                 'originalId'    => $result->fields[12],
                 'thumbnailRef'  => $result->fields[13],
                 'thumbnailMimeType' => $result->fields[14],
                 'thumbnailWidth'    => $result->fields[15],
                 'thumbnailHeight'   => $result->fields[16],
                 'thumbnailBytes'    => $result->fields[17],
                 'previewRef'        => $result->fields[18],
                 'previewMimeType'   => $result->fields[19],
                 'previewWidth'      => $result->fields[20],
                 'previewHeight'     => $result->fields[21],
                 'previewBytes'      => $result->fields[22],
                 'originalRef'       => $result->fields[23],
                 'originalMimeType'  => $result->fields[24],
                 'originalWidth'     => $result->fields[25],
                 'originalHeight'    => $result->fields[26],
                 'originalBytes'     => $result->fields[27],
                 'originalIsImage'   => substr($result->fields[24],0,6) == 'image/');

  if ($enableEscape)
    mediashareEscapeItem($item, $item['id']);
  mediashareAddKeywords($item);

  $result->Close();

  return $item;
}


function mediashare_userapi_getMediaUrl(&$args)
{
  $mediaItem = null;
  $src = $args['src'];

  if (isset($args['mediaId']))
    $mediaItem = pnModAPIFunc('mediashare', 'user', 'getMediaItem', 
                              array('mediaId' => $args['mediaId']));
  else if (isset($args['mediaItem']))
    $mediaItem = $args['mediaItem'];
  else
    return mediashareErrorAPI(__FILE__, __LINE__, 'Missing either mediaId or mediaItem in getMediaUrl()');

  $url = $mediaItem[$src];

  // Check for absolute URLs returned by external apps.
  if (substr($url,0,4) == 'http')
    return $url;

  return "mediashare/$mediaItem[$src]";
}


function mediashare_userapi_getMediaItems($args)
{
    // Argument check
  if (!isset($args['albumId'])  &&  !isset($args['mediaIdList']))
    return mediashareErrorAPI(__FILE__, __LINE__, 'Missing albumId or mediaIdList in mediashare_userapi_getMediaItems');

    // Check access
  if (!pnSecAuthAction(0, 'mediashare::', '::', ACCESS_READ))
    return mediashareErrorAPI(__FILE__, __LINE__, _MSNOAUTH);

  if (isset($args['albumId']))
  {
    $album = mediashare_userapi_getAlbumObject($args);
    return $album->getMediaItems();
  }
  else
    return mediashareGetMediaItemsData($args);
}


function mediashareGetMediaItemsData($args)
{
  $albumId = (isset($args['albumId']) ? (int)$args['albumId'] : null);
  $mediaIdList = (isset($args['mediaIdList']) ? $args['mediaIdList'] : null);
  $ownerId = (int)pnUserGetVar('uid');
  $enableEscape = (isset($args['enableEscape']) ? $args['enableEscape'] : true);
  $access =  (isset($args['access']) ? $args['access'] : mediashareAccessRequirementView);

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  pnModDBInfoLoad('User'); // Ensure DB table info is available

  $mediaTable    = $pntable['mediashare_media'];
  $mediaColumn   = $pntable['mediashare_media_column'];
  $storageTable  = $pntable['mediashare_mediastore'];
  $storageColumn = $pntable['mediashare_mediastore_column'];
  $usersTable     = $pntable['users'];
  $usersColumn    = $pntable['users_column'];

  if (array_key_exists('mediaId', $args))
    $mediaItemRestriction = "$mediaColumn[id] = " . (int)$mediaColumn['id'];
  else
    $mediaItemRestriction = '';

  if ($albumId != null)
  {
    $albumRestriction = "$mediaColumn[parentAlbumId] = $albumId";
  }
  else
  {
    for ($i=0, $cou=count($mediaIdList); $i<$cou; ++$i)
      $mediaIdList[$i] = (int)$mediaIdList[$i];
    if ($cou > 0)
      $albumRestriction = "$mediaColumn[id] IN (" . implode(',', $mediaIdList) . ')';
    else
      $albumRestriction = '1=0';

    $accessibleAlbumSql = pnModAPIFunc('mediashare', 'user', 'getAccessibleAlbumsSql',
                                       array('access'  => $access,
                                             'field'   => "$mediaColumn[parentAlbumId]"));
    if ($accessibleAlbumSql === false)
      return mediashareErrorAPIGet();

    $albumRestriction .= ' AND ' . $accessibleAlbumSql;
  }


  $sql = "SELECT   $mediaColumn[id],
                   $mediaColumn[ownerId],
                   UNIX_TIMESTAMP($mediaColumn[createdDate]),
                   UNIX_TIMESTAMP($mediaColumn[modifiedDate]),
                   $mediaColumn[title],
                   $mediaColumn[keywords],
                   $mediaColumn[description],
                   $mediaColumn[parentAlbumId],
                   $mediaColumn[mediaHandler],
                   $mediaColumn[thumbnailId],
                   $mediaColumn[previewId],
                   $mediaColumn[originalId],
                   thumbnail.$storageColumn[fileRef],
                   thumbnail.$storageColumn[mimeType],
                   thumbnail.$storageColumn[width],
                   thumbnail.$storageColumn[height],
                   thumbnail.$storageColumn[bytes],
                   preview.$storageColumn[fileRef],
                   preview.$storageColumn[mimeType],
                   preview.$storageColumn[width],
                   preview.$storageColumn[height],
                   preview.$storageColumn[bytes],
                   original.$storageColumn[fileRef],
                   original.$storageColumn[mimeType],
                   original.$storageColumn[width],
                   original.$storageColumn[height],
                   original.$storageColumn[bytes],
                   $usersColumn[uname]
          FROM $mediaTable 
          LEFT JOIN $storageTable thumbnail
                    ON thumbnail.$storageColumn[id] = $mediaColumn[thumbnailId]
          LEFT JOIN $storageTable preview
                    ON preview.$storageColumn[id] = $mediaColumn[previewId]
          LEFT JOIN $storageTable original
                    ON original.$storageColumn[id] = $mediaColumn[originalId]
          INNER JOIN $usersTable 
                ON $usersColumn[uid] = $mediaColumn[ownerId]
          WHERE    $albumRestriction
          ORDER BY $mediaColumn[position]";

  if ($mediaItemRestriction != null)
    $sql .= " AND $mediaItemRestriction";

  //echo "<pre>$sql</pre>\n"; exit(0);
  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"getMediaItems" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  $items = array();
  for (; !$result->EOF; $result->MoveNext())
  {
    $item = array( 'id'              => $result->fields[0],
                   'ownerId'         => $result->fields[1],
                   'createdDate'     => strftime(_MSDATEFORMAT, $result->fields[2]),
                   'modifiedDate'    => strftime(_MSDATEFORMAT, $result->fields[3]),
                   'createdDateRaw'  => $result->fields[2],
                   'modifiedDateRaw' => $result->fields[3],
                   'title'           => $result->fields[4],
                   'keywords'      => $result->fields[5],
                   'description'   => $result->fields[6],
                   'caption'       => empty($result->fields[4]) ? $result->fields[6] : $result->fields[4],
                   'captionLong'   => empty($result->fields[6]) ? $result->fields[4] : $result->fields[6],
                   'parentAlbumId' => $result->fields[7],
                   'mediaHandler'  => $result->fields[8],
                   'thumbnailId'   => $result->fields[9],
                   'previewId'     => $result->fields[10],
                   'originalId'    => $result->fields[11],
                   'thumbnailRef'      => $result->fields[12],
                   'thumbnailMimeType' => $result->fields[13],
                   'thumbnailWidth'    => $result->fields[14],
                   'thumbnailHeight'   => $result->fields[15],
                   'thumbnailBytes'    => $result->fields[16],
                   'previewRef'        => $result->fields[17],
                   'previewMimeType'   => $result->fields[18],
                   'previewWidth'      => $result->fields[19],
                   'previewHeight'     => $result->fields[20],
                   'previewBytes'      => $result->fields[21],
                   'originalRef'       => $result->fields[22],
                   'originalMimeType'  => $result->fields[23],
                   'originalWidth'     => $result->fields[24],
                   'originalHeight'    => $result->fields[25],
                   'originalBytes'     => $result->fields[26],
                   'originalIsImage'   => substr($result->fields[23],0,6) == 'image/',
                   'ownerName'         => $result->fields[27]);

    mediashareAddKeywords($item);

    if ($enableEscape)
      mediashareEscapeItem($item, $item['id']);

    $items[] = $item;
  }

  $result->Close();

  return $items;
}


// =======================================================================
// Latest, random and more
// =======================================================================

function mediashare_userapi_getLatestMediaItems($args)
{
  return pnModAPIFunc('mediashare', 'user', 'getList',
                      array('order' => 'created',
                            'orderDir' => 'desc'));
}


function mediashare_userapi_getLatestAlbums($args)
{
  return pnModAPIFunc('mediashare', 'user', 'getAlbumList');
}


function mediashare_userapi_getRandomMediaItem($args)
{
  $mode = (isset($args['mode']) ? $args['mode'] : 'all');
  $albumId = (isset($args['albumId']) ? (int)$args['albumId'] : null);
  $latest  = (isset($args['latest']) ? $args['latest'] : false);

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $albumsTable   = $pntable['mediashare_albums'];
  $albumsColumn  = $pntable['mediashare_albums_column'];
  $mediaTable    = $pntable['mediashare_media'];
  $mediaColumn   = $pntable['mediashare_media_column'];

  $accessibleAlbumSql = pnModAPIFunc('mediashare', 'user', 'getAccessibleAlbumsSql',
                                     array('access'  => mediashareAccessRequirementView,
                                           'field'   => "album.$albumsColumn[id]"));
  if ($accessibleAlbumSql === false)
    return false;

  if ($mode == 'latest')
  {
    $sql = "SELECT $albumsColumn[id] 
            FROM $albumsTable album
            WHERE $accessibleAlbumSql
            ORDER BY $albumsColumn[createdDate] DESC";
    
    $dbresult = $dbconn->selectLimit($sql, 1, 0);
    if ($dbconn->errorNo() != 0)
      return mediashareErrorAPI(__FILE__, __LINE__, '"getRandomMediaItem" failed: ' . $dbconn->errorMsg() . " while executing: $sql");
    
    $albumId = (int)$dbresult->fields[0];
    $dbresult->Close();

    $accessibleAlbumSql = "album.$albumsColumn[id] = $albumId";
  }

  $restriction = $accessibleAlbumSql;
  
  if ($mode == 'album' && $albumId != null)
  {
    $restriction .= " AND album.$albumsColumn[id] = $albumId";
  }
  
  $sql = "SELECT COUNT(*) FROM $mediaTable media
          JOIN $albumsTable album
               ON album.$albumsColumn[id] = media.$mediaColumn[parentAlbumId]
          WHERE $restriction";

  $dbresult = $dbconn->execute($sql);
  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"getRandomMediaItem" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  $count = (int)$dbresult->fields[0];
  $dbresult->Close();

  $sql = "SELECT media.$mediaColumn[id], media.$mediaColumn[parentAlbumId] FROM $mediaTable media
          JOIN $albumsTable album
               ON album.$albumsColumn[id] = media.$mediaColumn[parentAlbumId]
          WHERE $restriction";
  $dbresult = $dbconn->selectLimit($sql, 1, rand(0,$count-1));
  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"getRandomMediaItem" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  $result = array('mediaId' => (int)$dbresult->fields[0], 'albumId' => (int)$dbresult->fields[1]);

  $dbresult->Close();

  return $result;
}


// =======================================================================
// Escaping
// =======================================================================

function mediashareEscapeAlbum(&$album, $albumId)
{
  $album['title'] = pnVarPrepForDisplay($album['title']);
  list($album['summary'],
       $album['description']) = pnModCallHooks('item', 'transform', "album-$albumId", 
                                               array(pnVarPrepHTMLDisplay(isset($album['summary']) ? $album['summary'] : ''),
                                                     pnVarPrepHTMLDisplay(isset($album['description']) ? $album['description'] : '')));
}


function mediashareEscapeItem(&$item, $itemId)
{
  $item['title'] = pnVarPrepForDisplay($item['title']);
  $item['caption'] = pnVarPrepForDisplay($item['caption']);
  $item['captionLong'] = pnVarPrepForDisplay($item['captionLong']);
  list($item['description']) = pnModCallHooks('item', 'transform', "item-$itemId", 
                                               array(pnVarPrepHTMLDisplay($item['description'])));
}


// =======================================================================
// Settings
// =======================================================================

function mediashare_userapi_getSettings($args)
{
  return array('tmpDirName' => pnModGetVar('mediashare', 'tmpDirName'),
               'mediaDirName' => pnModGetVar('mediashare', 'mediaDirName'),
               'thumbnailSize' => pnModGetVar('mediashare', 'thumbnailSize'),
               'previewSize' => pnModGetVar('mediashare', 'previewSize'),
               'mediaSizeLimitSingle' => (int)pnModGetVar('mediashare', 'mediaSizeLimitSingle')/1000,
               'mediaSizeLimitTotal' => (int)pnModGetVar('mediashare', 'mediaSizeLimitTotal')/1000,
               'allowTemplateOverride' => pnModGetVar('mediashare', 'allowTemplateOverride'),
               'defaultAlbumTemplate' => pnModGetVar('mediashare', 'defaultAlbumTemplate'),
               'enableSharpen' => pnModGetVar('mediashare', 'enableSharpen'),
               'enableThumbnailStart' => pnModGetVar('mediashare', 'enableThumbnailStart'),
               'flickrAPIKey' => pnModGetVar('mediashare', 'flickrAPIKey'),
               'smugmugAPIKey' => pnModGetVar('mediashare', 'smugmugAPIKey'),
               'photobucketAPIKey' => pnModGetVar('mediashare', 'photobucketAPIKey'),
               'picasaAPIKey' => pnModGetVar('mediashare', 'picasaAPIKey'),
               'vfs' => pnModGetVar('mediashare', 'vfs'));
}


function mediashare_userapi_setSettings($args)
{
  if (!pnModSetVar('mediashare', 'tmpDirName', $args['tmpDirName']))
    return mediashareErrorAPI(__FILE__, __LINE__, "Could not set 'tmpDirName'");

  if (!pnModSetVar('mediashare', 'mediaDirName', $args['mediaDirName']))
    return mediashareErrorAPI(__FILE__, __LINE__, "Could not set 'mediaDirName'");

  if (!pnModSetVar('mediashare', 'thumbnailSize', $args['thumbnailSize']))
    return mediashareErrorAPI(__FILE__, __LINE__, "Could not set 'thumbnailSize'");

  if (!pnModSetVar('mediashare', 'previewSize', $args['previewSize']))
    return mediashareErrorAPI(__FILE__, __LINE__, "Could not set 'previewSize'");

  if (!pnModSetVar('mediashare', 'mediaSizeLimitSingle', (int)$args['mediaSizeLimitSingle']*1000))
    return mediashareErrorAPI(__FILE__, __LINE__, "Could not set 'mediaSizeLimitSingle'");

  if (!pnModSetVar('mediashare', 'mediaSizeLimitTotal', (int)$args['mediaSizeLimitTotal']*1000))
    return mediashareErrorAPI(__FILE__, __LINE__, "Could not set 'mediaSizeLimitTotal'");

  if (!pnModSetVar('mediashare', 'defaultAlbumTemplate', $args['defaultAlbumTemplate']))
    return mediashareErrorAPI(__FILE__, __LINE__, "Could not set 'defaultAlbumTemplate'");

  if (!pnModSetVar('mediashare', 'allowTemplateOverride', $args['allowTemplateOverride']))
    return mediashareErrorAPI(__FILE__, __LINE__, "Could not set 'allowTemplateOverride'");

  if (!pnModSetVar('mediashare', 'enableSharpen', $args['enableSharpen']))
    return mediashareErrorAPI(__FILE__, __LINE__, "Could not set 'enableSharpen'");

  if (!pnModSetVar('mediashare', 'enableThumbnailStart', $args['enableThumbnailStart']))
    return mediashareErrorAPI(__FILE__, __LINE__, "Could not set 'enableThumbnailStart'");

  if (!pnModSetVar('mediashare', 'flickrAPIKey', $args['flickrAPIKey']))
    return mediashareErrorAPI(__FILE__, __LINE__, "Could not set 'flickrAPIKey'");

  if (!pnModSetVar('mediashare', 'smugmugAPIKey', $args['smugmugAPIKey']))
    return mediashareErrorAPI(__FILE__, __LINE__, "Could not set 'smugmugAPIKey'");

  if (!pnModSetVar('mediashare', 'photobucketAPIKey', $args['photobucketAPIKey']))
    return mediashareErrorAPI(__FILE__, __LINE__, "Could not set 'photobucketAPIKey'");

  if (!pnModSetVar('mediashare', 'picasaAPIKey', $args['picasaAPIKey']))
    return mediashareErrorAPI(__FILE__, __LINE__, "Could not set 'picasaAPIKey'");

  if (!pnModSetVar('mediashare', 'vfs', $args['vfs']))
    return mediashareErrorAPI(__FILE__, __LINE__, "Could not set 'vfs'");
}


// =======================================================================
// Most xxx
// =======================================================================

function mediashare_userapi_getMostActiveUsers($args)
{
  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  pnModDBInfoLoad('User'); // Ensure DB table info is available

  $mediaTable     = $pntable['mediashare_media'];
  $mediaColumn    = $pntable['mediashare_media_column'];
  $usersTable     = $pntable['users'];
  $usersColumn    = $pntable['users_column'];

  $sql = "SELECT $usersColumn[uname],
                 COUNT(*) cou
          FROM $mediaTable
          LEFT JOIN $usersTable
               ON $usersColumn[uid] = $mediaColumn[ownerId]
          GROUP BY $usersColumn[uname]
          ORDER BY cou DESC";

  $dbresult = $dbconn->selectLimit($sql, 10, 0);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"mostActiveUsers" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  $result = array();
  for (; !$dbresult->EOF; $dbresult->MoveNext())
  {
    $active = array('uname'   => $dbresult->fields[0],
                    'count'   => $dbresult->fields[1]);

    $result[] = $active;
  }

  $dbresult->close();

  return $result;
}


function mediashare_userapi_getMostActiveKeywords($args)
{
  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  pnModDBInfoLoad('User'); // Ensure DB table info is available

  $mediaTable     = $pntable['mediashare_media'];
  $mediaColumn    = $pntable['mediashare_media_column'];
  $usersTable     = $pntable['users'];
  $usersColumn    = $pntable['users_column'];
  $keywordsTable  = $pntable['mediashare_keywords'];
  $keywordsColumn = $pntable['mediashare_keywords_column'];

  $sql = "SELECT $keywordsColumn[keyword],
                 COUNT(*) cou
          FROM $keywordsTable
          GROUP BY $keywordsColumn[keyword]
          ORDER BY $keywordsColumn[keyword]";

  $dbresult = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"mostActiveKeywords" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  $result = array();
  $max = -1;
  $min = -1;
  for (; !$dbresult->EOF; $dbresult->MoveNext())
  {
    $keyword = array('keyword'  => $dbresult->fields[0],
                     'count'    => (int)$dbresult->fields[1]);

    if ($keyword['count'] > $max)
      $max = $keyword['count'];

    if ($keyword['count'] < $min || $min == -1)
      $min = $keyword['count'];

    $result[] = $keyword;
  }

  $dbresult->close();

  $max -= $min;

  for ($i=0,$cou=count($result); $i<$cou; ++$i)
  {
    $result[$i]['percentage'] = (int)(($result[$i]['count']-$min) * 100 / $max);
    $result[$i]['fontsize'] = $result[$i]['percentage'] + 100;
  }

  return $result;
}


function mediashare_userapi_getSummary($args)
{
  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $mediaTable     = $pntable['mediashare_media'];
  $mediaColumn    = $pntable['mediashare_media_column'];
  $albumsTable    = $pntable['mediashare_albums'];
  $albumsColumn   = $pntable['mediashare_albums_column'];

  // Find accessible albums (media count)
  $accessibleAlbumSql = pnModAPIFunc('mediashare', 'user', 'getAccessibleAlbumsSql',
                                     array('access'  => mediashareAccessRequirementViewSomething,
                                           'field'   => "$mediaColumn[parentAlbumId]"));
  if ($accessibleAlbumSql === false)
    return false;

  $summary = array();

  $sql = "SELECT COUNT(*) FROM $mediaTable WHERE $accessibleAlbumSql";

  $dbresult = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"getSummary" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  $summary['mediaCount'] = (int)$dbresult->fields[0];


  // Find accessible albums (album count)
  $accessibleAlbumSql = pnModAPIFunc('mediashare', 'user', 'getAccessibleAlbumsSql',
                                     array('access'  => mediashareAccessRequirementViewSomething,
                                           'field'   => "$albumsColumn[id]"));
  if ($accessibleAlbumSql === false)
    return false;

  $sql = "SELECT COUNT(*) FROM $albumsTable WHERE $accessibleAlbumSql";

  $dbresult = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"getSummary" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  $summary['albumCount'] = (int)$dbresult->fields[0];

  return $summary;
}


// =======================================================================
// Keywords
// =======================================================================

function mediashareAddKeywords(&$item)
{
  $k = trim($item['keywords']);
  if (strlen($k) > 0)
  {
    $item['keywordsArray'] = preg_split("/[\s,]+/", $k);
    $item['hasKeywords'] = true; 
  }
  else
  {
    $item['keywordsArray'] = array();
    $item['hasKeywords'] = false; 
  }
}


function mediashare_userapi_getByKeyword($args)
{
  $keyword = $args['keyword'];

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $mediaTable     = $pntable['mediashare_media'];
  $mediaColumn    = $pntable['mediashare_media_column'];
  $keywordsTable  = $pntable['mediashare_keywords'];
  $keywordsColumn = $pntable['mediashare_keywords_column'];
  $albumsTable    = $pntable['mediashare_albums'];
  $albumsColumn   = $pntable['mediashare_albums_column'];
  $storageTable   = $pntable['mediashare_mediastore'];
  $storageColumn  = $pntable['mediashare_mediastore_column'];

  // Find accessible albums
  $accessibleAlbumSql = pnModAPIFunc('mediashare', 'user', 'getAccessibleAlbumsSql',
                                     array('access'  => mediashareAccessRequirementViewSomething,
                                           'field'   => "media.$mediaColumn[parentAlbumId]"));
  if ($accessibleAlbumSql === false)
    return false;

  $sql = "SELECT album.$albumsColumn[id],
                 album.$albumsColumn[title],
                 media.$mediaColumn[id],
                 media.$mediaColumn[title],
                 media.$mediaColumn[description],
                 media.$mediaColumn[mediaHandler],
                 thumbnail.$storageColumn[fileRef]
          FROM $keywordsTable keyword
          INNER JOIN $mediaTable media
                ON     media.$mediaColumn[id] = keyword.$keywordsColumn[itemId]
                   AND keyword.$keywordsColumn[type] = 'media'
          INNER JOIN $albumsTable album
                ON album.$albumsColumn[id] = media.$mediaColumn[parentAlbumId]
          INNER JOIN $storageTable thumbnail
                ON thumbnail.$storageColumn[id] = $mediaColumn[thumbnailId]
          WHERE ($accessibleAlbumSql) AND keyword.$keywordsColumn[keyword] = '" . pnVarPrepForStore($keyword) . "'
                 
          UNION
            
          SELECT album.$albumsColumn[id],
                 album.$albumsColumn[title],
                 media.$mediaColumn[id],
                 media.$mediaColumn[title],
                 media.$mediaColumn[description],
                 media.$mediaColumn[mediaHandler],
                 thumbnail.$storageColumn[fileRef]
          FROM $keywordsTable keyword
          INNER JOIN $albumsTable album
                ON     album.$albumsColumn[id] = keyword.$keywordsColumn[itemId]
                   AND keyword.$keywordsColumn[type] = 'album'
          INNER JOIN $mediaTable media
               ON media.$mediaColumn[id] = album.$albumsColumn[mainMediaId]
          INNER JOIN $storageTable thumbnail
               ON thumbnail.$storageColumn[id] = $mediaColumn[thumbnailId]
          WHERE ($accessibleAlbumSql) AND keyword.$keywordsColumn[keyword] = '" . pnVarPrepForStore($keyword) . "'";
          
          //ORDER BY album.$albumsColumn[title], media.$mediaColumn[title]";

  //echo "<pre>$sql</pre>\n"; exit(0);
  $dbresult = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"search" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  $result = array();
  for (; !$dbresult->EOF; $dbresult->MoveNext())
  {
    $result[] = array('albumId'       => $dbresult->fields[0],
                      'albumTitle'    => $dbresult->fields[1],
                      'mediaId'       => $dbresult->fields[2],
                      'mediaTitle'    => $dbresult->fields[3],
                      'caption'       => empty($dbresult->fields[3]) ? $dbresult->fields[4] : $dbresult->fields[3],
                      'captionLong'   => empty($dbresult->fields[4]) ? $dbresult->fields[3] : $dbresult->fields[4],
                      'mediaHandler'  => $dbresult->fields[5],
                      'thumbnailRef'  => $dbresult->fields[6]);
  }

  $dbresult->close();

  return $result;
}


// =======================================================================
// Lists
// =======================================================================

function mediashare_userapi_getList($args)
{
  $keyword    = isset($args['keyword']) ? $args['keyword'] : null;
  $uname      = isset($args['uname']) ? $args['uname'] : null;
  $albumId    = isset($args['albumId']) ? $args['albumId'] : null;
  $order      = isset($args['order']) ? $args['order'] : null;
  $orderDir   = isset($args['orderDir']) ? $args['orderDir'] : 'asc';
  $recordPos  = isset($args['recordPos']) ? (int)$args['recordPos'] : 0;
  $pageSize   = isset($args['pageSize']) ? (int)$args['pageSize'] : 5;

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  pnModDBInfoLoad('User'); // Ensure DB table info is available

  $mediaTable     = $pntable['mediashare_media'];
  $mediaColumn    = $pntable['mediashare_media_column'];
  $usersTable     = $pntable['users'];
  $usersColumn    = $pntable['users_column'];
  $keywordsTable  = $pntable['mediashare_keywords'];
  $keywordsColumn = $pntable['mediashare_keywords_column'];
  $albumsTable    = $pntable['mediashare_albums'];
  $albumsColumn   = $pntable['mediashare_albums_column'];
  $storageTable   = $pntable['mediashare_mediastore'];
  $storageColumn  = $pntable['mediashare_mediastore_column'];

  // Find accessible albums
  $accessibleAlbumSql = pnModAPIFunc('mediashare', 'user', 'getAccessibleAlbumsSql',
                                     array('access'  => mediashareAccessRequirementViewSomething,
                                           'field'   => "media.$mediaColumn[parentAlbumId]"));
  if ($accessibleAlbumSql === false)
    return false;

  // Build simple restriction
  $restriction = array();
  $join = array();

  if ($uname != null)
  {
    $restriction[] = "$usersColumn[uname] = '" . pnVarPrepForStore($uname) . "'";
    //$join[] = "INNER JOIN $usersTable ON $usersColumn[uid] = media.$mediaColumn[ownerId]";
  }

  if ($albumId != null)
  {
    $restriction[] = "album.$albumsColumn[id] = " . (int)$albumId;
  }

  $orderKey = 'title';
  switch ($order)
  {
    case 'title': 
      $orderKey = 'title';
      break;
    case 'uname': 
      $orderKey = 'uname';
      break;
    case 'created': 
      $orderKey = 'created';
      break;
    case 'modified': 
      $orderKey = 'modified';
      break;
  }

  $orderDir = ($orderDir == 'desc' ? 'desc' : 'asc');

  $restrictionSql = (count($restriction) > 0 ? ' AND ' . implode(' AND ', $restriction) : '');
  $joinSql = (count($join) > 0 ? implode(' ', $join) : '');

  if ($keyword != null)
  {
    $sql = "(SELECT album.$albumsColumn[id],
                   album.$albumsColumn[title],
                   album.$albumsColumn[keywords],
                   media.$mediaColumn[id],
                   media.$mediaColumn[ownerId],
                   $usersColumn[uname] AS uname,
                   UNIX_TIMESTAMP(media.$mediaColumn[createdDate]) AS created,
                   UNIX_TIMESTAMP(media.$mediaColumn[modifiedDate]) AS modified,
                   media.$mediaColumn[title] AS title,
                   media.$mediaColumn[keywords],
                   media.$mediaColumn[description],
                   media.$mediaColumn[mediaHandler],
                   media.$mediaColumn[position] AS position,
                   thumbnail.$storageColumn[fileRef],
                   preview.$storageColumn[fileRef],
                   preview.$storageColumn[mimeType],
                   preview.$storageColumn[width],
                   preview.$storageColumn[height],
                   preview.$storageColumn[bytes],
                   original.$storageColumn[fileRef],
                   original.$storageColumn[mimeType],
                   original.$storageColumn[width],
                   original.$storageColumn[height],
                   original.$storageColumn[bytes]
            FROM $keywordsTable keyword
            INNER JOIN $mediaTable media
                  ON     media.$mediaColumn[id] = keyword.$keywordsColumn[itemId]
                     AND keyword.$keywordsColumn[type] = 'media'
            INNER JOIN $albumsTable album
                  ON album.$albumsColumn[id] = media.$mediaColumn[parentAlbumId]
            LEFT JOIN $storageTable thumbnail
                 ON thumbnail.$storageColumn[id] = $mediaColumn[thumbnailId]
            LEFT JOIN $storageTable preview
                 ON preview.$storageColumn[id] = $mediaColumn[previewId]
            LEFT JOIN $storageTable original
                 ON original.$storageColumn[id] = $mediaColumn[originalId]
            INNER JOIN $usersTable 
                  ON $usersColumn[uid] = media.$mediaColumn[ownerId]
            $joinSql
            WHERE ($accessibleAlbumSql) AND keyword.$keywordsColumn[keyword] = '" . pnVarPrepForStore($keyword) . "'
                  $restrictionSql)
                   
            UNION
              
            (SELECT album.$albumsColumn[id],
                   album.$albumsColumn[title],
                   album.$albumsColumn[keywords],
                   media.$mediaColumn[id],
                   media.$mediaColumn[ownerId],
                   $usersColumn[uname],
                   UNIX_TIMESTAMP(media.$mediaColumn[createdDate]),
                   UNIX_TIMESTAMP(media.$mediaColumn[modifiedDate]),
                   media.$mediaColumn[title],
                   media.$mediaColumn[keywords],
                   media.$mediaColumn[description],
                   media.$mediaColumn[mediaHandler],
                   media.$mediaColumn[position],
                   thumbnail.$storageColumn[fileRef],
                   preview.$storageColumn[fileRef],
                   preview.$storageColumn[mimeType],
                   preview.$storageColumn[width],
                   preview.$storageColumn[height],
                   preview.$storageColumn[bytes],
                   original.$storageColumn[fileRef],
                   original.$storageColumn[mimeType],
                   original.$storageColumn[width],
                   original.$storageColumn[height],
                   original.$storageColumn[bytes]
            FROM $keywordsTable keyword
            INNER JOIN $albumsTable album
                  ON     album.$albumsColumn[id] = keyword.$keywordsColumn[itemId]
                     AND keyword.$keywordsColumn[type] = 'album'
            INNER JOIN $mediaTable media
                 ON media.$mediaColumn[id] = album.$albumsColumn[mainMediaId]
            LEFT JOIN $storageTable thumbnail
                 ON thumbnail.$storageColumn[id] = $mediaColumn[thumbnailId]
            LEFT JOIN $storageTable preview
                 ON preview.$storageColumn[id] = $mediaColumn[previewId]
            LEFT JOIN $storageTable original
                 ON original.$storageColumn[id] = $mediaColumn[originalId]
            INNER JOIN $usersTable 
                  ON $usersColumn[uid] = media.$mediaColumn[ownerId]
            $joinSql
            WHERE ($accessibleAlbumSql) AND keyword.$keywordsColumn[keyword] = '" . pnVarPrepForStore($keyword) . "'
                  $restrictionSql)
                    
            ORDER BY $orderKey $orderDir";
  }
  else
  {
    $sql = "SELECT album.$albumsColumn[id],
                   album.$albumsColumn[title],
                   album.$albumsColumn[keywords],
                   media.$mediaColumn[id],
                   media.$mediaColumn[ownerId],
                   $usersColumn[uname] AS uname,
                   UNIX_TIMESTAMP(media.$mediaColumn[createdDate]) AS created,
                   UNIX_TIMESTAMP(media.$mediaColumn[modifiedDate]) AS modified,
                   media.$mediaColumn[title] AS title,
                   media.$mediaColumn[keywords],
                   media.$mediaColumn[description],
                   media.$mediaColumn[mediaHandler],
                   media.$mediaColumn[position] AS position,
                   thumbnail.$storageColumn[fileRef],
                   preview.$storageColumn[fileRef],
                   preview.$storageColumn[mimeType],
                   preview.$storageColumn[width],
                   preview.$storageColumn[height],
                   preview.$storageColumn[bytes],
                   original.$storageColumn[fileRef],
                   original.$storageColumn[mimeType],
                   original.$storageColumn[width],
                   original.$storageColumn[height],
                   original.$storageColumn[bytes]
            FROM $mediaTable media
            INNER JOIN $albumsTable album
                  ON album.$albumsColumn[id] = media.$mediaColumn[parentAlbumId]
            LEFT JOIN $storageTable thumbnail
                 ON thumbnail.$storageColumn[id] = $mediaColumn[thumbnailId]
            LEFT JOIN $storageTable preview
                 ON preview.$storageColumn[id] = $mediaColumn[previewId]
            LEFT JOIN $storageTable original
                 ON original.$storageColumn[id] = $mediaColumn[originalId]
            INNER JOIN $usersTable 
                  ON $usersColumn[uid] = media.$mediaColumn[ownerId]
            $joinSql
            WHERE ($accessibleAlbumSql)
                  $restrictionSql
            ORDER BY $orderKey $orderDir";
  }

  //echo "<pre>$sql</pre>\n"; exit(0);
  $dbresult = $dbconn->selectLimit($sql, $pageSize, $recordPos);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"getList" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  $result = array();
  for (; !$dbresult->EOF; $dbresult->MoveNext())
  {
    $album = array('id'       => $dbresult->fields[0],
                   'title'    => $dbresult->fields[1],
                   'keywords' => $dbresult->fields[2]);

    mediashareAddKeywords($album);

    $media = array('id'             => $dbresult->fields[3],
                   'ownerId'        => $dbresult->fields[4],
                   'ownerName'      => $dbresult->fields[5],
                   'createdDate'    => strftime(_MSDATEFORMAT, $dbresult->fields[6]),
                   'modifiedDate'   => strftime(_MSDATEFORMAT, $dbresult->fields[7]),
                   'createdDateRaw' => $dbresult->fields[6],
                   'modifiedDateRaw'=> $dbresult->fields[7],
                   'title'          => $dbresult->fields[8],
                   'keywords'       => $dbresult->fields[9],
                   'description'    => $dbresult->fields[10],
                   'caption'        => empty($dbresult->fields[8]) ? $dbresult->fields[10] : $dbresult->fields[8],
                   'captionLong'    => empty($dbresult->fields[10]) ? $dbresult->fields[8] : $dbresult->fields[10],
                   'mediaHandler'   => $dbresult->fields[11],
                   'thumbnailRef'   => $dbresult->fields[13],
                   'previewRef'        => $dbresult->fields[14],
                   'previewMimeType'   => $dbresult->fields[15],
                   'previewWidth'      => $dbresult->fields[16],
                   'previewHeight'     => $dbresult->fields[17],
                   'previewBytes'      => $dbresult->fields[18],
                   'originalRef'       => $dbresult->fields[19],
                   'originalMimeType'  => $dbresult->fields[20],
                   'originalWidth'     => $dbresult->fields[21],
                   'originalHeight'    => $dbresult->fields[22],
                   'originalBytes'     => $dbresult->fields[23],
                   'originalIsImage'   => substr($dbresult->fields[20],0,6) == 'image/');

    mediashareAddKeywords($media);

    mediashareEscapeAlbum($album, $album['id']);
    mediashareEscapeItem($media, $media['id']);

    $result[] = array('album' => $album, 'media' => $media);
  }

  $dbresult->close();

  return $result;
}


function mediashare_userapi_getListCount($args)
{
  $keyword    = isset($args['keyword']) ? $args['keyword'] : null;
  $uname      = isset($args['uname']) ? $args['uname'] : null;
  $albumId    = $args['albumId'];

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  pnModDBInfoLoad('User'); // Ensure DB table info is available

  $mediaTable     = $pntable['mediashare_media'];
  $mediaColumn    = $pntable['mediashare_media_column'];
  $albumsTable    = $pntable['mediashare_albums'];
  $albumsColumn   = $pntable['mediashare_albums_column'];
  $usersTable     = $pntable['users'];
  $usersColumn    = $pntable['users_column'];
  $keywordsTable  = $pntable['mediashare_keywords'];
  $keywordsColumn = $pntable['mediashare_keywords_column'];

  // Find accessible albums
  $accessibleAlbumSql = pnModAPIFunc('mediashare', 'user', 'getAccessibleAlbumsSql',
                                     array('access'  => mediashareAccessRequirementViewSomething,
                                           'field'   => "media.$mediaColumn[parentAlbumId]"));
  if ($accessibleAlbumSql === false)
    return false;

  // Build simple restriction
  $restriction = array();
  $join = array();

  if ($uname != null)
  {
    $restriction[] = "$usersColumn[uname] = '" . pnVarPrepForStore($uname) . "'";
    //$join[] = "INNER JOIN $usersTable ON $usersColumn[uid] = media.$mediaColumn[ownerId]";
  }

  if ($albumId != null)
  {
    $restriction[] = "album.$albumsColumn[id] = " . (int)$albumId;
  }

  $restrictionSql = (count($restriction) > 0 ? ' AND ' . implode(' AND ', $restriction) : '');
  $joinSql = (count($join) > 0 ? implode(' ', $join) : '');

  if ($keyword != null)
  {
    $sql = "SELECT COUNT(*)
            FROM $keywordsTable keyword
            INNER JOIN $mediaTable media
                  ON     media.$mediaColumn[id] = keyword.$keywordsColumn[itemId]
                     AND keyword.$keywordsColumn[type] = 'media'
            INNER JOIN $usersTable 
                  ON $usersColumn[uid] = media.$mediaColumn[ownerId]
            INNER JOIN $albumsTable album
                  ON album.$albumsColumn[id] = media.$mediaColumn[parentAlbumId]
            $joinSql
            WHERE ($accessibleAlbumSql) AND keyword.$keywordsColumn[keyword] = '" . pnVarPrepForStore($keyword) . "'
                  $restrictionSql";

    $sql2 = "SELECT COUNT(*)
             FROM $keywordsTable keyword
             INNER JOIN $albumsTable album
                   ON     album.$albumsColumn[id] = keyword.$keywordsColumn[itemId]
                      AND keyword.$keywordsColumn[type] = 'album'
             INNER JOIN $mediaTable media
                  ON media.$mediaColumn[id] = album.$albumsColumn[mainMediaId]
             INNER JOIN $usersTable 
                   ON $usersColumn[uid] = media.$mediaColumn[ownerId]
             $joinSql
             WHERE ($accessibleAlbumSql) AND keyword.$keywordsColumn[keyword] = '" . pnVarPrepForStore($keyword) . "'
                   $restrictionSql";
  }
  else
  {
    $sql = "SELECT COUNT(*)
            FROM $mediaTable media
            INNER JOIN $usersTable 
                  ON $usersColumn[uid] = media.$mediaColumn[ownerId]
            INNER JOIN $albumsTable album
                  ON album.$albumsColumn[id] = media.$mediaColumn[parentAlbumId]
            $joinSql
            WHERE ($accessibleAlbumSql)
                  $restrictionSql";

    $sql2 = null;
  }

  //echo "<pre>$sql</pre>\n"; exit(0);
  $dbresult = $dbconn->execute($sql);
  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"getListCount" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  $count = (int)$dbresult->fields[0];

  $dbresult->close();

  if ($sql2 != null)
  {
    $dbresult = $dbconn->execute($sql2);
    if ($dbconn->errorNo() != 0)
      return mediashareErrorAPI(__FILE__, __LINE__, '"getListCount" failed: ' . $dbconn->errorMsg() . " while executing: $sql2");

    $count += (int)$dbresult->fields[0];

    $dbresult->close();
  }

  return $count;
}


// =======================================================================
// Searching
// =======================================================================

function mediashare_userapi_search($args)
{
  $query      = $args['query'];
  $match      = $args['match'];
  $itemIndex  = (int)$args['itemIndex'];
  $pageSize   = (int)$args['pageSize'];

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $mediaTable    = $pntable['mediashare_media'];
  $mediaColumn   = $pntable['mediashare_media_column'];
  $albumsTable   = $pntable['mediashare_albums'];
  $albumsColumn  = $pntable['mediashare_albums_column'];

    // Split query by whitespace allowing use of quotes "..."
  $words = array();
  $count = preg_match_all('/"[^"]+"|[^" ]+/', $query, $words);
  $words = $words[0];

  for ($i=0; $i<$count; ++$i)
    if ($words[$i][0] == '"')
      $words[$i] = substr($words[$i], 1, strlen($words[$i])-2);

    // Combine keywords to SQL restriction
  $restriction = '';
  foreach ($words as $word)
  {
    if ($restriction != '')
      $restriction .= ($match == 'AND' ? ' AND ' : ' OR ');

    $restriction .=   "(media.$mediaColumn[title] LIKE '%" . pnVarPrepForStore($word) . "%' OR "
                    . "media.$mediaColumn[description] LIKE '%" . pnVarPrepForStore($word) . "%' OR "
                    . "media.$mediaColumn[keywords] LIKE '%" . pnVarPrepForStore($word) . "%')";
  }

  // Find accessible albums
  $accessibleAlbumSql = pnModAPIFunc('mediashare', 'user', 'getAccessibleAlbumsSql',
                                     array('access'  => mediashareAccessRequirementViewSomething,
                                           'field'   => "album.$albumsColumn[id]"));
  if ($accessibleAlbumSql === false)
    return false;

  $sql = "SELECT   album.$albumsColumn[id],
                   album.$albumsColumn[title],
                   media.$mediaColumn[id],
                   media.$mediaColumn[title]
                   media.$mediaColumn[description]
          FROM     $albumsTable album
          LEFT JOIN $mediaTable media
               ON media.$mediaColumn[parentAlbumId] = album.$albumsColumn[id]
          WHERE    ($accessibleAlbumSql) AND $restriction
          ORDER BY album.$albumsColumn[title], media.$mediaColumn[title]";

  //echo "<pre>$sql</pre>\n"; exit(0);
  $dbresult = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"search" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  $result = array();
  $i = 0;
  $rowStart = $itemIndex;
  $rowEnd   = $itemIndex + $pageSize;
  for (; !$dbresult->EOF; $dbresult->MoveNext())
  {
    if ($i >= $rowStart  &&  $i < $rowEnd)
    {
      $result[] = array('albumId'           => $dbresult->fields[0],
                        'albumTitle'        => $dbresult->fields[1],
                        'mediaId'           => $dbresult->fields[2],
                        'mediaTitle'        => $dbresult->fields[3],
                        'mediaCaption'      => empty($result->fields[3]) ? $result->fields[4] : $result->fields[3],
                        'mediaCaptionLong'  => empty($result->fields[4]) ? $result->fields[3] : $result->fields[4]);
    }
    ++$i;
  }

  $dbresult->close();

  return array('result'   => $result,
               'hitCount' => $i);
}


function mediashare_userapi_errorAPIGet($args)
{
  return mediashareErrorAPIGet();
}


// =======================================================================
// Templates
// =======================================================================

function mediashare_userapi_getAllTemplates($args)
{
  $templates = array();

  if ($dh = opendir("modules/mediashare/pntemplates/Frontend"))
  {
    while (($filename=readdir($dh)) !== false)
    {
      if ($filename != '.'  &&  $filename != '..'  &&  $filename != 'CVS'  &&  $filename != '.svn')
      {
        $templates[] = array('title' => $filename, 'value' => $filename);
      }
    }

    closedir($dh);
  }

  return $templates;
}


?>