<?php
// $Id$
//
// Mediashare by Jorn Lind-Nielsen (C)
//

require_once 'modules/mediashare/common-edit.php';
require_once 'modules/mediashare/pnincludes/elfisk_common.php';

function mediashare_invitation_send($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $invitationId = mediashareGetIntUrl('iid', $args, -1);
    if ($invitationId > 0) {
        $invitation = pnModAPIFunc('mediashare', 'invitation', 'getById', array('id' => $invitationId));
        if ($invitation == null) {
            return LogUtil::registerError(__('Unknown invitation.', $dom));
        }
        $albumId = $invitation['albumId'];
    } else {
        $albumId = mediashareGetIntUrl('aid', $args, -1);
        if ($albumId < 0) {
            return LogUtil::registerError(__f('Missing URL parameter (%s).', 'aid', $dom));
        }
        $invitation = array('email' => '', 'subject' => __('Hi! See my new pictures', $dom), 'text' => '', 'sender' => pnUserGetVar('uname'), 'expires' => '');
    }

    // Check access
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementEditAccess, '')) {
        return LogUtil::registerPermissionError();
    }

    if (isset($_POST['cancelButton'])) {
        return pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
    }
    if (isset($_POST['saveButton']) && $invitationId < 0) {
        return mediashareUpdateInvitation($args);
    } else if (isset($_POST['saveButton']) && $invitationId > 0) {
        return mediashareResendInvitation($invitationId, $albumId);
    }

    if (!($album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId)))) {
        return false;
    }

    $render = & pnRender::getInstance('mediashare', false);

    $render->assign('album', $album);
    $render->assign('invitation', $invitation);
    $render->assign('accessSelected', 0);
    $render->assign('sendSelected', 1);
    $render->assign('listSelected', 0);

    return $render->fetch('mediashare_invitation_send.html');
}

function mediashareUpdateInvitation($args)
{
    $input = array(
        'albumId' => FormUtil::getPassedValue('albumid'),
        'emails' => FormUtil::getPassedValue('emails'),
        'subject' => FormUtil::getPassedValue('subject'),
        'text' => FormUtil::getPassedValue('text'),
        'sender' => FormUtil::getPassedValue('sender'),
        'senderemail' => pnUserGetVar('email'),
        'expires' => FormUtil::getPassedValue('expires'));

    if (!pnModAPIFunc('mediashare', 'invitation', 'sendInvitation', $input)) {
        return false;
    }

    return pnRedirect(pnModURL('mediashare', 'invitation', 'list', array('aid' => $input['albumId'])));
}

function mediashareResendInvitation($invitationId, $albumId)
{
    $args = array(
        'invitationId' => $invitationId,
        'emails' => FormUtil::getPassedValue('emails'),
        'subject' => FormUtil::getPassedValue('subject'),
        'text' => FormUtil::getPassedValue('text'),
        'sender' => FormUtil::getPassedValue('sender'),
        'senderemail' => pnUserGetVar('email'),
        'expires' => FormUtil::getPassedValue('expires'));

    if (!pnModAPIFunc('mediashare', 'invitation', 'resendInvitation', $args)) {
        return false;
    }

    return pnRedirect(pnModURL('mediashare', 'invitation', 'list', array('aid' => $albumId)));
}

function mediashare_invitation_link($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $albumId = mediashareGetIntUrl('aid', $args, -1);
    if ($albumId < 0) {
        return LogUtil::registerError(__f('Missing URL parameter (%s).', 'aid', $dom));
    }

    // Check access
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementEditAccess, '')) {
        return LogUtil::registerPermissionError();
    }

    $link = null;
    if (isset($_POST['generateButton'])) {
        $args = array('albumId' => $albumId, 'emails' => '', 'subject' => FormUtil::getPassedValue('subject'), 'text' => '', 'sender' => '', 'expires' => null);

        if (!($invitationId = pnModAPIFunc('mediashare', 'invitation', 'createInvitationId', $args))) {
            return false;
        }

        $link = pnModUrl('mediashare', 'invitation', 'open', array('inv' => $invitationId), false, false, true);
    }

    if (!($album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId)))) {
        return false;
    }

    $render = & pnRender::getInstance('mediashare', false);

    $render->assign('album', $album);
    $render->assign('link', $link);
    $render->assign('accessSelected', 0);
    $render->assign('sendSelected', 1);
    $render->assign('listSelected', 0);

    return $render->fetch('mediashare_invitation_link.html');
}

