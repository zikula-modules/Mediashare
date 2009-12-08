<?php

function smarty_function_mediashare_uservar($params, &$smarty)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    if (!isset($params['varName'])) {
        $smarty->trigger_error(__('Missing [%1$s] in \'%2$s\'', array('varName', 'mediashare_uservar'), $dom));
        return false;
    }
    if (!isset($params['userId'])) { 
        $smarty->trigger_error(__('Missing [%1$s] in \'%2$s\'', array('userId', 'mediashare_uservar'), $dom));
        return false;
    }

    $var = pnUserGetVar($params['varName'], $params['userId']);
    $var = DataUtil::formatForDisplay($var);

    if (isset($params['assign'])) {
        $smarty->assign($params['assign'], $var);
    }

    return $var;
}
