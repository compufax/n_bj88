<?php
$texto=chr(27)."@";
$texto.=chr(10).chr(13);
$fields = array('impresora' => '\\\\ADMIN-PC\\EPSON TM-T81 Receipt', 'data' => $texto);
$texto.=''.chr(10).chr(13);
$texto.=chr(27).'!'.chr(8)." VENTA DE CERTIFICADO";
$texto.=''.chr(10).chr(13);
$texto.=chr(27).'!'.chr(8)." FECHA: ".date('Y-m-d')."   ".date('H:i:s').''.chr(10).chr(13);
$texto.=chr(27).'!'.chr(8)." FEC.IMP.: ".date('Y-m-d H:i:s').''.chr(10).chr(13);
$texto.=chr(27).'!'.chr(8)." USUARIO: root".chr(10).chr(13);
$texto.=chr(27).'!'.chr(40)."PLACA: ABC123";
$texto.=''.chr(10).chr(13);
$texto.=chr(10).chr(13).chr(29).chr(86).chr(66).chr(0);s
$fields_string = http_build_query($fields);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost:8020");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string );
$datos = curl_exec($ch);
curl_close($ch);

?>