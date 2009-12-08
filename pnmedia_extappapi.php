<?php
// $Id$
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2005.
// =======================================================================

class mediashare_extapp
{
    function getTitle()
    {
        $dom = ZLanguage::getModuleDomain('mediashare');
        return __('Mediashare External Application Handler', $dom);
    }

    function getMediaTypes()
    {
        return array();
    }

    function createPreviews($args, $previews)
    {
        return array();
    }

    function getMediaDisplayHtml($url, $width, $height, $id, $args)
    {
        if ((string)(int)$width == "$width") {
            $width = "{$width}px";
        }
        $widthHtml   = ($width == null ? '' : " width:{$width}");
        $heightHtml  = ($height == null ? '' : " height:$height");
        $onclickHtml = (empty($args['onclick']) ? '' : " onclick=\"$args[onclick]\"");
        $classHtml   = (empty($args['class']) ? '' : " class=\"$args[class]\"");
        $idHtml      = ($id != '' ? " id=\"$id\"" : '');
        $style       = " style=\"$widthHtml$heightHtml\"";
        $title       = (isset($args['title']) ? $args['title'] : '');

        $html = "<img src=\"$url\"$style$idHtml title=\"$title\" alt=\"$title\"$onclickHtml$classHtml/>";

        if (isset($args['url'])) {
            $rel  = (isset($args['urlRel']) ? " rel=\"$args[urlRel]\"" : '');
            $html = "<a href=\"$args[url]\"$rel>$html</a>";
        }

        return $html;
    }
}

function mediashare_media_extappapi_buildHandler($args)
{
    return new mediashare_extapp();
}
