<?php
// $Id: pnmedia_imagegdapi.php,v 1.20 2008/02/22 19:07:56 jornlind Exp $
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2005.
// =======================================================================


//require_once 'modules/mediashare/mediaHandler.php';


class mediashare_extapp
{
    function getTitle()
    {
        return 'Mediashare External Application Handler';
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
        if ((string) (int) $width == "$width")
            $width = "{$width}px";
        $widthHtml = ($width == null ? '' : " width:{$width}");
        $heightHtml = ($height == null ? '' : " height:$height");
        $onclickHtml = (empty($args['onclick']) ? '' : " onclick=\"$args[onclick]\"");
        $classHtml = (empty($args['class']) ? '' : " class=\"$args[class]\"");
        $idHtml = ($id != '' ? " id=\"$id\"" : '');
        $style = " style=\"$widthHtml$heightHtml\"";
        $title = (isset($args['title']) ? $args['title'] : '');

        $html = "<img src=\"$url\"$style$idHtml title=\"$title\" alt=\"$title\"$onclickHtml$classHtml/>";

        if (isset($args['url'])) {
            $rel = (isset($args['urlRel']) ? " rel=\"$args[urlRel]\"" : '');
            $html = "<a href=\"$args[url]\"$rel>$html</a>";
        }

        return $html;
    }
}
;

function mediashare_media_extappapi_buildHandler($args)
{
    return new mediashare_extapp();
}

