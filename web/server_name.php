<?php
if (!empty($_SERVER['HTTP_HOST'])) $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'];
$globals['server_name'] = $_SERVER['SERVER_NAME'];

$globals['server_names'] = array(
  'woolr.com.local',
  'lokas.es.local',
  'misterios.info.local',
);

if (!in_array($_SERVER['SERVER_NAME'])) {
  foreach($globals['server_names'] as $serverName) {
    if (($i = strpos($_SERVER['SERVER_NAME'], $serverName)) > 0) {
      $sub = substr($_SERVER['SERVER_NAME'], 0, $i - 1);
      $globals['sub'] = $sub;
      $globals['server_name'] = $serverName;
    }
  }
}

$globals['canonical_server_name'] = $globals['server_name'];

if ($globals['sub']) {
  if (strpos($_SERVER['REQUEST_URI'], '/m/') === 0) {
    $i = strpos($_SERVER['REQUEST_URI'], '/', 3);
    $_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'], $i);
  }
}

$_SERVER['SERVER_NAME'] = $globals['server_name'];