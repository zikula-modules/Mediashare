<?php
// $Id: pnsourcesapi.php,v 1.8 2008/04/28 11:24:21 jornlind Exp $
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2005.
// =======================================================================


function mediashare_sourcesapi_getSources(&$args)
{
  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $sourcesTable   = $pntable['mediashare_sources'];
  $sourcesColumn  = $pntable['mediashare_sources_column'];

  $sql = "SELECT $sourcesColumn[name],
                 $sourcesColumn[title],
                 $sourcesColumn[formEncType]
          FROM $sourcesTable";

  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"mediashare_sourcesapi_getSources" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  $sources = array();

  for (; !$result->EOF; $result->moveNext())
  {
    $source = array('name'        => $result->fields[0],
                    'title'       => $result->fields[1],
                    'formEncType' => $result->fields[2]);

    $sources[] = $source;
  }

  $result->close();

  return $sources;
}


function mediashare_sourcesapi_scanSources($args)
{
  // Check access
  if (!pnSecAuthAction(0, 'mediashare::', '::', ACCESS_ADMIN))
    return mediashareErrorAPI(__FILE__, __LINE__, _MSNOAUTH);

  // Clear existing sources table
  
  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $sourcesTable = $pntable['mediashare_sources'];
  $sql = "TRUNCATE TABLE $sourcesTable";

  $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"scanSources" failed: ' . $dbconn->errorMsg() . " while executing: $sql");


  // Scan for sources
  if ($dh = opendir("modules/mediashare"))
  {
    while (($filename=readdir($dh)) !== false)
    {
      if (preg_match('/^pnsource_([-a-zA-Z0-9_]+)api.php$/', $filename, $matches))
      {
        $sourceName = $matches[1];
        $sourceApi = "source_$sourceName";

        // Force load - it is used during pninit
        if (!pnModAPILoad('mediashare', $sourceApi, true))
          return mediashareErrorAPI(__FILE__, __LINE__, "Missing '$sourceApi' API in scanSources");

        $title = pnModAPIFunc('mediashare', $sourceApi, 'getTitle');

        if ($title === false)
        {
          closedir($dh);
          return false;
        }

        $ok = pnModAPIFunc('mediashare', 'sources', 'addSource', 
                           array('title' => $title,
                                 'name'  => $sourceName));
        if ($ok === false)
        {
          closedir($dh);
          return false;
        }
      }
    }

    closedir($dh);
  }

  return true;
}


function mediashare_sourcesapi_addSource($args)
{
  $title  = $args['title'];
  $name   = $args['name'];

  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $sourcesTable   = $pntable['mediashare_sources'];
  $sourcesColumn  = $pntable['mediashare_sources_column'];

  $sql = "INSERT INTO $sourcesTable (
            $sourcesColumn[name],
            $sourcesColumn[title],
			$sourcesColumn[formEncType])
          VALUES (
            '" . pnVarPrepForStore($name) . "',
            '" . pnVarPrepForStore($title) . "',
			'')";

  $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, '"addSource" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  return true;
}

?>