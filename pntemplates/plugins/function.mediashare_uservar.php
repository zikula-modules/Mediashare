<?php

function smarty_function_mediashare_uservar($params, &$smarty)
{
    if (!isset($params['varName'])) {
        return $smarty->trigger_error('mediashare_uservar: varName parameter required');
    }
    if (!isset($params['userId'])) { 
        return $smarty->trigger_error('mediashare_uservar: userId parameter required');
    }

    $var = pnUserGetVar($params['varName'], $params['userId']);
    $var = DataUtil::formatForDisplay($var);

    if (isset($params['assign'])) {
        $smarty->assign($params['assign'], $var);
    }

    return $var;
}
