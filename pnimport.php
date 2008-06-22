<?php
// $Id: pnimport.php,v 1.2 2006/03/12 11:57:48 jornlind Exp $
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2005.
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

require_once 'modules/mediashare/common-edit.php';
require_once 'modules/mediashare/elfisk/elfisk_common.php';
require_once 'modules/mediashare/elfisk/elfiskRender.class.php';


function mediashare_import_main($args)
{
  if (!pnSecAuthAction(0, 'mediashare::', '::', ACCESS_ADMIN))
    return mediashareErrorPage(__FILE__, __LINE__, _MSNOAUTH);

  $render = new elfiskRender('mediashare');
  $render->caching = false;

  return $render->fetch('mediashare_import_main.html'); 
}


// =======================================================================
// Import Photoshare
// =======================================================================

function mediashare_import_photoshare($args)
{
  if (!pnSecAuthAction(0, 'mediashare::', '::', ACCESS_ADMIN))
    return mediashareErrorAPI(__FILE__, __LINE__, _MSNOAUTH);

  if (isset($_POST['importButton']))
    return mediashareImportPhotoshare();

  if (isset($_POST['cancelButton']))
  {
    pnRedirect(pnModURL('mediashare', 'import', 'main'));
    return true;
  }

  $render = new elfiskRender('mediashare');
  $render->caching = false;

  return $render->fetch('mediashare_import_photoshare.html'); 
}


function mediashareImportPhotoshare()
{
  if (!pnModAPILoad('mediashare', 'import'))
    return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare import API');

  $ok = pnModAPIFunc('mediashare', 'import', 'photoshare');
  if ($ok === false)
    return mediashareErrorAPIGet();

  pnRedirect(pnModURL('mediashare', 'import', 'main'));
  return true;
}

?>