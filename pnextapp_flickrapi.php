<?php
// $Id$
//
// Mediashare by Jorn Lind-Nielsen (C)
//

require_once ("modules/mediashare/common.php");
require_once ("modules/mediashare/pnincludes/phpFlickr/phpFlickr.php");

class MediashareFlickrAlbum extends MediashareBaseAlbum
{
    var $flickrApi;

    function MediashareFlickrAlbum($albumId, $albumData)
    {
        $albumData['allowMediaEdit'] = false;
        $this->albumId = $albumId;
        $this->albumData = $albumData;
    }

    function getApi()
    {
        if ($this->flickrApi == null) {
            $APIKey = pnModGetVar('mediashare', 'flickrAPIKey');
            $this->flickrApi = new phpFlickr($APIKey);
        }
        return $this->flickrApi;
    }

    function getMediaItems()
    {
        if (($images = $this->getRawImages()) === false) {
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
        $data = $this->albumData['extappData']['data'];

        if (empty($data['set'])) {
            $search = array();
            $search['per_page'] = 100;

            if (!empty($data['userName'])) {
                $user = $this->getApi()->urls_lookupUser($this->albumData['extappURL']);
                $search['user_id'] = $user['id'];
            }

            if (!empty($data['tag'])) {
                $search['tags'] = $data['tag'];
            }
            $images = $this->getApi()->photos_search($search);

        } else {
            $setId = $data['set'];
            $images = $this->getApi()->photosets_getPhotos($setId);
        }

        $images = $images['photo'];

        return $images;
    }

    function convertImage(&$image)
    {
        $dom = ZLanguage::getModuleDomain('mediashare');
        $image = array(
            'id' => $image['id'],
            'ownerId' => null,
            'createdDate' => null,
            'modifiedDate' => null,
            'createdDateRaw' => null,
            'modifiedDateRaw' => null,
            'title' => $image['title'],
            'keywordsArray' => array(),
            'hasKeywords' => false,
            'keywords' => null,
            'description' => '',
            'caption' => $image['title'],
            'captionLong' => $image['title'],
            'parentAlbumId' => $this->albumId,
            'mediaHandler' => 'extapp',
            'thumbnailId' => null,
            'previewId' => null,
            'originalId' => null,
            'thumbnailRef' => $this->getApi()->buildPhotoURL($image, "square"),
            'thumbnailMimeType' => 'image/jpeg',
            'thumbnailWidth' => 0,
            'thumbnailHeight' => 0,
            'thumbnailBytes' => 0,
            'previewRef' => $this->getApi()->buildPhotoURL($image, "mediaum"),
            'previewMimeType' => 'image/jpeg',
            'previewWidth' => 400,
            'previewHeight' => 0,
            'previewBytes' => 0,
            'originalRef' => $this->getApi()->buildPhotoURL($image, "large"),
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

function mediashare_extapp_flickrapi_parseURL($args)
{
    // Set: http://flickr.com/photos/jornwildt/sets/72157603856546632/
    // Tags: http://flickr.com/photos/jornwildt/tags/telemark/
    // Tags: http://flickr.com/photos/tags/winter/
    // Photo: http://www.flickr.com/photos/jornwildt/2224446102/in/set-72157603807044854/
    // User: http://www.flickr.com/photos/jornwildt/

    $r = '/flickr.com\/photos\/([-a-zA-Z0-9@_]+)\/tags\/([-a-zA-Z0-9_]+)\//';
    if (preg_match($r, $args['url'], $matches)) {
        return array('userName' => $matches[1], 'set' => null, 'tag' => $matches[2]);
    }

    $r = '/flickr.com\/photos\/([-a-zA-Z0-9@_]+)\/sets\/([-a-zA-Z0-9_]+)\//';
    if (preg_match($r, $args['url'], $matches)) {
        return array('userName' => $matches[1], 'set' => $matches[2], 'tag' => null);
    }

    $r = '/flickr.com\/photos\/([-a-zA-Z0-9@_]+)\/([-a-zA-Z0-9_]+)\/in\/set-([-a-zA-Z0-9_]+)\//';
    if (preg_match($r, $args['url'], $matches)) {
        return array('userName' => $matches[1], 'set' => $matches[3], 'tag' => null);
    }

    $r = '/flickr.com\/photos\/tags\/([-a-zA-Z0-9_]+)\//';
    if (preg_match($r, $args['url'], $matches)) {
        return array('userName' => null, 'set' => null, 'tag' => $matches[1]);
    }

    $r = '/flickr.com\/photos\/([-a-zA-Z0-9@_]+)\//';
    if (preg_match($r, $args['url'], $matches)) {
        return array('userName' => $matches[1], 'set' => null, 'tag' => null);
    }

    return null;
}

function mediashare_extapp_flickrapi_getAlbumInstance($args)
{
    return new MediashareFlickrAlbum($args['albumId'], $args['albumData']);
}
