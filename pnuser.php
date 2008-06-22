<?php
// $Id: pnuser.php,v 1.57 2008/06/22 06:04:23 jornlind Exp $
// =======================================================================
// Mediashare by Jorn Wildt (C) 2005.
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

require_once 'modules/mediashare/common.php';


// =======================================================================
// View album
// =======================================================================

function mediashare_user_main($args)
{
  return mediashare_user_view($args);
}


function mediashare_user_view($args)
{
  if (pnModGetVar('mediashare', 'enableThumbnailStart'))
    return mediashare_user_thumbnails($args);
  else
    return mediashare_user_browse($args);
}


function mediashare_user_browse($args)
{
  $albumId = mediashareGetIntUrl('aid', $args, 1);
  $mediaId = mediashareGetStringUrl('mid', $args, 0); // Ext apps. uses very long IDs, so int is not good
  $invitation = pnVarCleanFromInput('invitation');

  if (!pnModAPILoad('mediashare', 'user'))
    return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare user API');

  if (!empty($mediaId))
  {
      // Check access (use mediaId since album/media combo may not be correct)
    if (!mediashareAccessItem($mediaId, mediashareAccessRequirementViewSomething))
      return mediashareErrorPage(__FILE__, __LINE__, _MSNOAUTH);
  }
  else
  {
      // Check access (use albumId since no mediaId was passed)
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementViewSomething))
      return mediashareErrorPage(__FILE__, __LINE__, _MSNOAUTH);
  }

    // Fetch current album
  $album = pnModAPIFunc('mediashare', 'user', 'getAlbum',
                         array('albumId' => $albumId));
  if ($album === false)
    return mediashareErrorAPIGet();

    // Fetch subalbums
  $subAlbums = pnModAPIFunc('mediashare', 'user', 'getSubAlbums',
                            array('albumId' => $albumId,
                                  'access'  => mediashareAccessRequirementViewSomething));
  if ($subAlbums === false)
    return mediashareErrorAPIGet();

    // Fetch media items
  $items = pnModAPIFunc('mediashare', 'user', 'getMediaItems',
                        array('albumId' => $albumId));
  if ($items === false)
    return mediashareErrorAPIGet();

  // Locate current/prev/next items

  if ($mediaId <= 0)
    $mediaId = $album['mainMediaId'];
  $mediaItem = null;
  if (count($items) == 0)
  {
    $prevMediaId = null;
    $nextMediaId = null;
  }
  else
  {
    $prevMediaId = $items[count($items)-1]['id'];
    $nextMediaId = $items[0]['id'];
  
    if ($mediaId == null)
      $mediaId = $nextMediaId;
  }

  $mediaItemPos = 1;
  $pos = 1;
  foreach ($items as $item)
  {
    if ($mediaItem != null) // Media-Current item found, so this must be next
    {
      $nextMediaId = $item['id'];
      break;
    }
    if ($item['id'] == $mediaId)
    {
      $mediaItem = $item;
      $mediaItemPos = $pos;
    }
    else
      $prevMediaId = $item['id']; // Media-item not found, so this must become prev

    ++$pos;
  }
  if ($mediaItem == null)
    $mediaItem = array('title' => '', 'description' => '', 'id' => 0);

  $render = new pnRender('mediashare');
  $render->caching = false;
  $render->assign('album', $album);
  $render->assign('mediaItem', $mediaItem);
  $render->assign('itemCount', count($items));
  $render->assign('mediaItemPos', $mediaItemPos);
  $render->assign('subAlbums', $subAlbums);
  $render->assign('prevMediaId', $prevMediaId);
  $render->assign('nextMediaId', $nextMediaId);
  $render->assign('hasEditAccess', mediashareAccessAlbum($albumId, mediashareAccessRequirementEditSomething));
  $render->assign('thumbnailSize', pnModGetVar('mediashare', 'thumbnailSize'));

  $template = pnVarPrepForOS($album['template']);
  $templateFilename = "Frontend/$template/album.html";
  if (!$render->template_exists($templateFilename))
    $templateFilename = "Frontend/Standard/album.html";

  return $render->fetch($templateFilename);
}


// =======================================================================
// View items in slideshow
// =======================================================================

