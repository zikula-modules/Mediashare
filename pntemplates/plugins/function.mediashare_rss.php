<?php
// $Id$

function smarty_function_mediashare_rss($params, $smarty)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    if (!isset($params['title'])) { 
        $smarty->trigger_error(__f('Missing [%1$s] in \'%2$s\'', array('title', 'mediashare_rss'), $dom));
        return false;
    }

    if (!isset($params['urlParam']) && !isset($params['urlValue'])) { 
        $smarty->trigger_error(__f('Missing [%1$s] in \'%2$s\'', array('urlParam & urlValue', 'mediashare_rss'), $dom));
        return false;
    }

    $url = DataUtil::formatForDisplay(pnModUrl('mediashare', 'user', 'xmllist',
                                      array($params['urlParam'] => $params['urlValue'],
                                            'order'             => 'created',
                                            'orderdir'          => 'desc')));

    $title = $params['title'];

    $link = "<link rel=\"alternate\" type=\"application/rss+xml\" title=\"$title\" href=\"$url\"/>\n";
    PageUtil::addVar('rawtext', $link);

    $imageUrl = DataUtil::formatForDisplay('modules/mediashare/pnimages/rss.gif');

    if ($params['mode'] == 'text') {
        $html = "<span class=\"rss\">[<a href=\"$url\">RSS</a>]</span>";
    } else {
        $html = "<a href=\"$url\"><img src=\"$imageUrl\" alt=\"$title\" title=\"$title\" class=\"clickable\"/></a>";
    }

    return $html;
}
