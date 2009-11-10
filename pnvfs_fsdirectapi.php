<?php
// $Id$
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2005.
// =======================================================================

mt_srand((double)microtime()*1000000);

class mediashare_vfsHandlerFSDirect
{
  var $storageDir;

  function mediashare_vfsHandlerFSDirect()
  {
    $this->storageDir = pnModGetVar('mediashare', 'mediaDirName');
  }


  function createFile($filename, $args)
  {
    $fileReference = "$args[baseFileRef]-$args[fileMode].$args[fileType]";
    $newFilename = $this->storageDir . '/' . pnVarPrepForOS($fileReference);

    $ok = @copy($filename, $newFilename);
    if ($ok === false) {
      return mediashareErrorAPI(__FILE__, __LINE__, "Failed to copy file '$filename' to '$newFilename' while creating new file in virtual storage system. Please check media upload directory in admin settings and it's permissions.");
    }

    chmod($newFilename, 0777);
    return $fileReference;
  }


  function deleteFile($fileReference)
  {
    $filename = $this->storageDir . '/' . pnVarPrepForOS($fileReference);
    unlink($filename);
  }


  function updateFile($orgFileReference, $newFilename)
  {
    $orgFilename = $this->storageDir . '/' . pnVarPrepForOS($orgFileReference);

    if (!copy($newFilename, $orgFilename)) {
      return mediashareErrorAPI(__FILE__, __LINE__, "Failed to copy '$newFilename' to '$orgFileReference'");
    }

    return true;
  }


  function getNewFileReference()
  {
    $chars = "0123456789abcdefghijklmnopqrstuvwxyz";
    $charLen = strlen($chars);

    $id = $chars[mt_rand(0, $charLen-1)] . $chars[mt_rand(0, $charLen-1)];

    if (!file_exists("mediashare/$id")) {
      mkdir("mediashare/$id");
      chmod("mediashare/$id", 0777);
    }

    $id .= '/';

    for ($i=0; $i<30; ++$i) {
      $id .= $chars[mt_rand(0, $charLen-1)];
    }

    return $id;
  }
}


function mediashare_vfs_fsdirectapi_buildHandler($args)
{
  return new mediashare_vfsHandlerFSDirect();
}

