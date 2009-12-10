<?php
// $Id$
//
// Mediashare by Jorn Lind-Nielsen (C)
//

require_once 'modules/mediashare/common.php';

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
        $mainMedia   = null;
        foreach (array_keys($images) as $i)
        {
            if ($images[$i]['id'] == $mainMediaId) {
                $mainMedia = $images[$i];
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
    if (!$albumData = mediashare_userapi_getAlbumData($args)) {
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
    if (!isset($args['albumId']) || !is_numeric($args['albumId'])) {
        return LogUtil::registerError(__f('Missing [%1$s] in \'%2$s\'', array('albumId', 'userapi.getAlbumData'), $dom));
    }

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
        return LogUtil::registerError(__f('Missing [%1$s] in \'%2$s\'', array('albumId', 'userapi.getSubAlbumsData'), $dom));
    }

    $albumId         = (int)$args['albumId'];
    $startnum        = isset($args['startnum']) ? (int)$args['startnum'] : -1;
    $numitems        = isset($args['numitems']) ? (int)$args['numitems'] : -1;
    $recursively     = isset($args['recursively']) ? (bool)$args['recursively'] : false;
    $access          = isset($args['access']) ? (int)$args['access'] : 0xFF;
    $excludeAlbumId  = isset($args['excludeAlbumId']) ? (int)$args['excludeAlbumId'] : null;
    $onlyMine        = isset($args['onlyMine']) ? $args['onlyMine'] : false;
    $includeMainItem = isset($args['includeMainItem']) ? (bool)$args['includeMainItem'] : true; // FIXME rework this to default false

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
        $subalbums[$k]['mainMediaItem'] = null;
        if ($includeMainItem && (int)$subalbums[$k]['mainMediaId'] > 0) {
            $subalbums[$k]['mainMediaItem'] = pnModAPIFunc('mediashare', 'user', 'getMediaItem', array('mediaId' => $subalbums[$k]['mainMediaId']));
        }

        $subalbums[$k]['extappData'] = unserialize($subalbums[$k]['extappData']);

        mediashareAddKeywords($subalbums[$k]);
    }

    return $subalbums;
}

