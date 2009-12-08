<?php
// $Id$
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2005.
// =======================================================================

class mediashare_imageHandlerGD
{
    function getTitle()
    {
        $dom = ZLanguage::getModuleDomain('mediashare');
        return __('Mediashare GD Image Handler', $dom);
    }

    function getMediaTypes()
    {
        return array(
            array('mimeType' => 'image/pjpeg',
                  'fileType' => 'jpg',
                  'foundMimeType' => 'image/jpeg',
                  'foundFileType' => 'jpg'),
            array('mimeType' => 'image/jpeg',
                  'fileType' => 'jpeg',
                  'foundMimeType' => 'image/jpeg',
                  'foundFileType' => 'jpg'),
            array('mimeType' => 'image/png',
                  'fileType' => 'png',
                  'foundMimeType' => 'image/png',
                  'foundFileType' => 'png'),
            array('mimeType' => 'image/gif',
                  'fileType' => 'gif',
                  'foundMimeType' => 'image/gif',
                  'foundFileType' => 'gif'));
    }

    function createPreviews($args, $previews)
    {
        $mediaFilename = $args['mediaFilename'];
        $mimeType = $args['mimeType'];
        $mediaFileType = $args['fileType'];

        $originalImageData = $this->createImageFromFile($mediaFilename, $mimeType);
        if ($originalImageData == false) {
            return false;
        }

        $result = array();

        foreach ($previews as $preview)
        {
            $imPreview = $this->createResizedImage($originalImageData, $mimeType, $preview['imageSize']);

            if ($mimeType == 'image/jpeg' && !$preview['isThumbnail']) {
                imagejpeg($imPreview, $preview['outputFilename'], 90);
                $result[] = array('fileType' => 'jpg',
                                  'mimeType' => 'image/jpg',
                                  'width'    => imagesx($imPreview),
                                  'height'   => imagesy($imPreview),
                                  'bytes'    => filesize($preview['outputFilename']));
            } else {
                imagepng($imPreview, $preview['outputFilename']);
                $result[] = array('fileType' => 'png',
                                  'mimeType' => 'image/png',
                                  'width'    => imagesx($imPreview),
                                  'height'   => imagesy($imPreview),
                                  'bytes'    => filesize($preview['outputFilename']));
            }

            imagedestroy($imPreview);
        }

        $result[] = array('fileType' => $mediaFileType,
                          'mimeType' => $mimeType,
                          'width'    => imagesx($originalImageData),
                          'height'   => imagesy($originalImageData),
                          'bytes' => filesize($mediaFilename));

        imagedestroy($originalImageData);

        return $result;
    }

    function getMediaDisplayHtml($url, $width, $height, $id, $args)
    {
        if ((string) (int)$width == "$width") {
            $width = "{$width}px";
        }
        $widthHtml   = ($width == null ? '' : " width:{$width}");
        $heightHtml  = ($height == null ? '' : " height:$height");
        $onclickHtml = (empty($args['onclick']) ? '' : " onclick=\"$args[onclick]\"");
        $classHtml   = (empty($args['class']) ? '' : " class=\"$args[class]\"");
        $idHtml      = ($id != '' ? " id=\"$id\"" : '');
        $style       = " style=\"$widthHtml$heightHtml\"";
        $title       = (isset($args['title']) ? $args['title'] : '');

        $url  = pnGetBaseURI() . '/' . $url;
        $html = "<img src=\"$url\"$style$idHtml title=\"$title\" alt=\"$title\"$onclickHtml$classHtml/>";

        if (isset($args['url'])) {
            $rel  = (isset($args['urlRel']) ? " rel=\"$args[urlRel]\"" : '');
            $html = "<a href=\"$args[url]\"$rel>$html</a>";
        }

        return $html;
    }

