<?php
// $Id$
//
// Mediashare by Jorn Wildt (C)
//

require_once 'modules/mediashare/common.php';

/**
 * View album
 */
function mediashare_user_main($args)
{
    return mediashare_user_view($args);
}

function mediashare_user_view($args)
{
    if (pnModGetVar('mediashare', 'enableThumbnailStart')) {
        return mediashare_user_thumbnails($args);
    }

    return mediashare_user_browse($args);
}

function mediashare_user_browse($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $albumId = mediashareGetIntUrl('aid', $args, 1);
    $mediaId = mediashareGetStringUrl('mid', $args, 0); // Ext apps. uses very long IDs, so int is not good
    $invitation = FormUtil::getPassedValue('invitation');

    // Check access to album (media ID won't do a difference if not from this album)
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementViewSomething)) {
        return LogUtil::registerPermissionError();
    }

    // Fetch current album
    if (!($album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId)))) {
        return false;
    }

    // Fetch subalbums
    if (($subAlbums = pnModAPIFunc('mediashare', 'user', 'getSubAlbums', array('albumId' => $albumId, 'access' => mediashareAccessRequirementViewSomething))) === false) {
        return false;
    }

    // Fetch media items
    if (($items = pnModAPIFunc('mediashare', 'user', 'getMediaItems', array('albumId' => $albumId))) === false) {
        return false;
    }

    // Locate current/prev/next items
    if ($mediaId <= 0) {
        $mediaId = $album['mainMediaId'];
    }
    $mediaItem = null;
    if (count($items) == 0) {
        $prevMediaId = null;
        $nextMediaId = null;
    } else {
        $prevMediaId = $items[count($items) - 1]['id'];
        $nextMediaId = $items[0]['id'];

        if ($mediaId == null) {
            $mediaId = $nextMediaId;
        }
    }

    $mediaItemPos = 1;
    $pos = 1;
    foreach ($items as $item)
    {
        if ($mediaItem != null) {
            // Media-Current item found, so this must be next
            $nextMediaId = $item['id'];
            break;
        }
        if ($item['id'] == $mediaId) {
            $mediaItem = $item;
            $mediaItemPos = $pos;
        } else {
            $prevMediaId = $item['id']; // Media-item not found, so this must become prev
        }
        ++$pos;
    }
    if ($mediaItem == null && count($items) > 0) {
        $mediaItem = $items[0];
    } else if ($mediaItem == null) {
        $mediaItem = array('title' => '',
                           'description' => '',
                           'id' => 0);
    }

    // Build the output
    $render = & pnRender::getInstance('mediashare', false);

    $render->assign('album', $album);
    $render->assign('mediaItem', $mediaItem);
    $render->assign('itemCount', count($items));
    $render->assign('mediaItemPos', $mediaItemPos);
    $render->assign('subAlbums', $subAlbums);
    $render->assign('prevMediaId', $prevMediaId);
    $render->assign('nextMediaId', $nextMediaId);
    $render->assign('thumbnailSize', pnModGetVar('mediashare', 'thumbnailSize'));

    // Assign the access array
    if (!mediashareAddAccess($render, $album)) {
        return false;
    }

    $template = DataUtil::formatForOS($album['template']);
    if (!$render->template_exists("Frontend/$template/album.html")) {
        $template = 'Standard';
    }
    // Add the template stylesheets
    if (file_exists("modules/mediashare/pntemplates/Frontend/$template/style.css")) {
        PageUtil::addVar('stylesheet', "modules/mediashare/pntemplates/Frontend/$template/style.css");
    }

    return $render->fetch("Frontend/$template/album.html");
}

/**
 * View items in slideshow
 */
