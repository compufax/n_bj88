<?php
include("cnx_db.php");


function paga_timbres($datos){
	global $base,$array_tipo_pago, $array_tipo_pagosat;
	mysql_query("UPDATE compra_timbres SET estatus='P',usucan='',fechacan=NOW(),fecha_pago='".$datos['fecha_pago']."' WHERE factura='".$datos['cvefact']."' AND estatus='A' AND factura!=''");
	return true;
}



$function = $_POST['function'];

echo $function($_POST['datos']);

?>