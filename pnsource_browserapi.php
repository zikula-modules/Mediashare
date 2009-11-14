<?php
// $Id$
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2005.
// =======================================================================


require_once 'modules/mediashare/common-edit.php';

function mediashare_source_browserapi_getTitle($args)
{
    return 'Browser upload';
}

function mediashare_source_browserapi_addMediaItem($args)
{
    $uploadFilename = $args['uploadFilename'];

    if (!array_key_exists('albumId', $args))
        return mediashareErrorAPI(__FILE__, __LINE__, 'Missing albumId in mediashare_source_browserapi_addMediaItem');

    if (!pnModAPILoad('mediashare', 'edit'))
        return mediashareErrorAPI(__FILE__, __LINE__, 'Failed to load Mediashare edit API');

    // For OPEN_BASEDIR reasons we move the uploaded file as fast as possible to an accessible place
    // MUST remember to remove it afterwards!!!


    // Create and check tmpfilename
    $tmpDir = pnModGetVar('mediashare', 'tmpDirName');

    if (($tmpFilename = tempnam($tmpDir, 'Upload_')) === false)
        return mediashareErrorAPI(__FILE__, __LINE__, "Unable to create tmpFilename in '$tmpDir' (uploading image)");

    if (is_uploaded_file($uploadFilename)) {
        if (move_uploaded_file($uploadFilename, $tmpFilename) === false) {
            unlink($tmpFilename);
            return mediashareErrorAPI(__FILE__, __LINE__, "Unable to move uploaded file from '$uploadFilename' to '$tmpFilename' (uploading image)");
        }
    } else {
        if (!copy($uploadFilename, $tmpFilename)) {
            unlink($tmpFilename);
            return mediashareErrorAPI(__FILE__, __LINE__, "Unable to copy file from '$uploadFilename' to '$tmpFilename' (adding image)");
        }
    }

    $args['mediaFilename'] = $tmpFilename;

    $result = pnModAPIFunc('mediashare', 'edit', 'addMediaItem', $args);

    unlink($tmpFilename);

    if ($result === false)
        return false;
    return $result;
}

function mediashareSourceBrowserParseIni($ini)
{
    $l = strlen($ini);
    if ($ini[$l - 1] == 'M' || $ini[$l - 1] == 'm')
        return intval($ini) * 1000000;
    else if ($ini[$l - 1] == 'K' || $ini[$l - 1] == 'k')
        return intval($ini) * 1000;
    else
        return intval($ini);
}

function mediashare_source_browserapi_getUploadInfo($args)
{
    if (!pnModAPILoad('mediashare', 'edit'))
        return mediashareErrorAPI(__FILE__, __LINE__, 'Failed to load Mediashare edit API');

    $userInfo = pnModAPIFunc('mediashare', 'edit', 'getUserInfo');
    if ($userInfo === false)
        return mediashareErrorAPIGet();

    $totalCapacityUsed = $userInfo['totalCapacityUsed'];

    $upload_max_filesize = mediashareSourceBrowserParseIni(ini_get('upload_max_filesize'));
    if ($userInfo['totalCapacityLeft'] < $upload_max_filesize)
        $upload_max_filesize = $userInfo['totalCapacityLeft'];
    if ($userInfo['mediaSizeLimitSingle'] < $upload_max_filesize)
        $upload_max_filesize = $userInfo['mediaSizeLimitSingle'];

    $post_max_size = mediashareSourceBrowserParseIni(ini_get('post_max_size'));
    if ($userInfo['totalCapacityLeft'] < $post_max_size)
        $post_max_size = $userInfo['totalCapacityLeft'];

    return array('post_max_size' => (int) ($post_max_size / 1000), 'upload_max_filesize' => (int) ($upload_max_filesize / 1000));
}

