<?php
// $Id: pnextapp_smugmugapi.php,v 1.4 2008/06/22 14:10:29 jornlind Exp $
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
require_once("modules/mediashare/pnincludes/phpSmug/phpSmug.php");


class MediashareSmugMugAlbum
{
  var $_albumId;
  var $_albumData;
  var $_smugApi;


  function MediashareSmugMugAlbum($albumId, $albumData)
  {
    $APIKey = pnModGetVar('mediashare', 'smugmugAPIKey');

    $this->_albumId = $albumId;
    $this->_albumData = $albumData;
    $this->_smugApi = new phpSmug($APIKey);
    $this->_smugApi->enableCache('fs', pnConfigGetVar('temp'));
    $this->_smugApi->login_anonymously();
  }


  function getAlbumData()
  { 
    return $this->_albumData;
  }


  function getMediaItems()
  {
    $data = $this->_albumData['extappData']['data'];

    $images = $this->_smugApi->images_get($data['albumId'], $data['albumKey'], true);

    for ($i=0,$cou=count($images); $i<$cou; ++$i)
    {
      $image = & $images[$i];
      $images[$i] = array
        ( 
          'id'              => $image['id'],
          'ownerId'         => null,
          'createdDate'     => $image['LastUpdated'],
          'modifiedDate'    => $image['LastUpdated'],
          'createdDateRaw'  => $image['LastUpdated'],
          'modifiedDateRaw' => $image['LastUpdated'],
          'title'           => mb_convert_encoding($image['Caption'], _CHARSET, 'UTF-8'),
          'keywords'        => $image['Keywords'],
          'description'     => '',
          'caption'         => mb_convert_encoding($image['Caption'], _CHARSET, 'UTF-8'),
          'captionLong'     => mb_convert_encoding($image['Caption'], _CHARSET, 'UTF-8'),
          'parentAlbumId'   => $this->_albumId,
          'mediaHandler'    => 'imagegd',
          'thumbnailId'     => null,
          'previewId'       => null,
          'originalId'      => null,
          'thumbnailRef'      => $image['TinyURL'],
          'thumbnailMimeType' => 'image/jpeg',
          'thumbnailWidth'    => null,
          'thumbnailHeight'   => null,
          'thumbnailBytes'    => null,
          'previewRef'        => $image['SmallURL'],
          'previewMimeType'   => 'image/jpeg',
          'previewWidth'      => 400,
          'previewHeight'     => null,
          'previewBytes'      => null,
          'originalRef'       => $image['LargeURL'],
          'originalMimeType'  => 'image/jpeg',
          'originalWidth'     => null,
          'originalHeight'    => null,
          'originalBytes'     => null,
          'originalIsImage'   => true,
          'ownerName'         => null);
    }

    return $images;
  }
}


function mediashare_extapp_smugmugapi_parseURL($args)
{
  // User: http://bilroy.smugmug.com/
  // Gallery: http://bilroy.smugmug.com/gallery/5146474_BLeSn#316967890_QjBHz
  // Popular: http://bilroy.smugmug.com/popular/#312830908_gzKhz
  // Keyword: http://bilroy.smugmug.com/keyword/architecture#312830908_gzKhz

  $r = '/smugmug\.com\/gallery\/([-a-zA-Z0-9_]+)_([-a-zA-Z0-9_]+)/';
  if (preg_match($r, $args['url'], $matches))
  {
    return array('albumId' => $matches[1],
                 'albumKey' => $matches[2],
                 'userName' => null);
  }

  return null;
}


function mediashare_extapp_smugmugapi_getAlbumInstance($args)
{
  return new MediashareSmugMugAlbum($args['albumId'], $args['albumData']);
}
