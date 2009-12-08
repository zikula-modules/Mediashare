<?php
// $Id$

function smarty_block_mediashare_header($params, $content, $smarty)
{
    if ($content) {
        echo "<div class=\"mediashare-header\">\n";
        echo $content;
        echo "</div>\n";
    }
}
