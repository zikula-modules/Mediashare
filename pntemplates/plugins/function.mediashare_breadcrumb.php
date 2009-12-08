<?php
// $Id$

function smarty_function_mediashare_breadcrumb($params, &$smarty)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    if (!isset($params['albumId'])) {
        $smarty->trigger_error(__('Missing [%1$s] in \'%2$s\'', array('albumId', 'mediashare_breadcrumb'), $dom));
        return false;
    }

    $albumId = (int)$params['albumId'];

    $mode = isset($params['mode']) ? $params['mode'] : 'view';

    $breadcrumb = pnModAPIFunc('mediashare', 'user', 'getAlbumBreadcrumb',
                               array('albumId' => $params['albumId']));

    if ($breadcrumb === false) {
        $smarty->trigger_error(LogUtil::getErrorMessagesText());
        return false;
    }

    $urlType = $mode == 'edit' ? 'edit' : 'user';
    $url     = pnModUrl('mediashare', $urlType, 'view', array('aid' => 0));
    $result  = "<span class=\"mediashare-breadcrumb\">";
    $first   = true;
    foreach ($breadcrumb as $album)
    {
        $url = DataUtil::formatForDisplay(pnModUrl('mediashare', $urlType, 'view', array('aid' => $album['id'])));
        $result .= ($first ? '' : ' :: ')
                 . "<a href=\"$url\">$album[title]</a>";
        $first = false;
    }

    $result .= "</span>";

    if (isset($params['assign'])) {
        $smarty->assign($params['assign'], $result);
    }

    return $result;
}
