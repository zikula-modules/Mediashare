<?php
// $Id: pnextapp_picasaapi.php,v 1.3 2008/06/22 14:10:29 jornlind Exp $
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
        $images = $this->getRawImages();
        if ($images === false)
            return false;

        for ($i = 0, $cou = count($images); $i < $cou; ++$i) {
            $images[$i] = $this->convertImage($images[$i]);
        }

        $this->fixMainMedia($images);
        return $images;
    }

    function getRawImages()
    {
        $data = $this->albumData['extappData']['data'];

        try {
            if (!empty($data['userName']) && empty($data['albumName'])) {
                $images = $this->getApi()->getImages($data['userName'], 30, 0, null, null, 'public', '72,400', 800)->getImages();
            } else if (!empty($data['userName']) && !empty($data['albumName'])) {
                $images = $this->getApi()->getAlbumByName($data['userName'], $data['albumName'], 30, 0, null, null, '72,400', 800, $data['authkey'])->getImages();
            }
        } catch (Exception $e) {
            return mediashareErrorAPI(__FILE__, __LINE__, "The supplied URL resulted in an error.");
        }

        return $images;
    }

    function convertImage(&$image)
    {
        $dom = ZLanguage::getModuleDomain('Mediashare');
        $thumbUrlMap = $image->getThumbUrlMap();

        $image = array(
            'id' => (string) $image->getIdNum(),
            'ownerId' => null,
            'createdDate' => null,
            'modifiedDate' => null,
            'createdDateRaw' => null,
            'modifiedDateRaw' => null,
            'title' => mb_convert_encoding($image->getTitle(), __('ISO-8859-1', $dom), 'UTF-8'),
            'keywordsArray' => $image->getTags(),
            'hasKeywords' => count($image->getTags()) > 0,
            'keywords' => implode(',', $image->getTags()),
            'description' => mb_convert_encoding($image->getDescription(), __('ISO-8859-1', $dom), 'UTF-8'),
            'caption' => mb_convert_encoding($image->getTitle(), __('ISO-8859-1', $dom), 'UTF-8'),
            'captionLong' => mb_convert_encoding($image->getDescription(), __('ISO-8859-1', $dom), 'UTF-8'),
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

