<?php
// $Id$

function smarty_function_mediashare_mediaUrl($params, &$smarty)
{
    $result = pnModAPIFunc('mediashare', 'user', 'getMediaUrl', $params);

    if (isset($params['assign'])) {
        $smarty->assign($params['assign'], $result);
    }

    return DataUtil::formatForDisplay($result);
}
