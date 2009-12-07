<?php
// $Id$
// =======================================================================
// Mediashare by Jorn Wildt (C) 2005.
// =======================================================================

require_once 'modules/mediashare/common.php';

// =======================================================================
// Find / paste
// =======================================================================
function mediashare_external_finditem($args)
{
    // FIXME access check
    $albumId  = mediashareGetIntUrl('aid', $args, 1);
    $mediaId  = mediashareGetIntUrl('mid', $args, 0);
    $mode     = FormUtil::getPassedValue('mode');
    $cmd      = FormUtil::getPassedValue('cmd');
    $onlyMine = mediashareGetIntUrl('onlymine', $args, 0);

    $uploadFailed = false;

    if ($cmd == 'selectAlbum') {
        $mediaId = 0;
    } else if (isset($_POST['selectButton'])) {
        $file = (isset($_FILES['upload']) ? $_FILES['upload'] : null);

        if (!empty($file) && $file['error'] == 0 && mediashareAccessAlbum($albumId, mediashareAccessRequirementEditAlbum)) {
            pnModAPILoad('mediashare', 'source_browser');
            $result = pnModAPIFunc('mediashare', 'source_browser', 'addMediaItem',
                                   array('albumId' => $albumId,
                                         'uploadFilename' => $file['tmp_name'],
                                         'fileSize' => $file['size'],
                                         'filename' => $file['name'],
                                         'mimeType' => $file['type'],
                                         'title' => null,
                                         'keywords' => null,
                                         'description' => null,
                                         'width' => 0,
                                         'height' => 0));
            if ($result === false) {
                $uploadFailed = true;
            } else {
                $mediaId = $result['mediaId'];
            }
        }

        if (!$uploadFailed) {
            return pnRedirect(pnModUrl('mediashare', 'external', 'pasteitem', array('aid' => $albumId, 'mid' => $mediaId, 'mode' => $mode)));
        }
    }

    $mediaItem = pnModAPIFunc('mediashare', 'user', 'getMediaItem', array('mediaId' => $mediaId));

    $render = & pnRender::getInstance('mediashare', false);
    mediashareExternalLoadTheme($render);

    $render->assign('albumId', $albumId);
    $render->assign('mediaId', $mediaId);
    $render->assign('mediaItem', $mediaItem);
    $render->assign('mode', $mode);
    $render->assign('onlyMine', $onlyMine);
    $render->assign('hasEditAccess', mediashareAccessAlbum($albumId, mediashareAccessRequirementEditAlbum));

    if ($uploadFailed) {
        $render->assign('uploadErrorMessage', LogUtil::getErrorMessagesText());
    }

    echo $render->fetch('mediashare_external_finditem.html');
    return true;
}

function mediashare_external_pasteitem($args)
{
    // FIXME access check
    $albumId = mediashareGetIntUrl('aid', $args, 0);
    $mediaId = mediashareGetIntUrl('mid', $args, 0);
    $mode = FormUtil::getPassedValue('mode');

    if (isset($_POST['backButton'])) {
        return pnRedirect(pnModUrl('mediashare', 'external', 'finditem', array('aid' => $albumId, 'mid' => $mediaId, 'mode' => $mode)));
    }

    $mediaItem = pnModAPIFunc('mediashare', 'user', 'getMediaItem', array('mediaId' => $mediaId));

    $handler = pnModAPIFunc('mediashare', 'mediahandler', 'loadHandler', array('handlerName' => $mediaItem['mediaHandler']));
    if ($handler === false) {
        return false;
    }

    $render = & pnRender::getInstance('mediashare', false);
    mediashareExternalLoadTheme($render);

    $render->assign('albumId', $albumId);
    $render->assign('mediaId', $mediaId);
    $render->assign('mediaItem', $mediaItem);
    if ($mediaItem['mediaHandler'] != 'extapp') {
        $mediadir = pnModAPIFunc('mediashare', 'user', 'getRelativeMediadir');
        $render->assign('thumbnailUrl', $mediadir.$mediaItem['thumbnailRef']);
        $render->assign('previewUrl', $mediadir.$mediaItem['previewRef']);
        $render->assign('originalUrl', $mediadir.$mediaItem['originalRef']);
    } else {
        $render->assign('thumbnailUrl', "$mediaItem[thumbnailRef]");
        $render->assign('previewUrl', "$mediaItem[previewRef]");
        $render->assign('originalUrl', "$mediaItem[originalRef]");
    }
    $render->assign('mode', $mode);

    echo $render->fetch('mediashare_external_pasteitem.html');
    return true;
}

function mediashareExternalLoadTheme(&$render)
{
    $theme = DataUtil::formatForOS(pnUserGetTheme());

    $themeCssURL = '';
    if (file_exists("themes/$theme/style/style.css")) {
        $themeCssURL = "themes/$theme/style/style.css";
    }

    $modCssURL = '';
    if (file_exists("modules/mediashare/pnstyle/style.css")) {
        $modCssURL = "modules/mediashare/pnstyle/style.css";
    }

    $render->assign('themeCssURL', $themeCssURL);
    $render->assign('modCssURL', $modCssURL);
}