function mediashare_user_slideshow($args)
{
  $albumId = mediashareGetIntUrl('aid', $args, 1);
  $mediaId = mediashareGetIntUrl('mid', $args, 0);
  $delay   = mediashareGetIntUrl('delay', $args, 10);
  $mode    = mediashareGetStringUrl('mode', $args, 'stopped');
  $viewkey = pnVarCleanFromInput('viewkey');
  $center  = isset($args['center']) ? '_center' : '';
  $back    = mediashareGetIntUrl('back', $args, 0);

  if (!pnModAPILoad('mediashare', 'user'))
    return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare user API');

  if ($mediaId > 0)
  {
      // Check access (use mediaId since album/media combo may not be correct)
    if (!mediashareAccessItem($mediaId, mediashareAccessRequirementViewSomething, $viewkey))
      return mediashareErrorPage(__FILE__, __LINE__, _MSNOAUTH);
  }
  else
  {
      // Check access (use albumId since no mediaId was passed)
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementViewSomething, $viewkey))
      return mediashareErrorPage(__FILE__, __LINE__, _MSNOAUTH);
  }

  // Fetch current album

  $album = pnModAPIFunc('mediashare', 'user', 'getAlbum',
                         array('albumId' => $albumId));
  if ($album === false)
    return mediashareErrorAPIGet();
  if ($album === true)
    return mediashareErrorPage(__FILE__, __LINE__, 'Unknown album');

  // Fetch media items

  $items = pnModAPIFunc('mediashare', 'user', 'getMediaItems',
                        array('albumId' => $albumId));
  if ($items === false)
    return mediashareErrorAPIGet();

  // Find current, previous and next items
  if ($mediaId == 0  &&  count($items) > 0)
    $mediaId = $items[0]['id'];

  $mediaItem = null;
  if (count($items) > 0)
  {
    $prevMediaId = $items[count($items)-1]['id'];
    $nextMediaId = $items[0]['id'];
    foreach ($items as $item)
    {
      if ($mediaItem != null) // Media-Current item found, so this must be next
      {
        $nextMediaId = $item['id'];
        break;
      }
      if ($item['id'] == $mediaId)
        $mediaItem = $item;
      else
        $prevMediaId = $item['id']; // Media-item not found, so this must become prev
    }
  }
  else
  {
    $prevMediaId = -1;
    $nextMediaId = -1;
  }

  // Add media display HTML

  if (!pnModAPILoad('mediashare', 'mediahandler'))
    return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare mediahandler API');

  for ($i=0,$cou=count($items); $i<$cou; ++$i)
  {
    $handler = pnModAPIFunc('mediashare', 'mediahandler', 'loadHandler',
                            array('handlerName' => $items[$i]['mediaHandler']));
    if ($handler === false)
      return mediashareErrorAPIGet();

    $result = $handler->getMediaDisplayHtml('mediashare/' . $items[$i]['originalRef'], null, null, 'mediaItem', array());

    $items[$i]['html'] = str_replace(array("\r", "\n"), array(' ',' '), $result);
  }

  $viewUrl = pnModUrl('mediashare','user','slideshow', 
                      array('mid' => $mediaItem['id']));

  if ($back)
    pnSessionSetVar('mediashareQuitUrl', isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null);

  $quitUrl = pnSessionGetVar('mediashareQuitUrl');
  if ($quitUrl == null)
    $quitUrl = pnModUrl('mediashare', 'user', 'view', array('aid' => $album['id']));

  $render = new pnRender('mediashare');
  $render->caching = false;
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
  $render->assign('hasEditAccess', mediashareAccessAlbum($albumId, mediashareAccessRequirementEditAlbum, $viewkey));
  $render->assign('thumbnailSize', pnModGetVar('mediashare', 'thumbnailSize'));
  $render->assign('theme', pnUserGetTheme());
  $render->assign('templateName', "slideshow{$center}.html");
  $render->assign('quitUrl', $quitUrl);

  $viewURL = pnModUrl('mediashare','user','slideshow', array('mid' => $mediaItem['id']));

  echo $render->fetch('mediashare_user_slideshow.html');

  return true;
}


