<?php
error_reporting(E_ERROR | E_PARSE);
require_once('cnx_db.php');
require_once('globales.php');

if($_GET['cmd']==102){
	$_POST['fecha_ini'] = $_GET['fecha_ini'];
	$_POST['fecha_fin'] = $_GET['fecha_fin'];
	$_POST['usuario'] = $_GET['usuario'];
	$_POST['cveplaza'] = $_GET['cveplaza'];
	$array_forma_pago = array(1=>"Efectivo",2=>"Deposito Bancario",3=>"Cheque",4=>"Transferencia",5=>'Tarjeta Bancaria');
	$texto=chr(27)."@";
	$texto.=chr(10).chr(13);
	$resPlaza = mysql_query("SELECT numero, nombre FROM plazas WHERE cve='{$_POST['cveplaza']}'");
	$rowPlaza = mysql_fetch_array($resPlaza);
	$Usuario = mysql_fetch_assoc(mysql_query("SELECT usuario FROM usuarios WHERE cve='{$_POST['usuario']}'"));
	$texto.=chr(27).'!'.chr(8)." {$rowPlaza['numero']}".chr(10).chr(13)."{$rowPlaza['nombre']}".chr(10).chr(13).chr(10).chr(13)." CORTE VENTA CERTIFICADO";
	$texto.=chr(10).chr(13).date('Y-m-d H:i:s').chr(10).chr(13);
	if($_POST['fecha_ini']==$_POST['fecha_fin']) $texto.=" FECHA: ".chr(10).chr(13).$_POST['fecha_ini'];
	else $texto.=" FECHA INI: ".$_POST['fecha_ini'].chr(10).chr(13)."FECHA FIN: ".chr(10).chr(13).$_POST['fecha_fin'];
	$filtro="";
	if ($_POST['usuario']!=""){ 
		$filtro.=" AND a.usuario='{$_POST['usuario']}' "; 
		$texto.=chr(10).chr(13).'USUARIO: '.$Usuario['usuario'];
	}
	$texto.=chr(10).chr(13).chr(10).chr(13).' INGRESOS'.chr(10).chr(13).chr(10).chr(13);
	$t1=$t2=$t3=$t4=$t5=$t6=$t7=$t8=$t9=0;
	$res = mysql_query("SELECT COUNT(cve), SUM(IF(estatus='C',1,0)) FROM cobro_engomado a WHERE plaza='{$_POST['cveplaza']}' AND fecha BETWEEN '{$_POST['fecha_ini']}' AND '{$_POST['fecha_fin']}' {$filtro}");
	$row = mysql_fetch_array($res);
	$texto.=' NUMERO DE REGISTROS: '.$row[0].chr(10).chr(13).' CANCELADOS: '.$row[1].chr(10).chr(13).chr(10).chr(13).'';

	$efectivo = 0;
	$total=0;
	$res=mysql_query("SELECT a.tipo_pago,COUNT(a.cve),sum(a.monto), b.nombre FROM cobro_engomado a INNER JOIN tipos_pago b ON b.cve = a.tipo_pago WHERE a.plaza='{$_POST['cveplaza']}' AND a.fecha BETWEEN '{$_POST['fecha_ini']}' AND '{$_POST['fecha_fin']}' AND a.estatus!='C' {$filtro} GROUP BY a.tipo_pago ORDER BY a.tipo_pago");
	while($row=mysql_fetch_array($res)){
		if($row[0] == 1){
			$texto.=" EFECTIVO CANT: ".$row[1]." IMP: ".number_format($row[2],2).chr(10).chr(13).'';
			$efectivo += $row[2];
		}
		else{
			$texto.=" {$row['nombre']} CANT: ".$row[1]." IMP: ".number_format($row[2],2).chr(10).chr(13).'';	
		}
		if($row['tipo_pago'] != 12 && $row['tipo_pago'] != 6 && $row['tipo_pago'] != 2)
		$total+=$row[2];		
		
	}

	$Copias = mysql_fetch_array(mysql_query("SELECT SUM(copias),SUM(copias*costo_copias), SUM(IF(tipo_pago NOT IN (5,7), copas*costo_copias, 0)) FROM cobro_engomado a WHERE plaza='{$_POST['cveplaza']}' AND fecha BETWEEN '{$_POST['fecha_ini']}' AND '{$_POST['fecha_fin']}' AND estatus!='C' {$filtro}"));
	$texto.=" COPIAS CANT: ".$Copias[0]." IMP: ".number_format($Copias[1],2).chr(10).chr(13);
	
	$texto.=' PAGOS EN CAJA ';
	$t31=$t32=$t33=0;
	$res3=mysql_query("SELECT forma_pago,SUM(monto),COUNT(cve)  FROM pagos_caja a WHERE plaza='{$_POST['cveplaza']}' AND fecha BETWEEN '{$_POST['fecha_ini']}' AND '{$_POST['fecha_fin']}' AND estatus!='C' {$filtro} GROUP BY forma_pago");
	while($row3=mysql_fetch_array($res3)){
		$texto.=" ".$array_forma_pago[$row3['forma_pago']].' CANT: '.$row3[2].', IMP: '.number_format($row3[1],2).chr(10).chr(13).'';
		$t31+=$row3[1];
		$t32+=$row3[2];
		if($row3['forma_pago'] == 1) $efectivo+=$row3[1];
		$total+=$row3[1];
	}


	$texto.=chr(10).chr(13).chr(10).chr(13).' EGRESOS'.chr(10).chr(13).chr(10).chr(13);

	$res1=mysql_query("SELECT SUM(a.devolucion),COUNT(a.cve)  FROM devolucion_certificado a LEFT JOIN cobro_engomado b ON b.plaza = a.plaza AND b.cve = a.ticket WHERE a.plaza='{$_POST['cveplaza']}' AND a.fecha BETWEEN '{$_POST['fecha_ini']}' AND '{$_POST['fecha_fin']}' AND IFNULL(b.tipo_pago,0) NOT IN (2,6) AND a.estatus!='C' {$filtro}");
	$row1=mysql_fetch_array($res1);
	$texto.= ' DEVOLUCIONES CANT: '.$row1[1].', IMP: '.number_format($row1[0],2).chr(10).chr(13).'';

	$res2=mysql_query("SELECT SUM(a.monto),COUNT(a.cve)  FROM recibos_salidav a WHERE a.plaza='{$_POST['cveplaza']}' AND a.fecha BETWEEN '{$_POST['fecha_ini']}' AND '{$_POST['fecha_fin']}' AND a.estatus!='C' {$filtro}");
	$row2=mysql_fetch_array($res2);
	$texto.= ' R.SALIDA CANT: '.$row2[1].', IMP: '.number_format($row2[0],2).chr(10).chr(13).'';
	
	$texto.=' TOTAL EN EFECTIVO: '.number_format($efectivo-$row1[0]-$row2[0]+$Copias[2],2).chr(10).chr(13).'';
	$texto.=' TOTAL DE LA VENTA: '.number_format($total-$row1[0]-$row2[0]+$Copias[1],2).chr(10).chr(13).chr(10).chr(13).'';
	$texto.=chr(10).chr(13).chr(29).chr(86).chr(66).chr(0);

	echo $texto;
	
	/*$impresion='<iframe src="http://localhost/impresiongenerallogo.php?textoimp='.$texto.'&logo='.str_replace(' ','',$rowPlaza['numero']).'" width=200 height=200></iframe>';
	echo '<html><body>'.$impresion.'</body></html>';

	echo '<script>setTimeout("window.close()",2000);</script>';*/
	exit();
}

if($_GET['cmd']==101){
	function separar_letras($cadena){
		$cadena2 = '';
		for($i=0;$i<strlen($cadena);$i++){
			$cadena2.=' '.$cadena[$i];
		}
		$cadena2 = substr($cadena2, 1);
		return $cadena2;

	}
	require_once("numlet.php");
	$res=mysql_query("SELECT a.* FROM cobro_engomado a WHERE a.plaza='{$_GET['cveplaza']}' AND a.cve='{$_GET['cveticket']}'");
	$row=mysql_fetch_array($res);
	if($row['tipo_pago']==6) $row['monto']=0;
	$barcode = '1'.sprintf("%011s",(intval($row['cve'])));
	$Usuario = mysql_fetch_assoc(mysql_query("SELECT usuario FROM usuarios WHERE cve='{$_GET['cveusuario']}'"));
	$Anio = mysql_fetch_assoc(mysql_query("SELECT nombre FROM anios_certificados WHERE cve='{$row['anio']}'"));
	$Engomado = mysql_fetch_assoc(mysql_query("SELECT nombre FROM engomados WHERE cve='{$row['engomado']}'"));
	$TipoVenta = mysql_fetch_assoc(mysql_query("SELECT nombre FROM tipo_venta WHERE cve='{$row['tipo_venta']}'"));
	$TipoPago = mysql_fetch_assoc(mysql_query("SELECT nombre FROM tipos_pago WHERE cve='{$row['tipo_pago']}'"));
	$Depositante = mysql_fetch_assoc(mysql_query("SELECT nombre FROM depositantes WHERE cve='{$row['depositante']}'"));
	$TipoCombustible = mysql_fetch_assoc(mysql_query("SELECT nombre FROM tipo_combustible WHERE cve='{$row['tipo_combustible']}'"));

	$texto=chr(27)."@";
	$texto.=chr(10).chr(13);
	/*if(file_exists('img/logo.TMB')){
		$texto.=chr(27).'a'.chr(1);
		$texto.=file_get_contents('img/logo.TMB');
		$texto.=chr(10).chr(13);
		$texto.=chr(27).'a0';
	}*/
	if($row['tipo_venta']==1){
		$texto.='USTED POR ESTE TICKS NO PAGO'.chr(10).chr(13).'Y  NO SE PODRA FACTURAR'.chr(10).chr(13).chr(10).chr(13).'SI LE COBRARON FAVOR DE REPORTAR'.chr(10).chr(13).'AL GERENTE DEL CENTRO'.chr(10).chr(13);
	}
	$resPlaza = mysql_query("SELECT numero,nombre,bloqueada_sat FROM plazas WHERE cve='{$row['plaza']}'");
	$rowPlaza = mysql_fetch_array($resPlaza);
	$resPlaza2 = mysql_query("SELECT rfc FROM datosempresas WHERE plaza='{$row['plaza']}'");
	$rowPlaza2 = mysql_fetch_array($resPlaza2);
	$texto.=chr(27).'!'.chr(30)." {$rowPlaza['numero']}".chr(10).chr(13)."{$rowPlaza['nombre']}";
	$texto.=chr(10).chr(13).' RFC: '.$rowPlaza2['rfc'];
	//$texto.='|AV. CONGRESO DE LA UNION 6607,|COL. GRANJAS MODERNAS|CP 07460 DELG. GUSTAVO A MADERO';
	$texto.=''.chr(10).chr(13).chr(10).chr(13);
	if($_GET['reimpresion'] == 1){
		$texto.="     REIMPRESION ".chr(10).chr(13).chr(10).chr(13);
		$row['monto'] = 0;
	}

	$texto.=chr(27).'!'.chr(8)." ORIGINAL CLIENTE";
	$texto.=''.chr(10).chr(13);
	$texto.=''.chr(10).chr(13);
	$texto.=chr(27).'!'.chr(8)." TICKET: ".sprintf("%05s", $row['cve']);
	$texto.=''.chr(10).chr(13);
	if($row['tipo_pago'] != 2 && $row['tipo_pago'] != 6 && $row['tipo_pago'] != 12 && $rowPlaza['bloqueada_sat'] != 1 && $_GET['reimpresion'] != 1){
		$res1=mysql_query("SELECT * FROM claves_facturacion WHERE plaza='".$row['plaza']."' AND ticket='".$row['cve']."'");
		if($row1=mysql_fetch_array($res1)){
			$texto.=chr(27).'!'.chr(8)."CLAVE FACTURACION:".chr(10).chr(13).$row1['cve'];
			$texto.=''.chr(10).chr(13).chr(10).chr(13).chr(10).chr(13);
			$fecha_limite = date( "Y-m-t" , strtotime ( "+1 day" , strtotime(substr($row['fecha'],0, 8).'05') ) );
			$texto.=chr(27).'!'.chr(8)."FECHA LIMITE FACTURACION:".chr(10).chr(13)."    ".$fecha_limite." 21:00";
			$texto.=''.chr(10).chr(13).chr(10).chr(13);
			//if($row['plaza']!=59 && $row['plaza']!=1 && $row['plaza']!=15) 
				$texto.=chr(27).'!'.chr(8)."PAGINA PARA FACTURAR:".chr(10).chr(13)."{$url_impresion}/facturacion".chr(10).chr(13);
		}
	}
	$texto.=''.chr(10).chr(13);
	$texto.=chr(27).'!'.chr(8)." VENTA DE CERTIFICADO";
	$texto.=''.chr(10).chr(13);
	$texto.=chr(27).'!'.chr(8)." FECHA: ".$row['fecha']."   ".$row['hora'].''.chr(10).chr(13);
	$texto.=chr(27).'!'.chr(8)." FEC.IMP.: ".date('Y-m-d H:i:s').''.chr(10).chr(13);
	$texto.=chr(27).'!'.chr(8)." USUARIO: ".$Usuario['usuario'].''.chr(10).chr(13);
	$texto.=chr(27).'!'.chr(40)."PLACA: ".separar_letras($row['placa']);
	$texto.=''.chr(10).chr(13);
	$texto.=chr(27).'!'.chr(8)." A. CERTIFICADO: ".$Anio['nombre'];
	$texto.=''.chr(10).chr(13);
	$texto.=chr(27).'!'.chr(8)." T. CERTIFICADO: ".$Engomado['nombre'];
	$texto.=''.chr(10).chr(13);
	//$texto.=chr(27).'!'.chr(8)." MODELO: ".$row['modelo'];
	//$texto.='|';
	$texto.=chr(27).'!'.chr(8)." TIPO VENTA: ".$TipoVenta['nombre'].''.chr(10).chr(13);
	//if($row['tipo_venta']==1) $texto.=chr(27).'!'.chr(8)." NUM INTENTO: ".$row['num_intento'].'|';
	if($row['tipo_venta']==1) $texto.=chr(27).'!'.chr(8)."ESTA PLACA CUENTA CON ".$row['num_intento']." CANTIDAD DE INTENTOS SIN COBRO, SOLO IMPORTE DE COPIAS, FAVOR DE REPORTAR AL PERSONAL QUE SOLICITE COBRO ALGUNO".chr(10).chr(13);
	$texto.=chr(27).'!'.chr(8)." TIPO PAGO: ".$TipoPago['nombre'];
	$texto.=''.chr(10).chr(13);
	if($row['tipo_pago'] == 2 || $row['tipo_pago'] == 6 || $row['depositante']>0){
		$texto.=chr(27).'!'.chr(8)." DEPOSITANTE: ".$Depositante['nombre'];
		$texto.=''.chr(10).chr(13);
		if($row['tipo_pago']==6 && $row['vale_pago_anticipado']>0){
			$texto.=chr(27).'!'.chr(8)." VALE: ".$row['vale_pago_anticipado'];
			$texto.=''.chr(10).chr(13);
		}
		elseif($row['tipo_pago']==6 && $row['codigo_cortesia']!=''){
			$texto.=chr(27).'!'.chr(8)." VALE: ".$row['codigo_cortesia'];
			$texto.=''.chr(10).chr(13);
		}
	}
	$texto.=chr(27).'!'.chr(8)." TIPO COMBUSTIBLE ".$TipoCombustible['nombre'];
	if($row['descuento'] > 0){
		$texto.=''.chr(10).chr(13);
		$texto.=chr(27).'!'.chr(8)." DESCUENTO PROMOCION ";
	}
	$texto.=''.chr(10).chr(13);
	$texto.=chr(27).'!'.chr(8)." MONTO: ".$row['monto'];
	$texto.=''.chr(10).chr(13);
	$texto.=chr(27).'!'.chr(8)." COPIAS: ".$row['copias'];
	$texto.=''.chr(10).chr(13);
	$texto.=chr(27).'!'.chr(8)." TOTAL: ".($row['copias']*$row['costo_copias']+$row['monto']);
	$texto.=''.chr(10).chr(13);
	$texto.=chr(27).'!'.chr(8)." ".numlet(($row['copias']*$row['costo_copias']+$row['monto']));
	$texto.=''.chr(10).chr(13);

	$texto.=chr(10).chr(13).'SI EL IMPORTE COBRADO ES DIFERENTE AL DEL TICKET FAVOR DE REPORTARLO'.chr(10).chr(13);
	
	if($row['tipo_venta'] == 2){
		$texto.=''.chr(10).chr(13).'___________________'.chr(10).chr(13).$row['autoriza'].''.chr(10).chr(13).'Autoriza'.chr(10).chr(13);
	}
	if($row['tipo_venta'] == 0){
		$texto.=''.chr(10).chr(13).'AL PAGAR EL SERVICIO SE INFORMA QUE NO EXISTE DEVOLUCION POR CAUSAS NO IMPUTABLES AL CENTRO'.chr(10).chr(13).chr(10).chr(13).chr(10).chr(13).'______________________'.chr(10).chr(13).'FIRMA DEL PROPIETARIO'.chr(10).chr(13).'PLACA '.$row['placa'].chr(10).chr(13);
	}

	if($row['tipo_venta'] == 1){
		$MotivoIntento = mysql_fetch_assoc(mysql_query("SELECT nombre FROM motivos_intento WHERE cve='{$row['motivo_intento']}'"));
		$texto.=''.chr(10).chr(13).chr(27).'!'.chr(8)." MOTIVO INTENTO:".chr(10).chr(13).$MotivoIntento['nombre'];
		$texto.=''.chr(10).chr(13);
		$texto.=chr(27).'!'.chr(8)." OBSERVACIONES:".chr(10).chr(13).$row['obs'];
		$texto.=''.chr(10).chr(13);
		$res2=mysql_query("SELECT * FROM cobro_engomado WHERE plaza='{$_POST['plazausuario']}' AND placa='{$row['placa']}' AND monto>0 ORDER BY cve DESC LIMIT 1");
		$row2 = mysql_fetch_array($res2);
		$Engomado2 = mysql_fetch_assoc(mysql_query("SELECT nombre FROM engomados WHERE cve='{$row2['engomado']}'"));
		$TipoPago2 = mysql_fetch_assoc(mysql_query("SELECT nombre FROM tipos_pago WHERE cve='{$row2['tipo_pago']}'"));
		$TipoCombustible2 = mysql_fetch_assoc(mysql_query("SELECT nombre FROM tipo_combustible WHERE cve='{$row2['tipo_combustible']}'"));
		$texto.=chr(27).'!'.chr(8)." TICKET PAGADO: ".$row2['cve'];
		$texto.=''.chr(10).chr(13);
		$texto.=chr(27).'!'.chr(8)." FECHA: ".$row2['fecha']."   ".$row2['hora'].''.chr(10).chr(13);
		$texto.=''.chr(10).chr(13);
		$texto.=chr(27).'!'.chr(8)." T. CERTIFICADO: ".$Engomado2['nombre'];
		$texto.=''.chr(10).chr(13);
		$texto.=chr(27).'!'.chr(8)." TIPO PAGO ".$TipoPago2['nombre'];
		$texto.=''.chr(10).chr(13);
		$texto.=chr(27).'!'.chr(8)." TIPO COMBUSTIBLE ".$TipoCombustible2['nombre'];
		$texto.=''.chr(10).chr(13);
		$texto.=chr(27).'!'.chr(8)." MONTO: ".$row2['monto'];
		$texto.=''.chr(10).chr(13);
		$texto.=chr(27).'!'.chr(8)." ".numlet($row2['monto']);
		$texto.=''.chr(10).chr(13);
	}
	if($row['tipo_venta']==1){
		$texto.='USTED POR ESTE TICKS NO PAGO'.chr(10).chr(13).'Y  NO SE PODRA FACTURAR'.chr(10).chr(13).chr(10).chr(13).'SI LE COBRARON FAVOR DE REPORTAR'.chr(10).chr(13).'AL GERENTE DEL CENTRO'.chr(10).chr(13);
	}


	if($barcode!="")$texto.=chr(29)."h".chr(80).chr(29)."H".chr(2).chr(29)."k".chr(2).$barcode.chr(0);
	$texto.=chr(10).chr(13).chr(29).chr(86).chr(66).chr(0);


	$texto.=chr(10).chr(13);
	/*if(file_exists('img/logo.TMB')){
		$texto.=chr(27).'a'.chr(1);
		$texto.=file_get_contents('img/logo.TMB');
		$texto.=chr(10).chr(13);
		$texto.=chr(27).'a0';
	}*/
	if($row['tipo_venta']==1){
		$texto.='USTED POR ESTE TICKS NO PAGO'.chr(10).chr(13).'Y  NO SE PODRA FACTURAR'.chr(10).chr(13).chr(10).chr(13).'SI LE COBRARON FAVOR DE REPORTAR'.chr(10).chr(13).'AL GERENTE DEL CENTRO'.chr(10).chr(13);
	}
	$resPlaza = mysql_query("SELECT numero,nombre,bloqueada_sat FROM plazas WHERE cve='{$row['plaza']}'");
	$rowPlaza = mysql_fetch_array($resPlaza);
	$resPlaza2 = mysql_query("SELECT rfc FROM datosempresas WHERE plaza='{$row['plaza']}'");
	$rowPlaza2 = mysql_fetch_array($resPlaza2);
	$texto.=chr(27).'!'.chr(30)." {$rowPlaza['numero']}".chr(10).chr(13)."{$rowPlaza['nombre']}";
	$texto.=chr(10).chr(13).' RFC: '.$rowPlaza2['rfc'];
	//$texto.='|AV. CONGRESO DE LA UNION 6607,|COL. GRANJAS MODERNAS|CP 07460 DELG. GUSTAVO A MADERO';
	$texto.=''.chr(10).chr(13).chr(10).chr(13);
	if($_GET['reimpresion'] == 1){
		$texto.="     REIMPRESION ".chr(10).chr(13).chr(10).chr(13);
		$row['monto'] = 0;
	}
	$texto.=chr(27).'!'.chr(8)." COPIA ARCHIVO";
	$texto.=''.chr(10).chr(13);
	$texto.=''.chr(10).chr(13);
	$texto.=chr(27).'!'.chr(8)." TICKET: ".sprintf("%05s", $row['cve']);
	$texto.=''.chr(10).chr(13);
	$texto.=''.chr(10).chr(13);
	$texto.=chr(27).'!'.chr(8)." VENTA DE CERTIFICADO";
	$texto.=''.chr(10).chr(13);
	$texto.=chr(27).'!'.chr(8)." FECHA: ".$row['fecha']."   ".$row['hora'].''.chr(10).chr(13);
	$texto.=chr(27).'!'.chr(8)." FEC.IMP.: ".date('Y-m-d H:i:s').''.chr(10).chr(13);
	$texto.=chr(27).'!'.chr(8)." USUARIO: ".$Usuario['usuario'].''.chr(10).chr(13);
	$texto.=chr(27).'!'.chr(40)."PLACA: ".separar_letras($row['placa']);
	$texto.=''.chr(10).chr(13);
	$texto.=chr(27).'!'.chr(8)." A. CERTIFICADO: ".$Anio['nombre'];
	$texto.=''.chr(10).chr(13);
	$texto.=chr(27).'!'.chr(8)." T. CERTIFICADO: ".$Engomado['nombre'];
	$texto.=''.chr(10).chr(13);
	//$texto.=chr(27).'!'.chr(8)." MODELO: ".$row['modelo'];
	//$texto.='|';
	$texto.=chr(27).'!'.chr(8)." TIPO VENTA: ".$TipoVenta['nombre'].''.chr(10).chr(13);
	//if($row['tipo_venta']==1) $texto.=chr(27).'!'.chr(8)." NUM INTENTO: ".$row['num_intento'].'|';
	if($row['tipo_venta']==1) $texto.=chr(27).'!'.chr(8)."ESTA PLACA CUENTA CON ".$row['num_intento']." CANTIDAD DE INTENTOS SIN COBRO, SOLO IMPORTE DE COPIAS, FAVOR DE REPORTAR AL PERSONAL QUE SOLICITE COBRO ALGUNO".chr(10).chr(13);
	$texto.=chr(27).'!'.chr(8)." TIPO PAGO: ".$TipoPago['nombre'];
	$texto.=''.chr(10).chr(13);
	if($row['tipo_pago'] == 2 || $row['tipo_pago'] == 6 || $row['depositante']>0){
		$texto.=chr(27).'!'.chr(8)." DEPOSITANTE: ".$Depositante['nombre'];
		$texto.=''.chr(10).chr(13);
		if($row['tipo_pago']==6 && $row['vale_pago_anticipado']>0){
			$texto.=chr(27).'!'.chr(8)." VALE: ".$row['vale_pago_anticipado'];
			$texto.=''.chr(10).chr(13);
		}
		elseif($row['tipo_pago']==6 && $row['codigo_cortesia']!=''){
			$texto.=chr(27).'!'.chr(8)." VALE: ".$row['codigo_cortesia'];
			$texto.=''.chr(10).chr(13);
		}
	}
	$texto.=chr(27).'!'.chr(8)." TIPO COMBUSTIBLE ".$TipoCombustible['nombre'];
	if($row['descuento'] > 0){
		$texto.=''.chr(10).chr(13);
		$texto.=chr(27).'!'.chr(8)." DESCUENTO PROMOCION ";
	}
	$texto.=''.chr(10).chr(13);
	$texto.=chr(27).'!'.chr(8)." MONTO: ".$row['monto'];
	$texto.=''.chr(10).chr(13);
	$texto.=chr(27).'!'.chr(8)." COPIAS: ".$row['copias'];
	$texto.=''.chr(10).chr(13);
	$texto.=chr(27).'!'.chr(8)." TOTAL: ".($row['copias']*$row['costo_copias']+$row['monto']);
	$texto.=''.chr(10).chr(13);
	$texto.=chr(27).'!'.chr(8)." ".numlet(($row['copias']*$row['costo_copias']+$row['monto']));
	$texto.=''.chr(10).chr(13);

	$texto.=chr(10).chr(13).'SI EL IMPORTE COBRADO ES DIFERENTE AL DEL TICKET FAVOR DE REPORTARLO'.chr(10).chr(13);
	
	if($row['tipo_venta'] == 2){
		$texto.=''.chr(10).chr(13).'___________________'.chr(10).chr(13).$row['autoriza'].''.chr(10).chr(13).'Autoriza'.chr(10).chr(13);
	}
	if($row['tipo_venta'] == 0){
		$texto.=''.chr(10).chr(13).'AL PAGAR EL SERVICIO SE INFORMA QUE NO EXISTE DEVOLUCION POR CAUSAS NO IMPUTABLES AL CENTRO'.chr(10).chr(13).chr(10).chr(13).chr(10).chr(13).'______________________'.chr(10).chr(13).'FIRMA DEL PROPIETARIO'.chr(10).chr(13).'PLACA '.$row['placa'].chr(10).chr(13);
	}

	if($row['tipo_venta'] == 1){
		$MotivoIntento = mysql_fetch_assoc(mysql_query("SELECT nombre FROM motivos_intento WHERE cve='{$row['motivo_intento']}'"));
		$texto.=''.chr(10).chr(13).chr(27).'!'.chr(8)." MOTIVO INTENTO:".chr(10).chr(13).$MotivoIntento['nombre'];
		$texto.=''.chr(10).chr(13);
		$texto.=chr(27).'!'.chr(8)." OBSERVACIONES:".chr(10).chr(13).$row['obs'];
		$texto.=''.chr(10).chr(13);
		$res2=mysql_query("SELECT * FROM cobro_engomado WHERE plaza='{$_POST['plazausuario']}' AND placa='{$row['placa']}' AND monto>0 ORDER BY cve DESC LIMIT 1");
		$row2 = mysql_fetch_array($res2);
		$Engomado2 = mysql_fetch_assoc(mysql_query("SELECT nombre FROM engomados WHERE cve='{$row2['engomado']}'"));
		$TipoPago2 = mysql_fetch_assoc(mysql_query("SELECT nombre FROM tipos_pago WHERE cve='{$row2['tipo_pago']}'"));
		$TipoCombustible2 = mysql_fetch_assoc(mysql_query("SELECT nombre FROM tipo_combustible WHERE cve='{$row2['tipo_combustible']}'"));
		$texto.=chr(27).'!'.chr(8)." TICKET PAGADO: ".$row2['cve'];
		$texto.=''.chr(10).chr(13);
		$texto.=chr(27).'!'.chr(8)." FECHA: ".$row2['fecha']."   ".$row2['hora'].''.chr(10).chr(13);
		$texto.=''.chr(10).chr(13);
		$texto.=chr(27).'!'.chr(8)." T. CERTIFICADO: ".$Engomado2['nombre'];
		$texto.=''.chr(10).chr(13);
		$texto.=chr(27).'!'.chr(8)." TIPO PAGO ".$TipoPago2['nombre'];
		$texto.=''.chr(10).chr(13);
		$texto.=chr(27).'!'.chr(8)." TIPO COMBUSTIBLE ".$TipoCombustible2['nombre'];
		$texto.=''.chr(10).chr(13);
		$texto.=chr(27).'!'.chr(8)." MONTO: ".$row2['monto'];
		$texto.=''.chr(10).chr(13);
		$texto.=chr(27).'!'.chr(8)." ".numlet($row2['monto']);
		$texto.=''.chr(10).chr(13);
	}
	if($row['tipo_venta']==1){
		$texto.='USTED POR ESTE TICKS NO PAGO'.chr(10).chr(13).'Y  NO SE PODRA FACTURAR'.chr(10).chr(13).chr(10).chr(13).'SI LE COBRARON FAVOR DE REPORTAR'.chr(10).chr(13).'AL GERENTE DEL CENTRO'.chr(10).chr(13);
	}


	if($barcode!="")$texto.=chr(29)."h".chr(80).chr(29)."H".chr(2).chr(29)."k".chr(2).$barcode.chr(0);
	$texto.=chr(10).chr(13).chr(29).chr(86).chr(66).chr(0);

	if($row['tipo_venta']==0 && $row['depositante'] > 0 && ($row['tipo_pago']==1 || $row['tipo_pago']==5 || $row['tipo_pago']==7 || $row['tipo_pago']==4)){
		$res1 = mysql_query("SELECT folio FROM vale_cortesia_acumulado WHERE plaza='{$_GET['cveplaza']}' AND ticket='{$_GET['cveticket']}' AND estatus!='C'");
		while($row1 = mysql_fetch_array($res1)){
			$textosimp=chr(27).'!'.chr(30)." ".$rowPlaza['numero']."|".$rowPlaza['nombre'];
			$textosimp.='| RFC: '.$rowPlaza2['rfc'];
			$textosimp.='||';
			$textosimp.=chr(27).'!'.chr(8)." FOLIO: ".$row1['folio'];
			$textosimp.='|';
			$textosimp.=chr(27).'!'.chr(8)." VALE CORTESIA POR ACUMULADO";
			$textosimp.='|';
			$textosimp.=chr(27).'!'.chr(8)." FECHA: ".$row['fecha'].'|';
			$textosimp.='|';
			$textosimp.=chr(27).'!'.chr(8)." DEPOSITANTE: ".$rowDepositante['nombre'];
			$textosimp.='|';
			$texto.=chr(27)."@";
			$textoimp=explode("|",$textosimp);
			for($i=0;$i<count($textoimp);$i++){
				$texto.=$textoimp[$i].chr(10).chr(13);
			}
			$barcode = '6'.sprintf("%011s",(intval($row1['cve'])));
			if($barcode!="")$texto.=chr(29)."h".chr(80).chr(29)."H".chr(2).chr(29)."k".chr(2).$barcode.chr(0);
			$texto.=chr(10).chr(13).chr(29).chr(86).chr(66).chr(0);
		}
	}

	echo $texto;
	exit();
}

