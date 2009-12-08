<?php
// $Id$
//
// Mediashare by Jorn Lind-Nielsen (C)
//

class mediashare_flashHandler
{
    function getTitle()
    {
        $dom = ZLanguage::getModuleDomain('mediashare');
        return __('Mediashare Flash Handler', $dom);
    }

    function getMediaTypes()
    {
        return array(array('mimeType' => 'application/x-shockwave-flash',
                           'fileType' => 'swf',
                           'foundMimeType' => 'application/x-shockwave-flash',
                           'foundFileType' => 'swf'));
    }

    function createPreviews($args, $previews)
    {
        $mediaFilename = $args['mediaFilename'];
        $mimeType      = $args['mimeType'];
        $mediaFileType = $args['fileType'];

        $result = array();

        foreach ($previews as $preview)
        {
            if ($preview['isThumbnail']) {
                copy('modules/mediashare/pnimages/logo_flash_player.png', $preview['outputFilename']);
                $imPreview = @imagecreatefrompng($preview['outputFilename']);
                $result[]  = array('fileType' => 'png', 'mimeType' => 'image/png', 'width' => imagesx($imPreview), 'height' => imagesy($imPreview), 'bytes' => filesize($preview['outputFilename']));
                imagedestroy($imPreview);
            } else {
                $width  = $preview['imageSize'];
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

                $result[] = array('fileType' => $mediaFileType,
                                  'mimeType' => $mimeType,
                                  'width'    => $width,
                                  'height'   => $height,
                                  'useOriginal' => true,
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
        //$width="100px"; $height="100px";
        $widthHtml  = ($width == null  ? '' : " width=\"$width\"");
        $heightHtml = ($height == null ? '' : " height=\"$height\"");

        $output = '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"'
                 .' codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab"'
                 .$widthHtml.$heightHtml.' id="'.$id.'">'
                 .'<param name="movie" value="'.$url.'">'
                 .'<param name="quality" value="high">'
                 .'<param name="bgcolor" value="#FFFFFF">'
                 .'<embed src="'.$url.'" quality="high" bgcolor="#FFFFFF"'.$widthHtml.$heightHtml
                 .' type="application/x-shockwave-flash"'
                 .' pluginspage="http://www.macromedia.com/go/getflashplayer">'
                 .'</embed>'
                 .'</object>';

        return $output;
    }
}

function mediashare_media_flashapi_buildHandler($args)
{
    return new mediashare_flashHandler();
}
