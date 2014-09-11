<?php
include('../config.php');
header('Content-Type: application/x-javascript; charset=utf-8');
header('Cache-Control: public, max-age=864000');
header("Expires: " . gmdate("r", $globals['now'] + 864000));
header('Last-Modified: ' .  gmdate('D, d M Y H:i:s', filemtime('main.js')) . ' GMT');


$files = array(
  'transition.js',
  'alert.js',
  'button.js',
  'carousel.js',
  'collapse.js',
  'dropdown.js',
  'modal.js',
  'tooltip.js',
  'popover.js',
  'scrollspy.js',
  'tab.js',
  'affix.js'
);
foreach($files as $file) {  
  Haanga::Load('js/bootstrap/'.$file);
}
