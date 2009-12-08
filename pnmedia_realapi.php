<?php
// $Id$
// =======================================================================
// Mediashare by Jorn Wildt (C) 2005.
// =======================================================================

class mediashare_realHandler
{
    function getTitle()
    {
        $dom = ZLanguage::getModuleDomain('mediashare');
        return __('Mediashare Real Player Handler', $dom);
    }

    function getMediaTypes()
    {
        return array(
            array('mimeType' => 'application/vnd.rn-realmedia',
                  'fileType' => 'rm',
                  'foundMimeType' => 'application/vnd.rn-realmedia',
                  'foundFileType' => 'rm'),
            array('mimeType' => 'video/vnd.rn-realvideo',
                  'fileType' => 'rv',
                  'foundMimeType' => 'video/vnd.rn-realvideo',
                  'foundFileType' => 'rv'),
            array('mimeType' => 'audio/x-pn-realaudio',
                  'fileType' => 'ra',
                  'foundMimeType' => 'audio/x-pn-realaudio',
                  'foundFileType' => 'ra'));
    }

    function createPreviews($args, $previews)
    {
        $mediaFilename = $args['mediaFilename'];
        $mimeType = $args['mimeType'];
        $mediaFileType = $args['fileType'];

        $result = array();

        foreach ($previews as $preview)
        {
            if ($preview['isThumbnail']) {
                copy('modules/mediashare/pnimages/logo_real_player.png', $preview['outputFilename']);
                $imPreview = @imagecreatefrompng($preview['outputFilename']);
                $result[] = array('fileType' => 'png',
                                  'mimeType' => 'image/png',
                                  'width'    => imagesx($imPreview),
                                  'height'   => imagesy($imPreview),
                                  'bytes'    => filesize($preview['outputFilename']));
                imagedestroy($imPreview);
            } else {
                $width = $preview['imageSize'];
                $height = $preview['imageSize'];
                if (isset($args['width']) && (int)$args['width'] > 0 && isset($args['height']) && (int)$args['height'] > 0) {
                    $w = (int)$args['width'];
                    $h = (int)$args['height'];

                    if ($w < $width || $h < $height) {
                        $width = $w;
                        $height = $h;
                    } else if ($w > $h) {
                        $height = ($h / $w) * $height;
                    } else {
                        $width = ($w / $h) * $width;
                    }
                }

                copy($mediaFilename, $preview['outputFilename']);
                $result[] = array('fileType' => $mediaFileType,
                                  'mimeType' => $mimeType,
                                  'width'    => $width,
                                  'height'   => $height,
                                  'bytes'    => filesize($preview['outputFilename']));
            }
        }

        $width  = isset($args['width']) && (int)$args['width'] > 0   ? (int)$args['width']  : $preview['imageSize'];
        $height = isset($args['height']) && (int)$args['height'] > 0 ? (int)$args['height'] : $preview['imageSize'];

        $result[] = array('fileType' => $mediaFileType,
                          'mimeType' => $mimeType,
                          'width'    => $width,
                          'height'   => $height,
                          'bytes'    => filesize($mediaFilename));

        return $result;
    }

    function getMediaDisplayHtml($url, $width, $height, $id, $args)
    {
        $dom = ZLanguage::getModuleDomain('mediashare');

        $widthHtml = ($width == null ? '' : " width=\"$width\"");
        $heightHtml = ($height == null ? '' : " height=\"$height\"");

        $output = '<object classid="clsid:CFCDAA03-8BE4-11cf-B84B-0020AFBBCCFA"'
                 .' id="'.$id.'"'.$widthHtml.$heightHtml
                 .' standby="'.__('Loading Real Player components...', $dom).'"'
                 .' type="application/x-oleobject">'
                 .'<param name="src" value="'.$url.'">'
                 .'<param name="AutoStart" value="false">'
                 .'<param name="MaintainAspect" value="1">'
                 .'<embed type="audio/x-pn-realaudio-plugin" src="'.$url.'"'.$widthHtml.$heightHtml
                 .' autostart="false" align="center" MaintainAspect="1">'
                 .'</embed>'
                 .'</object>';

        return $output;
    }
}

function mediashare_media_realapi_buildHandler($args)
{
    return new mediashare_realHandler();
}