function mediashare_user_slideshow($args)
{
    $albumId = mediashareGetIntUrl('aid', $args, 1);
    $mediaId = mediashareGetIntUrl('mid', $args, 0);
    $delay   = mediashareGetIntUrl('delay', $args, 5);
    $mode    = mediashareGetStringUrl('mode', $args, 'stopped');
    $viewkey = FormUtil::getPassedValue('viewkey');
    $center  = isset($args['center']) ? '_center' : '';
    $back    = mediashareGetIntUrl('back', $args, 0);

    // Check access to album (media ID won't do a difference if not from this album)
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementViewSomething)) {
        return LogUtil::registerPermissionError();
    }

    // Fetch current album
    if (!($album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId)))) {
        return false;
    }
    if ($album === true) {
        return LogUtil::registerError(__('Unknown album.', $dom));
    }

    // Fetch media items
    if (($items = pnModAPIFunc('mediashare', 'user', 'getMediaItems', array('albumId' => $albumId))) === false) {
        return false;
    }

    // Find current, previous and next items
    if ($mediaId == 0 && count($items) > 0) {
        $mediaId = $items[0]['id'];
    }
    $mediaItem = null;
    if (count($items) > 0) {
        $prevMediaId = $items[count($items) - 1]['id'];
        $nextMediaId = $items[0]['id'];
        foreach ($items as $item) {
            if ($mediaItem != null) {
                // Media-Current item found, so this must be next
                $nextMediaId = $item['id'];
                break;
            }
            if ($item['id'] == $mediaId) {
                $mediaItem = $item;
            } else {
                // Media-item not found, so this must become prev
                $prevMediaId = $item['id'];
            }
        }
    } else {
        $prevMediaId = -1;
        $nextMediaId = -1;
    }

    // Add media display HTML
    $mediadir = pnModAPIFunc('mediashare', 'user', 'getRelativeMediadir');
    for ($i = 0, $cou = count($items); $i < $cou; ++$i) {
        if (!($handler = pnModAPIFunc('mediashare', 'mediahandler', 'loadHandler', array('handlerName' => $items[$i]['mediaHandler'])))) {
            return false;
        }
        $result = $handler->getMediaDisplayHtml($mediadir.$items[$i]['originalRef'], null, null, 'mediaItem', array());

        $items[$i]['html'] = str_replace(array("\r", "\n"), array(' ', ' '), $result);
    }

    $viewUrl = pnModUrl('mediashare', 'user', 'slideshow', array('mid' => $mediaItem['id']));

    if ($back) {
        SessionUtil::setVar('mediashareQuitUrl', isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null);
    }

    $quitUrl = SessionUtil::getVar('mediashareQuitUrl');
    if ($quitUrl == null) {
        $quitUrl = pnModUrl('mediashare', 'user', 'view', array('aid' => $album['id']));
    }

    // Build the output
    $render = & pnRender::getInstance('mediashare', false);

    $render->assign('viewUrl', $viewUrl);
    $render->assign('mediaId', $mediaId);
    $render->assign('mediaItem', $mediaItem);
    $render->assign('prevMediaId', $prevMediaId);
    $render->assign('nextMediaId', $nextMediaId);
    $render->assign('mediaItems', $items);
    $render->assign('album', $album);
    $render->assign('albumId', $albumId);
    $render->assign('delay', $delay);
    $render->assign('mode', $mode);
    $render->assign('thumbnailSize', pnModGetVar('mediashare', 'thumbnailSize'));
    $render->assign('theme', pnUserGetTheme());
    $render->assign('templateName', "slideshow{$center}.html");
    $render->assign('quitUrl', $quitUrl);

    // Add the access array
    if (!mediashareAddAccess($render, $album)) {
        return false;
    }

    $render->load_filter('output', 'pagevars');
    if (pnConfigGetVar('shorturls')) {
        $render->load_filter('output', 'shorturls');
    }

    $render->display('mediashare_user_slideshow.html');
    return true;
}

function mediashare_user_slideshowcenter($args)
{
    return pnModFunc('mediashare', 'user', 'slideshow', array('center' => true));
}

/**
 * View thumbnails list
 */
