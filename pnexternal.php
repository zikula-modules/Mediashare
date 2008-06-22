<?php
// $Id: pnexternal.php,v 1.10 2007/08/01 20:24:14 jornlind Exp $
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
require_once 'modules/mediashare/elfisk/elfiskRender.class.php';


// =======================================================================
// Find / paste
// =======================================================================

function mediashare_external_finditem($args)
{
  // FIXME access check

  $albumId  = mediashareGetIntUrl('aid', $args, 1);
  $mediaId  = mediashareGetIntUrl('mid', $args, 0);
  $mode     = pnVarCleanFromInput('mode');
  $cmd      = pnVarCleanFromInput('cmd');
  $onlyMine = mediashareGetIntUrl('onlymine', $args, 0);

  $uploadFailed = false;

  if ($cmd == 'selectAlbum')
    $mediaId = 0;
  else if (isset($_POST['selectButton']))
  {
    $file = (isset($_FILES['upload']) ? $_FILES['upload'] : null);

    if (!empty($file)  &&  $file['error'] == 0  &&  mediashareAccessAlbum($albumId, mediashareAccessRequirementEditAlbum))
    {
      pnModAPILoad('mediashare', 'source_browser');
      $result = pnModAPIFunc('mediashare', 'source_browser', 'addMediaItem',
                             array( 'albumId'        => $albumId,
                                    'uploadFilename' => $file['tmp_name'],
                                    'fileSize'       => $file['size'],
                                    'filename'       => $file['name'],
                                    'mimeType'       => $file['type'],
                                    'title'          => null,
                                    'keywords'       => null,
                                    'description'    => null,
                                    'width'          => 0,
                                    'height'         => 0));
      if ($result === false)
        $uploadFailed = true;
      else
        $mediaId = $result['mediaId'];
    }

    if (!$uploadFailed)
    {
      $url = pnModUrl('mediashare', 'external', 'pasteitem',
                      array('aid' =>  $albumId,
                            'mid' =>  $mediaId,
                            'mode' => $mode));
      pnRedirect($url);
      return true;
    }
  }

  $mediaItem = pnModAPIFunc('mediashare', 'user', 'getMediaItem',
                            array('mediaId' => $mediaId));


  $render = new elfiskRender('mediashare');
  mediashareExternalLoadTheme($render);
  $render->caching = false;
  $render->assign('albumId', $albumId);
  $render->assign('mediaId', $mediaId);
  $render->assign('mediaItem', $mediaItem);
  $render->assign('mode', $mode);
  $render->assign('onlyMine', $onlyMine);
  $render->assign('hasEditAccess', mediashareAccessAlbum($albumId, mediashareAccessRequirementEditAlbum));

  if ($uploadFailed)
    $render->assign('uploadErrorMessage', mediashareErrorAPIGet());

  echo $render->fetch('mediashare_external_finditem.html');
  return true;
}


function mediashare_external_pasteitem($args)
{
  // FIXME access check

  $albumId = mediashareGetIntUrl('aid', $args, 0);
  $mediaId = mediashareGetIntUrl('mid', $args, 0);
  $mode    = pnVarCleanFromInput('mode');

  if (isset($_POST['backButton']))
  {
    $url = pnModUrl('mediashare', 'external', 'finditem',
                    array('aid'   => $albumId,
                          'mid'   => $mediaId,
                          'mode'  => $mode));
    pnRedirect($url);
    return true;
  }

  $mediaItem = pnModAPIFunc('mediashare', 'user', 'getMediaItem',
                            array('mediaId' => $mediaId));


  if (!pnModAPILoad('mediashare', 'mediahandler'))
    return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare mediahandler API');

  $handler = pnModAPIFunc('mediashare', 'mediahandler', 'loadHandler',
                          array('handlerName' => $mediaItem['mediaHandler']));
  if ($handler === false)
    return mediashareErrorAPIGet();

  $render = new elfiskRender('mediashare');
  mediashareExternalLoadTheme($render);
  $render->caching = false;
  $render->assign('albumId', $albumId);
  $render->assign('mediaId', $mediaId);
  $render->assign('mediaItem', $mediaItem);
  $render->assign('thumbnailUrl', "mediashare/$mediaItem[thumbnailRef]");
  $render->assign('previewUrl', "mediashare/$mediaItem[previewRef]");
  $render->assign('originalUrl', "mediashare/$mediaItem[originalRef]");
  $render->assign('mode', $mode);

  echo $render->fetch('mediashare_external_pasteitem.html');
  return true;
}


function mediashareExternalLoadTheme(&$render)
{
  $theme = pnVarPrepForOS(pnUserGetTheme());
  if (file_exists("themes/$theme/style/style.css"))
    $themeCssURL = "themes/$theme/style/style.css";
  else
    $themeCssURL = '';
  
  if (file_exists("modules/mediashare/pnstyle/style.css"))
    $modCssURL = "modules/mediashare/pnstyle/style.css";
  else
    $modCssURL = '';

  $render->assign('themeCssURL', $themeCssURL);
  $render->assign('modCssURL', $modCssURL);
}

?>