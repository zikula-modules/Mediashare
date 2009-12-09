<?php
// $Id$
//
// Mediashare by Jorn Lind-Nielsen (C)
//

require_once ("modules/mediashare/common.php");

/**
 * Album class definition
 */
class MediashareBaseAlbum
{
    var $albumId;
    var $albumData;

    function fixMainMedia($images)
    {
        $mainMediaId = $this->albumData['mainMediaId'];
        $mainMedia = null;
        for ($i = 0, $cou = count($images); $i < $cou; ++$i) {
            $image = & $images[$i];
            if ($image['id'] == $mainMediaId) {
                $mainMedia = $image;
            }
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

function &mediashareGetAlbumInstance($albumId, $albumData)
{
    static $albumInstances = array();

    if (!isset($albumInstances[$albumId])) {
        if (empty($albumData['extappData'])) {
            $albumInstances[$albumId] = & new MediashareAlbum($albumId, $albumData);
        } else {
            $data = $albumData['extappData'];
            $albumInstances[$albumId] = pnModAPIFunc('mediashare', "extapp_{$data['appName']}", 'getAlbumInstance', array('albumId' => $albumId, 'albumData' => $albumData));
        }
    }

    return $albumInstances[$albumId];
}

/**
 * Access
 */
function mediashare_userapi_hasAlbumAccess($args)
{
    $albumId = (int)$args['albumId'];
    $access  = (int)$args['access'];
    $viewKey = $args['viewKey'];

    $accessApi = mediashareGetAccessAPI();

    return $accessApi->hasAlbumAccess($albumId, $access, $viewKey);
}

function mediashare_userapi_getAlbumAccess($args)
{
    $albumId = (int)$args['albumId'];

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
    $access  = (int)$args['access'];
    $viewKey = $args['viewKey'];

    $accessApi = mediashareGetAccessAPI();

    return $accessApi->hasItemAccess($mediaId, $access, $viewKey);
}

/**
 * Albums
 */
function mediashare_userapi_getAlbum($args)
{
    return mediashare_userapi_getAlbumData($args);
}

function &mediashare_userapi_getAlbumObject($args)
{
    $albumData = mediashare_userapi_getAlbumData($args);
    if ($albumData == false) {
        return false;
    }

    return mediashareGetAlbumInstance($albumData['id'], $albumData);
}

function mediashare_userapi_getAlbumData($args)
{
    // Check access
    if (!SecurityUtil::checkPermission('mediashare::', '::', ACCESS_READ)) {
        return LogUtil::registerPermissionError();
    }

    $dom = ZLanguage::getModuleDomain('mediashare');

    // Argument check
    if (!isset($args['albumId']) || !is_integer($args['albumId'])) {
        return LogUtil::registerError(__('Missing [%1$s] in \'%2$s\'', array('albumId', 'userapi.getAlbumData'), $dom));
    }

    $enableEscape   = isset($args['enableEscape']) ? $args['enableEscape'] : true;
    $countSubAlbums = isset($args['countSubAlbums']) ? $args['countSubAlbums'] : false; // FIXME unused param

    $album = DBUtil::selectObjectByID('mediashare_albums', $args['albumId'], 'id');

    if ($album === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('userapi.getAlbumData', 'Could not retrieve the album information.'), $dom));
    }
    if (!$album) {
        return LogUtil::registerError(__('Unknown album ID (%s).', $args['albumId'], $dom));
    }

     // select post process
    $album['extappData'] = unserialize($album['extappData']);
    $album['imageCount'] = 0; // FIXME

    if ((int)$album['mainMediaId'] > 0) {
        $album['mainMediaItem'] = pnModAPIFunc('mediashare', 'user', 'getMediaItem', array('mediaId' => $album['mainMediaId']));
    } else {
        $album['mainMediaItem'] = null;
    }

    mediashareAddKeywords($album);
    $album['allowMediaEdit'] = true;

    if ($enableEscape) {
        mediashareEscapeAlbum($album, $args['albumId']);
    }

    return $album;
}

function mediashare_userapi_getAllAlbums($args)
{
    $args['recursively'] = true;
    return mediashare_userapi_getSubAlbums($args);
}

function mediashare_userapi_getSubAlbums($args)
{
    return mediashare_userapi_getSubAlbumsData($args);
}

function mediashare_userapi_getSubAlbumsData($args)
{
    // Check access
    if (!SecurityUtil::checkPermission('mediashare::', '::', ACCESS_READ)) {
        return LogUtil::registerPermissionError();
    }

    $dom = ZLanguage::getModuleDomain('mediashare');

    // Argument check
    if (!isset($args['albumId'])) {
        return LogUtil::registerError(__('Missing [%1$s] in \'%2$s\'', array('albumId', 'userapi.getSubAlbumsData'), $dom));
    }

    $albumId         = (int)$args['albumId'];
    $startnum        = isset($args['startnum']) ? (int)$args['startnum'] : -1;
    $numitems        = isset($args['numitems']) ? (int)$args['numitems'] : -1;
    $recursively     = isset($args['recursively']) ? (bool)$args['recursively'] : false;
    $access          = isset($args['access']) ? (int)$args['access'] : 0xFF;
    $excludeAlbumId  = isset($args['excludeAlbumId']) ? (int)$args['excludeAlbumId'] : null;
    $onlyMine        = isset($args['onlyMine']) ? $args['onlyMine'] : false;
    $includeMainItem = isset($args['includeMainItem']) ? (bool)$args['includeMainItem'] : true; // FIXME rework this to default false
    $enableEscape    = isset($args['enableEscape']) ? $args['enableEscape'] : true;

    $pntable      = &pnDBGetTables();
    $albumsColumn = $pntable['mediashare_albums_column'];

    $accessibleAlbumSql = pnModAPIFunc('mediashare', 'user', 'getAccessibleAlbumsSql',
                                       array('albumId' => ($recursively ? null : $albumId),
                                             'access'  => $access,
                                             'field'   => $albumsColumn['id']));

    if (!$accessibleAlbumSql) {
        return false;
    }

    $excludeRestriction = '';
    if ($excludeAlbumId != null) {
        if (!($excludeAlbum = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $excludeAlbumId)))) {
            return false;
        }

