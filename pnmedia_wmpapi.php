<?php
// $Id: pnmedia_wmpapi.php,v 1.6 2007/08/13 19:52:27 jornlind Exp $
// =======================================================================
// Mediashare by Jorn Wildt (C) 2005.
// =======================================================================

class mediashare_wmpHandler
{
  function getTitle()
  {
    return 'Mediashare Windows Media Player Handler';
  }


  function getMediaTypes()
  {
    return array( 
      array('mimeType' => 'audio/x-ms-wma', 'fileType' => 'wma',
            'foundMimeType' => 'audio/x-ms-wmw', 'foundFileType' => 'wmv'),
      array('mimeType' => 'audio/x-ms-wma', 'fileType' => 'wmv',
            'foundMimeType' => 'audio/x-ms-wmv', 'foundFileType' => 'wmv')
      );
  }


  function createPreviews($args, $previews)
  {
    $mediaFilename = $args['mediaFilename'];
    $mimeType = $args['mimeType'];
    $mediaFileType = $args['fileType'];

    $result = array();

    foreach ($previews as $preview)
    {
      if ($preview['isThumbnail'])
      {
        copy('modules/mediashare/pnimages/logo_wmp_player.png', $preview['outputFilename']);
        $imPreview = @imagecreatefrompng($preview['outputFilename']);
        $result[] = array('fileType' => 'png', 
                          'mimeType' => 'image/png',
                          'width'    => imagesx($imPreview),
                          'height'   => imagesy($imPreview),
                          'bytes'    => filesize($preview['outputFilename']));
        imagedestroy($imPreview);
      }
      else
      {
        $width  = $preview['imageSize'];
        $height = $preview['imageSize'];
        if (    array_key_exists('width', $args) && (int)$args['width'] > 0
            &&  array_key_exists('height', $args) && (int)$args['height'] > 0)
        {
          $w = (int)$args['width'];
          $h = (int)$args['height'];

          if ($w < $width  ||  $h < $height)
          {
            $width = $w;
            $height = $h;
          }
          else if ($w > $h)
            $height = ($h/$w) * $height;
          else
            $width = ($w/$h) * $width;
        }

        copy($mediaFilename, $preview['outputFilename']);
        $result[] = array('fileType' => $mediaFileType, 
                          'mimeType' => $mimeType,
                          'width'    => $width,
                          'height'   => $height,
                          'bytes'    => filesize($preview['outputFilename']));
      }
    }

    $width  = (array_key_exists('width', $args) && (int)$args['width'] > 0 ? (int)$args['width'] : $preview['imageSize']);
    $height = (array_key_exists('height', $args) && (int)$args['height'] > 0 ? (int)$args['height'] : $preview['imageSize']);

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
    $widthHtml = ($width == null ? '' : " width=\"$width\"");
    $heightHtml = ($height == null ? '' : " height=\"$height\"");

    return "<object classid=\"clsid:22d6f312-b0f6-11d0-94ab-0080c74c7e95\"
id=\"$id\"$widthHtml$heightHtml
standby=\"Loading Microsoft Windows Media Player components...\"
type=\"application/x-oleobject\"
codebase=\"http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=5,1,52,701\">
<param name=\"fileName\" value=\"$url\">
<param name=\"AutoStart\" value=\"false\">
<param name=\"showControls\" value=\"true\">
<embed type=\"application/x-mplayer2\" src=\"$url\" name=\"MediaPlayer\"
ShowControls=\"1\" ShowStatusBar=\"0\" ShowDisplay=\"0\" autostart=\"0\"$widthHtml$heightHtml></embed>
</object>";
  }


  // Internal functions
};


function mediashare_media_wmpapi_buildHandler($args)
{
  return new mediashare_wmpHandler();
}

?>