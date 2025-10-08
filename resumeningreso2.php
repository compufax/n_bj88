<?php
error_reporting(E_ERROR | E_PARSE);
require_once('cnx_db.php');
require_once('globales.php');

function obtener_informacion($datos){
	$resultados = array();
	$fecha = $datos['busquedafechaini'];
	while($fecha<=$datos['busquedafechafin']){
		$resultados[$fecha] = array('fecha' => $fecha);
		$fecha=date( "Y-m-d" , strtotime ( "+1 day" , strtotime($fecha) ) );
	}

    $res = mysql_query("SELECT fecha, SUM(IF(tipo_venta=0 AND tipo_pago=1 AND estatus!='C', monto, 0)) as efectivo, SUM(IF(tipo_venta=0 AND tipo_pago=4 AND estatus!='C', monto, 0)) as transferencia, SUM(IF(tipo_venta=0 AND tipo_pago=5 AND estatus!='C', monto, 0)) as t_credito, SUM(IF(tipo_venta=0 AND tipo_pago=7 AND estatus!='C', monto, 0)) as t_debito, SUM(copias) as copias, SUM(IF(tipo_venta=0 AND tipo_pago=2 AND estatus!='C', monto, 0)) as credito, SUM(IF(tipo_venta=2 AND estatus!='C', 1, 0)) as cortesias, SUM(IF(tipo_venta=3 AND tipo_pago=1 AND estatus!='C', monto, 0)) as reposicion_efectivo, SUM(IF(tipo_venta=3 AND tipo_pago IN (5, 7) AND estatus!='C', monto, 0)) as reposicion_tb, SUM(IF(costo_especial=1 AND estatus!='C',1,0)) as medio_pago FROM cobro_engomado WHERE plaza='{$datos['cveplaza']}' AND fecha BETWEEN '{$datos['busquedafechaini']}' AND '{$datos['busquedafechafin']}' GROUP BY fecha") or die(mysql_error());
   	while($row = mysql_fetch_assoc($res)){
     	$resultados[$row['fecha']]['ventas_efectivo']=$row['efectivo'];
     	$resultados[$row['fecha']]['ventas_t_credito']=$row['t_credito'];
     	$resultados[$row['fecha']]['ventas_t_debito']=$row['t_debito'];
     	$resultados[$row['fecha']]['ventas_transferencia']=$row['transferencia'];
     	$resultados[$row['fecha']]['copias']=$row['copias'];
     	$resultados[$row['fecha']]['credito']=$row['credito'];
     	$resultados[$row['fecha']]['cortesias']=$row['cortesias'];
     	$resultados[$row['fecha']]['reposicion_efectivo']=$row['reposicion_efectivo'];
     	$resultados[$row['fecha']]['reposicion_tb']=$row['reposicion_tb'];
     	$resultados[$row['fecha']]['medio_pago']=$row['medio_pago'];
    }
    $res = mysql_query("SELECT a.fecha, SUM(IF(b.tipo_venta=0 AND b.tipo_pago=1, a.devolucion, 0)) as efectivo, SUM(IF(b.tipo_venta=0 AND b.tipo_pago = 5, a.devolucion, 0)) as t_credito, SUM(IF(b.tipo_venta=0 AND b.tipo_pago = 7, a.devolucion, 0)) as t_debito FROM devolucion_certificado a INNER JOIN cobro_engomado b ON b.plaza = a.plaza AND b.cve = a.ticket WHERE a.plaza='{$datos['cveplaza']}' AND a.estatus!='C' AND a.fecha BETWEEN '{$datos['busquedafechaini']}' AND '{$datos['busquedafechafin']}' GROUP BY a.fecha");
    while($row = mysql_fetch_assoc($res)){
     	$resultados[$row['fecha']]['ventas_t_credito']-=$row['t_credito'];
     	$resultados[$row['fecha']]['ventas_t_debito']-=$row['t_debito'];
     	$resultados[$row['fecha']]['ventas_efectivo']-=$row['efectivo'];
    }
    $res = mysql_query("SELECT fecha, SUM(IF(tipo_pago=2 AND forma_pago IN (2,3,4), monto, 0)) as rec_bancos, SUM(IF(tipo_pago=2 AND forma_pago=5, monto, 0)) as rec_tb, SUM(IF(tipo_pago=2 AND forma_pago = 1, monto, 0)) as rec_efectivo, SUM(IF(tipo_pago=13 AND forma_pago IN (2,3,4), monto, 0)) as rec_pa_bancos, SUM(IF(tipo_pago=13 AND forma_pago=5, monto, 0)) as rec_pa_tb, SUM(IF(tipo_pago=13 AND forma_pago = 1, monto, 0)) as rec_pa_efectivo, SUM(IF(tipo_pago=6 AND forma_pago IN (2,3,4), monto, 0)) as pa_bancos, SUM(IF(tipo_pago=6 AND forma_pago = 1, monto, 0)) as pa_efectivo, SUM(IF(tipo_pago=6 AND forma_pago = 5, monto, 0)) as pa_tb, SUM(IF(forma_pago=9, monto, 0)) as pa_credito FROM pagos_caja WHERE plaza='{$datos['cveplaza']}' AND estatus!='C' AND fecha BETWEEN '{$datos['busquedafechaini']}' AND '{$datos['busquedafechafin']}' GROUP BY fecha") or die(mysql_error());
    while($row = mysql_fetch_assoc($res)){
     	$resultados[$row['fecha']]['rec_bancos']=$row['rec_bancos'];
     	$resultados[$row['fecha']]['rec_tb']=$row['rec_tb'];
     	$resultados[$row['fecha']]['rec_efectivo']=$row['rec_efectivo'];
     	$resultados[$row['fecha']]['pa_transferencias']=$row['pa_bancos'];
     	$resultados[$row['fecha']]['rec_pa_tb']=$row['rec_pa_tb'];
     	$resultados[$row['fecha']]['rec_pa_efectivo']=$row['rec_pa_efectivo'];
     	$resultados[$row['fecha']]['rec_pa_bancos']=$row['rec_pa_bancos'];
     	$resultados[$row['fecha']]['pa_tb']=$row['pa_tb'];
     	$resultados[$row['fecha']]['pa_efectivo']=$row['pa_efectivo'];
     	$resultados[$row['fecha']]['pa_credito']=$row['pa_credito'];
    }
	$res=mysql_query("SELECT fecha, SUM(monto) as gastos FROM recibos_salidav WHERE plaza='{$datos['cveplaza']}' AND fecha BETWEEN '{$datos['busquedafechaini']}' AND '".$_POST['fecha_fin']."'AND estatus='A' GROUP BY fecha");
	while($row=mysql_fetch_array($res)){
		$resultados[$row['fecha']]['salidas']=$row['gastos'];
	}

	$res=mysql_query("SELECT fecha, SUM(monto) as gastos FROM venta_servicios WHERE plaza='{$datos['cveplaza']}' AND fecha BETWEEN '{$datos['busquedafechaini']}' AND '{$datos['busquedafechafin']}'AND estatus='A' GROUP BY fecha");
	while($row=mysql_fetch_array($res)){
		$resultados[$row['fecha']]['servicios']=$row['gastos'];
	}
	return $resultados;
}

