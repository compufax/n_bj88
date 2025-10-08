<?php
error_reporting(E_ERROR | E_PARSE);
require_once('cnx_db.php');
require_once('globales.php');

function obtener_informacion($datos){
	$resultado = array();
	$res = mysql_query("SELECT a.cve, a.nombre FROM engomados a inner join engomados_plazas b on a.cve = b.engomado WHERE b.entrega=1 AND b.plaza = '{$datos['cveplaza']}' ORDER BY a.nombre");
	while($row=mysql_fetch_array($res)){
		$resultado[$row['cve']] = array('nombre' => $row['nombre']);
	}
	$resultado['-1'] = array('nombre' => 'Voluntarios');
	
	$res = mysql_query("SELECT a.engomado, b.tipo_combustible, SUM(IF(a.engomado!=19,1,0)) as aprobados, SUM(IF(a.engomado=19,1,0)) as rechazos, SUM(IF(b.multa=1,1,0)) as multa, SUM(IF(b.voluntario=1 AND a.engomado!=19,1,0)) as aprobados_voluntarios, SUM(IF(b.voluntario=1 AND a.engomado=19,1,0)) as rechazos_voluntarios
						FROM certificados a 
						INNER JOIN cobro_engomado b ON b.plaza = a.plaza AND b.cve = a.ticket
						WHERE a.plaza = '{$datos['cveplaza']}' AND a.fecha BETWEEN '{$datos['busquedafechaini']}' AND '{$datos['busquedafechafin']}' AND a.estatus!='C'
						GROUP BY a.engomado, b.tipo_combustible");
	while($row = mysql_fetch_assoc($res)){
		$resultado[$row['engomado']][$row['tipo_combustible']] = array('aprobados' => $row['aprobados'], 'rechazos' => $row['rechazos']);
		$resultado[$row['engomado']]['multas'] += $row['multa'];
		$resultado['-1'][$row['tipo_combustible']]['aprobados'] += $row['aprobados_voluntarios'];
		$resultado['-1'][$row['tipo_combustible']]['rechazos'] += $row['rechazos_voluntarios'];

	}

	$res = mysql_query("SELECT a.engomado, a.tipo_combustible, SUM(IF(a.engomado!=19,1,0)) as aprobados, SUM(IF(a.engomado=19,1,0)) as rechazos
						FROM certificados_cancelados a 
						WHERE a.plaza = '{$datos['cveplaza']}' AND a.fecha BETWEEN '{$datos['busquedafechaini']}' AND '{$datos['busquedafechafin']}' AND a.estatus!='C'
						GROUP BY a.engomado, a.tipo_combustible");
	while($row = mysql_fetch_assoc($res)){
		$resultado[$row['engomado']][$row['tipo_combustible']]['c_aprobados'] = $row['aprobados'];
		$resultado[$row['engomado']][$row['tipo_combustible']]['c_rechazos'] = $row['rechazos'];

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
		  url: 'concentradogeneral.php',
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
	$resCombustibles = mysql_query("SELECT cve, nombre FROM tipo_combustible ORDER BY nombre");
	$Combustibles = array();
	while($rowCombustibles = mysql_fetch_assoc($resCombustibles)){
		$Combustibles[$rowCombustibles['cve']] = $rowCombustibles['nombre'];
	}
	$res = obtener_informacion($_POST);
	foreach($res as $row) {
?>
	<h3><?php echo $row['nombre'];?>
	<table class="table">
	  <thead>
	    <tr>
	      <th scope="col" style="text-align: center;">Utilizados</th>
	      <th scope="col" style="text-align: center;">Aprobados</th>
	      <th scope="col" style="text-align: center;">Rechazados</th>
		  <th scope="col" style="text-align: center;">Total</th> 
		  <?php if($row['nombre']!='Voluntarios'){ ?>
	      <th scope="col" style="text-align: center;">Cancelados</th> 
	      <th scope="col" style="text-align: center;">Aprobados</th> 
	      <th scope="col" style="text-align: center;">Rechazados</th> 
	      <th scope="col" style="text-align: center;">Total</th> 
	      <th scope="col" style="text-align: center;">Totales</th> 
	      <?php } ?>
	    </tr>
	  </thead>
	  <tbody>
	<?php
		$totales = array();
		$i = 0;
		foreach($Combustibles as $idCombustible => $nomCombustible){
	?>
	    <tr>
	      <td align="center"><?php echo $nomCombustible;?></td>
	      <td align="right"><?php echo number_format($row[$idCombustible]['aprobados'],0);?></td>
	      <td align="right"><?php echo number_format($row[$idCombustible]['rechazos'],0);?></td>
	      <td align="right"><?php echo number_format($row[$idCombustible]['aprobados']+$row[$idCombustible]['rechazos'],0);?></td>
	    <?php if($row['nombre']!='Voluntarios'){ ?>
	      <td align="right"><?php echo $nomCombustible;?></td>
	      <td align="right"><?php echo number_format($row[$idCombustible]['c_aprobados'],0);?></td>
	      <td align="right"><?php echo number_format($row[$idCombustible]['c_rechazos'],0);?></td>
	      <td align="right"><?php echo number_format($row[$idCombustible]['c_aprobados']+$row[$idCombustible]['c_rechazos'],0);?></td>
	      <td align="right"><?php echo number_format($row[$idCombustible]['aprobados']+$row[$idCombustible]['rechazos']+$row[$idCombustible]['c_aprobados']+$row[$idCombustible]['c_rechazos'],0);?></td>
	    <?php } ?>
	    </tr>
	<?php
			$i++;
			$totales[0]+=$row[$idCombustible]['aprobados'];
			$totales[1]+=$row[$idCombustible]['rechazos'];
			$totales[2]+=$row[$idCombustible]['aprobados']+$row[$idCombustible]['rechazos'];
			$totales[3]+=$row[$idCombustible]['c_aprobados'];
			$totales[4]+=$row[$idCombustible]['c_rechazos'];
			$totales[5]+=$row[$idCombustible]['c_aprobados']+$row[$idCombustible]['c_rechazos'];
			$totales[6]+=$row[$idCombustible]['aprobados']+$row[$idCombustible]['rechazos']+$row[$idCombustible]['c_aprobados']+$row[$idCombustible]['c_rechazos'];
		}
	?>
		<tr>
			<th style="text-align: left; border-top: 2px solid #000000;">Total</th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($totales[0],0);?></th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($totales[1],0);?></th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($totales[2],0);?></th>
			<?php if($row['nombre']!='Voluntarios'){ ?>
			<th style="text-align: right; border-top: 2px solid #000000;">Total</th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($totales[3],0);?></th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($totales[4],0);?></th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($totales[5],2);?></th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($totales[6],2);?></th>
			<?php } ?>
		</tr>
		<?php if($row['nombre']!='Voluntarios'){ ?>
		<tr>
			<th style="text-align: left; border-top: 2px solid #000000;">Multas</th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($row['multas'],0);?></th>
			<th style="text-align: right; border-top: 2px solid #000000;">Aprobados</th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($totales[0]+$totales[3],0);?></th>
			<th style="text-align: right; border-top: 2px solid #000000;">Rechazados</th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($totales[1]+$totales[4],0);?></th>
			<th style="text-align: right; border-top: 2px solid #000000;">Gran Total</th>
			<th style="text-align: right; border-top: 2px solid #000000;"><?php echo number_format($totales[6],2);?></th>
			<th style="text-align: right; border-top: 2px solid #000000;">&nbsp;</th>
		</tr>
		<?php } ?>
	  </tbody>
	</table>
	<br>
	

<?php
	}

}

?>