<?php
$url = 'http://localhost:8020/';
$data = array('printer' => '\\\\ADMIN-PC\\EPSON TM-T81 Receipt', 'data' => 'Prueba\n\n\n');

$options = array(
    'http' => array(
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'GET',
        'content' => http_build_query($data),
    ),
);
$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

var_dump($result);
?>

