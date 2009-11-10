<?php

function smarty_function_mediashare_mediaItem($params, &$smarty)
{
    $dom = ZLanguage::getModuleDomain('Mediashare');
    pnModLoad('mediashare', 'user');
    $mediaBase = 'mediashare/'; // FIXME


    // Check for absolute URLs returned by external apps.
    $src = (substr($params['src'], 0, 4) == 'http' ? $params['src'] : $mediaBase . htmlspecialchars($params['src']));

    $title = array_key_exists('title', $params) ? $params['title'] : '';
    $id = array_key_exists('id', $params) ? $params['id'] : null;
    $isThumbnail = array_key_exists('isThumbnail', $params) ? (bool) $params['isThumbnail'] : false;
    $width = array_key_exists('width', $params) ? $params['width'] : null;
    $height = array_key_exists('height', $params) ? $params['height'] : null;
    $class = array_key_exists('class', $params) ? $params['class'] : null;
    $style = array_key_exists('style', $params) ? $params['style'] : null;
    $onclick = array_key_exists('onclick', $params) ? $params['onclick'] : null;
    $onmousedown = array_key_exists('onmousedown', $params) ? $params['onmousedown'] : null;

    if ($params['src'] == '') {
        $result = __('No media item found in this album', $dom);
    } else if ($isThumbnail) {
        $onclickHtml = $onclick != null ? " onclick=\"$onclick\"" : '';
        $onmousedownHtml = $onmousedown != null ? " onmousedown=\"$onmousedown\"" : '';
        $widthHtml = ($width == null ? '' : " width=\"$width\"");
        $heightHtml = ($height == null ? '' : " height=\"$height\"");
        $classHtml = ($class == null ? '' : " class=\"$class\"");
        $styleHtml = ($style == null ? '' : " style=\"$style\"");
        $idHtml = array_key_exists('id', $params) ? " id=\"$params[id]\"" : '';
        $result = "<img src=\"$src\" alt=\"$title\"$idHtml$widthHtml$heightHtml$classHtml$styleHtml$onclickHtml$onmousedownHtml/>";
    } else {
        if (!pnModAPILoad('mediashare', 'mediahandler')) {
            return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare mediahandler API');
        }
        $handler = pnModAPIFunc('mediashare', 'mediahandler', 'loadHandler', array('handlerName' => $params['mediaHandler']));
        if ($handler === false) {
            return mediashareErrorAPIGet();
        }
        $result = $handler->getMediaDisplayHtml($src, $width, $height, $id, array('title' => $title, 'onclick' => $onclick, 'onmousedown' => $onmousedown, 'class' => $class, 'style' => $style));
    }

    if (array_key_exists('assign', $params)) {
        $smarty->assign($params['assign'], $result);
    } else {
        return $result;
    }
}

