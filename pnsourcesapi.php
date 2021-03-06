<?php
// $Id$
//
// Mediashare by Jorn Lind-Nielsen (C)
//

function mediashare_sourcesapi_getSources($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

	$pntable = pnDBGetTables();
		
	$sourcesTable  = $pntable['mediashare_sources'];
    $sourcesColumn = $pntable['mediashare_sources_column'];
		  		
		$where = "";
    if ($args['active']) {
        $where = "WHERE $sourcesColumn[active] = '" . DataUtil::formatForStore($args['active']) . "'";
    } 
					
    $result = DBUtil::selectObjectArray('mediashare_sources', $where);

    if ($result === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('sourcesapi.getSources', 'Could not retrieve the sources.'), $dom));
    }

    return $result;
}

function mediashare_sourcesapi_scanSources()
{
    // Check access
    if (!SecurityUtil::checkPermission('mediashare::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    $dom = ZLanguage::getModuleDomain('mediashare');

    // Clear existing sources table
    if (!DBUtil::truncateTable('mediashare_sources')) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('sourcesapi.scanSources', __f("Could not clear the '%s' table.", 'sources', $dom)), $dom));
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

            if (!($title = pnModAPIFunc('mediashare', $sourceApi, 'getTitle'))) {
                return false;
            }

            if (!pnModAPIFunc('mediashare', 'sources', 'addSource', array('title' => $title, 'name' => $sourceName))) {
                return false;
            }
        }
    }

    return true;
}

function mediashare_sourcesapi_addSource($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $title = $args['title'];
    $name  = $args['name'];

    $pntable       = pnDBGetTables();
    $sourcesColumn = $pntable['mediashare_sources_column'];

    $source = array(
        'name'        => $name,
        'title'       => $title,
        'formEncType' => ''
    );

    $result = DBUtil::insertObject($source, 'mediashare_sources', 'id');

    if ($result === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('sourcesapi.addSource', 'Could not add a source.'), $dom));
    }

    return true;
}


function mediashare_sourcesapi_OnOffsources($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

		if ($args['id'] === false) {
        return false;    
		}
		
		$source = DBUtil::selectObjectByID('mediashare_sources', $args['id']);
		
		
		if ($args['active'] === flase) {
				$source['active'] = 0;
    }else {
				$source['active'] = $args['active'];
				// todo turn off same mimeTypes

		}
				
		$result = DBUTil::updateObject($source, 'mediashare_sources');
		
		if ($result === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('mediahandlerapi.addHandler', 'Could not change source status.'), $dom));
    }

		
		//turn off same mimeTypes
		
		
    return true;
}
