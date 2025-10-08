<?php
error_reporting(E_ERROR | E_PARSE);
require_once('cnx_db.php');
require_once('globales.php');

function obtener_informacion($datos){
	$informacion = array();
	$resultado = array();
	$res = mysql_query("SELECT a.cve, a.nombre FROM engomados a inner join engomados_plazas b on a.cve = b.engomado WHERE b.entrega=1 AND b.plaza = '{$datos['cveplaza']}' ORDER BY a.nombre");
	while($row=mysql_fetch_array($res)){
		$resultado[$row['cve']] = array('nombre' => $row['nombre']);
	}
	
	$res = mysql_query("SELECT a.engomado, COUNT(a.cve) as entregados, SUM(a.costo) as costo,
								SUM(IF(tipo_venta=0 AND tipo_pago IN (1,5,7), 1, 0)) as pagados,
								SUM(IF(tipo_venta=0 AND tipo_pago IN (1,5,7), b.monto, 0)) as venta,
								SUM(IF(b.tipo_venta=1,1,0)) as intentos,
								SUM(IF(b.tipo_venta=2,1,0)) as cortesias, 
								SUM(IF(b.tipo_venta=0 and b.tipo_pago=6,1,0)) as pagos_anticipados,
								SUM(IF(b.tipo_venta=0 and b.tipo_pago=2,1,0)) as creditos,
								SUM(IF(b.tipo_venta=0 and b.tipo_pago=2,b.monto,0)) as creditos_monto,
								SUM(IF(a.engomado!=24, a.costo_ambiente, 0)) as fondo_ambiental
						FROM certificados a 
						INNER JOIN cobro_engomado b ON b.plaza = a.plaza AND b.cve = a.ticket
						WHERE a.plaza = '{$datos['cveplaza']}' AND a.fecha BETWEEN '{$datos['busquedafechaini']}' AND '{$datos['busquedafechafin']}' AND a.estatus!='C'
						GROUP BY a.engomado");
	while($row = mysql_fetch_assoc($res)){
		$resultado[$row['engomado']]['entregados'] = $row['entregados'];
		$resultado[$row['engomado']]['costo'] += $row['costo'];
		$resultado[$row['engomado']]['pagados'] = $row['pagados'];
		$resultado[$row['engomado']]['venta'] = $row['venta'];
		$resultado[$row['engomado']]['intentos'] = $row['intentos'];
		$resultado[$row['engomado']]['cortesias'] = $row['cortesias'];
		$resultado[$row['engomado']]['pagos_anticipados'] = $row['pagos_anticipados'];
		$resultado[$row['engomado']]['creditos'] = $row['creditos'];
		$informacion['creditos_monto'] += $row['creditos_monto'];
		$informacion['creditos'] += $row['creditos'];
		$resultado[$row['engomado']]['fondo_ambiental'] = $row['fondo_ambiental'];
	}

	$res = mysql_query("SELECT engomado, COUNT(cve) as cancelados, SUM(costo) as costo FROM certificados_cancelados WHERE plaza='{$datos['cveplaza']}' AND fecha BETWEEN '{$datos['busquedafechaini']}' AND '{$datos['busquedafechafin']}' AND estatus!='C' GROUP BY engomado");
	while($row = mysql_fetch_assoc($res)){
		$resultado[$row['engomado']]['cancelados'] = $row['cancelados'];
		$resultado[$row['engomado']]['costo'] += $row['costo'];
	}

	$res = mysql_query("SELECT SUM(IF(a.forma_pago=1, 1, 0)) as cant_efectivo, SUM(IF(a.forma_pago=1, b.monto, 0)) as monto_efectivo,
		                       SUM(IF(a.forma_pago!=1, 1, 0)) as cant_banco, SUM(IF(a.forma_pago!=1, b.monto, 0)) as monto_banco creditos_monto 
		                FROM pagos_caja a INNER JOIN vales_pago_anticipado b ON a.plaza = b.plaza AND a.cve = b.pago 
		                WHERE a.plaza = '{$datos['cveplaza']}' AND a.fecha BETWEEN '{$datos['busquedafechaini']}' AND '{$datos['busquedafechafin']}' AND a.estatus!='C'");
	$row = mysql_fetch_assoc($res);
	$informacion['pa_cant_efectivo'] = $row['cant_efectivo'];
	$informacion['pa_monto_efectivo'] = $row['monto_efectivo'];
	$informacion['pa_cant_banco'] = $row['cant_banco'];
	$informacion['pa_monto_banco'] = $row['monto_banco'];

	$res = mysql_query("SELECT SUM(IF(tipo_pago IN (5,7), copias, 0)) as copias_banco, SUM(IF(tipo_pago NOT IN (5,7), copias, 0)) as copias_efectivo, 
		SUM(IF(tipo_venta=3, monto, 0)) as reposiciones FROM cobro_engomado WHERE plaza='{$datos['cveplaza']}' AND fecha BETWEEN '{$datos['busquedafechaini']}' AND '{$datos['busquedafechafin']}' AND estatus!='C'");
	$row = mysql_fetch_assoc($res);
	$informacion['copias_banco'] = $row['copias_banco'];
	$informacion['copias_efectivo'] = $row['copias_efectivo'];
	$informacion['reposiciones'] = $row['reposiciones'];
	$informacion['engomados'] = $resultado;

	$res = mysql_query("SELECT SUM(devolucion) as devoluciones FROM devolucion_certificado WHERE plaza='{$datos['cveplaza']}' AND fecha BETWEEN '{$datos['busquedafechaini']}' AND '{$datos['busquedafechafin']}' AND estatus!='C'");
	$informacion['devoluciones'] = $row['devoluciones'];

	return $informacion;
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
		  url: 'reporte_utilidad.php',
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
	      <th scope="col" style="text-align: center;">Pagados</th>
	      <th scope="col" style="text-align: center;">Pagos Anticipados</th>
		  <th scope="col" style="text-align: center;">Creditos</th> 
	      <th scope="col" style="text-align: center;">Intentos</th> 
	      <th scope="col" style="text-align: center;">Cortesias</th> 
	      <th scope="col" style="text-align: center;">Entregados</th> 
	      <th scope="col" style="text-align: center;">Cancelados</th> 
	      <th scope="col" style="text-align: center;">Venta</th> 
	      <th scope="col" style="text-align: center;">Costo Utilizados</th> 
	      <th scope="col" style="text-align: center;">Fondo Ambiental</th> 
	    </tr>
	  </thead>
	  <tbody>
	<?php
		$totales = array();
		$i = 0;
		foreach($res['engomados'] as $row){
	?>
	    <tr>
	      <td align="center"><?php echo $row['tipo'];?></td>
	      <td align="right"><?php echo number_format($row['pagados'],0);?></td>
	      <td align="right"><?php echo number_format($row['pagos_anticipados'],0);?></td>
	      <td align="right"><?php echo number_format($row['creditos'],0);?></td>
	      <td align="right"><?php echo number_format($row['cortesias'],0);?></td>
	      <td align="right"><?php echo number_format($row['entregados'],0);?></td>
	      <td align="right"><?php echo number_format($row['cancelados'],0);?></td>
	      <td align="right"><?php echo number_format($row['venta'],2);?></td>
	      <td align="right"><?php echo number_format($row['costo'],2);?></td>
	      <td align="right"><?php echo number_format($row['fondo_ambiental'],2);?></td>
	    </tr>
	<?php
		$i++;
		$totales[0]+=$row['pagados'];
		$totales[1]+=$row['pagos_anticipados'];
		$totales[2]+=$row['creditos'];
		$totales[3]+=$row['cortesias'];
		$totales[4]+=$row['entregados'];
		$totales[5]+=$row['cancelados'];
		$totales[6]+=$row['venta'];
		$totales[7]+=$row['costo'];
		$totales[8]+=$row['fondo_ambiental'];
	}
	?>
		<tr>
			<th style="text-align: left; border-top: 2px solid #000000;"><?php echo $i;?> Registro(s)</th>
			<th style="text-align: right; border-top: 2px solid #000000;">Totales:</th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($totales[0],0);?></th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($totales[1],0);?></th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($totales[2],0);?></th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($totales[3],0);?></th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($totales[4],0);?></th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($totales[5],0);?></th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($totales[6],2);?></th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($totales[7],2);?></th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($totales[8],2);?></th>
		</tr>
	  </tbody>
	</table>
	<br>
	<table class="table">
	  <thead>
	    <tr>
	      <th scope="col" style="text-align: center;">Tipo</th>
	      <th scope="col" style="text-align: center;">Cantidad</th>
	      <th scope="col" style="text-align: center;">Costo</th>
	    </tr>
	  </thead>
	  <tbody>
	  	<tr>
	  		<td align="left">VALES ANTICIPADOS PAGADOS EN EFECTIVO</td>
	  		<td align="right"><?php echo number_format($res['pa_cant_efectivo'],0);?></td>
	  		<td align="right"><?php echo number_format($res['pa_monto_efectivo'],2);?></td>
	  	</tr>
	  	<tr>
	  		<td align="left">VALES ANTICIPADOS PAGADOS EN BANCOS</td>
	  		<td align="right"><?php echo number_format($res['pa_cant_banco'],0);?></td>
	  		<td align="right"><?php echo number_format($res['pa_monto_banco'],2);?></td>
	  	</tr>
	  	<tr>
	  		<td align="left">Creditos Verificados</td>
	  		<td align="right"><?php echo number_format($res['creditos'],0);?></td>
	  		<td align="right"><?php echo number_format($res['creditos_monto'],2);?></td>
	  	</tr>
	  	<tr>
	  		<th style="text-align: right; border-top: 2px solid #000000;">TOTAL</th>
	  		<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($res['creditos']+$res['pa_cant_banco']+$res['pa_cant_efectivo'],0);?></th>
	  		<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($res['creditos_monto']+$res['pa_monto_banco']+$res['pa_monto_efectivo'],2);?></th>
	  	</tr>
	  </tbody>
	</table>
	<br>
	<table class="table">
	  <thead>
	    <tr>
	      <th scope="col" style="text-align: center;">Tipo</th>
	      <th scope="col" style="text-align: center;">Costo</th>
	    </tr>
	  </thead>
	  <tbody>
	  	<tr>
	  		<td align="left">Copias en Banco</td>
	  		<td align="right"><?php echo number_format($res['copias_banco'],2);?></td>
	  	</tr>
	  	<tr>
	  		<td align="left">Copias en Efectivo</td>
	  		<td align="right"><?php echo number_format($res['copias_efectivo'],2);?></td>
	  	</tr>
	  	<tr>
	  		<td align="left">Reposiciones</td>
	  		<td align="right"><?php echo number_format($res['reposiciones'],2);?></td>
	  	</tr>
	  	<tr>
	  		<td align="left">Devoluciones</td>
	  		<td align="right"><?php echo number_format($res['devoluciones'],2);?></td>
	  	</tr>
	  	<tr>
	  		<th style="text-align: right; border-top: 2px solid #000000;">TOTAL</th>
	  		<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($res['copias_banco']+$res['copias_efectivo']+$res['reposiciones']-$res['devoluciones'],2);?></th>
	  	</tr>
	  	<tr>
	  		<td style="text-align: left; border-top: 2px solid #000000;">TOTAL INGRESOS</td>
	  		<td style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($res['copias_banco']+$res['copias_efectivo']+$res['reposiciones']-$res['devoluciones']+$res['creditos_monto']+$res['pa_monto_banco']+$res['pa_monto_efectivo']+$totales[6],2);?></td>
	  	</tr>
	  	<tr>
	  		<td style="text-align: left;">Utilizados mas Fondo Ambiental</td>
	  		<td style="text-align: right;"><?php echo number_format($totales[7]+$totales[8],2);?></td>
	  	</tr>
	  	<tr>
	  		<td style="text-align: left;">TOTAL INGRESOS</td>
	  		<td style="text-align: right;"><?php echo number_format($res['copias_banco']+$res['copias_efectivo']+$res['reposiciones']-$res['devoluciones']+$res['creditos_monto']+$res['pa_monto_banco']+$res['pa_monto_efectivo']+$totales[6]-$totales[7]-$totales[8],2);?></td>
	  	</tr>
	  </tbody>
	</table>

<?php
}

?>