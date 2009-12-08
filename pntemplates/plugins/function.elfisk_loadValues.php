<?php
// $Id: pnexternal.php 96 2009-12-08 00:40:46Z mateo $

function smarty_function_elfisk_loadValues($params, &$smarty)
{
    elfisk_loadValues($params['source']);
}
