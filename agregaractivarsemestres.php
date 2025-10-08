<?php
include("cnx_db.php");
if($_GET['codigo']=='13579'){
	if($_GET['accion']==1){
		if ($_GET['sem'] == 1){
			$semestre = '1ro';
			$fecha_ini="{$_GET['anio']}-01-01";
			$fecha_fin="{$_GET['anio']}-06-30";
		}
		else{
			$semestre = '2do';
			$fecha_ini="{$_GET['anio']}-07-01";
			$fecha_fin="{$_GET['anio']}-12-31";
		}
		mysql_query("INSERT anios_certificados SET nombre = '{$_GET['anio']} {$semestre} Semestre', estatus=0, fecha_ini='{$fecha_ini}', fecha_fin='{$fecha_fin}', venta=0");
	}
	elseif($_GET['accion']==2){
		if ($_GET['sem'] == 1){
			$fecha_ini="{$_GET['anio']}-01-01";
			$fecha_fin="{$_GET['anio']}-06-30";
		}
		else{
			$fecha_ini="{$_GET['anio']}-07-01";
			$fecha_fin="{$_GET['anio']}-12-31";
		}
		mysql_query("UPDATE anios_certificados SET venta=1 WHERE fecha_ini='{$fecha_ini}' AND fecha_fin='{$fecha_fin}'");
	}
}