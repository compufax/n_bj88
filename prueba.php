<?php
$texto=chr(27)."@";
$texto.=chr(10).chr(13);
$texto.=''.chr(10).chr(13);
$texto.=chr(27).'!'.chr(8)." VENTA DE CERTIFICADO";
$texto.=''.chr(10).chr(13);
$texto.=chr(27).'!'.chr(8)." FECHA: ".date('Y-m-d')."   ".date('H:i:s').''.chr(10).chr(13);
$texto.=chr(27).'!'.chr(8)." FEC.IMP.: ".date('Y-m-d H:i:s').''.chr(10).chr(13);
$texto.=chr(27).'!'.chr(8)." USUARIO: root".chr(10).chr(13);
$texto.=chr(27).'!'.chr(40)."PLACA: ABC123";
$texto.=''.chr(10).chr(13);
$texto.=chr(10).chr(13).chr(29).chr(86).chr(66).chr(0);
echo $texto;
?>
