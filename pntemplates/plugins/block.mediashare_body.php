<?php

function smarty_block_mediashare_body($params, $content, $smarty)
{
    if ($content) {
        echo "<div class=\"mediashare-body\">\n";
        echo $content;
        echo "</div>\n";
    }
}
