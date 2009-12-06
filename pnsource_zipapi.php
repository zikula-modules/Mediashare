<?php
// $Id$
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2005.
// =======================================================================

require_once 'modules/mediashare/common-edit.php';

function mediashare_source_zipapi_getTitle($args)
{
    return 'Zip upload';
}

function mediashare_source_zipapi_addMediaItem($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    if (!array_key_exists('albumId', $args)) {
        return LogUtil::registerError(__('Missing [%1$s] in \'%2$s\'', array('albumId', 'source_zipapi.addMediaItem'), $dom));
    }

    $uploadFilename = $args['uploadFilename'];

    $args['mediaFilename'] = $uploadFilename;

    $result = pnModAPIFunc('mediashare', 'edit', 'addMediaItem', $args);

    unlink($uploadFilename);

    return $result;
}

function mediashareSourceZipParseIni($ini)
{
    $l = strlen($ini);
    if ($ini[$l - 1] == 'M' || $ini[$l - 1] == 'm')
        return intval($ini) * 1000000;
    else if ($ini[$l - 1] == 'K' || $ini[$l - 1] == 'k')
        return intval($ini) * 1000;
    else
        return intval($ini);
}

function mediashare_source_zipapi_getUploadInfo($args)
{
    $userInfo = pnModAPIFunc('mediashare', 'edit', 'getUserInfo');
    if ($userInfo === false)
        return mediashareErrorAPIGet();

    $totalCapacityUsed = $userInfo['totalCapacityUsed'];

    $upload_max_filesize = mediashareSourceZipParseIni(ini_get('upload_max_filesize'));
    if ($userInfo['totalCapacityLeft'] < $upload_max_filesize)
        $upload_max_filesize = $userInfo['totalCapacityLeft'];
    if ($userInfo['mediaSizeLimitSingle'] < $upload_max_filesize)
        $upload_max_filesize = $userInfo['mediaSizeLimitSingle'];

    $post_max_size = mediashareSourceZipParseIni(ini_get('post_max_size'));
    if ($userInfo['totalCapacityLeft'] < $post_max_size)
        $post_max_size = $userInfo['totalCapacityLeft'];

    return array('post_max_size' => (int) ($post_max_size / 1000), 'upload_max_filesize' => (int) ($upload_max_filesize / 1000));
}