if($_POST['cmd']==37){
	$res = mysql_query("SELECT placa, estatus, fecha, tipo_pago, depositante FROM cobro_engomado WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['ticket']}'");
	$row = mysql_fetch_array($res);
	if($row['placa']!=$_POST['placa']){
		mysql_query("INSERT historial SET menu='{$_POST['cvemenu']}', cveaux='{$_POST['ticket']}', fecha=NOW(), dato='Placa', nuevo='{$_POST['placa']}', anterior='{$row['placa']}', obs='{$_POST['cveplaza']}', usuario='{$_POST['cveusuario']}'");
	}
	if($row['fecha']!=$_POST['fecha']){
		mysql_query("INSERT historial SET menu='{$_POST['cvemenu']}', cveaux='{$_POST['ticket']}', fecha=NOW(), dato='Fecha', nuevo='{$_POST['fecha']}', anterior='{$row['fecha']}', obs='{$_POST['cveplaza']}', usuario='{$_POST['cveusuario']}'");
	}
	if($row['tipo_pago']!=$_POST['tipo_pago']){
		mysql_query("INSERT historial SET menu='{$_POST['cvemenu']}', cveaux='{$_POST['ticket']}', fecha=NOW(), dato='Tipo pago', nuevo='{$_POST['tipo_pago']}', anterior='{$row['tipo_pago']}', obs='{$_POST['cveplaza']}', usuario='{$_POST['cveusuario']}'");
	}
	if($row['depositante']!=$_POST['depositante']){
		mysql_query("INSERT historial SET menu='{$_POST['cvemenu']}', cveaux='{$_POST['ticket']}', fecha=NOW(), dato='Depositante', nuevo='{$_POST['depositante']}', anterior='{$row['depositante']}', obs='{$_POST['cveplaza']}', usuario='{$_POST['cveusuario']}'");
	}
	mysql_query("UPDATE cobro_engomado SET placa='{$_POST['placa']}', fecha='{$_POST['fecha']}', tipo_pago='{$_POST['tipo_pago']}', depositante='{$_POST['depositante']}' WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['ticket']}'");
	exit();
}

if($_POST['cmd']==36){
	$resultado = array('mensaje' => '', 'error'=>0, 'placa' => '', 'fecha' => '', 'tipos_pago' => '', 'depositantes' => '');
	$res = mysql_query("SELECT placa, estatus, fecha, tipo_pago, depositante FROM cobro_engomado WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['ticket']}'");
	$row = mysql_fetch_array($res);
	if ($row['estatus'] != 'A') {
		$resultado['mensaje'] = 'La venta esta cancelada';
		$resultado['error'] = 1;
	}
	else {
		$resultado['placa'] = $row['placa'];
		$resultado['fecha'] = $row['fecha'];
		$resultado['tipos_pago'] = '';
		if ($row['tipo_pago'] != 1 && $row['tipo_pago'] != 5 && $row['tipo_pago'] != 7){
			$res1 = mysql_query("SELECT cve, nombre FROM tipos_pago WHERE cve={$row['tipo_pago']} ORDER BY nombre");
		}
		else{
			$res1 = mysql_query("SELECT cve, nombre FROM tipos_pago WHERE cve IN (1,5,7) ORDER BY nombre");
		}
		while($row1=mysql_fetch_array($res1)){
			$resultado['tipos_pago'] .= '<option value="'.$row1['cve'].'"';
			if ($row1['cve'] == $row['tipo_pago']){
				$resultado['tipos_pago'] .= ' selected';
			}
			$resultado['tipos_pago'] .= '>'.$row1['nombre'].'</option>';
		}

		if ($row['tipo_pago'] != 1 && $row['tipo_pago'] != 5 && $row['tipo_pago'] != 7){
			$res1 = mysql_query("SELECT cve, nombre FROM depositantes WHERE cve={$row['depositante']} ORDER BY nombre");
		}
		else{
			$res1 = mysql_query("SELECT cve, nombre FROM depositantes WHERE plaza='{$_POST['cveplaza']}' AND tipo_depositante=2 ORDER BY nombre");
		}
		while($row1=mysql_fetch_array($res1)){
			$resultado['depositantes'] .= '<option value="'.$row1['cve'].'"';
			if ($row1['cve'] == $row['depositante']){
				$resultado['depositantes'] .= ' selected';
			}
			$resultado['depositantes'] .= '>'.utf8_encode($row1['nombre']).'</option>';
		}
	}
	echo json_encode($resultado);
	exit();
}

