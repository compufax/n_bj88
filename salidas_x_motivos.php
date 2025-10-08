<?php
error_reporting(E_ERROR | E_PARSE);
require_once('cnx_db.php');
require_once('globales.php');

function obtener_informacion($datos){
	$resultado = array();
	$res = mysql_query("SELECT b.cve, b.nombre, a.importe FROM (SELECT motivo, SUM(monto) as importe FROM recibo_salida WHERE plaza='{$datos['cveplaza']}' AND estatus!='C' AND fecha BEWTEEN '{$datos['busquedafechaini']}' AND '{$datos['busquedafechafin']}' GROUP BY motivo) a INNER JOIN motivos b ON b.cve = a.motivo ORDER BY b.nombre");
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
		  url: 'salidas_x_motivos.php',
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
	      <th scope="col" style="text-align: center;">Ver</th>
	      <th scope="col" style="text-align: center;">Motivo</th>
	      <th scope="col" style="text-align: center;">Importe</th>
	    </tr>
	  </thead>
	  <tbody>
	<?php
		$total = 0;
		$i = 0;
		foreach($res as $row){
	?>
	    <tr>
	      <td align="center"><span class="btn btn-circle btn-info" style="cursor:pointer;"><i class="fas fa-eye" onClick="atcr('salidas_x_motivos.php','',1,<?php echo $row['cve'];?>)" title="Ver"></i></span></td>
	      <td align="left"><?php echo $row['nombre'];?></td>
	      <td align="right"><?php echo number_format($row['importe'],2);?></td>
	    </tr>
	<?php
		$i++;
		$total+=$row['importe'];
		
	}
	?>
		<tr>
			<th style="text-align: right;" colspan="2">Totales:</th>
			<th style="text-align: right;"><?php echo number_format($total,2);?></th>
		</tr>
	  </tbody>
	</table>
	

<?php
}

if($_POST['cmd']==1){
	$Motivo = mysql_fetch_assoc(mysql_query("SELECT nombre FROM motivos WHERE cve='{$_POST['reg']}'"));
?>

<h2>Detalle <?php echo $Motivo['nombre'];?> del <?php echo mostrar_fechas($_POST['busquedafechaini']); ?> al <?php echo mostrar_fechas($_POST['busquedafechafin']);?></h2>
<div class="row" id="resultadocorte">
	<table class="table">
	  <thead>
	    <tr>
	      <th scope="col" style="text-align: center;">Folio</th>
	      <th scope="col" style="text-align: center;">Fecha</th>
	      <th scope="col" style="text-align: center;">Beneficiario</th>
	      <th scope="col" style="text-align: center;">Monto</th>
	      <th scope="col" style="text-align: center;">Concepto</th>
	      <th scope="col" style="text-align: center;">Usuario</th>
	    </tr>
	  </thead>
	  <tbody>
	  	<?php

	  	$res = mysql_query("SELECT a.cve, a.fecha, b.nombre, a.monto, a.concepto, c.usuario FROM recibos_salida a INNER JOIN beneficiarios b ON b.cve = a.beneficiario INNER JOIN usuarios c ON c.cve = a.usuario WHERE a.plaza='{$_POST['cveplaza']}' AND a.estatus!='C' AND a.motivo='{$_POST['reg']}' AND a.fecha BETWEEN '{$_POST['busquedafechaini']}' AND '{$_POST['busquedafechafin']}' ORDER BY a.cve DESC");
	  	$total=0;
	  	$i=0;
	  	while($row = mysql_fetch_assoc($res)){
	  	?>
	  	<tr>
	      <td align="center"><?php echo $row['cve']; ?></td>
	      <td align="center"><?php echo mostrar_fechas($row['fecha']);?></td>
	      <td align="left"><?php echo utf8_encode($row['nombre']); ?></td>
	      <td align="right"><?php echo number_format($row['monto'],2);?></td>
	      <td align="left"><?php echo utf8_encode($row['concepto']); ?></td>
	      <td align="left"><?php echo utf8_encode($row['usuario']); ?></td>
	    </tr>

	  	<?php
	  		$i++;
	  		$total+=$row['monto'];
	  	}
	  	?>

	  	<tr>
			<th style="text-align: left;" colspan="2"><?php echo $i;?> Registro(s)</th>
			<th style="text-align: right;">Totales:</th>
			<th style="text-align: right;"><?php echo number_format($total,2);?></th>
			<th style="text-align: left;" colspan="2">&nbsp;</th>
		</tr>
	  </tbody>

	</table>
</div>

<?php
}
?>