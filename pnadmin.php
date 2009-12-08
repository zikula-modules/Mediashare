<?php
// $Id$
//
// Mediashare by Jorn Lind-Nielsen (C)
//

require_once 'modules/mediashare/common-edit.php';
require_once 'modules/mediashare/elfisk/elfisk_common.php';

/**
 * General settings
 */
function mediashare_admin_main($args)
{
    if (!SecurityUtil::checkPermission('mediashare::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    if (FormUtil::getPassedValue('saveButton') || FormUtil::getPassedValue('templateButton')) {
        return mediashareAdminSettings($args);
    }

    if (!($settings = pnModAPIFunc('mediashare', 'user', 'getSettings'))) {
        return false;
    }

    $render = & pnRender::getInstance('mediashare', false);
    array_push($render->plugins_dir, "modules/mediashare/elfisk/plugins");

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
    $settings = array(
        'tmpDirName'            => FormUtil::getPassedValue('tmpDirName'),
        'mediaDirName'          => FormUtil::getPassedValue('mediaDirName'),
        'thumbnailSize'         => FormUtil::getPassedValue('thumbnailSize'),
        'previewSize'           => FormUtil::getPassedValue('previewSize'),
        'mediaSizeLimitSingle'  => (int)FormUtil::getPassedValue('mediaSizeLimitSingle'),
        'mediaSizeLimitTotal'   => (int)FormUtil::getPassedValue('mediaSizeLimitTotal'),
        'defaultAlbumTemplate'  => FormUtil::getPassedValue('defaultAlbumTemplate'),
        'allowTemplateOverride' => FormUtil::getPassedValue('allowTemplateOverride'),
        'enableSharpen'         => FormUtil::getPassedValue('enableSharpen'),
        'enableThumbnailStart'  => FormUtil::getPassedValue('enableThumbnailStart'),
        'flickrAPIKey'          => FormUtil::getPassedValue('flickrAPIKey'),
        'smugmugAPIKey'         => FormUtil::getPassedValue('smugmugAPIKey'),
        'photobucketAPIKey'     => FormUtil::getPassedValue('photobucketAPIKey'),
        'picasaAPIKey'          => FormUtil::getPassedValue('picasaAPIKey'),
        'vfs'                   => FormUtil::getPassedValue('vfs'));

    if (!pnModAPIFunc('mediashare', 'user', 'setSettings', $settings)) {
        return false;
    }

    if (FormUtil::getPassedValue('templateButton')) {
        if (!pnModAPIFunc('mediashare', 'admin', 'setTemplateGlobally', array('template' => $settings['defaultAlbumTemplate']))) {
            return false;
        }
    }

    $dom = ZLanguage::getModuleDomain('mediashare');

    LogUtil::registerStatus(__('Done! Module configuration updated.', $dom));

    return pnRedirect(pnModURL('mediashare', 'admin', 'main'));
}

/**
 * Plugins
 */
function mediashare_admin_plugins($args)
{
    if (!SecurityUtil::checkPermission('mediashare::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    if (FormUtil::getPassedValue('scanButton')) {
        return mediashareAdminScanPlugins();
    }

    if (!($mediaHandlers = pnModAPIFunc('mediashare', 'mediahandler', 'getMediaHandlers'))) {
        return false;
    }

    if (!($sources = pnModAPIFunc('mediashare', 'sources', 'getSources'))) {
        return false;
    }

    $render = & pnRender::getInstance('mediashare', false);

    $render->assign('mediaHandlers', $mediaHandlers);
    $render->assign('sources',       $sources);

    return $render->fetch('mediashare_admin_plugins.html');
}

function mediashareAdminScanPlugins()
{
    if (!pnModAPIFunc('mediashare', 'admin', 'scanAllPlugins')) {
        return false;
    }

    LogUtil::registerStatus(__('Done! Plugins list regenerated.', $dom));

    return pnRedirect(pnModURL('mediashare', 'admin', 'plugins'));
}

/**
 * Recalculate images
 */
function mediashare_admin_recalc($args)
{
    if (!SecurityUtil::checkPermission('mediashare::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    $allItems = pnModAPIFunc('mediashare', 'user', 'getList',
                             array('pageSize' => 999999999));

    $render = & pnRender::getInstance('mediashare', false);

    $render->assign('allItems', $allItems);

    return $render->fetch('mediashare_admin_recalc.html');
}

function mediashare_admin_recalcitem($args)
{
    if (!SecurityUtil::checkPermission('mediashare::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    $mediaId = mediashareGetIntUrl('id');

    if (!pnModAPIFunc('mediashare', 'edit', 'recalcItem', array('mediaId' => $mediaId))) {
        return false;
    }

    if (!($mediaItem = pnModAPIFunc('mediashare', 'user', 'getMediaItem', array('mediaId' => $mediaId)))) {
        return false;
    }

    $render = & pnRender::getInstance('mediashare', false);

    $render->assign('item', $mediaItem);

    $render->display('mediashare_admin_recalcitem.html');
    return true;
}