        $excludeRestriction = " AND (album.$albumsColumn[nestedSetLeft] < $excludeAlbum[nestedSetLeft]
                                  OR album.$albumsColumn[nestedSetRight] > $excludeAlbum[nestedSetRight]) ";
    }

    $mineSql = '';
    if ($onlyMine) {
        $uid     = (int)pnUserGetVar('uid');
        $mineSql = " AND album.$albumsColumn[ownerId] = '$uid'";
    }

    $where = "($accessibleAlbumSql) $excludeRestriction $mineSql";
    if ($recursively) {
        $orderby = "$albumsColumn[nestedSetLeft], $albumsColumn[title]";
    } else {
        $where  .= " AND $albumsColumn[parentAlbumId] = '$albumId'";
        $orderby = $albumsColumn['title'];
    }

    $subalbums = DBUtil::selectObjectArray('mediashare_albums', $where, $orderby, $startnum, $numitems, 'id');

    if ($subalbums === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('userapi.getSubAlbumsData', 'Could not retrieve the sub albums information.'), $dom));
    }

    foreach (array_keys($subalbums) as $k)
    {
        if ($includeMainItem && (int)$subalbums[$k]['mainMediaId'] > 0) {
            $subalbums[$k]['mainMediaItem'] = pnModAPIFunc('mediashare', 'user', 'getMediaItem', array('mediaId' => $subalbums[$k]['mainMediaId']));
        }

        $subalbums[$k]['extappData'] = unserialize($subalbums[$k]['extappData']);

        mediashareAddKeywords($subalbums[$k]);

        if ($enableEscape) {
            mediashareEscapeAlbum($subalbums[$k], $albumId);
        }
    }

    return $subalbums;
}

function mediashare_userapi_getAlbumBreadcrumb($args)
{
    // Argument check
    if (!isset($args['albumId'])) {
        return LogUtil::registerError(__('Missing [%1$s] in \'%2$s\'', array('albumId', 'userapi.getAlbumBreadcrumb'), $dom));
    }
    $albumId = (int)$args['albumId'];

    $pntable = pnDBGetTables();

    $albumsTable  = $pntable['mediashare_albums'];
    $albumsColumn = $pntable['mediashare_albums_column'];

    $sql = "    SELECT parentAlbum.$albumsColumn[id],
                       parentAlbum.$albumsColumn[title]
                  FROM $albumsTable parentAlbum
       LEFT OUTER JOIN $albumsTable album
                    ON album.$albumsColumn[nestedSetLeft] >= parentAlbum.$albumsColumn[nestedSetLeft]
                   AND album.$albumsColumn[nestedSetRight] <= parentAlbum.$albumsColumn[nestedSetRight]
                 WHERE album.$albumsColumn[id] = $albumId
              ORDER BY parentAlbum.$albumsColumn[nestedSetLeft]";

    $result = DBUtil::executeSQL($sql);

    if (!$result) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('userapi.getAlbumBreadcrumb', 'Could not retrieve the breadcrumb information.'), $dom));
    }

    return DBUtil::marshallObjects($result, array('id', 'title'));
}

function mediashare_userapi_getAlbumList($args)
{
    // Check access
    if (!SecurityUtil::checkPermission('mediashare::', '::', ACCESS_READ)) {
        return LogUtil::registerPermissionError();
    }

    $dom = ZLanguage::getModuleDomain('mediashare');

    $recordPos       = isset($args['recordPos']) ? (int)$args['recordPos'] : 0;
    $pageSize        = isset($args['pageSize']) ? (int)$args['pageSize'] : 5;
    $access          = isset($args['access']) ? $args['access'] : mediashareAccessRequirementView;
    $includeMainItem = isset($args['includeMainItem']) ? (bool)$args['includeMainItem'] : true; // FIXME rework this to default false
    $enableEscape    = isset($args['enableEscape']) ? $args['enableEscape'] : true;

    $pntable      = &pnDBGetTables();
    $albumsColumn = $pntable['mediashare_albums_column'];

    $where = pnModAPIFunc('mediashare', 'user', 'getAccessibleAlbumsSql',
                          array('access' => $access,
                                'field'  => $albumsColumn['id']));
    if (!$where) {
        return false;
    }

    $orderby = "$albumsColumn[createdDate] DESC";
    $albums  = DBUtil::selectObjectArray('mediashare_albums', $where, $orderby, $recordPos, $pageSize, 'id');

    if ($albums === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('userapi.getAlbumList', 'Could not retrieve the albums list.'), $dom));
    }

    foreach (array_keys($albums) as $aid)
    {
        if ($includeMainItem && (int)$albums[$aid]['mainMediaId'] > 0) {
            $subalbums[$aid]['mainMediaItem'] = pnModAPIFunc('mediashare', 'user', 'getMediaItem', array('mediaId' => $albums[$aid]['mainMediaId']));
        }

        $albums[$aid]['extappData'] = unserialize($albums[$aid]['extappData']);

        mediashareAddKeywords($albums[$aid]);

        if ($enableEscape) {
            mediashareEscapeAlbum($albums[$aid], $aid);
        }
    }

    return $albums;
}