if($_POST['cmd']==35){
	$resultado = array('mensaje' => 'Se gener&oacute; exitosamente', 'tipo'=>'success');

	$row = mysql_fetch_assoc(mysql_query("SELECT * FROM cobro_engomado WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['ticket']}' AND factura>0 AND notacredito=0"));
	if($row['cve']==0){
		$resultado = array('mensaje' => 'Error en el ticket', 'tipo'=>'warning');
		echo json_encode($resultado);
		exit();
	}

	$resF = mysql_query("SELECT * FROM facturas WHERE plaza='{$_POST['cveplaza']}' AND cve='{$row['factura']}'");
	$rowF = mysql_fetch_array($resF);
	if($rowF['rfc_cli'] != 'XAXX010101000'){
		$resultado = array('mensaje' => 'La factura no es publico general', 'tipo'=>'warning');
		echo json_encode($resultado);
		exit();
	}

	if ($rowF['factura']=='C' && $rowF['factura']=='D'){
		$resultado = array('mensaje' => 'La factura no esta activa', 'tipo'=>'warning');
		echo json_encode($resultado);
		exit();	
	}

	$resplaza = mysql_query("SELECT * FROM plazas WHERE cve='{$_POST['cveplaza']}'");
	$rowplaza = mysql_fetch_array($resplaza);
	$res = mysql_query("SELECT serie,folio_inicial FROM foliosiniciales WHERE plaza='{$_POST['cveplaza']}' AND tipo=0 AND tipodocumento=2");
	$row = mysql_fetch_array($res);
	
	$res1 = mysql_query("SELECT IFNULL(MAX(folio+1),1) FROM notascredito WHERE plaza='{$_POST['cveplaza']}' AND serie='{$row['serie']}'");
	$row1 = mysql_fetch_array($res1);
	if($row['folio_inicial']<$row1[0]){
		$row['folio_inicial'] = $row1[0];
	}
	$insert = "INSERT notascredito SET plaza='{$_POST['cveplaza']}', serie='{$row['serie']}', folio='{$row['folio_inicial']}', fecha=CURDATE(), fecha_creacion=CURDATE(), hora=CURTIME(), obs='".addslashes($_POST['obs'])."', cliente='{$rowF['cliente']}', tipo_pago='{$rowF['tipo_pago']}', forma_pago='{$rowF['forma_pago']}', usuario='{$_POST['cveusuario']}', tipo_relacion='01', uuidsrelacionados='{$rowF['uuid']}'";
	while(!$resinsert=mysql_query($insert)){
		$row['folio_inicial']++;
		$insert = "INSERT notascredito SET plaza='{$_POST['cveplaza']}', serie='{$row['serie']}', folio='{$row['folio_inicial']}', fecha=CURDATE(), fecha_creacion=CURDATE(), hora=CURTIME(), obs='".addslashes($_POST['obs'])."', cliente='{$rowF['cliente']}', tipo_pago='{$rowF['tipo_pago']}', forma_pago='{$rowF['forma_pago']}', usuario='{$_POST['cveusuario']}', tipo_relacion='01', uuidsrelacionados='{$rowF['uuid']}'";
	}
	
	$cvefact=mysql_insert_id();
	$documento=array();
	require_once("nusoap/nusoap.php");
	$fserie=$row['serie'];
	$ffolio=$row['folio_inicial'];
	$resultado['mensaje'] = ('Se genero la nota de cr&eacute;dito '.$fserie.' '.$ffolio);

	$resD = mysql_query("SELECT * FROM facturasmov WHERE plaza='{$_POST['cveplaza']}' AND cvefact='{$rowF['cve']}' AND ticket='{$_POST['ticket']}'");
	$rowD = mysql_fetch_array($resD);

	if(trim($rowD['unidad'])=="") $rowD['unidad'] = "Unidad de servicio";
	mysql_query("INSERT notascreditomov SET plaza='{$rowF['plaza']}', cvefact='{$cvefact}', cantidad='{$rowD['cantidad']}', concepto='{$rowD['concepto']}',	precio='{$rowD['precio']}', descuento='{$rowD['descuento']}', importe='{$rowD['importe']}', iva='{$rowD['iva']}', ticket='{$rowD['ticket']}', importe_iva='{$rowD['importe_iva']}',unidad='{$rowD['unidad']}', engomado='{$rowD['engomado']}',claveprodsat='77121503',claveunidadsat='E48'");


	mysql_query("UPDATE notascredito SET subtotal='{$rowD['precio']}', iva='{$rowD['importe_iva']}', total='".round($rowD['precio']+$rowD['importe_iva'],2)."' WHERE plaza='{$rowF['plaza']}' AND cve={$cvefact}");
	mysql_query("UPDATE cobro_engomado SET notacredito = '{$cvefact}' WHERE plaza='{$rowF['plaza']}' AND cve='{$_POST['ticket']}' AND factura>0");
	mysql_query("UPDATE venta_engomado_factura SET notacredito = '{$cvefact}' WHERE plaza='{$rowF['plaza']}' AND venta='{$_POST['ticket']}'");

	$documento = genera_arreglo_facturacion($_POST['cveplaza'], $cvefact, 'E');
	$resultadotimbres = validar_timbres($_POST['cveplaza']);
	if($resultadotimbres['seguir']){
		$rsSucursal = mysql_query("SELECT * FROM datosempresas WHERE plaza = '{$_POST['cveplaza']}'");
		$rowempresa = mysql_fetch_assoc($rsSucursal);
		$oSoapClient = new nusoap_client("https://servicios.integratucfdi.net/wscfdi.php?wsdl", true);	
		$err = $oSoapClient->getError();
		if($err!=""){
			echo "error1:".$err;
		}
		else{
			$oSoapClient->timeout = 300;
			$oSoapClient->response_timeout = 300;
			$respuesta = $oSoapClient->call("generarComprobante", array ('id' => $rowempresa['idplaza'],'rfcemisor' => $rowempresa['rfc'],'idcertificado' => $rowempresa['idcertificado'],'documento' => $documento, 'usuario' => $rowempresa['usuario'],'password' => $rowempresa['pass']));
			if ($oSoapClient->fault) {
				/*echo '<p><b>Fault: ';
				echo '</b></p>';
				echo '<p><b>Request: <br>';
				echo htmlspecialchars($oSoapClient->request, ENT_QUOTES) . '</b></p>';
				echo '<p><b>Response: <br>';
				echo htmlspecialchars($oSoapClient->response, ENT_QUOTES) . '</b></p>';
				echo '<p><b>Debug: <br>';
				echo htmlspecialchars($oSoapClient->debug_str, ENT_QUOTES) . '</b></p>';*/
			}
			else{
				$err = $oSoapClient->getError();
				if ($err){
					/*echo '<p><b>Error: ' . $err . '</b></p>';
					echo '<p><b>Request: <br>';
					echo htmlspecialchars($oSoapClient->request, ENT_QUOTES) . '</b></p>';
					echo '<p><b>Response: <br>';
					echo htmlspecialchars($oSoapClient->response, ENT_QUOTES) . '</b></p>';
					echo '<p><b>Debug: <br>';
					echo htmlspecialchars($oSoapClient->debug_str, ENT_QUOTES) . '</b></p>';*/
				}
				else{
					if($respuesta['resultado']){
						mysql_query("UPDATE notascredito SET respuesta1='{$respuesta['uuid']}', seriecertificado='{$respuesta['seriecertificado']}', sellodocumento='{$respuesta['sellodocumento']}', uuid='{$respuesta['uuid']}', seriecertificadosat='{$respuesta['seriecertificadosat']}', sellotimbre='{$respuesta['sellotimbre']}', cadenaoriginal='{$respuesta['cadenaoriginal']}', fechatimbre='".substr($respuesta['fechatimbre'],0,10)." ".substr($respuesta['fechatimbre'],-8)."'
						WHERE plaza='{$_POST['cveplaza']}' AND cve={$cvefact}");
						//Tomar la informacion de Retorno
						$dir="cfdi/comprobantes/";
						$dir2="cfdi/";
						//Leer el Archivo Zip
						$fileresult=$respuesta['archivos'];
						$strzipresponse=base64_decode($fileresult);
						$filename='cfdinc_'.$_POST['cveplaza'].'_'.$cvefact;
						file_put_contents($dir2.$filename.'.zip', $strzipresponse);
						$zip = new ZipArchive;
						if ($zip->open($dir2.$filename.'.zip') === TRUE){
							$strxml=$zip->getFromName('xml.xml');
							file_put_contents($dir.$filename.'.xml', $strxml);
							$zip->close();		
							generaFacturaPdf($_POST['cveplaza'],$cvefact, 0, 2);
							if($emailenvio!=""){
								$mail = obtener_mail();		
								$mail->FromName = "Verificentros Plaza ".$rowempresa['nombre'];
								$mail->Subject = "Nota Credito ".$fserie." ".$ffolio;
								$mail->Body = "Nota Credito ".$fserie." ".$ffolio;
								$correos = explode(",",trim($emailenvio));
								foreach($correos as $correo){
									$mail->AddAddress(trim($correo));
								}
								$mail->AddAttachment("cfdi/comprobantes/nc_{$_POST['cveplaza']}_{$cvefact}.pdf", "Nota Credito {$fserie} {$ffolio}.pdf");
								$mail->AddAttachment("cfdi/comprobantes/cfdinc_{$_POST['cveplaza']}_{$cvefact}.xml", "Nota Credito {$fserie} {$ffolio}.xml");
								$mail->Send();
							}	
							
							@unlink("cfdi/comprobantes/nc_{$_POST['cveplaza']}_{$cvefact}.pdf");
						}
						else 
							$strmsg='Error al descomprimir el archivo';
						if(file_exists($dir2.$filename.'.zip')){
							unlink($dir2.$filename.'.zip');
						}
					}
					else{
						$strmsg=$respuesta['mensaje'];
					}
					$resultado['mensaje'].='<br>'.utf8_encode($strmsg);
				}
			}
		}
	}

	echo json_encode($resultado);
	exit();
}

if($_POST['cmd']==34){
	$resultado = array('mensaje' => '', 'error'=>0);
	$res = mysql_query("SELECT placa, fecha, factura, tipo_venta FROM cobro_engomado WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['ticket']}'");
	$row = mysql_fetch_array($res);
	if ($row['factura'] > 0) {
		$resultado['mensaje'] = 'La venta ya esta facturada';
		$resultado['error'] = 1;
	}
	/*elseif($_POST['cveusuario'] != 1 && $_POST['cveusuario'] != 4 && $row['fecha']<date('Y-m-d')){
		$resultado['mensaje'] = 'No puede cancelar ventas de dias anteriores';
		$resultado['error'] = 1;
	}*/
	else {
		$res1=mysql_query("SELECT fecha FROM cobro_engomado WHERE placa='{$row['placa']}' AND estatus!='C' AND estatus!='D' AND fecha>'{$row['fecha']}' ORDER BY fecha LIMIT 1");
		if($row1=mysql_fetch_array($res1)){
			$resultado['mensaje'] = 'La placa tiene movimientos posteriores';
			$resultado['error'] = 1;
		}
		else{
			$res1=mysql_query("SELECT cve FROM certificados WHERE plaza='{$_POST['cveplaza']}' AND ticket='{$_POST['ticket']}' AND estatus!='C'");
			if($row1=mysql_fetch_array($res1)){
				$resultado['mensaje'] = 'La venta ya tiene certificado entregado';
				$resultado['error'] = 1;
			}
		}
	}
	echo json_encode($resultado);
	exit();
}

if($_POST['cmd']==33){
	$resultado = array('mensaje' => 'Se cancelo exitosamente', 'tipo'=>'success');

	mysql_query("UPDATE cobro_engomado SET estatus='C', usucan='{$_POST['cveusuario']}', fechacan=NOW(), obscan='{$_POST['motivocancelacion']}' WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['ticket']}'");
	$row = mysql_fetch_assoc(mysql_query("SELECT vales_pago_anticipado, codigo_cortesia FROM cobro_engomado WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['ticket']}'"));
	if($row['vale_pago_anticipado']!= '' || $row['codigo_cortesia'] != '') {
		if($row['tipo_pago']==6){
			if($row['vale_pago_anticipado'] != ''){
				$vale = $row['vale_pago_anticipado'];
				$tipo=0;
			}
			else{
				$vale = $row['codigo_cortesia'];
				$tipo=1;
			}
			mysql_query("UPDATE vales_pago_anticipado SET usado=0 WHERE plaza='{$_POST['cveplaza']}' AND cve='{$vale}' AND tipo='{$tipo}'");
		}
		elseif($row['tipo_pago']==1 || $row['tipo_pago']==5 || $row['tipo_pago']==7 || $row['tipo_pago']==4){
			mysql_query("UPDATE vale_cortesia_acumulado SET usado=0 WHERE plaza={$row['plaza']} AND folio={$row['codigo_cortesia']}");
		}
	}
	echo json_encode($resultado);
	exit();
}
require_once('validarloging.php');

if($_POST['cmd']==0){
	$nivelUsuario = nivelUsuario();
?>
<div id="modalEditar" class="modal fade" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="staticBackdropLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="staticBackdropLabel">Editar</h5>
		        <!--<button type="button" class="close" data-dismiss="modal" aria-label="Close">
		          <span aria-hidden="true">&times;</span>
		        </button>-->
			</div>
			<div class="modal-body" id="bodypago">
				<div class="row">
					<div class="col-xl-12 col-lg-12 col-md-12">
						<div class="form-row">
					        <div class="form-group col-sm-6">
								<label for="ticketeditar">Ticket</label>
					            <input type="text" class="form-control" id="ticketeditar" readonly>
					        </div>
					    </div>
						<div class="form-row">
					        <div class="form-group col-sm-6">
								<label for="placaeditar">Placa</label>
					            <input type="text" class="form-control" id="placaeditar">
					        </div>
					    </div>
					    <div class="form-row">
					        <div class="form-group col-sm-6">
								<label for="placaeditar">Fecha</label>
					            <input type="date" class="form-control" id="fechaeditar">
					        </div>
					    </div>
					    <div class="form-row">
					        <div class="form-group col-sm-6">
								<label for="tipopagoeditar">Tipo Pago</label>
					            <select id="tipopagoeditar" class="form-control"></select>
					        </div>
					    </div>
					    <div class="form-row">
					        <div class="form-group col-sm-6">
								<label for="depositanteeditar">Depositante</label>
					            <select id="depositanteeditar" class="form-control"></select>
					        </div>
					    </div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-danger" onClick="guardareditar();">Guardar</button>
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
		     </div>
		</div>
	</div>
</div>
<input type="hidden" id="ticketcancelar" value="">
<div id="modalCancelacion" class="modal fade" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="staticBackdropLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="staticBackdropLabel">Cancelación</h5>
		        <!--<button type="button" class="close" data-dismiss="modal" aria-label="Close">
		          <span aria-hidden="true">&times;</span>
		        </button>-->
			</div>
			<div class="modal-body" id="bodypago">
				<div class="row">
					<div class="col-xl-12 col-lg-12 col-md-12">
						<div class="form-row">
					        <div class="form-group col-sm-12">
								<label for="total">Motivo</label>
					            <textarea type="text" class="form-control" rows="3" id="motivocancelacion"></textarea>
					        </div>
					    </div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-danger" onClick="cancelarventa();">Cancelar</button>
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
		     </div>
		</div>
	</div>
</div>
<div class="row justify-content-center">
	<div class="col-xl-9 col-lg-9 col-md-9">
		<div class="form-group row">
			<label class="col-sm-2 col-form-label">Fecha Inicio</label>
			<div class="col-sm-4">
            	<input type="date" class="form-control" id="busquedafechaini" name="busquedafechaini" placeholder="Fecha Inicio" value="<?php echo date('Y-m-d');?>">
        	</div>
			<label class="col-sm-2 col-form-label">Fecha Fin</label>
			<div class="col-sm-4">
            	<input type="date" class="form-control" id="busquedafechafin" name="busquedafechafin" placeholder="Fecha Fin" value="<?php echo date('Y-m-d');?>">
        	</div>
        </div>
		<div class="form-group row">
			<label class="col-sm-2 col-form-label">Ticket</label>
			<div class="col-sm-4">
            	<input type="text" class="form-control" id="busquedaticket" name="busquedaticket" placeholder="Busqueda Ticket">
        	</div>
        	<label class="col-sm-2 col-form-label">Placa</label>
			<div class="col-sm-4">
            	<input type="text" class="form-control" id="busquedaplaca" name="busquedaplaca" placeholder="Placa">
        	</div>
        </div>
        <div class="form-group row">
			<label class="col-sm-2 col-form-label">Tipo de Certificado</label>
			<div class="col-sm-4">
            	<select name="busquedatipocertificado" id="busquedatipocertificado" class="form-control"><option value="">Todos</option>
            	<?php
            	$res1 = mysql_query("SELECT a.cve, a.nombre FROM engomados a inner join engomados_plazas b on a.cve = b.engomado WHERE b.venta=1 AND b.plaza = '{$_POST['cveplaza']}' ORDER BY a.nombre");
				while($row1=mysql_fetch_array($res1)){
					echo '<option value="'.$row1['cve'].'">'.$row1['nombre'].'</option>';
				}
				?>
            	</select>
        	</div>
        	<label class="col-sm-2 col-form-label">Usuario</label>
			<div class="col-sm-4">
            	<select name="busquedausuario" id="busquedausuario" class="form-control" data-container="body" data-live-search="true" title="Usuario" data-hide-disabled="true" data-actions-box="true" data-virtual-scroll="false"><option value="">Todos</option>
            	<?php
            	$res1 = mysql_query("SELECT b.cve, b.usuario FROM (SELECT usuario FROM cobro_engomado WHERE plaza='{$_POST['cveplaza']}' GROUP BY usuario) a INNER JOIN usuarios b ON b.cve = a.usuario ORDER BY b.usuario");
				while($row1=mysql_fetch_array($res1)){
					echo '<option value="'.$row1['cve'].'">'.$row1['usuario'].'</option>';
				}
				?>
            	</select>
            	<script>
					$("#busquedausuario").selectpicker();	
				</script>
        	</div>
        </div>
        <div class="form-group row">
			<label class="col-sm-2 col-form-label">Tipo de Venta</label>
			<div class="col-sm-4">
            	<select name="busquedatipoventa" id="busquedatipoventa" class="form-control"><option value="">Todos</option>
            	<?php
            	$res1 = mysql_query("SELECT cve, nombre FROM tipo_venta ORDER BY nombre");
				while($row1=mysql_fetch_array($res1)){
					echo '<option value="'.$row1['cve'].'">'.$row1['nombre'].'</option>';
				}
				?>
            	</select>
        	</div>
        	<label class="col-sm-2 col-form-label">Tipo de Pago</label>
			<div class="col-sm-4">
            	<select name="busquedatipopago" id="busquedatipopago" class="form-control"><option value="">Todos</option>
            	<?php
            	$res1 = mysql_query("SELECT cve, nombre FROM tipos_pago WHERE mostrar_ventas=1 ORDER BY nombre");
				while($row1=mysql_fetch_array($res1)){
					echo '<option value="'.$row1['cve'].'">'.$row1['nombre'].'</option>';
				}
				?>
            	</select>
        	</div>
        </div>
        <div class="form-group row">
        	<div class="col-sm-12" align="center">
        		<div class="btn-group">
	        		<button type="button" class="btn btn-primary" onClick="buscar();">
		            	Buscar
		        	</button>&nbsp;&nbsp;
		        </div>
		        <div class="btn-group">
		        	<button type="button" class="btn btn-success" onClick="atcr('cobro_engomado.php','',1,0);">
		            	Nuevo
		        	</button>&nbsp;&nbsp;
		        </div>
				    <div class="btn-group">
			      	<button class="btn btn-info dropdown-toggle" type="button" id="dropdownMenuButton3" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							    Corte
							</button>
							<div class="dropdown-menu" aria-labelledby="dropdownMenuButton3">
							    <a class="dropdown-item" href="#" onClick="atcr('cobro_engomado.php','_blank', 102, 0);">Termico</a>
							    <a class="dropdown-item" href="#" onClick="atcr('cobro_engomado.php','_blank', 103, 1);">HTML</a>
							</div>
						</div>
				 	
        	</div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-3 col-md-3">
    	<div class="form-group row">
			<label class="col-sm-6 col-form-label"><b>Efectivo</b></label>
			<label class="col-sm-6 col-form-label" id="lefectivo"></label>
        </div>
        <div class="form-group row">
			<label class="col-sm-6 col-form-label"><b>Registros Totales</b></label>
			<label class="col-sm-6 col-form-label" id="lreg_total"></label>
        </div>
        <div class="form-group row">
			<label class="col-sm-6 col-form-label"><b>Activos</b></label>
			<label class="col-sm-6 col-form-label" id="lactivos"></label>
        </div>
        <div class="form-group row">
			<label class="col-sm-6 col-form-label"><b>Cancelados</b></label>
			<label class="col-sm-6 col-form-label" id="lcancelados"></label>
        </div>
        <div class="form-group row">
			<label class="col-sm-6 col-form-label"><b>Sin Entrega</b></label>
			<label class="col-sm-6 col-form-label" id="lsin_entrega"></label>
        </div>
    </div>
</div>

<div class="table-responsive">
	<table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
    	<thead>
			<tr>
				<th>&nbsp;</th>
				<th>Ticket</th>
				<th>Fecha</th>
				<th>Placa</th>
				<th>Tipo de Certificado</th>
				<th>Tipo de Venta</th>
				<th>Monto</th>
				<th>Copias</th>
				<th>Total</th>
				<th>Tipo de Pago</th>
				<th>Depositante</th>
				<th>Usuario</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>&nbsp;</th>
				<th>Ticket</th>
				<th>Fecha</th>
				<th>Placa</th>
				<th>Tipo de Certificado</th>
				<th>Tipo de Venta</th>
				<th>Monto<br><span id="tmonto" style="text-align: right;"></span></th>
				<th>Copias<br><span id="tcopias" style="text-align: right;"></span></th>
				<th>Total<br><span id="ttotal" style="text-align: right;"></span></th>
				<th>Tipo de Pago</th>
				<th>Depositante</th>
				<th>Usuario</th>
			</tr>
		</tfoot>
	</table>
</div>
<script>
	var tablalistado = $('#dataTable').DataTable( {
        "ajax": {
        	url: 'cobro_engomado.php',
        	type: "POST",
        	"data": {
        		"cmd": 10,
        		"busquedafechaini": $("#busquedafechaini").val(),
        		"busquedafechafin": $("#busquedafechafin").val(),
        		"busquedaticket": $("#busquedaticket").val(),
        		"busquedaplaca": $("#busquedaplaca").val(),
        		"busquedausuario": $("#busquedausuario").val(),
        		"busquedatipocertificado": $("#busquedatipocertificado").val(),
        		"busquedatipoventa": $("#busquedatipoventa").val(),
        		"busquedatipopago": $("#busquedatipopago").val(),
        		"cvemenu": $('#cvemenu').val(),
        		"cveplaza": $('#cveplaza').val(),
        		"cveusuario": $('#cveusuario').val()
        	},
        	fncallback: function(json){
        		$('#tmonto').html(json.monto);
        		$('#tcopias').html(json.copias);
        		$('#ttotal').html(json.total);
        		$('#lefectivo').html(json.efectivo);
        		$('#lreg_total').html(json.total_registros);
        		$('#lactivos').html(json.activos);
        		$('#lcancelados').html(json.cancelados);
        		$('#lsin_entrega').html(json.sin_entrega);
        		if (json.mostrar_mensaje_efectivo==1){
        			sweetAlert('Generar Desglose', 'En caja tiene mas de 5000 pesos, necesita hacer un desglose de dinero', 'warning');
        		}
        	}
        },
        "processing": true,
        "serverSide": true,
        "bFilter": false,
        "order": [[0, "DESC"]],
        "columnDefs": [
        	{ className: "dt-head-center dt-body-center", "targets": 0 },
        	{ className: "dt-head-center dt-body-right", "targets": 1 },
        	{ className: "dt-head-center dt-body-center", "targets": 2 },
        	{ className: "dt-head-center dt-body-center", "targets": 3 },
        	{ className: "dt-head-center dt-body-left", "targets": 4 },
        	{ className: "dt-head-center dt-body-left", "targets": 5 },
        	{ className: "dt-head-center dt-body-right", "targets": 6 },
        	{ className: "dt-head-center dt-body-right", "targets": 7 },
        	{ className: "dt-head-center dt-body-right", "targets": 8 },
        	{ className: "dt-head-center dt-body-left", "targets": 9 },
        	{ className: "dt-head-center dt-body-left", "targets": 10 },
        	{ className: "dt-head-center dt-body-left", "targets": 11 },
        	{ orderable: false, "targets": 0 }
		  ]
    } );
	function buscar(){
		tablalistado.ajax.data({
    		"cmd": 10,
    		"busquedafechaini": $("#busquedafechaini").val(),
    		"busquedafechafin": $("#busquedafechafin").val(),
    		"busquedaticket": $("#busquedaticket").val(),
    		"busquedaplaca": $("#busquedaplaca").val(),
    		"busquedausuario": $("#busquedausuario").val(),
    		"busquedatipocertificado": $("#busquedatipocertificado").val(),
    		"busquedatipoventa": $("#busquedatipoventa").val(),
    		"busquedatipopago": $("#busquedatipopago").val(),
    		"cvemenu": $('#cvemenu').val(),
    		"cveplaza": $('#cveplaza').val(),
    		"cveusuario": $('#cveusuario').val()
        });
        tablalistado.ajax.reload();
	}

	function cancelarventa(){
		if ($("#motivocancelacion").val() == ""){
			alert("Necesita seleccionar un motivo de cancelacion");
		}
		else{
			$('#modalCancelacion').modal('hide');
			waitingDialog.show();
			$.ajax({
				url: 'cobro_engomado.php',
				type: "POST",
				dataType: 'json',
				data: {
					cmd: 33,
					ticket: $('#ticketcancelar').val(),
					motivocancelacion: $("#motivocancelacion").val(),
					cveplaza: $('#cveplaza').val(),
					cveusuario: $('#cveusuario').val()
				},
				success: function(data) {
					waitingDialog.hide();
					sweetAlert('', data.mensaje, data.tipo);
					buscar();
				}
			});
		}
	}

	function precancelarventa(ticket){
		waitingDialog.show();
		$.ajax({
			url: 'cobro_engomado.php',
			type: "POST",
			dataType: 'json',
			data: {
				cmd: 34,
				ticket: ticket,
				cveplaza: $('#cveplaza').val(),
				cveusuario: $('#cveusuario').val()
			},
			success: function(data) {
				waitingDialog.hide();
				if (data.error == 1) {
					sweetAlert('', data.mensaje, 'warning');
				}
				else {
					$('#ticketcancelar').val(ticket);
					$("#motivocancelacion").val('');
					$('#modalCancelacion').modal('show');
				}
			}
		});
	}

	function editarventa(ticket) {
		waitingDialog.show();
		$.ajax({
			url: 'cobro_engomado.php',
			type: "POST",
			dataType: 'json',
			data: {
				cmd: 36,
				ticket: ticket,
				cveplaza: $('#cveplaza').val(),
				cveusuario: $('#cveusuario').val()
			},
			success: function(data) {
				waitingDialog.hide();
				if (data.error == 1) {
					sweetAlert('', data.mensaje, 'warning');
				}
				else {
					$('#ticketeditar').val(ticket);
					$("#placaeditar").val(data.placa);
					$("#fechaeditar").val(data.fecha);
					$("#tipopagoeditar").html(data.tipos_pago);
					$("#depositanteeditar").html(data.depositantes);
					$('#modalEditar').modal('show');
				}
			}
		});
	}

	function guardareditar(){
		if ($("#placaeditar").val() == ""){
			alert("Necesita ingresar la placa");
		}
		else{
			$('#modalEditar').modal('hide');
			waitingDialog.show();
			$.ajax({
				url: 'cobro_engomado.php',
				type: "POST",
				data: {
					cmd: 37,
					ticket: $('#ticketeditar').val(),
					placa: $("#placaeditar").val(),
					fecha: $("#fechaeditar").val(),
					tipo_pago: $("#tipopagoeditar").val(),
					depositante: $("#depositanteeditar").val(),
					cveplaza: $('#cveplaza').val(),
					cveusuario: $('#cveusuario').val(),
					cvemenu: $('#cvemenu').val()
				},
				success: function(data) {
					waitingDialog.hide();
					buscar();
				}
			});
		}
	}

	function generar_nota_credito(ticket){
		if(confirm('Esta seguro de generar la nota de credito del ticket?')){
			waitingDialog.show();
			$.ajax({
				url: 'cobro_engomado.php',
				type: "POST",
				dataType: 'json',
				data: {
					cmd: 35,
					ticket: ticket,
					cveplaza: $('#cveplaza').val(),
					cveusuario: $('#cveusuario').val()
				},
				success: function(data) {
					waitingDialog.hide();
					sweetAlert('', data.mensaje, data.tipo);
					buscar();
				}
			});
		}
	}

	$("#modalCancelacion").modal({
		backdrop: false,
		keyboard: false,
		show: false
	});

	$("#modalEditar").modal({
		backdrop: false,
		keyboard: false,
		show: false
	});
</script>
<?php
}

if($_POST['cmd']==10){
	$columnas=array("a.cve", "a.fecha", "a.placa", "b.nombre", 'c.nombre', 'a.monto', 'a.copias', '(a.monto+a.copias)', 'e.nombre', 'f.nombre', 'h.usuario');

	$orderby = "";
	foreach($_POST['order'] as $dato){
		$orderby .= ",{$columnas[$dato['column']]} {$dato['dir']}";
	}

	if($orderby == ""){
		$orderby = " ORDER BY a.cve";
	}
	else{
		$orderby = " ORDER BY ".substr($orderby, 1);
	}

	$condicionmonto = " AND a.tipo_pago=1";
	$condicionmontocopias = " AND (a.tipo_pago NOT IN (5,7) OR a.pago_copias_efectivo = 1)";

	$where = " WHERE a.plaza='{$_POST['cveplaza']}'";
	$where1 = " WHERE a.plaza='{$_POST['cveplaza']}'";
	if ($_POST['busquedaticket']) {
		$where .= " AND a.cve = '{$_POST['busquedaticket']}'";
	}
	else{
		if($_POST['busquedafechaini']!=''){
			$where .= " AND a.fecha >= '{$_POST['busquedafechaini']}'";
			$where1 .= " AND a.fecha >= '{$_POST['busquedafechaini']}'";
		}

		if($_POST['busquedafechafin']!=''){
			$where .= " AND a.fecha <= '{$_POST['busquedafechafin']}'";
			$where1 .= " AND a.fecha <= '{$_POST['busquedafechafin']}'";
		}

		if($_POST['busquedaplaca']!=''){
			$where .= " AND a.placa = '{$_POST['busquedaplaca']}'";
		}

		if($_POST['busquedatipocertificado']!=''){
			$where .= " AND a.engomado = '{$_POST['busquedatipocertificado']}'";
		}

		if($_POST['busquedausuario']!=''){
			$where .= " AND a.usuario = '{$_POST['busquedausuario']}'";
			$where1 .= " AND a.usuario = '{$_POST['busquedausuario']}'";
		}

		if($_POST['busquedatipoventa']!=''){
			$where .= " AND a.tipo_venta = '{$_POST['busquedatipoventa']}'";
		}

		if($_POST['busquedatipopago']!=''){
			$where .= " AND a.tipo_pago = '{$_POST['busquedatipopago']}'";
			$condicionmonto="";
			$condicionmontocopias="";
		}
	}

	$row1 = mysql_fetch_assoc(mysql_query("SELECT COUNT(a.cve) as registros, SUM(IF(a.estatus='A',1,0)) as activos,SUM(IF(a.estatus='C',1,0)) as cancelados,SUM(IF(a.estatus='A' AND a.tipo_venta=0 AND a.tipo_pago=1,a.monto,0)) as efectivo, SUM(IF(a.estatus='A' AND ISNULL(b.cve),1,0)) as sin_entrega FROM cobro_engomado a LEFT JOIN certificados b ON a.plaza = b.plaza AND a.cve = b.ticket AND b.estatus!='C'{$where1}"));
	$efectivo = $row1['efectivo'];
	$total_registros = $row1['registros'];
	$activos = $row1['activos'];
	$cancelados = $row1['cancelados'];
	$sin_entrega = $row1['sin_entrega'];
	$row1 = mysql_fetch_assoc(mysql_query("SELECT SUM(a.monto) as importe FROM pagos_caja a{$where1} AND a.estatus!='C' AND a.forma_pago=1"));
	$efectivo+=$row1['importe'];

	$row1 = mysql_fetch_assoc(mysql_query("SELECT SUM(a.devolucion) as importe FROM devolucion_certificado a{$where1} AND a.estatus!='C'"));
	$efectivo-=$row1['importe'];
	$row1 = mysql_fetch_assoc(mysql_query("SELECT SUM(a.monto) as importe FROM desglose_dinero a{$where1} AND a.estatus!='C'"));
	$efectivo-=$row1['importe'];

	$res = mysql_query("SELECT COUNT(a.cve) as registros, SUM(IF(a.estatus!='C'{$condicionmontocopias}, a.monto, 0)) as monto, SUM(IF(a.estatus!='C'{$condicionmontocopias}, a.copias*a.costo_copias, 0)) as copias FROM cobro_engomado a {$where1}");
	$registros = mysql_fetch_assoc($res);
	$resultado = array(
		'data' => array(),
		'draw'=> $_POST['draw'],
		'recordsTotal'=> $registros['registros'],
		'recordsFiltered'=> $registros['registros'],
		'monto' => number_format($registros['monto'],2),
		'copias' => number_format($registros['copias'],2),
		'total' => number_format($registros['monto']+$registros['copias'],2),
		'efectivo' => number_format($efectivo,2),
		'total_registros' => number_format($total_registros,0),
		'activos' => number_format($activos,0),
		'cancelados' => number_format($cancelados,0),
		'sin_entrega' => number_format($sin_entrega,0),
		'mostrar_mensaje_efectivo' => (($efectivo >= 5000) ? 0 : 0)
	);
	$res = mysql_query("SELECT a.cve, a.fecha, a.hora, a.placa, a.multa, b.nombre as nomengomado, c.nombre as nomtipoventa, IF(a.estatus!='C'{$condicionmonto},a.monto,0) as monto, IF(a.estatus!='C'{$condicionmontocopias}, a.copias*a.costo_copias,0) as copias, IF(a.estatus!='C'{$condicionmonto},a.monto+a.copias*a.costo_copias,0) as total, e.nombre as nomtipopago, f.nombre as nomdepositante, h.usuario, a.estatus, a.fechacan, a.usucan, a.factura, a.notacredito FROM cobro_engomado a INNER JOIN engomados b ON b.cve = a.engomado INNER JOIN tipo_venta c ON c.cve = a.tipo_venta INNER JOIN tipos_pago e ON e.cve = a.tipo_pago LEFT JOIN depositantes f ON f.cve = a.depositante INNER JOIN usuarios h ON h.cve = a.usuario{$where}{$orderby} LIMIT {$_POST['start']},{$_POST['length']}");
	$tmonto = 0;
	$nivelUsuario = nivelUsuario();
	$usuarios_editar=array(1,10);
	while($row = mysql_fetch_assoc($res)){
		
		$extras2 = '';
		if ($row['estatus'] == 'A' && $nivelUsuario >= 3) {
			$extras2 .= '<a class="dropdown-item" href="#" onClick="precancelarventa('.$row['cve'].')">Cancelar</a>';
		}
		if ($row['estatus'] =='A' && $nivelUsuario>=3 && $row['factura']>0 && $row['notacredito']==0){
			$extras2 .= '<a class="dropdown-item" href="#" onClick="generar_nota_credito('.$row['cve'].')">Generar Nota de Cr&eacute;dito</a>';
		}
		if($row['estatus']=='A' && in_array($_POST['cveusuario'], $usuarios_editar)){
			$extras2 .= '<a class="dropdown-item" href="#" onClick="editarventa('.$row['cve'].')">Editar Venta</a>';
		}

		$dropmenu = '<button class="btn btn-info dropdown-toggle" type="button" id="dropdownMenuButton_'.$row['cve'].'" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                      Acci&oacute;n
                    </button><div class="dropdown-menu animated--fade-in" aria-labelledby="dropdownMenuButton_'.$row['cve'].'" x-placement="bottom-start" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(0px, 38px, 0px);">
                      <a class="dropdown-item" href="#" onClick="atcr(\'cobro_engomado.php\',\'_blank\',101,'.$row['cve'].')">Imprimir</a>
                      '.$extras2.'
                    </div>';
    if($row['estatus']=='C'){
    	$dropmenu='CANCELADO<br>'.$row['fechacan'].'<br>';
    	$Usuario=mysql_fetch_assoc(mysql_query("SELECT usuario FROM usuarios WHERE cve='{$row['usucan']}'"));
    	$dropmenu.=$Usuario['usuario'];

    }
    elseif($row['estatus']=='D'){
    	$dropmenu="DEVUELTO";
    	$devolucion=mysql_fetch_assoc(mysql_query("SELECT fecha FROM devolucion_certificado WHERE plaza={$_POST['cveplaza']} AND ticket={$row['cve']} AND estatus!='C'"));
    	$dropmenu.='<br>'.$devolucion['fecha'];
    }
		$resultado['data'][] = array(
			$dropmenu,
			($row['cve']),
			mostrar_fechas($row['fecha']).' '.$row['hora'],
			utf8_encode($row['placa']),
			utf8_encode($row['nomengomado']),
			utf8_encode($row['nomtipoventa']),
			number_format($row['monto'],2),
			number_format($row['copias'],2),
			number_format($row['monto']+$row['copias'],2),
			utf8_encode($row['nomtipopago']),
			utf8_encode($row['nomdepositante']),
			utf8_encode($row['usuario'])
		);
	}
	echo json_encode($resultado);

}

if($_POST['cmd']==1){
	$res = mysql_query("SELECT * FROM costos_copias_impresiones ORDER BY cve DESC");
	$row = mysql_fetch_assoc($res);
	$costo_copias = $row['copias'];
	$res=mysql_query("SELECT local,vende_seguros,intentoporcertificadodif,num_intentos,num_intentosanticipados,bloquear_impresion, pagos_para_cortesia,maneja_medio_pago,monto_medio_pago, costo_copias FROM plazas WHERE cve='".$_POST['cveplaza']."'");
	$row=mysql_fetch_array($res);
	$PlazaLocal=$row[0];
	$VendeSeguros=$row[1];
	$num_intentos_plaza = $row['num_intentos'];
	$num_intentos_anticipados = $row['num_intentosanticipados'];
	$intentoporcertificadodif = $row['intentoporcertificadodif'];
	$bloquear_impresion = $row['bloquear_impresion'];
	$pagos_para_cortesia = $row['pagos_para_cortesia'];
	$maneja_medio_pago = $row['maneja_medio_pago'];
	$monto_medio_pago = $row['monto_medio_pago'];

?>
<input type="hidden" id="tienebrinco" name="tienebrinco" value="">
<input type="hidden" id="tipopagobrinco" name="tipopagobrinco" value="">
<div id="modalBrinco" class="modal fade" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="staticBackdropLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="staticBackdropLabel">Supervisi&oacute;n</h5>
		        <!--<button type="button" class="close" data-dismiss="modal" aria-label="Close">
		          <span aria-hidden="true">&times;</span>
		        </button>-->
			</div>
			<div class="modal-body" id="bodypago">
				<div class="row">
					<div class="col-xl-12 col-lg-12 col-md-12">
						<div class="form-row">
					        <div class="form-group col-sm-12">
								<label><h3>La placa tiene supervision <span id="tipobrinco"></span></h3></label>
					        </div>
					    </div>
						<div class="form-row">
					        <div class="form-group col-sm-12">
								<label for="tipopagobrincod">Tipo de Pago</label>
					            <select id="tipopagobrincod" class="form-control"><option value="1">Efectivo</option></select>
					        </div>
					    </div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-danger" onClick="aceptar_brinco()">Aceptar</button>
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
		     </div>
		</div>
	</div>
</div>
<div class="row justify-content-center">
	<div class="col-sm-12" align="center">
	<?php
		if(nivelUsuario() > 1){
	?>
		<button type="button" class="btn btn-success" onClick="tiene_brinco();">Guardar</button>
	&nbsp;&nbsp;&nbsp;
	<?php
		}
	?>
		<button type="button" class="btn btn-primary" onClick="$('#contenedorprincipal').html('');atcr('cobro_engomado.php','',0,0);">Volver</button>
	</div>
</div><br>
<div class="row">
	<div class="col-xl-6 col-lg-6 col-md-6">
		<div class="card shadow">
			<div class="card-header">
				<h6 class="m-0 font-weight-bold text-secondary">Venta</h6>
			</div>
		  <div class="card-body">
		  	<div class="form-row">
	      		<div class="form-group col-sm-1">
					<label for="voluntario">Voluntario</label><br>
	          		<input type="checkbox" id="voluntario" class="form-control" onClick="cambiar_check('voluntario');mostrar_entidad();" onChange="cambiar_check('voluntario');mostrar_entidad();">
					<input type="hidden" class="form-control" id="voluntario_h" name="voluntario" value="0">
	        	</div>
	        	<div class="form-group col-sm-3" style="display: none;">
					<label for="entidad">Entidad</label>
	          		<select name="entidad" id="entidad" class="form-control"><option value="">Seleccione</option>
	           	<?php
	           		$res1 = mysql_query("SELECT cve, nombre FROM cat_entidades ORDER BY nombre");
					while($row1=mysql_fetch_array($res1)){
						echo '<option value="'.$row1['cve'].'">'.utf8_encode($row1['nombre']).'</option>';
					}
					?>
					</select>
	        	</div>
	      </div>
			
	      <div class="form-row">
	      		<div class="form-group col-sm-1">
					<label for="multa">Multa</label><br>
	          		<input type="checkbox" id="multa" class="form-control" onClick="cambiar_check('multa');mostrar_multa();" onChange="cambiar_check('multa');mostrar_multa();">
					<input type="hidden" class="form-control" id="multa_h" name="multa" value="0">
	        	</div>
	        	<div class="form-group col-sm-3" style="display: none;">
					<label for="folio_multa">Folio Multa</label>
	          		<input type="text" class="form-control" id="folio_multa" value="" name="folio_multa">
	        	</div>
	      </div>
	      <div class="form-row">
	        	<div class="form-group col-sm-3">
					<label for="placa">Placa</label>
	           		<input type="text" class="form-control" id="placa" value="" autocomplete="off" onChange="traeRegistro();" onKeyUp="if(event.keyCode==13){ traeRegistro();}else{this.value = $.trim(this.value.toUpperCase());}" name="placa">
	        	</div>
	      </div>
	      <div class="form-row">
	        <div class="form-group col-sm-3">
						<label for="engomado">Tipo de Certificado</label>
	          <select name="engomado" id="engomado" class="form-control" onChange="muestra_precio()">
	           	<?php
	           		$res1 = mysql_query("SELECT a.cve, a.nombre, b.precio FROM engomados a inner join engomados_plazas b on a.cve = b.engomado WHERE b.venta=1 AND b.plaza = '{$_POST['cveplaza']}' ORDER BY a.nombre");
	           		if(mysql_num_rows($res1) > 1){
	           			echo '<option value="0" precio="0">Seleccione</option>';
	           		}
								while($row1=mysql_fetch_array($res1)){
									echo '<option value="'.$row1['cve'].'" precio="'.$row1['precio'].'">'.utf8_encode($row1['nombre']).'</option>';
								}
							?>
						</select>
	        </div>
	      </div>
	      <div class="form-row">
	        <div class="form-group col-sm-3">
						<label for="tipo_venta">Tipo de Venta</label>
	          <select name="tipo_venta" id="tipo_venta" class="form-control" onChange="mostrar_campos_tipo_venta()">
	           	<?php
	           	$filtrocortesias = "";
	           	if(nivelUsuario() < 3) {
	           		$filtrocortesias = " WHERE cve NOT IN (4)";
	           	}
	           		$res1 = mysql_query("SELECT cve, nombre, costo, maneja_motivo, maneja_autoriza FROM tipo_venta{$filtrocortesias} ORDER BY cve");
								while($row1=mysql_fetch_array($res1)){
									echo '<option value="'.$row1['cve'].'" costo="'.$row1['costo'].'" maneja_motivo="'.$row1['maneja_motivo'].'" maneja_autoriza="'.$row1['maneja_autoriza'].'">'.utf8_encode($row1['nombre']).'</option>';
								}
							?>
						</select>
	        </div>
	        <div class="form-group col-sm-3"<?php if($maneja_medio_pago!=1){?> style="display: none;"<?php }?>>
						<label for="costo_especial">Descuento por 3 y 4 intento</label><br>
	          <input type="checkbox" id="costo_especial" class="form-control" onClick="cambiar_check('costo_especial');costo_especial();" onChange="cambiar_check('costo_especial');costo_especial();">
						<input type="hidden" class="form-control" id="costo_especial_h" name="costo_especial" value="0">
	        </div>
	        <div class="form-group col-sm-3" style="display: none;">
						<label for="motivo_intento">Motivo Intento</label>
	          <select name="motivo_intento" id="motivo_intento" class="form-control"><option value="0">Seleccione</option>
	           	<?php
	           		$res1 = mysql_query("SELECT cve, nombre FROM motivos_intento ORDER BY cve");
								while($row1=mysql_fetch_array($res1)){
									echo '<option value="'.$row1['cve'].'">'.utf8_encode($row1['nombre']).'</option>';
								}
							?>
						</select>
	        </div>
	        <div class="form-group col-sm-3" style="display: none;">
						<label for="tipo_cortesia">Tipo de Cortesia</label>
	          <select name="tipo_cortesia" id="tipo_cortesia" class="form-control" onChange="muestra_campo_cortesia()"><option value="0">Seleccione</option>
	           	<?php
	           		if(in_array($_POST['cveusuario'], $usuarios_root))
	           			$res1 = mysql_query("SELECT cve, nombre FROM tipos_cortesia ORDER BY cve");
	           		else
	           			$res1 = mysql_query("SELECT cve, nombre FROM tipos_cortesia WHERE cve!=1 ORDER BY cve");
								while($row1=mysql_fetch_array($res1)){
									echo '<option value="'.$row1['cve'].'">'.utf8_encode($row1['nombre']).'</option>';
								}
							?>
						</select>
	        </div>
	        <div class="form-group col-sm-3" style="display: none;">
						<label for="autoriza">Autoriza</label>
	          <input type="text" class="form-control" id="autoriza" value="" name="autoriza">
	        </div>
	        <div class="form-group col-sm-3" style="display: none;">
						<label for="codigo_cortesia">Vale de Cortesia</label>
	          <input type="number" class="form-control" id="codigo_cortesia" value="" name="codigo_cortesia">
	        </div>
	      </div>
	     	<div class="form-row">
	        <div class="form-group col-sm-3">
						<label for="tipo_pago">Tipo de Pago</label>
	          <select name="tipo_pago" id="tipo_pago" class="form-control" onChange="mostrar_campos_tipo_pago()">
	           	<?php
	           		$res1 = mysql_query("SELECT cve, nombre FROM tipos_pago WHERE mostrar_ventas=1 ORDER BY cve");
								while($row1=mysql_fetch_array($res1)){
									echo '<option value="'.$row1['cve'].'">'.utf8_encode($row1['nombre']).'</option>';
								}
							?>
						</select>
	        </div>
	        <div class="form-group col-sm-3" style="display: none;">
						<label for="vale_pago_anticipado">Vale Anticipado</label>
	          <input type="number" class="form-control" id="vale_pago_anticipado" value="" name="vale_pago_anticipado">
	        </div>
	        <div class="form-group col-sm-6" style="display: none;">
			  <label for="referencia_tarjeta">Referencia</label>
	          <input type="number" class="form-control" id="referencia_tarjeta" value="" name="referencia_tarjeta">
	        </div>
	        <div class="form-group col-sm-3" style="display: none;">
			  <label for="pago_copias_efectivo">Copias en Efectivo</label>
	          <input type="checkbox" class="form-control" id="pago_copias_efectivo" value="1" name="pago_copias_efectivo">
	        </div>
	        
	      </div>
	      <div class="form-row">
	      	<div class="form-group col-sm-8">
				<label for="depositante">Depositante</label>
	         	<select name="depositante" class="form-control" data-container="body" data-live-search="true" title="Depositante" data-hide-disabled="true" data-actions-box="true" data-virtual-scroll="false" id="depositante"><option value="">Seleccione</option>

	         	</select>
	         	<script>
					$("#depositante").selectpicker();	
				</script>
	        </div>
	      </div>
	      <div class="form-row">
	      	<div class="form-group col-sm-4">
						<label for="monto">Monto</label>
	          <input type="number" class="form-control" id="monto" value="" name="monto" readOnly>
	        </div>
	        <div class="form-group col-sm-4"<?php if($costo_copias==0){?> style="display: none;"<?php }?>>
						<label for="copias">Copias</label>
	          <input type="number" class="form-control" id="copias" value="" name="copias" onKeyUp="calcular()">
	        </div>
	        <div class="form-group col-sm-4"<?php if($costo_copias==0){?> style="display: none;"<?php }?>>
						<label for="total">Total</label>
	          <input type="number" class="form-control" id="total" value="" name="total" readOnly>
	        </div>
	      </div>
	      <div class="form-row">
	      	<div class="form-group col-sm-6">
						<label for="tipo_combustible">Tipo de Combustible</label>
	          <select name="tipo_combustible" id="tipo_combustible" class="form-control"><option value="0">Seleccione</option>
	           	<?php
	           		$res1 = mysql_query("SELECT cve, nombre FROM tipo_combustible ORDER BY cve");
								while($row1=mysql_fetch_array($res1)){
									echo '<option value="'.$row1['cve'].'">'.utf8_encode($row1['nombre']).'</option>';
								}
							?>
						</select>
	        </div>
	        <div class="form-group col-sm-6" style="display: none;">
				<label for="certificado_anterior">Certificado Anterior</label>
	          	<input type="text" class="form-control" id="certificado_anterior" value="" name="certificado_anterior">
	        </div>
	      </div>
	      <div class="form-row">
	        <div class="form-group col-sm-5">
	        	<label for="obs">Observaciones</label>
	        	<textarea rows="3" id="obs" name="obs" class="form-control"></textarea>
	        </div>
	      </div>
	    </div>
	  </div>
	</div>
	<div class="col-xl-6 col-lg-6 col-md-6">
		<div class="card shadow">
			<div class="card-header">
				<h6 class="m-0 font-weight-bold text-secondary">Historial Placa</h6>
			</div>
		  <div class="card-body" id="divhistorial">
		  </div>
		</div>
	</div>
</div>


<script>
function traeRegistro(){
	$.ajax({
	  url: "cobro_engomado.php",
	  type: "POST",
	  async: false,
	  dataType: "json",
	  data: {
		placa: $('#placa').val(),
		cvemenu: $('#cvemenu').val(),
        cveplaza: $('#cveplaza').val(),
        cveusuario: $('#cveusuario').val(),
		cmd: 30
	  },
		success: function(data) {
			$('#divhistorial').html(data.historial);
			$('#certificado_anterior').val(data.certificado_anterior);
			if(data.supervision==1){
				alert("La plaza tiene supervision");
			}
		}
	});
}

function muestra_precio(){
	if($('#tipo_venta').val() == 0){
		$('#monto').val($('#engomado').find('option:selected').attr('precio'));
	}
	else {
		$('#monto').val($('#tipo_venta').find('option:selected').attr('costo'));	
	}
	calcular();
}

function mostrar_multa(){
	$('#folio_multa').val('');
	if($('#multa_h').val() == 1) {
		$('#folio_multa').parents('div:first').show();
	}
	else {
		$('#folio_multa').parents('div:first').hide();
	}
}

function mostrar_entidad(){
	$('#entidad').val('');
	if($('#voluntario_h').val() == 1) {
		$('#entidad').parents('div:first').show();
	}
	else {
		$('#entidad').parents('div:first').hide();
	}
}

muestra_precio();

function mostrar_campos_tipo_venta(){
	$('#costo_especial').parents('div:first').hide();
	$('#costo_especial').removeAttr('checked');
	cambiar_check('costo_especial');
	$('#motivo_intento').parents('div:first').hide();
	$('#motivo_intento').val('0');
	$('#tipo_cortesia').parents('div:first').hide();
	$('#tipo_cortesia').val('0');
	if($('#tipo_venta').val() == 0){
		<?php if($maneja_medio_pago==1){?>$('#costo_especial').parents('div:first').show();<?php }?>
	}
	else if($('#tipo_venta').val() == 1){
		$('#motivo_intento').parents('div:first').show();
	}
	else if($('#tipo_venta').val() == 2){
		$('#tipo_cortesia').parents('div:first').show();
	}



	mostrar_campos_tipo_pago();
	muestra_precio();
	muestra_campo_cortesia();
}

function muestra_campo_cortesia(){
	$('#autoriza').parents('div:first').hide();
	$('#autoriza').val('');
	$('#codigo_cortesia').parents('div:first').hide();
	$('#codigo_cortesia').val('');
	if ($('#tipo_cortesia').val() == 1){
		$('#autoriza').parents('div:first').show();
	}
	else if ($('#tipo_cortesia').val() >= 2){
		$('#codigo_cortesia').parents('div:first').show();
	}
}

function costo_especial(){
	if($('#costo_especial_h').val() == 1) {
		$('#monto').val(<?php echo $monto_medio_pago;?>);
		calcular();
	}
	else{
		muestra_precio();
	}
}

function mostrar_campos_tipo_pago(){
	$('#vale_pago_anticipado').parents('div:first').hide();
	$('#vale_pago_anticipado').val('');
	traeDepositante();
	if ($('#tipo_venta').val() == 0){
		if($('#tipo_pago').val() == 6){
			$('#vale_pago_anticipado').parents('div:first').show();
		}
		
	}
	if($('#tipo_pago').val() == 5 || $('#tipo_pago').val() == 7){
		$('#referencia_tarjeta').parents('div:first').show();	
		$('#pago_copias_efectivo').parents('div:first').show();	
	}
	else{
		$('#referencia_tarjeta').parents('div:first').hide();
		$('#referencia_tarjeta').val('');
		$('#pago_copias_efectivo').parents('div:first').hide();
		$('#pago_copias_efectivo').val('');
	}
}

function traeDepositante(){
	$.ajax({
		url: 'pagos_caja.php',
		type: "POST",
		dataType: 'json',
		data: {
			cmd: 20,
			cveplaza: $('#cveplaza').val(),
			tipo_pago: $('#tipo_pago').val()
		},
		success: function(data) {
			$('#depositante').html(data.html);
			$('#depositante').selectpicker('refresh');
		}
	});
}

function calcular(){
	var total = $('#monto').val()/1;
	<?php if($costo_copias>0){?>
		total += <?php echo $costo_copias;?>*$('#copias').val();
		$('#total').val(total.toFixed(2));
	<?php }?>
}

$('#placa').keypress(function(e){
	var caracteres_permitidos = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
	var c = String.fromCharCode(e.which);
	if (caracteres_permitidos.indexOf(c) < 0){
		return false;
	}
});

$("#modalBrinco").modal({
	backdrop: false,
	keyboard: false,
	show: false
});

function aceptar_brinco(){
	if($('#tipopagobrincod').val() == ''){
		alert("Necesita ingresar el tipo de pago del apoyo");
	}
	else{
		$('#tienebrinco').val(1);
		$('#tipopagobrinco').val($('#tipopagobrincod').val());
		atcr('cobro_engomado.php','',2,'0');
	}
}

function tiene_brinco(){
	$('#tienebrinco').val(0);
	$('#tipopagobrinco').val(0);
	$('#tipopagobrincod').val('');
	$.ajax({
		url: 'cobro_engomado.php',
		type: "POST",
		dataType: 'json',
		data: {
			cmd: 21,
			cveplaza: $('#cveplaza').val(),
			placa: $('#placa').val()
		},
		success: function(data) {
			if(data.resultado==1){
				$("#tipobrinco").html(data.tipo);
				$('#modalBrinco').modal('show');
			}
			else{
				atcr('cobro_engomado.php','',2,'0');
			}
		}
	});
}

traeDepositante();
</script>

<?php
}


if($_POST['cmd']==20){
	$resultado=array('mensaje' => '', 'depositante' => '', 'nom_cliente' => '');
	if($_POST['tipo_pago'] == 6){
		$tipo_depositante=0;
	}
	elseif($_POST['tipo_pago']==2){
		$tipo_depositante=4;
	}
	else{
		$tipo_depositante=2;
	}
	$res = mysql_query("SELECT cve, nombre FROM depositantes WHERE plaza='{$_POST['cveplaza']}' AND tipo_depositante='{$tipo_depositante}' AND numero_cliente='{$_POST['no_cliente']}'");
	if($row = mysql_fetch_assoc($res)){
		$resultado['depositante'] = $row['cve'];
		$resultado['nom_cliente'] = utf8_encode($row['nombre']);
	}
	else{
		$resultado['mensaje'] = utf8_encode('No se encontró el cliente');
	}

	echo json_encode($resultado);
}

if($_POST['cmd']==21){
	$respuesta = array('resultado' => 0, 'tipo' => '');
	$res = mysql_query("SELECT cve, nombre FROM tipos_brinco WHERE plaza='{$_POST['plazausuario']}' ORDER BY nombre");
	$array_tipo = array();
	while($row = mysql_fetch_assoc($res)){
		$array_tipo[$row['cve']] = $row['nombre'];
	}
	$fecha_minima = date( "Y-m-d" , strtotime ( "-7 day" , strtotime(date('Y-m-d')) ));
	$res = mysql_query("SELECT cve, tipo FROM placas_brincos WHERE plaza='{$_POST['cveplaza']}' AND placa='{$_POST['placa']}' AND fecha>='{$fecha_minima}'");
	if($row = mysql_fetch_array($res)){
		$respuesta = array('resultado' => 1, 'tipo' => utf8_encode($array_tipo[$row['tipo']]));
	}
	echo json_encode($respuesta);
	exit();
}


if($_POST['cmd']==2){
	$resultado = array('error' => 0, 'mensaje' => '');
	$res = mysql_query("SELECT placa, tipo_venta, tipo_pago FROM cobro_engomado WHERE plaza={$_POST['cveplaza']} AND fecha=CURDATE() AND estatus!='C' ORDER BY cve DESC LIMIT 1");
	$row = mysql_fetch_assoc($res);
	//if($row['placa']==$_POST['placa'] && $row['tipo_venta']==$_POST['tipo_venta'] && $row['tipo_pago'] == $_POST['tipo_pago']){
	if($row['placa']==$_POST['placa']){
		$resultado = array('error' => 1, 'mensaje' => 'Ocurrio un error al guardar favor de recargar la pantalla');
	}
	elseif(trim($_POST['placa'])==''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar la placa');
	}
	elseif(trim($_POST['engomado']) == '0'){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita seleccionar el tipo de certificado');
	}
	elseif(trim($_POST['tipo_venta']) == 1 && $_POST['motivo_intento']==0){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita seleccionar el motivo del intento');
	}
	elseif(trim($_POST['tipo_venta']) == 2 && $_POST['tipo_cortesia']==0){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita seleccionar el tipo de cortesia');
	}
	elseif(trim($_POST['tipo_venta']) == 2 && $_POST['tipo_cortesia']==1 && trim($_POST['autoriza']) == ''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar quien autoriza la cortesia');
	}
	elseif(trim($_POST['tipo_venta']) == 2 && ($_POST['tipo_cortesia']==2 || $_POST['tipo_cortesia']==3) && trim($_POST['codigo_cortesia']) == ''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar codigo de cortesia');
	}
	elseif(trim($_POST['tipo_venta']) == 2 && $_POST['tipo_cortesia']==2 && $_POST['tipo_pago'] != 6){
		$resultado = array('error' => 1, 'mensaje' => 'El tipo de cortesia solo es para pago anticipado');
	}
	elseif(trim($_POST['tipo_venta']) == 2 && $_POST['tipo_cortesia']==3 && $_POST['tipo_pago'] != 1 && $_POST['tipo_pago'] != 5 && $_POST['tipo_pago'] != 7){
		$resultado = array('error' => 1, 'mensaje' => 'El tipo de cortesia solo es para pagos de contado, tarjeta de credito o debito');
	}
	elseif(trim($_POST['tipo_venta']) == 0 && $_POST['tipo_pago']==6 && trim($_POST['vale_pago_anticipado']) == ''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar vale de pago anticipado');
	}
	elseif((trim($_POST['tipo_pago']) == 6 || $_POST['tipo_pago']==2) && $_POST['depositante']==''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar cliente');
	}
	elseif((trim($_POST['tipo_pago']) == 5 || $_POST['tipo_pago']==7) && $_POST['referencia_tarjeta']==''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar la referencia');
	}
	elseif($_POST['multa']==1 && $_POST['folio_multa']==''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar el folio de la multa');
	}
	elseif($_POST['voluntario']==1 && $_POST['entidad']==''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita seleccionar la entidad');
	}
	elseif(trim($_POST['tipo_combustible']) == '0'){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita seleccionar el tipo de combustible');
	}
	elseif($_POST['tipo_pago']==6 && ($_POST['vale_pago_anticipado']!='' || $_POST['codigo_cortesia'] != '')){
		if($_POST['tipo_venta']==0){
			$tipo=0;
			$nomtipo='vale de pago anticipado';
			$vale=$_POST['vale_pago_anticipado'];
		}
		else{
			$tipo=1;
			$nomtipo='codigo de cortesia';
			$vale=$_POST['codigo_cortesia'];	
		}
		$res = mysql_query("SELECT a.depositante, b.estatus, a.engomado FROM pagos_caja a INNER JOIN vales_pago_anticipado b ON a.plaza = b.plaza AND a.cve = b.pago WHERE a.plaza='{$_POST['cveplaza']}' AND b.cve= '{$vale}' AND b.tipo='{$tipo}'");
		if ($row = mysql_fetch_assoc($res)){
			if($row['estatus']=='C'){
				$resultado = array('error' => 1, 'mensaje' => utf8_encode('El '.$nomtipo.' está cancelado'));
			}
			elseif($row['depositante']!=$_POST['depositante']){
				$resultado = array('error' => 1, 'mensaje' => 'El depositante no pertenece al folio');
			}
			elseif($row['engomado']!=$_POST['engomado']){
				$resultado = array('error' => 1, 'mensaje' => 'El tipo de certificado es diferente al del vale');
			}
			else{
				if($tipo==0)
					$res = mysql_query("SELECT cve FROM cobro_engomado WHERE plaza='{$_POST['cveplaza']}' AND vale_pago_anticipado = '{$vale}' AND estatus!='C' AND tipo_pago=6 and tipo_venta=0");
				else
					$res = mysql_query("SELECT cve FROM cobro_engomado WHERE plaza='{$_POST['cveplaza']}' AND codigo_cortesia = '{$vale}' AND estatus!='C' AND tipo_cortesia=2");
				if(mysql_num_rows($res)>0){
					$resultado = array('error' => 1, 'mensaje' => 'El vale ya fue utilizado');
				}
			}
		}
		else{
			$resultado = array('error' => 1, 'mensaje' => utf8_encode('No se encontró el '.$nomtipo));
		}
	}
	elseif($_POST['tipo_venta']==2 && $_POST['tipo_cortesia']==3) {
		$res = mysql_query("SELECT depositante, estatus, engomado FROM vale_cortesia_acumulado WHERE plaza={$_POST['cveplaza']} AND folio= {$_POST['codigo_cortesia']}");
		if($row = mysql_fetch_assoc($res)){
			if($row['estatus']=='C'){
				$resultado = array('error' => 1, 'mensaje' => utf8_encode('El vale está cancelado'));
			}
			elseif($row['depositante']!=$_POST['depositante']){
				$resultado = array('error' => 1, 'mensaje' => 'El depositante no pertenece al folio');
			}
			/*elseif($row['engomado']!=$_POST['engomado']){
				$resultado = array('error' => 1, 'mensaje' => 'El tipo de certificado es diferente al del vale');
			}*/
			else{
				$res = mysql_query("SELECT cve FROM cobro_engomado WHERE plaza='{$_POST['cveplaza']}' AND codigo_cortesia = '{$vale}' AND estatus!='C' AND tipo_cortesia = 3");
				if(mysql_num_rows($res)>0){
					$resultado = array('error' => 1, 'mensaje' => 'El vale ya fue utilizado');
				}
			}
		}
		else{
			$resultado = array('error' => 1, 'mensaje' => utf8_encode('No se encontró el vale'));
		}
	}
	elseif($_POST['tipo_venta']==1){
		$intentos = 1;
		if(in_array($_POST['cveusuario'], $usuarios_root)) $intentos=99999;
		elseif($_POST['cveusuario']==7) $intentos=3;
		$res = mysql_query("SELECT cve,fecha,hora,depositante FROM cobro_engomado WHERE plaza='{$_POST['cveplaza']}' AND placa='{$_POST['placa']}' AND estatus!='C' AND estatus!='D' AND costo_especial!=1 AND tipo_venta IN (0,2,4)  AND DATEDIFF(CURDATE(), fecha)<=90 ORDER BY cve DESC LIMIT 1");
		$row = mysql_fetch_assoc($res);
		if($row['cve']>0 || in_array($_POST['cveusuario'], $usuarios_root)){
			$res1=mysql_query("SELECT COUNT(cve) as intentos FROM cobro_engomado WHERE plaza='{$_POST['cveplaza']}' AND placa='{$_POST['placa']}' AND estatus!='C' AND estatus!='D' AND cve>'{$row['cve']}' AND tipo_venta=1");
			$row1 = mysql_fetch_assoc($res1);
			if($row1['intentos'] >= $intentos) {
				$resultado = array('error' => 1, 'mensaje' => 'La placa ya se acabo sus intentos');
			}
			else{
				$res1 = mysql_query("SELECT b.engomado FROM cobro_engomado a LEFT JOIN certificados b ON a.plaza = b.plaza AND a.cve = b.ticket AND b.estatus!='C' WHERE a.plaza='{$_POST['cveplaza']}' AND a.placa='{$_POST['placa']}' AND a.estatus!='C' AND a.estatus!='D' ORDER BY a.cve DESC LIMIT 1");
				$row1 = mysql_fetch_assoc($res1);
				if($row1['engomado'] != 19){
					$resultado = array('error' => 1, 'mensaje' => 'El ultimo ticket no tiene constancia de rechazo');
				}
			}			
		}
		else{
			$resultado = array('error' => 1, 'mensaje' => 'La placa debe de tener un pago previo no mayor a 30 dias');
		}
	}
	if($resultado['error']==1){
		echo json_encode($resultado);
	}
	else{
		$aplicadescuento = 0;
		$descuento = 0;
		/*$numero = numeroPlaca($_POST['placa']);
		if($_POST['anio']==2 && $_POST['multa']!=1){
			$numero = numeroPlaca($_POST['placa']);
			if(($numero == 7 || $numero == 8) && intval(date("m"))==4 && intval(date("d")) <= 15) $aplicadescuento=10;
			elseif(($numero == 3 || $numero == 4) && intval(date("m"))==5 && intval(date("d")) <= 15) $aplicadescuento=10;
			elseif(($numero == 1 || $numero == 2) && intval(date("m"))==6 && intval(date("d")) <= 15) $aplicadescuento=10;
			elseif(($numero == 9 || $numero == 0) && intval(date("m"))==7 && intval(date("d")) <= 15) $aplicadescuento=10;
		}*/
		if($aplicadescuento>0){
			$descuento = round($_POST['monto']*$aplicadescuento/100,2);
			$_POST['monto'] -= $descuento;
		}
		$ticketpago = 0;
		if($_POST['tipo_venta']==1){
			$res1 = mysql_query("SELECT cve FROM cobro_engomado WHERE plaza='{$_POST['cveplaza']}' AND placa = '{$_POST['placa']}' AND estatus!='C' AND estatus!='D' AND tipo_venta IN (0,2,4) ORDER BY cve DESC LIMIT 1");
			$row1 = mysql_fetch_array($res1);
			$ticketpago = $row1[0];
		}

		$anio = mysql_fetch_assoc(mysql_query("SELECT cve FROM anios_certificados WHERE venta=1 ORDER BY cve DESC LIMIT 1"));

		$row1 = mysql_fetch_assoc(mysql_query("SELECT precio FROM engomados_plazas WHERE plaza='{$_POST['cveplaza']}' AND engomado='{$_POST['engomado']}'"));
		$row2 = mysql_fetch_assoc(mysql_query("SELECT * FROM costos_copias_impresiones ORDER BY cve DESC"));
		$costo_copias = $row2['copias'];

		$insert = " INSERT cobro_engomado 
								SET 
								plaza = '{$_POST['cveplaza']}', fecha=CURDATE(), hora=CURTIME(), placa='{$_POST['placa']}', engomado='{$_POST['engomado']}', monto='".$_POST['monto']."', tipo_combustible='{$_POST['tipo_combustible']}', tipo_pago='{$_POST['tipo_pago']}', depositante='{$_POST['depositante']}', usuario='{$_POST['cveusuario']}', estatus='A', motivo_intento='{$_POST['motivo_intento']}', obs='".addslashes($_POST['obs'])."', anio='{$anio['cve']}', descuento='$descuento', tipo_venta = '{$_POST['tipo_venta']}', monto_verificacion='{$row1['precio']}', autoriza='".addslashes($_POST['autoriza'])."', tipo_cortesia='{$_POST['tipo_cortesia']}', codigo_cortesia='{$_POST['codigo_cortesia']}', ticketpago='{$ticketpago}', vale_pago_anticipado='{$_POST['vale_pago_anticipado']}', tipo_vale='{$_POST['tipo_vale']}', ticketpagointento='$ticketpagointento', copias='{$_POST['copias']}', costo_especial='{$_POST['costo_especial']}', multa='{$_POST['multa']}', folio_multa='{$_POST['folio_multa']}', certificado_anterior='{$_POST['certificado_anterior']}', voluntario='{$_POST['voluntario']}', entidad='{$_POST['entidad']}',costo_copias='{$costo_copias}', referencia_tarjeta='{$_POST['referencia_tarjeta']}', pago_copias_efectivo='{$_POST['pago_copias_efectivo']}'";
		mysql_query($insert) or die(mysql_error());
		$cvecobro = mysql_insert_id();
		if($_POST['vale_pago_anticipado']!= '' || $_POST['codigo_cortesia'] != '') {
			if ($_POST['tipo_pago'] == 6){
				if($_POST['vale_pago_anticipado'] != ''){
					$vale = $_POST['vale_pago_anticipado'];
					$tipo=0;
				}
				else{
					$vale = $_POST['codigo_cortesia'];
					$tipo=1;
				}
				mysql_query("UPDATE vales_pago_anticipado SET usado=1 WHERE plaza='{$_POST['cveplaza']}' AND cve='{$vale}' AND tipo='{$tipo}'");
			}
			elseif($_POST['tipo_pago']==1 || $_POST['tipo_pago']==5 || $_POST['tipo_pago']==6){
				mysql_query("UPDATE vale_cortesia_acumulado SET usado=1 WHERE plaza={$_POST['cveplaza']} AND folio={$_POST['codigo_cortesia']}");
			}
		}


		if($_POST['tipo_venta'] == 0 && ($_POST['tipo_pago'] == 1 || $_POST['tipo_pago'] == 5 || $_POST['tipo_pago'] == 7 || $_POST['tipo_pago'] == 4)){
			guardaClave($_POST['cveplaza'], $cvecobro);
			if ($_POST['depositante'] > 0) {
				$Depositante = mysql_fetch_assoc(mysql_query("SELECT n_cortesias FROM depositantes WHERE cve={$_POST['depositante']}"));
				if($Depositante['n_cortesias'] > 0) {
					$res1 = mysql_query("SELECT MAX(ticket) FROM vale_cortesia_acumulado WHERE plaza={$_POST['cveplaza']} AND estatus!='C' AND depositante={$_POST['depositante']}");
					$row1 = mysql_fetch_array($res1);
					$res = mysql_query("SELECT COUNT(cve) as pagos, GROUP_CONCAT(cve) as tickets FROM cobro_engomado WHERE plaza={$_POST['cveplaza']} AND estatus='A' AND depositante={$_POST['depositante']} AND fecha>='2023-12-11' AND monto>0 AND tipo_venta=0 AND tipo_pago IN (1,5,7,4) AND cve>'{$row1[0]}'");
					$row = mysql_fetch_assoc($res);
					$cortesias_ganadas = intval($row['pagos']/$Depositante['n_cortesias']);
					if($cortesias_ganadas>0){
						$resF = mysql_query("SELECT IFNULL(MAX(folio)+1,1) as siguiente FROM vale_cortesia_acumulado WHERE plaza={$_POST['cveplaza']}");
						$rowF=mysql_fetch_assoc($resF);
						$folioV = $rowF['siguiente'];
						$foliosV = array();
						for($c=0;$c<$cortesias_ganadas;$c++){
							$insert = "INSERT vale_cortesia_acumulado SET plaza='{$_POST['cveplaza']}', folio='{$folioV}', fecha=CURDATE(),hora=CURTIME(), depositante='{$_POST['depositante']}', ticket='{$cvecobro}', estatus='A', usuario='{$_POST['cveusuario']}', tickets='{$row['tickets']}', engomado='0'";
							while(!$rinsert=mysql_query($insert)){
								$folioV++;
								$insert = "INSERT vale_cortesia_acumulado SET plaza='{$_POST['cveplaza']}', folio='{$folioV}', fecha=CURDATE(),hora=CURTIME(), depositante='{$_POST['depositante']}', ticket='{$cvecobro}', estatus='A', usuario='{$_POST['cveusuario']}', tickets='{$row['tickets']}', engomado='0'";
							}
							$foliosV[] = $folioV;
							$folioV++;
						}
					}
				}
			}
		}


		if ($_POST['tienebrinco']==1){
			$fecha_minima = date( "Y-m-d" , strtotime ( "-7 day" , strtotime(date('Y-m-d')) ));
			$rowBrinco = mysql_fetch_assoc(mysql_query("SELECT a.tipo, b.precio FROM placas_brincos a INNER JOIN tipos_brinco b ON b.cve = a.tipo WHERE a.plaza='{$_POST['cveplaza']}' AND a.placa='{$_POST['placa']}' AND a.fecha>='{$fecha_minima}' ORDER BY a.cve DESC LIMIT 1"));
			$costobrinco = $rowBrinco['precio'];
			$rowfoliobrinco = mysql_fetch_assoc(mysql_query("SELECT IFNULL(MAX(folio)+1,1) as folio FROM brincos WHERE plaza='{$_POST['cveplaza']}'"));
			$foliobrinco=$rowfoliobrinco['folio'];
			$insertbrinco = "INSERT brincos SET plaza='{$_POST['cveplaza']}', usuario='{$_POST['cveusuario']}', tipo_pago='{$_POST['tipopagobrinco']}', monto='{$costobrinco}', estatus='A', folio='{$foliobrinco}', tipo='{$rowBrinco['tipo']}', placa='{$_POST['placa']}', fecha=CURDATE(), hora=CURTIME()";
			while(!$resbrinco = mysql_query($insertbrinco)){
				$foliobrinco++;
				$insertbrinco = "INSERT brincos SET plaza='{$_POST['cveplaza']}', usuario='{$_POST['cveusuario']}', tipo_pago='{$_POST['tipopagobrinco']}', monto='{$costobrinco}', estatus='A', folio='{$foliobrinco}', tipo='{$rowBrinco['tipo']}', placa='{$_POST['placa']}', fecha=CURDATE(), hora=CURTIME()";
			}
		}
		echo '<script>$("#contenedorprincipal").html("");atcr("cobro_engomado.php","",0,"");atcr("cobro_engomado.php","_blank",101,"'.$cvecobro.'");</script>';
	}
}

if($_POST['cmd']==101){
	/*$variables = array(
		'server' => '',
		'printer' => 'impresoratermica',
		'url' => $url_impresion.'/cobro_engomado.php?cmd=101&cveplaza='.$_POST['cveplaza'].'&cveticket='.$_POST['reg'].'&cveusuario='.$_POST['cveusuario'].'&reimpresion='.$_GET['reimpresion']
	);
	$impresion='<iframe src="http://localhost:8020/?'.http_build_query($variables).'" width=200 height=200></iframe>';*/
	function separar_letras($cadena){
		$cadena2 = '';
		for($i=0;$i<strlen($cadena);$i++){
			$cadena2.=' '.$cadena[$i];
		}
		$cadena2 = substr($cadena2, 1);
		return $cadena2;

	}
	require_once('numlet.php');
	$res=mysql_query("SELECT * FROM cobro_engomado WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['reg']}'");
	$row=mysql_fetch_array($res);
	if($row['tipo_pago']==6) $row['monto']=0;
	$barcode = '1'.sprintf("%011s",(intval($row['cve'])));
	$Usuario = mysql_fetch_assoc(mysql_query("SELECT usuario FROM usuarios WHERE cve='{$_POST['cveusuario']}'"));
	$Anio = mysql_fetch_assoc(mysql_query("SELECT nombre FROM anios_certificados WHERE cve='{$row['anio']}'"));
	$Engomado = mysql_fetch_assoc(mysql_query("SELECT nombre FROM engomados WHERE cve='{$row['engomado']}'"));
	$TipoVenta = mysql_fetch_assoc(mysql_query("SELECT nombre FROM tipo_venta WHERE cve='{$row['tipo_venta']}'"));
	$TipoPago = mysql_fetch_assoc(mysql_query("SELECT nombre FROM tipos_pago WHERE cve='{$row['tipo_pago']}'"));
	$Depositante = mysql_fetch_assoc(mysql_query("SELECT nombre FROM depositantes WHERE cve='{$row['depositante']}'"));
	$TipoCombustible = mysql_fetch_assoc(mysql_query("SELECT nombre FROM tipo_combustible WHERE cve='{$row['tipo_combustible']}'"));
	
	$texto=chr(27)."@";
	$texto.=chr(10).chr(13);
	/*if(file_exists('img/logo.TMB')){
		$texto.=chr(27).'a'.chr(1);
		$texto.=file_get_contents('img/logo.TMB');
		$texto.=chr(10).chr(13);
		$texto.=chr(27).'a0';
	}*/
	if($row['tipo_venta']==1){
		$texto.='USTED POR ESTE TICKS NO PAGO||Y  NO SE PODRA FACTURAR||SI LE COBRARON FAVOR DE REPORTAR||AL GERENTE DEL CENTRO|';
	}
	$resPlaza = mysql_query("SELECT numero,nombre,bloqueada_sat,domicilioticket FROM plazas WHERE cve='{$row['plaza']}'");
	$rowPlaza = mysql_fetch_array($resPlaza);
	$resPlaza2 = mysql_query("SELECT rfc FROM datosempresas WHERE plaza='{$row['plaza']}'");
	$rowPlaza2 = mysql_fetch_array($resPlaza2);
	$texto.=chr(27).'!'.chr(30)." {$rowPlaza['numero']}|{$rowPlaza['nombre']}";
	$texto.='| RFC: '.$rowPlaza2['rfc'];
	$texto.='|'.$rowPlaza['domicilioticket'];
	$texto.='||';
	if($_GET['reimpresion'] == 1){
		$texto.="     REIMPRESION ||";
		//$row['monto'] = 0;
	}

	//$texto.=chr(27).'!'.chr(8)." ORIGINAL CLIENTE";
	//$texto.='||';
	$texto.=chr(27).'!'.chr(8)." TICKET: ".sprintf("%05s", $row['cve']);
	$texto.='|';
	if($row['tipo_pago'] != 2 && $row['tipo_pago'] != 6 && $row['tipo_pago'] != 12 && $rowPlaza['bloqueada_sat'] != 1){
		$res1=mysql_query("SELECT * FROM claves_facturacion WHERE plaza='{$row['plaza']}' AND ticket='{$row['cve']}'");
		if($row1=mysql_fetch_array($res1)){
			$texto.=chr(27).'!'.chr(8)."     CLAVE FACTURACION:||".$row1['cve'];
			$texto.='|||';
			$fecha_limite = date( "Y-m-t" , strtotime ( "+1 day" , strtotime(substr($row['fecha'],0, 8).'05') ) );
			$texto.=chr(27).'!'.chr(8)."     FECHA LIMITE FACTURACION:|    ".$fecha_limite." 21:00";
			$texto.='||';
			//if($row['plaza']!=59 && $row['plaza']!=1 && $row['plaza']!=15) 
				$texto.=chr(27).'!'.chr(8)."     PAGINA PARA FACTURAR:|    {$url_impresion}/facturacion|";
		}
	}
	$texto.='|';
	$texto.=chr(27).'!'.chr(8)." VENTA DE CERTIFICADO";
	$texto.='|';
	$texto.=chr(27).'!'.chr(8)." FECHA: ".$row['fecha']."   ".$row['hora'].'|';
	$texto.=chr(27).'!'.chr(8)." FEC.IMP.: ".date('Y-m-d H:i:s').'|';
	$texto.=chr(27).'!'.chr(8)." USUARIO: ".$Usuario['usuario'].'|';
	$texto.=chr(27).'!'.chr(40)."PLACA: ".separar_letras($row['placa']);
	$texto.='|';
	$texto.=chr(27).'!'.chr(8)." A. CERTIFICADO: ".$Anio['nombre'];
	$texto.='|';
	$texto.=chr(27).'!'.chr(8)." T. CERTIFICADO: ".$Engomado['nombre'];
	$texto.='|';
	//$texto.=chr(27).'!'.chr(8)." MODELO: ".$row['modelo'];
	//$texto.='|';
	$texto.=chr(27).'!'.chr(8)." TIPO VENTA: ".$TipoVenta['nombre'].'|';
	//if($row['tipo_venta']==1) $texto.=chr(27).'!'.chr(8)." NUM INTENTO: ".$row['num_intento'].'|';
	if($row['tipo_venta']==1) $texto.=chr(27).'!'.chr(8)."ESTA PLACA CUENTA CON ".$row['num_intento']." CANTIDAD DE INTENTOS SIN COBRO, SOLO IMPORTE DE COPIAS, FAVOR DE REPORTAR AL PERSONAL QUE SOLICITE COBRO ALGUNO|";
	$texto.=chr(27).'!'.chr(8)." TIPO PAGO: ".$TipoPago['nombre'];
	$texto.='|';
	if($row['tipo_pago'] == 2 || $row['tipo_pago'] == 6 || $row['depositante']>0){
		$texto.=chr(27).'!'.chr(8)." DEPOSITANTE: ".$Depositante['nombre'];
		$texto.='|';
		if($row['tipo_pago']==6 && $row['vale_pago_anticipado']>0){
			$texto.=chr(27).'!'.chr(8)." VALE: ".$row['vale_pago_anticipado'];
			$texto.='|';
		}
		elseif($row['tipo_pago']==6 && $row['codigo_cortesia']!=''){
			$texto.=chr(27).'!'.chr(8)." VALE: ".$row['codigo_cortesia'];
			$texto.='|';
		}
	}
	$texto.=chr(27).'!'.chr(8)." TIPO COMBUSTIBLE ".$TipoCombustible['nombre'];
	if($row['descuento'] > 0){
		$texto.='|';
		$texto.=chr(27).'!'.chr(8)." DESCUENTO PROMOCION ";
	}
	$texto.='|';
	$texto.=chr(27).'!'.chr(8)." MONTO: ".$row['monto'];
	$texto.='|';
	$texto.=chr(27).'!'.chr(8)." COPIAS: ".$row['copias'];
	$texto.='|';
	$texto.=chr(27).'!'.chr(8)." TOTAL: ".($row['copias']*$row['costo_copias']+$row['monto']);
	$texto.='|';
	$texto.=chr(27).'!'.chr(8)." ".numlet(($row['copias']*$row['costo_copias']+$row['monto']));
	$texto.='|';

	$texto.='|SI EL IMPORTE COBRADO ES DIFERENTE AL DEL TICKET FAVOR DE REPORTARLO|';
	
	if($row['tipo_venta'] == 2){
		$texto.='|___________________|'.$row['autoriza'].'|Autoriza|';
	}
	if($row['tipo_venta'] == 0){
		$texto.='|AL PAGAR EL SERVICIO SE INFORMA QUE NO EXISTE DEVOLUCION POR CAUSAS NO IMPUTABLES AL CENTRO|||______________________|FIRMA DEL PROPIETARIO|PLACA '.$row['placa'].'|';
	}

	if($row['tipo_venta'] == 11){
		$MotivoIntento = mysql_fetch_assoc(mysql_query("SELECT nombre FROM motivos_intento WHERE cve='{$row['motivo_intento']}'"));
		$texto.='|'.chr(27).'!'.chr(8)." MOTIVO INTENTO:|".$MotivoIntento['nombre'];
		$texto.='|';
		$texto.=chr(27).'!'.chr(8)." OBSERVACIONES:|".$row['obs'];
		$texto.='|';
		$res2=mysql_query("SELECT * FROM cobro_engomado WHERE plaza='{$_POST['cveplaza']}' AND placa='{$row['placa']}' AND monto>0 ORDER BY cve DESC LIMIT 1");
		$row2 = mysql_fetch_array($res2);
		$Engomado2 = mysql_fetch_assoc(mysql_query("SELECT nombre FROM engomados WHERE cve='{$row2['engomado']}'"));
		$TipoPago2 = mysql_fetch_assoc(mysql_query("SELECT nombre FROM tipos_pago WHERE cve='{$row2['tipo_pago']}'"));
		$TipoCombustible2 = mysql_fetch_assoc(mysql_query("SELECT nombre FROM tipo_combustible WHERE cve='{$row2['tipo_combustible']}'"));
		$texto.=chr(27).'!'.chr(8)." TICKET PAGADO: ".$row2['cve'];
		$texto.='|';
		$texto.=chr(27).'!'.chr(8)." FECHA: ".$row2['fecha']."   ".$row2['hora'].'|';
		$texto.='|';
		$texto.=chr(27).'!'.chr(8)." T. CERTIFICADO: ".$Engomado2['nombre'];
		$texto.='|';
		$texto.=chr(27).'!'.chr(8)." TIPO PAGO ".$TipoPago2['nombre'];
		$texto.='|';
		$texto.=chr(27).'!'.chr(8)." TIPO COMBUSTIBLE ".$TipoCombustible2['nombre'];
		$texto.='|';
		$texto.=chr(27).'!'.chr(8)." MONTO: ".$row2['monto'];
		$texto.='|';
		$texto.=chr(27).'!'.chr(8)." ".numlet($row2['monto']);
		$texto.='|';
	}
	if($row['tipo_venta']==1){
		$texto.='USTED POR ESTE TICKS NO PAGO|Y  NO SE PODRA FACTURAR||SI LE COBRARON FAVOR DE REPORTAR|AL GERENTE DEL CENTRO|';
	}

	if($_GET['reimpresion']==1){
		$texto.='|ESTA ES UNA REIMPRESION, POR SEGURIDAD LOS IMPORTES SALEN EN CERO|';
	}

	if($row['tipo_venta']==0 && $row['depositante'] > 0 && ($row['tipo_pago']==1 || $row['tipo_pago']==5 || $row['tipo_pago']==7)){
		$res1 = mysql_query("SELECT cve FROM vale_cortesia_acumulado WHERE plaza='{$_POST['cveplaza']}' AND ticket='{$_POST['reg']}' AND estatus!='C'");
		$primero=true;
		$foliosv = '';
		while($row1 = mysql_fetch_array($res1)){
			if($primero){
				$foliosv = $row1['folio'];
			}
			else{
				$foliosv .= ','.$row1['folio'];
			}
			$primero = false;
		}
		$plazav=$array_plaza[$row['plaza']];
		$nplazav=$rowPlaza['nombre'];
		$fechav = $row1['fecha'];
		$depositantev = $row['nomdepositante'];
	}
	
	$impresion='<iframe src="http://localhost/impresiongenerallogo.php?textoimp='.$texto.'&logo='.str_replace(' ','',$array_plaza[$row['plaza']]).'&barcod=1'.sprintf("%011s",(intval($row['cve']))).'&copia=1" width=200 height=200></iframe>';
	echo '<html><body>'.$impresion.'</body></html>';
	echo '<script>setTimeout("window.close()",5000);</script>';
}

if($_POST['cmd']==103){
	$_POST['fecha_ini'] = $_POST['busquedafechaini'];
	$_POST['fecha_fin'] = $_POST['busquedafechafin'];
	$_POST['usuario'] = $_POST['busquedausuario'];
	$_POST['plazausuario'] = $_POST['cveplaza'];
	$resPlaza = mysql_query("SELECT numero, nombre FROM plazas WHERE cve={$_POST['plazausuario']}");
	$rowPlaza = mysql_fetch_array($resPlaza);
	$Usuario = mysql_fetch_assoc(mysql_query("SELECT usuario FROM usuarios WHERE cve='{$_POST['usuario']}'"));
	echo '<h2>'.$rowPlaza['numero'].'<br>'.$rowPlaza['nombre'].'<br>CORTE VENTA CERTIFICADO<br>'.date('Y-m-d H:i:s');
	if($_POST['fecha_ini']==$_POST['fecha_fin']) echo "<br>FECHA: ".$_POST['fecha_ini'];
	else echo "<br>FECHA INICIO: ".$_POST['fecha_ini']."<br>FECHA FIN: ".$_POST['fecha_fin'];
	if ($_POST['usuario']!=""){ 
		$filtro.=" AND a.usuario='".$_POST['usuario']."' "; 
		echo '<br>USUARIO: '.$Usuario['usuario'];
	}
	else{
		echo '<br>USUARIO: TODOS';
	}
	echo '</h2>';
	$array_resultado = array();
	$res = mysql_query("SELECT engomado, tipo_pago, tipo_venta, costo_especial, pago_copias_efectivo, SUM(IF(estatus!='C',copias,0)) as copias, SUM(IF(estatus!='C',copias*costo_copias,0)) as montocopias, SUM(IF(estatus!='C',monto,0)) as monto, SUM(IF(estatus!='C',1,0)) as reg FROM cobro_engomado a WHERE plaza={$_POST['plazausuario']} AND fecha BETWEEN '{$_POST['fecha_ini']}' AND '{$_POST['fecha_fin']}'{$filtro} GROUP BY engomado, tipo_venta, tipo_pago, costo_especial, pago_copias_efectivo");
	while($row = mysql_fetch_assoc($res)){
		if($row['tipo_venta']==0 && $row['costo_especial'] != 1 && ($row['engomado']==20 || $row['engomado']==24) && $row['tipo_pago'] == 1){
			$array_resultado['CONTADO'][0][0]+=$row['reg'];
			$array_resultado['CONTADO'][0][1]+=$row['monto'];
			$array_resultado['CONTADO'][9][0]+=$row['copias'];
			$array_resultado['CONTADO'][9][1]+=$row['montocopias'];
		}
		elseif($row['tipo_venta']==0 && $row['costo_especial'] == 1 && ($row['engomado']==20 || $row['engomado']==24) && $row['tipo_pago'] == 1){
			$array_resultado['CONTADO'][3][0]+=$row['reg'];
			$array_resultado['CONTADO'][3][1]+=$row['monto'];
			$array_resultado['CONTADO'][9][0]+=$row['copias'];
			$array_resultado['CONTADO'][9][1]+=$row['montocopias'];
		}
		elseif($row['tipo_venta']==3 && $row['costo_especial'] != 1 && $row['tipo_pago'] == 1){
			$array_resultado['CONTADO'][4][0]+=$row['reg'];
			$array_resultado['CONTADO'][4][1]+=$row['monto'];
			$array_resultado['CONTADO'][9][0]+=$row['copias'];
			$array_resultado['CONTADO'][9][1]+=$row['montocopias'];
		}
		if($row['tipo_venta']==0 && $row['tipo_pago'] == 1 && $row['engomado']==22){
			$array_resultado['CONTADO'][5][0]+=$row['reg'];
			$array_resultado['CONTADO'][5][1]+=$row['monto'];
			$array_resultado['CONTADO'][9][0]+=$row['copias'];
			$array_resultado['CONTADO'][9][1]+=$row['montocopias'];
		}
		elseif($row['tipo_venta']==0  && $row['tipo_pago'] == 1 && $row['engomado']==200){
			$array_resultado['CONTADO'][6][0]+=$row['reg'];
			$array_resultado['CONTADO'][6][1]+=$row['monto'];
			$array_resultado['CONTADO'][9][0]+=$row['copias'];
			$array_resultado['CONTADO'][9][1]+=$row['montocopias'];
		}
		elseif($row['tipo_venta']==0  && $row['tipo_pago'] == 6){
			$array_resultado['CONTADO'][2][0]+=$row['reg'];
			$array_resultado['CONTADO'][2][1]+=0;
			$array_resultado['CONTADO'][9][0]+=$row['copias'];
			$array_resultado['CONTADO'][9][1]+=$row['montocopias'];
		}
		elseif($row['tipo_venta']==0  && $row['tipo_pago'] == 12){
			$array_resultado['CREDITO'][4][0]+=$row['reg'];
			$array_resultado['CREDITO'][4][1]+=$row['monto'];
			$array_resultado['CONTADO'][9][0]+=$row['copias'];
			$array_resultado['CONTADO'][9][1]+=$row['montocopias'];
		}
		elseif($row['tipo_venta']==0  && $row['tipo_pago'] == 13){
			//$array_resultado['CONTADO'][2][0]+=$row['reg'];
			//$array_resultado['CONTADO'][2][1]+=0;
			$array_resultado['CONTADO'][9][0]+=$row['copias'];
			$array_resultado['CONTADO'][9][1]+=$row['montocopias'];
		}
		elseif($row['tipo_venta']==1){
			$array_resultado['CONTADO'][7][0]+=$row['reg'];
			$array_resultado['CONTADO'][7][1]+=$row['monto'];
			$array_resultado['CONTADO'][9][0]+=$row['copias'];
			$array_resultado['CONTADO'][9][1]+=$row['montocopias'];
		}
		elseif($row['tipo_venta']==2){
			$array_resultado['CONTADO'][8][0]+=$row['reg'];
			$array_resultado['CONTADO'][8][1]+=$row['monto'];
			$array_resultado['CONTADO'][9][0]+=$row['copias'];
			$array_resultado['CONTADO'][9][1]+=$row['montocopias'];
		}
		elseif($row['tipo_venta']==4){
			$array_resultado['CONTADO'][14][0]+=$row['reg'];
			$array_resultado['CONTADO'][14][1]+=$row['monto'];
			$array_resultado['CONTADO'][9][0]+=$row['copias'];
			$array_resultado['CONTADO'][9][1]+=$row['montocopias'];
		}
		elseif(($row['tipo_pago'] == 5 || $row['tipo_pago']==7) && $row['engomado'] == 20 && $row['costo_especial'] != 1 && $row['tipo_venta'] == 0){
			$array_resultado['TARJETA DE CREDITO/DEBITO'][0][0]+=$row['reg'];
			$array_resultado['TARJETA DE CREDITO/DEBITO'][0][1]+=$row['monto'];
			if($row['pago_copias_efectivo']==1){
				$array_resultado['CONTADO'][9][0]+=$row['copias'];
				$array_resultado['CONTADO'][9][1]+=$row['montocopias'];
			}
			else{
				$array_resultado['TARJETA DE CREDITO/DEBITO'][6][0]+=$row['copias'];
				$array_resultado['TARJETA DE CREDITO/DEBITO'][6][1]+=$row['montocopias'];
			}
		}
		elseif(($row['tipo_pago'] == 5 || $row['tipo_pago']==7) && $row['costo_especial'] == 1 && $row['tipo_venta'] == 0){
			$array_resultado['TARJETA DE CREDITO/DEBITO'][2][0]+=$row['reg'];
			$array_resultado['TARJETA DE CREDITO/DEBITO'][2][1]+=$row['monto'];
			if($row['pago_copias_efectivo']==1){
				$array_resultado['CONTADO'][9][0]+=$row['copias'];
				$array_resultado['CONTADO'][9][1]+=$row['montocopias'];
			}
			else{
				$array_resultado['TARJETA DE CREDITO/DEBITO'][6][0]+=$row['copias'];
				$array_resultado['TARJETA DE CREDITO/DEBITO'][6][1]+=$row['montocopias'];
			}
		}
		elseif(($row['tipo_pago'] == 5 || $row['tipo_pago']==7) && $row['tipo_venta'] == 3){
			$array_resultado['TARJETA DE CREDITO/DEBITO'][3][0]+=$row['reg'];
			$array_resultado['TARJETA DE CREDITO/DEBITO'][3][1]+=$row['monto'];
			if($row['pago_copias_efectivo']==1){
				$array_resultado['CONTADO'][9][0]+=$row['copias'];
				$array_resultado['CONTADO'][9][1]+=$row['montocopias'];
			}
			else{
				$array_resultado['TARJETA DE CREDITO/DEBITO'][6][0]+=$row['copias'];
				$array_resultado['TARJETA DE CREDITO/DEBITO'][6][1]+=$row['montocopias'];
			}
		}
		elseif(($row['tipo_pago'] == 5 || $row['tipo_pago']==7) && $row['tipo_venta'] == 0 && $row['engomado']==22){
			$array_resultado['TARJETA DE CREDITO/DEBITO'][4][0]+=$row['reg'];
			$array_resultado['TARJETA DE CREDITO/DEBITO'][4][1]+=$row['monto'];
			if($row['pago_copias_efectivo']==1){
				$array_resultado['CONTADO'][9][0]+=$row['copias'];
				$array_resultado['CONTADO'][9][1]+=$row['montocopias'];
			}
			else{
				$array_resultado['TARJETA DE CREDITO/DEBITO'][6][0]+=$row['copias'];
				$array_resultado['TARJETA DE CREDITO/DEBITO'][6][1]+=$row['montocopias'];
			}
		}
		elseif(($row['tipo_pago'] == 5 || $row['tipo_pago']==7) && $row['tipo_venta'] == 0 && $row['engomado']==200){
			$array_resultado['TARJETA DE CREDITO/DEBITO'][5][0]+=$row['reg'];
			$array_resultado['TARJETA DE CREDITO/DEBITO'][5][1]+=$row['monto'];
			if($row['pago_copias_efectivo']==1){
				$array_resultado['CONTADO'][9][0]+=$row['copias'];
				$array_resultado['CONTADO'][9][1]+=$row['montocopias'];
			}
			else{
				$array_resultado['TARJETA DE CREDITO/DEBITO'][6][0]+=$row['copias'];
				$array_resultado['TARJETA DE CREDITO/DEBITO'][6][1]+=$row['montocopias'];
			}
		}
		elseif($row['tipo_pago']==2 && $row['tipo_venta']==0 && $row['engomado']==20){
			$array_resultado['CREDITO'][0][0]+=$row['reg'];
			$array_resultado['CREDITO'][0][1]+=$row['monto'];
			$array_resultado['CONTADO'][9][0]+=$row['copias'];
			$array_resultado['CONTADO'][9][1]+=$row['montocopias'];
		}
		elseif($row['tipo_pago']==2 && $row['tipo_venta']==0 && $row['engomado']==22){
			$array_resultado['CREDITO'][1][0]+=$row['reg'];
			$array_resultado['CREDITO'][1][1]+=$row['monto'];
			$array_resultado['CONTADO'][9][0]+=$row['copias'];
			$array_resultado['CONTADO'][9][1]+=$row['montocopias'];
		}
		elseif($row['tipo_pago']==2 && $row['tipo_venta']==0 && $row['engomado']==200){
			$array_resultado['CREDITO'][3][0]+=$row['reg'];
			$array_resultado['CREDITO'][3][1]+=$row['monto'];
			$array_resultado['CONTADO'][9][0]+=$row['copias'];
			$array_resultado['CONTADO'][9][1]+=$row['montocopias'];
		}
	}
	$res = mysql_query("SELECT a.tipo_pago, a.forma_pago, 1 as tipo_verificacion, SUM(IF(a.tipo_pago!=2,IFNULL(b.cantidad,0),1)) as reg, SUM(a.monto) as monto  FROM pagos_caja a LEFT JOIN (SELECT pago, COUNT(cve) as cantidad FROM vales_pago_anticipado WHERE plaza={$_POST['plazausuario']} AND tipo=0 AND fecha BETWEEN '{$_POST['fecha_ini']}' AND '{$_POST['fecha_fin']}' GROUP BY pago) b ON a.cve = b.pago WHERE a.plaza={$_POST['plazausuario']} AND a.fecha BETWEEN '{$_POST['fecha_ini']}' AND '{$_POST['fecha_fin']}' AND a.estatus!='C' AND a.tipo_pago IN (2,6,12) {$filtro} GROUP BY a.tipo_pago, a.forma_pago");
	while($row = mysql_fetch_assoc($res)){
		if($row['tipo_pago']==6){
			if($row['forma_pago']==1){
				$array_resultado['CONTADO'][1][0]+=$row['reg'];
				$array_resultado['CONTADO'][1][1]+=$row['monto'];
			}
			elseif($row['forma_pago']==5){
				$array_resultado['TARJETA DE CREDITO/DEBITO'][1][0]+=$row['reg'];
				$array_resultado['TARJETA DE CREDITO/DEBITO'][1][1]+=$row['monto'];
			}
			elseif($row['tipo_verificacion']==22){
				$array_resultado['TRANSFERENCIAS/CHEQUE'][1][0]+=$row['reg'];
				$array_resultado['TRANSFERENCIAS/CHEQUE'][1][1]+=$row['monto'];
			}
			elseif($row['tipo_verificacion']==20){
				$array_resultado['TRANSFERENCIAS/CHEQUE'][2][0]+=$row['reg'];
				$array_resultado['TRANSFERENCIAS/CHEQUE'][2][1]+=$row['monto'];
			}
			else{
				$array_resultado['TRANSFERENCIAS/CHEQUE'][0][0]+=$row['reg'];
				$array_resultado['TRANSFERENCIAS/CHEQUE'][0][1]+=$row['monto'];
			}
		}
		elseif($row['tipo_pago']==2){
			if($row['forma_pago']==1){
				$array_resultado['CONTADO'][11][0]+=$row['reg'];
				$array_resultado['CONTADO'][11][1]+=$row['monto'];
			}
			elseif($row['forma_pago']==5){
				$array_resultado['TARJETA DE CREDITO/DEBITO'][7][0]+=$row['reg'];
				$array_resultado['TARJETA DE CREDITO/DEBITO'][7][1]+=$row['monto'];
			}
			elseif($row['tipo_verificacion']==22){
				$array_resultado['TRANSFERENCIAS/CHEQUE'][1][0]+=$row['reg'];
				$array_resultado['TRANSFERENCIAS/CHEQUE'][1][1]+=$row['monto'];
			}
			elseif($row['tipo_verificacion']==20){
				$array_resultado['TRANSFERENCIAS/CHEQUE'][2][0]+=$row['reg'];
				$array_resultado['TRANSFERENCIAS/CHEQUE'][2][1]+=$row['monto'];
			}
			else{
				$array_resultado['TRANSFERENCIAS/CHEQUE'][3][0]+=$row['reg'];
				$array_resultado['TRANSFERENCIAS/CHEQUE'][3][1]+=$row['monto'];
			}
		}
		else{
			if($row['forma_pago']==1){
				$array_resultado['CONTADO'][13][0]+=$row['reg'];
				$array_resultado['CONTADO'][13][1]+=$row['monto'];
			}
			elseif($row['forma_pago']==5){
				$array_resultado['TARJETA DE CREDITO/DEBITO'][9][0]+=$row['reg'];
				$array_resultado['TARJETA DE CREDITO/DEBITO'][9][1]+=$row['monto'];
			}
			else{
				$array_resultado['TRANSFERENCIAS/CHEQUE'][5][0]+=$row['reg'];
				$array_resultado['TRANSFERENCIAS/CHEQUE'][5][1]+=$row['monto'];
			}	
		}
	}
	$res = mysql_query("SELECT 1 as forma_pago, SUM(a.cantidad) as reg, SUM(a.cantidad*a.costo_uni) as monto FROM copias a WHERE a.plaza={$_POST['plazausuario']} AND a.fecha BETWEEN '{$_POST['fecha_ini']}' AND '{$_POST['fecha_fin']}' AND a.estatus!='C' {$filtro}");
	while($row = mysql_fetch_assoc($res)){
		if ($row['forma_pago'] == 1) {
			$array_resultado['CONTADO'][10][0]+=$row['reg'];
			$array_resultado['CONTADO'][10][1]+=$row['monto'];
		}
		elseif($row['forma_pago']==5) {
			$array_resultado['TARJETA DE CREDITO/DEBITO'][8][0]+=$row['reg'];
			$array_resultado['TARJETA DE CREDITO/DEBITO'][8][1]+=$row['monto'];
		}
		else{
			$array_resultado['TRANSFERENCIAS/CHEQUE'][4][0]+=$row['reg'];
			$array_resultado['TRANSFERENCIAS/CHEQUE'][4][1]+=$row['monto'];
		}
	}
	$array_conceptos=array(
		'CONTADO' => array(
			0=>'EFECTIVO',
			1=>'P/A COBRADOS',
			11=>'RECUPERACION DE CREDITOS',
			13=>'RECUPERACION V/E',
			2=>'P/A VERIFICADOS',
			3=>'MEDIOS PAGOS',
			4=>'REPOSICIONES',
			5=>'PLATAFORMA',
			6=>'CONCESION',
			7=>'INTENTO',
			8=>'CORTESIA NORMAL',
			14=>'CORTESIA AUTORIZADA',
			9=>'COPIAS',
			10=>'COPIAS PAGO ANTICIPADO'
		),
		'TARJETA DE CREDITO/DEBITO' => array(
			0=>'VERIFICACION',
			1=>'PAGOS ANTICIPADOS',
			7=>'RECUPERACION DE CREDITOS',
			9=>'RECUPERACION V/E',
			2=>'MEDIOS PAGOS',
			3=>'REPOSICIONES',
			4=>'PLATAFORMA',
			5=>'CONCESION',
			6=>'COPIAS',
			8=>'COPIAS PAGO ANTICIPADO'
		),
		'TRANSFERENCIAS/CHEQUE' => array(
			0=>'PAGOS ANTICIPADOS',
			3=>'RECUPERACION DE CREDITOS',
			5=>'RECUPERACION V/E',
			1=>'PLATAFORMA',
			2=>'CONCESION',
			4=>'COPIAS PAGO ANTICIPADO'
		),
		'CREDITO' => array(
			'VERIFICACION',
			'PLATAFORMA',
			'CONCESION',
			4=>'V/E VERIFICADOS'
		)
	);
	echo '<table>';
	$conceptos_a_depositar = array(0,1,3,4,5,6,9,10,11,13);
	$gtotales = 0;
	$adepositar = 0;
	foreach($array_conceptos as $concepto => $detalles){
		echo '<tr><th colspan="3" align="left">'.$concepto.'</th></tr>';
		$totales=array(0,0);
		foreach($detalles as $indice => $detalle){
			echo '<tr>';
			echo '<td>'.$detalle.'</td>';
			echo '<td align="right">'.number_format($array_resultado[$concepto][$indice][0],0).'</td>';
			echo '<td align="right">'.number_format($array_resultado[$concepto][$indice][1],2).'</td>';
			echo '</tr>';
			$totales[0]+=round($array_resultado[$concepto][$indice][0],0);
			//if (!($concepto =='CREDITO' && $indice==4))
				$totales[1]+=round($array_resultado[$concepto][$indice][1],2);
			if($concepto=='CONTADO' && in_array($indice, $conceptos_a_depositar)){
				$adepositar+=round($array_resultado[$concepto][$indice][1],2);
			}
		}
		echo '<tr>';
		echo '<td>TOTAL</td>';
		echo '<td align="right">'.number_format($totales[0],0).'</td>';
		echo '<td align="right">'.number_format($totales[1],2).'</td>';
		echo '</tr>';
		$gtotales += $totales[1];
	}
	echo '<tr><th colspan="3" align="left">GASTOS</th></tr>';
	$devolucion=mysql_fetch_assoc(mysql_query("SELECT count(a.cve) as cant, sum(a.devolucion) as imp from devolucion_certificado a WHERE a.fecha BETWEEN '{$_POST['fecha_ini']}' AND '{$_POST['fecha_fin']}' AND a.estatus!='C'{$filtro}"));
	echo '<tr><td>DEVOLUCIONES</td><td align="right">'.number_format($devolucion['cant'],0).'</td><td align="right">'.number_format($devolucion['imp'],2).'</td></tr>';
	$gtotales-=$devolucion['imp'];
	$adepositar-=$devolucion['imp'];
	$res = mysql_query("SELECT b.cve, b.nombre, COUNT(a.cve) as reg, SUM(a.monto) as monto FROM recibos_salida a INNER JOIN motivos b ON b.cve = a.motivo WHERE a.plaza={$_POST['plazausuario']} AND a.fecha_aplicacion BETWEEN '{$_POST['fecha_ini']}' AND '{$_POST['fecha_fin']}' AND a.estatus!='C' {$filtro} GROUP BY b.cve");
	$totales=array(0,0);
	while($row = mysql_fetch_array($res)){
		echo '<tr>';
		echo '<td>'.$row['nombre'].'</td>';
		echo '<td align="right">'.number_format($row['reg'],0).'</td>';
		echo '<td align="right">'.number_format($row['monto'],2).'</td>';
		echo '</tr>';
		$totales[0]+=round($row['reg'],0);
		$totales[1]+=round($row['monto'],2);
	}
	$gtotales -= $totales[1];
	$adepositar -= $totales[1];
	echo '<tr>';
	echo '<td>TOTAL</td>';
	echo '<td align="right">'.number_format($totales[0],0).'</td>';
	echo '<td align="right">'.number_format($totales[1],2).'</td>';
	echo '</tr>';
	echo '<tr><td colspan="3">&nbsp;</td></tr>';
	echo '<tr>';
	echo '<td>GRAN TOTAL</td>';
	echo '<td align="right">&nbsp;</td>';
	echo '<td align="right">'.number_format($gtotales,2).'</td>';
	echo '</tr>';
	echo '<tr>';
	echo '<td>TOTAL A DEPOSITAR</td>';
	echo '<td align="right">&nbsp;</td>';
	echo '<td align="right">'.number_format($adepositar,2).'</td>';
	echo '</tr>';
	echo '</table>';
	exit();

}

if($_POST['cmd']==103.1){
	$_POST['fecha_ini'] = $_POST['busquedafechaini'];
	$_POST['fecha_fin'] = $_POST['busquedafechafin'];
	$_POST['usuario'] = $_POST['busquedausuario'];
	$array_forma_pago = array(1=>"Efectivo",2=>"Deposito Bancario",3=>"Cheque",4=>"Transferencia",5=>'Tarjeta Bancaria');
	$resPlaza = mysql_query("SELECT numero, nombre FROM plazas WHERE cve='{$_POST['cveplaza']}'");
	$rowPlaza = mysql_fetch_array($resPlaza);
	$Usuario = mysql_fetch_assoc(mysql_query("SELECT usuario FROM usuarios WHERE cve='{$_POST['usuario']}'"));
	echo '<h2>'.$rowPlaza['numero'].'<br>'.$rowPlaza['nombre'].'<br>CORTE VENTA CERTIFICADO<br>'.date('Y-m-d H:i:s');
	if($_POST['fecha_ini']==$_POST['fecha_fin']) echo "<br>FECHA: ".$_POST['fecha_ini'];
	else echo "<br>FECHA INICIO: ".$_POST['fecha_ini']."<br>FECHA FIN: ".$_POST['fecha_fin'];
	if ($_POST['usuario']!=""){ 
		$filtro.=" AND a.usuario='{$_POST['usuario']}' "; 
		echo '<br>USUARIO: '.$Usuario['usuario'];
	}
	$texto.='<br><br>';
	$t1=$t2=$t3=$t4=$t5=$t6=$t7=$t8=$t9=$t10=$t11=0;
	
	$res=mysql_query("SELECT engomado,
		SUM(IF(tipo_pago!=6 AND tipo_pago!=12,monto,0)),
		COUNT(cve),
		SUM(IF(tipo_pago IN (2),monto,0)),
		SUM(IF(tipo_pago IN (2),1,0)),
		SUM(IF(tipo_pago IN (5),monto,0)),
		SUM(IF(tipo_pago IN (5),1,0)),
		SUM(IF(tipo_pago IN (7),monto,0)),
		SUM(IF(tipo_pago IN (7),1,0)),
		SUM(IF(tipo_pago IN (12),1,0)),
		SUM(IF(tipo_pago IN (4),monto,0)),
		SUM(IF(tipo_pago IN (4),1,0))   
	FROM cobro_engomado a WHERE plaza='{$_POST['cveplaza']}' AND tipo_venta!=4 AND fecha BETWEEN '{$_POST['fecha_ini']}' AND '{$_POST['fecha_fin']}' AND estatus!='C' {$filtro} AND tipo_venta!=3 GROUP BY engomado");
	while($row=mysql_fetch_array($res)){
		$t1+=$row[2];
		$t2+=$row[1];
		$t3+=$row[4];
		$t4+=$row[3];
		$t5+=$row[6];
		$t6+=$row[5];
		$t7+=$row[8];
		$t8+=$row[7];
		$t9+=$row[9];
		$t10+=$row[10];
		$t11+=$row[11];
	}
	$res=mysql_query("SELECT engomado,
		SUM(IF(tipo_pago!=6 AND tipo_pago!=12,monto,0)),
		COUNT(cve),
		SUM(IF(tipo_pago IN (2),monto,0)),
		SUM(IF(tipo_pago IN (2),1,0)),
		SUM(IF(tipo_pago IN (5),monto,0)),
		SUM(IF(tipo_pago IN (5),1,0)),
		SUM(IF(tipo_pago IN (7),monto,0)),
		SUM(IF(tipo_pago IN (7),1,0)),
		SUM(IF(tipo_pago IN (12),1,0)),
		SUM(IF(tipo_pago IN (4),monto,0)),
		SUM(IF(tipo_pago IN (4),1,0))     
	FROM cobro_engomado a WHERE plaza='{$_POST['cveplaza']}' AND tipo_venta!=4 AND fecha BETWEEN '{$_POST['fecha_ini']}' AND '{$_POST['fecha_fin']}' AND estatus!='C' {$filtro} AND tipo_venta=3");
	while($row=mysql_fetch_array($res)){
		$t1+=$row[2];
		$t2+=$row[1];
		$t3+=$row[4];
		$t4+=$row[3];
		$t5+=$row[6];
		$t6+=$row[5];
		$t7+=$row[8];
		$t8+=$row[7];
		$t9+=$row[9];
		$t10+=$row[10];
		$t11+=$row[11];
	}
	echo '<br>GRAN TOTAL VENTA CONTADO CANT: '.($t1-$t3-$t5-$t9).', IMP: '.number_format($t2-$t4-$t6-$t8,2).'';
	echo '<br>GRAN TOTAL VENTA CREDITO CANT: '.$t3.', IMP: '.number_format($t4,2).'';
	echo '<br>GRAN TOTAL VENTA TRANSFERENCIA CANT: '.$t11.', IMP: '.number_format($t10,2).'';
	echo '<br>GRAN TOTAL VENTA T. CREDITO CANT: '.$t5.', IMP: '.number_format($t6,2).'';
	echo '<br>GRAN TOTAL VENTA T. DEBITO CANT: '.$t7.', IMP: '.number_format($t8,2).'';
	echo '<br>GRAN TOTAL VENTA CANT: '.$t1.', IMP: '.number_format($t2,2).'';
	$tcopias = 0;
	$timpcopias=0;
	$tcopiasefectivo=0;
	$array_tipo_pago = array(1=>'CONTADO', 5=>'T. CREDITO', 7=>'T. DEBITO');
	$rsCopias = mysql_query("SELECT IF(tipo_pago NOT IN (5,7), 1, tipo_pago) as tipo_pago, SUM(copias) as copias, SUM(copias*costo_copias) as imp_copias FROM cobro_engomado a WHERE plaza='{$_POST['cveplaza']}' AND fecha BETWEEN '{$_POST['fecha_ini']}' AND '{$_POST['fecha_fin']}' AND a.copias>0 AND estatus!='C' {$filtro} GROUP BY IF(tipo_pago NOT IN (5,7), 1, tipo_pago)");
	while($Copias = mysql_fetch_assoc($rsCopias)){
		echo '<br>COPIAS '.$array_tipo_pago[$Copias['tipo_pago']].' CANT: '.$Copias['copias'].', IMP: '.number_format($Copias['imp_copias'],2).'';
		$tcopias+=$Copias['copias'];
		$timpcopias+=$Copias['imp_copias'];
		if($Copias['tipo_pago']==1) $tcopiasefectivo+=$Copias['imp_copias'];
	}
	echo '<br>TOTAL COPIAS CANT: '.$tcopias.', IMP: '.number_format($timpcopias,2).'';
	
	$Copias2 = mysql_fetch_array(mysql_query("SELECT SUM(cant), SUM(monto) FROM venta_copias a WHERE plaza='{$_POST['cveplaza']}' AND fecha BETWEEN '{$_POST['fecha_ini']}' AND '{$_POST['fecha_fin']}' AND estatus!='C' {$filtro}"));
	echo '<br>VENTA COPIAS CANT: '.$Copias2[0].', IMP: '.number_format($Copias2[1],2).'';
	
	echo '<h2>DEVOLUCION</h2>';
	$res1=mysql_query("SELECT SUM(a.devolucion),COUNT(a.cve)  FROM devolucion_certificado a LEFT JOIN cobro_engomado b ON b.plaza = a.plaza AND b.cve = a.ticket WHERE a.plaza='{$_POST['cveplaza']}' AND a.fecha BETWEEN '{$_POST['fecha_ini']}' AND '{$_POST['fecha_fin']}' AND IFNULL(b.tipo_pago,0) NOT IN (2,6) AND a.estatus!='C' {$filtro}");
	$row1=mysql_fetch_array($res1);
	echo ' CANT: '.$row1[1].', IMP: '.number_format($row1[0],2).'';
	
	echo '<h2>PAGOS EN CAJA</h2>';
	echo '<table><tr><th>Tipo Pago</th><th>Cantidad</th><th>Importe</th></tr>';
	$t31=$t32=$t33=0;
	$res3=mysql_query("SELECT forma_pago,SUM(monto),COUNT(cve)  FROM pagos_caja a WHERE plaza='{$_POST['cveplaza']}' AND fecha BETWEEN '{$_POST['fecha_ini']}' AND '{$_POST['fecha_fin']}' AND estatus!='C' {$filtro} GROUP BY forma_pago");
	while($row3=mysql_fetch_array($res3)){
		echo '<tr><td>'.$array_forma_pago[$row3['forma_pago']].'</td><td>'.$row3[2].'</td><td>'.number_format($row3[1],2).'</td></tr>';
		$t31+=$row3[1];
		$t32+=$row3[2];
		if($row3['forma_pago'] == 1) $t33+=$row3[1];
	}
	echo '<tr><td>TOTAL</td><td>'.$t32.'</td><td>'.number_format($t31,2).'</td></tr></table>';


	$resRS=mysql_query("SELECT SUM(a.monto),COUNT(a.cve)  FROM recibos_salidav a WHERE a.plaza='{$_POST['cveplaza']}' AND a.fecha BETWEEN '{$_POST['fecha_ini']}' AND '{$_POST['fecha_fin']}' AND a.estatus!='C' {$filtro}");
	$rowRS=mysql_fetch_array($resRS);
	echo '<h2>RECIBOS SALIDA</h2>';
	echo ' CANT: '.$rowRS[1].', IMP: '.number_format($rowRS[0],2).'';
	
	echo '<br><br>GRAN TOTAL IMP: '.number_format($t2+$row2[0]-$row1[0]+$t31+$tVL1-$row4[0]-$row5[0]-$rowRS[0]+$tcopiasefectivo+$Copias2[1],2).'';
	echo '<br>TOTAL A DEPOSITAR IMP: '.number_format($t2-$t4-$t6-$t8-$t10+$row2[0]-$row1[0]+$t33+$tVL3-$row4[0]-$row5[0]-$rowRS[0]+$tcopiasefectivo+$Copias2[1],2).'';
	exit();
}


if($_POST['cmd']==102){
	/*$variables = array(
		'server' => '',
		'printer' => 'impresoratermica',
		'url' => $url_impresion.'/cobro_engomado.php?cmd=102&cveplaza='.$_POST['cveplaza'].'&fecha_ini='.$_POST['busquedafechaini'].'&fecha_fin='.$_POST['busquedafechafin'].'&usuario='.$_POST['busquedausuario']
	);
	$impresion='<iframe src="http://localhost:8020/?'.http_build_query($variables).'" width=200 height=200></iframe>';*/
	$_POST['fecha_ini'] = $_POST['busquedafechaini'];
	$_POST['fecha_fin'] = $_POST['busquedafechafin'];
	$_POST['usuario'] = $_POST['busquedausuario'];
	$array_forma_pago = array(1=>"Efectivo",2=>"Deposito Bancario",3=>"Cheque",4=>"Transferencia",5=>'Tarjeta Bancaria');
	$texto=chr(27)."@";
	$texto.='|';
	$resPlaza = mysql_query("SELECT numero, nombre FROM plazas WHERE cve='{$_POST['cveplaza']}'");
	$rowPlaza = mysql_fetch_array($resPlaza);
	$Usuario = mysql_fetch_assoc(mysql_query("SELECT usuario FROM usuarios WHERE cve='{$_POST['usuario']}'"));
	$texto.=chr(27).'!'.chr(8)." {$rowPlaza['numero']}|{$rowPlaza['nombre']}|| CORTE VENTA CERTIFICADO";
	$texto.='|'.date('Y-m-d H:i:s').'|';
	if($_POST['fecha_ini']==$_POST['fecha_fin']) $texto.=" FECHA: |".$_POST['fecha_ini'];
	else $texto.=" FECHA INI: ".$_POST['fecha_ini']."|FECHA FIN: |".$_POST['fecha_fin'];
	$filtro="";
	if ($_POST['usuario']!=""){ 
		$filtro.=" AND a.usuario='{$_POST['usuario']}' "; 
		$texto.='|USUARIO: '.$Usuario['usuario'];
	}
	$texto.='|| INGRESOS||';
	$t1=$t2=$t3=$t4=$t5=$t6=$t7=$t8=$t9=0;
	$res = mysql_query("SELECT COUNT(cve), SUM(IF(estatus='C',1,0)) FROM cobro_engomado a WHERE plaza='{$_POST['cveplaza']}' AND fecha BETWEEN '{$_POST['fecha_ini']}' AND '{$_POST['fecha_fin']}' {$filtro}");
	$row = mysql_fetch_array($res);
	//$texto.=' NUMERO DE REGISTROS: '.$row[0].chr(10).chr(13).' CANCELADOS: '.$row[1].chr(10).chr(13).chr(10).chr(13).'';
	$texto.=' CANCELADOS: '.$row[1].'||';

	$efectivo = 0;
	$total=0;
	$res=mysql_query("SELECT a.tipo_pago,COUNT(a.cve),sum(a.monto), b.nombre FROM cobro_engomado a INNER JOIN tipos_pago b ON b.cve = a.tipo_pago WHERE a.plaza='{$_POST['cveplaza']}' AND a.fecha BETWEEN '{$_POST['fecha_ini']}' AND '{$_POST['fecha_fin']}' AND a.estatus!='C' {$filtro} AND a.tipo_venta=0 GROUP BY a.tipo_pago ORDER BY a.tipo_pago");
	while($row=mysql_fetch_array($res)){
		if($row[0] == 1){
			$texto.=" EFECTIVO CANT: ".$row[1]." IMP: ".number_format($row[2],2).'|';
			$efectivo += $row[2];
		}
		else{
			$texto.=" {$row['nombre']} CANT: ".$row[1]." IMP: ".number_format($row[2],2).'|';	
		}
		if($row['tipo_pago'] != 12 && $row['tipo_pago'] != 6 && $row['tipo_pago'] != 2)
		$total+=$row[2];		
		
	}

	$Copias = mysql_fetch_array(mysql_query("SELECT SUM(copias),SUM(copias*costo_copias), SUM(IF(tipo_pago NOT IN (5,7), copias*costo_copias, 0)) FROM cobro_engomado a WHERE plaza='{$_POST['cveplaza']}' AND fecha BETWEEN '{$_POST['fecha_ini']}' AND '{$_POST['fecha_fin']}' AND estatus!='C' {$filtro}"));
	$texto.=" COPIAS CANT: ".$Copias[0]." IMP: ".number_format($Copias[1],2).'|';
	
	$texto.=' PAGOS EN CAJA |';
	$t31=$t32=$t33=0;
	$res3=mysql_query("SELECT forma_pago,SUM(monto),COUNT(cve)  FROM pagos_caja a WHERE plaza='{$_POST['cveplaza']}' AND fecha BETWEEN '{$_POST['fecha_ini']}' AND '{$_POST['fecha_fin']}' AND estatus!='C' {$filtro} GROUP BY forma_pago");
	while($row3=mysql_fetch_array($res3)){
		$texto.=" ".$array_forma_pago[$row3['forma_pago']].' CANT: '.$row3[2].', IMP: '.number_format($row3[1],2).'|';
		$t31+=$row3[1];
		$t32+=$row3[2];
		if($row3['forma_pago'] == 1) $efectivo+=$row3[1];
		$total+=$row3[1];
	}


	$texto.='|| EGRESOS||';

	$res1=mysql_query("SELECT SUM(a.devolucion),COUNT(a.cve)  FROM devolucion_certificado a LEFT JOIN cobro_engomado b ON b.plaza = a.plaza AND b.cve = a.ticket WHERE a.plaza='{$_POST['cveplaza']}' AND a.fecha BETWEEN '{$_POST['fecha_ini']}' AND '{$_POST['fecha_fin']}' AND IFNULL(b.tipo_pago,0) NOT IN (2,6) AND a.estatus!='C' {$filtro}");
	$row1=mysql_fetch_array($res1);
	$texto.= ' DEVOLUCIONES CANT: '.$row1[1].', IMP: '.number_format($row1[0],2).'|';

	$res2=mysql_query("SELECT SUM(a.monto),COUNT(a.cve)  FROM recibos_salidav a WHERE a.plaza='{$_POST['cveplaza']}' AND a.fecha BETWEEN '{$_POST['fecha_ini']}' AND '{$_POST['fecha_fin']}' AND a.estatus!='C' {$filtro}");
	$row2=mysql_fetch_array($res2);
	$texto.= ' R.SALIDA CANT: '.$row2[1].', IMP: '.number_format($row2[0],2).'|';
	
	$texto.=' TOTAL EN EFECTIVO: '.number_format($efectivo-$row1[0]-$row2[0]+$Copias[2],2).'|';
	$texto.=' TOTAL DE LA VENTA: '.number_format($total-$row1[0]-$row2[0]+$Copias[1],2).'||';
	

	
	$impresion='<iframe src="http://localhost/impresiongenerallogo.php?textoimp='.$texto.'&logo='.str_replace(' ','',$rowPlaza['numero']).'" width=200 height=200></iframe>';
	echo '<html><body>'.$impresion.'</body></html>';
	echo '<script>setTimeout("window.close()",5000);</script>';
}

if($_POST['cmd']==30){
	$resultado = array('certificado_anterior' => '', 'historial' => '', 'supervision' => 0);
	$select .= "SELECT a.cve, CONCAT(a.fecha,' ',a.hora) as fecha, CONCAT(b.fecha,' ',b.hora) as fechaentrega, TIMEDIFF(IFNULL(CONCAT(b.fecha,' ',b.hora),NOW()),CONCAT(a.fecha,' ',a.hora)) as diferencia, a.placa, c.nombre as nomengomado, d.nombre as nomtipoventa, IF(a.estatus NOT IN ('C','D'), a.monto, 0) as monto, a.copias, e.nombre as nomanio, f.nombre as nomtipopago, IF(a.tipo_pago=6, 'Vale Anticipado', '') as tipo_vale, IF(a.tipo_pago=6 AND a.tipo_venta IN (0,2,4), IF(a.tipo_venta=0, a.vale_pago_anticipado, a.codigo_cortesia), '') as vale, g.nombre as nomdepositante, IF(a.factura=0, '', a.factura) as factura, IFNULL(b.cve, '') as entrega, IFNULL(b.certificado,'') as holograma, 
	IFNULL(i.nombre, '') as nomengomadoentregado, j.usuario, a.obscan, k.nombre as nomintento, a.obs, a.certificado_anterior,a.voluntario,a.entidad
	FROM cobro_engomado a 
	LEFT JOIN certificados b ON a.plaza = b.plaza AND a.cve = b.ticket AND b.estatus!='C' 
	INNER JOIN engomados c ON c.cve = a.engomado 
	INNER JOIN tipo_venta d ON d.cve = a.tipo_venta
	INNER JOIN anios_certificados e ON e.cve = a.anio
	INNER JOIN tipos_pago f ON f.cve = a.tipo_pago
	LEFT JOIN depositantes g ON g.cve = a.depositante
	LEFT JOIN engomados i ON i.cve = b.engomado 
	INNER JOIN usuarios j ON j.cve = a.usuario
	LEFT JOIN motivos_intento k ON k.cve = a.motivo_intento
	WHERE a.plaza = {$_POST['cveplaza']} AND a.placa='{$_POST['placa']}' AND a.estatus NOT IN ('C','D') ORDER BY a.cve DESC";
	$resultado['historial'] = '<table class="table table-responsive">
	  <thead>
	    <tr>
	      <th scope="col" style="text-align: center;">Ticket</th>
	      <th scope="col" style="text-align: center;">Fecha</th>
	      <th scope="col" style="text-align: center;">Fecha Entrega</th>
		  <th scope="col" style="text-align: center;">Placa</th> 
	      <th scope="col" style="text-align: center;">Tipo de Certificado</th> 
	      <th scope="col" style="text-align: center;">Tipo de Venta</th> 
		    <th scope="col" style="text-align: center;">Voluntario</th> 
			  <th scope="col" style="text-align: center;">Entidad</th> 
	      <th scope="col" style="text-align: center;">Monto</th> 
	      <th scope="col" style="text-align: center;">Copias</th> 
	      <th scope="col" style="text-align: center;">Total</th> 
	      <th scope="col" style="text-align: center;">A&ntilde;o de Certificaci&oacute;n</th> 
	      <th scope="col" style="text-align: center;">Tipo de Pago</th> 
	      <th scope="col" style="text-align: center;">Tipo de Vale</th> 
	      <th scope="col" style="text-align: center;">Vale</th> 
	      <th scope="col" style="text-align: center;">Depositante</th> 
	      <th scope="col" style="text-align: center;">Certificado Anterior</th> 
	      <th scope="col" style="text-align: center;">Factura</th> 
	      <th scope="col" style="text-align: center;">Entrega Certificado</th> 
	      <th scope="col" style="text-align: center;">Holograma Entregado</th> 
	      <th scope="col" style="text-align: center;">Tipo de Verificaci&oacute;n Entregada</th> 
	      <th scope="col" style="text-align: center;">Usuario</th> 
	      <th scope="col" style="text-align: center;">Motivo Intento</th> 
	      <th scope="col" style="text-align: center;">Observaciones</th> 
	    </tr>
	  </thead>
	  <tbody>';
	  $res = mysql_query($select);
	  while($row = mysql_fetch_assoc($res)){
	  	$resultado['historial'].='<tr>
	      <td align="center">'.$row['cve'].'</td>
	      <td align="center">'.$row['fecha'].'</td>
	      <td align="center">'.$row['fechaentrega'].'</td>
	      <td align="center">'.$row['placa'].'</td>
	      <td align="left">'.($row['nomengomado']).'</td>
	      <td align="left">'.($row['nomtipoventa']).'</td>
		  <td align="left">'.(utf8_encode ($array_nosi[ $row['voluntario']])).'</td>
		  <td align="left">'.($array_entidad[$row['entidad']]).'</td>
	      <td align="right">'.number_format($row['monto'],2).'</td>
	      <td align="right">'.number_format($row['copias'],2).'</td>
	      <td align="right">'.number_format($row['monto']+$row['copias'],2).'</td>
	      <td align="left">'.($row['nomanio']).'</td>
	      <td align="left">'.($row['nomtipopago']).'</td>
	      <td align="left">'.$row['tipo_vale'].'</td>
	      <td align="center">'.$row['vale'].'</td>
	      <td align="left">'.$row['nomdepositante'].'</td>
	      <td align="center">'.($row['certificado_anterior']).'</td>
	      <td align="center">'.$row['factura'].'</td>
	      <td align="center">'.$row['entrega'].'</td>
	      <td align="center">'.($row['holograma']).'</td>
	      <td align="left">'.($row['nomengomadoentregado']).'</td>
	      <td align="left">'.($row['usuario']).'</td>
	      <td align="left">'.$row['nomintento'].'</td>
	      <td align="left">'.$row['obs'].'</td>
	    </tr>';
	  	if ($resultado['certificado_anterior'] == '' && $row['holograma']!=''){
	  		$resultado['certificado_anterior'] = $row['holograma'];
	  	}
	  }
	  $resultado['historial'].='</table>';
	  $fecha_minima = date( "Y-m-d" , strtotime ( "-7 day" , strtotime(date('Y-m-d')) ));
	$res = mysql_query("SELECT cve, tipo FROM placas_brincos WHERE plaza='{$_POST['cveplaza']}' AND placa='{$_POST['placa']}' AND fecha>='{$fecha_minima}'");
	if($row = mysql_fetch_array($res)){
		$resultado['supervision'] = 1;
	}
	echo json_encode($resultado);
	exit();
}
?>