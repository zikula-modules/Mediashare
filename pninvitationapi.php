<?php
// $Id$
//
// Mediashare by Jorn Lind-Nielsen (C)
//

/**
 * Invitations
 */
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
    do {
        $key = mediashareCreateInvitationKey();
        
        $record = array(
            'albumId' => (int)$args['albumId'],
            'created' => DateUtil::getDatetime(),
            'key'     => $key,
            'email'   => $args['email'],
            'subject' => $args['subject'],
            'text'    => $args['text'],
            'sender'  => $args['sender'],
            'expires' => !empty($args['expires']) ? $args['expires'] : null
        );
        $result = DBUtil::insertObject($record, 'mediashare_invitation', 'id');

        if ($result == false) {
            return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('invitationapi.createInvitationId', 'Could not create the invitation.'), $dom));
        }
    } while (false); // FIXME: add "key exists" check

    return $key;
}

function mediashareCreateInvitationKey()
{
    // FIXME Use RandomUtil?
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
            $message  = str_replace("\n", '<br/>', $message);

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

    $pntable          = &pnDBGetTables();
    $invitationColumn = $pntable['mediashare_invitation_column'];

    $where = "     $invitationColumn[key] = '$key'
              AND (   $invitationColumn[expires] > NOW()
                   OR $invitationColumn[expires] IS NULL)";

    $invitation = DBUtil::selectObject('mediashare_invitation', $where);

    if ($invitation === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('invitationapi.getByKey', 'Could not retrieve the invitation.'), $dom));
    }

    return $invitation;
}

function mediashare_invitationapi_getById($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $invitation = DBUtil::selectObjectByID('mediashare_invitation', (int)$args['id'], 'id');

    if ($invitation === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('invitationapi.getById', 'Could not retrieve the invitation.'), $dom));
    }

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

    $pntable          = &pnDBGetTables();
    $invitationColumn = $pntable['mediashare_invitation_column'];

    $where   = "$invitationColumn[albumId] = '".(int)$args['albumId']."'";
    $orderby = "$invitationColumn[created] DESC";

    $invitations = DBUtil::selectObjectArray('mediashare_invitation', $where, $orderby);

    if ($invitations === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('invitationapi.getInvitations', 'Could not retrieve the invitation.'), $dom));
    }

    return $invitations;
}

function mediashare_invitationapi_expireInvitations(&$args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    if (empty($args['invitations']) || !is_array($args['invitations'])) {
        return true;
    }

    $albumId     = (int)$args['albumId'];
    $expires     = $args['expires'];
    $invitations = $args['invitations'];

    $pntable = &pnDBGetTables();

    $invitationTable  = $pntable['mediashare_invitation'];
    $invitationColumn = $pntable['mediashare_invitation_column'];

    // Safeguard againt SQL injections
    $ids = array();
    foreach ($invitations as $id) {
        $ids[] = (int)$id;
    }
    $ids = "'".implode("','", $ids)."'";

    // Access control done on album ID so include that in SQL
    $sql = "UPDATE $invitationTable
               SET $invitationColumn[expires] = " . (!empty($expires) ? "'" . DataUtil::formatForStore($expires) . "'" : 'NULL') . "
             WHERE $invitationColumn[albumId] = '$albumId'
               AND $invitationColumn[id] IN ($ids)";

    $result = DBUtil::executeSQL($sql);

    if ($result === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('invitationapi.expireInvitations', 'Could not set the expiration for the invitations.'), $dom));
    }

    return true;
}

function mediashare_invitationapi_deleteInvitations(&$args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    if (empty($args['invitations']) || !is_array($args['invitations'])) {
        return true;
    }

    $albumId = (int)$args['albumId'];
    $invitations = $args['invitations'];

    $pntable = &pnDBGetTables();

    $invitationTable  = $pntable['mediashare_invitation'];
    $invitationColumn = $pntable['mediashare_invitation_column'];

    // Safeguard againt SQL injections
    $ids = array();
    foreach ($invitations as $id) {
        $ids[] = (int)$id;
    }
    $ids = "'".implode("','", $ids)."'";

    // Access control done on album ID so include that in SQL
    $sql = "DELETE FROM $invitationTable
                  WHERE $invitationColumn[albumId] = '$albumId'
                    AND $invitationColumn[id] IN ($ids)";

    $result = DBUtil::executeSQL($sql);

    if ($result === false) {
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

    return array('ok'      => true,
                 'albumId' => $invitation['albumId']);
}

function mediashare_invitationapi_getInvitedAlbums($args)
{
    $invitedAlbums = SessionUtil::getVar('mediashareInvitedAlbums');
    if ($invitedAlbums == null)
        return $invitedAlbums;

    $keys = array();
    foreach ($invitedAlbums['keys'] as $key => $ok) {
        if ($ok) {
            $keys[] = "'" . DataUtil::formatForStore($key) . "'";
        }
    }
    $keys = "'".implode("','", $keys)."'";

    $pntable = &pnDBGetTables();

    $invitationTable  = $pntable['mediashare_invitation'];
    $invitationColumn = $pntable['mediashare_invitation_column'];

    $sql = "SELECT $invitationColumn[albumId]
              FROM $invitationTable
             WHERE $invitationColumn[key] IN ($keys)
               AND ($invitationColumn[expires] > NOW() OR $invitationColumn[expires] IS NULL)";

    $result = DBUtil::executeSQL($sql);

    if ($result === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('invitationapi.getInvitedAlbums', 'Could not retrieve the invited albums.'), $dom));
    }

    $albums = DBUtil::marshallObjects($result, array('albumId'));
    
    $result = array();
    foreach (array_keys($albums) as $k) {
        $result[(int)$albums[$k]['albumId']] = true;
    }

    return $result;
}
