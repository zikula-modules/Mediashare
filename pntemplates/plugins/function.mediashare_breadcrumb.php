<?php

function smarty_function_mediashare_breadcrumb($params, &$smarty)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    if (!isset($params['albumId'])) {
        $smarty->trigger_error(__f('Missing [%1$s] in \'%2$s\'', array('albumId', 'mediashare_breadcrumb'), $dom));
        return false;
    }

    $mode = isset($params['mode']) ? $params['mode'] : 'view';

    $breadcrumb = pnModAPIFunc('mediashare', 'user', 'getAlbumBreadcrumb',
                               array('albumId' => (int)$params['albumId']));

    if ($breadcrumb === false) {
        $smarty->trigger_error(LogUtil::getErrorMessagesText());
        return false;
    }

    $urlType = $mode == 'edit' ? 'edit' : 'user';
    $url     = pnModUrl('mediashare', $urlType, 'view', array('aid' => 0));
    $result  = "<div class=\"mediashare-breadcrumb\">";
    $first   = true;

    foreach ($breadcrumb as $album)
    {
        $url = DataUtil::formatForDisplay(pnModUrl('mediashare', $urlType, 'view', array('aid' => $album['id'])));
        $result .= ($first ? '' : ' &raquo; ')
                 . "<a href=\"$url\">".htmlspecialchars($album['title'])."</a>";
        $first = false;
    }

    $result .= "</div>";

    if (isset($params['assign'])) {
        $smarty->assign($params['assign'], $result);
    }

    return $result;
}
