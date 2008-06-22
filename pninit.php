<?php
// $Id: pninit.php,v 1.46 2008/06/18 19:38:12 jornlind Exp $
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2002.
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


// -----------------------------------------------------------------------
// Module initialization
// -----------------------------------------------------------------------
function mediashare_init()
{
  $modInfo = pnModGetInfo( pnModGetIDFromName('Topics') );
  if ($modInfo === false)
    return mediashareInitError(__FILE__, __LINE__, 'Mediashare requires Topics module to be installed');

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $dict = NewDataDictionary($dbconn);
  $taboptarray = pnDBGetTableOptions();

  // Album creation

  $albumTable = $pntable['mediashare_albums'];
  $albumColumn = &$pntable['mediashare_albums_column'];

    // Create the table
  $sql = "$albumColumn[id] I NOTNULL AUTOINCREMENT KEY,
          $albumColumn[ownerId] I NOTNULL,
          $albumColumn[createdDate] T NOTNULL,
          $albumColumn[modifiedDate] T NOTNULL DEFTIMESTAMP,
          $albumColumn[title] C(255) NOTNULL DEFAULT '',
          $albumColumn[keywords] C(255) NOTNULL DEFAULT '',
          $albumColumn[summary] X NOTNULL DEFAULT '',
          $albumColumn[description] X NOTNULL DEFAULT '',
          $albumColumn[topicId] I,
          $albumColumn[template] C(255) NOTNULL DEFAULT 'msslideshow',
          $albumColumn[parentAlbumId] I,
          $albumColumn[access] I1 NOTNULL DEFAULT 0,
          $albumColumn[viewKey] C(32) NOTNULL,
          $albumColumn[mainMediaId] I,
          $albumColumn[thumbnailSize] I NOTNULL,
          $albumColumn[nestedSetLeft] I NOTNULL DEFAULT 0,
          $albumColumn[nestedSetRight] I NOTNULL DEFAULT 0,
          $albumColumn[nestedSetLevel] I NOTNULL DEFAULT 0,
          $albumColumn[extappURL] C(256)
          $albumColumn[extappData] C(512)";

  $sqlArray = $dict->CreateTableSQL($albumTable, $sql, $taboptarray);
  $result = $dict->ExecuteSQLArray($sqlArray);

  // Check for an error with the database code, and if so set an
  // appropriate error message and return
  if ($result != 2)
    return mediashareInitError(__FILE__, __LINE__, 'Table creation failed: ' . $dbconn->ErrorMsg() . ' while executing' . $sqlArray[0]);

    // Media items creation

  $mediaTable = $pntable['mediashare_media'];
  $mediaColumn = &$pntable['mediashare_media_column'];

    // Create the table
  $sql = "$mediaColumn[id] I NOTNULL AUTOINCREMENT KEY,
          $mediaColumn[ownerId] I NOTNULL,
          $albumColumn[createdDate] T NOTNULL,
          $albumColumn[modifiedDate] T NOTNULL DEFTIMESTAMP,
          $mediaColumn[title] C(255) NOTNULL DEFAULT '',
          $mediaColumn[keywords] C(255) NOTNULL DEFAULT '',
          $mediaColumn[description] X NOTNULL DEFAULT '',
          $mediaColumn[parentAlbumId] I NOTNULL,
          $mediaColumn[position] I NOTNULL,
          $mediaColumn[mediaHandler] C(50) NOTNULL,
          $mediaColumn[thumbnailId] I NOTNULL,
          $mediaColumn[previewId] I NOTNULL,
          $mediaColumn[originalId] I NOTNULL";

  $sqlArray = $dict->CreateTableSQL($mediaTable, $sql, $taboptarray);
  $result = $dict->ExecuteSQLArray($sqlArray);

  // Check for an error with the database code, and if so set an
  // appropriate error message and return
  if ($result != 2)
    return mediashareInitError(__FILE__, __LINE__, 'Table creation failed: ' . $dbconn->ErrorMsg() . ' while executing' . $sqlArray[0]);


    // Keywords creation

  $keywordsTable = $pntable['mediashare_keywords'];
  $keywordsColumn = &$pntable['mediashare_keywords_column'];

    // Create the table
  $sql = "$keywordsColumn[itemId] I NOTNULL,
          $keywordsColumn[type] C(5) NOTNULL,
          $keywordsColumn[keyword] C(50) NOTNULL";

  $sqlArray = $dict->CreateTableSQL($keywordsTable, $sql, $taboptarray);
  $result = $dict->ExecuteSQLArray($sqlArray);

  // Check for an error with the database code, and if so set an
  // appropriate error message and return
  if ($result != 2)
    return mediashareInitError(__FILE__, __LINE__, 'Table creation failed: ' . $dbconn->ErrorMsg() . ' while executing' . $sqlArray[0]);

    // Add index on keyword
  $indexFields = $keywordsColumn['keyword'];  
  $sqlArray = $dict->CreateIndexSQL('keywordsKeywordIdx', $keywordsTable, $indexFields);
  $result = $dict->ExecuteSQLArray($sqlArray);
  
  // Check for an error with the database code, and if so set an
  // appropriate error message and return
  if ($result != 2)
    return mediashareInitError(__FILE__, __LINE__, 'Table creation failed: ' . $dbconn->ErrorMsg() . ' while executing' . $sqlArray[0]);

    // Media store creation

  $mediastoreTable = $pntable['mediashare_mediastore'];
  $mediastoreColumn = &$pntable['mediashare_mediastore_column'];

    // Create the table
  $sql = "$mediastoreColumn[id] I NOTNULL AUTOINCREMENT KEY,
          $mediastoreColumn[mimeType] C(100) NOTNULL,
          $mediastoreColumn[fileRef] C(50) NOTNULL,
          $mediastoreColumn[width] I2 NOTNULL,
          $mediastoreColumn[height] I2 NOTNULL,
          $mediastoreColumn[bytes] I NOTNULL";

  $sqlArray = $dict->CreateTableSQL($mediastoreTable, $sql, $taboptarray);
  $result = $dict->ExecuteSQLArray($sqlArray);

  // Check for an error with the database code, and if so set an
  // appropriate error message and return
  if ($result != 2)
    return mediashareInitError(__FILE__, __LINE__, 'Table creation failed: ' . $dbconn->ErrorMsg() . ' while executing' . $sqlArray[0]);

  if (!mediashareCreateMediaDB($dbconn, $pntable, $dict, $taboptarray))
    return false;

    // Media handlers creation

  $handlerTable = $pntable['mediashare_mediahandlers'];
  $handlerColumn = &$pntable['mediashare_mediahandlers_column'];

    // Create the table
  $sql = "$handlerColumn[id] I NOTNULL AUTOINCREMENT KEY,
          $handlerColumn[mimeType] C(50),
          $handlerColumn[fileType] C(10),
          $handlerColumn[handler] C(50) NOTNULL,
          $handlerColumn[foundMimeType] C(50) NOTNULL,
          $handlerColumn[foundFileType] C(50) NOTNULL,
          $handlerColumn[title] C(50) NOTNULL DEFAULT '',
          $handlerColumn[active] I1 NOTNULL DEFAULT 1";

  $sqlArray = $dict->CreateTableSQL($handlerTable, $sql, $taboptarray);
  $result = $dict->ExecuteSQLArray($sqlArray);

  // Check for an error with the database code, and if so set an
  // appropriate error message and return
  if ($result != 2)
    return mediashareInitError(__FILE__, __LINE__, 'Table creation failed: ' . $dbconn->ErrorMsg() . ' while executing' . $sqlArray[0]);


    // Sources handlers creation

  $sourcesTable = $pntable['mediashare_sources'];
  $sourcesColumn = &$pntable['mediashare_sources_column'];

    // Create the table
  $sql = "$sourcesColumn[id] I NOTNULL AUTOINCREMENT KEY,
          $sourcesColumn[name] C(50) NOTNULL,
          $sourcesColumn[title] C(50) NOTNULL DEFAULT '',
          $sourcesColumn[formEncType] C(50) NOTNULL DEFAULT 'multipart/form-data',
          $sourcesColumn[active] I1 NOTNULL DEFAULT 1";

  $sqlArray = $dict->CreateTableSQL($sourcesTable, $sql, $taboptarray);
  $result = $dict->ExecuteSQLArray($sqlArray);

  // Check for an error with the database code, and if so set an
  // appropriate error message and return
  if ($result != 2)
    return mediashareInitError(__FILE__, __LINE__, 'Table creation failed: ' . $dbconn->ErrorMsg() . ' while executing' . $sqlArray[0]);


    // Access control creation

  $accessTable = $pntable['mediashare_access'];
  $accessColumn = &$pntable['mediashare_access_column'];

    // Create the table
  $sql = "$accessColumn[id] I NOTNULL AUTOINCREMENT KEY,
          $accessColumn[albumId] I NOTNULL,
          $accessColumn[groupId] I NOTNULL,
          $accessColumn[access] I NOTNULL";

  $sqlArray = $dict->CreateTableSQL($accessTable, $sql, $taboptarray);
  $result = $dict->ExecuteSQLArray($sqlArray);

  // Check for an error with the database code, and if so set an
  // appropriate error message and return
  if ($result != 2)
    return mediashareInitError(__FILE__, __LINE__, 'Table creation failed: ' . $dbconn->ErrorMsg() . ' while executing' . $sqlArray[0]);


  $indexFields = $accessColumn['albumId'];  
  $sqlArray = $dict->CreateIndexSQL('accessAlbumIdx', $accessTable, $indexFields);
  $result = $dict->ExecuteSQLArray($sqlArray);
  
  // Check for an error with the database code, and if so set an
  // appropriate error message and return
  if ($result != 2)
    return mediashareInitError(__FILE__, __LINE__, 'Table creation failed: ' . $dbconn->ErrorMsg() . ' while executing' . $sqlArray[0]);

    // Setup table

  $setupTable = $pntable['mediashare_setup'];
  $setupColumn = $pntable['mediashare_setup_column'];

    // Create the table
  $sql = "$setupColumn[id] I NOTNULL AUTOINCREMENT KEY,
          $setupColumn[kind] I1 NOTNULL,
          $setupColumn[storageLimit] I NOTNULL,
          $setupColumn[unitId] I NOTNULL";

  $sqlArray = $dict->CreateTableSQL($setupTable, $sql, $taboptarray);
  $result = $dict->ExecuteSQLArray($sqlArray);

  // Check for an error with the database code, and if so set an
  // appropriate error message and return
  if ($result != 2)
    return mediashareInitError(__FILE__, __LINE__, 'Table creation failed: ' . $dbconn->ErrorMsg() . ' while executing' . $sqlArray[0]);


    // Photoshare table

  $photoshareTable = $pntable['mediashare_photoshare'];
  $photoshareColumn = $pntable['mediashare_photoshare_column'];

    // Create the table
  $sql = "$setupColumn[id] I NOTNULL AUTOINCREMENT KEY,
          $photoshareColumn[photoshareImageId] I NOTNULL,
          $photoshareColumn[mediashareThumbnailRef] C(50) NOTNULL,
          $photoshareColumn[mediasharePreviewRef] C(50) NOTNULL,
          $photoshareColumn[mediashareOriginalRef] C(50) NOTNULL";

  $sqlArray = $dict->CreateTableSQL($photoshareTable, $sql, $taboptarray);
  $result = $dict->ExecuteSQLArray($sqlArray);

  // Check for an error with the database code, and if so set an
  // appropriate error message and return
  if ($result != 2)
    return mediashareInitError(__FILE__, __LINE__, 'Table creation failed: ' . $dbconn->ErrorMsg() . ' while executing' . $sqlArray[0]);

  if (!mediashareCreateInvitationTable($dbconn, $pntable, $dict, $taboptarray))
    return false;

    // Initialize global variables

  if (!pnModSetVar('mediashare', 'tmpDirName', '/tmp'))
    return mediashareInitError(__FILE__, __LINE__, 'Set module var tmpDirName failed');

  if (!pnModSetVar('mediashare', 'mediaDirName', str_replace('/modules', '', dirname(__FILE__))))
    return mediashareInitError(__FILE__, __LINE__, 'Set module var mediaDirName failed');

  if (!pnModSetVar('mediashare', 'thumbnailSize', '100'))
    return mediashareInitError(__FILE__, __LINE__, 'Set module var thumbnailSize failed');

  if (!pnModSetVar('mediashare', 'previewSize', '400'))
    return mediashareInitError(__FILE__, __LINE__, 'Set module var previewSize failed');

  if (!pnModSetVar('mediashare', 'mediaSizeLimitSingle', 250000))
    return mediashareInitError(__FILE__, __LINE__, 'Set module var mediaSizeLimitSingle failed');

  if (!pnModSetVar('mediashare', 'mediaSizeLimitTotal', 5000000))
    return mediashareInitError(__FILE__, __LINE__, 'Set module var mediaSizeLimitTotal failed');

  if (!pnModSetVar('mediashare', 'defaultAlbumTemplate', 'standard'))
    return mediashareInitError(__FILE__, __LINE__, 'Set module var defaultAlbumTemplate failed');

  if (!pnModSetVar('mediashare', 'defaultSlideshowTemplate', 'standard'))
    return mediashareInitError(__FILE__, __LINE__, 'Set module var defaultSlideshowTemplate failed');

  if (!mediashareSetDefaultTopic())
    return mediashareInitError(__FILE__, __LINE__, 'Failed to set default topic');


  // Scan for plugins

  if (!pnModAPILoad('mediashare', 'admin', true))
    return mediashareInitError(__FILE__, __LINE__, 'Failed to load Mediashare admin API');

  $ok = pnModAPIFunc('mediashare', 'admin', 'scanAllPlugins');
  if ($ok === false)
    mediashareInitError(__FILE__, __LINE__, mediashareErrorAPIGet());


  // Add top album

  if (!pnModAPILoad('mediashare', 'edit', true))
    return mediashareInitError(__FILE__, __LINE__, 'Failed to load Mediashare edit API');

  $topAlbum = array('title'         => _MSTOP,
                    'keywords'      => '',
                    'summary'       => '',
                    'description'   => _MSTOPDESCRIPTION,
                    'parentAlbumId' => 0,
                    'topicId'       => 0);
  $topId = pnModAPIFunc('mediashare', 'edit', 'addAlbum', $topAlbum);
  if ($topId === false)
    return mediashareInitError(__FILE__, __LINE__, mediashareErrorAPIGet());

  $ok = pnModAPIFunc('mediashare', 'edit', 'setDefaultAccess', 
                     array('albumId' => $topId,
                           'usersMayAddAlbum' => true));
  if ($ok === false)
    return mediashareInitError(__FILE__, __LINE__, mediashareErrorAPIGet());

    // Initialisation successful
  return true;
}


