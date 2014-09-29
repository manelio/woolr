<?php
if (!empty($_SERVER['HTTP_HOST'])) $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'];
$globals['server_name'] = $_SERVER['SERVER_NAME'];

$serverName = $_SERVER['SERVER_NAME'];

while($serverName && !file_exists(dirname(__FILE__).'/local/'.$serverName.'-local.php')) {  
  if (($i = strpos($serverName, ".")) !== false) {    
    $serverName = substr($serverName, $i + 1);
  } else $serverName = null;
}

if ($serverName) {
  $globals['server_name'] = $serverName;
}
$globals['canonical_server_name'] = $globals['server_name'];

if ($_SERVER['SERVER_NAME'] != $serverName) {
  if (($i = strpos($_SERVER['SERVER_NAME'], $serverName)) > 0) {
    $sub = substr($_SERVER['SERVER_NAME'], 0, $i - 1);
    $globals['sub'] = $sub;    
  }
}

if ($globals['sub']) {
  if (strpos($_SERVER['REQUEST_URI'], '/m/') === 0) {
    $i = strpos($_SERVER['REQUEST_URI'], '/', 3);
    $_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'], $i);
  }
}

$_SERVER['SERVER_NAME'] = $globals['server_name'];

$globals['is_secure'] = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
$globals['http_protocol'] = $globals['is_secure']?'https':'http';
