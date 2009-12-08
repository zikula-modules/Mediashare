<?php
// $Id$
//
// Mediashare by Jorn Lind-Nielsen (C)
//

class mediashare_pdfHandler
{
    function getTitle()
    {
        $dom = ZLanguage::getModuleDomain('mediashare');
        return __('Mediashare PDF Handler', $dom);
    }

    function getMediaTypes()
    {
        return array(array('mimeType' => 'application/pdf',
                           'fileType' => 'pdf',
                           'foundMimeType' => 'application/pdf',
                           'foundFileType' => 'pdf'));
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
                copy('modules/mediashare/pnimages/logo_pdf_appl.gif', $preview['outputFilename']);
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

                $result[] = array('fileType' => $mediaFileType,
                                  'mimeType' => $mimeType,
                                  'width'    => $width,
                                  'height'   => $height,
                                  'useOriginal' => true,
                                  'bytes' => filesize($preview['outputFilename']));
            }
        }

        $width  = (isset($args['width']) && (int)$args['width'] > 0   ? (int)$args['width']  : $preview['imageSize']);
        $height = (isset($args['height']) && (int)$args['height'] > 0 ? (int)$args['height'] : $preview['imageSize']);

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

        //$width="100px"; $height="100px";
        $widthHtml  = ($width == null  ? '' : " width=\"$width\"");
        $heightHtml = ($height == null ? '' : " height=\"$height\"");

        // FIXME Incomplete plugin?
        return '<a href="'.$url.'">'.__('PDF Link', $dom).'</a>';
    }
}

function mediashare_media_pdfapi_buildHandler($args)
{
    return new mediashare_pdfHandler();
}
