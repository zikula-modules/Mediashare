<?php
// $Id$
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2005.
// =======================================================================


require_once 'modules/mediashare/common-edit.php';
require_once 'modules/mediashare/elfisk/elfisk_common.php';

function mediashare_invitation_send($args)
{
    $invitationId = mediashareGetIntUrl('iid', $args, -1);
    if ($invitationId > 0) {
        $invitation = pnModAPIFunc('mediashare', 'invitation', 'getById', array('id' => $invitationId));
        if ($invitation == null)
            return mediashareErrorPage(__FILE__, __LINE__, "Unknown invitation");

        $albumId = $invitation['albumId'];
    } else {
        $albumId = mediashareGetIntUrl('aid', $args, -1);
        if ($albumId < 0)
            return mediashareErrorPage(__FILE__, __LINE__, "Missing URL parameter 'aid'");

        $invitation = array('email' => '', 'subject' => __('Hi! See my new pictures', $dom), 'text' => '', 'sender' => pnUserGetVar('uname'), 'expires' => '');
    }

    // Check access
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementEditAccess, ''))
        return mediashareErrorPage(__FILE__, __LINE__, __('You do not have access to this feature', $dom));

    if (isset($_POST['saveButton']) && $invitationId < 0)
        return mediashareUpdateInvitation($args);
    else if (isset($_POST['saveButton']) && $invitationId > 0)
        return mediashareResendInvitation($invitationId, $albumId);

    if (isset($_POST['cancelButton'])) {
        return pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
    }

    $album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId));
    if ($album === false) {
        return mediashareErrorAPIGet();
    }

    $render = & pnRender::getInstance('mediashare');
    $render->caching = false;
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

    $ok = pnModAPIFunc('mediashare', 'invitation', 'sendInvitation', $input);
    if ($ok === false)
        return mediashareErrorAPIGet();

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

    $ok = pnModAPIFunc('mediashare', 'invitation', 'resendInvitation', $args);
    if ($ok === false)
        return mediashareErrorAPIGet();

    return pnRedirect(pnModURL('mediashare', 'invitation', 'list', array('aid' => $albumId)));
}

function mediashare_invitation_link($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');
    $albumId = mediashareGetIntUrl('aid', $args, -1);
    if ($albumId < 0)
        return mediashareErrorPage(__FILE__, __LINE__, "Missing URL parameter 'aid'");

    // Check access
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementEditAccess, ''))
        return mediashareErrorPage(__FILE__, __LINE__, __('You do not have access to this feature', $dom));

    $link = null;
    if (isset($_POST['generateButton'])) {
        $args = array('albumId' => $albumId, 'emails' => '', 'subject' => FormUtil::getPassedValue('subject'), 'text' => '', 'sender' => '', 'expires' => null);

        $invitationId = pnModAPIFunc('mediashare', 'invitation', 'createInvitationId', $args);
        if ($invitationId === false)
            return mediashareErrorAPIGet();

        $link = pnModUrl('mediashare', 'invitation', 'open', array('inv' => $invitationId), false, false, true);
    }

    $album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId));
    if ($album === false)
        return mediashareErrorAPIGet();

    $render = & pnRender::getInstance('mediashare');
    $render->caching = false;
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
    if ($invitationId < 0)
        return mediashareErrorPage(__FILE__, __LINE__, "Missing URL parameter 'iid'");

    $invitation = pnModAPIFunc('mediashare', 'invitation', 'getById', array('id' => $invitationId));
    if ($invitation == null)
        return mediashareErrorPage(__FILE__, __LINE__, "Unknown invitation");

    $albumId = $invitation['albumId'];

    // Check access
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementEditAccess, ''))
        return mediashareErrorPage(__FILE__, __LINE__, __('You do not have access to this feature', $dom));

    $link = pnModUrl('mediashare', 'invitation', 'open', array('inv' => $invitation['key']));

    $album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId));
    if ($album === false)
        return mediashareErrorAPIGet();

    $render = & pnRender::getInstance('mediashare');
    $render->caching = false;
    $render->assign('album', $album);
    $render->assign('link', $link);
    $render->assign('accessSelected', 0);
    $render->assign('sendSelected', 1);
    $render->assign('listSelected', 0);

    return $render->fetch('mediashare_invitation_link.html');
}

function mediashare_invitation_list($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');
    $albumId = mediashareGetIntUrl('aid', $args, 1);

    // Check access
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementEditAccess, ''))
        return mediashareErrorPage(__FILE__, __LINE__, __('You do not have access to this feature', $dom));

    if (isset($_POST['expireButton']))
        return mediashareExpireInvitations($args);
    else if (isset($_POST['deleteButton']))
        return mediashareDeleteInvitations($args);

    $album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId));
    if ($album === false)
        return mediashareErrorAPIGet();

    $invitations = pnModAPIFunc('mediashare', 'invitation', 'getInvitations', array('albumId' => $albumId));
    if ($invitations === false)
        return mediashareErrorAPIGet();

    $render = & pnRender::getInstance('mediashare');
    $render->caching = false;
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
        $ok = pnModAPIFunc('mediashare', 'invitation', 'expireInvitations', array('albumId' => $albumId, 'expires' => $expires, 'invitations' => $invitations));
        if ($ok === false)
            return mediashareErrorAPIGet();
    }

    return pnRedirect(pnModURL('mediashare', 'invitation', 'list', array('aid' => $albumId)));
}

function mediashareDeleteInvitations($args)
{
    $albumId = FormUtil::getPassedValue('albumid');
    $invitations = FormUtil::getPassedValue('invitation');

    if (!empty($invitations)) {
        $ok = pnModAPIFunc('mediashare', 'invitation', 'deleteInvitations', array('albumId' => $albumId, 'invitations' => $invitations));
        if ($ok === false)
            return mediashareErrorAPIGet();
    }

    return pnRedirect(pnModURL('mediashare', 'invitation', 'list', array('aid' => $albumId)));
}

function mediashare_invitation_open($args)
{
    $key = FormUtil::getPassedValue('inv');

    $result = pnModAPIFunc('mediashare', 'invitation', 'register', array('key' => $key));
    if ($result === false)
        return mediashareErrorAPIGet();
    else if (!$result['ok'])
        return $result['message'];

    return pnRedirect(pnModURL('mediashare', 'user', 'view', array('aid' => $result['albumId'])));
}
