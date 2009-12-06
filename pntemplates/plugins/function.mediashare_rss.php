<?php

function smarty_function_mediashare_rss($params, $smarty)
{
    if (!isset($params['title'])) { 
        return $smarty->trigger_error('mediashare_rss: title parameter required');
    }

    if (!isset($params['urlParam'])  &&  !isset($params['urlValue'])) { 
        return $smarty->trigger_error('mediashare_rss: urlParam and urlValue parameter required');
    }

    $url = pnVarPrepForDisplay(pnModUrl('mediashare', 'user', 'xmllist',
                                      array($params['urlParam'] => $params['urlValue'],
                                            'order'             => 'created',
                                            'orderdir'          => 'desc')));

    $title = $params['title'];

    global $additional_header;
    $link = "<link rel=\"alternate\" type=\"application/rss+xml\" title=\"$title\" href=\"$url\"/>\n";
    $additional_header[] = $link;

    $imageUrl = pnVarPrepForDisplay('modules/mediashare/pnimages/rss.gif');

    if ($params['mode'] == 'text') {
        $html = "<span class=\"rss\">[<a href=\"$url\">RSS</a>]</span>";
    } else {
        $html = "<a href=\"$url\"><img src=\"$imageUrl\" alt=\"$title\" title=\"$title\" class=\"clickable\"/></a>";
    }

    return $html;
}