function mediashare_user_thumbnails($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $albumId = mediashareGetIntUrl('aid', $args, 1);
    $viewkey = FormUtil::getPassedValue('viewkey');

    // Check access (use albumId since no mediaId was passed)
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementViewSomething, $viewkey)) {
        return LogUtil::registerPermissionError();
    }

    // Fetch current album
    if (!($album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId)))) {
        return false;
    }
    if ($album === true) {
        return LogUtil::registerError(__('Unknown album.', $dom));
    }

    // Fetch media items
    if (($items = pnModAPIFunc('mediashare', 'user', 'getMediaItems', array('albumId' => $albumId))) === false) {
        return false;
    }

    // Fetch subalbums
    if (($subAlbums = pnModAPIFunc('mediashare', 'user', 'getSubAlbums', array('albumId' => $albumId, 'access' => mediashareAccessRequirementViewSomething))) === false) {
        return false;
    }

    // Build the output
    $render = & pnRender::getInstance('mediashare', false);

    $render->assign('mediaItems', $items);
    $render->assign('album', $album);
    $render->assign('subAlbums', $subAlbums);
    $render->assign('albumId', $albumId);
    $render->assign('thumbnailSize', pnModGetVar('mediashare', 'thumbnailSize'));
    $render->assign('itemCount', count($items));
    $render->assign('theme', pnUserGetTheme());

    if (!mediashareAddAccess($render, $album)) {
        return false;
    }

    $template = DataUtil::formatForOS($album['template']);
    if (!$render->template_exists("Frontend/$template/thumbnails.html")) {
        $template = 'Standard';
    }

    return $render->fetch("Frontend/$template/thumbnails.html");
}

function mediashare_user_simplethumbnails($args)
{
    $albumId   = mediashareGetIntUrl('aid', $args, 1);
    $template  = isset($args['template']) ? $args['template'] : FormUtil::getPassedValue('template');
    $itemCount = isset($args['count']) ? $args['count'] : FormUtil::getPassedValue('count');

    // Check access (use albumId since no mediaId was passed)
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementViewSomething)) {
        return LogUtil::registerPermissionError();
    }

    // Fetch current album
    if (!($album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId)))) {
        return false;
    }
    if ($album === true) {
        return LogUtil::registerError(__('Unknown album.', $dom));
    }

    // Fetch media items
    if (($items = pnModAPIFunc('mediashare', 'user', 'getMediaItems', array('albumId' => $albumId))) === false) {
        return false;
    }

    // Build the output
    $render = & pnRender::getInstance('mediashare', false);

    $render->assign('itemCount', count($items));
    $render->assign('mediaItems', $itemCount === null ? $items : array_slice($items, 0, $itemCount));
    $render->assign('album', $album);
    $render->assign('albumId', $albumId);
    $render->assign('thumbnailSize', pnModGetVar('mediashare', 'thumbnailSize'));
    $render->assign('theme', pnUserGetTheme());

    mediashareLoadLightbox();

    $template = 'content'.DataUtil::formatForOS($template); // filmstrip
    if (!$render->template_exists("mediashare_user_{$template}.html")) {
        $template = 'simplethumbnails';
    }

    return $render->fetch("mediashare_user_{$template}.html");
}

/**
 * Display single item
 */

// Display fullscreen item - including <html> ... </html>
function mediashare_user_display($args)
{
    $mediaId = mediashareGetIntUrl('mid', $args, 0);
    $viewkey = FormUtil::getPassedValue('viewkey');

    // Fetch media item
    if (!($mediaItem = pnModAPIFunc('mediashare', 'user', 'getMediaItem', array('mediaId' => $mediaId)))) {
        return false;
    }

    $albumId = $mediaItem['parentAlbumId'];

    // Check access
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementViewSomething, $viewkey)) {
        return LogUtil::registerPermissionError();
    }

    // Build the output
    $render = & pnRender::getInstance('mediashare', false);

    $render->assign('mediaItem', $mediaItem);

    $render->display('mediashare_user_display.html');
    return true;
}

