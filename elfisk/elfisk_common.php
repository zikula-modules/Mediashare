<?php

function elfisk_getStyleHtml(&$params)
{
    $style = '';
    if (array_key_exists('width', $params))
        $style .= "width: $params[width];";

    if (array_key_exists('height', $params))
        $style .= "height: $params[height];";

    if ($style != '')
        $style = " style=\"$style\"";

    return $style;
}

function &elfisk_getCurrentValues()
{
    global $elfisk_currentValues;
    if ($elfisk_currentValues == null)
        $elfisk_currentValues = array();

    return $elfisk_currentValues;
}

function elfisk_loadValues(&$values, $group = 'none')
{
    $currentValues = & elfisk_getCurrentValues();
    $currentValues[$group] = & $values;
}

function elfisk_getLoadedValue($id, $group = 'none')
{
    $values = & elfisk_getCurrentValues();
    return $values[$group][$id];
}

function elfisk_decodeInput($fieldSpecs)
{
    $values = array();

    foreach ($fieldSpecs as $id => $spec) {
        switch ($spec['type']) {
            case 'int':
                $values[$id] = (int) pnVarCleanFromInput($id);
                break;
            default:
                $values[$id] = pnVarCleanFromInput($id);
                break;
        }
    }

    return $values;
}

/*

class elfisk_Control
{
  var $id;
};


function & elfisk_ensureControlsList()
{
  global $elfisk_Controls;
  if ($elfisk_Controls == null)
    $elfisk_Controls = array();

  return $controls;
}


function elfisk_addControl($c)
{
  $controls =& elfisk_ensureControlsList();
  $controls[$c->id] = $c;
}


function elfisk_getControl($id)
{
  $controls =& elfisk_ensureControlsList();
}

*/

?>