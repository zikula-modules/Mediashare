<?php
// $Id$
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2005.
// =======================================================================


require_once 'modules/mediashare/common-edit.php';
require_once 'modules/mediashare/elfisk/elfisk_common.php';
require_once 'modules/mediashare/elfisk/elfiskRender.class.php';

function mediashare_source_browser_view(&$args)
{
    $albumId = mediashareGetIntUrl('aid', $args, 0);

    if (isset($_POST['saveButton']))
        return mediashareSourceBrowserUpload($args);

    if (isset($_POST['moreButton']) || isset($_POST['continueButton'])) {
        // After upload - update items and then continue to next page
        $ok = mediashareSourceBrowserUpdate();
        if ($ok === false)
            return mediashareErrorAPIGet();
    }

    if (isset($_POST['cancelButton']) || isset($_POST['continueButton'])) {
        pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
        return true;
    }

    if (isset($_POST['moreButton'])) {
        pnRedirect(pnModURL('mediashare', 'edit', 'addmedia', array('aid' => $albumId, 'source' => 'browser')));
        return true;
    }

    if (!pnModAPILoad('mediashare', 'edit'))
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare edit API');

    $uploadInfo = pnModAPIFunc('mediashare', 'source_browser', 'getUploadInfo');

    $render = new pnRender('mediashare');
    $render->caching = false;
    $render->assign('imageNum', 10);
    $render->assign('uploadFields', array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10));
    $render->assign('post_max_size', $uploadInfo['post_max_size']);
    $render->assign('upload_max_filesize', $uploadInfo['upload_max_filesize']);

    return $render->fetch('mediashare_source_browser_view.html');
}

function mediashareSourceBrowserUpload(&$args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');
    if (!pnSecConfirmAuthKey())
        return mediashareErrorPage(__FILE__, __LINE__, __('Unknown authentication key: you cannot submit the same form twice.', $dom));

    $albumId = mediashareGetIntUrl('aid', $args, 0);

    // Check access
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementAddMedia, ''))
        return mediashareErrorPage(__FILE__, __LINE__, __('You do not have access to this feature', $dom));

    if (!pnModAPILoad('mediashare', 'source_browser'))
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare source_browser API');

    if (!pnModAPILoad('mediashare', 'edit'))
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare edit API');

    if (!pnModAPILoad('mediashare', 'user'))
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare user API');

    // Get parent album information
    $album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId));
    if ($album === false)
        return mediashareErrorAPIGet();

    // Get user information
    $userInfo = pnModAPIFunc('mediashare', 'edit', 'getUserInfo');
    if ($userInfo === false)
        return mediashareErrorAPIGet();

    $totalCapacityUsed = $userInfo['totalCapacityUsed'];

    // Start fetching media items


    $imageNum = (int) pnVarCleanFromInput('imagenum');
    $statusSet = array();

    for ($i = 1; $i <= $imageNum; ++$i) {
        $uploadInfo = $_FILES["upload$i"];
        $width = pnVarCleanFromInput("width$i");
        $height = pnVarCleanFromInput("height$i");

        if (isset($uploadInfo['error']) && $uploadInfo['error'] != 0 && $uploadInfo['name'] != '') {
            $statusSet[] = array('ok' => false, 'message' => $uploadInfo['name'] . ': ' . mediashareUploadErrorMsg($uploadInfo['error']));
        } else if ($uploadInfo['size'] > 0) {
            $result = pnModAPIFunc('mediashare', 'source_browser', 'addMediaItem', array(
                'albumId' => $albumId,
                'uploadFilename' => $uploadInfo['tmp_name'],
                'fileSize' => $uploadInfo['size'],
                'filename' => $uploadInfo['name'],
                'mimeType' => $uploadInfo['type'],
                'title' => null,
                'keywords' => null,
                'description' => null,
                'width' => $width,
                'height' => $height));
            if ($result === false)
                $status = array('ok' => false, 'message' => mediashareErrorApiGet());
            else
                $status = array('ok' => true, 'message' => $result['message'], 'mediaId' => $result['mediaId']);

            $statusSet = array_merge($statusSet, array($status));
        }
    }

    // Quick count of uploaded images + getting IDs for further editing
    $editMediaIds = array();
    $acceptedImageNum = 0;
    foreach ($statusSet as $status) {
        if ($status['ok']) {
            ++$acceptedImageNum;
            $editMediaIds[] = $status['mediaId'];
        }
    }
    $album['imageCount'] += $acceptedImageNum; // Update for showing only


    if ($acceptedImageNum == 0)
        $statusSet[] = array('ok' => false, 'message' => __('No media items', $dom));

    $items = pnModAPIFunc('mediashare', 'user', 'getMediaItems', array('mediaIdList' => $editMediaIds));
    if ($items === false)
        return mediashareErrorAPIGet();

    $render = new pnRender('mediashare');

    $render->caching = false;
    $render->assign('statusSet', $statusSet);
    $render->assign('items', $items);

    return $render->fetch('mediashare_source_browser_uploadet.html');
}

// Second page in upload sequence - user has entered media titles and such like, and it needs to be updated
function mediashareSourceBrowserUpdate()
{
    $dom = ZLanguage::getModuleDomain('mediashare');
    if (!pnSecConfirmAuthKey())
        return mediashareErrorPage(__FILE__, __LINE__, __('Unknown authentication key: you cannot submit the same form twice.', $dom));

    $mediaIds = pnVarCleanFromInput('mediaId');
    foreach ($mediaIds as $mediaId) {
        $mediaId = (int) $mediaId;

        $title = pnVarCleanFromInput("title-$mediaId");
        $keywords = pnVarCleanFromInput("keywords-$mediaId");
        $description = pnVarCleanFromInput("description-$mediaId");

        // Check access
        if (!mediashareAccessItem($mediaId, mediashareAccessRequirementEditMedia, ''))
            return mediashareErrorPage(__FILE__, __LINE__, __('You do not have access to this feature', $dom));

        $ok = pnModAPIFunc('mediashare', 'edit', 'updateItem', array('mediaId' => $mediaId, 'title' => $title, 'keywords' => $keywords, 'description' => $description));
        if ($ok === false)
            return false;
    }

    return true;
}

