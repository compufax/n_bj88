<?php
error_reporting(E_ERROR | E_PARSE);
require_once('cnx_db.php');
require_once('globales.php');

function obtener_informacion($datos){
	if($_SESSION['CveUsuario']==1) {
	    $res = mysql_query("SELECT a.cve,a.numero, a.nombre FROM plazas a WHERE a.estatus!='I' ORDER BY a.lista, a.numero, a.nombre");
	}
	elseif($_SESSION['TipoUsuario']==1){
	    $res = mysql_query("SELECT a.cve,a.numero,a.nombre FROM plazas a WHERE a.estatus!='I' ORDER BY a.lista, a.numero, a.nombre");
	}
	else{
	    $res = mysql_query("SELECT a.cve,a.numero,a.nombre FROM plazas a INNER JOIN usuario_accesos b ON a.cve=b.plaza AND b.usuario='{$_SESSION['CveUsuario']}' AND b.menu=56 AND b.acceso>0 WHERE a.estatus!='I' GROUP BY a.cve ORDER BY a.lista, a.numero, a.nombre");
	}
	$plaza = array();
	while($row = mysql_fetch_assoc($res)){
		$plaza[] = $row['cve'];
	}
	$resultado = array();
	$select = "SELECT a.cve as cveasistencia, a.estatus, b.cve, b.rfc, b.puesto, a.plaza, b.nombre, SUM(IF(a.estatus > 0, 1, 0)) as asistencias, c.numero, c.nombre as nomplaza, d.nombre as nompuesto
		FROM asistencia a 
		INNER JOIN personal b ON a.personal=b.cve 
		INNER JOIN plazas c ON c.cve = b.plaza
		INNER JOIN puestos d ON d.cve=b.puesto
		WHERE a.fecha = CURDATE() AND b.administrativo != 1 AND b.plaza IN (".implode(',',$plaza).")";
	if ($_POST['busquedanombre']!="") { $select.=" AND b.nombre LIKE '%{$_POST['busquedanombre']}%'"; }
	if ($_POST['busquedanumero']!="") { $select.=" AND b.cve='{$_POST['busquedanumero']}'"; }
	if ($_POST['busquedapuesto']!="") { $select.=" AND b.puesto='{$_POST['busquedapuesto']}'"; }
	if ($_POST['busquedaplaza']!="") { $select.=" AND a.plaza='{$_POST['busquedaplaza']}'"; }
	if ($_POST['busquedaestatus']!="") { $select.=" AND b.estatus='{$_POST['busquedaestatus']}'"; }
	$select.=" GROUP BY b.cve ORDER BY b.cve";
	$res = mysql_query($select);
	while($row = mysql_fetch_assoc($res)){
		$renglon = array();
		$renglon['plaza'] = $row['numero'].' '.$row['nomplaza'];
		$renglon['numero'] = $row['cve'];
		$renglon['nombre'] = $row['nombre'];
		$renglon['rfc'] = $row['rfc'];
		$renglon['puesto'] = $row['nompuesto'];
		$renglon['asistio'] = ($row['asistencias']>0) ? 'SI':'NO';
		$res1=mysql_query("SELECT RIGHT(fechahora, 8) as hora, TIME_TO_SEC(RIGHT(fechahora, 8)) as segundos  FROM checada_lector WHERE cvepersonal = '{$row['cve']}' AND DATE(fechahora)=CURDATE() ORDER BY fechahora");
		$row1 = mysql_fetch_array($res1);
		$hora_entrada_trabajo = $row1['hora'];

		$row1 = mysql_fetch_array($res1);
		$hora_salida_trabajo = $row1['hora'];
		$renglon['entrada'] = $hora_entrada_trabajo;
		$renglon['salida'] = $hora_salida_trabajo;
		$resultado[] = $renglon;
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
			<label class="col-sm-2 col-form-label">Plaza</label>
			<div class="col-sm-4">
            	<select id="busquedaplaza" class="form-control"><option value="">Todos</option>
            	<?php
            	//$res1 = mysql_query("SELECT cve, numero, nombre FROM plazas ORDER BY numero, nombre");
            	if($_SESSION['CveUsuario']==1) {
				    $res1 = mysql_query("SELECT a.cve,a.numero, a.nombre FROM plazas a WHERE a.estatus!='I' ORDER BY a.lista, a.numero, a.nombre");
				}
				elseif($_SESSION['TipoUsuario']==1){
				    $res1 = mysql_query("SELECT a.cve,a.numero,a.nombre FROM plazas a WHERE a.estatus!='I' ORDER BY a.lista, a.numero, a.nombre");
				}
				else{
				    $res1 = mysql_query("SELECT a.cve,a.numero,a.nombre FROM plazas a INNER JOIN usuario_accesos b ON a.cve=b.plaza AND b.usuario='{$_SESSION['CveUsuario']}' AND b.menu=101 AND b.acceso>0 WHERE a.estatus!='I' GROUP BY a.cve ORDER BY a.lista, a.numero, a.nombre");
				}
				while($row1=mysql_fetch_array($res1)){
					echo '<option value="'.$row1['cve'].'">'.utf8_encode($row1['numero'].' '.$row1['nombre']).'</option>';
				}
				?>	
            	</select>
        	</div>
        	<label class="col-sm-2 col-form-label">No. Personal</label>
			<div class="col-sm-4">
            	<input type="text" class="form-control" id="busquedanumero" placeholder="No. Personal">
        	</div>
        </div>
		<div class="form-group row">
			<label class="col-sm-2 col-form-label">Nombre</label>
			<div class="col-sm-4">
            	<input type="text" class="form-control" id="busquedanombre" placeholder="Nombre">
        	</div>
        	<label class="col-sm-2 col-form-label">Puesto</label>
			<div class="col-sm-4">
            	<select id="busquedapuesto" class="form-control"><option value="">Todos</option>
            	<?php
            	$res1 = mysql_query("SELECT cve, nombre FROM puestos ORDER BY nombre");
				while($row1=mysql_fetch_array($res1)){
					echo '<option value="'.$row1['cve'].'">'.utf8_encode($row1['nombre']).'</option>';
				}
				?>	
            	</select>
        	</div>
        </div>
        <div class="form-group row">
        	<label class="col-sm-2 col-form-label">Estatus</label>
			<div class="col-sm-4">
            	<select id="busquedaestatus" class="form-control"><option value="">Todos</option>
            		<option value="1" selected>Alta</option>
            		<option value="2">Baja</option>
            		<option value="3">Inactivo</option>
            	</select>
        	</div>
        </div>
        <div class="form-group row">
        	<div class="col-sm-12" align="center">
        		<button type="button" class="btn btn-primary" onClick="buscar();">
	            	Buscar
	        	</button>&nbsp;
	        	
        	</div>
        </div>
    </div>
</div>
<div class="row" id="resultadocorte">
	
</div>
<script>
	function buscar(){
		$.ajax({
		  url: 'asistencia_personal.php',
		  type: "POST",
		  data: {
			menu: $('#cvemenu').val(),
			cmd: 10,
			cveusuario: $('#cveusuario').val(),
			busquedaplaza: $('#busquedaplaza').val(),
			busquedanumero: $('#busquedanumero').val(),
			busquedanombre: $('#busquedanombre').val(),
			busquedapuesto: $('#busquedapuesto').val(),
			busquedaestatus: $('#busquedaestatus').val(),
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
?>
	<table class="table">
	  <thead>
	    <tr>
	      <th scope="col" style="text-align: center;">Plaza</th>
	      <th scope="col" style="text-align: center;">No.</th>
		  <th scope="col" style="text-align: center;">Nombre</th> 
		  <th scope="col" style="text-align: center;">RFC</th> 
	      <th scope="col" style="text-align: center;">Puesto</th> 
	      <th scope="col" style="text-align: center;">Asistio</th>
	      <th scope="col" style="text-align: center;">Hora Entrada</th>  
	      <th scope="col" style="text-align: center;">Hora Salida</th> 
	    </tr>
	  </thead>
	  <tbody>
	<?php
		$totales = array();
		$i = 0;
		foreach($res as $row){
	?>
	    <tr>
	      <td align="left"><?php echo utf8_encode($row['plaza']);?></td>
	      <td align="center"><?php echo $row['numero'];?></td>
	      <td align="left"><?php echo utf8_encode($row['nombre']);?></td>
	      <td align="center"><?php echo $row['rfc'];?></td>
	      <td align="left"><?php echo utf8_encode($row['puesto']);?></td>
	      <td align="center"><?php echo $row['asistio'];?></td>
	      <td align="center"><?php echo $row['entrada'];?></td>
	      <td align="center"><?php echo $row['salida'];?></td>
	    </tr>
	<?php
		}
	?>
	  </tbody>
	</table>
	

<?php
}

?>