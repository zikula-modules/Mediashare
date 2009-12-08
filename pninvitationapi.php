<?php
// $Id$
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2005.
// =======================================================================

// =======================================================================
// Invitations
// =======================================================================
function mediashare_invitationapi_sendInvitation(&$args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $emails = $args['emails'];

    // Split and trim e-mails
    $emails = str_replace("\n", ' ', $emails);
    $emails = str_replace("\r", ' ', $emails);
    $emails = str_replace(";", ' ', $emails);
    $emails = str_replace(",", ' ', $emails);
    $emailList = explode(' ', $emails);

    foreach ($emailList as $email)
    {
        $email = trim($email);
        if ($email != '') {
            $args['email'] = $email;

            if (!($invitationId = pnModAPIFunc('mediashare', 'invitation', 'createInvitationId', $args))) {
                return false;
            }

            $message = $args['text'];
            $message .= "\n\n";

            $invitationUrl = pnModUrl('mediashare', 'invitation', 'open', array('inv' => $invitationId), false, false, true);

            $message .= str_replace('%url%', $invitationUrl, __('<p>Just follow the link below (click on it or copy it to your webbrowser)</p> <p><a href="%url%">%url%</a></p>', $dom));
            $message = str_replace("\n", '<br/>', $message);

            $params = array('toaddress' => $email,
                            'fromname' => $args['sender'],
                            'fromaddress' => $args['senderemail'],
                            'subject' => $args['subject'],
                            'body' => $message,
                            'html' => true);

            if (!pnModAPIFunc('Mailer', 'user', 'sendmessage', $params)) {
                return LogUtil::registerError(__('Some error occured while trying to send invitation.', $dom));
            }
        }
    }

    return true;
}

function mediashare_invitationapi_createInvitationId(&$args)
{
    list ($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $invitationTable = $pntable['mediashare_invitation'];
    $invitationColumn = $pntable['mediashare_invitation_column'];

    do {
        $key = mediashareCreateInvitationKey();

        $sql = "
INSERT INTO $invitationTable
  ($invitationColumn[albumId],
   $invitationColumn[created],
   $invitationColumn[key],
   $invitationColumn[email],
   $invitationColumn[subject],
   $invitationColumn[text],
   $invitationColumn[sender],
   $invitationColumn[expires])
VALUES
   (" . (int) $args['albumId'] . ",
    NOW(),
    '" . $key . "',
    '" . DataUtil::formatForStore($args['email']) . "',
    '" . DataUtil::formatForStore($args['subject']) . "',
    '" . DataUtil::formatForStore($args['text']) . "',
    '" . DataUtil::formatForStore($args['sender']) . "',
    " . (empty($args['expires']) ? 'NULL' : "'" . DataUtil::formatForStore($args['expires']) . "'") . ")";

        $dbconn->execute($sql);

        if ($dbconn->errorNo() != 0) {
            return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('invitationapi.createInvitationId', 'Could not create the invitation.'), $dom));
        }
    } while (false); // FIXME: add "key exists" check

    return $key;
}

function mediashareCreateInvitationKey()
{
    static $chars = 'abcdefghijklmnopqrstuvwxyz';

    $key = '';
    for ($i = 0; $i < 10; ++$i) {
        $key .= $chars[mt_rand(0, strlen($chars) - 1)];
    }

    return $key;
}

function mediashare_invitationapi_resendInvitation(&$args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $invitationId = $args['invitationId'];

    $invitation = pnModAPIFunc('mediashare', 'invitation', 'getById', array('id' => $invitationId));
    if ($invitation == null) {
        return false;
    }

    $emails = $args['emails'];

    // Split and trim e-mails
    $emails = str_replace("\n", ' ', $emails);
    $emails = str_replace("\r", ' ', $emails);
    $emails = str_replace(";", ' ', $emails);
    $emails = str_replace(",", ' ', $emails);
    $emailList = explode(' ', $emails);

    foreach ($emailList as $email)
    {
        $email = trim($email);
        if ($email != '') {
            $args['email'] = $email;

            $message = $args['text'];
            $message .= "\n\n";

            $invitationUrl = pnModUrl('mediashare', 'invitation', 'open', array('inv' => $invitation['key']), false, false, true);

            $message .= str_replace('%url%', $invitationUrl, __('<p>Just follow the link below (click on it or copy it to your webbrowser)</p> <p><a href="%url%">%url%</a></p>', $dom));
            $message = str_replace("\n", '<br/>', $message);

            $params = array('toaddress' => $email,
                            'fromname' => $args['sender'],
                            'fromaddress' => $args['senderemail'],
                            'subject' => $args['subject'],
                            'body' => $message,
                            'html' => true);

            if (!pnModAPIFunc('Mailer', 'user', 'sendmessage', $params)) {
                return LogUtil::registerError(__('Some error occured sending the invitation.', $dom));
            }
        }
    }

    return true;
}

