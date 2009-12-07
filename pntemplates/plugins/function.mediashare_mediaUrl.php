<?php

function smarty_function_mediashare_mediaUrl($params, &$smarty)
{
    $result = pnModAPIFunc('mediashare', 'user', 'getMediaUrl', $params);

    if (array_key_exists('assign', $params)) {
        $smarty->assign($params['assign'], $result);
    }

    return DataUtil::formatForDisplay($result);
}