    // Internal functions
    function createImageFromFile($filename, $mimeType)
    {
        $dom = ZLanguage::getModuleDomain('mediashare');

        $im = false;

        if ($mimeType == 'image/jpeg') {
            $im = @imagecreatefromjpeg($filename);
        } else if ($mimeType == 'image/gif') {
            $im = @imagecreatefromgif($filename);
        } else if ($mimeType == 'image/png') {
            $im = @imagecreatefrompng($filename);
        }

        if (!$im) {
            return LogUtil::registerError(__('Unknown image format %1$s/%2$s.', array($mimeType, $filename), $dom));
        }

        return $im;
    }

    function createResizedImage(&$im, $mimeType, $newSize)
    {
        $xs = imagesx($im);
        $ys = imagesy($im);

        // Calculate thumbnail X and Y sizes
        if ($xs > $ys) {
            $resizedXSize = $newSize;
            $resizedYSize = ($ys * $newSize) / $xs;
        } else {
            $resizedYSize = $newSize;
            $resizedXSize = ($xs * $newSize) / $ys;
        }

        // If thumbnail area becomes bigger than original, then use original's size
        if ($resizedXSize * $resizedYSize >= $xs * $ys) {
            $resizedXSize = $xs;
            $resizedYSize = $ys;
        }

        $isTrueColor = false;
        $thumbnail = $this->createEmptyImage($resizedXSize, $resizedYSize, $mimeType, $isTrueColor);

        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);

        $white = imagecolorallocate($thumbnail, 255, 255, 255); // First allocated - becomes background
        $gray  = imagecolorallocate($thumbnail, 100, 100, 100);

        // Copy resized image into center of thumbnail
        if ($isTrueColor && function_exists("imagecopyresampled")) {
            if (!@imagecopyresampled($thumbnail, $im, 0, 0, 0, 0, $resizedXSize, $resizedYSize, $xs, $ys)) {
                mediashareImageCopyResampleBicubic($thumbnail, $im, 0, 0, 0, 0, $resizedXSize, $resizedYSize, $xs, $ys);
            }
        } else {
            imagecopyresized($thumbnail, $im, 0, 0, 0, 0, $resizedXSize, $resizedYSize, $xs, $ys);
        }

        if (pnModGetVar('mediashare', 'enableSharpen')) {
            $thumbnailEnhanced = $this->UnsharpMask($thumbnail, 80, .5, 3);
        } else {
            $thumbnailEnhanced = $thumbnail;
        }

