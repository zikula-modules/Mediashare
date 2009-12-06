<?php

// $Id$
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2005.
// =======================================================================


require_once 'modules/mediashare/common-edit.php';
require_once 'modules/mediashare/elfisk/elfisk_common.php';

function mediashare_source_zip_view(& $args)
{
    $albumId = mediashareGetIntUrl('aid', $args, 0);

    if (isset($_POST['saveButton']))
        return mediashareSourceZipUpload($args);

    if (isset($_POST['moreButton']) || isset($_POST['continueButton'])) {
        // After upload - update items and then continue to next page
        $ok = mediashareSourceZipUpdate();
        if ($ok === false)
            return mediashareErrorAPIGet();
    }

    if (isset($_POST['cancelButton']) || isset($_POST['continueButton'])) {
        pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
        return true;
    }

    if (isset($_POST['moreButton'])) {
        pnRedirect(pnModURL('mediashare', 'edit', 'addmedia', array('aid' => $albumId, 'source' => 'zip')));
        return true;
    }

    if (!pnModAPILoad('mediashare', 'edit'))
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare edit API');

    $uploadInfo = pnModAPIFunc('mediashare', 'source_zip', 'getUploadInfo');

    $render = & pnRender::getInstance('mediashare');
    $render->caching = false;
    $render->assign('imageNum', 1);
    $render->assign('uploadFields', array(1));
    $render->assign('post_max_size', $uploadInfo['post_max_size']);
    $render->assign('upload_max_filesize', $uploadInfo['upload_max_filesize']);

    return $render->fetch('mediashare_source_zip_view.html');
}

function mediashareSourceZipAddFile(& $zip, & $zipEntry, & $args)
{
    // Read zip info and file data into buffer


    $zipSize = zip_entry_filesize($zipEntry);
    $zipName = zip_entry_name($zipEntry);

    if (!zip_entry_open($zip, $zipEntry, "rb"))
        return array(array('ok' => false, 'message' => _MSZIPENTRYOPENERROR . ": $zipName"));

    $buffer = zip_entry_read($zipEntry, $zipSize);
    zip_entry_close($zipEntry);

    // Ensure sub-folder exists


    // Split name by slashes into folders and filename and create/verify the folders recursively
    $folders = explode('/', $zipName);
    $albumId = $args['albumId'];
    $subFolderID = mediashareEnsureFolderExists($albumId, $folders, 0);
    if ($subFolderID === false)
        return false;
    $args['albumId'] = $subFolderID;

    // Get actual filename from folderlist (last item in the array)
    $imageName = $folders[sizeof($folders) - 1];

    // Create tmp. file and copy image data into it
    $tmpdir = pnModGetVar('mediashare', 'tmpDirName');
    $tmpfilename = tempnam($tmpdir, 'IMG');
    if (!($f = fopen($tmpfilename, "wb"))) {
        @ unlink($tmpfilename);
        return false;
    }
    fwrite($f, $buffer);
    fclose($f);

    $args['mimeType'] = '';
    if (function_exists('mime_content_type')) {
        $args['mimeType'] = mime_content_type($tmpfilename);
        if (empty($args['mimeType']))
            $args['mimeType'] = mime_content_type($imageName);
    }
    if (empty($args['mimeType']))
        $args['mimeType'] = mediashareGetMimeType($imageName);

    $args['uploadFilename'] = $tmpfilename;
    $args['fileSize'] = $zipSize;
    $args['filename'] = $imageName;
    $args['keywords'] = null;
    $args['description'] = null;

    // Create image (or add recursively zip archive)
    $result = pnModAPIFunc('mediashare', 'source_zip', 'addMediaItem', $args);

    if ($result === false)
        $status = array('ok' => false, 'message' => mediashareErrorApiGet());
    else
        $status = array('ok' => true, 'message' => $result['message'], 'mediaId' => $result['mediaId']);
    $args['albumId'] = $albumId;

    return $status;
}