function mediashareCreateMediaDB(&$dbconn, &$pntable, &$dict, &$taboptarray)
{
    // Media DB creation

  $mediadbTable = $pntable['mediashare_mediadb'];
  $mediadbColumn = &$pntable['mediashare_mediadb_column'];

    // Create the header table
  $sql = "$mediadbColumn[id] I NOTNULL AUTOINCREMENT KEY,
          $mediadbColumn[fileref] C(50) NOTNULL,
          $mediadbColumn[mode] C(20) NOTNULL,
          $mediadbColumn[type] C(10) NOTNULL,
          $mediadbColumn[bytes] I NOTNULL,
          $mediadbColumn[data] B NOTNULL";

  $sqlArray = $dict->CreateTableSQL($mediadbTable, $sql, $taboptarray);
  $result = $dict->ExecuteSQLArray($sqlArray);

  // Check for an error with the database code, and if so set an
  // appropriate error message and return
  if ($result != 2)
    return mediashareInitError(__FILE__, __LINE__, 'Table creation failed: ' . $dbconn->ErrorMsg() . ' while executing' . $sqlArray[0]);

  pnModSetVar('mediashare', 'vfs', 'fsdirect');
  pnModSetVar('mediashare', 'enableSharpen', 1);

  return true;
}


function mediashareCreateInvitationTable(&$dbconn, &$pntable, &$dict, &$taboptarray)
{
  $invitationTable = $pntable['mediashare_invitation'];
  $invitationColumn = &$pntable['mediashare_invitation_column'];

    // Create the header table
  $sql = "$invitationColumn[id] I NOTNULL AUTOINCREMENT KEY,
          $invitationColumn[created] T NOTNULL,
          $invitationColumn[albumId] I NOTNULL,
          $invitationColumn[key] C(20) NOTNULL DEFAULT '',
          $invitationColumn[viewCount] I NOTNULL DEFAULT 0,
          $invitationColumn[email] C(100) NOTNULL DEFAULT '',
          $invitationColumn[subject] C(255) NOTNULL DEFAULT '',
          $invitationColumn[text] X NOTNULL DEFAULT '',
          $invitationColumn[sender] C(50) NOTNULL DEFAULT '',
          $invitationColumn[expires] T";

  $sqlArray = $dict->CreateTableSQL($invitationTable, $sql, $taboptarray);
  $result = $dict->ExecuteSQLArray($sqlArray);

  // Check for an error with the database code, and if so set an
  // appropriate error message and return
  if ($result != 2)
    return mediashareInitError(__FILE__, __LINE__, 'Table creation failed: ' . $dbconn->ErrorMsg() . ' while executing' . $sqlArray[0]);

  return true;
}


