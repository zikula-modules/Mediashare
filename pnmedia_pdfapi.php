<?php
// $Id: pnmedia_pdfapi.php,v 1.1 2006/09/10 17:19:36 jornlind Exp $
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2005.
// =======================================================================


//require_once 'modules/mediashare/mediaHandler.php';


class mediashare_pdfHandler
{
    function getTitle()
    {
        return 'Mediashare PDF Handler';
    }

    function getMediaTypes()
    {
        return array(array('mimeType' => 'application/pdf', 'fileType' => 'pdf', 'foundMimeType' => 'application/pdf', 'foundFileType' => 'pdf'));
    }

    function createPreviews($args, $previews)
    {
        $mediaFilename = $args['mediaFilename'];
        $mimeType = $args['mimeType'];
        $mediaFileType = $args['fileType'];

        $result = array();

        foreach ($previews as $preview) {
            if ($preview['isThumbnail']) {
                copy('modules/mediashare/pnimages/logo_pdf_appl.gif', $preview['outputFilename']);
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
        //$width="100px"; $height="100px";
        $widthHtml = ($width == null ? '' : " width=\"$width\"");
        $heightHtml = ($height == null ? '' : " height=\"$height\"");

        return "
                        <param name=\"src\" value=\"$url\">
                        <a href=\"$url\">Link</a>
                        ";
    }

// Internal functions
}
;

function mediashare_media_pdfapi_buildHandler($args)
{
    return new mediashare_pdfHandler();
}