function mediashare_invitationapi_getByKey($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $key = DataUtil::formatForStore($args['key']);

    list ($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $invitationTable  = $pntable['mediashare_invitation'];
    $invitationColumn = $pntable['mediashare_invitation_column'];

    $sql = "
SELECT
  $invitationColumn[id],
  $invitationColumn[created],
  $invitationColumn[albumId],
  $invitationColumn[key],
  $invitationColumn[viewCount],
  $invitationColumn[email],
  $invitationColumn[subject],
  $invitationColumn[text],
  $invitationColumn[sender],
  $invitationColumn[expires]
FROM $invitationTable
WHERE     $invitationColumn[key] = '$key'
      AND ($invitationColumn[expires] > NOW() OR $invitationColumn[expires] IS NULL)";

    $dbresult = $dbconn->execute($sql);

    if ($dbconn->errorNo() != 0) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('invitationapi.getByKey', 'Could not retrieve the invitation.'), $dom));
    }

    if ($dbresult->EOF) {
        $invitation = null;
    } else {
        $invitation = array(
            'id' => $dbresult->fields[0],
            'created' => $dbresult->fields[1],
            'albumId' => $dbresult->fields[2],
            'key' => $dbresult->fields[3],
            'viewCount' => $dbresult->fields[4],
            'email' => $dbresult->fields[5],
            'subject' => $dbresult->fields[6],
            'text' => $dbresult->fields[7],
            'sender' => $dbresult->fields[8],
            'expires' => $dbresult->fields[9]);
    }

    $dbresult->close();

    return $invitation;
}

function mediashare_invitationapi_getById($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $id = (int) $args['id'];

    list ($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $invitationTable  = $pntable['mediashare_invitation'];
    $invitationColumn = $pntable['mediashare_invitation_column'];

    $sql = "
SELECT
  $invitationColumn[id],
  $invitationColumn[created],
  $invitationColumn[albumId],
  $invitationColumn[key],
  $invitationColumn[viewCount],
  $invitationColumn[email],
  $invitationColumn[subject],
  $invitationColumn[text],
  $invitationColumn[sender],
  $invitationColumn[expires]
FROM $invitationTable
WHERE $invitationColumn[id] = $id";

    $dbresult = $dbconn->execute($sql);

    if ($dbconn->errorNo() != 0) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('invitationapi.getById', 'Could not retrieve the invitation.'), $dom));
    }

    if ($dbresult->EOF) {
        $invitation = null;
    } else {
        $invitation = array(
            'id' => $dbresult->fields[0],
            'created' => $dbresult->fields[1],
            'albumId' => $dbresult->fields[2],
            'key' => $dbresult->fields[3],
            'viewCount' => $dbresult->fields[4],
            'email' => $dbresult->fields[5],
            'subject' => $dbresult->fields[6],
            'text' => $dbresult->fields[7],
            'sender' => $dbresult->fields[8],
            'expires' => $dbresult->fields[9]);
    }

    $dbresult->close();

    return $invitation;
}

function mediashare_invitationapi_updateViewCount($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    if (!(DBUtil::incrementObjectFieldByID('mediashare_invitation', 'viewCount', $args['key'], 'key'))) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('invitationapi.updateViewCount', 'Could not update the counter.'), $dom));
    }

    return true;
}

function mediashare_invitationapi_getInvitations(&$args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $albumId = (int) $args['albumId'];

    list ($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $invitationTable  = $pntable['mediashare_invitation'];
    $invitationColumn = $pntable['mediashare_invitation_column'];

    $sql = "
SELECT
  $invitationColumn[id],
  $invitationColumn[created],
  $invitationColumn[albumId],
  $invitationColumn[key],
  $invitationColumn[viewCount],
  $invitationColumn[email],
  $invitationColumn[subject],
  $invitationColumn[text],
  $invitationColumn[sender],
  $invitationColumn[expires]
FROM $invitationTable
WHERE $invitationColumn[albumId] = $albumId
ORDER BY $invitationColumn[created] DESC";

    $dbresult = $dbconn->execute($sql);

    if ($dbconn->errorNo() != 0) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('invitationapi.getInvitations', 'Could not retrieve the invitation.'), $dom));
    }

    $result = array();
    for (; !$dbresult->EOF; $dbresult->MoveNext()) {
        $invitation = array(
            'id' => $dbresult->fields[0],
            'created' => $dbresult->fields[1],
            'albumId' => $dbresult->fields[2],
            'key' => $dbresult->fields[3],
            'viewCount' => $dbresult->fields[4],
            'email' => $dbresult->fields[5],
            'subject' => $dbresult->fields[6],
            'text' => $dbresult->fields[7],
            'sender' => $dbresult->fields[8],
            'expires' => $dbresult->fields[9]);

        $result[] = $invitation;
    }

    return $result;
}

