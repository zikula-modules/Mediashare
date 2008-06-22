<?php
// $Id: pnmedia_realapi.php,v 1.2 2006/03/26 18:01:55 jornlind Exp $
// =======================================================================
// Mediashare by Jorn Wildt (C) 2005.
// ----------------------------------------------------------------------
// For POST-NUKE Content Management System
// Copyright (C) 2002 by the PostNuke Development Team.
// http://www.postnuke.com/
// ----------------------------------------------------------------------
// LICENSE
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License (GPL)
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WIthOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// To read the license please visit http://www.gnu.org/copyleft/gpl.html
// =======================================================================

class mediashare_realHandler
{
  function getTitle()
  {
    return 'Mediashare Real Player Handler';
  }


  function getMediaTypes()
  {
    return array( 
      array('mimeType' => 'application/vnd.rn-realmedia', 'fileType' => 'rm',
            'foundMimeType' => 'application/vnd.rn-realmedia', 'foundFileType' => 'rm'),
      array('mimeType' => 'video/vnd.rn-realvideo', 'fileType' => 'rv',
            'foundMimeType' => 'video/vnd.rn-realvideo', 'foundFileType' => 'rv'),
      array('mimeType' => 'audio/x-pn-realaudio', 'fileType' => 'ra',
            'foundMimeType' => 'audio/x-pn-realaudio', 'foundFileType' => 'ra')
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
        copy('modules/mediashare/pnimages/logo_real_player.png', $preview['outputFilename']);
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
    $widthHtml = ($width == null ? '' : " width=\"$width\"");
    $heightHtml = ($height == null ? '' : " height=\"$height\"");

    return "<object classid=\"clsid:CFCDAA03-8BE4-11cf-B84B-0020AFBBCCFA\"
id=\"$id\"$widthHtml$heightHtml
standby=\"Loading Real Player components...\"
type=\"application/x-oleobject\">
<param name=\"src\" value=\"$url\">
<param name=\"AutoStart\" value=\"false\">
<param name=\"MaintainAspect\" value=\"1\">
<embed type=\"audio/x-pn-realaudio-plugin\" src=\"$url\"$widthHtml$heightHtml
 autostart=\"false\" align=\"center\" MaintainAspect=\"1\">
</embed>
</object>";
  }


  // Internal functions
};


function mediashare_media_realapi_buildHandler($args)
{
  return new mediashare_realHandler();
}

?>