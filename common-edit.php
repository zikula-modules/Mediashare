<?php
// $Id: common-edit.php,v 1.4 2007/06/20 20:26:49 jornlind Exp $
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2003.
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