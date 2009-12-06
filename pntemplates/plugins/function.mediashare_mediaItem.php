<?php

function smarty_function_mediashare_mediaItem($params, &$smarty)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    pnModLoad('mediashare', 'user');
    $mediaBase = pnModAPIFunc('mediashare', 'user', 'getRelativeMediadir');

    // Check for absolute URLs returned by external apps.
    $src = (substr($params['src'], 0, 4) == 'http') ? $params['src'] : pnGetBaseURL() . $mediaBase . htmlspecialchars($params['src']);

    $title       = isset($params['title']) ? $params['title'] : '';
    $id          = array_key_exists($params['id']) ? $params['id'] : null;
    $isThumbnail = array_key_exists($params['isThumbnail']) ? (bool) $params['isThumbnail'] : false;
    $width       = array_key_exists($params['width']) ? $params['width'] : null;
    $height      = array_key_exists($params['height']) ? $params['height'] : null;
    $class       = array_key_exists($params['class']) ? $params['class'] : null;
    $style       = array_key_exists($params['style']) ? $params['style'] : null;
    $onclick     = array_key_exists($params['onclick']) ? $params['onclick'] : null;
    $onmousedown = array_key_exists($params['onmousedown']) ? $params['onmousedown'] : null;

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
        $handler = pnModAPIFunc('mediashare', 'mediahandler', 'loadHandler', array('handlerName' => $params['mediaHandler']));
        if ($handler === false) {
            return false;
        }
        $result = $handler->getMediaDisplayHtml($src, $width, $height, $id, array('title' => $title, 'onclick' => $onclick, 'onmousedown' => $onmousedown, 'class' => $class, 'style' => $style));
    }

    if (array_key_exists('assign', $params)) {
        $smarty->assign($params['assign'], $result);
    }

    return $result;
}
