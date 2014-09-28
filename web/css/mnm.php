<?
include('../config.php');
$theme = $globals['theme'];
if ($_GET['theme']) $theme = $_GET['theme'];

header('Content-Type: text/css; charset=utf-8');
header('Cache-Control: public, max-age=864000');
header("Expires: " . gmdate("r", $globals['now'] + 864000));
header('Last-Modified: ' .  gmdate('D, d M Y H:i:s', max(filemtime("{$theme}/styles.css"), filemtime('handheld.css')) ) . ' GMT');

Haanga::Load('/css/colorbox.css');

// bootstrap styles
Haanga::Load("/css/{$theme}/styles.css");

Haanga::Load('css/memocracia.css');
Haanga::Load('css/masonry.css');

/* Include handheld classes for mobile/tablets */

if (! $globals['mobile']) { /* If not mobile, it's a @media rule */
	echo "@media (max-width: 800px) {";
}

// MDOMENECH
//Haanga::Load('css/handheld.css');
Haanga::Load('css/handheld.memocracia.css');

if (! $globals['mobile']) { /* Close @media bracket */
	echo "}";
}