function mediashare_userapi_getFirstItemIdInAlbum($args)
{
    // Check access
    if (!SecurityUtil::checkPermission('mediashare::', '::', ACCESS_READ)) {
        return LogUtil::registerPermissionError();
    }

    $dom = ZLanguage::getModuleDomain('mediashare');

    // Argument check
    if (!isset($args['albumId'])) {
        return LogUtil::registerError(__('Missing [%1$s] in \'%2$s\'', array('albumId', 'userapi.getFirstItemIdInAlbum'), $dom));
    }

    $albumId = (int)$args['albumId'];
    
    $where   = "$mediaColumn[parentAlbumId] = '$albumId'";
    $orderby = "$mediaColumn[createdDate] DESC";
    $media   = DBUtil::selectFieldArray('mediashare_media', 'id', $where, $orderby);

    if ($media === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('userapi.getFirstItemInAlbum', 'Could not retrieve the album information.'), $dom));
    }
    if (!$media) {
        return true;
    }

    return $media[0];
}

/**
 * Media items
 */
function mediashare_userapi_getMediaItem($args)
{
    // Check access
    if (!SecurityUtil::checkPermission('mediashare::', '::', ACCESS_READ)) {
        return LogUtil::registerPermissionError();
    }

    $dom = ZLanguage::getModuleDomain('mediashare');

    // Argument check
    if (!isset($args['mediaId'])) {
        return LogUtil::registerError(__('Missing [%1$s] in \'%2$s\'', array('mediaId', 'userapi.getMediaItem'), $dom));
    }

    $enableEscape = isset($args['enableEscape']) ? $args['enableEscape'] : true;

    $mediaId = (int)$args['mediaId'];

    $pntable = pnDBGetTables();

    $mediaTable    = $pntable['mediashare_media'];
    $mediaColumn   = $pntable['mediashare_media_column'];
    $storageTable  = $pntable['mediashare_mediastore'];
    $storageColumn = $pntable['mediashare_mediastore_column'];

    $sql = "SELECT $mediaColumn[id],
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
             WHERE $mediaColumn[id] = '$mediaId'";

    $result = DBUtil::executeSQL($sql);

    if ($result === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('userapi.getMediaItem', 'Could not retrieve the media information.'), $dom));
    }
    if ($result->EOF) {
        return null;
    }

    $colArray = array('id', 'ownerId', 'createdDate', 'modifiedDate',
                      'title', 'keywords', 'description',
                      'parentAlbumId', 'position', 'mediaHandler',
                      'thumbnailId', 'previewId', 'originalId',
                      'thumbnailRef', 'thumbnailMimeType', 'thumbnailWidth', 'thumbnailHeight', 'thumbnailBytes',
                      'previewRef', 'previewMimeType', 'previewWidth', 'previewHeight', 'previewBytes',
                      'originalRef', 'originalMimeType', 'originalWidth', 'originalHeight', 'originalBytes');

    list($item) = DBUtil::marshallObjects($result, $colArray);

    // select post process
    $item['caption'] = empty($item['title']) ? $item['description'] : $item['title'];
    $item['captionLong'] = empty($item['description']) ? $item['title'] : $item['description'];
    $item['originalIsImage'] = substr($item['originalMimeType'], 0, 6) == 'image/';

    if ($enableEscape) {
        mediashareEscapeItem($item, $item['id']);
    }

    mediashareAddKeywords($item);

    return $item;
}

function mediashare_userapi_getMediaUrl(&$args)
{
    $mediaItem = null;
    $src = $args['src'];

    if (isset($args['mediaId'])) {
        $mediaItem = pnModAPIFunc('mediashare', 'user', 'getMediaItem', array('mediaId' => $args['mediaId']));
    } else if (isset($args['mediaItem'])) {
        $mediaItem = $args['mediaItem'];
    } else {
        return LogUtil::registerError(__('Missing [%1$s] in \'%2$s\'', array('mediaId / mediaItem', 'userapi.getMediaUrl'), $dom));
    }

    $url = $mediaItem[$src];

    // Check for absolute URLs returned by external apps.
    if (substr($url, 0, 4) == 'http') {
        return $url;
    }

    $mediadir = pnModAPIFunc('mediashare', 'user', 'getRelativeMediadir');

    return $mediadir.$mediaItem[$src];
}

function mediashare_userapi_getMediaItems($args)
{
    // Check access
    if (!SecurityUtil::checkPermission('mediashare::', '::', ACCESS_READ)) {
        return LogUtil::registerPermissionError();
    }

    $dom = ZLanguage::getModuleDomain('mediashare');

    // Argument check
    if (!isset($args['albumId']) && !isset($args['mediaIdList'])) {
        return LogUtil::registerError(__('Missing [%1$s] in \'%2$s\'', array('albumId / mediaIdList', 'userapi.getMediaItems'), $dom));
    }
/*
    if (isset($args['albumId'])) {
        $album = mediashare_userapi_getAlbumObject($args);
       return $album->getMediaItems();
    } else {
        return mediashareGetMediaItemsData($args);
    }
*/
    return mediashareGetMediaItemsData($args);
}