function mediashare_userapi_getAlbumBreadcrumb($args)
{
    // Argument check
    if (!isset($args['albumId'])) {
        return LogUtil::registerError(__f('Missing [%1$s] in \'%2$s\'', array('albumId', 'userapi.getAlbumBreadcrumb'), $dom));
    }
    $albumId = (int)$args['albumId'];

    $pntable = &pnDBGetTables();

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

    if ($result == false) {
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
        $albums[$aid]['mainMediaItem'] = null;
        if ($includeMainItem && (int)$albums[$aid]['mainMediaId'] > 0) {
            $albums[$aid]['mainMediaItem'] = pnModAPIFunc('mediashare', 'user', 'getMediaItem', array('mediaId' => $albums[$aid]['mainMediaId']));
        }

        $albums[$aid]['extappData'] = unserialize($albums[$aid]['extappData']);

        mediashareAddKeywords($albums[$aid]);
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
        return LogUtil::registerError(__f('Missing [%1$s] in \'%2$s\'', array('albumId', 'userapi.getFirstItemIdInAlbum'), $dom));
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
        return LogUtil::registerError(__f('Missing [%1$s] in \'%2$s\'', array('mediaId', 'userapi.getMediaItem'), $dom));
    }

    $mediaId = (int)$args['mediaId'];

    $pntable = &pnDBGetTables();

    $mediaTable    = $pntable['mediashare_media'];
    $mediaColumn   = $pntable['mediashare_media_column'];
    $storageTable  = $pntable['mediashare_mediastore'];
    $storageColumn = $pntable['mediashare_mediastore_column'];

    $sql = "SELECT $mediaColumn[id],
                   $mediaColumn[ownerId],
                   $mediaColumn[createdDate],
                   $mediaColumn[modifiedDate],
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
    if (!$result) {
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
        return LogUtil::registerError(__f('Missing [%1$s] in \'%2$s\'', array('mediaId / mediaItem', 'userapi.getMediaUrl'), $dom));
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
        return LogUtil::registerError(__f('Missing [%1$s] in \'%2$s\'', array('albumId / mediaIdList', 'userapi.getMediaItems'), $dom));
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
    $access       = isset($args['access']) ? $args['access'] : mediashareAccessRequirementView;
    $startnum     = isset($args['startnum']) ? (int)$args['startnum'] : -1;
    $numitems     = isset($args['numitems']) ? (int)$args['numitems'] : -1;

    pnModDBInfoLoad('User'); // Ensure DB table info is available

    $pntable = &pnDBGetTables();

    $mediaTable    = $pntable['mediashare_media'];
    $mediaColumn   = $pntable['mediashare_media_column'];
    $storageTable  = $pntable['mediashare_mediastore'];
    $storageColumn = $pntable['mediashare_mediastore_column'];

    if (!empty($albumId)) {
        $albumRestriction = "$mediaColumn[parentAlbumId] = '$albumId'";

    } else {
        $albumRestriction = array();

        if (!empty($mediaIdList)) {
            foreach (array_keys($mediaIdList) as $i) {
                $mediaIdList[$i] = (int)$mediaIdList[$i];
            }
            $albumRestriction[] = "$mediaColumn[id] IN ('" . implode("','", $mediaIdList) . "')";
        }

        $accessibleAlbumSql = pnModAPIFunc('mediashare', 'user', 'getAccessibleAlbumsSql',
                                           array('access' => $access,
                                                 'field'  => "$mediaColumn[parentAlbumId]"));
        if (!$accessibleAlbumSql) {
            return false;
        }
        $albumRestriction[] = $accessibleAlbumSql;

        $albumRestriction = implode(' AND ', $albumRestriction);
    }

    $sql = "SELECT $mediaColumn[id],
                   $mediaColumn[ownerId],
                   $mediaColumn[createdDate],
                   $mediaColumn[modifiedDate],
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
                      'originalRef', 'originalMimeType', 'originalWidth', 'originalHeight', 'originalBytes');

    $items = DBUtil::marshallObjects($result, $colArray);

    // select post process
    foreach (array_keys($items) as $id)
    {
        $items[$id]['isExternal'] = false;
        $items[$id]['originalIsImage'] = substr($items[$id]['originalMimeType'], 0, 6) == 'image/';
        $items[$id]['caption'] = empty($items[$id]['title']) ? $items[$id]['description'] : $items[$id]['title'];
        $items[$id]['captionLong'] = empty($items[$id]['description']) ? $items[$id]['title'] : $items[$id]['description'];

        mediashareAddKeywords($items[$id]);
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

    $pntable = &pnDBGetTables();

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
 * Settings
 */
function mediashare_userapi_getSettings($args)
{
    $modvars = pnModGetVar('mediashare');
    $modvars['mediaSizeLimitSingle'] = (int)$modvars['mediaSizeLimitSingle']/1000;
    $modvars['mediaSizeLimitTotal']  = (int)$modvars['mediaSizeLimitTotal']/1000;
    
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
    $args['mediaSizeLimitTotal']  = (int)$args['mediaSizeLimitTotal'] * 1000;

    return pnModSetVars('mediashare', $args);
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
    pnModDBInfoLoad('User'); // Ensure DB table info is available

    $pntable = &pnDBGetTables();

    $mediaTable  = $pntable['mediashare_media'];
    $mediaColumn = $pntable['mediashare_media_column'];
    $usersTable  = $pntable['users'];
    $usersColumn = $pntable['users_column'];

    $sql = "SELECT $usersColumn[uname],
                   COUNT(*) cou
              FROM $mediaTable
         LEFT JOIN $usersTable
                ON $usersColumn[uid] = $mediaColumn[ownerId]
          GROUP BY $usersColumn[uname]
          ORDER BY cou DESC";

    $result = DBUtil::executeSQL($sql, 0, 10);

    if ($result === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('userapi.mostActiveUsers', 'Could not retrieve the most active users.'), $dom));
    }

    // FIXME uname as index, count as values?
    return DBUtil::marshallObjects($result, array('uname', 'count'));
}

