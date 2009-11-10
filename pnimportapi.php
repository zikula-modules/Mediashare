<?php
// $Id: pnimportapi.php,v 1.8 2007/06/17 20:47:51 jornlind Exp $
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2005.
// =======================================================================


require_once ("modules/mediashare/common-edit.php");

function mediashare_importapi_photoshare($args)
{
    if (!pnModAPILoad('mediashare', 'edit')) {
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare edit API');
    }

    if (!pnModAPILoad('photoshare', 'user')) {
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Photoshare user API');
    }

    if (!pnModAPILoad('photoshare', 'show')) {
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Photoshare show API');
    }

    $ok = mediashareImportPhotoshareRec(-1, 1);
    if ($ok) {
        pnModDelVar('Mediashare', 'ImportedPhotoshareAlbums');
        pnModDelVar('Mediashare', 'ImportedPhotoshareImages');
    }

    return $ok;
}

function mediashareImportPhotoshareRec($photoshareFolderId, $mediashareAlbumId)
{
    $folders = pnModAPIFunc('photoshare', 'user', 'get_accessible_folders', array('getForList' => true, 'order' => 'title', 'parentFolderID' => $photoshareFolderId));
    if ($folders === false) {
        return mediashareErrorAPI(__FILE__, __LINE__, photoshareErrorAPIGet());
    }

    foreach ($folders as $folder) {
        // Check already imported?
        $importedAlbums = pnModGetVar('Mediashare', 'ImportedPhotoshareAlbums');
        if ($importedAlbums === false) {
            mediashareStartPhotoshareRef();
            $importedAlbums = array();
        } else {
            $importedAlbums = unserialize($importedAlbums);
        }

        if (!array_key_exists($folder['id'], $importedAlbums)) {
            $folderData = pnModAPIFunc('photoshare', 'user', 'get_folder_info', array('folderID' => $folder['id']));
            if ($folderData === false) {
                return mediashareErrorAPI(__FILE__, __LINE__, photoshareErrorAPIGet());
            }

            $id = pnModAPIFunc('mediashare', 'edit', 'addAlbum', array(
                'title' => $folderData['title'],
                'keywords' => '',
                'summary' => '',
                'description' => $folderData['description'],
                'ownerId' => $folderData['owner'],
                'parentAlbumId' => $mediashareAlbumId));
            if ($id === false) {
                return false;
            }

            // Mark album as created
            $importedAlbums[$folder['id']] = $id;
            pnModSetVar('Mediashare', 'ImportedPhotoshareAlbums', serialize($importedAlbums));
        } else {
            $id = $importedAlbums[$folder['id']];
        }

        $ok = mediashareImportPhotoshareImages($folder['id'], $id);
        if ($ok === false) {
            return false;
        }

        $ok = mediashareImportPhotoshareRec($folder['id'], $id);
        if ($ok === false) {
            return false;
        }
    }

    return true;
}

