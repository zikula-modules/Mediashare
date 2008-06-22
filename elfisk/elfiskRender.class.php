<?php

class elfiskRender extends pnRender
{
  function elfiskRender($module)
  {
    $this->pnRender($module);
    array_push($this->plugins_dir, "modules/$module/elfisk/plugins");
  }
}

?>