<?php
// $Id: pnextapp_picasaapi.php,v 1.3 2008/06/22 14:10:29 jornlind Exp $
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2005.
// =======================================================================

require_once("modules/mediashare/common.php");
require_once("modules/mediashare/pnincludes/phpPicasa/Picasa.php");


class MediasharePicasaAlbum extends MediashareBaseAlbum
{
  var $cache_dir;
  var $cache_expire;
  var $picasaApi;


  function MediasharePicasaAlbum($albumId, $albumData)
  {
    $albumData['allowMediaEdit'] = false;
    $this->albumId = $albumId;
    $this->albumData = $albumData;
  }


  function getApi()
  {
    if ($this->picasaApi == null)
    {
      $APIKey = pnModGetVar('mediashare', 'picasaAPIKey');
      $this->picasaApi = new Picasa($APIKey);
      //$this->enableCache(pnConfigGetVar('temp'));
    }
    return $this->picasaApi;
  }


  function enableCache($connection, $cache_expire = 3600)
  {
    $connection = realpath($connection);
    $this->cache_dir = $connection;
    if ($dir = @opendir($this->cache_dir))
    {
      if (is_writeable($this->cache_dir))
      {
        while ($file = readdir($dir))
        {
          if (substr($file, -6) == '.cache' && ((filemtime($this->cache_dir . '/' . $file) + $cache_expire) < time()) )
          {
            unlink($this->cache_dir . '/' . $file);
          }
        }
        closedir($dir);
      }
      else
      {
        die("Cache Directory \"".$this->cache_dir."\" is not writeable.  Please set the appropriate permissions.");
      }
    }
    else
    {
      die("Cache Directory \"".$this->cache_dir."\" doesn't exist or is not readable.  Please create this directory and set appropriate permissions.");
    }
    $this->cache_expire = $cache_expire;
  }


  function cache($request, $response)
  {
    $reqhash = md5(serialize($request));
    $file = $this->cache_dir . "/" . $reqhash . '.cache';
    $fstream = fopen($file, "w");
    $result = fwrite($fstream,serialize($response));
    fclose($fstream);
    return $result;
  }


  function getCached($request)
  {
    $reqhash = md5(serialize($request));
    $expire = $this->cache_expire;
    $file = $this->cache_dir . '/' . $reqhash . '.cache';
    if (file_exists($file) && filemtime($file)+$expire > time())
    {
      return unserialize(file_get_contents($file));
    }
    return false;
  }

  
  function clearCache()
  {
    if ($dir = @opendir($this->cache_dir))
    {
      while ($file = readdir($dir)) 
      {
        if (substr($file, -6) == '.cache')
        {
          $result = unlink($this->cache_dir . '/' . $file);
        } 
      }
      closedir($dir);
    }
    return true;
  }

  
  function getAlbumData()
  {
    $images = $this->getMediaItems();
    if (empty($images))
    {
      $this->albumData['mainMediaId'] = null;
      $this->albumData['mainMediaItem'] = null;
    }
    else
    {
      $this->albumData['mainMediaId'] = $images[0]['id'];
      $this->albumData['mainMediaItem'] = $this->convertImage($images[0]);
    }
    return $this->albumData;
  }


  function getMediaItems()
  {
    $images = $this->getRawImages();
    if ($images === false)
      return array();

    for ($i=0,$cou=count($images); $i<$cou; ++$i)
    {
      $images[$i] = $this->convertImage($images[$i]);
    }

    $this->fixMainMedia($images);
    return $images;

/*
    $data = $this->albumData['extappData']['data'];

    if ($images = $this->getCached($data))
      return $images;

    if (!empty($data['userName'])  &&  empty($data['albumName']))
    {
      $images = $this->picasaApi->getImages($data['userName'], 30, 0, null, null, 'public', '72,400', 800)->getImages();
    }
    else if (!empty($data['userName'])  &&  !empty($data['albumName']))
    {
      $images = $this->picasaApi->getAlbumByName($data['userName'], $data['albumName'], 30, 0, null, null, '72,400', 800)->getImages();
    }

    for ($i=0,$cou=count($images); $i<$cou; ++$i)
    {
      $images[$i] = $this->convertImage($images[$i]);
    }

    $this->cache($data, $images);

    $this->fixMainMedia($images);
    return $images;
    */
  }


