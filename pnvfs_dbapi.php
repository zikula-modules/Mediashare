<?php
// $Id$
//
// Mediashare by Jorn Lind-Nielsen (C)
//

require_once 'modules/mediashare/common.php';

class mediashare_vfsHandlerDB
{
    function mediashare_vfsHandlerDB()
    {
    }

    function createFile($filename, $args)
    {
        $dom = ZLanguage::getModuleDomain('mediashare');

        $fileReference = "vfsdb/$args[baseFileRef]-$args[fileMode].$args[fileType]";

        $pntable       = &pnDBGetTables();
        $mediadbColumn = $pntable['mediashare_mediadb_column'];

        $data  = file_get_contents($filename);
        $bytes = count($data);

        $record = array(
            'fileref' => $fileReference,
            'mode'    => $args['fileMode'],
            'type'    => $args['fileType'],
            'bytes'   => $bytes,
            'data'    => $data
        );

        $result = DBUtil::insertObject($record, 'mediashare_mediadb', 'id');

        if ($result === false) {
            return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('vfsHandlerDB.createFile', 'Could not retrieve insert the file information.'), $dom));
        }

        return $fileReference;
    }

    function deleteFile($fileReference)
    {
        $dom = ZLanguage::getModuleDomain('mediashare');

        $pntable       = &pnDBGetTables();
        $mediadbColumn = $pntable['mediashare_mediadb_column'];

        $fileReference = DataUtil::formatForStore($fileReference);
        $where         = "$mediadbColumn[fileref] = '$fileReference'";
        
        $result = DBUtil::deleteWhere('mediashare_mediadb', $where);

        if ($result === false) {
            return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('vfsHandlerDB.deleteFile', 'Could not delete the file information.'), $dom));
        }

        return true;
    }

    function updateFile($orgFileReference, $newFilename)
    {
        $dom = ZLanguage::getModuleDomain('mediashare');

        $pntable = &pnDBGetTables();

        $mediadbTable  = $pntable['mediashare_mediadb'];
        $mediadbColumn = $pntable['mediashare_mediadb_column'];

        $data  = file_get_contents($newFilename);
        $bytes = count($data);
        $orgFileReference = DataUtil::formatForStore($orgFileReference);

        $sql = "UPDATE $mediadbTable
                   SET $mediadbColumn[data] = '" . DataUtil::formatForStore($data) . "',
                       $mediadbColumn[bytes] = '$bytes'
                 WHERE $mediadbColumn[fileref] = '$orgFileReference'";

        $result = DBUtil::executeSQL($sql);

        if ($result === false) {
            return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('vfsHandlerDB.updateFile', 'Could not update the file information.'), $dom));
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

function mediashare_vfs_dbapi_buildHandler($args)
{
    return new mediashare_vfsHandlerDB();
}

function mediashare_vfs_dbapi_getMedia($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $fileref = DataUtil::formatForStore($args['fileref']);

    $pntable = &pnDBGetTables();

    $mediaTable    = $pntable['mediashare_media'];
    $mediaColumn   = $pntable['mediashare_media_column'];
    $storageTable  = $pntable['mediashare_mediastore'];
    $storageColumn = $pntable['mediashare_mediastore_column'];
    $mediadbTable  = $pntable['mediashare_mediadb'];
    $mediadbColumn = &$pntable['mediashare_mediadb_column'];

    $sql = "SELECT db.$mediadbColumn[data],
                   store.$storageColumn[mimeType],
                   store.$storageColumn[bytes],
                   media.$mediaColumn[id],
                   media.$mediaColumn[parentAlbumId],
                   media.$mediaColumn[title],
                   media.$mediaColumn[modifiedDate]
              FROM $mediadbTable db
         LEFT JOIN $storageTable store
                ON store.$storageColumn[fileRef] = db.$mediadbColumn[fileref]
         LEFT JOIN $mediaTable media
                ON (media.$mediaColumn[thumbnailId] = store.$storageColumn[id]
                    OR media.$mediaColumn[previewId] = store.$storageColumn[id]
                    OR media.$mediaColumn[originalId] = store.$storageColumn[id])
               AND media.$mediaColumn[title] IS NOT NULL
             WHERE db.$mediadbColumn[fileref] = '$fileref'";

    $result = DBUtil::executeSQL($sql);

    if ($result === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('vfsHandlerDB.getMedia', 'Could not retrieve the file information.'), $dom));
    }

    if (!$result) {
        return LogUtil::registerError(__('Unknown media item.', $dom));
    }

    $info = DBUtil::marshallObjects($result, array('data', 'mimeType', 'bytes', 'mediaId', 'albumId', 'title', 'modifiedDate'));

    return $info;
}

