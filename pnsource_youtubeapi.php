<?php
// $Id: pnsource_zipapi.php 154 2009-12-18 00:42:55Z mateo $
//
// Mediashare by Jorn Lind-Nielsen (C)
//

require_once 'modules/mediashare/common-edit.php';

function mediashare_source_youtubeapi_getTitle()
{
    $dom = ZLanguage::getModuleDomain('mediashare');
    return __('Youtube Videos', $dom);
}

function mediashare_source_youtubeapi_addMediaItem($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    if (!isset($args['albumId'])) {
        return LogUtil::registerError(__f('Missing [%1$s] in \'%2$s\'', array('albumId', 'source_zipapi.addMediaItem'), $dom));
    }

    $uploadFilename = $args['uploadFilename'];

    $args['mediaFilename'] = $uploadFilename;

    $result = pnModAPIFunc('mediashare', 'edit', 'addMediaItem', $args);

    unlink($uploadFilename);

    return $result;
}



function mediashare_source_youtubeapi_getUploadInfo()
{
    if (!($userInfo = pnModAPIFunc('mediashare', 'edit', 'getUserInfo'))) {
        return false;
    }


    return array('post_max_size'       => (int)($post_max_size / 1000),
                 'upload_max_filesize' => (int)($upload_max_filesize / 1000));
}
