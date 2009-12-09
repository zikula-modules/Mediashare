<?php
// $Id$
//
// Mediashare by Jorn Lind-Nielsen (C) 2002.
//

require_once ("modules/mediashare/common-edit.php");

/**
 * Module initialization
 */
function mediashare_init()
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $tables = array('albums',
                    'media',
                    'keywords',
                    'mediastore',
                    'mediadb',
                    'mediahandlers',
                    'sources',
                    'access',
                    'setup',
                    'invitation');

    // Create the mediashare tables
    foreach ($tables as $table) {
        if (!DBUtil::createTable("mediashare_$table")) {
            return false;
        }
    }

    // Initialize module variables
    pnModSetVar('mediashare', 'tmpDirName', '/tmp');
    pnModSetVar('mediashare', 'mediaDirName', str_replace('/modules', '', dirname(__FILE__)));
    pnModSetVar('mediashare', 'thumbnailSize', '100');
    pnModSetVar('mediashare', 'previewSize', '400');
    pnModSetVar('mediashare', 'mediaSizeLimitSingle', 250000);
    pnModSetVar('mediashare', 'mediaSizeLimitTotal', 5000000);
    pnModSetVar('mediashare', 'defaultAlbumTemplate', 'standard');
    pnModSetVar('mediashare', 'defaultSlideshowTemplate', 'standard');
    pnModSetVar('mediashare', 'vfs', 'fsdirect');
    pnModSetVar('mediashare', 'enableSharpen', 1);

    // Scan for plugins
    pnModAPILoad('mediashare', 'admin', true);
    pnModAPIFunc('mediashare', 'admin', 'scanAllPlugins');

    // Add top album
    pnModAPILoad('mediashare', 'edit', true);
    pnModAPILoad('mediashare', 'user', true); // FIXME why this?

    $topAlbum = array('title'    => __('Top Album', $dom),
                      'description' => __('This is the top album (of which there can be only one). You can edit this album to change the title and other attributes of it.', $dom),
                      'keywords' => '',
                      'summary'  => '',
                      'parentAlbumId' => 0);

    if (!pnModAPIFunc('mediashare', 'edit', 'addAlbum', $topAlbum)) {
        return false;
    }
    
    if (!pnModAPIFunc('mediashare', 'edit', 'setDefaultAccess', array('albumId' => $topId, 'usersMayAddAlbum' => true))) {
        return false;
    }

    if (!mediashareCreateMediashareUpdateNestedSetValues()) {
        return false;
    }

    // Initialisation successful
    return true;
}

function mediashareCreateMediaDB()
{
    // Media DB creation
    if (!DBUtil::createTable('mediashare_mediadb')) {
        return false;
    }

    pnModSetVar('mediashare', 'vfs', 'fsdirect');
    pnModSetVar('mediashare', 'enableSharpen', 1);

    return true;
}

function mediashareCreateInvitationTable()
{
    // Media DB creation
    if (!DBUtil::createTable('mediashare_invitation')) {
        return false;
    }

    return true;
}

function mediashareCreateMediashareUpdateNestedSetValues()
{
    $pntable = &pnDBGetTables();

    $table   = $pntable['mediashare_albums'];
    $columns = &$pntable['mediashare_albums_column'];

    $procSql = "CREATE PROCEDURE mediashareUpdateNestedSetValuesRec(albumId int, level int, inout count int)
                BEGIN
                    DECLARE done int DEFAULT 0;
                    DECLARE nleft int;
                    DECLARE nright int;
                    DECLARE subAlbumId int;

                    DECLARE albumsCur CURSOR FOR
                        SELECT $columns[id]
                          FROM $table
                         WHERE $columns[parentAlbumId] = albumId
                      ORDER BY $columns[title];

                    DECLARE continue HANDLER FOR sqlstate '02000' SET done = 1;

                    SET max_sp_recursion_depth = 100;

                    OPEN albumsCur;

                    SET nleft = count;
                    SET count = count + 1;
                    
                    REPEAT
                        FETCH albumsCur INTO subAlbumId;
                        IF NOT done THEN
                            CALL mediashareUpdateNestedSetValuesRec(subAlbumId, level+1, count);
                        END IF;
                    UNTIL done END REPEAT;

                    CLOSE albumsCur;

                    SET nright = count;
                    SET count = count + 1;

                    UPDATE $table
                       SET $columns[nestedSetLeft] = nleft,
                           $columns[nestedSetRight] = nright,
                           $columns[nestedSetLevel] = level
                     WHERE $columns[id] = albumId;
                 END
                 ";

    // Ignore errors
    DBUtil::executeSQL($procSql);

    $procSql = "CREATE PROCEDURE mediashareUpdateNestedSetValues()
                BEGIN
                    DECLARE count int DEFAULT 0;
                    CALL mediashareUpdateNestedSetValuesRec(0,0,count);
                END
                ";

    // Ignore errors
    DBUtil::executeSQL($procSql);

    return true;
}