function mediashareGetMediaItemsData($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $albumId      = isset($args['albumId']) ? (int)$args['albumId'] : null;
    $mediaIdList  = isset($args['mediaIdList']) ? $args['mediaIdList'] : null;
    $enableEscape = isset($args['enableEscape']) ? $args['enableEscape'] : true;
    $access       = isset($args['access']) ? $args['access'] : mediashareAccessRequirementView;
    $startnum     = isset($args['startnum']) ? (int)$args['startnum'] : -1;
    $numitems     = isset($args['numitems']) ? (int)$args['numitems'] : -1;

    pnModDBInfoLoad('User'); // Ensure DB table info is available

    $pntable = pnDBGetTables();

    $mediaTable    = $pntable['mediashare_media'];
    $mediaColumn   = $pntable['mediashare_media_column'];
    $storageTable  = $pntable['mediashare_mediastore'];
    $storageColumn = $pntable['mediashare_mediastore_column'];

    if (!empty($albumId)) {
        $albumRestriction = "$mediaColumn[parentAlbumId] = $albumId";

    } elseif (!empty($mediaIdList) && is_array($mediaIdList)) {
        foreach (array_keys($mediaIdList) as $i) {
            $mediaIdList[$i] = (int)$mediaIdList[$i];
        }
        $albumRestriction = "$mediaColumn[id] IN ('" . implode("','", $mediaIdList) . "')";

        $accessibleAlbumSql = pnModAPIFunc('mediashare', 'user', 'getAccessibleAlbumsSql',
                                           array('access' => $access,
                                                 'field'  => "$mediaColumn[parentAlbumId]"));
        if (!$accessibleAlbumSql) {
            return false;
        }
        $albumRestriction .= " AND $accessibleAlbumSql";

    } else {
        return LogUtil::registerError(__('Missing [%1$s] in \'%2$s\'', array('albumId / mediaIdList', 'userapi.mediashareGetMediaItemsData'), $dom));
    }

    $sql = "SELECT $mediaColumn[id],
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
                   original.$storageColumn[bytes]
              FROM $mediaTable
         LEFT JOIN $storageTable thumbnail
                ON thumbnail.$storageColumn[id] = $mediaColumn[thumbnailId]
         LEFT JOIN $storageTable preview
                ON preview.$storageColumn[id] = $mediaColumn[previewId]
         LEFT JOIN $storageTable original
                ON original.$storageColumn[id] = $mediaColumn[originalId]
             WHERE $albumRestriction
          ORDER BY $mediaColumn[position]";

    $result = DBUtil::executeSQL($sql, $startnum, $numitems);

    if ($result === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('userapi.getMediaItems', 'Could not retrieve the media items.'), $dom));
    }

    $colArray = array('id', 'ownerId', 'createdDate', 'modifiedDate',
                      'title', 'keywords', 'description',
                      'parentAlbumId', 'mediaHandler',
                      'thumbnailId', 'previewId', 'originalId',
                      'thumbnailRef', 'thumbnailMimeType', 'thumbnailWidth', 'thumbnailHeight', 'thumbnailBytes',
                      'previewRef', 'previewMimeType', 'previewWidth', 'previewHeight', 'previewBytes',
                      'originalRef', 'originalMimeType', 'originalWidth', 'originalHeight', 'originalBytes',
                      'ownerName');

    $items = DBUtil::marshallObjects($result, $colArray);

    // select post process
    foreach (array_keys($items) as $id)
    {
        $items[$id]['isExternal'] = false;
        $items[$id]['originalIsImage'] = substr($items[$id]['originalMimeType'], 0, 6) == 'image/';
        $items[$id]['caption'] = empty($items[$id]['title']) ? $items[$id]['description'] : $items[$id]['title'];
        $items[$id]['captionLong'] = empty($items[$id]['description']) ? $items[$id]['title'] : $items[$id]['description'];

        mediashareAddKeywords($items[$id]);

        if ($enableEscape) {
            mediashareEscapeItem($items[$id], $id);
        }
    }

    return $items;
}

/**
 * Latest, random and more
 */
function mediashare_userapi_getLatestMediaItems($args)
{
    return pnModAPIFunc('mediashare', 'user', 'getList', array('order' => 'created', 'orderDir' => 'desc'));
}

function mediashare_userapi_getLatestAlbums($args)
{
    return pnModAPIFunc('mediashare', 'user', 'getAlbumList');
}

