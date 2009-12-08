<?php
// $Id$
//
// Mediashare by Jorn Lind-Nielsen (C) 2003.
//

class mediashareAccessApi
{
    function hasAlbumAccess($albumId, $access, $viewKey)
    {
        // Admin can do everything
        if (SecurityUtil::checkPermission('mediashare::', '::', ACCESS_ADMIN)) {
            return true;
        }

        $userId = (int)pnUserGetVar('uid');

        // Owner can do everything
        if (!($album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId)))) {
            return false;
        }
        if ($album['ownerId'] == $userId) {
            return true;
        }

        // Don't enable any edit access if not having normal Zikula edit access
        if (!SecurityUtil::checkPermission('mediashare::', '::', ACCESS_EDIT)) {
            $access = $access & ~mediashareAccessRequirementEditSomething;
        }

        // Must have normal PN read access to the module
        if (!SecurityUtil::checkPermission('mediashare::', '::', ACCESS_READ)) {
            return false;
        }

        // Anonymous is not allowed to add stuff, so remove those bits
        if (!pnUserLoggedIn()) {
            $access = $access & ~mediashareAccessRequirementAddSomething;
        }

        pnModDBInfoLoad('Groups'); // Make sure groups database info is available

        list ($dbconn) = pnDBGetConn();
        $pntable = pnDBGetTables();

        $accessTable = $pntable['mediashare_access'];
        $accessColumn = $pntable['mediashare_access_column'];
        $membershipTable = $pntable['group_membership'];
        $membershipColumn = &$pntable['group_membership_column'];

        $invitedAlbums = pnModAPIFunc('mediashare', 'invitation', 'getInvitedAlbums', array());
        if (is_array($invitedAlbums) && $invitedAlbums[$albumId] && ($access & mediashareAccessRequirementView) == mediashareAccessRequirementView) {
            return true;
        }

        $sql = "SELECT COUNT(*)
                  FROM $accessTable
             LEFT JOIN $membershipTable
                    ON $membershipColumn[gid] = $accessColumn[groupId]
                   AND $membershipColumn[uid] = $userId
                 WHERE $accessColumn[albumId] = $albumId
                   AND ($accessColumn[access] & $access) != 0
                   AND ($membershipColumn[gid] IS NOT NULL OR $accessColumn[groupId] = -1)";

        $dbresult = $dbconn->execute($sql);
        if ($dbconn->errorNo() != 0) {
            return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('accessapi.hasAlbumAccess', 'Could not retrieve the user privilegies.'), $dom));
        }

        $hasAccess = $dbresult->fields[0];

        $dbresult->close();

        return $hasAccess > 0;
    }

    function getAlbumAccess($albumId)
    {
        // Admin can do everything
        if (SecurityUtil::checkPermission('mediashare::', '::', ACCESS_ADMIN)) {
            return 0xFF;
        }

        $userId = (int)pnUserGetVar('uid');

        // Owner can do everything
        if (!($album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId)))) {
            return false;
        }
        if ($album['ownerId'] == $userId) {
            return 0xFF;
        }

        // Make sure groups database info is available
        pnModDBInfoLoad('Groups');

        list ($dbconn) = pnDBGetConn();
        $pntable = pnDBGetTables();

        $accessTable      = $pntable['mediashare_access'];
        $accessColumn     = $pntable['mediashare_access_column'];
        $membershipTable  = $pntable['group_membership'];
        $membershipColumn = $pntable['group_membership_column'];

        if (!SecurityUtil::checkPermission('mediashare::', '::', ACCESS_READ)) {
            return 0x00;
        }

        $sql = "SELECT $accessColumn[access]
                  FROM $accessTable
             LEFT JOIN $membershipTable
                    ON $membershipColumn[gid] = $accessColumn[groupId]
                   AND $membershipColumn[uid] = $userId
                 WHERE $accessColumn[albumId] = $albumId
                   AND ($membershipColumn[gid] IS NOT NULL OR $accessColumn[groupId] = -1)";

        $dbresult = $dbconn->execute($sql);
        if ($dbconn->errorNo() != 0) {
            return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('accessapi.getAlbumAccess', 'Could not retrieve the access level.'), $dom));
        }

        $access = 0x00;
        for (; !$dbresult->EOF; $dbresult->MoveNext()) {
            $access |= (int)$dbresult->fields[0];
        }

        $dbresult->close();

        $invitedAlbums = pnModAPIFunc('mediashare', 'invitation', 'getInvitedAlbums', array());
        if (is_array($invitedAlbums) && $invitedAlbums[$albumId]) {
            $access |= mediashareAccessRequirementView;
        }

        return $access;
    }

    function getAccessibleAlbumsSql($albumId, $access, $field)
    {
        // Admin can do everything
        if (SecurityUtil::checkPermission('mediashare::', '::', ACCESS_ADMIN)) {
            return '1=1';
        }
        if (!SecurityUtil::checkPermission('mediashare::', '::', ACCESS_READ)) {
            return '1=0';
        }
        $userId = (int)pnUserGetVar('uid');

        // Make sure groups database info is available
        pnModDBInfoLoad('Groups');

        list ($dbconn) = pnDBGetConn();
        $pntable = pnDBGetTables();

        $albumsTable      = $pntable['mediashare_albums'];
        $albumsColumn     = $pntable['mediashare_albums_column'];
        $accessTable      = $pntable['mediashare_access'];
        $accessColumn     = $pntable['mediashare_access_column'];
        $membershipTable  = $pntable['group_membership'];
        $membershipColumn = &$pntable['group_membership_column'];

        $invitedAlbums = pnModAPIFunc('mediashare', 'invitation', 'getInvitedAlbums', array());

        $invitedSql = array();
        if (is_array($invitedAlbums) && ($access & mediashareAccessRequirementView)) {
            foreach ($invitedAlbums as $invAlbumId => $ok) {
                if ($ok) {
                    $invitedSql[] = (int)$invAlbumId;
                }
            }
        }
        $invitedSql = implode(', ', $invitedSql);

        $parentAlbumSql = '';
        if ($albumId != null) {
            $parentAlbumSql = "$albumsColumn[parentAlbumId] = $albumId AND";
        }

        $sql = "SELECT DISTINCT $albumsColumn[id]
                           FROM $albumsTable
                      LEFT JOIN $accessTable
                             ON $accessColumn[albumId] = $albumsColumn[id]
                      LEFT JOIN $membershipTable
                             ON $membershipColumn[gid] = $accessColumn[groupId]
                            AND $membershipColumn[uid] = $userId
                          WHERE $parentAlbumSql
                                (
                                  ($accessColumn[access] & $access) != 0 AND ($membershipColumn[gid] IS NOT NULL OR $accessColumn[groupId] = -1)
                                  OR  $albumsColumn[ownerId] = $userId
                                )";

        $dbresult = $dbconn->execute($sql);
        if ($dbconn->errorNo() != 0) {
            return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('accessapi.getAccessibleAlbumsSql', 'Could not retrieve the accessible albums.'), $dom));
        }

        $albumIds = $invitedSql;
        for (; !$dbresult->EOF; $dbresult->MoveNext()) {
            if ($albumIds != '') {
                $albumIds .= ',';
            }
            $albumIds .= $dbresult->fields[0];
        }
        $dbresult->close();

        //echo "Access bits = $access. Albums = ($albumIds). ";
        return $albumIds == '' ? '1=0' : "$field IN ($albumIds)";
    }

    function hasItemAccess($mediaId, $access, $viewKey)
    {
        if (!($item = pnModAPIFunc('mediashare', 'user', 'getMediaItem', array('mediaId' => $mediaId)))) {
            return false;
        }

        // Owner can do everything
        $userId = (int)pnUserGetVar('uid');
        if ($item['ownerId'] == $userId) {
            return true;
        }

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
