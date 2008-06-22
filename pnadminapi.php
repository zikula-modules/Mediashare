<?php
// $Id: pnadminapi.php,v 1.4 2007/06/17 20:47:51 jornlind Exp $
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

require_once("modules/mediashare/common-edit.php");


// =======================================================================
// Scan for all media
// =======================================================================

function mediashare_adminapi_scanAllPlugins($args)
{
  // Force load - it is used during pninit
  if (!pnModAPILoad('mediashare', 'mediahandler', true))
    return mediashareErrorAPI(__FILE__, __LINE__, 'Failed to load Mediashare mediahandler API');

  $ok = pnModAPIFunc('mediashare', 'mediahandler', 'scanMediaHandlers');
  if ($ok === false)
    return false;

  // Force load - it is used during pninit
  if (!pnModAPILoad('mediashare', 'sources', true))
    return mediashareErrorAPI(__FILE__, __LINE__, 'Failed to load Mediashare sources API');

  $ok = pnModAPIFunc('mediashare', 'sources', 'scanSources');
  if ($ok === false)
    return false;

  return true;
}


// =======================================================================
// Set plugins
// =======================================================================

function mediashare_adminapi_setTemplateGlobally($args)
{
  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $albumsTable = $pntable['mediashare_albums'];
  $albumsColumn = &$pntable['mediashare_albums_column'];

  $template = pnVarPrepForStore($args['template']);

  $sql = "UPDATE $albumsTable 
          SET $albumsColumn[template] = '$template'";

  $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, 'Set template failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  return true;
}

?>