/**
 * Module upgrade
 */
function mediashare_upgrade($oldVersion)
{
    $ok = true;

    // Upgrade dependent on old version number
    switch ($oldVersion)
    {
        case '1.0.0':
        case '1.0.1':
            if (!mediashare_upgrade_to_1_0_2()) {
                return '1.0.1';
            }
        case '1.0.2':
        case '2.0.0':
        case '2.0.1':
        case '2.1.0':
            if (!mediashare_upgrade_to_2_1_1()) {
                return '2.1.0';
            }
        case '2.1.1':
        case '2.1.2':
            if (!mediashare_upgrade_to_2_2_0()) {
                return '2.1.2';
            }
        case '2.2.0':
            if (!mediashare_upgrade_to_2_3_0()) {
                return '2.2.0';
            }
        case '2.3.0':
        case '3.0.0':
        case '3.0.1':
        case '3.1.0':
        case '3.1.1':
        case '3.2.0':
        case '3.3.0':
            if (!mediashare_upgrade_to_3_4_0()) {
                return '3.3.0';
            }
        case '3.4.0':
            if (!mediashare_upgrade_to_3_4_1()) {
                return '3.4.0';
            }
        case '3.4.1':
            mediashare_upgrade_to_3_4_2();
        case '3.4.2':
        case '4.0.0':
        case '4.0.1':
            $tables = DBUtil::metaTables();
            $ptable = DBUtil::getLimitedTablename('mediashare_photoshare');
            if (in_array($ptable, $tables) && !DBUtil::dropTable('mediashare_photoshare')) {
                return '4.0.1';
            }
        case '4.0.2':
            // future
    }

    // Update successful
    return true;
}

function mediashare_upgrade_to_1_0_2()
{
    if (!DBUtil::changeTable('mediashare_albums')) {
        return false;
    }

    if (!DBUtil::changeTable('mediashare_media')) {
        return false;
    }

    $pntable = &pnDBGetTables();
    $albumTable  = $pntable['mediashare_albums'];
    $albumColumn = &$pntable['mediashare_albums_column'];

    $sql = "UPDATE $albumTable
               SET $albumColumn[modifiedDate] = $albumColumn[createdDate]";

    if (!DBUtil::executeSQL($sql)) {
        return false;
    }

    return true;
}

function mediashare_upgrade_to_2_1_1()
{
    pnModSetVar('mediashare', 'defaultAlbumTemplate', 'Lightbox');

    pnModAPILoad('mediashare', 'admin', true);
    if (!pnModAPIFunc('mediashare', 'admin', 'setTemplateGlobally', array('template' => 'Lightbox'))) {
        return false;
    }

    return true;
}

function mediashare_upgrade_to_2_2_0()
{
    $columns = array_keys(DBUtil::metaColumns('mediashare_mediastore', true));
    if (in_array('MSS_DATA', $columns) && !DBUtil::dropColumn('mediashare_mediastore', 'mss_data')) {
        return false;
    }

    if (!mediashareCreateMediaDB()) {
        return false;
    }

    return true;
}

function mediashare_upgrade_to_2_3_0()
{
    if (!mediashareCreateInvitationTable()) {
        return false;
    }

    return true;
}

function mediashare_upgrade_to_3_4_0()
{
    if (!DBUtil::changeTable('mediashare_albums')) {
        return false;
    }

    return true;
}

function mediashare_upgrade_to_3_4_1()
{
    if (!DBUtil::changeTable('mediashare_mediastore')) {
        return false;
    }

    return true;
}

function mediashare_upgrade_to_3_4_2()
{
    mediashareCreateMediashareUpdateNestedSetValues();

    // Ignore stored procedure creation failure
    return true;
}

/**
 * Module delete
 */
function mediashare_delete()
{
    $tables = array('albums',
                    'media',
                    'keywords',
                    'mediastore',
                    'mediadb',
                    'mediahandlers',
                    'sources',
                    'access',
                    'setup',
                    'invitation');

    // Delete the mediashare tables
    foreach ($tables as $table) {
        if (!DBUtil::dropTable("mediashare_$table")) {
            return false;
        }
    }

    pnModDelVar('mediashare');

    $sql = "DROP PROCEDURE mediashareUpdateNestedSetValuesRec";
    DBUtil::executeSQL($sql);

    $sql = "DROP PROCEDURE mediashareUpdateNestedSetValues";
    DBUtil::executeSQL($sql);

    // Deletion successful
    return true;
}