        return $thumbnailEnhanced;
    }

    // Unsharp mask algorithm by Torstein H�nsi 2003 (thoensi_at_netcom_dot_no)
    // Modified by Christoph Erdmann: changed it a little, cause i could not reproduce the
    //                                darker blurred image, now it is up to 15% faster with same results
    //   See http://www.cerdmann.com/thumb/
    // Adopted for Mediashare by Jorn Wildt
    function UnsharpMask($img, $amount, $radius, $threshold)
    {
        // Attempt to calibrate the parameters to Photoshop:
        if ($amount > 500) {
            $amount = 500;
        }
        $amount = $amount * 0.016;

        if ($radius > 50) {
            $radius = 50;
        }
        $radius = $radius * 2;

        if ($threshold > 255) {
            $threshold = 255;
        }

        $radius = abs(round($radius)); // Only integers make sense.
        if ($radius == 0) {
            return null;
        }

        $w = imagesx($img);
        $h = imagesy($img);
        $imgCanvas = $img;
        $imgCanvas2 = $img;
        $imgBlur = imagecreatetruecolor($w, $h);

        // Gaussian blur matrix:
        //  1   2   1
        //  2   4   2
        //  1   2   1

        // Move copies of the image around one pixel at the time and merge them with weight
        // according to the matrix. The same matrix is simply repeated for higher radii.
        for ($i = 0; $i < $radius; $i++) {
            imagecopy($imgBlur, $imgCanvas, 0, 0, 1, 1, $w - 1, $h - 1); // up left
            imagecopymerge($imgBlur, $imgCanvas, 1, 1, 0, 0, $w, $h, 50); // down right
            imagecopymerge($imgBlur, $imgCanvas, 0, 1, 1, 0, $w - 1, $h, 33.33333); // down left
            imagecopymerge($imgBlur, $imgCanvas, 1, 0, 0, 1, $w, $h - 1, 25); // up right
            imagecopymerge($imgBlur, $imgCanvas, 0, 0, 1, 0, $w - 1, $h, 33.33333); // left
            imagecopymerge($imgBlur, $imgCanvas, 1, 0, 0, 0, $w, $h, 25); // right
            imagecopymerge($imgBlur, $imgCanvas, 0, 0, 0, 1, $w, $h - 1, 20); // up
            imagecopymerge($imgBlur, $imgCanvas, 0, 1, 0, 0, $w, $h, 16.666667); // down
            imagecopymerge($imgBlur, $imgCanvas, 0, 0, 0, 0, $w, $h, 50); // center
        }

        $imgCanvas = $imgBlur;

        // Calculate the difference between the blurred pixels and the original and set the pixels
        for ($x = 0; $x < $w; $x++) {
            // each row
            for ($y = 0; $y < $h; $y++) {
                // each pixel
                $rgbOrig = ImageColorAt($imgCanvas2, $x, $y);
                $rOrig = (($rgbOrig >> 16) & 0xFF);
                $gOrig = (($rgbOrig >> 8) & 0xFF);
                $bOrig = ($rgbOrig & 0xFF);

                $rgbBlur = ImageColorAt($imgCanvas, $x, $y);
                $rBlur = (($rgbBlur >> 16) & 0xFF);
                $gBlur = (($rgbBlur >> 8) & 0xFF);
                $bBlur = ($rgbBlur & 0xFF);

                // When the masked pixels differ less from the original
                // than the threshold specifies, they are set to their original value.
                $rNew = (abs($rOrig - $rBlur) >= $threshold) ? max(0, min(255, ($amount * ($rOrig - $rBlur)) + $rOrig)) : $rOrig;
                $gNew = (abs($gOrig - $gBlur) >= $threshold) ? max(0, min(255, ($amount * ($gOrig - $gBlur)) + $gOrig)) : $gOrig;
                $bNew = (abs($bOrig - $bBlur) >= $threshold) ? max(0, min(255, ($amount * ($bOrig - $bBlur)) + $bOrig)) : $bOrig;

                if (($rOrig != $rNew) || ($gOrig != $gNew) || ($bOrig != $bNew)) {
                    $pixCol = ImageColorAllocate($img, $rNew, $gNew, $bNew);
                    ImageSetPixel($img, $x, $y, $pixCol);
                }
            }
        }

        return $img;
    }

    function createEmptyImage($xsize, $ysize, $mimeType, &$isTrueColor)
    {
        // Detect presense of ImageCreateTrueColor() - although this apparently doesn't say anything about GD2 existing ...
        $hasTrueColorImage = false;
        $gdfunctions = get_extension_funcs("gd");
        foreach ($gdfunctions as $f) {
            if ($f == 'imagecreatetruecolor') {
                $hasTrueColorImage = true;
            }
        }

        // If the check for imagecreatetruecolor fails then insert the next line
        // $hasTrueColorImage = false;

        $isTrueColor = false;

        // Create actual image
        if (/*$mimeType == 'image/gif'  ||  */!$hasTrueColorImage) {
            $image = ImageCreate($xsize, $ysize); // Create white background image
        } else {
            $image = @ImageCreateTrueColor($xsize, $ysize); // Create black background image

            // Didn't work ... perhaps ImageCreateTrueColor doesn't exist?
            if (!$image) {
                $image = ImageCreate($xsize, $ysize); // Create white background image
            } else {
                $isTrueColor = true;
            }
        }

        return $image;
    }
}

function mediashare_media_imagegdapi_buildHandler($args)
{
    return new mediashare_imageHandlerGD();
}
