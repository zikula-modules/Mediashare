<?php

function smarty_function_mediashare_albumSelector($params, &$smarty)
{
    if (!isset($params['albumId'])) { 
        return $smarty->trigger_error('mediashare_albumSelector: albumId parameter required');
    }

    $albumId        = $params['albumId'];
    $id             = isset($params['id']) ? $params['id'] : 'album';
    $name           = isset($params['name']) ? $params['name'] : $id;
    $excludeAlbumId = isset($params['excludeAlbumId']) ? $params['excludeAlbumId'] : null;
    $onlyMine       = isset($params['onlyMine']) ? $params['onlyMine'] : false;
    $access         = isset($params['access']) ? constant($params['access']) : 0xFF;

    $albums = pnModAPIFunc('mediashare', 'user', 'getAllAlbums',
                           array('albumId'        => 1, // Always get all from top
                                 'excludeAlbumId' => $excludeAlbumId,
                                 'access'         => $access,
                                 'onlyMine'       => $onlyMine));

    if ($albums === false) {
        return $smarty->trigger_error( mediashareErrorAPIGet() );
    }

    if (isset($params['onchange'])) {
        $onChangeHtml = " onchange=\"$params[onchange]\"";
    } else {
        $onChangeHtml = '';
    }

    if (isset($params['id'])) {
        $idHtml = " id=\"$id\"";
    } else {
        $idHtml = '';
    }

    $html = "<select name=\"$name\"$onChangeHtml$idHtml>\n";

    foreach ($albums as $album)
    {
        $title = $album['title'];
        $id    = (int)$album['id'];
        $level = $album['nestedSetLevel'] - 1;

        $indent = '';
        for ($i=0; $i<$level; ++$i) {
            $indent .= '+ ';
        }

        $selectedHtml = ($id == $albumId ? ' selected="1"' : '');

        $html .= "<option value=\"$id\"$selectedHtml>$indent$title</option>\n";
    }

    $html .= "</select>";

    if (isset($params['assign'])) {
        $smarty->assign($params['assign'], $html);
    }

    return $html;
}