function mediashare_user_slideshowcenter($args)
{
  return pnModFunc('mediashare', 'user', 'slideshow', array('center' => true));
}


// =======================================================================
// View thumbnails list
// =======================================================================

function mediashare_user_thumbnails($args)
{
  $albumId = mediashareGetIntUrl('aid', $args, 1);
  $viewkey = pnVarCleanFromInput('viewkey');

  if (!pnModAPILoad('mediashare', 'user'))
    return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare user API');

    // Check access (use albumId since no mediaId was passed)
  if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementViewSomething, $viewkey))
    return mediashareErrorPage(__FILE__, __LINE__, _MSNOAUTH);

  // Fetch current album

  $album = pnModAPIFunc('mediashare', 'user', 'getAlbum',
                         array('albumId' => $albumId));
  if ($album === false)
    return mediashareErrorAPIGet();
  if ($album === true)
    return mediashareErrorPage(__FILE__, __LINE__, 'Unknown album');

  // Fetch media items
  $items = pnModAPIFunc('mediashare', 'user', 'getMediaItems',
                        array('albumId' => $albumId));
  if ($items === false)
    return mediashareErrorAPIGet();

    // Fetch subalbums
  $subAlbums = pnModAPIFunc('mediashare', 'user', 'getSubAlbums',
                            array('albumId' => $albumId,
                                  'access'  => mediashareAccessRequirementViewSomething));
  if ($subAlbums === false)
    return mediashareErrorAPIGet();

  $render = new pnRender('mediashare');
  $render->caching = false;
  $render->assign('mediaItems', $items);
  $render->assign('album', $album);
  $render->assign('subAlbums', $subAlbums);
  $render->assign('albumId', $albumId);
  $render->assign('hasEditAccess', mediashareAccessAlbum($albumId, mediashareAccessRequirementEditAlbum, $viewkey));
  $render->assign('thumbnailSize', pnModGetVar('mediashare', 'thumbnailSize'));
  $render->assign('itemCount', count($items));
  $render->assign('theme', pnUserGetTheme());

  $template = pnVarPrepForOS($album['template']);
  $templateFilename = "Frontend/$template/thumbnails.html";
  if (!$render->template_exists($templateFilename))
    $templateFilename = "Frontend/Standard/thumbnails.html";

  return $render->fetch($templateFilename);
}


function mediashare_user_simplethumbnails($args)
{
  $albumId = mediashareGetIntUrl('aid', $args, 1);
  $template = isset($args['template']) ? $args['template'] : pnVarCleanFromInput('template');
  $itemCount = isset($args['count']) ? $args['count'] : pnVarCleanFromInput('count');

  if (!pnModAPILoad('mediashare', 'user'))
    return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare user API');

    // Check access (use albumId since no mediaId was passed)
  if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementViewSomething))
    return mediashareErrorPage(__FILE__, __LINE__, _MSNOAUTH);

  // Fetch current album

  $album = pnModAPIFunc('mediashare', 'user', 'getAlbum',
                         array('albumId' => $albumId));
  if ($album === false)
    return mediashareErrorAPIGet();
  if ($album === true)
    return mediashareErrorPage(__FILE__, __LINE__, 'Unknown album');

  // Fetch media items
  $items = pnModAPIFunc('mediashare', 'user', 'getMediaItems',
                        array('albumId' => $albumId));
  if ($items === false)
    return mediashareErrorAPIGet();

  $render = new pnRender('mediashare');
  $render->caching = false;
  $render->assign('itemCount', count($items));
  $render->assign('mediaItems', $itemCount === null ? $items : array_slice($items,0,$itemCount));
  $render->assign('album', $album);
  $render->assign('albumId', $albumId);
  $render->assign('thumbnailSize', pnModGetVar('mediashare', 'thumbnailSize'));
  $render->assign('theme', pnUserGetTheme());

  mediashareLoadLightbox();

  $templateFile = 'mediashare_user_simplethumbnails.html';
  if ($template == 'filmstrip')
    $templateFile = 'mediashare_user_contentfilmstrip.html';
  return $render->fetch($templateFile);
}


// =======================================================================
// Display single item
// =======================================================================