function mediashare_userapi_getRandomMediaItem($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $albumId = (isset($args['albumId']) ? (int)$args['albumId'] : null);
    $mode    = (isset($args['mode']) ? $args['mode'] : 'all');
    $latest  = (isset($args['latest']) ? $args['latest'] : false);

    $pntable = pnDBGetTables();

    $albumsTable  = $pntable['mediashare_albums'];
    $albumsColumn = $pntable['mediashare_albums_column'];
    $mediaTable   = $pntable['mediashare_media'];
    $mediaColumn  = $pntable['mediashare_media_column'];

    $accessibleAlbumSql = pnModAPIFunc('mediashare', 'user', 'getAccessibleAlbumsSql',
                                       array('access' => mediashareAccessRequirementView,
                                             'field'  => "album.$albumsColumn[id]"));
    if (!$accessibleAlbumSql) {
        return false;
    }

    if ($mode == 'latest') {
        $sql = "SELECT $albumsColumn[id]
                  FROM $albumsTable album
                 WHERE $accessibleAlbumSql
              ORDER BY $albumsColumn[createdDate] DESC";

        $result = DBUtil::executeSQL($sql, 0, 1);
        if ($result === false) {
            return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('userapi.getRandomMediaItem', 'Could not retrieve the random media item.'), $dom));
        }

        $albumId = DBUtil::marshallObjects($result, array('id'));
        $albumId = (int)$albumId[0]['id'];

        $accessibleAlbumSql = "album.$albumsColumn[id] = '$albumId'";
    }

    if ($mode == 'album' && !empty($albumId)) {
        $accessibleAlbumSql .= " AND album.$albumsColumn[id] = '$albumId'";
    }

    $sql = "SELECT COUNT(*)
              FROM $mediaTable media
              JOIN $albumsTable album
                ON album.$albumsColumn[id] = media.$mediaColumn[parentAlbumId]
             WHERE $accessibleAlbumSql";

    $result = DBUtil::executeSQL($sql);
    if ($result === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('userapi.getRandomMediaItem', 'Could not retrieve the random media item.'), $dom));
    }

    $count = DBUtil::marshallObjects($result, array('count'));
    $count = (int)$count[0]['count'];

    $sql = "SELECT media.$mediaColumn[id],
                   media.$mediaColumn[parentAlbumId]
              FROM $mediaTable media
              JOIN $albumsTable album
                ON album.$albumsColumn[id] = media.$mediaColumn[parentAlbumId]
             WHERE $accessibleAlbumSql";

    $result = DBUtil::executeSQL($sql, rand(0, $count - 1), 1);
    if ($result === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('userapi.getRandomMediaItem', 'Could not retrieve the random media item.'), $dom));
    }

    $media = DBUtil::marshallObjects($result, array('mediaId', 'albumId'));
    $media = $media[0];

    return $media;
}

/**
 * Escaping
 */
function mediashareEscapeAlbum(&$album, $albumId)
{
    $album['title'] = DataUtil::formatForDisplay($album['title']);
    // summary and item and description transform hooks
    $album['summary'] = isset($album['summary']) ? $album['summary'] : '';
    $album['description'] = isset($album['description']) ? $album['description'] : '';
    list ($album['summary'], $album['description']) = pnModCallHooks('item', 'transform', "album-$albumId",
                                                                     array(DataUtil::formatForDisplayHTML($album['summary']),
                                                                           DataUtil::formatForDisplayHTML($album['description'])));
}

function mediashareEscapeItem(&$item, $itemId)
{
    $item['title'] = DataUtil::formatForDisplay($item['title']);
    $item['caption'] = DataUtil::formatForDisplay($item['caption']);
    $item['captionLong'] = DataUtil::formatForDisplay($item['captionLong']);
    list ($item['description']) = pnModCallHooks('item', 'transform', "item-$itemId",
                                                 array(DataUtil::formatForDisplayHTML($item['description'])));
}

/**
 * Settings
 */
function mediashare_userapi_getSettings($args)
{
    $modvars = pnModGetVar('mediashare');
    $modvars['mediaSizeLimitSingle'] = (int)$modvars['mediaSizeLimitSingle']/1000;
    $modvars['mediaSizeLimitTotal'] = (int)$modvars['mediaSizeLimitTotal']/1000;
    
	return $modvars;
}

/**
 * Set the module vars
 * expect :tmpDirName, mediaDirName, thumbnailSize, previewSize
 *         mediaSizeLimitSingle, mediaSizeLimitTotal
 *         defaultAlbumTemplate, allowTemplateOverride
 *         enableSharpen, enableThumbnailStart, vfs
 *         flickrAPIKey, smugmugAPIKey, photobucketAPIKey, picasaAPIKey
 */
function mediashare_userapi_setSettings($args)
{
    $args['mediaSizeLimitSingle'] = (int)$args['mediaSizeLimitSingle'] * 1000;
    $args['mediaSizeLimitTotal'] = (int)$args['mediaSizeLimitTotal'] * 1000;
    pnModSetVars('mediashare', $args);
}

function mediashare_userapi_getRelativeMediadir()
{
    $zkroot    = substr(pnServerGetVar('DOCUMENT_ROOT'), 0, -1).pnGetBaseURI();
    $mediaBase = str_replace($zkroot, '', pnModGetVar('mediashare', 'mediaDirName', 'mediashare'));
    $mediaBase = substr($mediaBase, 1).'/';

    return $mediaBase;
}

/**
 * Most xxx
 */
function mediashare_userapi_getMostActiveUsers($args)
{
    list ($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    pnModDBInfoLoad('User'); // Ensure DB table info is available

    $mediaTable = $pntable['mediashare_media'];
    $mediaColumn = $pntable['mediashare_media_column'];
    $usersTable = $pntable['users'];
    $usersColumn = $pntable['users_column'];

    $sql = "SELECT $usersColumn[uname],
                   COUNT(*) cou
              FROM $mediaTable
         LEFT JOIN $usersTable
                ON $usersColumn[uid] = $mediaColumn[ownerId]
          GROUP BY $usersColumn[uname]
          ORDER BY cou DESC";

    $dbresult = $dbconn->selectLimit($sql, 10, 0);

    if ($dbconn->errorNo() != 0) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('userapi.mostActiveUsers', 'Could not retrieve the most active users.'), $dom));
    }

    $result = array();
    for (; !$dbresult->EOF; $dbresult->MoveNext()) {
        $result[] = array('uname' => $dbresult->fields[0], 'count' => $dbresult->fields[1]);
    }

    $dbresult->close();

    return $result;
}