// Display item with as little framing as possible
function mediashare_user_simpledisplay($args)
{
    $mediaId        = mediashareGetIntUrl('mid', $args, 0);
    $showAlbumLink  = isset($args['showAlbumLink']) ? $args['showAlbumLink'] : false;
    $containerWidth = isset($args['containerWidth']) ? $args['containerWidth'] : 'auto';
    $text           = isset($args['text']) ? $args['text'] : '';

    // Fetch media item
    if (!($mediaItem = pnModAPIFunc('mediashare', 'user', 'getMediaItem', array('mediaId' => $mediaId)))) {
        return false;
    }
    if ($text == '.') {
        $text = '';
    } else if (empty($text)) {
        $text = $mediaItem['title'];
    }
    $albumId = $mediaItem['parentAlbumId'];

    // Check access
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementViewSomething)) {
        return LogUtil::registerPermissionError();
    }

    // Build the output
    $render = & pnRender::getInstance('mediashare', false);

    $render->assign('mediaItem', $mediaItem);
    $render->assign('showAlbumLink', $showAlbumLink);
    $render->assign('text', $text);
    $render->assign('width', $containerWidth == 'wauto' ? null : '100%');

    mediashareLoadLightbox();

    return $render->fetch('mediashare_user_simpledisplay.html');
}

function mediashare_user_displaygb($args)
{
    $mediaId = mediashareGetIntUrl('mid', $args, 0);
    $viewkey = FormUtil::getPassedValue('viewkey');

    if (!($mediaItem = pnModAPIFunc('mediashare', 'user', 'getMediaItem', array('mediaId' => $mediaId)))) {
        return false;
    }

    $albumId = $mediaItem['parentAlbumId'];

    // Check access
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementViewSomething, $viewkey)) {
        return LogUtil::registerPermissionError();
    }

    // Build the output
    $render = & pnRender::getInstance('mediashare', false);

    $render->assign('mediaItem', $mediaItem);

    $render->display('mediashare_user_displaygb.html');
    return true;
}

/**
 * View latest items
 */
function mediashare_user_latest($args)
{
    if (!($latestMediaItems = pnModAPIFunc('mediashare', 'user', 'getLatestMediaItems'))) {
        return false;
    }

    if (!($latestAlbums = pnModAPIFunc('mediashare', 'user', 'getLatestAlbums'))) {
        return false;
    }

    if (!($mostActiveUsers = pnModAPIFunc('mediashare', 'user', 'getMostActiveUsers'))) {
        return false;
    }

    if (!($mostActiveKeywords = pnModAPIFunc('mediashare', 'user', 'getMostActiveKeywords'))) {
        return false;
    }

    if (!($summary = pnModAPIFunc('mediashare', 'user', 'getSummary'))) {
        return false;
    }

    // Build the output
    $render = & pnRender::getInstance('mediashare', false);

    $render->assign('latestMediaItems', $latestMediaItems);
    $render->assign('latestAlbums', $latestAlbums);
    $render->assign('mostActiveUsers', $mostActiveUsers);
    $render->assign('mostActiveKeywords', $mostActiveKeywords);
    $render->assign('summary', $summary);
    $render->assign('thumbnailSize', pnModGetVar('mediashare', 'thumbnailSize'));

    return $render->fetch('mediashare_user_latest.html');
}

/**
 * Keywords
 */
function mediashare_user_keys($args)
{
    $keyword = mediashareGetStringUrl('key', $args);

    if (!($items = pnModAPIFunc('mediashare', 'user', 'getByKeyword', array('keyword' => $keyword)))) {
        return false;
    }

    // Build the output
    $render = & pnRender::getInstance('mediashare', false);

    $render->assign('keyword', $keyword);
    $render->assign('items', $items);

    return $render->fetch('mediashare_user_keys.html');
}

/**
 * List
 */