function mediashareSetDefaultTopic()
{
  $topics = mediasharePNGetTopics(NULL);
  if ($topics === false  ||  sizeof($topics) == 0)
    return pnModSetVar('mediashare', 'defaultTopic', '');
  else
    return pnModSetVar('mediashare', 'defaultTopic', $topics[0]['id']);
}


// -----------------------------------------------------------------------
// Module upgrade
// -----------------------------------------------------------------------
function mediashare_upgrade($oldVersion)
{
  $ok = true;

    // Upgrade dependent on old version number
  switch($oldVersion)
  {
    case '1.0.0':
      // ignore
    case '1.0.1':
      $ok = $ok && mediashare_upgrade_to_1_0_2($oldVersion);
    case '1.0.2':
    case '2.0.0':
    case '2.0.1':
    case '2.1.0':
      $ok = $ok && mediashare_upgrade_to_2_1_1($oldVersion);
    case '2.1.1':
    case '2.1.2':
      $ok = $ok && mediashare_upgrade_to_2_2_0($oldVersion);
    case '2.2.0':
      $ok = $ok && mediashare_upgrade_to_2_3_0($oldVersion);
    case '2.3.0':
    case '3.0.0':
    case '3.0.1':
    case '3.1.0':
    case '3.1.1':
    case '3.2.0':
    case '3.3.0':
      $ok = $ok && mediashare_upgrade_to_3_4_0($oldVersion);
    case '3.4.0':
      // future
  }

    // Update successful
  return $ok;
}


