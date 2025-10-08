<?php
error_reporting(E_ERROR | E_PARSE);
require_once('cnx_db.php');
require_once('globales.php');

function obtener_informacion($datos){
	$resultado = array();
	
	$res = mysql_query("SELECT  SUM(IF(a.tipo_venta = 0 AND a.tipo_pago=1 AND a.estatus!='C' AND a.engomado!=23,1,0)) as efectivos,
								SUM(IF(a.tipo_venta = 0 AND a.tipo_pago=1 AND a.estatus!='C' AND a.engomado!=23,a.monto,0)) as efectivos_monto,
								SUM(IF(a.tipo_venta=0 AND a.tipo_pago=2 AND a.estatus!='C',1,0)) as creditos,
								SUM(IF(a.tipo_venta=0 AND a.tipo_pago=2 AND a.estatus!='C',a.monto,0)) as creditos_monto
								SUM(IF(a.tipo_pago IN (5,7) AND a.estatus!='C',1,0)) as bancos,
								SUM(IF(a.tipo_pago IN (5,7) AND a.estatus!='C',a.monto,0)) as bancos_monto,
								SUM(IF(a.tipo_venta=3 AND a.estatus!='C', 1, 0)) as reposiciones,
								SUM(IF(a.tipo_venta=3 AND a.estatus!='C', a.monto, 0)) as reposiciones_monto,
								SUM(IF(a.tipo_pago IN (5,7), a.copias, 0)) as copias_banco,
								SUM(IF(a.tipo_pago NOT IN (5,7), a.copias, 0)) as copias_efectivo,
								SUM(IF(a.engomado = 24 AND a.estatus!='C', 1, 0)) as excentos,
								SUM(IF(a.tipo_venta=1 AND a.estatus!='C', 1, 0)) as intentos,
								SUM(IF(a.tipo_venta = 2 AND a.estatus!='C', 1, 0)) as cortesias,
								SUM(IF(a.tipo_venta=0 AND a.tipo_pago=6 AND a.estatus!='C', 1, 0)) as vales_usados,
								SUF(IF(ISNULL(b.cve, 1, 0))) as no_verificados
						FROM cobro_engomado a
						LEFT JOIN certificados b ON a.plaza = b.plaza AND a.cve = b.ticket AND b.estatus!='C' AND b.fecha BETWEEN '{$datos['busquedafechaini']}' AND '{$datos['busquedafechafin']}'
						WHERE a.plaza = '{$datos['cveplaza']}' AND a.fecha BETWEEN '{$datos['busquedafechaini']}' AND '{$datos['busquedafechafin']}'");
	$row = mysql_fetch_assoc($res);
	$resultado = $row;

	$res = mysql_query("SELECT SUM(IF(a.forma_pago=1, 1, 0)) as pa_cant_efectivo, SUM(IF(a.forma_pago=1, b.monto, 0)) as pa_monto_efectivo,
		                       SUM(IF(a.forma_pago!=1, 1, 0)) as pa_cant_banco, SUM(IF(a.forma_pago!=1, b.monto, 0)) as pa_monto_banco 
		                FROM pagos_caja a INNER JOIN vales_pago_anticipado b ON a.plaza = b.plaza AND a.cve = b.pago 
		                WHERE a.plaza = '{$datos['cveplaza']}' AND a.fecha BETWEEN '{$datos['busquedafechaini']}' AND '{$datos['busquedafechafin']}' AND a.estatus!='C'");
	$row = mysql_fetch_assoc($res);
	$resultado = array_merge($resultado, $row);

	$res = mysql_query("SELECT COUNT(cve) as devoluciones, SUM(devolucion) as devoluciones_monto FROM devolucion_certificado WHERE plaza='{$datos['cveplaza']}' AND fecha BETWEEN '{$datos['busquedafechaini']}' AND '{$datos['busquedafechafin']}' AND estatus!='C'");
	$row = mysql_fetch_assoc($res);
	$resultado = array_merge($resultado, $row);


	$res = mysql_query("SELECT COUNT(cve) as recuperacion, SUM(monto) as recuperacion_monto FROM pagos_caja WHERE plaza='{$datos['cveplaza']}' AND fecha BETWEEN '{$datos['busquedafechaini']}' AND '{$datos['busquedafechafin']}' AND tipo_pago = 2 AND estatus!='C'");
	$row = mysql_fetch_assoc($res);
	$resultado = array_merge($resultado, $row);

	$res = mysql_query("SELECT a.cve, a.nombre FROM engomados a inner join engomados_plazas b on a.cve = b.engomado WHERE b.entrega=1 AND b.plaza = '{$datos['cveplaza']}' ORDER BY a.nombre");
	while($row=mysql_fetch_array($res)){
		$resultado['engomados'][$row['cve']] = array('nombre' => $row['nombre']);
	}

	$res = mysql_query("SELECT engomado, SUM(foliofin+1-folioini) as cantidad,
		SUM((foliofin+1-folioini)*costo) as costo
		FROM compras WHERE plaza='{$datos['cveplaza']}' AND estatus!='C' AND fecha_compra <= '{$datos['busquedafechafin']}' GROUP BY engomado");
	while($row = mysql_fetch_assoc($res)){
		$resultado['engomados'][$row['engomado']]['existencia']['cant'] += $row['cantidad'];
		$resultado['engomados'][$row['engomado']]['existencia']['costo'] += $row['costo'];
	}

	$res = mysql_query("SELECT engomado, COUNT(cve) as cantidad, SUM(costo) as costo, SUM(IF(fecha>='{$datos['busquedafechaini']}', 1, 0)) as cantperiodo, SUM(IF(fecha>='{$datos['busquedafechaini']}', costo, 0)) as costoperiodo  FROM certificados WHERE plaza='{$_POST['cveplaza']}' AND fecha <= '{$datos['busquedafechafin']}' AND estatus!='C' GROUP BY engomado");
	while($row = mysql_fetch_assoc($res)){
		$resultado['engomados'][$row['engomado']]['entregados'] = array('cant'=>$row['cantperiodo'], 'costo' => $row['costoperiodo']);
		$resultado['engomados'][$row['engomado']]['existencia']['cant'] -= $row['cantidad'];
		$resultado['engomados'][$row['engomado']]['existencia']['costo'] -= $row['costo'];
	}
	$res = mysql_query("SELECT engomado, COUNT(cve) as cantidad, SUM(costo) as costo, SUM(IF(fecha>='{$datos['busquedafechaini']}', 1, 0)) as cantperiodo, SUM(IF(fecha>='{$datos['busquedafechaini']}', costo, 0)) as costoperiodo FROM certificados_cancelados WHERE plaza='{$_POST['cveplaza']}' AND fecha <= '{$datos['busquedafechafin']}' AND estatus!='C' GROUP BY engomado");
	while($row = mysql_fetch_assoc($res)){
		$resultado['engomados'][$row['engomado']]['cancelados'] = array('cant'=>$row['cantperiodo'], 'costo' => $row['costoperiodo']);
		$resultado['engomados'][$row['engomado']]['existencia']['cant'] -= $row['cantidad'];
		$resultado['engomados'][$row['engomado']]['existencia']['costo'] -= $row['costo'];
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
			<label class="col-sm-2 col-form-label">A&ntilde;o de Certificaci&oacute;n</label>
			<div class="col-sm-4">
            	<select name="busquedaanio" id="busquedaanio" class="form-control"><option value="">Todos</option>
            	<?php
            	$primero = true;
            	$res1 = mysql_query("SELECT cve, nombre FROM anios_certificados WHERE venta=1 ORDER BY nombre");
				while($row1=mysql_fetch_array($res1)){
					echo '<option value="'.$row1['cve'].'"';
					if($primero) echo ' selected';
					echo '>'.$row1['nombre'].'</option>';
					$primero = false;
				}
				?>
            	</select>
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
		  url: 'desglose_venta_periodo.php',
		  type: "POST",
		  data: {
			menu: $('#cvemenu').val(),
			cmd: 10,
			cveusuario: $('#cveusuario').val(),
			busquedafechaini: $('#busquedafechaini').val(),
			busquedafechafin: $('#busquedafechafin').val(),
			busquedaanio: $('#busquedaanio').val(),
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
	      <th scope="col" style="text-align: center;">Tipo</th>
	      <th scope="col" style="text-align: center;">Cantidad</th>
	      <th scope="col" style="text-align: center;">Importe</th>
	    </tr>
	  </thead>
	  <tbody>
	  	<tr>
	      <td align="left">Excentos</td>
	      <td align="right"><?php echo number_format($row['excentos'],0);?></td>
	      <td align="right"><?php echo number_format(0,2);?></td>
	    </tr>
	    <tr>
	      <td align="left">Intentos</td>
	      <td align="right"><?php echo number_format($row['intentos'],0);?></td>
	      <td align="right"><?php echo number_format(0,2);?></td>
	    </tr>
	    <tr>
	      <td align="left">Cortesias</td>
	      <td align="right"><?php echo number_format($row['cortesias'],0);?></td>
	      <td align="right"><?php echo number_format(0,2);?></td>
	    </tr>
		<tr>
			<th style="text-align: left; border-top: 2px solid #000000;">TOTAL</th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($row['excentos']+$row['intentos']+$row['cortesias'],0);?></th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format(0,2);?></th>
		</tr>
		<tr>
	      <td align="left">Canje de Pagos Anticipaso</td>
	      <td align="right"><?php echo number_format($row['vales_usados'],0);?></td>
	      <td align="right"><?php echo number_format(0,2);?></td>
	    </tr>
	    <tr>
	      <td align="left">Creditos Verificados</td>
	      <td align="right"><?php echo number_format($row['creditos'],0);?></td>
	      <td align="right"><?php echo number_format($row['creditos_monto'],2);?></td>
	    </tr>
	    <tr>
	      <td align="left">Depositos en Efectivo</td>
	      <td align="right"><?php echo number_format($row['efectivos'],0);?></td>
	      <td align="right"><?php echo number_format($row['efectivos_monto'],2);?></td>
	    </tr>
	    <tr>
	      <td align="left">Banco Tarjeta de Credito y Debito</td>
	      <td align="right"><?php echo number_format($row['bancos'],0);?></td>
	      <td align="right"><?php echo number_format($row['bancos_monto'],2);?></td>
	    </tr>
		<tr>
			<th style="text-align: left; border-top: 2px solid #000000;">TOTAL</th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($row['creditos']+$row['efectivos']+$row['bancos']+$row['vales_usados'],0);?></th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($row['creditos_monto']+$row['efectivos_monto']+$row['bancos_monto'],2);?></th>
		</tr>
		<tr><td colspan="3"><h3>Venta de Vales de Pago Anticipado</h3></td></tr>
		<tr>
	      <td align="left">Compra de Vales Anticipados en Efectivo</td>
	      <td align="right"><?php echo number_format($row['pa_cant_efectivo'],0);?></td>
	      <td align="right"><?php echo number_format($row['pa_monto_efectivo'],2);?></td>
	    </tr>
	    <tr>
	      <td align="left">Compra de Vales Anticipados en Banco</td>
	      <td align="right"><?php echo number_format($row['pa_cant_banco'],0);?></td>
	      <td align="right"><?php echo number_format($row['pa_monto_banco'],2);?></td>
	    </tr>
		<tr>
			<th style="text-align: left; border-top: 2px solid #000000;">TOTAL</th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($row['pa_cant_banco']+$row['pa_cant_efectivo'],0);?></th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($row['pa_monto_banco']+$row['pa_monto_efectivo'],2);?></th>
		</tr>
		<tr>
	      <td align="left">Copias en Efectivo</td>
	      <td align="right">&nbsp;</td>
	      <td align="right"><?php echo number_format($row['copias_efectivo'],2);?></td>
	    </tr>
	    <tr>
	      <td align="left">Copias en Bancos</td>
	      <td align="right">&nbsp;</td>
	      <td align="right"><?php echo number_format($row['copias_banco'],2);?></td>
	    </tr>
	    <tr>
			<th style="text-align: left; border-top: 2px solid #000000;">TOTAL</th>
			<th style="text-align: right; border-top: 2px solid #000000;">&nbsp;</th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($row['copias_efectivo']+$row['copias_banco'],2);?></th>
		</tr>
		<tr>
	      <td align="left">Reposiciones</td>
	      <td align="right"><?php echo number_format($row['reposiciones'],0);?></td>
	      <td align="right"><?php echo number_format($row['reposiciones_monto'],2);?></td>
	    </tr>
	    <tr>
	      <td align="left">Pagados No Verficados</td>
	      <td align="right"><?php echo number_format($row['no_verificados'],0);?></td>
	      <td align="right">&nbsp;</td>
	    </tr>
	    <tr>
	      <td align="left">Recuperacion del Mes</td>
	      <td align="right"><?php echo number_format($row['recuperacion'],0);?></td>
	      <td align="right"><?php echo number_format($row['recuperacion_monto'],2);?></td>
	    </tr>
	   	<tr>
			<th style="text-align: left; border-top: 2px solid #000000;">GRAN TOTAL</th>
			<th style="text-align: right; border-top: 2px solid #000000;">&nbsp;</th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($row['creditos_monto']+$row['efectivos_monto']+$row['bancos_monto']+$row['pa_monto_efectivo']+$row['pa_monto_banco']+$row['copias_efectivo']+$row['copias_banco']+$row['reposiciones_monto']+$row['recuperacion_monto'],2);?></th>
		</tr>
		<tr><td colspan="3"><h3>DESGLOSE DE CERTIFICADOS DE ENTREGA</h3></td></tr>
		<?php
		$t1=$t2=0;
		foreach($row['engomados'] as $engomados){
		?>
			<tr>
		      <td align="left"><?php echo $engomados['nombre'];?></td>
		      <td align="right"><?php echo number_format($engomados['entregados']['cant'],0);?></td>
		      <td align="right"><?php echo number_format($engomados['entregados']['costo'],2);?></td>
		    </tr>
		<?php
			$t1+=$engomados['entregados']['cant'];
			$t2+=$engomados['entregados']['costo'];
		}
		?>
		<tr>
			<th style="text-align: left; border-top: 2px solid #000000;">TOTAL DE CERTIFICADOS ENTREGADOS</th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($t1,0);?></th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($t2,2);?></th>
		</tr>
		<tr><td colspan="3"><h3>DESGLOSE DE CERTIFICADOS CANCELADOS</h3></td></tr>
		<?php
		$t1=$t2=0;
		foreach($row['engomados'] as $engomados){
		?>
			<tr>
		      <td align="left"><?php echo $engomados['nombre'];?></td>
		      <td align="right"><?php echo number_format($engomados['cancelados']['cant'],0);?></td>
		      <td align="right"><?php echo number_format($engomados['cancelados']['costo'],2);?></td>
		    </tr>
		<?php
			$t1+=$engomados['cancelados']['cant'];
			$t2+=$engomados['cancelados']['costo'];
		}
		?>
		<tr>
			<th style="text-align: left; border-top: 2px solid #000000;">TOTAL DE CERTIFICADOS CANCELADOS</th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($t1,0);?></th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($t2,2);?></th>
		</tr>
		<tr><td colspan="3"><h3>EXISTENCIA EN ALMACEN</h3></td></tr>
		<?php
		$t1=$t2=0;
		foreach($row['engomados'] as $engomados){
		?>
			<tr>
		      <td align="left"><?php echo $engomados['nombre'];?></td>
		      <td align="right"><?php echo number_format($engomados['existencia']['cant'],0);?></td>
		      <td align="right"><?php echo number_format($engomados['existencia']['costo'],2);?></td>
		    </tr>
		<?php
			$t1+=$engomados['existencia']['cant'];
			$t2+=$engomados['existencia']['costo'];
		}
		?>
		<tr>
			<th style="text-align: left; border-top: 2px solid #000000;">TOTAL DE INVENTARIO</th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($t1,0);?></th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($t2,2);?></th>
		</tr>
	  </tbody>
	</table>
	

<?php
}

?>