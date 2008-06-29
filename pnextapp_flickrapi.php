<?php
// $Id: pnextapp_flickrapi.php,v 1.4 2008/06/22 14:10:29 jornlind Exp $
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
require_once("modules/mediashare/pnincludes/phpFlickr/phpFlickr.php");


class MediashareFlickrAlbum extends MediashareBaseAlbum
{
  var $flickrApi;


  function MediashareFlickrAlbum($albumId, $albumData)
  {
    $APIKey = pnModGetVar('mediashare', 'flickrAPIKey');

    $albumData['allowMediaEdit'] = false;
    $this->albumId = $albumId;
    $this->albumData = $albumData;
    $this->flickrApi = new phpFlickr($APIKey);
    $this->flickrApi->enableCache('fs', pnConfigGetVar('temp'));
  }


  function getAlbumData()
  {
    $images = $this->getRawImages();
    if (count($images) > 0)
    {
      $this->albumData['mainMediaId'] = $images[0]['id'];
      $this->albumData['mainMediaItem'] = $this->convertImage($images[0]);
    }
    return $this->albumData;
  }


  function getMediaItems()
  {
    $images = $this->getRawImages();
    for ($i=0,$cou=count($images); $i<$cou; ++$i)
    {
      $images[$i] = $this->convertImage($images[$i]);
    }

    $this->fixMainMedia($images);
    return $images;
  }


  function getRawImages()
  {
    $data = $this->albumData['extappData']['data'];
    
    if (empty($data['set']))
    {
      $search = array();
      $search['per_page'] = 30;

      if (!empty($data['userName']))
      {
         $user = $this->flickrApi->urls_lookupUser($this->albumData['extappURL']);
         $search['user_id'] = $user['id'];
      }

      if (!empty($data['tag']))
      {
        $search['tags'] = $data['tag'];
      }
      $images = $this->flickrApi->photos_search($search);
    }
    else
    {
      $setId = $data['set'];
      $images = $this->flickrApi->photosets_getPhotos($setId);
    }

    //var_dump($images); exit(0);
    $images = $images['photo'];

    return $images;
  }


  function convertImage(&$image)
  {
    $image = array
      ( 
        'id'              => $image['id'],
        'ownerId'         => null,
        'createdDate'     => null,
        'modifiedDate'    => null,
        'createdDateRaw'  => null,
        'modifiedDateRaw' => null,
        'title'           => mb_convert_encoding($image['title'], _CHARSET, 'UTF-8'),
        'keywordsArray'   => array(),
        'hasKeywords'     => false,
        'keywords'        => null,
        'description'     => '',
        'caption'         => mb_convert_encoding($image['title'], _CHARSET, 'UTF-8'),
        'captionLong'     => mb_convert_encoding($image['title'], _CHARSET, 'UTF-8'),
        'parentAlbumId'   => $this->albumId,
        'mediaHandler'    => 'imagegd',
        'thumbnailId'     => null,
        'previewId'       => null,
        'originalId'      => null,
        'thumbnailRef'      => $this->flickrApi->buildPhotoURL($image, "square"),
        'thumbnailMimeType' => 'image/jpeg',
        'thumbnailWidth'    => null,
        'thumbnailHeight'   => null,
        'thumbnailBytes'    => null,
        'previewRef'        => $this->flickrApi->buildPhotoURL($image, "mediaum"),
        'previewMimeType'   => 'image/jpeg',
        'previewWidth'      => 400,
        'previewHeight'     => null,
        'previewBytes'      => null,
        'originalRef'       => $this->flickrApi->buildPhotoURL($image, "large"),
        'originalMimeType'  => 'image/jpeg',
        'originalWidth'     => null,
        'originalHeight'    => null,
        'originalBytes'     => null,
        'originalIsImage'   => true,
        'ownerName'         => null);

    mediashareAddKeywords($image);

    return $image;
  }
}


function mediashare_extapp_flickrapi_parseURL($args)
{
  // Set: http://flickr.com/photos/jornwildt/sets/72157603856546632/
  // Tags: http://flickr.com/photos/jornwildt/tags/telemark/
  // Tags: http://flickr.com/photos/tags/winter/
  // Photo: http://www.flickr.com/photos/jornwildt/2224446102/in/set-72157603807044854/
  // User: http://www.flickr.com/photos/jornwildt/

  $r = '/flickr.com\/photos\/([-a-zA-Z0-9@_]+)\/tags\/([-a-zA-Z0-9_]+)\//';
  if (preg_match($r, $args['url'], $matches))
  {
    return array('userName' => $matches[1],
                 'set' => null,
                 'tag' => $matches[2]);
  }

  $r = '/flickr.com\/photos\/([-a-zA-Z0-9@_]+)\/sets\/([-a-zA-Z0-9_]+)\//';
  if (preg_match($r, $args['url'], $matches))
  {
    return array('userName' => $matches[1],
                 'set' => $matches[2],
                 'tag' => null);
  }

  $r = '/flickr.com\/photos\/([-a-zA-Z0-9@_]+)\/([-a-zA-Z0-9_]+)\/in\/set-([-a-zA-Z0-9_]+)\//';
  if (preg_match($r, $args['url'], $matches))
  {
    return array('userName' => $matches[1],
                 'set' => $matches[3],
                 'tag' => null);
  }

  $r = '/flickr.com\/photos\/tags\/([-a-zA-Z0-9_]+)\//';
  if (preg_match($r, $args['url'], $matches))
  {
    return array('userName' => null,
                 'set' => null,
                 'tag' => $matches[1]);
  }

  $r = '/flickr.com\/photos\/([-a-zA-Z0-9@_]+)\//';
  if (preg_match($r, $args['url'], $matches))
  {
    return array('userName' => $matches[1],
                 'set' => null,
                 'tag' => null);
  }

  return null;
}


function mediashare_extapp_flickrapi_getAlbumInstance($args)
{
  return new MediashareFlickrAlbum($args['albumId'], $args['albumData']);
}