function mediashareImportPhotoshareImages($photoshareFolderId, $mediashareAlbumId)
{
    $images = pnModAPIFunc('photoshare', 'user', 'get_image_list', array('folderID' => $photoshareFolderId, 'usePageCount' => false));
    if ($images === false) {
        return mediashareErrorAPI(__FILE__, __LINE__, photoshareErrorAPIGet());
    }

    $tmpDir = pnModGetVar('mediashare', 'tmpDirName');

    foreach ($images['images'] as $image) {
        // Check already imported?
        $importedImages = pnModGetVar('Mediashare', 'ImportedPhotoshareImages');

        if ($importedImages === false) {
            $importedImages = array();
        } else {
            $importedImages = unserialize($importedImages);
        }

        if (!array_key_exists($image['id'], $importedImages)) {
            $imageData = pnModAPIFunc('photoshare', 'show', 'get_image_info', array('imageID' => $image['id']));
            if ($imageData === false) {
                return mediashareErrorAPI(__FILE__, __LINE__, photoshareErrorAPIGet());
            }
            if (($photoshareFilename = tempnam($tmpDir, 'imp')) === false) {
                return mediashareErrorAPI(__FILE__, __LINE__, "Failed to create temporary photoshare file in directory '$tmpDir'");
            }

            $photoshareImageData = photoshareGetImageData($image['id'], false, true);
            $fh = fopen($photoshareFilename, 'w');
            fwrite($fh, $photoshareImageData['imageData']);
            fclose($fh);

            $args = array(
                'albumId' => $mediashareAlbumId,
                'ownerId' => $imageData['owner'],
                'mimeType' => $imageData['mimeType'],
                'fileSize' => filesize($photoshareFilename),
                'filename' => $imageData['filename'],
                'mediaFilename' => $photoshareFilename,
                'title' => $imageData['title'],
                'description' => $imageData['description'],
                'ignoreSizeLimits' => true);

            $result = pnModAPIFunc('mediashare', 'edit', 'addMediaItem', $args);
            unlink($photoshareFilename);
            if ($result === false) {
                return mediashareErrorAPI(__FILE__, __LINE__, "Got error while converting (" . $imageData['title'] . "): " . mediashareErrorAPIGet());
            }

            // Mark album as imported
            $importedImages[$image['id']] = 1;
            pnModSetVar('Mediashare', 'ImportedPhotoshareImages', serialize($importedImages));

            // Add Photoshare/Mediashare conversion
            if (mediashareAddPhotoshareRef($image['id'], $result) === false) {
                return false;
            }
        }
    }

    return true;
}

function mediashareStartPhotoshareRef()
{
    list ($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $photoshareTable = $pntable['mediashare_photoshare'];
    $photoshareColumn = $pntable['mediashare_photoshare_column'];

    $sql = "TRUNCATE TABLE $photoshareTable";

    $dbconn->execute($sql);

    if ($dbconn->errorNo() != 0) {
        return mediashareErrorAPI(__FILE__, __LINE__, '"mediashareStartPhotoshareRef" failed: ' . $dbconn->errorMsg() . " while executing: $sql");
    }

    return true;
}

function mediashareAddPhotoshareRef($photoshareImageId, $args)
{
    list ($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $photoshareTable = $pntable['mediashare_photoshare'];
    $photoshareColumn = $pntable['mediashare_photoshare_column'];

    $sql = "INSERT INTO $photoshareTable
            ($photoshareColumn[photoshareImageId], $photoshareColumn[mediashareThumbnailRef],
             $photoshareColumn[mediasharePreviewRef], $photoshareColumn[mediashareOriginalRef])
          VALUES ($photoshareImageId, '$args[thumbnailFileRef]',
                  '$args[previewFileRef]', '$args[originalFileRef]')";

    $dbconn->execute($sql);

    if ($dbconn->errorNo() != 0) {
        return mediashareErrorAPI(__FILE__, __LINE__, '"mediashareAddPhotoshareRef" failed: ' . $dbconn->errorMsg() . " while executing: $sql");
    }

    return true;
}

function mediashare_importapi_getMediashareUrl($args)
{
    $thumbnail = $args['thumbnail'];

    list ($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $photoshareTable = $pntable['mediashare_photoshare'];
    $photoshareColumn = $pntable['mediashare_photoshare_column'];

    $sql = "SELECT
            $photoshareColumn[mediashareThumbnailRef],
            $photoshareColumn[mediasharePreviewRef],
            $photoshareColumn[mediashareOriginalRef]
          FROM $photoshareTable
          WHERE $photoshareColumn[photoshareImageId] = " . (int) $args[imageId];

    $dbresult = $dbconn->execute($sql);

    if ($dbconn->errorNo() != 0) {
        return mediashareErrorAPI(__FILE__, __LINE__, '"getMediashareUrl" failed: ' . $dbconn->errorMsg() . " while executing: $sql");
    }

    $thumbnailRef = $dbresult->fields[0];
    $originalRef = $dbresult->fields[2];

    $dbresult->close();

    if ($thumbnail) {
        return "mediashare/$thumbnailRef";
    } else {
        return "mediashare/$originalRef";
    }
}