function mediashare_userapi_getMostActiveKeywords($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    pnModDBInfoLoad('User'); // Ensure DB table info is available

    $pntable = &pnDBGetTables();

    $usersTable     = $pntable['users'];
    $usersColumn    = $pntable['users_column'];
    $keywordsTable  = $pntable['mediashare_keywords'];
    $keywordsColumn = $pntable['mediashare_keywords_column'];

    $sql = "SELECT $keywordsColumn[keyword],
                   COUNT(*) cou
              FROM $keywordsTable
          GROUP BY $keywordsColumn[keyword]
          ORDER BY $keywordsColumn[keyword]";

    $result = DBUtil::executeSQL($sql);

    if ($result === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('userapi.mostActiveKeywords', 'Could not retrieve the most active keywords.'), $dom));
    }

    // FIXME keyword as index, count as values?
    $keywords = DBUtil::marshallObjects($result, array('keyword', 'count'));

    // counters calculation
    $max = -1;
    $min = -1;
    $total = 0;
    foreach ($keywords as $keyword)
    {
        if ($keyword['count'] > $max) {
            $max = $keyword['count'];
        }

        if ($keyword['count'] < $min || $min == -1) {
            $min = $keyword['count'];
        }

        $total += (int)$keyword['count'];
    }

    $max -= $min;

    // equal values case
    if ($max == 0) {
        $max = 1;
        $min = 1 - 1/$total;
    }

    foreach (array_keys($keywords) as $i)
    {
        $keywords[$i]['percentage'] = (int)(($keywords[$i]['count'] - $min) * 100 / $max);
        $keywords[$i]['fontsize']   = $keywords[$i]['percentage'] + 100;
    }

    return $keywords;
}

function mediashare_userapi_getSummary($args)
{
    $pntable = &pnDBGetTables();

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

    $result = DBUtil::executeSQL($sql);

    if ($result === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('userapi.getSummary', 'Could not count the media table.'), $dom));
    }

    $count = DBUtil::marshallObjects($result, array('count'));
    $summary['mediaCount'] = (int)$count[0]['count'];

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

    $result = DBUtil::executeSQL($sql);

    if ($result === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('userapi.getSummary', 'Could not count the albums table.'), $dom));
    }

    $count = DBUtil::marshallObjects($result, array('count'));
    $summary['albumCount'] = (int)$count[0]['count'];

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
        $item['hasKeywords']   = true;
    } else {
        $item['keywordsArray'] = array();
        $item['hasKeywords']   = false;
    }
}