// Display fullscreen item - including <html> ... </html>
function mediashare_user_display($args)
{
  $mediaId = mediashareGetIntUrl('mid', $args, 0);
  $viewkey = pnVarCleanFromInput('viewkey');

  if (!pnModAPILoad('mediashare', 'user'))
    return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare user API');

  // Fetch media item

  $mediaItem = pnModAPIFunc('mediashare', 'user', 'getMediaItem',
                            array('mediaId' => $mediaId));
  if ($mediaItem === false)
    return mediashareErrorAPIGet();

  $albumId = $mediaItem['parentAlbumId'];

    // Check access
  if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementViewSomething, $viewkey))
    return mediashareErrorPage(__FILE__, __LINE__, _MSNOAUTH);

  $render = new pnRender('mediashare');
  $render->caching = false;
  $render->assign('mediaItem', $mediaItem);

  echo $render->fetch('mediashare_user_display.html');
  return true;
}


// Display item with as little framing as possible
function mediashare_user_simpledisplay($args)
{
  $mediaId = mediashareGetIntUrl('mid', $args, 0);
  $showAlbumLink = isset($args['showAlbumLink']) ? $args['showAlbumLink'] : false;
  $containerWidth = isset($args['containerWidth']) ? $args['containerWidth'] : 'auto';

  if (!pnModAPILoad('mediashare', 'user'))
    return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare user API');

  // Fetch media item

  $mediaItem = pnModAPIFunc('mediashare', 'user', 'getMediaItem',
                            array('mediaId' => $mediaId));
  if ($mediaItem === false)
    return mediashareErrorAPIGet();

  $albumId = $mediaItem['parentAlbumId'];

    // Check access
  if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementViewSomething))
    return mediashareErrorPage(__FILE__, __LINE__, _MSNOAUTH);

  $render = new pnRender('mediashare');
  $render->caching = false;
  $render->assign('mediaItem', $mediaItem);
  $render->assign('showAlbumLink', $showAlbumLink);
  $render->assign('width', $containerWidth=='wauto' ? null : '100%');

  $csssrc = ThemeUtil::getModuleStylesheet('mediashare');
  PageUtil::addVar('stylesheet', $csssrc);

  mediashareLoadLightbox();
  return $render->fetch('mediashare_user_simpledisplay.html');
}


function mediashare_user_displaygb($args)
{
  $mediaId = mediashareGetIntUrl('mid', $args, 0);
  $viewkey = pnVarCleanFromInput('viewkey');

  if (!pnModAPILoad('mediashare', 'user'))
    return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare user API');

  $mediaItem = pnModAPIFunc('mediashare', 'user', 'getMediaItem',
                            array('mediaId' => $mediaId));
  if ($mediaItem === false)
    return mediashareErrorAPIGet();

  $albumId = $mediaItem['parentAlbumId'];

    // Check access
  if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementViewSomething, $viewkey))
    return mediashareErrorPage(__FILE__, __LINE__, _MSNOAUTH);

  $render = new pnRender('mediashare');
  $render->caching = false;
  $render->assign('mediaItem', $mediaItem);

  echo $render->fetch('mediashare_user_displaygb.html');
  return true;
}


// =======================================================================
// View latest items
// =======================================================================

function mediashare_user_latest($args)
{
  $latestMediaItems = pnModAPIFunc('mediashare', 'user', 'getLatestMediaItems');
  if ($latestMediaItems === false)
    return mediashareErrorAPIGet();

  $latestAlbums = pnModAPIFunc('mediashare', 'user', 'getLatestAlbums');
  if ($latestAlbums === false)
    return mediashareErrorAPIGet();

  $mostActiveUsers = pnModAPIFunc('mediashare', 'user', 'getMostActiveUsers');
  if ($mostActiveUsers === false)
    return mediashareErrorAPIGet();

  $mostActiveKeywords = pnModAPIFunc('mediashare', 'user', 'getMostActiveKeywords');
  if ($mostActiveKeywords === false)
    return mediashareErrorAPIGet();

  $summary = pnModAPIFunc('mediashare', 'user', 'getSummary');
  if ($summary === false)
    return mediashareErrorAPIGet();

  $render = new pnRender('mediashare');
  $render->caching = false;
  $render->assign('latestMediaItems', $latestMediaItems);
  $render->assign('latestAlbums', $latestAlbums);
  $render->assign('mostActiveUsers', $mostActiveUsers);
  $render->assign('mostActiveKeywords', $mostActiveKeywords);
  $render->assign('summary', $summary);
  $render->assign('thumbnailSize', pnModGetVar('mediashare', 'thumbnailSize'));

  return $render->fetch('mediashare_user_latest.html');
}