function mediashare_user_list($args)
{
    $keyword   = mediashareGetStringUrl('key', $args);
    $uname     = mediashareGetStringUrl('uname', $args);
    $albumId   = mediashareGetIntUrl('aid', $args, null);
    $order     = mediashareGetStringUrl('order', $args, 'title');
    $orderDir  = mediashareGetStringUrl('orderdir', $args);
    $recordPos = mediashareGetIntUrl('pos', $args, 0);
    $template  = (isset($args['tpl']) ? $args['tpl'] : 'list');

    if (!($items = pnModAPIFunc('mediashare', 'user', 'getList', compact('keyword', 'uname', 'albumId', 'order', 'orderDir', 'recordPos')))) {
        return false;
    }

    if (!($itemCount = pnModAPIFunc('mediashare', 'user', 'getListCount', compact('keyword', 'uname', 'albumId')))) {
        return false;
    }

    $dom = ZLanguage::getModuleDomain('mediashare');

    $filterTexts = array();
    if ($keyword != '') {
        $filterTexts[] = __('Items tagged with "%s"', DataUtil::formatForDisplay($keyword), $dom);
    }
    if ($uname != '') {
        $filterTexts[] = __('Items by %s', DataUtil::formatForDisplay($uname), $dom);
    }
    if ($albumId != null) {
        if (!($album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId)))) {
            return false;
        }
        $albumOwner = pnUserGetVar('uname', $album['ownerId']);
        $filterTexts[] = __('Items from %1$s\'s album \'%2$s\'', array(DataUtil::formatForDisplay($albumOwner), $album['title']), $dom);
    }

    if (count($filterTexts)) {
        $filterText = implode(', ', $filterTexts);
    } else {
        $filterText = __('All items', $dom);
    }

    // Build the output
    $render = & pnRender::getInstance('mediashare', false);

    $render->assign('keyword', $keyword);
    $render->assign('items', $items);
    $render->assign('itemCount', $itemCount);
    $render->assign('order', $order);
    $render->assign('filterText', $filterText);
    $render->assign('orderTitleClass', ($order == 'title' ? ' class="selected"' : ''));
    $render->assign('orderUnameClass', ($order == 'uname' ? ' class="selected"' : ''));
    $render->assign('orderCreatedClass', ($order == 'created' ? ' class="selected"' : ''));
    $render->assign('orderModifiedClass', ($order == 'modified' ? ' class="selected"' : ''));
    $render->assign('pos', $recordPos);

    $template = DataUtil::formatForOS($template);
    if (!$render->template_exists("mediashare_user_{$template}.html")) {
        $template = 'list';
    }

    return $render->fetch("mediashare_user_{$template}.html");
}

function mediashare_user_albumlist($args)
{
    $order    = mediashareGetStringUrl('order', $args, 'createdDate');
    $orderDir = mediashareGetStringUrl('orderdir', $args, 'desc');
    $template = (isset($args['tpl']) ? $args['tpl'] : 'list');

    if (!($albums = pnModAPIFunc('mediashare', 'user', 'getAlbumList', compact('order', 'orderDir')))) {
        return false;
    }

    // Build the output
    $render = & pnRender::getInstance('mediashare', false);

    $render->assign('albums', $albums);

    $template = DataUtil::formatForOS($template);
    if (!$render->template_exists("mediashare_user_album{$template}.html")) {
        $template = 'list';
    }

    return $render->fetch("mediashare_user_album{$template}.html");
}

function mediashare_user_xmllist($args)
{
    $args['tpl'] = 'rsslist';
    $output = mediashare_user_list($args);

    header("Content-type: text/xml");
    echo $output;
    return true;
}

function mediashare_user_albumxmllist($args)
{
    $args['tpl'] = 'rsslist';
    $output = mediashare_user_albumlist($args);

    header("Content-type: text/xml");
    echo $output;
    return true;
}

/**
 * Ext. app. help
 */
function mediashare_user_extapphelp($args)
{
    if (!($settings = pnModAPIFunc('mediashare', 'user', 'getSettings'))) {
        return false;
    }

    // Build the output
    $render = & pnRender::getInstance('mediashare');
    $render->assign('settings', $settings);

    return $render->fetch('mediashare_user_extapphelp.html');
}