function mediashare_userapi_getByKeyword($args)
{
    $keyword = $args['keyword'];

    $pntable = &pnDBGetTables();

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

    $result = DBUtil::executeSQL($sql);

    if ($result == false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('userapi.getByKeyword', 'Could not retrieve the search results.'), $dom));
    }

    $colArray = array('albumId', 'albumTitle', 'mediaId', 'mediaTitle', 'captionLong', 'mediaHandler', 'thumbnailRef');

    $media = DBUtil::marshallObjects($result, $colArray);

    foreach (array_keys($media) as $k)
    {
        $media[$k]['caption'] = empty($media[$k]['mediaTitle']) ? $media[$k]['captionLong'] : $media[$k]['mediaTitle'];
        $media[$k]['captionLong'] = empty($media[$k]['captionLong']) ? $media[$k]['mediaTitle'] : $media[$k]['captionLong'];
    }

    return $media;
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

    pnModDBInfoLoad('User'); // Ensure DB table info is available

    $pntable = &pnDBGetTables();

    $mediaTable     = $pntable['mediashare_media'];
    $mediaColumn    = $pntable['mediashare_media_column'];
    $keywordsTable  = $pntable['mediashare_keywords'];
    $keywordsColumn = $pntable['mediashare_keywords_column'];
    $albumsTable    = $pntable['mediashare_albums'];
    $albumsColumn   = $pntable['mediashare_albums_column'];
    $storageTable   = $pntable['mediashare_mediastore'];
    $storageColumn  = $pntable['mediashare_mediastore_column'];
    $usersTable     = $pntable['users'];
    $usersColumn    = $pntable['users_column'];

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

    if (!empty($uname)) {
        $restriction[] = "$usersColumn[uname] = '" . DataUtil::formatForStore($uname) . "'";
        $join[] = "INNER JOIN $usersTable
                           ON $usersColumn[uid] = media.$mediaColumn[ownerId]";
    }

    if (!empty($albumId)) {
        $restriction[] = "album.$albumsColumn[id] = '". (int)$albumId ."'";
    }

    $orderKey = 'title';
    if (in_array($order, array('uname', 'created', 'modified'))) {
        $orderKey = $order;
    }

    $orderDir = strtolower($orderDir) == 'desc' ? 'DESC' : 'ASC';

    $restrictionSql = count($restriction) > 0 ? ' AND ' . implode(' AND ', $restriction) : '';
    $joinSql        = count($join) > 0 ? implode(' ', $join) : '';

    if (!empty($keyword)) {
        $sql = "
                   SELECT album.$albumsColumn[id],
                          album.$albumsColumn[title],
                          album.$albumsColumn[keywords],
                          media.$mediaColumn[id],
                          media.$mediaColumn[ownerId],
                          media.$mediaColumn[createdDate] AS created,
                          media.$mediaColumn[modifiedDate] AS modified,
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
                          $joinSql
                    WHERE ($accessibleAlbumSql)
                      AND keyword.$keywordsColumn[keyword] = '" . DataUtil::formatForStore($keyword) . "'
                          $restrictionSql

              UNION

              (
                   SELECT album.$albumsColumn[id],
                          album.$albumsColumn[title],
                          album.$albumsColumn[keywords],
                          media.$mediaColumn[id],
                          media.$mediaColumn[ownerId],
                          media.$mediaColumn[createdDate],
                          media.$mediaColumn[modifiedDate],
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
                          media.$mediaColumn[createdDate] AS created,
                          media.$mediaColumn[modifiedDate] AS modified,
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
                          $joinSql
                    WHERE ($accessibleAlbumSql)
                          $restrictionSql
                 ORDER BY $orderKey $orderDir";
    }

    $result = DBUtil::executeSQL($sql, $recordPos, $pageSize);

    if ($result === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('userapi.getList', 'Could not retrieve the media list.'), $dom));
    }

    $colArray = array('albumId', 'albumTitle', 'albumKeywords',
                      'id', 'ownerId', 'createdDate', 'modifiedDate',
                      'title', 'keywords', 'description', 'mediaHandler',
                      'position', 'thumbnailRef',
                      'previewRef', 'previewMimeType', 'previewWidth', 'previewHeight', 'previewBytes',
                      'originalRef', 'originalMimeType', 'originalWidth', 'originalHeight', 'originalBytes');

    $media = DBUtil::marshallObjects($result, $colArray);

    $result = array();
    foreach (array_keys($media) as $k)
    {
        // build the album
        $album = array('id' => $media[$k]['albumId'],
                       'title' => $media[$k]['albumTitle'],
                       'keywords' => $media[$k]['albumKeywords']);

        mediashareAddKeywords($album);

        // remove the album data
        unset($media[$k]['albumId']);
        unset($media[$k]['albumTitle']);
        unset($media[$k]['albumKeywords']);

        // media data post process
        unset($media[$k]['position']);
        $media[$k]['caption'] = empty($media[$k]['title']) ? $media[$k]['description'] : $media[$k]['title'];
        $media[$k]['captionLong'] = empty($media[$k]['description']) ? $media[$k]['title'] : $media[$k]['description'];
        $media[$k]['originalIsImage'] = substr($media[$k]['originalMimeType'], 0, 6) == 'image/';

        mediashareAddKeywords($media[$k]);

        $result[] = array('album' => $album,
                          'media' => $media[$k]);
    }

    return $result;
}

