<?php

function smarty_function_mediashare_itemSelector($params, &$smarty)
{
    if (!isset($params['albumId'])) {
        return $smarty->trigger_error('mediashare_albumSelector: albumId parameter required');
    }

    $albumId = $params['albumId'];
    $mediaId = $params['mediaId'];

    $items = pnModAPIFunc('mediashare', 'user', 'getMediaItems',
                          array('albumId' => $albumId));

    if ($items === false) {
        return mediashareErrorAPIGet();
    }

    if ($mediaId == 0 && count($items) > 0 && isset($params['fetchSelectedInto'])) {
        $mediaId = $items[0]['id'];
        $mediaItem = pnModAPIFunc('mediashare', 'user', 'getMediaItem',
                                  array('mediaId' => $mediaId));

        $smarty->assign($params['fetchSelectedInto'], $mediaItem);
    }

    if (isset($params['onchange'])) {
        $onChangeHtml = " onchange=\"$params[onchange]\"";
    } else {
        $onChangeHtml = '';
    }

    $html = "<select name=\"mid\"$onChangeHtml>\n";

    foreach ($items as $item)
    {
        $title = $item['title'];
        $id    = (int)$item['id'];

        $selectedHtml = ($id == $mediaId ? ' selected="1"' : '');

        $html .= "<option value=\"$id\"$selectedHtml>$title</option>\n";
    }

    $html .= "</select>";

    if (isset($params['assign'])) {
        $smarty->assign($params['assign'], $html);
    }

    return $html;
}
