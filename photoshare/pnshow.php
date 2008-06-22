<?php
// $Id: pnshow.php,v 1.2 2007/12/30 08:58:37 jornlind Exp $
// =======================================================================
// Photoshare by Jorn Lind-Nielsen (C) 2002-2004.
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

// This file contains just enough to display an image, with the sole purpose
// of reducing the load and CPU time needed for image display.

function photoshare_show_viewimage()
{
  $imageID   = pnVarCleanFromInput('iid');
  $thumbnail = intval(pnVarCleanFromInput('thumbnail'));

  if (!pnModAPILoad('mediashare', 'import'))
    return photoshareErrorPage(__FILE__, __LINE__, "Failed to load Mediashare import API");

  $mediashareUrl = pnModAPIFunc('mediashare', 'import', 'getMediashareUrl',
                                array('imageId' => $imageID,
                                      'thumbnail' => $thumbnail));

  pnRedirect($mediashareUrl);
  return true;
}

?>
