<?php
// $Id$
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2005.
// =======================================================================

require_once ("modules/mediashare/common.php");
require_once ("modules/mediashare/pnincludes/phpPicasa/Picasa.php");

class MediasharePicasaAlbum extends MediashareBaseAlbum
{
    var $picasaApi;

    function MediasharePicasaAlbum($albumId, $albumData)
    {
        $albumData['allowMediaEdit'] = false;
        $this->albumId = $albumId;
        $this->albumData = $albumData;
    }

    function getApi()
    {
        if ($this->picasaApi == null) {
            $APIKey = pnModGetVar('mediashare', 'picasaAPIKey');
            $this->picasaApi = new Picasa($APIKey);
        }
        return $this->picasaApi;
    }

    function getMediaItems()
    {
        if (!($images = $this->getRawImages())) {
            return false;
        }

        for ($i = 0, $cou = count($images); $i < $cou; ++$i) {
            $images[$i] = $this->convertImage($images[$i]);
        }

        $this->fixMainMedia($images);
        return $images;
    }

    function getRawImages()
    {
        $dom = ZLanguage::getModuleDomain('mediashare');

        $data = $this->albumData['extappData']['data'];

        try {
            if (!empty($data['userName']) && empty($data['albumName'])) {
                $images = $this->getApi()->getImages($data['userName'], 30, 0, null, null, 'public', '72,400', 800)->getImages();
            } else if (!empty($data['userName']) && !empty($data['albumName'])) {
                $images = $this->getApi()->getAlbumByName($data['userName'], $data['albumName'], 30, 0, null, null, '72,400', 800, $data['authkey'])->getImages();
            }
        } catch (Exception $e) {
            return LogUtil::registerError(__('The supplied URL resulted in an error.', $dom));
        }

        return $images;
    }

    function convertImage(&$image)
    {
        $dom = ZLanguage::getModuleDomain('mediashare');
        $thumbUrlMap = $image->getThumbUrlMap();

        $image = array(
            'id' => (string) $image->getIdNum(),
            'ownerId' => null,
            'createdDate' => null,
            'modifiedDate' => null,
            'createdDateRaw' => null,
            'modifiedDateRaw' => null,
            'title' => $image->getTitle(),
            'keywordsArray' => $image->getTags(),
            'hasKeywords' => count($image->getTags()) > 0,
            'keywords' => implode(',', $image->getTags()),
            'description' => $image->getDescription(),
            'caption' => $image->getTitle(),
            'captionLong' => $image->getDescription(),
            'parentAlbumId' => $this->albumId,
            'mediaHandler' => 'extapp',
            'thumbnailId' => null,
            'previewId' => null,
            'originalId' => null,
            'thumbnailRef' => (string) $image->getSmallThumb(),
            'thumbnailMimeType' => 'image/jpeg',
            'thumbnailWidth' => 72,
            'thumbnailHeight' => 0,
            'thumbnailBytes' => 0,
            'previewRef' => (string) $image->getMediumThumb(),
            'previewMimeType' => 'image/jpeg',
            'previewWidth' => 400,
            'previewHeight' => 0,
            'previewBytes' => 0,
            'originalRef' => (string) $image->getContent(),
            'originalMimeType' => 'image/jpeg',
            'originalWidth' => 0,
            'originalHeight' => 0,
            'originalBytes' => 0,
            'originalIsImage' => true,
            'ownerName' => null);

        mediashareAddKeywords($image);

        return $image;
    }
}

function mediashare_extapp_picasaapi_parseURL($args)
{
    $r = '/picasaweb.google.com\/([-a-zA-Z0-9_.]+)\/([-a-zA-Z0-9_.]+)(\?authkey=([a-zA-Z0-9]+))?.*/';
    if (preg_match($r, $args['url'], $matches)) {
        return array('userName' => $matches[1], 'albumName' => $matches[2], 'authkey' => $matches[4]);
    }

    $r = '/picasaweb.google.com\/([-a-zA-Z0-9_.]+)\/?/';
    if (preg_match($r, $args['url'], $matches)) {
        return array('userName' => $matches[1], 'albumName' => null, 'authkey' => null);
    }

    return null;
}

function mediashare_extapp_picasaapi_getAlbumInstance($args)
{
    return new MediasharePicasaAlbum($args['albumId'], $args['albumData']);
}
