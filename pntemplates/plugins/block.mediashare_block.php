<?php

function smarty_block_mediashare_block($params, $content, $smarty)
{
  if ($content)
  {
    echo "<div class=\"mediashare-block\">\n";
    echo $content;
    echo "</div>\n";
  }
}