function mediashare_upgrade_to_1_0_2($oldVersion)
{
  $newVersion = '1.0.2';

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $dict = NewDataDictionary($dbconn);
  $taboptarray = pnDBGetTableOptions();

  $albumTable = $pntable['mediashare_albums'];
  $albumColumn = &$pntable['mediashare_albums_column'];

  $sql = "$albumColumn[createdDate] T NOTNULL,
          $albumColumn[modifiedDate] T NOTNULL DEFTIMESTAMP";

  $sqlArray = $dict->AlterColumnSQL($albumTable, $sql, $taboptarray);
  $result = $dict->ExecuteSQLArray($sqlArray);

  // Check for an error with the database code, and if so set an
  // appropriate error message and return
  if ($result != 2)
    return mediashareInitError(__FILE__, __LINE__, 'Album table change failed: ' . $dbconn->ErrorMsg() . ' while executing' . $sqlArray[0]);


  $mediaTable = $pntable['mediashare_media'];
  $mediaColumn = &$pntable['mediashare_media_column'];

  $sql = "$albumColumn[createdDate] T NOTNULL,
          $albumColumn[modifiedDate] T NOTNULL DEFTIMESTAMP";

  $sqlArray = $dict->AlterColumnSQL($mediaTable, $sql, $taboptarray);
  $result = $dict->ExecuteSQLArray($sqlArray);

  // Check for an error with the database code, and if so set an
  // appropriate error message and return
  if ($result != 2)
    return mediashareInitError(__FILE__, __LINE__, 'Media table change failed: ' . $dbconn->ErrorMsg() . ' while executing' . $sqlArray[0]);

  $sql = "UPDATE $albumTable
          SET $albumColumn[modifiedDate] = $albumColumn[createdDate]";
  $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return mediashareInitError(__FILE__, __LINE__, 'Media table change failed: ' . $dbconn->ErrorMsg() . ' while executing' . $sql);

  return true;
}


