<?php
error_reporting(E_ERROR | E_PARSE);
require_once('cnx_db.php');
require_once('globales.php');

function obtener_informacion($datos){
	$resultado = array();
	$res = mysql_query("SELECT a.fecha, a.motivo, a.cargo, a.abono, a.obs FROM 
		(SELECT CONCAT(a.fecha, ' ', a.hora) as fecha, CONCAT('Recibo de Salida ', a.cve,', Motivo: ', b.nombre) as motivo, a.monto as cargo, 0 as abono, a.concepto as obs FROM recibos_salida a INNER JOIN motivos b ON b.cve = a.motivo WHERE a.plaza='{$datos['cveplaza']}' AND a.estatus != 'C' AND a.fecha BETWEEN '{$datos['busquedafechaini']}' AND '{$datos['busquedafechafin']}'
		 UNION ALL 
		 SELECT CONCAT(fecha, ' ', hora) as fecha, CONCAT('Reembolso ', cve) as motivo, 0 as cargo, monto as abono, obs FROM reembolsos WHERE plaza='{$datos['cveplaza']}' AND estatus!='C' AND fecha BETWEEN '{$datos['busquedafechaini']}' AND '{$datos['busquedafechafin']}'
		) a ORDER BY a.fecha");
	while($row=mysql_fetch_array($res)){
		$resultado[] = $row;
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
		  url: 'edo_cuenta_gastos.php',
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
	      <th scope="col" style="text-align: center;">Fecha</th>
	      <th scope="col" style="text-align: center;">Motivo</th>
	      <th scope="col" style="text-align: center;">Cargo</th>
	      <th scope="col" style="text-align: center;">Abono</th>
	      <th scope="col" style="text-align: center;">Saldo</th>
	      <th scope="col" style="text-align: center;">Observaciones</th>
	    </tr>
	  </thead>
	  <tbody>
	<?php
		$saldo=0;
		$res = mysql_query("SELECT SUM(monto) as monto FROM reembolsos WHERE plaza='{$_POST['cveplaza']}' AND estatus!='C' AND fecha_mov<'{$_POST['busquedafechaini']}'");
		$row = mysql_fetch_assoc($res);
		$saldo+=$row['monto'];
		$res = mysql_query("SELECT SUM(monto) as monto FROM recibos_salida WHERE plaza='{$_POST['cveplaza']}' AND estatus!='C' AND fecha<'{$_POST['busquedafechaini']}'");
		$row = mysql_fetch_assoc($res);
		$saldo-=$row['monto'];
	?>
		<tr>
	      <td align="center"><?php echo $_POST['busquedafechaini'];?></td>
	      <td align="left">Saldo Anterior</td>
	      <td align="right"></td>
	      <td align="right"></td>
	      <td align="right"><?php echo number_format($saldo,2);?></td>
	      <td align="left"></td>
	    </tr>

	<?php

		$i = 0;
		foreach($res as $row){
			$saldo += $row['abono']-$row['cargo'];
	?>
	    <tr>
	      <td align="center"><?php echo $row['fecha'];?></td>
	      <td align="left"><?php echo utf8_encode($row['motivo']);?></td>
	      <td align="right"><?php echo number_format($row['cargo'],2);?></td>
	      <td align="right"><?php echo number_format($row['abono'],2);?></td>
	      <td align="right"><?php echo number_format($saldo,2);?></td>
	      <td align="left"><?php echo utf8_encode($row['obs']);?></td>
	    </tr>
	<?php
		$i++;
		$cargo+=$row['cargo'];
		$abono+=$row['abono'];
		
	}
	?>
		<tr>
			<th style="text-align: right;" colspan="2">Totales:</th>
			<th style="text-align: right;"><?php echo number_format($cargo,2);?></th>
			<th style="text-align: right;"><?php echo number_format($abono,2);?></th>
			<th style="text-align: right;" colspan="2">&nbsp;</th>
		</tr>
	  </tbody>
	</table>
	

<?php
}

?>