function mediashare_userapi_getListCount($args)
{
    $keyword = isset($args['keyword']) ? $args['keyword'] : null;
    $uname = isset($args['uname']) ? $args['uname'] : null;
    $albumId = $args['albumId'];

    pnModDBInfoLoad('User'); // Ensure DB table info is available

    $pntable = &pnDBGetTables();

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
        $join[] = "INNER JOIN $usersTable
                           ON $usersColumn[uid] = media.$mediaColumn[ownerId]";
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
                    ON media.$mediaColumn[id] = keyword.$keywordsColumn[itemId]
                   AND keyword.$keywordsColumn[type] = 'media'
            INNER JOIN $usersTable
                    ON $usersColumn[uid] = media.$mediaColumn[ownerId]
            INNER JOIN $albumsTable album
                    ON album.$albumsColumn[id] = media.$mediaColumn[parentAlbumId]
                       $joinSql
                 WHERE ($accessibleAlbumSql)
                   AND keyword.$keywordsColumn[keyword] = '" . DataUtil::formatForStore($keyword) . "'
                       $restrictionSql";

        $sql2 = "SELECT COUNT(*)
                   FROM $keywordsTable keyword
             INNER JOIN $albumsTable album
                     ON album.$albumsColumn[id] = keyword.$keywordsColumn[itemId]
                    AND keyword.$keywordsColumn[type] = 'album'
             INNER JOIN $mediaTable media
                     ON media.$mediaColumn[id] = album.$albumsColumn[mainMediaId]
             INNER JOIN $usersTable
                     ON $usersColumn[uid] = media.$mediaColumn[ownerId]
                        $joinSql
                  WHERE ($accessibleAlbumSql)
                    AND keyword.$keywordsColumn[keyword] = '" . DataUtil::formatForStore($keyword) . "'
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

    $result = DBUtil::executeSQL($sql);

    if ($result === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('userapi.getListCount', 'Could not retrieve the list count.'), $dom));
    }

    $result = DBUtil::marshallObjects($result, array('count'));
    $count  = (int)$result[0]['count'];

    if ($sql2 != null) {
        $result = DBUtil::executeSQL($sql2);
        if ($result === false) {
            return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('userapi.getListCount', 'Could not retrieve the second list count.'), $dom));
        }

        $result = DBUtil::marshallObjects($result, array('count'));
        $count += (int)$result[0]['count'];
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

    $pntable = &pnDBGetTables();

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
    $restriction = array();
    foreach ($words as $word)
    {
        $word = DataUtil::formatForStore($word);
        $restriction[] = "  (media.$mediaColumn[title] LIKE '%$word%'
                          OR media.$mediaColumn[description] LIKE '%$word%'
                          OR media.$mediaColumn[keywords] LIKE '%$word%')";
    }
    $restriction = implode(($match == 'AND' ? ' AND ' : ' OR '), $restriction);

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

    $result = DBUtil::executeSQL($sql, $itemIndex, $pageSize);

    if ($result === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('userapi.search', 'Could not retrieve the search results.'), $dom));
    }

    $colArray = array('albumId', 'albumTitle', 'mediaId', 'mediaTitle', 'mediaCaptionLong');

    $items = DBUtil::marshallObjects($result, $colArray);

    // select post process
    foreach (array_keys($items) as $k)
    {
        $items[$k]['mediaCaption'] = empty($items[$k]['mediaTitle']) ? $items[$k]['mediaCaptionLong'] : $items[$k]['mediaTitle'];
        $items[$k]['mediaCaptionLong'] = empty($items[$k]['mediaCaptionLong']) ? $items[$k]['mediaTitle'] : $items[$k]['mediaCaptionLong'];
    }

    return array('result' => $result, 'hitCount' => -1); // FIXME implement or deprecate the count
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
