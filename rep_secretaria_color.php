<?php
error_reporting(E_ERROR | E_PARSE);
require_once('cnx_db.php');
require_once('globales.php');

function obtener_informacion($datos){
	global $array_indices_color;
	$resultado = array();
	$filtro = "";
	if (is_array($datos['busquedaplaza'])){
		$filtro .= " AND plaza IN (".implode(",", $datos['busquedaplaza']).")";
	}
	if($datos['busquedafechaini']!=''){
		$filtro.= " AND fecha>='{$datos['busquedafechaini']}'";
	}
	if($datos['busquedafechafin']!=''){
		$filtro.= " AND fecha<='{$datos['busquedafechafin']}'";
	}

	$res = mysql_query("SELECT engomado, fecha, fn_ultimonumero(placa) as numero, COUNT(cve) as cantidad FROM certificados WHERE estatus!='C'{$filtros} GROUP BY engomado, fecha, numero");
	while($row = mysql_fetch_array($res)){
		$resultado[$row['engomado']][$row['fecha']]['color_'.$array_indices_color[$row['numero']]]+=$row['cantidad'];
	}
	$res = mysql_query("SELECT engomado, fecha, COUNT(cve) as cantidad FROM certificados_cancelados WHERE estatus!='C'{$filtros} GROUP BY engomado, fecha");
	while($row = mysql_fetch_array($res)){
		$resultado[$row['engomado']][$row['fecha']]['cancelados']+=$row['cantidad'];
	}
	return $resultado;
}
require_once('validarloging.php');