  function getRawImages()
  {
    $data = $this->albumData['extappData']['data'];

    if (!empty($data['userName'])  &&  empty($data['albumName']))
    {
      $images = $this->getApi()->getImages($data['userName'], 30, 0, null, null, 'public', '72,400', 800)->getImages();
    }
    else if (!empty($data['userName'])  &&  !empty($data['albumName']))
    {
      $images = $this->getApi()->getAlbumByName($data['userName'], $data['albumName'], 30, 0, null, null, '72,400', 800)->getImages();
    }

    return $images;
  }

  
  function convertImage(&$image)
  {
    $thumbUrlMap = $image->getThumbUrlMap();

    $image = array
      ( 
        'id'              => (string)$image->getIdNum(),
        'isExternal'      => true,
        'ownerId'         => null,
        'createdDate'     => null,
        'modifiedDate'    => null,
        'createdDateRaw'  => null,
        'modifiedDateRaw' => null,
        'title'           => mb_convert_encoding($image->getTitle(), _CHARSET, 'UTF-8'),
        'keywordsArray'   => $image->getTags(),
        'hasKeywords'     => count($image->getTags()) > 0,
        'keywords'        => implode(',', $image->getTags()),
        'description'     => mb_convert_encoding($image->getDescription(), _CHARSET, 'UTF-8'),
        'caption'         => mb_convert_encoding($image->getTitle(), _CHARSET, 'UTF-8'),
        'captionLong'     => mb_convert_encoding($image->getDescription(), _CHARSET, 'UTF-8'),
        'parentAlbumId'   => $this->albumId,
        'mediaHandler'    => 'extapp',
        'thumbnailId'     => null,
        'previewId'       => null,
        'originalId'      => null,
        'thumbnailRef'      => (string)$image->getSmallThumb(),
        'thumbnailMimeType' => 'image/jpeg',
        'thumbnailWidth'    => 72,
        'thumbnailHeight'   => 0,
        'thumbnailBytes'    => 0,
        'previewRef'        => (string)$image->getMediumThumb(),
        'previewMimeType'   => 'image/jpeg',
        'previewWidth'      => 400,
        'previewHeight'     => 0,
        'previewBytes'      => 0,
        'originalRef'       => (string)$image->getContent(),
        'originalMimeType'  => 'image/jpeg',
        'originalWidth'     => 0,
        'originalHeight'    => 0,
        'originalBytes'     => 0,
        'originalIsImage'   => true,
        'ownerName'         => null);

    mediashareAddKeywords($image);

    return $image;
  }
}


function mediashare_extapp_picasaapi_parseURL($args)
{
  // Album: http://picasaweb.google.com/GisseDk/PrinsesseRagnhildIDokVedOrskov

  $r = '/picasaweb.google.com\/([-a-zA-Z0-9_.]+)\/([-a-zA-Z0-9_.]+)(\?authkey=([a-zA-Z0-9]+))?.*/';
  if (preg_match($r, $args['url'], $matches))
  {
    return array('userName' => $matches[1],
                 'albumName' => $matches[2],
                 'authkey' => $matches[4]);
  }

  $r = '/picasaweb.google.com\/([-a-zA-Z0-9_.]+)\/?/';
  if (preg_match($r, $args['url'], $matches))
  {
    return array('userName' => $matches[1],
                 'albumName' => null,
                 'authkey' => null);
  }

  return null;
}


function mediashare_extapp_picasaapi_getAlbumInstance($args)
{
  return new MediasharePicasaAlbum($args['albumId'], $args['albumData']);
}

