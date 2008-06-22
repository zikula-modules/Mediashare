<?php
// $Id: pnadmin.php,v 1.11 2008/06/18 19:38:12 jornlind Exp $
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2005.
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

require_once 'modules/mediashare/common-edit.php';
require_once 'modules/mediashare/elfisk/elfisk_common.php';
require_once 'modules/mediashare/elfisk/elfiskRender.class.php';


// =======================================================================
// General settings
// =======================================================================

function mediashare_admin_main($args)
{
  if (!pnSecAuthAction(0, 'mediashare::', '::', ACCESS_ADMIN))
    return mediashareErrorPage(__FILE__, __LINE__, _MSNOAUTH);

  if (isset($_POST['saveButton'])  ||  isset($_POST['templateButton']))
    return mediashareAdminSettings($args);

  if (!pnModAPILoad('mediashare', 'user'))
    return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare user API');

  $settings = pnModAPIFunc('mediashare', 'user', 'getSettings');
  if ($settings === false)
    return mediashareErrorAPIGet();

  $render = new elfiskRender('mediashare');

  $render->caching = false;
  $render->assign($settings);
  $render->assign('openBaseDir', ini_get('open_basedir'));
  $render->assign('currentDir', dirname(__FILE__));
  $render->assign('fileUploadsAllowed', ini_get('file_uploads'));
  $render->assign('tmpDirIsWritable', mediashareDirIsWritable($settings['tmpDirName']));
  $render->assign('mediaDirIsWritable', mediashareDirIsWritable($settings['mediaDirName']));

  return $render->fetch('mediashare_admin_main.html');
}


function mediashareDirIsWritable($dir)
{
  return is_dir($dir) && is_writable($dir);
}


function mediashareAdminSettings($args)
{
  if (!pnModAPILoad('mediashare', 'user'))
    return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare user API');

  $settings = array('tmpDirName' => pnVarCleanFromInput('tmpDirName'),
                    'mediaDirName' => pnVarCleanFromInput('mediaDirName'),
                    'thumbnailSize' => pnVarCleanFromInput('thumbnailSize'),
                    'previewSize' => pnVarCleanFromInput('previewSize'),
                    'mediaSizeLimitSingle' => (int)pnVarCleanFromInput('mediaSizeLimitSingle'),
                    'mediaSizeLimitTotal' => (int)pnVarCleanFromInput('mediaSizeLimitTotal'),
                    'defaultAlbumTemplate' => pnVarCleanFromInput('defaultAlbumTemplate'),
                    'allowTemplateOverride' => pnVarCleanFromInput('allowTemplateOverride'),
                    'enableSharpen' => pnVarCleanFromInput('enableSharpen'),
                    'enableThumbnailStart' => pnVarCleanFromInput('enableThumbnailStart'),
                    'flickrAPIKey' => pnVarCleanFromInput('flickrAPIKey'),
                    'smugmugAPIKey' => pnVarCleanFromInput('smugmugAPIKey'),
                    'photobucketAPIKey' => pnVarCleanFromInput('photobucketAPIKey'),
                    'picasaAPIKey' => pnVarCleanFromInput('picasaAPIKey'),
                    'vfs' => pnVarCleanFromInput('vfs'));

  $ok = pnModAPIFunc('mediashare', 'user', 'setSettings', $settings);
  if ($ok === false)
    return mediashareErrorAPIGet();

  if (isset($_POST['templateButton']))
  {
    $ok = pnModAPIFunc('mediashare', 'admin', 'setTemplateGlobally', array('template' => pnVarCleanFromInput('defaultAlbumTemplate')));
    if ($ok === false)
      return mediashareErrorAPIGet();
  }

  pnRedirect(pnModURL('mediashare', 'admin', 'main'));
  return true;
}


// =======================================================================
// Plugins
// =======================================================================

function mediashare_admin_plugins($args)
{
  if (!pnSecAuthAction(0, 'mediashare::', '::', ACCESS_ADMIN))
    return mediashareErrorPage(__FILE__, __LINE__, _MSNOAUTH);

  if (isset($_POST['scanButton']))
    return mediashareAdminScanPlugins();

  if (!pnModAPILoad('mediashare', 'mediahandler'))
    return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare mediahandler API');

  if (!pnModAPILoad('mediashare', 'sources'))
    return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare sources API');

  $mediaHandlers = pnModAPIFunc('mediashare', 'mediahandler', 'getMediaHandlers');
  if ($mediaHandlers === false)
    return mediashareErrorAPIGet();

  $sources = pnModAPIFunc('mediashare', 'sources', 'getSources');
  if ($sources === false)
    return mediashareErrorAPIGet();

  $render = new elfiskRender('mediashare');
  $render->caching = false;
  $render->assign('mediaHandlers', $mediaHandlers);
  $render->assign('sources', $sources);

  return $render->fetch('mediashare_admin_plugins.html');
}


function mediashareAdminScanPlugins()
{
  if (!pnModAPILoad('mediashare', 'admin'))
    return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare admin API');

  $ok = pnModAPIFunc('mediashare', 'admin', 'scanAllPlugins');
  if ($ok === false)
    return mediashareErrorAPIGet();

  pnRedirect(pnModURL('mediashare', 'admin', 'plugins'));
  return true;
}


// =======================================================================
// Recalculate images
// =======================================================================

function mediashare_admin_recalc($args)
{
  if (!pnSecAuthAction(0, 'mediashare::', '::', ACCESS_ADMIN))
    return mediashareErrorPage(__FILE__, __LINE__, _MSNOAUTH);

  if (isset($_POST['recalcButton']))
    return mediashareAdminRecalculate($args);

  if (!pnModAPILoad('mediashare', 'user'))
    return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare user API');

  $allItems = pnModAPIFunc('mediashare', 'user', 'getList', 
                           array('pageSize' => 999999999));

  $render = new pnRender('mediashare');
  $render->caching = false;
  $render->assign('allItems', $allItems);

  return $render->fetch('mediashare_admin_recalc.html');
}


function mediashare_admin_recalcitem($args)
{
  if (!pnSecAuthAction(0, 'mediashare::', '::', ACCESS_ADMIN))
    return mediashareErrorPage(__FILE__, __LINE__, _MSNOAUTH);

  $mediaId = mediashareGetIntUrl('id');

  $ok = pnModAPIFunc('mediashare', 'edit', 'recalcItem',
                     array('mediaId' => $mediaId));
  if ($ok === false)
    return mediashareErrorAPIGet();

  $mediaItem = pnModAPIFunc('mediashare', 'user', 'getMediaItem',
                            array('mediaId' => $mediaId));
  if ($mediaItem === false)
    return mediashareErrorAPIGet();

  $render = new pnRender('mediashare');
  $render->caching = false;
  $render->assign('item', $mediaItem);

  echo $render->fetch('mediashare_admin_recalcitem.html');

  return true;
}


?>