function mediashare_userapi_getMostActiveKeywords($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    list ($dbconn) = pnDBGetConn();
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

    if ($dbconn->errorNo() != 0) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('userapi.mostActiveKeywords', 'Could not retrieve the most active keywords.'), $dom));
    }

    $result = array();
    $max = -1;
    $min = -1;
    $total = 0;
    for (; !$dbresult->EOF; $dbresult->MoveNext()) {
        $keyword = array('keyword' => $dbresult->fields[0],
                         'count'   => (int)$dbresult->fields[1]);

        if ($keyword['count'] > $max) {
            $max = $keyword['count'];
        }

        if ($keyword['count'] < $min || $min == -1) {
            $min = $keyword['count'];
        }

        $total += (int)$keyword['count'];

        $result[] = $keyword;
    }

    $dbresult->close();

    $max -= $min;

    // equal values case
    if ($max == 0) {
        $max = 1;
        $min = 1 - 1/$total;
    }

    foreach (array_keys($result) as $i) {
        $result[$i]['percentage'] = (int)(($result[$i]['count'] - $min) * 100 / $max);
        $result[$i]['fontsize']   = $result[$i]['percentage'] + 100;
    }

    return $result;
}

function mediashare_userapi_getSummary($args)
{
    list ($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $mediaTable   = $pntable['mediashare_media'];
    $mediaColumn  = $pntable['mediashare_media_column'];
    $albumsTable  = $pntable['mediashare_albums'];
    $albumsColumn = $pntable['mediashare_albums_column'];

    // Find accessible albums (media count)
    $accessibleAlbumSql = pnModAPIFunc('mediashare', 'user', 'getAccessibleAlbumsSql',
                                       array('access' => mediashareAccessRequirementViewSomething,
                                             'field'  => "$mediaColumn[parentAlbumId]"));
    if (!$accessibleAlbumSql) {
        return false;
    }

    $summary = array();

    $sql = "SELECT COUNT(*)
              FROM $mediaTable
             WHERE $accessibleAlbumSql";

    $dbresult = $dbconn->execute($sql);

    if ($dbconn->errorNo() != 0) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('userapi.getSummary', 'Could not count the media table.'), $dom));
    }

    $summary['mediaCount'] = (int)$dbresult->fields[0];

    // Find accessible albums (album count)
    $accessibleAlbumSql = pnModAPIFunc('mediashare', 'user', 'getAccessibleAlbumsSql',
                                       array('access' => mediashareAccessRequirementViewSomething,
                                             'field'  => "$albumsColumn[id]"));
    if (!$accessibleAlbumSql) {
        return false;
    }

    $sql = "SELECT COUNT(*)
              FROM $albumsTable
             WHERE $accessibleAlbumSql";

    $dbresult = $dbconn->execute($sql);

    if ($dbconn->errorNo() != 0) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('userapi.getSummary', 'Could not count the albums table.'), $dom));
    }

    $summary['albumCount'] = (int)$dbresult->fields[0];

    return $summary;
}

/**
 * Keywords
 */
function mediashareAddKeywords(&$item)
{
    $k = trim(mediashareStripKeywords($item['keywords']));
    if (strlen($k) > 0) {
        $item['keywordsArray'] = preg_split("/[\s,]+/", $k);
        $item['hasKeywords'] = true;
    } else {
        $item['keywordsArray'] = array();
        $item['hasKeywords'] = false;
    }
}

function mediashare_userapi_getByKeyword($args)
{
    $keyword = $args['keyword'];

    list ($dbconn) = pnDBGetConn();
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
                                       array('access' => mediashareAccessRequirementViewSomething,
                                             'field'  => "media.$mediaColumn[parentAlbumId]"));
    if (!$accessibleAlbumSql) {
        return false;
    }

    $sql = "   SELECT album.$albumsColumn[id],
                      album.$albumsColumn[title],
                      media.$mediaColumn[id],
                      media.$mediaColumn[title],
                      media.$mediaColumn[description],
                      media.$mediaColumn[mediaHandler],
                      thumbnail.$storageColumn[fileRef]
                 FROM $keywordsTable keyword
           INNER JOIN $mediaTable media
                   ON media.$mediaColumn[id] = keyword.$keywordsColumn[itemId]
                  AND keyword.$keywordsColumn[type] = 'media'
           INNER JOIN $albumsTable album
                   ON album.$albumsColumn[id] = media.$mediaColumn[parentAlbumId]
           INNER JOIN $storageTable thumbnail
                   ON thumbnail.$storageColumn[id] = $mediaColumn[thumbnailId]
                WHERE ($accessibleAlbumSql)
                  AND keyword.$keywordsColumn[keyword] = '" . DataUtil::formatForStore($keyword) . "'

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
                   ON album.$albumsColumn[id] = keyword.$keywordsColumn[itemId]
                  AND keyword.$keywordsColumn[type] = 'album'
           INNER JOIN $mediaTable media
                   ON media.$mediaColumn[id] = album.$albumsColumn[mainMediaId]
           INNER JOIN $storageTable thumbnail
                   ON thumbnail.$storageColumn[id] = $mediaColumn[thumbnailId]
                WHERE ($accessibleAlbumSql)
                  AND keyword.$keywordsColumn[keyword] = '" . DataUtil::formatForStore($keyword) . "'";
           //ORDER BY album.$albumsColumn[title], media.$mediaColumn[title]";

    $dbresult = $dbconn->execute($sql);

    if ($dbconn->errorNo() != 0) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('userapi.getByKeyword', 'Could not retrieve the search results.'), $dom));
    }

    $result = array();
    for (; !$dbresult->EOF; $dbresult->MoveNext()) {
        $result[] = array(
            'albumId'      => $dbresult->fields[0],
            'albumTitle'   => $dbresult->fields[1],
            'mediaId'      => $dbresult->fields[2],
            'mediaTitle'   => $dbresult->fields[3],
            'caption'      => empty($dbresult->fields[3]) ? $dbresult->fields[4] : $dbresult->fields[3],
            'captionLong'  => empty($dbresult->fields[4]) ? $dbresult->fields[3] : $dbresult->fields[4],
            'mediaHandler' => $dbresult->fields[5],
            'thumbnailRef' => $dbresult->fields[6]);
    }

    $dbresult->close();

    return $result;
}

