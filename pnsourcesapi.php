<?php
// $Id$
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2005.
// =======================================================================

function mediashare_sourcesapi_getSources(&$args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    list ($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $sourcesTable  = $pntable['mediashare_sources'];
    $sourcesColumn = $pntable['mediashare_sources_column'];

    $sql = "SELECT $sourcesColumn[name],
                   $sourcesColumn[title],
                   $sourcesColumn[formEncType]
              FROM $sourcesTable";

    $result = $dbconn->execute($sql);

    if ($dbconn->errorNo() != 0) {
        return LogUtil::registerError(__f('Error in %1$s: %2$%', array('sourcesapi.getSources', 'Could not retrieve the sources.'), $dom));
    }

    $sources = array();

    for (; !$result->EOF; $result->moveNext()) {
        $sources[] = array('name' => $result->fields[0],
                           'title' => $result->fields[1],
                           'formEncType' => $result->fields[2]);
    }

    $result->close();

    return $sources;
}

function mediashare_sourcesapi_scanSources($args)
{
    // Check access
    if (!SecurityUtil::checkPermission('mediashare::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    $dom = ZLanguage::getModuleDomain('mediashare');

    // Clear existing sources table
    if (!DBUtil::truncateTable('mediashare_sources')) {
        return LogUtil::registerError(__f('Error in %1$s: %2$%', array('sourcesapi.scanSources', __f("Could not clear the '%s' table.", 'sources', $dom)), $dom));
    }

    // Scan for sources APIs
    $files = FileUtil::getFiles('modules/mediashare', false, true, 'php', 'f');
    foreach ($files as $file)
    {
        if (preg_match('/^pnsource_([-a-zA-Z0-9_]+)api.php$/', $file, $matches)) {
            $sourceName = $matches[1];
            $sourceApi = "source_$sourceName";

            // Force load - it is used during pninit
            pnModAPILoad('mediashare', $sourceApi, true);

            $title = pnModAPIFunc('mediashare', $sourceApi, 'getTitle');

            if ($title === false) {
                return false;
            }

            $ok = pnModAPIFunc('mediashare', 'sources', 'addSource', array('title' => $title, 'name' => $sourceName));
            if ($ok === false) {
                return false;
            }
        }
    }

    return true;
}

function mediashare_sourcesapi_addSource($args)
{
    $title = $args['title'];
    $name  = $args['name'];

    list ($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $sourcesTable  = $pntable['mediashare_sources'];
    $sourcesColumn = $pntable['mediashare_sources_column'];

    $sql = "INSERT INTO $sourcesTable (
            $sourcesColumn[name],
            $sourcesColumn[title],
			$sourcesColumn[formEncType])
          VALUES (
            '" . DataUtil::formatForStore($name) . "',
            '" . DataUtil::formatForStore($title) . "',
			'')";

    $dbconn->execute($sql);

    if ($dbconn->errorNo() != 0) {
        return LogUtil::registerError(__f('Error in %1$s: %2$%', array('sourcesapi.addSource', 'Could not add a source.'), $dom));
    }

    return true;
}
