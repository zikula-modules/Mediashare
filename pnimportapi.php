<?php
// $Id$
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2005.
// =======================================================================

require_once ("modules/mediashare/common-edit.php");

function mediashare_importapi_photoshare($args)
{
    pnModAPILoad('mediashare', 'edit');
    pnModAPILoad('photoshare', 'user');
    pnModAPILoad('photoshare', 'show');

    $ok = mediashareImportPhotoshareRec(-1, 1);
    if ($ok) {
        pnModDelVar('Mediashare', 'ImportedPhotoshareAlbums');
        pnModDelVar('Mediashare', 'ImportedPhotoshareImages');
    }

    return $ok;
}

function mediashareImportPhotoshareRec($photoshareFolderId, $mediashareAlbumId)
{
    if (!($folders = pnModAPIFunc('photoshare', 'user', 'get_accessible_folders', array('getForList' => true, 'order' => 'title', 'parentFolderID' => $photoshareFolderId)))) {
        return LogUtil::registerError(photoshareErrorAPIGet());
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

        if (!isset($importedAlbums[$folder['id']])) {
            if (!($folderData = pnModAPIFunc('photoshare', 'user', 'get_folder_info', array('folderID' => $folder['id'])))) {
                return LogUtil::registerError(photoshareErrorAPIGet());
            }

            $id = pnModAPIFunc('mediashare', 'edit', 'addAlbum',
                               array('title' => $folderData['title'],
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

        if (!mediashareImportPhotoshareImages($folder['id'], $id)) {
            return false;
        }

        if (!mediashareImportPhotoshareRec($folder['id'], $id)) {
            return false;
        }
    }

    return true;
}

function mediashareImportPhotoshareImages($photoshareFolderId, $mediashareAlbumId)
{
    if (!($images = pnModAPIFunc('photoshare', 'user', 'get_image_list', array('folderID' => $photoshareFolderId, 'usePageCount' => false)))) {
        return LogUtil::registerError(photoshareErrorAPIGet());
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

        if (!isset($importedImages[$image['id']])) {
            if (!($imageData = pnModAPIFunc('photoshare', 'show', 'get_image_info', array('imageID' => $image['id'])))) {
                return LogUtil::registerError(photoshareErrorAPIGet());
            }
            if (($photoshareFilename = tempnam($tmpDir, 'imp')) === false) {
                return LogUtil::registerError("Failed to create temporary photoshare file in the '$tmpDir' directory.");
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
                return LogUtil::registerError("Got error while converting ($imageData[title]).");
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
    $dom = ZLanguage::getModuleDomain('mediashare');

    if (!DBUtil::truncateTable('mediashare_photoshare')) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('importapi.mediashareStartPhotoshareRef', __f("Could not clear the '%s' table.", 'photoshare', $dom)), $dom));
    }

    return true;
}

function mediashareAddPhotoshareRef($photoshareImageId, $args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');
    
    list ($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $photoshareTable = $pntable['mediashare_photoshare'];
    $photoshareColumn = $pntable['mediashare_photoshare_column'];

    $sql = "INSERT INTO $photoshareTable
                   ($photoshareColumn[photoshareImageId],
                    $photoshareColumn[mediashareThumbnailRef],
                    $photoshareColumn[mediasharePreviewRef],
                    $photoshareColumn[mediashareOriginalRef])
            VALUES ($photoshareImageId,
                    '$args[thumbnailFileRef]',
                    '$args[previewFileRef]',
                    '$args[originalFileRef]')";

    $dbconn->execute($sql);

    if ($dbconn->errorNo() != 0) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('importapi.mediashareStartPhotoshareRef', 'Could not insert the references to photoshare.'), $dom));
    }

    return true;
}

function mediashare_importapi_getMediashareUrl($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $thumbnail = $args['thumbnail'];

    list ($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $photoshareTable  = $pntable['mediashare_photoshare'];
    $photoshareColumn = $pntable['mediashare_photoshare_column'];

    $sql = "SELECT $photoshareColumn[mediashareThumbnailRef],
                   $photoshareColumn[mediasharePreviewRef],
                   $photoshareColumn[mediashareOriginalRef]
              FROM $photoshareTable
             WHERE $photoshareColumn[photoshareImageId] = ".(int)$args['imageId'];

    $dbresult = $dbconn->execute($sql);

    if ($dbconn->errorNo() != 0) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('importapi.getMediashareUrl', 'Could not retrieve the references for the media item.'), $dom));
    }

    $thumbnailRef = $dbresult->fields[0];
    $originalRef = $dbresult->fields[2];

    $dbresult->close();

    $mediadir = pnModAPIFunc('mediashare', 'user', 'getRelativeMediadir');

    if ($thumbnail) {
        return $mediadir.$thumbnailRef;
    }

    return $mediadir.$originalRef;
}
