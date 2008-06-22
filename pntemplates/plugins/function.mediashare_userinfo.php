<?php

function smarty_function_mediashare_userinfo($params, $smarty)
{
  $userInfo = pnModAPIFunc('mediashare', 'edit', 'getUserInfo');
  if ($userInfo === false)
    return mediashareErrorAPIGet();

  $maxSize = $userInfo['mediaSizeLimitTotal'];
  $size    = $userInfo['totalCapacityUsed'];

  $imageDir = 'modules/mediashare/pnimages';

  $leftSize  = intval($maxSize > $size ? ($size*100)/$maxSize : 100);
  $rightSize = intval($maxSize > $size ? 100-$leftSize : 0);

  $scale = 1000000;
  $unitTitle = 'Mb';
  $str = sprintf("%.2f %s %.2f %s", $size/$scale, _MSOF, $maxSize/$scale, $unitTitle);

  $result =   "<div class=\"mediashare-userinfo\"><img src=\"$imageDir/bar_left.gif\" height=\"5\" width=\"$leftSize\" alt=\"\">"
            . "<img src=\"$imageDir/bar_right.gif\" height=\"5\" width=\"$rightSize\" alt=\"\">"
            . " $leftSize% ($str)</div>";

  return $result;
}

?>