function obtener_informacion2($datos){
	$resultado = array();
	$fecha = $datos['busquedafechaini'];
	while($fecha<=$datos['busquedafechafin']){
		$resultado[$fecha] = array('fecha' => $fecha);
		$fecha=date( "Y-m-d" , strtotime ( "+1 day" , strtotime($fecha) ) );
	}
	$res = mysql_query("SELECT fecha, SUM(IF(estatus='A' AND tipo_venta IN (0,3) AND tipo_pago = 1, monto, 0)) as efectivo,
		SUM(IF(estatus='A' AND tipo_venta IN (0,3) AND tipo_pago = 5, monto, 0)) as t_credito,
		SUM(IF(estatus='A' AND tipo_venta IN (0,3) AND tipo_pago = 7, monto, 0)) as t_debito,
		SUM(copias) as copias,
		SUM(IF(estatus='A' and tipo_venta=0 and tipo_pago = 2, monto, 0)) as credito
		FROM cobro_engomado WHERE plaza='{$datos['cveplaza']}' AND fecha BETWEEN '{$datos['busquedafechaini']}' AND '{$datos['busquedafechafin']}' GROUP BY fecha");
	while($row = mysql_fetch_assoc($res)){
		$resultado[$row['fecha']]['efectivo'] = $row['efectivo'];
		$resultado[$row['fecha']]['t_credito'] = $row['t_credito'];
		$resultado[$row['fecha']]['t_debito'] = $row['t_debito'];
		$resultado[$row['fecha']]['copias'] = $row['copias'];
		$resultado[$row['fecha']]['credito'] = $row['credito'];
		$resultado[$row['fecha']]['cometra'] += $row['efectivo'];
		$resultado[$row['fecha']]['bancos'] += $row['efectivo']+$row['t_credito']+$row['t_debito'];
		$resultado[$row['fecha']]['total_venta'] += $row['efectivo']+$row['t_credito']+$row['t_debito']+$row['credito'];
	}

	$res = mysql_query("SELECT fecha, SUM(IF(tipo_pago = 6 AND forma_pago = 1, monto, 0)) as efectivo_pa,
		SUM(IF(tipo_pago = 6 AND forma_pago != 1, monto, 0)) as banco_pa,
		SUM(IF(tipo_pago = 2 AND forma_pago != 1, monto, 0)) as banco_rc,
		SUM(IF(tipo_pago = 2 AND forma_pago = 1, monto, 0)) as efectivo_rc FROM pagos_caja WHERE plaza='{$datos['cveplaza']}' AND fecha BETWEEN '{$datos['busquedafechaini']}' AND '{$datos['busquedafechafin']}' AND estatus!='C' AND tipo_pago IN (2,6) GROUP BY fecha");
	while($row = mysql_fetch_assoc($res)){
		$resultado[$row['fecha']]['efectivo_pa'] = $row['efectivo_pa'];
		$resultado[$row['fecha']]['banco_pa'] = $row['banco_pa'];
		$resultado[$row['fecha']]['banco_rc'] = $row['banco_rc'];
		$resultado[$row['fecha']]['efectivo_rc'] = $row['efectivo_rc'];
		$resultado[$row['fecha']]['cometra'] += $row['efectivo_pa'];
		$resultado[$row['fecha']]['bancos'] += $row['efectivo_pa']+$row['banco_pa']+$row['banco_rc'];
		$resultado[$row['fecha']]['total_venta'] += $row['efectivo_pa']+$row['banco_pa'];
	}

	$res = mysql_query("SELECT fecha, SUM(monto) as gastos FROM recibos_salida WHERE plaza='{$_POST['plazausuario']}' AND fecha BETWEEN '{$datos['busquedafechaini']}' AND '{$datos['busquedafechafin']}' AND estatus='A' GROUP BY fecha");
	while($row = mysql_fetch_assoc($res)){
		$resultado[$row['fecha']]['gastos'] = $row['gastos'];
		$resultado[$row['fecha']]['total_venta'] -= $row['gastos'];
	}
	
	return $resultado;
}

function obtener_informacion_certificados($datos){
	$resultado = array();
	$res = mysql_query("SELECT cve, numero, nombre FROM plazas ORDER BY numero");
	while($row = mysql_fetch_assoc($res)){
		$resultado[$row['cve']]['plaza'] = $row['numero'];
	}
	$select = "SELECT a.plaza, SUM(if(a.engomado=19,1,0)) as t_rechazos, SUM(if(a.engomado=3,1,0)) as t_doble_cero, SUM(if(a.engomado=2,1,0)) as t_cero, SUM(if(a.engomado=5,1,0)) as t_uno, SUM(if(a.engomado=1,1,0)) as t_dos, SUM(if(a.engomado=21,1,0)) as t_taxi, SUM(if(a.engomado=22,1,0)) as t_privado, SUM(if(a.engomado=23,1,0)) as t_exento
		FROM certificados a 
		INNER JOIN cobro_engomado b ON b.plaza = a.plaza AND b.cve = a.ticket 
		LEFT JOIN cobro_engomado_referencia f ON b.plaza = f.plaza AND b.cve = f.ticket 
		LEFT JOIN depositantes d ON d.plaza = b.plaza AND d.cve = b.depositante 
		WHERE a.fecha BETWEEN '{$datos['busquedafechaini']}' AND '{$datos['busquedafechafin']}' AND a.estatus!='C' GROUP BY a.plaza";
	$res = mysql_query($select);
	while($row = mysql_fetch_assoc($res)){
		$resultado[$row['plaza']]['rechazos'] = $row['t_rechazos'];
		$resultado[$row['plaza']]['doble_cero'] = $row['t_doble_cero'];
		$resultado[$row['plaza']]['cero'] = $row['t_cero'];
		$resultado[$row['plaza']]['uno'] = $row['t_uno'];
		$resultado[$row['plaza']]['dos'] = $row['t_dos'];
		$resultado[$row['plaza']]['taxi'] = $row['t_taxi'];
		$resultado[$row['plaza']]['privado'] = $row['t_privado'];
		$resultado[$row['plaza']]['exento'] = $row['t_exento'];
	}
	return $resultado;
}
require_once('validarloging.php');

if($_POST['cmd']==0){
?>

<div class="row justify-content-center">
	<div class="col-xl-12 col-lg-12 col-md-12">
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
        	<div class="col-sm-12" align="center">
	        	<button type="button" class="btn btn-primary" onClick="buscar();">
	            	Buscar
	        	</button>
        	</div>
        </div>
    </div>
</div>
<div class="row" id="resultadocorte">
	
</div>
<script>
	function buscar(){
		$.ajax({
		  url: 'resumeningreso2.php',
		  type: "POST",
		  data: {
			menu: $('#cvemenu').val(),
			cmd: 10,
			cveusuario: $('#cveusuario').val(),
			busquedafechaini: $('#busquedafechaini').val(),
			busquedafechafin: $('#busquedafechafin').val(),
    		cvemenu: $('#cvemenu').val(),
    		cveplaza: $('#cveplaza').val(),
    		cveusuario: $('#cveusuario').val()
		  },
			success: function(data) {
				$('#resultadocorte').html(data);
			}
		});
	}
</script>
<?php
}


if($_POST['cmd']==10){
	$res = obtener_informacion($_POST);
	$colspan = 9;
?>
	<table class="table">
	  <thead>
	  	<tr>
	  		<th>&nbsp;</th>
		    <th colspan="3">Ventas y Devoluciones</th>
		    <th colspan="3">Recuperaci&oacute;n de Cr&eacute;ditos</th>
		    <th colspan="3">Recuperaci&oacute;n de Vales a Cr&eacute;dito</th>
		    <th colspan="2">Cr&eacute;ditos</th>
		    <th colspan="3">Vales de Pago Anticipado</th>
		    <th colspan="2">Reposiciones</th>
		    <th>&nbsp;</th>
		    <th colspan="5">Resumen</th>
		    <th>&nbsp;</th>
		    <th>&nbsp;</th>
		    <th>&nbsp;</th>
		</tr>
	    <tr>
	      <th scope="col" style="text-align: center;">Fecha</th>
	      <th scope="col" style="text-align: center;">Ventas Efectivo (Ya contando devoluciones)</th>
	      <th scope="col" style="text-align: center;">Tarjeta de Credito (Ya contando devoluciones)</th>
	      <th scope="col" style="text-align: center;">Tarjeta de Debito (Ya contando devoluciones)</th>
	      <th scope="col" style="text-align: center;">Transferencia (Ya contando devoluciones)</th>
	      <th scope="col" style="text-align: center;">Recuperaci&oacute;n de Cr&eacute;ditos Banco</th>
	      <th scope="col" style="text-align: center;">Recuperaci&oacute;n de Cr&eacute;ditos Efectivo</th>
	      <th scope="col" style="text-align: center;">Recuperaci&oacute;n de Cr&eacute;ditos Tarjetas Bancarias</th>
	      <th scope="col" style="text-align: center;">Recuperaci&oacute;n de Cr&eacute;ditos Banco</th>
	      <th scope="col" style="text-align: center;">Recuperaci&oacute;n de Cr&eacute;ditos Efectivo</th>
	      <th scope="col" style="text-align: center;">Recuperaci&oacute;n de Cr&eacute;ditos Tarjetas Bancarias</th>
	      <th scope="col" style="text-align: center;">Creditos</th>
	      <th scope="col" style="text-align: center;">Vales de Credito</th>
	      <th scope="col" style="text-align: center;">Vales de Pago Anticipado Transferencias</th>
	      <th scope="col" style="text-align: center;">Vales de Pago Anticipado Tarjetas Bancarias</th>
	      <th scope="col" style="text-align: center;">Vales de Pago Anticipado Efectivo</th>
	      <th scope="col" style="text-align: center;">Reposiciones Efectivo</th>
	      <th scope="col" style="text-align: center;">Reposiciones Tarjeta Bancaria</th>
	      <th scope="col" style="text-align: center;">Servicios</th>
	      <th scope="col" style="text-align: center;">Recibos de Salida(Gastos)</th>
	      <th scope="col" style="text-align: center;">Copias</th>
	      <th scope="col" style="text-align: center;">Total de Efectivo</th>
	      <th scope="col" style="text-align: center;">Venta Efectiva(Bancos Total)</th>
	      <th scope="col" style="text-align: center;">Bancos</th>
	      <th scope="col" style="text-align: center;">&nbsp;</th>
	      <th scope="col" style="text-align: center;">Cortesias Generadas</th>
	      <th scope="col" style="text-align: center;">Medios Pagos Generados en el dia</th>
	    </tr>
	  </thead>
	  <tbody>
	<?php
		$totales = array();
		$i = 0;
		foreach($res as $row){
	?>
	    <tr>
	      <td align="center"><?php echo $row['fecha'];?></td>
	      <td align="right"><?php echo number_format($row['ventas_efectivo'],2);?></td>
	      <td align="right"><?php echo number_format($row['ventas_t_credito'],2);?></td>
	      <td align="right"><?php echo number_format($row['ventas_t_debito'],2);?></td>
	      <td align="right"><?php echo number_format($row['ventas_transferencia'],2);?></td>
	      <td align="right"><?php echo number_format($row['rec_bancos'],2);?></td>
	      <td align="right"><?php echo number_format($row['rec_efectivo'],2);?></td>
	      <td align="right"><?php echo number_format($row['rec_tb'],2);?></td>
	      <td align="right"><?php echo number_format($row['rec_pa_bancos'],2);?></td>
	      <td align="right"><?php echo number_format($row['rec_pa_efectivo'],2);?></td>
	      <td align="right"><?php echo number_format($row['rec_pa_tb'],2);?></td>
	      <td align="right"><?php echo number_format($row['credito'],2);?></td>
	      <td align="right"><?php echo number_format($row['pa_credito'],2);?></td>
	      <td align="right"><?php echo number_format($row['pa_transferencias'],2);?></td>
	      <td align="right"><?php echo number_format($row['pa_tb'],2);?></td>
	      <td align="right"><?php echo number_format($row['pa_efectivo'],2);?></td>
	      <td align="right"><?php echo number_format($row['reposicion_efectivo'],2);?></td>
	      <td align="right"><?php echo number_format($row['reposicion_tb'],2);?></td>
	      <td align="right"><?php echo number_format($row['servicios'],2);?></td>
	      <td align="right"><?php echo number_format($row['salidas'],2);?></td>
	      <td align="right"><?php echo number_format($row['copias'],2);?></td>
	<?php
		$total_efectivo = $row['ventas_efectivo'] + $row['rec_efectivo']+$row['rec_pa_efectivo']+$row['pa_efectivo']+$row['reposicion_efectivo']+$row['copias']-$row['salidas'];
		$total_venta = $total_efectivo + $row['salidas'] + $row['ventas_t_credito'] + $row['ventas_t_debito'] + $row['ventas_transferencia'] + $row['rec_bancos'] + $row['rec_tb'] + $row['rec_pa_bancos'] + $row['rec_pa_tb']+$row['pa_transferencias']+$row['pa_tb']+$row['reposicion_tb'];
		$bancos_total = $total_venta - $row['salidas'];

	?>
		  <td align="right"><?php echo number_format($total_efectivo,2);?></td>
		  <td align="right"><?php echo number_format($total_venta,2);?></td>
		  <td align="right"><?php echo number_format($bancos_total,2);?></td>
		  <td align="right">&nbsp;</td>
		  <td align="right"><?php echo number_format($row['cortesias'],2);?></td>
		  <td align="right"><?php echo number_format($row['medio_pago'],2);?></td>
	    </tr>
	<?php
		$i++;
		$c=0;
		$totales[$c]+=$row['ventas_efectivo'];$c++;
		$totales[$c]+=$row['ventas_t_credito'];$c++;
		$totales[$c]+=$row['ventas_t_debito'];$c++;
		$totales[$c]+=$row['ventas_transferencia'];$c++;
		$totales[$c]+=$row['rec_bancos'];$c++;
		$totales[$c]+=$row['rec_efectivo'];$c++;
		$totales[$c]+=$row['rec_tb'];$c++;
		$totales[$c]+=$row['rec_pa_bancos'];$c++;
		$totales[$c]+=$row['rec_pa_efectivo'];$c++;
		$totales[$c]+=$row['rec_pa_tb'];$c++;
		$totales[$c]+=$row['credito'];$c++;
		$totales[$c]+=$row['pa_credito'];$c++;
		$totales[$c]+=$row['pa_transferencias'];$c++;
		$totales[$c]+=$row['pa_tb'];$c++;
		$totales[$c]+=$row['pa_efectivo'];$c++;
		$totales[$c]+=$row['reposicion_efectivo'];$c++;
		$totales[$c]+=$row['reposicion_tb'];$c++;
		$totales[$c]+=$row['servicios'];$c++;
		$totales[$c]+=$row['salidas'];$c++;
		$totales[$c]+=$row['copias'];$c++;
		$totales[$c]+=$total_efectivo;$c++;
		$totales[$c]+=$total_venta;$c++;
		$totales[$c]+=$bancos_total;$c++;
		$totales[$c]+=$row['cortesias'];$c++;
		$totales[$c]+=$row['medio_pago'];$c++;
	}
	$canttotales = count($totales);
	?>
		<tr>
			<th style="text-align: left;"><?php echo $i;?> Registro(s)</th>
	<?php
		foreach($totales as $k=>$v){
			if($k==($canttotales-2))
				echo '<th>&nbsp;</th><th style="text-align: right;">'.number_format($v,0);
			elseif($k==($canttotales-1))
				echo '<th style="text-align: right;">'.number_format($v,0);
			else
				echo '<th style="text-align: right;">'.number_format($v,2);
			echo '</th>';
		}
	?>
		</tr>
	  </tbody>
	</table>
	<br>
	<table class="table">
	  <thead>
	  	<tr>
	  	  <th scope="col" style="text-align: center;">Plaza</th>
	      <th scope="col" style="text-align: center;">Rechazo</th>
	      <th scope="col" style="text-align: center;">Verificaci&oacute;n 00</th>
	      <th scope="col" style="text-align: center;">Verificaci&oacute;n 0</th>
	      <th scope="col" style="text-align: center;">Verificaci&oacute;n 1</th>
	      <th scope="col" style="text-align: center;">Verificaci&oacute;n 2</th>
	      <th scope="col" style="text-align: center;">Taxis Revistas</th>
	      <th scope="col" style="text-align: center;">Transportes Privados</th>
	      <th scope="col" style="text-align: center;">Verificaci&oacute;n Exento</th>
	    </tr>
	  </thead>
<?php
	$res = obtener_informacion_certificados($_POST);
	foreach ($res as $row) {
?>
	<tr>
	      <td align="left"><?php echo $row['plaza'];?></td>
	      <td align="right"><?php echo number_format($row['rechazos'],0);?></td>
	      <td align="right"><?php echo number_format($row['doble_cero'],0);?></td>
	      <td align="right"><?php echo number_format($row['cero'],0);?></td>
	      <td align="right"><?php echo number_format($row['uno'],0);?></td>
	      <td align="right"><?php echo number_format($row['dos'],0);?></td>
	      <td align="right"><?php echo number_format($row['taxi'],0);?></td>
	      <td align="right"><?php echo number_format($row['privado'],0);?></td>
	      <td align="right"><?php echo number_format($row['exento'],0);?></td>
	</tr>
<?php
	}
?>
	</table>
<?php
}

if($_POST['cmd']==10.5){
	$res = obtener_informacion2($_POST);
	$colspan = 9;
?>
	<table class="table">
	  <thead>
	    <tr>
	      <th scope="col" style="text-align: center;">Fecha</th>
	      <th scope="col" style="text-align: center;">Efectivo</th>
	      <th scope="col" style="text-align: center;">Copias</th>
		  <th scope="col" style="text-align: center;">Pagos Anticipados<br>Efectivo</th> 
	      <th scope="col" style="text-align: center;">Pagos Anticipados<br>Bancos</th> 
	      <th scope="col" style="text-align: center;">Efectivo</th> 
	      <th scope="col" style="text-align: center;">Recuperaci&oacute;n de Cr&eacute;dito<br>Banco</th> 
	      <th scope="col" style="text-align: center;">Tarjeta<br>Cr&eacute;dito</th> 
	      <th scope="col" style="text-align: center;">Tarjeta<br>Debito</th> 
	      <th scope="col" style="text-align: center;">Bancos</th> 
	      <th scope="col" style="text-align: center;">Creditos</th> 
	      <th scope="col" style="text-align: center;">Gastos</th>
	      <th scope="col" style="text-align: center;">Total Venta</th>  
	    </tr>
	  </thead>
	  <tbody>
	<?php
		$totales = array();
		$i = 0;
		foreach($res as $row){
	?>
	    <tr>
	      <td align="center"><?php echo $row['fecha'];?></td>
	      <td align="right"><?php echo number_format($row['efectivo'],2);?></td>
	      <td align="right"><?php echo number_format($row['copias'],2);?></td>
	      <td align="right"><?php echo number_format($row['efectivo_pa'],2);?></td>
	      <td align="right"><?php echo number_format($row['banco_pa'],2);?></td>
	      <td align="right"><?php echo number_format($row['cometra'],2);?></td>
	      <td align="right"><?php echo number_format($row['banco_rc'],2);?></td>
	      <td align="right"><?php echo number_format($row['t_credito'],2);?></td>
	      <td align="right"><?php echo number_format($row['t_debito'],2);?></td>
	      <td align="right"><?php echo number_format($row['bancos'],2);?></td>
	      <td align="right"><?php echo number_format($row['credito'],2);?></td>
	      <td align="right"><?php echo number_format($row['gastos'],2);?></td>
	      <td align="right"><?php echo number_format($row['total_venta'],2);?></td>
	    </tr>
	<?php
		$i++;
		$totales[0]+=$row['efectivo'];
		$totales[1]+=$row['copias'];
		$totales[2]+=$row['efectivo_pa'];
		$totales[3]+=$row['banco_pa'];
		$totales[4]+=$row['cometra'];
		$totales[5]+=$row['banco_rc'];
		$totales[6]+=$row['t_credito'];
		$totales[7]+=$row['t_debito'];
		$totales[8]+=$row['bancos'];
		$totales[9]+=$row['credito'];
		$totales[10]+=$row['gastos'];
		$totales[11]+=$row['total_venta'];
	}
	?>
		<tr>
			<th style="text-align: left;"><?php echo $i;?> Registro(s)</th>
			<th style="text-align: right;"><?php echo number_format($totales[0],2);?></th>
			<th style="text-align: right;"><?php echo number_format($totales[1],2);?></th>
			<th style="text-align: right;"><?php echo number_format($totales[2],2);?></th>
			<th style="text-align: right;"><?php echo number_format($totales[3],2);?></th>
			<th style="text-align: right;"><?php echo number_format($totales[4],2);?></th>
			<th style="text-align: right;"><?php echo number_format($totales[5],2);?></th>
			<th style="text-align: right;"><?php echo number_format($totales[6],2);?></th>
			<th style="text-align: right;"><?php echo number_format($totales[7],2);?></th>
			<th style="text-align: right;"><?php echo number_format($totales[8],2);?></th>
			<th style="text-align: right;"><?php echo number_format($totales[9],2);?></th>
			<th style="text-align: right;"><?php echo number_format($totales[10],2);?></th>
			<th style="text-align: right;"><?php echo number_format($totales[11],2);?></th>
		</tr>
	  </tbody>
	</table>
	

<?php
}

?>