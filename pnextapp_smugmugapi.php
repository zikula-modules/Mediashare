<?php
// $Id: pnextapp_smugmugapi.php,v 1.4 2008/06/22 14:10:29 jornlind Exp $
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2005.
// =======================================================================

require_once("modules/mediashare/common.php");
require_once("modules/mediashare/pnincludes/phpSmug/phpSmug.php");


class MediashareSmugMugAlbum extends MediashareBaseAlbum
{
  var $smugApi;


  function MediashareSmugMugAlbum($albumId, $albumData)
  {
    $albumData['allowMediaEdit'] = false;
    $this->albumId = $albumId;
    $this->albumData = $albumData;
  }


  function getApi()
  {
    if ($this->smugApi == null)
    {
      $APIKey = pnModGetVar('mediashare', 'smugmugAPIKey');
      $this->smugApi = new phpSmug($APIKey);
      $this->smugApi->enableCache('fs', pnConfigGetVar('temp'));
      $this->smugApi->login_anonymously();
    }
    return $this->smugApi;
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
    $images = $this->getApi()->images_get($data['albumId'], $data['albumKey'], true);
    return $images;
  }


  function convertImage(&$image)
  {
    return array
      ( 
        'id'              => $image['id'],
        'ownerId'         => null,
        'createdDate'     => $image['LastUpdated'],
        'modifiedDate'    => $image['LastUpdated'],
        'createdDateRaw'  => $image['LastUpdated'],
        'modifiedDateRaw' => $image['LastUpdated'],
        'title'           => mb_convert_encoding($image['Caption'], _CHARSET, 'UTF-8'),
        'keywordsArray'   => array(),
        'hasKeywords'     => false,
        'keywords'        => $image['Keywords'],
        'description'     => '',
        'caption'         => mb_convert_encoding($image['Caption'], _CHARSET, 'UTF-8'),
        'captionLong'     => mb_convert_encoding($image['Caption'], _CHARSET, 'UTF-8'),
        'parentAlbumId'   => $this->albumId,
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
