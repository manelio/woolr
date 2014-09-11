<?php
include('../config.php');
header('Content-Type: application/x-javascript; charset=utf-8');
header('Cache-Control: public, max-age=864000');
header("Expires: " . gmdate("r", $globals['now'] + 864000));
header('Last-Modified: ' .  gmdate('D, d M Y H:i:s', filemtime('main.js')) . ' GMT');

Haanga::Load('js/main.js');
Haanga::Load('js/jquery.colorbox-min.js');
Haanga::Load('js/jquery.autosize.min.js');

Haanga::Load('js/imagesloaded.pkgd.min.js');

/*
Haanga::Load('js/masonry.pkgd.min.js');
Haanga::Load('js/masonry.main.js');
*/

Haanga::Load('js/isotope.pkgd.min.js');
Haanga::Load('js/modulo-columns.js');
//Haanga::Load('js/jquery.isotope.perfectmasonry.js');
Haanga::Load('js/fit-columns.js');
Haanga::Load('js/masonry-horizontal.js');

Haanga::Load('js/isotope.main.js');