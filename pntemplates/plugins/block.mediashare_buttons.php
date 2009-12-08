<?php
// $Id$

function smarty_block_mediashare_buttons($params, $content, $smarty)
{
    if ($content) {
        echo "<div class=\"mediashare-buttons\">\n";
        echo $content;
        echo "</div>\n";
    }
}
