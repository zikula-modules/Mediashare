<?php
// $Id: pnshow.php,v 1.2 2007/12/30 08:58:37 jornlind Exp $
// =======================================================================
// Photoshare by Jorn Lind-Nielsen (C) 2002-2004.
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