function mediashare_invitation_viewlink($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $invitationId = mediashareGetIntUrl('iid', $args, -1);
    if ($invitationId < 0) {
        return LogUtil::registerError(__f('Missing URL parameter (%s).', 'iid', $dom));
    }

    $invitation = pnModAPIFunc('mediashare', 'invitation', 'getById', array('id' => $invitationId));
    if ($invitation == null) {
        return LogUtil::registerError(__('Unknown invitation.', $dom));
    }

    $albumId = $invitation['albumId'];

    // Check access
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementEditAccess, '')) {
        return LogUtil::registerPermissionError();
    }

    $link = pnModUrl('mediashare', 'invitation', 'open', array('inv' => $invitation['key']));

    if (!($album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId)))) {
        return false;
    }

    $render = & pnRender::getInstance('mediashare', false);

    $render->assign('album', $album);
    $render->assign('link', $link);
    $render->assign('accessSelected', 0);
    $render->assign('sendSelected', 1);
    $render->assign('listSelected', 0);

    return $render->fetch('mediashare_invitation_link.html');
}

function mediashare_invitation_list($args)
{
    $albumId = mediashareGetIntUrl('aid', $args, 1);

    // Check access
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementEditAccess, '')) {
        return LogUtil::registerPermissionError();
    }

    if (isset($_POST['expireButton'])) {
        return mediashareExpireInvitations($args);
    } else if (isset($_POST['deleteButton'])) {
        return mediashareDeleteInvitations($args);
    }

    if (!($album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId)))) {
        return false;
    }

    if (!($invitations = pnModAPIFunc('mediashare', 'invitation', 'getInvitations', array('albumId' => $albumId)))) {
        return false;
    }

    $render = & pnRender::getInstance('mediashare', false);

    $render->assign('album', $album);
    $render->assign('invitations', $invitations);
    $render->assign('accessSelected', 0);
    $render->assign('sendSelected', 0);
    $render->assign('listSelected', 1);

    return $render->fetch('mediashare_invitation_list.html');
}

function mediashareExpireInvitations($args)
{
    $albumId = FormUtil::getPassedValue('albumid');
    $expires = FormUtil::getPassedValue('expires');
    $invitations = FormUtil::getPassedValue('invitation');

    if (!empty($invitations)) {
        if (!pnModAPIFunc('mediashare', 'invitation', 'expireInvitations',
                          array('albumId' => $albumId,
                                'expires' => $expires,
                                'invitations' => $invitations))) {
            return false;
        }
    }

    return pnRedirect(pnModURL('mediashare', 'invitation', 'list', array('aid' => $albumId)));
}

function mediashareDeleteInvitations($args)
{
    $albumId     = FormUtil::getPassedValue('albumid');
    $invitations = FormUtil::getPassedValue('invitation');

    if (!empty($invitations)) {
        if (!pnModAPIFunc('mediashare', 'invitation', 'deleteInvitations',
                           array('albumId' => $albumId,
                           'invitations' => $invitations))) {
            return false;
        }
    }

    return pnRedirect(pnModURL('mediashare', 'invitation', 'list', array('aid' => $albumId)));
}

function mediashare_invitation_open($args)
{
    $key = FormUtil::getPassedValue('inv');

    $result = pnModAPIFunc('mediashare', 'invitation', 'register', array('key' => $key));
    if ($result === false) {
        return false;
    } else if (!$result['ok']) {
        return $result['message'];
    }

    return pnRedirect(pnModURL('mediashare', 'user', 'view', array('aid' => $result['albumId'])));
}