/**
 * Lists
 */
function mediashare_userapi_getList($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $keyword   = isset($args['keyword']) ? $args['keyword'] : null;
    $uname     = isset($args['uname']) ? $args['uname'] : null;
    $albumId   = isset($args['albumId']) ? $args['albumId'] : null;
    $order     = isset($args['order']) ? $args['order'] : null;
    $orderDir  = isset($args['orderDir']) ? $args['orderDir'] : 'asc';
    $recordPos = isset($args['recordPos']) ? (int)$args['recordPos'] : 0;
    $pageSize  = isset($args['pageSize']) ? (int)$args['pageSize'] : 5;

    list ($dbconn) = pnDBGetConn();
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
                                       array('access' => mediashareAccessRequirementViewSomething,
                                             'field'  => "media.$mediaColumn[parentAlbumId]"));
    if (!$accessibleAlbumSql) {
        return false;
    }

    // Build simple restriction
    $restriction = array();
    $join = array();

    if ($uname != null) {
        $restriction[] = "$usersColumn[uname] = '" . DataUtil::formatForStore($uname) . "'";
        //$join[]      = "INNER JOIN $usersTable ON $usersColumn[uid] = media.$mediaColumn[ownerId]";
    }

    if ($albumId != null) {
        $restriction[] = "album.$albumsColumn[id] = " . (int)$albumId;
    }

    $orderKey = 'title';
    if (in_array($order, array('uname', 'created', 'modified'))) {
        $orderKey = $order;
    }

    $orderDir = ($orderDir == 'desc' ? 'desc' : 'asc');

    $restrictionSql = (count($restriction) > 0 ? ' AND ' . implode(' AND ', $restriction) : '');
    $joinSql        = (count($join) > 0 ? implode(' ', $join) : '');

    if ($keyword != null) {
        $sql = "(
                   SELECT album.$albumsColumn[id],
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
                       ON media.$mediaColumn[id] = keyword.$keywordsColumn[itemId]
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
                    WHERE ($accessibleAlbumSql)
                      AND keyword.$keywordsColumn[keyword] = '" . DataUtil::formatForStore($keyword) . "'
                          $restrictionSql
              )

              UNION

              (
                   SELECT album.$albumsColumn[id],
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
                       ON album.$albumsColumn[id] = keyword.$keywordsColumn[itemId]
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
                    WHERE ($accessibleAlbumSql)
                      AND keyword.$keywordsColumn[keyword] = '" . DataUtil::formatForStore($keyword) . "'
                          $restrictionSql
               )
               ORDER BY $orderKey $orderDir";

    } else {
        $sql = "   SELECT album.$albumsColumn[id],
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

    $dbresult = $dbconn->selectLimit($sql, $pageSize, $recordPos);

    if ($dbconn->errorNo() != 0) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('userapi.getList', 'Could not retrieve the media list.'), $dom));
    }

    $result = array();
    for (; !$dbresult->EOF; $dbresult->MoveNext()) {
        $album = array('id' => $dbresult->fields[0], 'title' => $dbresult->fields[1], 'keywords' => $dbresult->fields[2]);

        mediashareAddKeywords($album);

        $media = array(
            'id' => $dbresult->fields[3],
            'ownerId' => $dbresult->fields[4],
            'ownerName' => $dbresult->fields[5],
            'createdDate' => $dbresult->fields[6],
            'modifiedDate' => $dbresult->fields[7],
            'createdDateRaw' => $dbresult->fields[6],
            'modifiedDateRaw' => $dbresult->fields[7],
            'title' => $dbresult->fields[8],
            'keywords' => $dbresult->fields[9],
            'description' => $dbresult->fields[10],
            'caption' => empty($dbresult->fields[8]) ? $dbresult->fields[10] : $dbresult->fields[8],
            'captionLong' => empty($dbresult->fields[10]) ? $dbresult->fields[8] : $dbresult->fields[10],
            'mediaHandler' => $dbresult->fields[11],
            'thumbnailRef' => $dbresult->fields[13],
            'previewRef' => $dbresult->fields[14],
            'previewMimeType' => $dbresult->fields[15],
            'previewWidth' => $dbresult->fields[16],
            'previewHeight' => $dbresult->fields[17],
            'previewBytes' => $dbresult->fields[18],
            'originalRef' => $dbresult->fields[19],
            'originalMimeType' => $dbresult->fields[20],
            'originalWidth' => $dbresult->fields[21],
            'originalHeight' => $dbresult->fields[22],
            'originalBytes' => $dbresult->fields[23],
            'originalIsImage' => substr($dbresult->fields[20], 0, 6) == 'image/');

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
    $keyword = isset($args['keyword']) ? $args['keyword'] : null;
    $uname = isset($args['uname']) ? $args['uname'] : null;
    $albumId = $args['albumId'];

    list ($dbconn) = pnDBGetConn();
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
                                       array('access' => mediashareAccessRequirementViewSomething,
                                             'field'  => "media.$mediaColumn[parentAlbumId]"));
    if (!$accessibleAlbumSql) {
        return false;
    }

    // Build simple restriction
    $restriction = array();
    $join = array();

    if ($uname != null) {
        $restriction[] = "$usersColumn[uname] = '" . DataUtil::formatForStore($uname) . "'";
        //$join[] = "INNER JOIN $usersTable ON $usersColumn[uid] = media.$mediaColumn[ownerId]";
    }

    if ($albumId != null) {
        $restriction[] = "album.$albumsColumn[id] = " . (int)$albumId;
    }

    $restrictionSql = (count($restriction) > 0 ? ' AND ' . implode(' AND ', $restriction) : '');
    $joinSql = (count($join) > 0 ? implode(' ', $join) : '');

    if ($keyword != null) {
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
            WHERE ($accessibleAlbumSql) AND keyword.$keywordsColumn[keyword] = '" . DataUtil::formatForStore($keyword) . "'
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
             WHERE ($accessibleAlbumSql) AND keyword.$keywordsColumn[keyword] = '" . DataUtil::formatForStore($keyword) . "'
                   $restrictionSql";
    } else {
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
    if ($dbconn->errorNo() != 0) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('userapi.getListCount', 'Could not retrieve the list count.'), $dom));
    }

    $count = (int)$dbresult->fields[0];

    $dbresult->close();

    if ($sql2 != null) {
        $dbresult = $dbconn->execute($sql2);
        if ($dbconn->errorNo() != 0) {
            return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('userapi.getListCount', 'Could not retrieve the list count.'), $dom));
        }

        $count += (int)$dbresult->fields[0];

        $dbresult->close();
    }

    return $count;
}