function mediashare_upgrade_to_2_1_1($oldVersion)
{
  $newVersion = '2.1.1';
  pnModAPILoad('mediashare', 'admin', true);
  pnModSetVar('mediashare', 'defaultAlbumTemplate', 'Lightbox');
  pnModAPIFunc('mediashare', 'admin', 'setTemplateGlobally', array('template' => 'Lightbox'));
  return true;
}


function mediashare_upgrade_to_2_2_0($oldVersion)
{
  $newVersion = '2.2.0';

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $dict = NewDataDictionary($dbconn);
  $taboptarray = pnDBGetTableOptions();

  $sqlArray = $dict->DropColumnSQL($pntable['mediashare_mediastore'], $pntable['mediashare_mediastore_column']['data']);
  $result = $dict->ExecuteSQLArray($sqlArray);

    // Check for an error with the database code
  if ($result != 2)
    return mediashareInitError(__FILE__, __LINE__, 'Drop column "data" failed ' . ': ' . 
                              $dbconn->ErrorMsg() . ' while executing ' . $sqlArray[0]);

  if (!mediashareCreateMediaDB($dbconn, $pntable, $dict, $taboptarray))
    return false;

  return true;
}


function mediashare_upgrade_to_2_3_0($oldVersion)
{
  $newVersion = '2.3.0';

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $dict = NewDataDictionary($dbconn);
  $taboptarray = pnDBGetTableOptions();

  return mediashareCreateInvitationTable($dbconn, $pntable, $dict, $taboptarray);
}


