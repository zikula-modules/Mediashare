<?php
// $Id: pnexternal.php 96 2009-12-08 00:40:46Z mateo $

function smarty_function_ms_greyboxheader($params, &$smarty)
{
    PageUtil::addVar('stylesheet', 'modules/mediashare/pnincludes/greybox/gb_styles.css');
    $script = '<script type="text/javascript">
                   var GB_ROOT_DIR = "'.pnGetBaseURL().'modules/mediashare/greybox/";
               </script>';
    PageUtil::addVar('rawtext', $script);
    PageUtil::addVar('javascript', 'modules/mediashare/pnincludes/greybox/AJS.js');
    PageUtil::addVar('javascript', 'modules/mediashare/pnincludes/greybox/AJS_fx.js');
    PageUtil::addVar('javascript', 'modules/mediashare/pnincludes/greybox/gb_scripts.js');
}
