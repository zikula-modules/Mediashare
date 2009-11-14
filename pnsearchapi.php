<?php
// $Id$
// =======================================================================
// Mediashare by Jorn Wildt (C) 2005.
// =======================================================================


require_once 'modules/mediashare/common.php';

/**
 * Search plugin info
 **/
function mediashare_searchapi_info()
{
    return array('title' => 'mediashare', 'functions' => array('Media files' => 'search'));
}

/**
 * Search form component
 **/
function mediashare_searchapi_options($args)
{
    if (SecurityUtil::checkPermission('mediashare::', '::', ACCESS_READ)) {
        $pnRender = pnRender::getInstance('mediashare');
        $pnRender->assign('active', (isset($args['active']) && isset($args['active']['mediashare'])) || (!isset($args['active'])));
        return $pnRender->fetch('mediashare_search_options.html');
    }

    return '';
}

function mediashare_searchapi_search($args)
{
    pnModDBInfoLoad('mediashare');
    pnModDBInfoLoad('Search');
    $dbconn = pnDBGetConn(true);
    $pntable = pnDBGetTables();

    $mediaTable = $pntable['mediashare_media'];
    $mediaColumn = $pntable['mediashare_media_column'];
    $albumsTable = $pntable['mediashare_albums'];
    $albumsColumn = $pntable['mediashare_albums_column'];
    $searchTable = &$pntable['search_result'];
    $searchColumn = &$pntable['search_result_column'];

    $sessionId = session_id();

    // Find accessible albums
    $accessibleAlbumSql = pnModAPIFunc('mediashare', 'user', 'getAccessibleAlbumsSql', array('access' => mediashareAccessRequirementViewSomething, 'field' => "media.$mediaColumn[parentAlbumId]"));

    $albumText = __('Multimedia file in album: ', $dom);

    $sql = "
INSERT INTO $searchTable
  ($searchColumn[title],
   $searchColumn[text],
   $searchColumn[module],
   $searchColumn[extra],
   $searchColumn[created],
   $searchColumn[session])
SELECT CONCAT(media.$mediaColumn[title], ' [$albumText', album.$albumsColumn[title], ']'),
       media.$mediaColumn[description],
       'mediashare',
       CONCAT(album.$albumsColumn[id], ':', media.$mediaColumn[id]),
       media.$mediaColumn[createdDate],
       '$sessionId'
FROM $mediaTable media
INNER JOIN $albumsTable album
      ON album.$albumsColumn[id] = media.$mediaColumn[parentAlbumId]
WHERE ($accessibleAlbumSql) AND ";

    $sql .= search_construct_where($args, array("media.$mediaColumn[title]", "media.$mediaColumn[description]", "media.$mediaColumn[keywords]"));

    $dbresult = DBUtil::executeSQL($sql);
    if (!$dbresult)
        return LogUtil::registerError(__('Error! Could not load items.', $dom));

    return true;
}

function mediashare_searchapi_search_check(&$args)
{
    $datarow = &$args['datarow'];
    list ($albumId, $mediaId) = explode(':', $datarow['extra']);

    $datarow['url'] = pnModUrl('mediashare', 'user', 'main', array('aid' => $albumId, 'mid' => $mediaId));

    return true;
}

