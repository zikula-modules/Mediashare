<?php
// $Id$
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2005.
// =======================================================================


require_once 'modules/mediashare/common.php';

class mediashare_vfsHandlerDB
{
    function mediashare_vfsHandlerDB()
    {
    }

    function createFile($filename, $args)
    {
        $fileReference = "vfsdb/$args[baseFileRef]-$args[fileMode].$args[fileType]";

        list ($dbconn) = pnDBGetConn();
        $pntable = pnDBGetTables();

        $mediadbTable = $pntable['mediashare_mediadb'];
        $mediadbColumn = &$pntable['mediashare_mediadb_column'];

        $data = file_get_contents($filename);
        $bytes = count($data);

        $sql = "
INSERT INTO $mediadbTable
  ($mediadbColumn[fileref], $mediadbColumn[mode], $mediadbColumn[type], $mediadbColumn[bytes], $mediadbColumn[data])
VALUES
  ('" . pnVarPrepForStore($fileReference) . "',
   '" . pnVarPrepForStore($args['fileMode']) . "',
   '" . pnVarPrepForStore($args['fileType']) . "',
   $bytes,
   '" . pnVarPrepForStore($data) . "')";

        $result = $dbconn->execute($sql);

        if ($dbconn->errorNo() != 0) {
            return mediashareErrorAPI(__FILE__, __LINE__, '"createFile" failed (pnvf_dbapi): ' . $dbconn->errorMsg() . " while executing: $sql");
        }

        return $fileReference;
    }

    function deleteFile($fileReference)
    {
        list ($dbconn) = pnDBGetConn();
        $pntable = pnDBGetTables();

        $mediadbTable = $pntable['mediashare_mediadb'];
        $mediadbColumn = &$pntable['mediashare_mediadb_column'];

        $fileReference = pnVarPrepForStore($fileReference);

        $sql = "DELETE FROM $mediadbTable WHERE $mediadbColumn[fileref] = '$fileReference'";

        $result = $dbconn->execute($sql);

        if ($dbconn->errorNo() != 0) {
            return mediashareErrorAPI(__FILE__, __LINE__, '"deleteFile" failed (pnvf_dbapi): ' . $dbconn->errorMsg() . " while executing: $sql");
        }

        return true;
    }

    function updateFile($orgFileReference, $newFilename)
    {
        list ($dbconn) = pnDBGetConn();
        $pntable = pnDBGetTables();

        $mediadbTable = $pntable['mediashare_mediadb'];
        $mediadbColumn = &$pntable['mediashare_mediadb_column'];

        $data = file_get_contents($newFilename);
        $bytes = count($data);
        $orgFileReference = pnVarPrepForStore($orgFileReference);

        $sql = "
UPDATE $mediadbTable SET
  $mediadbColumn[data] = '" . pnVarPrepForStore($data) . "',
  $mediadbColumn[bytes] = $bytes
WHERE $mediadbColumn[fileref] = '$orgFileReference'";

        $result = $dbconn->execute($sql);

        if ($dbconn->errorNo() != 0) {
            return mediashareErrorAPI(__FILE__, __LINE__, '"createFile" failed (pnvf_dbapi): ' . $dbconn->errorMsg() . " while executing: $sql");
        }
        return true;
    }

    function getNewFileReference()
    {
        $chars = "0123456789abcdefghijklmnopqrstuvwxyz";
        $charLen = strlen($chars);

        $id = '';

        for ($i = 0; $i < 30; ++$i) {
            $id .= $chars[mt_rand(0, $charLen - 1)];
        }

        return $id;
    }
}
;

function mediashare_vfs_dbapi_buildHandler($args)
{
    return new mediashare_vfsHandlerDB();
}

function mediashare_vfs_dbapi_getMedia($args)
{
    $fileref = pnVarPrepForStore($args['fileref']);

    list ($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $mediaTable = $pntable['mediashare_media'];
    $mediaColumn = $pntable['mediashare_media_column'];
    $storageTable = $pntable['mediashare_mediastore'];
    $storageColumn = $pntable['mediashare_mediastore_column'];
    $mediadbTable = $pntable['mediashare_mediadb'];
    $mediadbColumn = &$pntable['mediashare_mediadb_column'];

    $sql = "
SELECT db.$mediadbColumn[data],
       store.$storageColumn[mimeType],
       store.$storageColumn[bytes],
       media.$mediaColumn[id],
       media.$mediaColumn[parentAlbumId],
       media.$mediaColumn[title],
       UNIX_TIMESTAMP(media.$mediaColumn[modifiedDate])
FROM $mediadbTable db
LEFT JOIN $storageTable store
     ON store.$storageColumn[fileRef] = db.$mediadbColumn[fileref]
LEFT JOIN $mediaTable media
     ON (   media.$mediaColumn[thumbnailId] = store.$storageColumn[id]
         OR media.$mediaColumn[previewId] = store.$storageColumn[id]
         OR media.$mediaColumn[originalId] = store.$storageColumn[id])
        AND media.$mediaColumn[title] IS NOT NULL
WHERE     db.$mediadbColumn[fileref] = '$fileref'";

    $result = $dbconn->execute($sql);

    if ($dbconn->errorNo() != 0) {
        return mediashareErrorAPI(__FILE__, __LINE__, '"getMedia" failed (pnvf_dbapi): ' . $dbconn->errorMsg() . " while executing: $sql");
    }

    if ($result->EOF) {
        return mediashareErrorAPI(__FILE__, __LINE__, "Unknown media item");
    }

    $info = array('data' => $result->fields[0], 'mimeType' => $result->fields[1], 'bytes' => $result->fields[2], 'mediaId' => $result->fields[3], 'albumId' => $result->fields[4], 'title' => $result->fields[5], 'modifiedDate' => $result->fields[6]);

    $result->Close();

    return $info;
}