function mediashareGetMimeType($filename)
{
    $i = strpos($filename, '.');
    if ($i >= 0) {
        $ext = strtolower(substr($filename, $i + 1));
        if ($ext == 'gif')
            return 'image/gif';
        if ($ext == 'jpg')
            return 'image/jpeg';
        if ($ext == 'jpeg')
            return 'image/jpeg';
        if ($ext == 'png')
            return 'image/png';
    }

    return 'unknown';
}

function mediashareSourceZipUpload(& $args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');
    if (!pnSecConfirmAuthKey())
        return mediashareErrorPage(__FILE__, __LINE__, __('Unknown authentication key: you cannot submit the same form twice.', $dom));

    $albumId = mediashareGetIntUrl('aid', $args, 0);

    // Check access
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementAddMedia, ''))
        return mediashareErrorPage(__FILE__, __LINE__, __('You do not have access to this feature', $dom));

    if (!pnModAPILoad('mediashare', 'source_zip'))
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare source_zip API');

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


    $imageNum = (int) FormUtil::getPassedValue('imagenum');
    $statusSet = array();

    $args['albumId'] = $albumId;

    for ($i = 1; $i <= $imageNum; ++$i) {
        $uploadInfo = $_FILES["upload$i"];
        $args['width'] = FormUtil::getPassedValue("width$i");
        $args['height'] = FormUtil::getPassedValue("height$i");

        if (isset($uploadInfo['error']) && $uploadInfo['error'] != 0 && $uploadInfo['name'] != '') {
            $statusSet[] = array('ok' => false, 'message' => $uploadInfo['name'] . ': ' . mediashareUploadErrorMsg($uploadInfo['error']));
        } else if ($uploadInfo['size'] > 0) {
            $zip = zip_open($uploadInfo['tmp_name']);
            if (!$zip) {
                return mediashareErrorAPI(__FILE__, __LINE__, MSZIPOPENERROR);
            }

            while ($zipEntry = zip_read($zip)) {
                //                  echo "Name:               ".zip_entry_name($zipEntry)."\n";
                //                  echo "Actual Filesize:    ".zip_entry_filesize($zipEntry)."\n";
                //                  echo "Compressed Size:    ".zip_entry_compressedsize($zipEntry)."\n";
                //                  echo "Compression Method: ".zip_entry_compressionmethod($zipEntry)."\n";
                //                  echo "<br>\n";
                if (zip_entry_filesize($zipEntry) > 0) {
                    $result = mediashareSourceZipAddFile($zip, $zipEntry, $args);
                    if ($result === false)
                        $status = array('ok' => false, 'message' => mediashareErrorApiGet());
                    else
                        $status = array('ok' => true, 'message' => $result['message'], 'mediaId' => $result['mediaId']);
                    $statusSet = array_merge($statusSet, array($status));
                }
            }
            zip_close($zip);
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

    $render = & pnRender::getInstance('mediashare');

    $render->caching = false;
    $render->assign('statusSet', $statusSet);
    $render->assign('items', $items);

    return $render->fetch('mediashare_source_zip_uploadet.html');
}

// Second page in upload sequence - user has entered media titles and such like, and it needs to be updated
function mediashareSourceZipUpdate()
{
    if (!pnSecConfirmAuthKey())
        return mediashareErrorPage(__FILE__, __LINE__, __('Unknown authentication key: you cannot submit the same form twice.', $dom));

    $mediaIds = FormUtil::getPassedValue('mediaId');
    foreach ($mediaIds as $mediaId) {
        $mediaId = (int) $mediaId;

        $title = FormUtil::getPassedValue("title-$mediaId");
        $keywords = FormUtil::getPassedValue("keywords-$mediaId");
        $description = FormUtil::getPassedValue("description-$mediaId");

        // Check access
        if (!mediashareAccessItem($mediaId, mediashareAccessRequirementEditMedia, ''))
            return mediashareErrorPage(__FILE__, __LINE__, __('You do not have access to this feature', $dom));

        $ok = pnModAPIFunc('mediashare', 'edit', 'updateItem', array('mediaId' => $mediaId, 'title' => $title, 'keywords' => $keywords, 'description' => $description));
        if ($ok === false)
            return false;
    }

    return true;
}
