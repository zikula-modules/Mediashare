<?php

function smarty_function_mediashare_userinfo($params, $smarty)
{
    if (!($userInfo = pnModAPIFunc('mediashare', 'edit', 'getUserInfo'))) {
        return false;
    }

    $dom = ZLanguage::getModuleDomain('mediashare');

    $maxSize = $userInfo['mediaSizeLimitTotal'];
    $size    = $userInfo['totalCapacityUsed'];

    $imageDir = 'modules/mediashare/pnimages';

    $leftSize  = intval($maxSize > $size ? ($size * 100) / $maxSize : 100);
    $rightSize = intval($maxSize > $size ? 100 - $leftSize : 0);

    $scale     = 1000000;
    $unitTitle = 'Mb';
    $str       = sprintf("%.2f %s %.2f %s", $size / $scale, __('of', $dom), $maxSize / $scale, $unitTitle);

    $result = "<div class=\"mediashare-userinfo\"><img src=\"$imageDir/bar_left.gif\" height=\"5\" width=\"$leftSize\" alt=\"\" />" . "<img src=\"$imageDir/bar_right.gif\" height=\"5\" width=\"$rightSize\" alt=\"\" />" . " $leftSize% ($str)</div>";

    return $result;
}
