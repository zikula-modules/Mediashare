<?php
// $Id: common-edit.php,v 1.4 2007/06/20 20:26:49 jornlind Exp $
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2003.
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

require_once("modules/mediashare/common.php");


function mediashareUploadErrorMsg($error)
{
  switch ($error)
  {
    case 1:
      return _MSUPLOADERROR_INISIZE;
    case 2:
      return _MSUPLOADERROR_FORMSIZE;
    case 3:
      return _MSUPLOADERROR_PARTIAL;
    case 4:
      return _MSUPLOADERROR_NOFILE;
    default:
      return _MSUPLOADERROR_UNKNOWN;
  }
}


?>