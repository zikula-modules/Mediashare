<?php
// $Id: accessapi.php,v 1.4 2007/07/24 19:36:12 jornlind Exp $
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2003.
// =======================================================================

class mediashareAccessApi
{
  function hasAlbumAccess($albumId, $access, $viewKey)
  {
    // Admin can do everything
    if (pnSecAuthAction(0, 'mediashare::', '::', ACCESS_ADMIN))
      return true;

    $userId = (int)pnUserGetVar('uid');

    // Owner can do everything
    $album = pnModAPIFunc('mediashare', 'user', 'getAlbum',
                           array('albumId' => $albumId));
    if ($album === false)
      return mediashareErrorAPIGet();

    if ($album['ownerId'] == $userId)
      return true;


    // Don't enable any edit access if not having normal Zikula edit access
    if (!pnSecAuthAction(0, 'mediashare::', '::', ACCESS_EDIT))
      $access = $access & ~mediashareAccessRequirementEditSomething;

    // Must have normal PN read access to the module
    if (!pnSecAuthAction(0, 'mediashare::', '::', ACCESS_READ))
      return false;

    // Anonymous is not allowed to add stuff, so remove those bits
    if (!pnUserLoggedIn())
      $access = $access & ~mediashareAccessRequirementAddSomething;

    pnModDBInfoLoad('Groups'); // Make sure groups database info is available

    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $accessTable  = $pntable['mediashare_access'];
    $accessColumn = $pntable['mediashare_access_column'];
    $membershipTable  = $pntable['group_membership'];
    $membershipColumn = &$pntable['group_membership_column'];

    $invitedAlbums = pnModAPIFunc('mediashare', 'invitation', 'getInvitedAlbums', array());
    if (    is_array($invitedAlbums)  &&  $invitedAlbums[$albumId]  
        && ($access & mediashareAccessRequirementView) == mediashareAccessRequirementView)
      return true;

    $sql = "SELECT COUNT(*)
            FROM $accessTable
            LEFT JOIN $membershipTable
                  ON     $membershipColumn[gid] = $accessColumn[groupId]
                     AND $membershipColumn[uid] = $userId
            WHERE     $accessColumn[albumId] = $albumId
                  AND ($accessColumn[access] & $access) != 0
                  AND ($membershipColumn[gid] IS NOT NULL OR $accessColumn[groupId] = -1)";

    $dbresult = $dbconn->execute($sql);
    if ($dbconn->errorNo() != 0)
    {
      echo mediashareErrorPage(__FILE__, __LINE__, '"hasAlbumAccess" failed: ' . $dbconn->errorMsg() . " while executing: $sql");
      return false;
    }

    $hasAccess = $dbresult->fields[0];

    $dbresult->close();

    return $hasAccess > 0;
  }


