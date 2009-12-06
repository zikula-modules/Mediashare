<?php
// $Id$
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2002.
// =======================================================================


require_once ("modules/mediashare/common-edit.php");

// -----------------------------------------------------------------------
// Module initialization
// -----------------------------------------------------------------------
function mediashare_init()
{
    $dom = ZLanguage::getModuleDomain('mediashare');
    list ($dbconn) = pnDBGetConn();
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
          $albumColumn[template] C(255) NOTNULL DEFAULT 'msslideshow',
          $albumColumn[parentAlbumId] I,
          $albumColumn[access] I1 NOTNULL DEFAULT 0,
          $albumColumn[viewKey] C(32) NOTNULL,
          $albumColumn[mainMediaId] I,
          $albumColumn[thumbnailSize] I NOTNULL,
          $albumColumn[nestedSetLeft] I NOTNULL DEFAULT 0,
          $albumColumn[nestedSetRight] I NOTNULL DEFAULT 0,
          $albumColumn[nestedSetLevel] I NOTNULL DEFAULT 0,
          $albumColumn[extappURL] C(255),
          $albumColumn[extappData] C(512)";

    $sqlArray = $dict->CreateTableSQL($albumTable, $sql, $taboptarray);
    $result = $dict->ExecuteSQLArray($sqlArray);

    // Check for an error with the database code, and if so set an
    // appropriate error message and return
    if ($result != 2) {
        return LogUtil::registerError('Table creation failed: ' . $dbconn->ErrorMsg() . ' while executing' . $sqlArray[0]);
    }
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
    if ($result != 2) {
        return LogUtil::registerError('Table creation failed: ' . $dbconn->ErrorMsg() . ' while executing' . $sqlArray[0]);
    }

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
    if ($result != 2) {
        return LogUtil::registerError('Table creation failed: ' . $dbconn->ErrorMsg() . ' while executing' . $sqlArray[0]);
    }
    // Add index on keyword
    $indexFields = $keywordsColumn['keyword'];
    $sqlArray = $dict->CreateIndexSQL('keywordsKeywordIdx', $keywordsTable, $indexFields);
    $result = $dict->ExecuteSQLArray($sqlArray);

    // Check for an error with the database code, and if so set an
    // appropriate error message and return
    if ($result != 2) {
        return LogUtil::registerError('Table creation failed: ' . $dbconn->ErrorMsg() . ' while executing' . $sqlArray[0]);
    }
    // Media store creation


    $mediastoreTable = $pntable['mediashare_mediastore'];
    $mediastoreColumn = &$pntable['mediashare_mediastore_column'];

    // Create the table
    $sql = "$mediastoreColumn[id] I NOTNULL AUTOINCREMENT KEY,
          $mediastoreColumn[mimeType] C(100) NOTNULL,
          $mediastoreColumn[fileRef] C(300) NOTNULL,
          $mediastoreColumn[width] I2 NOTNULL,
          $mediastoreColumn[height] I2 NOTNULL,
          $mediastoreColumn[bytes] I NOTNULL";

    $sqlArray = $dict->CreateTableSQL($mediastoreTable, $sql, $taboptarray);
    $result = $dict->ExecuteSQLArray($sqlArray);

    // Check for an error with the database code, and if so set an
    // appropriate error message and return
    if ($result != 2) {
        return LogUtil::registerError('Table creation failed: ' . $dbconn->ErrorMsg() . ' while executing' . $sqlArray[0]);
    }

    if (!mediashareCreateMediaDB($dbconn, $pntable, $dict, $taboptarray)) {
        return false;
    }
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
    if ($result != 2) {
        return LogUtil::registerError('Table creation failed: ' . $dbconn->ErrorMsg() . ' while executing' . $sqlArray[0]);
    }

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
    if ($result != 2) {
        return LogUtil::registerError('Table creation failed: ' . $dbconn->ErrorMsg() . ' while executing' . $sqlArray[0]);
    }

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
    if ($result != 2) {
        return LogUtil::registerError('Table creation failed: ' . $dbconn->ErrorMsg() . ' while executing' . $sqlArray[0]);
    }

    $indexFields = $accessColumn['albumId'];
    $sqlArray = $dict->CreateIndexSQL('accessAlbumIdx', $accessTable, $indexFields);
    $result = $dict->ExecuteSQLArray($sqlArray);

    // Check for an error with the database code, and if so set an
    // appropriate error message and return
    if ($result != 2) {
        return LogUtil::registerError('Table creation failed: ' . $dbconn->ErrorMsg() . ' while executing' . $sqlArray[0]);
    }
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
    if ($result != 2) {
        return LogUtil::registerError('Table creation failed: ' . $dbconn->ErrorMsg() . ' while executing' . $sqlArray[0]);
    }

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
    if ($result != 2) {
        return LogUtil::registerError('Table creation failed: ' . $dbconn->ErrorMsg() . ' while executing' . $sqlArray[0]);
    }

    if (!mediashareCreateInvitationTable($dbconn, $pntable, $dict, $taboptarray)) {
        return false;
    }
    // Initialize global variables


    if (!pnModSetVar('mediashare', 'tmpDirName', '/tmp')) {
        return LogUtil::registerError('Set module var tmpDirName failed');
    }

    if (!pnModSetVar('mediashare', 'mediaDirName', str_replace('/modules', '', dirname(__FILE__)))) {
        return LogUtil::registerError('Set module var mediaDirName failed');
    }

    if (!pnModSetVar('mediashare', 'thumbnailSize', '100')) {
        return LogUtil::registerError('Set module var thumbnailSize failed');
    }

    if (!pnModSetVar('mediashare', 'previewSize', '400')) {
        return LogUtil::registerError('Set module var previewSize failed');
    }

    if (!pnModSetVar('mediashare', 'mediaSizeLimitSingle', 250000)) {
        return LogUtil::registerError('Set module var mediaSizeLimitSingle failed');
    }

    if (!pnModSetVar('mediashare', 'mediaSizeLimitTotal', 5000000)) {
        return LogUtil::registerError('Set module var mediaSizeLimitTotal failed');
    }

    if (!pnModSetVar('mediashare', 'defaultAlbumTemplate', 'standard')) {
        return LogUtil::registerError('Set module var defaultAlbumTemplate failed');
    }

    if (!pnModSetVar('mediashare', 'defaultSlideshowTemplate', 'standard')) {
        return LogUtil::registerError('Set module var defaultSlideshowTemplate failed');
    }

    // Scan for plugins
    pnModAPILoad('mediashare', 'admin', true);

    $ok = pnModAPIFunc('mediashare', 'admin', 'scanAllPlugins');
    if ($ok === false) {
        LogUtil::registerError(LogUtil::getErrorMessagesText());
    }

    // Add top album
    pnModAPILoad('mediashare', 'edit', true);
    pnModAPILoad('mediashare', 'user', true);

    $topAlbum = array('title' => __('Top', $dom), 'keywords' => '', 'summary' => '', 'description' => __('This is the top album (of which there can be only one). You can edit this album to change the title and other attributes of it.', $dom), 'parentAlbumId' => 0);
    $topId = pnModAPIFunc('mediashare', 'edit', 'addAlbum', $topAlbum);
    if ($topId === false) {
        return LogUtil::registerError(LogUtil::getErrorMessagesText());
    }
    $ok = pnModAPIFunc('mediashare', 'edit', 'setDefaultAccess', array('albumId' => $topId, 'usersMayAddAlbum' => true));
    if ($ok === false) {
        return LogUtil::registerError(LogUtil::getErrorMessagesText());
    }

    if (!mediashareCreateMediashareUpdateNestedSetValues()) {
        return false;
    }
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
    if ($result != 2) {
        return LogUtil::registerError('Table creation failed: ' . $dbconn->ErrorMsg() . ' while executing' . $sqlArray[0]);
    }
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
    if ($result != 2) {
        return LogUtil::registerError('Table creation failed: ' . $dbconn->ErrorMsg() . ' while executing' . $sqlArray[0]);
    }
    return true;
}

