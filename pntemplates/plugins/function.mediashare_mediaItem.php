<?php

function smarty_function_mediashare_mediaItem($params, &$smarty)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    pnModLoad('mediashare', 'user');
    $mediaBase = pnModAPIFunc('mediashare', 'user', 'getRelativeMediadir');

    // Check for absolute URLs returned by external apps.
    $src = (substr($params['src'], 0, 4) == 'http') ? $params['src'] : pnGetBaseURL() . $mediaBase . htmlspecialchars($params['src']);

    $title       = isset($params['title']) ? $params['title'] : '';
    $id          = isset($params['id']) ? $params['id'] : null;
    $isThumbnail = isset($params['isThumbnail']) ? (bool)$params['isThumbnail'] : false;
    $width       = isset($params['width']) ? $params['width'] : null;
    $height      = isset($params['height']) ? $params['height'] : null;
    $class       = isset($params['class']) ? $params['class'] : null;
    $style       = isset($params['style']) ? $params['style'] : null;
    $onclick     = isset($params['onclick']) ? $params['onclick'] : null;
    $onmousedown = isset($params['onmousedown']) ? $params['onmousedown'] : null;

    if ($params['src'] == '') {
        $result = __('No media item found in this album', $dom);

    } else if ($isThumbnail) {
        $onclickHtml     = $onclick != null ? " onclick=\"$onclick\"" : '';
        $onmousedownHtml = $onmousedown != null ? " onmousedown=\"$onmousedown\"" : '';
        $widthHtml       = $width == null ? '' : " width=\"$width\"";
        $heightHtml      = $height == null ? '' : " height=\"$height\"";
        $classHtml       = $class == null ? '' : " class=\"$class\"";
        $styleHtml       = $style == null ? '' : " style=\"$style\"";
        $idHtml          = isset($params['id']) ? " id=\"$params[id]\"" : '';

        $result = "<img src=\"$src\" alt=\"$title\"$idHtml$widthHtml$heightHtml$classHtml$styleHtml$onclickHtml$onmousedownHtml/>";

    } else {
        $handler = pnModAPIFunc('mediashare', 'mediahandler', 'loadHandler', array('handlerName' => $params['mediaHandler']));
        if ($handler === false) {
            return false;
        }
        $result = $handler->getMediaDisplayHtml($src, $width, $height, $id, array('title' => $title, 'onclick' => $onclick, 'onmousedown' => $onmousedown, 'class' => $class, 'style' => $style));
    }

    if (isset($params['assign'])) {
        $smarty->assign($params['assign'], $result);
    }

    return $result;
}
