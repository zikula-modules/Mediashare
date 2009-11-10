<?php
// $Id$
// =======================================================================
// Mediashare by Jorn Wildt (C) 2005.
// =======================================================================


require_once 'modules/mediashare/common.php';
require_once 'modules/mediashare/elfisk/elfiskRender.class.php';

// =======================================================================
// Find / paste
// =======================================================================


function mediashare_external_finditem($args)
{
    // FIXME access check


    $albumId = mediashareGetIntUrl('aid', $args, 1);
    $mediaId = mediashareGetIntUrl('mid', $args, 0);
    $mode = pnVarCleanFromInput('mode');
    $cmd = pnVarCleanFromInput('cmd');
    $onlyMine = mediashareGetIntUrl('onlymine', $args, 0);

    $uploadFailed = false;

    if ($cmd == 'selectAlbum') {
        $mediaId = 0;
    } else if (isset($_POST['selectButton'])) {
        $file = (isset($_FILES['upload']) ? $_FILES['upload'] : null);

        if (!empty($file) && $file['error'] == 0 && mediashareAccessAlbum($albumId, mediashareAccessRequirementEditAlbum)) {
            pnModAPILoad('mediashare', 'source_browser');
            $result = pnModAPIFunc('mediashare', 'source_browser', 'addMediaItem', array(
                'albumId' => $albumId,
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
            $url = pnModUrl('mediashare', 'external', 'pasteitem', array('aid' => $albumId, 'mid' => $mediaId, 'mode' => $mode));
            pnRedirect($url);
            return true;
        }
    }

    $mediaItem = pnModAPIFunc('mediashare', 'user', 'getMediaItem', array('mediaId' => $mediaId));

    $render = new elfiskRender('mediashare');
    mediashareExternalLoadTheme($render);
    $render->caching = false;
    $render->assign('albumId', $albumId);
    $render->assign('mediaId', $mediaId);
    $render->assign('mediaItem', $mediaItem);
    $render->assign('mode', $mode);
    $render->assign('onlyMine', $onlyMine);
    $render->assign('hasEditAccess', mediashareAccessAlbum($albumId, mediashareAccessRequirementEditAlbum));

    if ($uploadFailed) {
        $render->assign('uploadErrorMessage', mediashareErrorAPIGet());
    }

    echo $render->fetch('mediashare_external_finditem.html');
    return true;
}

function mediashare_external_pasteitem($args)
{
    // FIXME access check


    $albumId = mediashareGetIntUrl('aid', $args, 0);
    $mediaId = mediashareGetIntUrl('mid', $args, 0);
    $mode = pnVarCleanFromInput('mode');

    if (isset($_POST['backButton'])) {
        $url = pnModUrl('mediashare', 'external', 'finditem', array('aid' => $albumId, 'mid' => $mediaId, 'mode' => $mode));
        pnRedirect($url);
        return true;
    }

    $mediaItem = pnModAPIFunc('mediashare', 'user', 'getMediaItem', array('mediaId' => $mediaId));

    if (!pnModAPILoad('mediashare', 'mediahandler')) {
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare mediahandler API');
    }

    $handler = pnModAPIFunc('mediashare', 'mediahandler', 'loadHandler', array('handlerName' => $mediaItem['mediaHandler']));
    if ($handler === false) {
        return mediashareErrorAPIGet();
    }

    $render = new elfiskRender('mediashare');
    mediashareExternalLoadTheme($render);
    $render->caching = false;
    $render->assign('albumId', $albumId);
    $render->assign('mediaId', $mediaId);
    $render->assign('mediaItem', $mediaItem);
    if ($mediaItem['mediaHandler'] != 'extapp') {
        $render->assign('thumbnailUrl', "mediashare/$mediaItem[thumbnailRef]");
        $render->assign('previewUrl', "mediashare/$mediaItem[previewRef]");
        $render->assign('originalUrl', "mediashare/$mediaItem[originalRef]");
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
    $theme = pnVarPrepForOS(pnUserGetTheme());
    if (file_exists("themes/$theme/style/style.css")) {
        $themeCssURL = "themes/$theme/style/style.css";
    } else {
        $themeCssURL = '';
    }

    if (file_exists("modules/mediashare/pnstyle/style.css")) {
        $modCssURL = "modules/mediashare/pnstyle/style.css";
    } else {
        $modCssURL = '';
    }

    $render->assign('themeCssURL', $themeCssURL);
    $render->assign('modCssURL', $modCssURL);
}

?>