if($_POST['cmd']==0){
	$nivelUsuario = nivelUsuario();
?>

<div class="row justify-content-center">
	<div class="col-xl-12 col-lg-12 col-md-12">
		<div class="form-group row">
			<label class="col-sm-2 col-form-label">Fecha Inicio</label>
			<div class="col-sm-4">
            	<input type="date" class="form-control" id="busquedafechaini" name="busquedafechaini" value="<?php echo date("Y-m-d", strtotime("-6 day"));?>" placeholder="Fecha Inicio">
        	</div>
			<label class="col-sm-2 col-form-label">Fecha Fin</label>
			<div class="col-sm-4">
            	<input type="date" class="form-control" id="busquedafechafin" name="busquedafechafin" value="<?php echo date('Y-m-d');?>" placeholder="Fecha Fin">
        	</div>
        </div>
        <div class="form-group row">
			<label class="col-sm-2 col-form-label">Plaza</label>
			<div class="col-sm-4">
            	<select multiple name="busquedaplaza[]" class="form-control" data-container="body" data-live-search="true" title="Plaza" data-hide-disabled="true" data-actions-box="true" data-virtual-scroll="false" id="busquedaplaza">
            	<?php
            	$res1 = mysql_query("SELECT cve, numero, nombre FROM plazas WHERE estatus!='I'  ORDER BY lista, numero, nombre");
				while($row1=mysql_fetch_array($res1)){
					echo '<option value="'.$row1['cve'].'" selected>'.$row1['numero'].' '.$row1['nombre'].'</option>';
				}
				?>
            	</select>
            	<script>
					$("#busquedaplaza").selectpicker();	
				</script>
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
		  url: 'rep_secretaria_color.php',
		  type: "POST",
		  data: {
			menu: $('#cvemenu').val(),
			cmd: 10,
			cveusuario: $('#cveusuario').val(),
			busquedafechaini: $('#busquedafechaini').val(),
			busquedafechafin: $('#busquedafechafin').val(),
			busquedaplaza: $("#busquedaplaza").val(),
    		cvemenu: $('#cvemenu').val(),
    		cveplaza: $('#cveplaza').val()
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
	$resultado = obtener_informacion($_POST);
	$res = mysql_query("SELECT cve, nombre FROM engomados WHERE entrega=1 ORDER BY nombre");
	while($row = mysql_fetch_assoc($res)){
?>
	<h2><?php echo $row['nombre'];?></h2>
	<table class="table">
	  <thead>
	    <tr>
	      <th scope="col" style="text-align: center;">Dia</th>
	      <th scope="col" style="text-align: center;">&nbsp;</th>
	      <?php 
	      	foreach ($array_color as $k => $color){
	      ?>
	      		<th scope="col" style="text-align: center;" bgcolor="#<?php echo $color;?>"><?php echo implode(" y ", $array_valores_color[$k]);?></th>
	      <?php
	      	}
	      ?>
		  <th scope="col" style="text-align: center;">Cancelados</th> 
		  <th scope="col" style="text-align: center;">Utilizados</th> 
	      <th scope="col" style="text-align: center;">Total Utilizados</th> 
	    </tr>
	  </thead>
	  <tbody>
	  	<?php
		  	$fecha = $_POST['busquedafechaini'];
			$array_totales=array();
			while($fecha<=$_POST['busquedafechafin']){
				$arfecha=explode("-",$fecha);
				$dia=date("w", mktime(0, 0, 0, intval($arfecha[1]), intval($arfecha[2]), $arfecha[0]));
		?>
				<tr>
					<td align="center"><?php echo substr($fecha,8,2); ?></td>
					<td align="center"><?php echo $array_dias_semana[$dia]; ?></td>
				<?php
					$c=0;
					$total=0;
					foreach ($array_color as $k => $v){
				?>
						<td align="center"><a href="#" onClick="atcr('rep_secretaria_color.php', '', 11, '<?php echo $row['cve'].'|'.$k.'|'.$fecha;?>')">
							<?php 
								echo number_format($resultado[$row['cve']][$fecha]['color_'.$k],0); 
								$array_totales[$c]+=$resultado[$row['cve']][$fecha]['color_'.$k];$c++;
								$total+=$resultado[$row['cve']][$fecha]['color_'.$k];
							?></a></td>
						<?php
					}
				?>
					<td align="center"><a href="#" onClick="atcr('rep_secretaria_color.php', '', 12, '<?php echo $row['cve'].'|'.$fecha;?>')">
					<?php 
						echo number_format($resultado[$row['cve']][$fecha]['cancelados'],0); 
						$array_totales[$c]+=$resultado[$row['cve']][$fecha]['cancelados'];$c++;
					?></a></td>
					<td align="center"><a href="#" onClick="atcr('rep_secretaria_color.php', '', 11, '<?php echo $row['cve'].'|-1|'.$fecha;?>')">
					<?php 
						echo number_format($total,0); 
						$array_totales[$c]+=$total;$c++;
					?></a></td>
					<td align="center">
					<?php 
						echo number_format($resultado[$row['cve']][$fecha]['cancelados']+$total,0); 
						$array_totales[$c]+=$resultado[$row['cve']][$fecha]['cancelados']+$total;$c++;
					?></td>
				</tr>

		<?php
				$fecha=date("Y-m-d", strtotime("+ 1 day", strtotime($fecha)));
			}
		?>
	  </tbody>
	  <tfoot>
	  	<tr>
	  		<th colspan="2" style="text-align: left;">Totales</th>
	  		<?php
	  			foreach($array_totales as $total){
	  		?>
	  			<th style="text-align: center;"><?php echo number_format($total, 0);?></th>
	  		<?php
	  			}
	  		?>
	  	</tr>
	  </tfoot>
	</table>

<?php
	}
}

if($_POST['cmd']==11){
	$datos = explode('|', $_POST['reg']);
	$array_valores_color[-1] = array("Todos");
?>
<div class="row justify-content-center">
	<div class="col-xl-12 col-lg-12 col-md-12">
        <div class="form-group row">
        	<div class="col-sm-12" align="center">
	        		<button type="button" class="btn btn-primary" onClick="atcr('rep_secretaria_color.php','',0,0);">
		            	Volver
		        	</button>
        	</div>
        </div>
    </div>
</div>
	<h2>Detallado de <?php echo implode(' y ', $array_valores_color[$datos[1]]);?> del dia <?php echo mostrar_fechas($datos[2]);?></h2>
	<table class="table">
	  <thead>
	    <tr>
	      <th scope="col" style="text-align: center;">Plaza</th>
	      <th scope="col" style="text-align: center;">Folio Entrega</th>
		  <th scope="col" style="text-align: center;">Ticket</th> 
		  <th scope="col" style="text-align: center;">Fecha</th> 
	      <th scope="col" style="text-align: center;">Placa</th> 
	      <th scope="col" style="text-align: center;">Tipo de Combustible</th> 
	      <th scope="col" style="text-align: center;">Tipo de Verificaci&oacute;n</th> 
	      <th scope="col" style="text-align: center;">T&eacute;cnico</th> 
	      <th scope="col" style="text-align: center;">Certificado</th> 
	      <th scope="col" style="text-align: center;">Usuario</th> 
	    </tr>
	  </thead>
	  <tbody>
<?php
	$filtro = "";
	if (is_array($_POST['busquedaplaza'])){
		$filtro .= " AND a.plaza IN (".implode(",", $_POST['busquedaplaza']).")";
	}
	if ($datos[1] >= 0){
		$filtro .= " AND fn_ultimonumero(a.placa) IN (".implode(',', $array_valores_color[$datos[1]]).")";
	}
	$c=0;
	$res = mysql_query("SELECT b.numero as numplaza, a.cve, a.ticket, a.fecha, a.placa, d.nombre as nomtipocombustible, e.nombre as nomengomado, f.nombre as nomtecnico, a.certificado, g.usuario FROM certificados a INNER JOIN plazas b ON b.cve = a.plaza INNER JOIN cobro_engomado c ON c.plaza = a.plaza AND c.cve = a.ticket INNER JOIN tipo_combustible d ON d.cve = c.tipo_combustible INNER JOIN engomados e ON e.cve = a.engomado INNER JOIN tecnicos f ON f.plaza = a.plaza AND f.cve = a.tecnico INNER JOIN usuarios g on g.cve = a.usuario WHERE a.engomado = {$datos[0]} AND a.fecha='{$datos[2]}' AND a.estatus!='C'{$filtro} ORDER BY a.fecha, a.cve");
	while($row = mysql_fetch_assoc($res)){
?>
		<tr>
			<td><?php echo $row['numplaza'];?></td>
			<td align="center"><?php echo $row['cve'];?></td>
			<td align="center"><?php echo $row['ticket'];?></td>
			<td align="center"><?php echo $row['fecha'];?></td>
			<td align="center"><?php echo $row['placa'];?></td>
			<td align="left"><?php echo $row['nomtipocombustible'];?></td>
			<td align="left"><?php echo $row['nomengomado'];?></td>
			<td align="left"><?php echo $row['nomtecnico'];?></td>
			<td align="center"><?php echo $row['certificado'];?></td>
			<td align="left"><?php echo $row['usuario'];?></td>
		</tr>

<?php
		$c++;
	}
?>
	<tfoot>
	  	<tr>
	  		<th colspan="10" style="text-align: left;"><?php echo $c;?> Registro(s)</th>
	  	</tr>
	  </tfoot>
	</table>	
<?php
}


if($_POST['cmd']==12){
	$datos = explode('|', $_POST['reg']);
?>
<div class="row justify-content-center">
	<div class="col-xl-12 col-lg-12 col-md-12">
        <div class="form-group row">
        	<div class="col-sm-12" align="center">
	        		<button type="button" class="btn btn-primary" onClick="atcr('rep_secretaria_color.php','',0,0);">
		            	Volver
		        	</button>
        	</div>
        </div>
    </div>
</div>
	<h2>Detallado de Cancelados del dia <?php echo mostrar_fechas($datos[1]);?></h2>
	<table class="table">
	  <thead>
	    <tr>
	      <th scope="col" style="text-align: center;">Plaza</th>
	      <th scope="col" style="text-align: center;">Folio Cancelacion</th>
	      <th scope="col" style="text-align: center;">Tipo de Verificaci&oacute;n</th> 
	      <th scope="col" style="text-align: center;">Certificado</th> 
	      <th scope="col" style="text-align: center;">Usuario</th> 
	    </tr>
	  </thead>
	  <tbody>
<?php
	$filtro = "";
	if (is_array($_POST['busquedaplaza'])){
		$filtro .= " AND a.plaza IN (".implode(",", $_POST['busquedaplaza']).")";
	}
	$c=0;
	$res = mysql_query("SELECT b.numero as numplaza, a.cve, a.fecha, e.nombre as nomengomado, a.certificado, g.usuario FROM certificados a INNER JOIN plazas b ON b.cve = a.plaza INNER JOIN engomados e ON e.cve = a.engomado INNER JOIN usuarios g on g.cve = a.usuario WHERE a.engomado = {$datos[0]} AND a.fecha='{$datos[1]}' AND a.estatus!='C'{$filtro} ORDER BY a.fecha, a.cve");
	while($row = mysql_fetch_assoc($res)){
?>
		<tr>
			<td><?php echo $row['numplaza'];?></td>
			<td align="center"><?php echo $row['cve'];?></td>
			<td align="center"><?php echo $row['fecha'];?></td>
			<td align="left"><?php echo $row['nomengomado'];?></td>
			<td align="center"><?php echo $row['certificado'];?></td>
			<td align="left"><?php echo $row['usuario'];?></td>
		</tr>

<?php
		$c++;
	}
?>
	<tfoot>
	  	<tr>
	  		<th colspan="6" style="text-align: left;"><?php echo $c;?> Registro(s)</th>
	  	</tr>
	  </tfoot>
	</table>	
<?php
}
?>