function mediashare_upgrade_to_3_4_0($oldVersion)
{
  $newVersion = '3.4.0';

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $dict = NewDataDictionary($dbconn);
  $taboptarray = pnDBGetTableOptions();

  $albumTable = $pntable['mediashare_albums'];
  $albumColumn = &$pntable['mediashare_albums_column'];

  $columns = "$albumColumn[extappURL] C(256),
              $albumColumn[extappData] C(512)";
  
  $sqlArray = $dict->AddColumnSQL($pntable['mediashare_albums'], $columns);
  $result = $dict->ExecuteSQLArray($sqlArray);

    // Check for an error with the database code
  if ($result != 2)
    return mediashareInitError(__FILE__, __LINE__, 'Add columns failed ' . ': ' . 
                              $dbconn->ErrorMsg() . ' while executing ' . $sqlArray[0]);

  return true;
}


// -----------------------------------------------------------------------
// Module delete
// -----------------------------------------------------------------------
function mediashare_delete()
{
  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

    // Note: Do not return on errors - this makes it impossible to remove an incomplete installation

  $dict = NewDataDictionary($dbconn);

    // Drop the albums table

  $sqlArray = $dict->DropTableSQL($pntable['mediashare_albums']);

  $result = $dict->ExecuteSQLArray($sqlArray);

    // Check for an error with the database code
  if ($result != 2)
    pnSessionSetVar('errormsg', 'Drop table failed ' . ': ' . 
                    $dbconn->ErrorMsg() . ' while executing ' . $sqlArray[0]);

    // Drop the media items table

  $sqlArray = $dict->DropTableSQL($pntable['mediashare_media']);

  $result = $dict->ExecuteSQLArray($sqlArray);

    // Check for an error with the database code
  if ($result != 2)
    pnSessionSetVar('errormsg', 'Drop table failed ' . ': ' . 
                    $dbconn->ErrorMsg() . ' while executing ' . $sqlArray[0]);

    // Drop the media storage table

  $sqlArray = $dict->DropTableSQL($pntable['mediashare_mediastore']);

  $result = $dict->ExecuteSQLArray($sqlArray);

    // Drop the media DB table

  $sqlArray = $dict->DropTableSQL($pntable['mediashare_mediadb']);

  $result = $dict->ExecuteSQLArray($sqlArray);

    // Check for an error with the database code
  if ($result != 2)
    pnSessionSetVar('errormsg', 'Drop table failed ' . ': ' . 
                    $dbconn->ErrorMsg() . ' while executing ' . $sqlArray[0]);

    // Drop the media handler table

  $sqlArray = $dict->DropTableSQL($pntable['mediashare_mediahandlers']);

  $result = $dict->ExecuteSQLArray($sqlArray);

    // Check for an error with the database code
  if ($result != 2)
    pnSessionSetVar('errormsg', 'Drop table failed ' . ': ' . 
                    $dbconn->ErrorMsg() . ' while executing ' . $sqlArray[0]);

    // Drop the sources table

  $sqlArray = $dict->DropTableSQL($pntable['mediashare_sources']);

  $result = $dict->ExecuteSQLArray($sqlArray);

    // Check for an error with the database code
  if ($result != 2)
    pnSessionSetVar('errormsg', 'Drop table failed ' . ': ' . 
                    $dbconn->ErrorMsg() . ' while executing ' . $sqlArray[0]);

    // Drop the access control table

  $sqlArray = $dict->DropTableSQL($pntable['mediashare_access']);

  $result = $dict->ExecuteSQLArray($sqlArray);

    // Check for an error with the database code
  if ($result != 2)
    pnSessionSetVar('errormsg', 'Drop table failed ' . ': ' . 
                    $dbconn->ErrorMsg() . ' while executing ' . $sqlArray[0]);

    // Drop the setup table

  $sqlArray = $dict->DropTableSQL($pntable['mediashare_setup']);

  $result = $dict->ExecuteSQLArray($sqlArray);

    // Check for an error with the database code
  if ($result != 2)
    pnSessionSetVar('errormsg', 'Drop table failed ' . ': ' . 
                    $dbconn->ErrorMsg() . ' while executing ' . $sqlArray[0]);

    // Drop the photoshare table

  $sqlArray = $dict->DropTableSQL($pntable['mediashare_photoshare']);

  $result = $dict->ExecuteSQLArray($sqlArray);

    // Check for an error with the database code
  if ($result != 2)
    pnSessionSetVar('errormsg', 'Drop table failed ' . ': ' . 
                    $dbconn->ErrorMsg() . ' while executing ' . $sqlArray[0]);

    // Drop the keywords table
 
  $sqlArray = $dict->DropTableSQL($pntable['mediashare_keywords']);

  $result = $dict->ExecuteSQLArray($sqlArray);

    // Check for an error with the database code
  if ($result != 2)
    pnSessionSetVar('errormsg', 'Drop table failed ' . ': ' . 
                    $dbconn->ErrorMsg() . ' while executing ' . $sqlArray[0]);

    // Drop the invitation table
 
  $sqlArray = $dict->DropTableSQL($pntable['mediashare_invitation']);

  $result = $dict->ExecuteSQLArray($sqlArray);

    // Check for an error with the database code
  if ($result != 2)
    pnSessionSetVar('errormsg', 'Drop table failed ' . ': ' . 
                    $dbconn->ErrorMsg() . ' while executing ' . $sqlArray[0]);


  pnModDelVar('mediashare', 'tmpDirName');
  pnModDelVar('mediashare', 'mediaDirName');
  pnModDelVar('mediashare', 'thumbnailSize');
  pnModDelVar('mediashare', 'previewSize');
  pnModDelVar('mediashare', 'mediaSizeLimitSingle');
  pnModDelVar('mediashare', 'mediaSizeLimitTotal');
  pnModDelVar('mediashare', 'defaultAlbumTemplate');
  pnModDelVar('mediashare', 'defaultSlideshowTemplate');

    // Deletion always successful
  return true;
}


// -----------------------------------------------------------------------
// Error handling
// -----------------------------------------------------------------------

function mediashareInitError($file, $line, $msg)
{
  pnSessionSetVar('errormsg', "$file($line): $msg");

  return false;
}

?>