function mediashare_invitationapi_expireInvitations(&$args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $albumId     = (int) $args['albumId'];
    $expires     = $args['expires'];
    $invitations = $args['invitations'];

    list ($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $invitationTable  = $pntable['mediashare_invitation'];
    $invitationColumn = $pntable['mediashare_invitation_column'];

    // Safeguard againt SQL injections
    $ids = array();
    foreach ($invitations as $id) {
        $ids[] = (int) $id;
    }
    $ids = implode(',', $ids);

    // Access control done on album ID so include that in SQL
    $sql = "UPDATE $invitationTable
               SET $invitationColumn[expires] = " . (empty($args['expires']) ? 'NULL' : "'" . DataUtil::formatForStore($args['expires']) . "'") . "
             WHERE $invitationColumn[albumId] = $albumId
               AND $invitationColumn[id] IN ($ids)";

    $dbresult = $dbconn->execute($sql);

    if ($dbconn->errorNo() != 0) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('invitationapi.expireInvitations', 'Could not set the expiration for the invitations.'), $dom));
    }

    return true;
}

function mediashare_invitationapi_deleteInvitations(&$args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $albumId = (int) $args['albumId'];
    $invitations = $args['invitations'];

    list ($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $invitationTable  = $pntable['mediashare_invitation'];
    $invitationColumn = $pntable['mediashare_invitation_column'];

    // Safeguard againt SQL injections
    $ids = array();
    foreach ($invitations as $id) {
        $ids[] = (int) $id;
    }
    $ids = implode(',', $ids);

    // Access control done on album ID so include that in SQL
    $sql = "DELETE FROM $invitationTable
                  WHERE $invitationColumn[albumId] = $albumId
                    AND $invitationColumn[id] IN ($ids)";

    $dbresult = $dbconn->execute($sql);

    if ($dbconn->errorNo() != 0) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('invitationapi.deleteInvitations', 'Could not delete the invitations.'), $dom));
    }

    return true;
}

function mediashare_invitationapi_register(&$args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $key = $args['key'];

    $invitation = pnModAPIFunc('mediashare', 'invitation', 'getByKey', $args);
    if ($invitation === false) {
        return false;
    } else if ($invitation === null) {
        return array('ok' => false, 'message' => __('Unknown or expired invitation ID.', $dom));
    }

    if (!pnModAPIFunc('mediashare', 'invitation', 'updateViewCount', $args)) {
        return false;
    }

    $albums = SessionUtil::getVar('mediashareInvitedAlbums');
    if ($albums == false || $albums == null) {
        $albums = array('version' => 1, 'keys' => array());
    }

    $albums['keys'][$key] = true;
    SessionUtil::setVar('mediashareInvitedAlbums', $albums);

    return array('ok' => true, 'albumId' => $invitation['albumId']);
}

function mediashare_invitationapi_getInvitedAlbums($args)
{
    $invitedAlbums = SessionUtil::getVar('mediashareInvitedAlbums');
    if ($invitedAlbums == null)
        return $invitedAlbums;

    $keys = array();
    foreach ($invitedAlbums['keys'] as $key => $ok) {
        if ($ok) {
            $keys[] = '\'' . DataUtil::formatForStore($key) . '\'';
        }
    }
    $keys = implode(', ', $keys);

    list ($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $invitationTable  = $pntable['mediashare_invitation'];
    $invitationColumn = $pntable['mediashare_invitation_column'];

    $sql = "SELECT $invitationColumn[albumId]
              FROM $invitationTable
             WHERE $invitationColumn[key] IN ($keys)
               AND ($invitationColumn[expires] > NOW() OR $invitationColumn[expires] IS NULL)";

    $dbresult = $dbconn->execute($sql);

    if ($dbconn->errorNo() != 0) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('invitationapi.getInvitedAlbums', 'Could not retrieve the invited albums.'), $dom));
    }

    $albums = array();
    for (; !$dbresult->EOF; $dbresult->MoveNext()) {
        $albums[(int) $dbresult->fields[0]] = true;
    }

    return $albums;
}
