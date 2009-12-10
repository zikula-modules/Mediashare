<?php
// $Id: random.php,v 1.5 2007/07/22 06:17:04 jornlind Exp $
//
// Mediashare by Jorn Wildt (C)
//

require_once 'modules/mediashare/common.php';
require_once 'modules/mediashare/pnincludes/elfisk_common.php';

function mediashare_randomblock_init()
{
    // Security
    SecurityUtil::registerPermissionSchema('mediashare:randomblock:', 'Block title::Block Id');
}

/**
 * get information on block
 */
function mediashare_randomblock_info()
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    return array('module'         => 'mediashare',
                 'text_type'      => __('Mediashare random item', $dom),
                 'text_type_long' => __('Display a Mediashare random item', $dom),
                 'allow_multiple' => true,
                 'form_content'   => false,
                 'form_refresh'   => false,
                 'show_preview'   => true);
}

function mediashare_randomblock_display($blockinfo)
{
    // Security check
    if (!SecurityUtil::checkPermission('mediashare:randomblock:', "$blockinfo[title]::$blockinfo[bid]", ACCESS_READ)) {
        return;
    }

    // Get variables from content block
    $vars = pnBlockVarsFromContent($blockinfo['content']);

    $sessionVarName = 'mediashare_block_' . $blockinfo['bid'];
    $sessionVars    = SessionUtil::getVar($sessionVarName);
    if ($sessionVars == '' || $sessionVars == null) {
        $sessionVars = array();
    }

    if (isset($sessionVars['oldContent']) && isset($sessionVars['lastUpdate'])) {
        $past = time() - $sessionVars['lastUpdate'];
        if ($past < $vars['cacheTime']) {
            // No need to refresh - move old content into real content
            $blockinfo['content'] = $sessionVars['oldContent'];
            return themesideblock($blockinfo);
        }
    }

    if ($vars['type'] == 'album') {
        $randomInfo = pnModAPIFunc('mediashare', 'user', 'getRandomMediaItem', array('albumId' => $vars['albumId'], 'mode' => 'album'));
    } else if ($vars['type'] == 'latest') {
        $randomInfo = pnModAPIFunc('mediashare', 'user', 'getRandomMediaItem', array('latest' => true, 'mode' => 'latest'));
    } else {
        $randomInfo = pnModAPIFunc('mediashare', 'user', 'getRandomMediaItem');
    }

    if ($randomInfo === false) {
        return false;
    }

    $mediaId = $randomInfo['mediaId'];
    $albumId = $randomInfo['albumId'];

    if (empty($mediaId)) {
        return;
    }

    // Get image info
    $mediaInfo    = pnModAPIFunc('mediashare', 'user', 'getMediaItem', array('mediaId' => $mediaId));

    // Get album info
    $albumInfo    = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId));
    $originalURL  = pnModAPIFunc('mediashare', 'user', 'getMediaUrl', array('mediaItem' => $mediaInfo, 'src' => 'originalRef'));
    $previewURL   = pnModAPIFunc('mediashare', 'user', 'getMediaUrl', array('mediaItem' => $mediaInfo, 'src' => 'previewRef'));
    $thumbnailURL = pnModAPIFunc('mediashare', 'user', 'getMediaUrl', array('mediaItem' => $mediaInfo, 'src' => 'thumbnailRef'));
    $albumURL     = pnModUrl('mediashare', 'user', 'view', array('aid' => $albumId, 'mid' => $mediaId));

    // Create the final HTML by substituting various macros into the user specified HTML code
    $substitutes = array('originalURL'  => $originalURL,
                         'previewURL'   => $previewURL,
                         'thumbnailURL' => $thumbnailURL,
                         'albumURL'     => $albumURL,
                         'title'        => $mediaInfo['title'],
                         'owner'        => __('Unknown', $dom),
                         'albumTitle'   => $albumInfo['title']);

    $html = $vars['html'];

    foreach ($substitutes as $key => $value)
    {
        $pattern = '${' . $key . '}';
        $html = str_replace($pattern, $value, $html);
    }

    $blockinfo['content'] = $html;

    $sessionVars['oldContent'] = $html;
    $sessionVars['lastUpdate'] = time();

    SessionUtil::setVar($sessionVarName, $sessionVars);

    // ... and return encapsulated in a theme block
    return themesideblock($blockinfo);
}

function mediashare_randomblock_modify($blockinfo)
{
    // Get current content
    $vars = pnBlockVarsFromContent($blockinfo['content']);

    // Defaults
    if (!isset($vars['type'])) {
        $vars['type'] = 'all';
    }
    if (!isset($vars['albumId'])) {
        $vars['albumId'] = '';
    }
    if (!isset($vars['cacheTime'])) {
        $vars['cacheTime'] = 30;
    }
    if (!isset($vars['html'])) {
        $vars['html'] = '<div class="mediashare-random-block"><a href="${originalURL}" target="_new"><img src="${thumbnailURL}" alt=""></a><br/><b>${title}</b><br/>Album: <a href="${albumURL}">${albumTitle}</a></div>';
    }
    if (!isset($vars['useRefreshTime'])) {
        $vars['useRefreshTime'] = 0;
    }

    $render = & pnRender::getInstance('mediashare', false);

    $render->assign($vars);

    return $render->fetch('mediashare_block_random.html');
}

function mediashare_randomblock_update($blockinfo)
{
    $vars = array('type'      => FormUtil::getPassedValue('mstype'),
                  'albumId'   => (int)FormUtil::getPassedValue('msalbumId'),
                  'cacheTime' => (int)FormUtil::getPassedValue('cacheTime'),
                  'html'      => FormUtil::getPassedValue('mshtml'));

    $blockinfo['content'] = pnBlockVarsToContent($vars);

    return $blockinfo;
}