function mediashareCreateMediashareUpdateNestedSetValues()
{
    list ($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $albumsTable = $pntable['mediashare_albums'];
    $albumsColumn = &$pntable['mediashare_albums_column'];

    $procSql = "
create procedure mediashareUpdateNestedSetValuesRec(albumId int, level int, inout count int)
begin
  declare done int default 0;
  declare nleft int;
  declare nright int;
  declare subAlbumId int;

  declare albumsCur cursor for
    select $albumsColumn[id]
    from $albumsTable
    where $albumsColumn[parentAlbumId] = albumId
    order by ms_title;

  declare continue handler for sqlstate '02000' set done = 1;

  set max_sp_recursion_depth = 100;

  open albumsCur;

  set nleft = count;
  set count = count + 1;

  repeat
    fetch albumsCur into subAlbumId;
    if not done then
      call mediashareUpdateNestedSetValuesRec(subAlbumId, level+1, count);
    end if;
  until done end repeat;

  close albumsCur;

  set nright = count;
  set count = count + 1;

  update $albumsTable set
    $albumsColumn[nestedSetLeft] = nleft,
    $albumsColumn[nestedSetRight] = nright,
    $albumsColumn[nestedSetLevel] = level
  where $albumsColumn[id] = albumId;
end
";

    // Ignore errors
    $dbconn->execute($procSql);

    $procSql = "
create procedure mediashareUpdateNestedSetValues()
begin
  declare count int default 0;
  call mediashareUpdateNestedSetValuesRec(0,0,count);
end
";

    // Ignore errors
    $dbconn->execute($procSql);

    return true;
}

// -----------------------------------------------------------------------
// Module upgrade
// -----------------------------------------------------------------------
function mediashare_upgrade($oldVersion)
{
    $ok = true;

    // Upgrade dependent on old version number
    switch ($oldVersion) {
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
        case '3.4.1':
            $ok = $ok && mediashare_upgrade_to_3_4_1($oldVersion);
        case '3.4.2':
        case '4.0.0':
        // future
    }

    // Update successful
    return $ok;
}

function mediashare_upgrade_to_1_0_2($oldVersion)
{
    $newVersion = '1.0.2';

    list ($dbconn) = pnDBGetConn();
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
    if ($result != 2) {
        return LogUtil::registerError('Album table change failed: ' . $dbconn->ErrorMsg() . ' while executing' . $sqlArray[0]);
    }

    $mediaTable = $pntable['mediashare_media'];
    $mediaColumn = &$pntable['mediashare_media_column'];

    $sql = "$albumColumn[createdDate] T NOTNULL,
          $albumColumn[modifiedDate] T NOTNULL DEFTIMESTAMP";

    $sqlArray = $dict->AlterColumnSQL($mediaTable, $sql, $taboptarray);
    $result = $dict->ExecuteSQLArray($sqlArray);

    // Check for an error with the database code, and if so set an
    // appropriate error message and return
    if ($result != 2) {
        return LogUtil::registerError('Media table change failed: ' . $dbconn->ErrorMsg() . ' while executing' . $sqlArray[0]);
    }
    $sql = "UPDATE $albumTable
          SET $albumColumn[modifiedDate] = $albumColumn[createdDate]";
    $dbconn->execute($sql);

    if ($dbconn->errorNo() != 0) {
        return LogUtil::registerError('Media table change failed: ' . $dbconn->ErrorMsg() . ' while executing' . $sql);
    }

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

    list ($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $dict = NewDataDictionary($dbconn);
    $taboptarray = pnDBGetTableOptions();

    $sqlArray = $dict->DropColumnSQL($pntable['mediashare_mediastore'], $pntable['mediashare_mediastore_column']['data']);
    $result = $dict->ExecuteSQLArray($sqlArray);

    // Check for an error with the database code
    if ($result != 2) {
        return LogUtil::registerError('Drop column "data" failed ' . ': ' . $dbconn->ErrorMsg() . ' while executing ' . $sqlArray[0]);
    }
    if (!mediashareCreateMediaDB($dbconn, $pntable, $dict, $taboptarray)) {
        return false;
    }

    return true;
}

function mediashare_upgrade_to_2_3_0($oldVersion)
{
    $newVersion = '2.3.0';

    list ($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $dict = NewDataDictionary($dbconn);
    $taboptarray = pnDBGetTableOptions();

    return mediashareCreateInvitationTable($dbconn, $pntable, $dict, $taboptarray);
}

function mediashare_upgrade_to_3_4_0($oldVersion)
{
    $newVersion = '3.4.0';

    list ($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $dict = NewDataDictionary($dbconn);
    $taboptarray = pnDBGetTableOptions();

    $albumTable = $pntable['mediashare_albums'];
    $albumColumn = &$pntable['mediashare_albums_column'];

    $columns = "$albumColumn[extappURL] C(255),
              $albumColumn[extappData] C(512)";

    $sqlArray = $dict->AddColumnSQL($pntable['mediashare_albums'], $columns);
    $result = $dict->ExecuteSQLArray($sqlArray);

    // Check for an error with the database code
    if ($result != 2) {
        return LogUtil::registerError('Add columns failed ' . ': ' . $dbconn->ErrorMsg() . ' while executing ' . $sqlArray[0]);
    }
    return true;
}

function mediashare_upgrade_to_3_4_1($oldVersion)
{
    $newVersion = '3.4.1';

    list ($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $dict = NewDataDictionary($dbconn);
    $taboptarray = pnDBGetTableOptions();

    $storeTable = $pntable['mediashare_mediastore'];
    $storeColumn = &$pntable['mediashare_mediastore_column'];

    $columns = "$storeColumn[fileRef] C(300)";

    $sqlArray = $dict->AlterColumnSQL($storeTable, $columns);
    $result = $dict->ExecuteSQLArray($sqlArray);

    // Check for an error with the database code
    if ($result != 2) {
        return LogUtil::registerError('Add columns failed ' . ': ' . $dbconn->ErrorMsg() . ' while executing ' . $sqlArray[0]);
    }
    return true;
}

function mediashare_upgrade_to_3_4_2($oldVersion)
{
    mediashareCreateMediashareUpdateNestedSetValues();
    // Ignore stored procedure creation failure
    return true;
}

// -----------------------------------------------------------------------
// Module delete
// -----------------------------------------------------------------------
function mediashare_delete()
{
    list ($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    // Note: Do not return on errors - this makes it impossible to remove an incomplete installation


    $dict = NewDataDictionary($dbconn);

    // Drop the albums table


    $sqlArray = $dict->DropTableSQL($pntable['mediashare_albums']);

    $result = $dict->ExecuteSQLArray($sqlArray);

    // Check for an error with the database code
    if ($result != 2) {
        LogUtil::registerError('Drop table failed ' . ': ' . $dbconn->ErrorMsg() . ' while executing ' . $sqlArray[0]);
    }
    // Drop the media items table


    $sqlArray = $dict->DropTableSQL($pntable['mediashare_media']);

    $result = $dict->ExecuteSQLArray($sqlArray);

    // Check for an error with the database code
    if ($result != 2) {
        SessionUtil::setVar('errormsg', 'Drop table failed ' . ': ' . $dbconn->ErrorMsg() . ' while executing ' . $sqlArray[0]);
    }
    // Drop the media storage table


    $sqlArray = $dict->DropTableSQL($pntable['mediashare_mediastore']);

    $result = $dict->ExecuteSQLArray($sqlArray);

    // Drop the media DB table


    $sqlArray = $dict->DropTableSQL($pntable['mediashare_mediadb']);

    $result = $dict->ExecuteSQLArray($sqlArray);

    // Check for an error with the database code
    if ($result != 2) {
        LogUtil::registerError('Drop table failed ' . ': ' . $dbconn->ErrorMsg() . ' while executing ' . $sqlArray[0]);
    }
    // Drop the media handler table


    $sqlArray = $dict->DropTableSQL($pntable['mediashare_mediahandlers']);

    $result = $dict->ExecuteSQLArray($sqlArray);

    // Check for an error with the database code
    if ($result != 2) {
        LogUtil::registerError('Drop table failed ' . ': ' . $dbconn->ErrorMsg() . ' while executing ' . $sqlArray[0]);
    }
    // Drop the sources table


    $sqlArray = $dict->DropTableSQL($pntable['mediashare_sources']);

    $result = $dict->ExecuteSQLArray($sqlArray);

    // Check for an error with the database code
    if ($result != 2) {
        LogUtil::registerError('Drop table failed ' . ': ' . $dbconn->ErrorMsg() . ' while executing ' . $sqlArray[0]);
    }
    // Drop the access control table


    $sqlArray = $dict->DropTableSQL($pntable['mediashare_access']);

    $result = $dict->ExecuteSQLArray($sqlArray);

    // Check for an error with the database code
    if ($result != 2) {
        LogUtil::registerError('Drop table failed ' . ': ' . $dbconn->ErrorMsg() . ' while executing ' . $sqlArray[0]);
    }
    // Drop the setup table


    $sqlArray = $dict->DropTableSQL($pntable['mediashare_setup']);

    $result = $dict->ExecuteSQLArray($sqlArray);

    // Check for an error with the database code
    if ($result != 2) {
        LogUtil::registerError('Drop table failed ' . ': ' . $dbconn->ErrorMsg() . ' while executing ' . $sqlArray[0]);
    }
    // Drop the photoshare table


    $sqlArray = $dict->DropTableSQL($pntable['mediashare_photoshare']);

    $result = $dict->ExecuteSQLArray($sqlArray);

    // Check for an error with the database code
    if ($result != 2) {
        LogUtil::registerError('Drop table failed ' . ': ' . $dbconn->ErrorMsg() . ' while executing ' . $sqlArray[0]);
    }
    // Drop the keywords table


    $sqlArray = $dict->DropTableSQL($pntable['mediashare_keywords']);

    $result = $dict->ExecuteSQLArray($sqlArray);

    // Check for an error with the database code
    if ($result != 2) {
        LogUtil::registerError('Drop table failed ' . ': ' . $dbconn->ErrorMsg() . ' while executing ' . $sqlArray[0]);
    }
    // Drop the invitation table


    $sqlArray = $dict->DropTableSQL($pntable['mediashare_invitation']);

    $result = $dict->ExecuteSQLArray($sqlArray);

    // Check for an error with the database code
    if ($result != 2) {
        LogUtil::registerError('Drop table failed ' . ': ' . $dbconn->ErrorMsg() . ' while executing ' . $sqlArray[0]);
    }

    pnModDelVar('mediashare', 'tmpDirName');
    pnModDelVar('mediashare', 'mediaDirName');
    pnModDelVar('mediashare', 'thumbnailSize');
    pnModDelVar('mediashare', 'previewSize');
    pnModDelVar('mediashare', 'mediaSizeLimitSingle');
    pnModDelVar('mediashare', 'mediaSizeLimitTotal');
    pnModDelVar('mediashare', 'defaultAlbumTemplate');
    pnModDelVar('mediashare', 'defaultSlideshowTemplate');

    $sql = "drop procedure mediashareUpdateNestedSetValuesRec";
    $dbconn->execute($sql);

    $sql = "drop procedure mediashareUpdateNestedSetValues";
    $dbconn->execute($sql);

    // Deletion always successful
    return true;
}
