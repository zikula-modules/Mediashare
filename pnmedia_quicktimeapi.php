<?php
// $Id$
// =======================================================================
// Mediashare by Jorn Wildt (C) 2005.
// =======================================================================


class mediashare_quicktimeHandler
{
    function getTitle()
    {
        return 'Mediashare Quicktime Handler';
    }

    function getMediaTypes()
    {
        return array(
            array('mimeType' => 'video/quicktime', 'fileType' => 'mov', 'foundMimeType' => 'video/quicktime', 'foundFileType' => 'mov'),
            array('mimeType' => 'video/avi', 'fileType' => 'avi', 'foundMimeType' => 'video/avi', 'foundFileType' => 'avi'),
            array('mimeType' => 'audio/wav', 'fileType' => 'wav', 'foundMimeType' => 'audio/wav', 'foundFileType' => 'wav'),
            array('mimeType' => 'audio/mpg', 'fileType' => 'mpg', 'foundMimeType' => 'audio/mpeg', 'foundFileType' => 'mpeg'),
            array('mimeType' => 'audio/mpeg', 'fileType' => 'mpeg', 'foundMimeType' => 'audio/mpeg', 'foundFileType' => 'mpeg'),
            array('mimeType' => 'audio/mpeg3', 'fileType' => 'mpeg3', 'foundMimeType' => 'audio/mpeg3', 'foundFileType' => 'mpeg3'),
            array('mimeType' => 'audio/mpg3', 'fileType' => 'mpg3', 'foundMimeType' => 'audio/mpeg3', 'foundFileType' => 'mpeg3'),
            array('mimeType' => 'audio/mp3', 'fileType' => 'mp3', 'foundMimeType' => 'audio/mpeg3', 'foundFileType' => 'mpeg3'),
            array('mimeType' => 'audio/mpeg4', 'fileType' => 'mpeg4', 'foundMimeType' => 'audio/mpeg4', 'foundFileType' => 'mpeg4'),
            array('mimeType' => 'audio/mpg4', 'fileType' => 'mpg4', 'foundMimeType' => 'audio/mpeg4', 'foundFileType' => 'mpeg4'),
            array('mimeType' => 'audio/mp4', 'fileType' => 'mp4', 'foundMimeType' => 'audio/mpeg4', 'foundFileType' => 'mpeg4'));
    }

    function createPreviews($args, $previews)
    {
        $mediaFilename = $args['mediaFilename'];
        $mimeType = $args['mimeType'];
        $mediaFileType = $args['fileType'];

        $result = array();

        foreach ($previews as $preview) {
            if ($preview['isThumbnail']) {
                copy('modules/mediashare/pnimages/logo_quicktime_player.png', $preview['outputFilename']);
                $imPreview = @imagecreatefrompng($preview['outputFilename']);
                $result[] = array('fileType' => 'png', 'mimeType' => 'image/png', 'width' => imagesx($imPreview), 'height' => imagesy($imPreview), 'bytes' => filesize($preview['outputFilename']));
                imagedestroy($imPreview);
            } else {
                $width = $preview['imageSize'];
                $height = $preview['imageSize'];
                if (array_key_exists('width', $args) && (int) $args['width'] > 0 && array_key_exists('height', $args) && (int) $args['height'] > 0) {
                    $w = (int) $args['width'];
                    $h = (int) $args['height'];

                    if ($w < $width || $h < $height) {
                        $width = $w;
                        $height = $h;
                    } else if ($w > $h)
                        $height = ($h / $w) * $height;
                    else
                        $width = ($w / $h) * $width;
                }

                $result[] = array('fileType' => $mediaFileType, 'mimeType' => $mimeType, 'width' => $width, 'height' => $height, 'useOriginal' => true, 'bytes' => filesize($preview['outputFilename']));
            }
        }

        $width = (array_key_exists('width', $args) && (int) $args['width'] > 0 ? (int) $args['width'] : $preview['imageSize']);
        $height = (array_key_exists('height', $args) && (int) $args['height'] > 0 ? (int) $args['height'] : $preview['imageSize']);

        $result[] = array('fileType' => $mediaFileType, 'mimeType' => $mimeType, 'width' => $width, 'height' => $height, 'bytes' => filesize($mediaFilename));

        return $result;
    }

    function getMediaDisplayHtml($url, $width, $height, $id, $args)
    {
        $widthHtml = ($width == null ? '' : " width=\"$width\"");
        $heightHtml = ($height == null ? '' : " height=\"" . ($height + 16) . "\"");

        return "<object classid=\"clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B\"
id=\"$id\"$widthHtml$heightHtml
codebase=\"http://www.apple.com/qtactivex/qtplugin.cab\">
<param name=\"src\" value=\"$url\"/>
<param name=\"Scale\" value=\"aspect\"/>
<param name=\"AutoPlay\" value=\"false\"/>
<param name=\"Controller\" value=\"true\"/>
<embed src=\"$url\" Autoplay=\"false\" Controller=\"true\"$widthHtml$heightHtml scale=\"aspect\"
pluginspage=\"http://www.apple.com/quicktime/download\">
</embed>
</object>";
    }

// Internal functions
}
;

function mediashare_media_quicktimeapi_buildHandler($args)
{
    return new mediashare_quicktimeHandler();
}