// =======================================================================
// Keywords
// =======================================================================

function mediashare_user_keys($args)
{
  $keyword = mediashareGetStringUrl('key', $args);

  if (!pnModAPILoad('mediashare', 'user'))
    return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare user API');

  $items = pnModAPIFunc('mediashare', 'user', 'getByKeyword',
                        array('keyword' => $keyword));
  if ($items === false)
    return mediashareErrorAPIGet();

  $render = new pnRender('mediashare');
  $render->caching = false;
  $render->assign('keyword', $keyword);
  $render->assign('items', $items);

  return $render->fetch('mediashare_user_keys.html');
}


// =======================================================================
// List
// =======================================================================

function mediashare_user_list($args)
{
  $keyword    = mediashareGetStringUrl('key', $args);
  $uname      = mediashareGetStringUrl('uname', $args);
  $albumId    = mediashareGetIntUrl('aid', $args, null);
  $topicId    = mediashareGetIntUrl('topic', $args, null);
  $order      = mediashareGetStringUrl('order', $args, 'title');
  $orderDir   = mediashareGetStringUrl('orderdir', $args);
  $recordPos  = mediashareGetIntUrl('pos', $args, 0);
  $template   = (isset($args['tpl']) ? $args['tpl'] : 'list');

  if (!pnModAPILoad('mediashare', 'user'))
    return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare user API');

  $items = pnModAPIFunc('mediashare', 'user', 'getList',
                        compact('keyword', 'uname', 'albumId', 'topicId', 'order', 'orderDir', 'recordPos'));
  if ($items === false)
    return mediashareErrorAPIGet();

  $itemCount = pnModAPIFunc('mediashare', 'user', 'getListCount',
                            compact('keyword', 'uname', 'albumId', 'topicId'));
  if ($itemCount === false)
    return mediashareErrorAPIGet();

  $filterTexts = array();
  if ($keyword != '')
    $filterTexts[] = _MSFILTERKEYWORD . ' "' . pnVarPrepForDisplay($keyword) . '"';
  if ($uname != '')
    $filterTexts[] = _MSFILTERUNAME . ' ' . pnVarPrepForDisplay($uname);
  if ($albumId != null)
  {
    $album = pnModAPIFunc('mediashare', 'user', 'getAlbum',
                           array('albumId' => $albumId));
    if ($album === false)
      return mediashareErrorAPIGet();
    $albumOwner = pnUserGetVar('uname', $album['ownerId']);
    $filterTexts[] = str_replace(array('%user%','%album%'), array(pnVarPrepForDisplay($albumOwner), $album['title']), _MSFILTERITEMSFROM);
  }

  if (count($filterTexts))
    $filterText = implode(', ', $filterTexts);
  else
    $filterText = _MSFILTERNONE;

  $render = new pnRender('mediashare');
  $render->caching = false;
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

  $templateFile = "mediashare_user_{$template}.html";
  return $render->fetch($templateFile);
}


function mediashare_user_albumlist($args)
{
  $order      = mediashareGetStringUrl('order', $args, 'createdDate');
  $orderDir   = mediashareGetStringUrl('orderdir', $args, 'desc');
  $template   = (isset($args['tpl']) ? $args['tpl'] : 'list');

  if (!pnModAPILoad('mediashare', 'user'))
    return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare user API');

  $albums = pnModAPIFunc('mediashare', 'user', 'getAlbumList',
                         compact('order', 'orderDir'));
  if ($albums === false)
    return mediashareErrorAPIGet();

  $render = new pnRender('mediashare');
  $render->caching = false;
  $render->assign('albums', $albums);

  $templateFile = "mediashare_user_album{$template}.html";
  return $render->fetch($templateFile);
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

?>