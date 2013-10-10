<?php
    /**
    * Another var_dump() alternative, for debugging use.
    *
    * @param mixed $data Data to be dumped on screen.
    * @param boolean $exit Whether to terminate script after dump.
    */
    function printr($data, $exit = FALSE) {
      if ($data) {
        print '<pre>';
        print_r($data);
        print '</pre>';
      }
      if ($exit) {
        exit;
      }
    }

/*---------------------------------------------------------------
    TOOLS
----------------------------------------------------------------*/

    
?>