/**
 * Searching
 */
function mediashare_userapi_search($args)
{
    $query = $args['query'];
    $match = $args['match'];
    $itemIndex = (int)$args['itemIndex'];
    $pageSize = (int)$args['pageSize'];

    list ($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $mediaTable   = $pntable['mediashare_media'];
    $mediaColumn  = $pntable['mediashare_media_column'];
    $albumsTable  = $pntable['mediashare_albums'];
    $albumsColumn = $pntable['mediashare_albums_column'];

    // Split query by whitespace allowing use of quotes "..."
    $words = array();
    $count = preg_match_all('/"[^"]+"|[^" ]+/', $query, $words);
    $words = $words[0];

    for ($i = 0; $i < $count; ++$i) {
        if ($words[$i][0] == '"') {
            $words[$i] = substr($words[$i], 1, strlen($words[$i]) - 2);
        }
    }

    // Combine keywords to SQL restriction
    $restriction = '';
    foreach ($words as $word) {
        if ($restriction != '') {
            $restriction .= ($match == 'AND' ? ' AND ' : ' OR ');
        }
        $restriction .= "(media.$mediaColumn[title] LIKE '%" . DataUtil::formatForStore($word) . "%' OR " . "media.$mediaColumn[description] LIKE '%" . DataUtil::formatForStore($word) . "%' OR " . "media.$mediaColumn[keywords] LIKE '%" . DataUtil::formatForStore($word) . "%')";
    }

    // Find accessible albums
    $accessibleAlbumSql = pnModAPIFunc('mediashare', 'user', 'getAccessibleAlbumsSql',
                                       array('access' => mediashareAccessRequirementViewSomething,
                                             'field'  => "album.$albumsColumn[id]"));
    if (!$accessibleAlbumSql) {
        return false;
    }

    $sql = "SELECT album.$albumsColumn[id],
                   album.$albumsColumn[title],
                   media.$mediaColumn[id],
                   media.$mediaColumn[title]
                   media.$mediaColumn[description]
              FROM $albumsTable album
         LEFT JOIN $mediaTable media
                ON media.$mediaColumn[parentAlbumId] = album.$albumsColumn[id]
             WHERE ($accessibleAlbumSql) AND $restriction
          ORDER BY album.$albumsColumn[title], media.$mediaColumn[title]";

    $dbresult = $dbconn->execute($sql);

    if ($dbconn->errorNo() != 0) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('userapi.search', 'Could not retrieve the search results.'), $dom));
    }

    $result = array();
    $i = 0;
    $rowStart = $itemIndex;
    $rowEnd = $itemIndex + $pageSize;
    for (; !$dbresult->EOF; $dbresult->MoveNext()) {
        if ($i >= $rowStart && $i < $rowEnd) {
            $result[] = array(
                'albumId' => $dbresult->fields[0],
                'albumTitle' => $dbresult->fields[1],
                'mediaId' => $dbresult->fields[2],
                'mediaTitle' => $dbresult->fields[3],
                'mediaCaption' => empty($result->fields[3]) ? $result->fields[4] : $result->fields[3],
                'mediaCaptionLong' => empty($result->fields[4]) ? $result->fields[3] : $result->fields[4]
            );
        }
        ++$i;
    }

    $dbresult->close();

    return array('result' => $result, 'hitCount' => $i);
}

/**
 * Templates
 */
function mediashare_userapi_getAllTemplates($args)
{
    $templates = array();

    $sets = FileUtil::getFiles('modules/mediashare/pntemplates/Frontend', false, true, null, 'd');

    if (file_exists('config/templates/mediashare/Frontend')) {
        $add = FileUtil::getFiles('config/templates/mediashare/Frontend', false, true, null, 'd');
        $sets = array_merge($sets, $add);
    }

    foreach ($sets as $set) {
        $templates[] = array('title' => $set,
                             'value' => $set);
    }

    return $templates;
}