  function getAlbumAccess($albumId)
  {
    // Admin can do everything
    if (pnSecAuthAction(0, 'mediashare::', '::', ACCESS_ADMIN))
      return 0xFF;

    $userId = (int)pnUserGetVar('uid');

    // Owner can do everything
    $album = pnModAPIFunc('mediashare', 'user', 'getAlbum',
                           array('albumId' => $albumId));
    if ($album === false)
      return mediashareErrorAPIGet();

    if ($album['ownerId'] == $userId)
      return 0xFF;


    pnModDBInfoLoad('Groups'); // Make sure groups database info is available

    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $accessTable  = $pntable['mediashare_access'];
    $accessColumn = $pntable['mediashare_access_column'];
    $membershipTable  = $pntable['group_membership'];
    $membershipColumn = &$pntable['group_membership_column'];

    if (!pnSecAuthAction(0, 'mediashare::', '::', ACCESS_READ))
      return 0x00;

    $sql = "SELECT $accessColumn[access]
            FROM $accessTable
            LEFT JOIN $membershipTable
                  ON     $membershipColumn[gid] = $accessColumn[groupId]
                     AND $membershipColumn[uid] = $userId
            WHERE     $accessColumn[albumId] = $albumId
                  AND ($membershipColumn[gid] IS NOT NULL OR $accessColumn[groupId] = -1)";

    $dbresult = $dbconn->execute($sql);
    if ($dbconn->errorNo() != 0)
      return mediashareErrorAPI(__FILE__, __LINE__, '"getAlbumAccess" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

    $access = 0x00;
    for (; !$dbresult->EOF; $dbresult->MoveNext())
    {
      $access |= (int)$dbresult->fields[0];
    }

    $dbresult->close();

    $invitedAlbums = pnModAPIFunc('mediashare', 'invitation', 'getInvitedAlbums', array());
    if (is_array($invitedAlbums)  &&  $invitedAlbums[$albumId])
      $access |= mediashareAccessRequirementView;

    return $access;
  }


  function getAccessibleAlbumsSql($albumId, $access, $field)
  {
    // Admin can do everything
    if (pnSecAuthAction(0, 'mediashare::', '::', ACCESS_ADMIN))
      return '1=1';

    if (!pnSecAuthAction(0, 'mediashare::', '::', ACCESS_READ))
      return '1=0';

    $userId = (int)pnUserGetVar('uid');

    pnModDBInfoLoad('Groups'); // Make sure groups database info is available

    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $albumsTable   = $pntable['mediashare_albums'];
    $albumsColumn  = $pntable['mediashare_albums_column'];
    $accessTable  = $pntable['mediashare_access'];
    $accessColumn = $pntable['mediashare_access_column'];
    $membershipTable  = $pntable['group_membership'];
    $membershipColumn = &$pntable['group_membership_column'];

    $invitedAlbums = pnModAPIFunc('mediashare', 'invitation', 'getInvitedAlbums', array());
    $invitedSql = '';
    
    if (is_array($invitedAlbums) && ($access & mediashareAccessRequirementView) == mediashareAccessRequirementView)
    {
      foreach ($invitedAlbums as $albumId => $ok)
      {
        if ($ok)
        {
          if (!empty($invitedSql))
            $invitedSql .= ',';
          $invitedSql .= (int)$albumId;
        }
      }
    }

    $parentAlbumSql = '';
    if ($albumId != null)
      $parentAlbumSql = "$albumsColumn[parentAlbumId] = $albumId AND";

    $sql = "SELECT DISTINCT $albumsColumn[id]
            FROM $albumsTable
            LEFT JOIN $accessTable
                 ON $accessColumn[albumId] = $albumsColumn[id]
            LEFT JOIN $membershipTable
                  ON     $membershipColumn[gid] = $accessColumn[groupId]
                     AND $membershipColumn[uid] = $userId
            WHERE $parentAlbumSql
                  (    ($accessColumn[access] & $access) != 0
                   AND ($membershipColumn[gid] IS NOT NULL OR $accessColumn[groupId] = -1)
                   OR  $albumsColumn[ownerId] = $userId)";

    //echo "<pre>$sql</pre><br/>\n";
    $dbresult = $dbconn->execute($sql);
    if ($dbconn->errorNo() != 0)
      return mediashareErrorAPI(__FILE__, __LINE__, '"getAccessibleAlbumsSql" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

    $albumIds = $invitedSql;
    for (; !$dbresult->EOF; $dbresult->MoveNext())
    {
      if ($albumIds != '')
        $albumIds .= ',';

      $albumIds .= $dbresult->fields[0];
    }
    $dbresult->close();

    //echo "Acces bits = $access. Albums = ($albumIds). ";
    return $albumIds == '' ? '1=0' : "$field IN ($albumIds)";
  }


  function hasItemAccess($mediaId, $access, $viewKey)
  {
    $item = pnModAPIFunc('mediashare', 'user', 'getMediaItem',
                         array('mediaId' => $mediaId));
    if ($item === false)
      return false;

    // Owner can do everything
    $userId = (int)pnUserGetVar('uid');

    if ($item['ownerId'] == $userId)
      return true;
    
    $albumId = $item['parentAlbumId'];

    return $this->hasAlbumAccess($albumId, $access, $viewKey);
  }


  function hasUserRealNameAccess()
  {
    return true;
  }
}


function mediashareCreateAccessAPI()
{
  return new mediashareAccessApi();
}


?>