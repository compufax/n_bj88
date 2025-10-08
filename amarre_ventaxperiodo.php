<?php
error_reporting(E_ERROR | E_PARSE);
require_once('cnx_db.php');
require_once('globales.php');

function obtener_informacion($datos){
	$resultado = array();
	
	$res = mysql_query("SELECT  SUM(IF(tipo_venta = 0 AND tipo_pago=1 AND estatus!='C' AND engomado!=23,1,0)) as efectivos,
								SUM(IF(tipo_venta = 0 AND tipo_pago=1 AND estatus!='C' AND engomado!=23,monto,0)) as efectivos_monto,
								SUM(IF(tipo_venta=0 AND tipo_pago=2 AND estatus!='C',1,0)) as creditos,
								SUM(IF(tipo_venta=0 AND tipo_pago=2 AND estatus!='C',monto,0)) as creditos_monto
								SUM(IF(tipo_pago IN (5,7) AND estatus!='C',1,0)) as bancos,
								SUM(IF(tipo_pago IN (5,7) AND estatus!='C',monto,0)) as bancos_monto,
								SUM(IF(tipo_venta=3 AND estatus!='C', 1, 0)) as reposiciones,
								SUM(IF(tipo_venta=3 AND estatus!='C', monto, 0)) as reposiciones_monto,
								SUM(IF(tipo_pago IN (5,7), copias, 0)) as copias_banco
						FROM cobro_engomado
						WHERE plaza = '{$datos['cveplaza']}' AND fecha BETWEEN '{$datos['busquedafechaini']}' AND '{$datos['busquedafechafin']}'");
	$row = mysql_fetch_assoc($res);
	$resultado = $row;

	$res = mysql_query("SELECT SUM(IF(a.forma_pago=1, 1, 0)) as pa_cant_efectivo, SUM(IF(a.forma_pago=1, b.monto, 0)) as pa_monto_efectivo,
		                       SUM(IF(a.forma_pago!=1, 1, 0)) as pa_cant_banco, SUM(IF(a.forma_pago!=1, b.monto, 0)) as pa_monto_banco 
		                FROM pagos_caja a INNER JOIN vales_pago_anticipado b ON a.plaza = b.plaza AND a.cve = b.pago 
		                WHERE a.plaza = '{$datos['cveplaza']}' AND a.fecha BETWEEN '{$datos['busquedafechaini']}' AND '{$datos['busquedafechafin']}' AND a.estatus!='C'");
	$row = mysql_fetch_assoc($res);
	$$resultado = array_merge($resultado, $row);

	$res = mysql_query("SELECT COUNT(cve) as devoluciones, SUM(devolucion) as devoluciones_monto FROM devolucion_certificado WHERE plaza='{$datos['cveplaza']}' AND fecha BETWEEN '{$datos['busquedafechaini']}' AND '{$datos['busquedafechafin']}' AND estatus!='C'");
	$$resultado = array_merge($resultado, $row);

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
		  url: 'amarre_ventaxperiodo.php',
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
	      <td align="center">Creditos Verificados</td>
	      <td align="right"><?php echo number_format($row['creditos'],0);?></td>
	      <td align="right"><?php echo number_format($row['creditos_monto'],2);?></td>
	    </tr>
	    <tr>
	      <td align="center">Depositos en Efectivo</td>
	      <td align="right"><?php echo number_format($row['efectivos'],0);?></td>
	      <td align="right"><?php echo number_format($row['efectivos_monto'],2);?></td>
	    </tr>
	    <tr>
	      <td align="center">Banco Tarjeta de Credito y Debito</td>
	      <td align="right"><?php echo number_format($row['bancos'],0);?></td>
	      <td align="right"><?php echo number_format($row['bancos_monto'],2);?></td>
	    </tr>
		<tr>
			<th style="text-align: left; border-top: 2px solid #000000;">TOTAL</th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($row['creditos']+$row['efectivos']+$row['bancos'],0);?></th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($row['creditos_monto']+$row['efectivos_monto']+$row['bancos_monto'],2);?></th>
		</tr>
		<tr>
	      <td align="center">Compra de Vales Anticipados en Efectivo</td>
	      <td align="right"><?php echo number_format($row['pa_cant_efectivo'],0);?></td>
	      <td align="right"><?php echo number_format($row['pa_monto_efectivo'],2);?></td>
	    </tr>
	    <tr>
	      <td align="center">Compra de Vales Anticipados en Banco</td>
	      <td align="right"><?php echo number_format($row['pa_cant_banco'],0);?></td>
	      <td align="right"><?php echo number_format($row['pa_monto_banco'],2);?></td>
	    </tr>
		<tr>
			<th style="text-align: left; border-top: 2px solid #000000;">TOTAL</th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($row['pa_cant_banco']+$row['pa_cant_efectivo'],0);?></th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($row['pa_monto_banco']+$row['pa_monto_efectivo'],2);?></th>
		</tr>
		<tr>
			<th style="text-align: left; border-top: 2px solid #000000;">TOTAL VERIFICACIONES PAGADAS</th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($row['creditos']+$row['efectivos']+$row['bancos']+$row['pa_cant_efectivo']+$row['pa_cant_banco'],0);?></th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($row['creditos_monto']+$row['efectivos_monto']+$row['bancos_monto']+$row['pa_monto_efectivo']+$row['pa_monto_banco'],2);?></th>
		</tr>
		<tr>
	      <td align="center">Reposiciones</td>
	      <td align="right"><?php echo number_format($row['reposiciones'],0);?></td>
	      <td align="right"><?php echo number_format($row['reposiciones_monto'],2);?></td>
	    </tr>
	    <tr>
	      <td align="center">Copias en Bancos</td>
	      <td align="right">&nbsp;</td>
	      <td align="right"><?php echo number_format($row['copias_banco'],2);?></td>
	    </tr>
	    <tr>
			<th style="text-align: left; border-top: 2px solid #000000;">TOTAL</th>
			<th style="text-align: right; border-top: 2px solid #000000;">&nbsp;</th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($row['creditos_monto']+$row['efectivos_monto']+$row['bancos_monto']+$row['pa_monto_efectivo']+$row['pa_monto_banco']+$row['reposiciones_monto']+$row['copias_banco'],2);?></th>
		</tr>
		<tr>
	      <td align="center">Devoluciones</td>
	      <td align="right"><?php echo number_format($row['devoluciones'],0);?></td>
	      <td align="right"><?php echo number_format($row['devoluciones_monto'],2);?></td>
	    </tr>
	    <tr>
			<th style="text-align: left; border-top: 2px solid #000000;">TOTAL</th>
			<th style="text-align: right; border-top: 2px solid #000000;">&nbsp;</th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($row['creditos_monto']+$row['efectivos_monto']+$row['bancos_monto']+$row['pa_monto_efectivo']+$row['pa_monto_banco']+$row['reposiciones_monto']+$row['copias_banco']-$row['devoluciones_monto'],2);?></th>
		</tr>
	  </tbody>
	</table>
	

<?php
}

?>