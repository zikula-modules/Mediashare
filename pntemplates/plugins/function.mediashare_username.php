<?php
// $Id$

function smarty_function_mediashare_username($params, &$smarty)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    if (!isset($params['userId'])) {
        $smarty->trigger_error(__f('Missing [%1$s] in \'%2$s\'', array('userId', 'mediashare_username'), $dom));
        return false;
    }

    if (mediashareAccessUserRealName()) {
        $name = pnUserGetVar('name', $params['userId']);
    } else {
        $name = null;
    }

    if (!$name) {
        $name = pnUserGetVar('uname', $params['userId']);
    }
    $name = DataUtil::formatForDisplay($name);

    if (isset($params['assign'])) {
        $smarty->assign($params['assign'], $name);
    }

    return $name;
}
