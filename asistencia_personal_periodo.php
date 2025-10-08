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
	    $res = mysql_query("SELECT a.cve,a.numero,a.nombre FROM plazas a INNER JOIN usuario_accesos b ON a.cve=b.plaza AND b.usuario='{$_SESSION['CveUsuario']}' AND b.menu=101 AND b.acceso>0 WHERE a.estatus!='I' GROUP BY a.cve ORDER BY a.lista, a.numero, a.nombre");
	}
	$plaza = array();
	while($row = mysql_fetch_assoc($res)){
		$plaza[] = $row['cve'];
	}
	$resultado = array();
	$select = "SELECT a.cve as cveasistencia, a.estatus, b.cve, b.rfc, b.puesto, a.plaza, b.nombre, SUM(IF(a.estatus > 0, 1, 0)) as asistencias,
		(DATEDIFF('{$datos['busquedafechaini']}','{$datos['busquedafechafin']}')+1) as diass, COUNT(a.cve) as dias, c.numero, c.nombre as nomplaza
		FROM asistencia a 
		INNER JOIN personal b ON a.personal=b.cve 
		INNER JOIN plazas c ON c.cve = b.plaza
		WHERE a.fecha BETWEEN '{$datos['busquedafechaini']}' AND '{$datos['busquedafechafin']}' AND b.administrativo != 1 AND b.plaza IN (".implode(',',$plaza).")";
	if ($_POST['busquedanombre']!="") { $select.=" AND b.nombre LIKE '%{$_POST['busquedanombre']}%'"; }
	if ($_POST['busquedanumero']!="") { $select.=" AND b.cve='{$_POST['busquedanumero']}'"; }
	if ($_POST['busquedapuesto']!="") { $select.=" AND b.puesto='{$_POST['busquedapuesto']}'"; }
	if ($_POST['busquedaplaza']!="") { $select.=" AND a.plaza='{$_POST['busquedaplaza']}'"; }
	if ($_POST['busquedaestatus']!="") { $select.=" AND b.estatus='{$_POST['busquedaestatus']}'"; }
	$select.=" GROUP BY b.cve ORDER BY b.cve";
//	echo $select;
	$res = mysql_query($select);
	while($row = mysql_fetch_assoc($res)){
		$renglon = array();
		$renglon['plaza'] = $row['numero'].' '.$row['nomplaza'];
		$renglon['numero'] = $row['cve'];
		$renglon['nombre'] = $row['nombre'];
		$renglon['rfc'] = $row['rfc'];
		$renglon['faltas'] = $row['dias']-$row['asistencias'];
		$fecha=$datos['busquedafechaini'];
		$ttrabajo=0;
		while($fecha<=$datos['busquedafechafin']){
			$AsistenciaPersonal = mysql_fetch_assoc(mysql_query("SELECT plaza FROM asistencia WHERE personal={$row['cve']} AND fecha='{$fecha}'"));
			$arfecha=explode("-",$fecha);
			$dia=date("w", mktime(0, 0, 0, intval($arfecha[1]), intval($arfecha[2]), $arfecha[0]));
			if($dia != 0){
				$res1=mysql_query("SELECT RIGHT(a.fechahora, 8) as hora, TIME_TO_SEC(RIGHT(a.fechahora, 8)) as segundos, b.plaza  FROM checada_lector a INNER JOIN series b ON b.cve = a.cvelector WHERE a.cvepersonal = '{$row['cve']}' AND DATE(a.fechahora)='{$fecha}' ORDER BY a.fechahora");
				$row1 = mysql_fetch_array($res1);
				$hora_entrada_trabajo = $row1['hora'];
				if($row1['plaza'] != $AsistenciaPersonal['plaza']){
					$hora_entrada_trabajo = '<font color="RED">'.$row1['hora'].'</font>';
				}
				$segundos_entrada_trabajo = $row1['segundos'];
				$row1 = mysql_fetch_array($res1);
				$hora_salida_trabajo = $row1['hora'];
				if($row1['plaza'] != $AsistenciaPersonal['plaza']){
					$hora_salida_trabajo = '<font color="RED">'.$row1['hora'].'</font>';
				}
				$segundos_salida_trabajo = $row1['segundos'];
				$renglon['fechas'][$fecha]['hora_entrada'] = $hora_entrada_trabajo;
				$renglon['fechas'][$fecha]['hora_salida'] = $hora_salida_trabajo;
				$tiempo_trabajo=0;
				$tiempo_comer=0;
				if($segundos_entrada_trabajo > 0 && $segundos_salida_trabajo > 0){
					$tiempo_trabajo += $segundos_salida_trabajo - $segundos_entrada_trabajo;
				}
				
				$res1 = mysql_query("SELECT SEC_TO_TIME({$tiempo_trabajo}) as tiempo_trabajo, SEC_TO_TIME({$tiempo_comer}) as tiempo_comer");
				$row1 = mysql_fetch_array($res1);
				$renglon['fechas'][$fecha]['tiempo_trabajo'] = $row1['tiempo_trabajo'];
				$ttrabajo+=$tiempo_trabajo;
			}
			$fecha=date( "Y-m-d" , strtotime ( "+1 day" , strtotime($fecha) ) );
		}
		$res1 = mysql_query("SELECT SEC_TO_TIME({$ttrabajo}) as tiempo_trabajo");
		$row1 = mysql_fetch_array($res1);
		$renglon['tiempo_trabajo'] = $row1['tiempo_trabajo'];
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
			<label class="col-sm-2 col-form-label">Plaza</label>
			<div class="col-sm-4">
            	<select id="busquedaplaza" class="form-control" name="busquedaplaza"><option value="">Todos</option>
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
            	<input type="text" class="form-control" id="busquedanumero" name="busquedanumero" placeholder="No. Personal">
        	</div>
        </div>
		<div class="form-group row">
			<label class="col-sm-2 col-form-label">Nombre</label>
			<div class="col-sm-4">
            	<input type="text" class="form-control" id="busquedanombre" name="busquedanombre" placeholder="Nombre">
        	</div>
        	<label class="col-sm-2 col-form-label">Puesto</label>
			<div class="col-sm-4">
            	<select id="busquedapuesto" name="busquedapuesto" class="form-control"><option value="">Todos</option>
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
            	<select id="busquedaestatus" name="busquedaestatus"class="form-control"><option value="">Todos</option>
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
				<button type="button" class="btn btn-primary" onClick="atcr('asistencia_personal_periodo.php','_blank',100,0);">
	            	Excel
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
		  url: 'asistencia_personal_periodo.php',
		  type: "POST",
		  data: {
			menu: $('#cvemenu').val(),
			cmd: 10,
			cveusuario: $('#cveusuario').val(),
			busquedafechaini: $('#busquedafechaini').val(),
			busquedafechafin: $('#busquedafechafin').val(),
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
	      <th scope="col" rowspan="2" style="text-align: center;">Plaza</th>
	      <th scope="col" rowspan="2" style="text-align: center;">No.</th>
		  <th scope="col" rowspan="2" style="text-align: center;">Nombre</th> 
		  <th scope="col" rowspan="2" style="text-align: center;">RFC</th> 
	      <th scope="col" rowspan="2" style="text-align: center; border-left: 2px solid #000000;">Faltas</th> 
	      <?php
	        $fecha=$_POST['busquedafechaini'];
			while($fecha<=$_POST['busquedafechafin']){
				$arfecha=explode("-",$fecha);
				$dia=date("w", mktime(0, 0, 0, intval($arfecha[1]), intval($arfecha[2]), $arfecha[0]));
				if($dia != 0){
					echo '<th scope="col" colspan="3" style="text-align: center; border-left: 2px solid #000000;">'.$fecha.'</th> ';
				}
				$fecha=date( "Y-m-d" , strtotime ( "+1 day" , strtotime($fecha) ) );
			}
		  ?>
	      <th scope="col" rowspan="2" style="text-align: center; border-left: 2px solid #000000;">Total Tiempo Trabajo</th> 
	    </tr>
	    <tr>
	      <?php
	        $fecha=$_POST['busquedafechaini'];
			while($fecha<=$_POST['busquedafechafin']){
				$arfecha=explode("-",$fecha);
				$dia=date("w", mktime(0, 0, 0, intval($arfecha[1]), intval($arfecha[2]), $arfecha[0]));
				if($dia != 0){
					echo '<th scope="col" style="text-align: center; border-left: 2px solid #000000;">Hora Entrada Trabajar</th>';
					echo '<th scope="col" style="text-align: center;">Hora Salida Trabajar</th>';
					echo '<th scope="col" style="text-align: center;">Tiempo Trabajo</th>';
				}
				$fecha=date( "Y-m-d" , strtotime ( "+1 day" , strtotime($fecha) ) );
			}
		  ?>
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
	      <td align="center" style="border-left: 2px solid #000000;"><?php echo $row['faltas'];?></td>
	      <?php
	        $fecha=$_POST['busquedafechaini'];
			while($fecha<=$_POST['busquedafechafin']){
				$arfecha=explode("-",$fecha);
				$dia=date("w", mktime(0, 0, 0, intval($arfecha[1]), intval($arfecha[2]), $arfecha[0]));
				if($dia != 0){
					echo '<td align="center" style="border-left: 2px solid #000000;">'.$row['fechas'][$fecha]['hora_entrada'].'</td>';
					echo '<td align="center">'.$row['fechas'][$fecha]['hora_salida'].'</td>';
					echo '<td align="center">'.$row['fechas'][$fecha]['tiempo_trabajo'].'</td>';
				}
				$fecha=date( "Y-m-d" , strtotime ( "+1 day" , strtotime($fecha) ) );
			}
		  ?>
	      <td align="center" style="border-left: 2px solid #000000;"><?php echo $row['tiempo_trabajo'];?></td>
	    </tr>
	<?php
		}
	?>
	  </tbody>
	</table>
	

<?php
}

if($_POST['cmd']==100){
	require_once('PHPExcel/Classes/PHPExcel.php');
	include 'PHPExcel/Classes/PHPExcel/Writer/Excel2007.php'; 
	$objPHPExcel = new PHPExcel(); 
	$filename = "AsistenciaPersonal_periodo.xlsx"; 
	$res = obtener_informacion($_POST);
	$tmonto = 0;
	
	 header('Content-type: application/vnd.ms-excel');
header("Content-Disposition: attachment; filename=AsistenciaPersonal_periodo.xls");
header("Pragma: no-cache");
header("Expires: 0");

$html= '<h2>Asistencia Personal del Periodo de : '.$_POST['busquedafechaini'].' al '.$_POST['busquedafechafin'].'</h2><br>
	<table width="100%" border="1" cellpadding="4" cellspacing="1" class="" >';
	$html.= '<thead>';
	$html.= '<tr bgclor="#E9F2F8">';

	$F=$objPHPExcel->getActiveSheet(); 
	$Line = 1;
		$html.='<th scope="col" rowspan="2" style="text-align: center;">Plaza</th>';
	     $html.=' <th scope="col" rowspan="2" style="text-align: center;">No.</th>';
		$html.='  <th scope="col" rowspan="2" style="text-align: center;">Nombre</th> ';
		$html.='  <th scope="col" rowspan="2" style="text-align: center;">RFC</th> ';
	     $html.=' <th scope="col" rowspan="2" style="text-align: center; border-left: 2px solid #000000;">Faltas</th> ';
			$fecha=$_POST['busquedafechaini'];
			while($fecha<=$_POST['busquedafechafin']){
				$arfecha=explode("-",$fecha);
				$dia=date("w", mktime(0, 0, 0, intval($arfecha[1]), intval($arfecha[2]), $arfecha[0]));
				if($dia != 0){
					$html.='<th scope="col" colspan="3" style="text-align: center; border-left: 2px solid #000000;">'.$fecha.'</th> ';
				}
				$fecha=date( "Y-m-d" , strtotime ( "+1 day" , strtotime($fecha) ) );
			}
	
	$html.='<th scope="col" rowspan="2" style="text-align: center; border-left: 2px solid #000000;">Total Tiempo Trabajo</th>
			</tr>';
			$html.='<tr>';
	      
	        $fecha=$_POST['busquedafechaini'];
			while($fecha<=$_POST['busquedafechafin']){
				$arfecha=explode("-",$fecha);
				$dia=date("w", mktime(0, 0, 0, intval($arfecha[1]), intval($arfecha[2]), $arfecha[0]));
				if($dia != 0){
					$html.= '<th scope="col" style="text-align: center; border-left: 2px solid #000000;">Hora Entrada Trabajar</th>';
					$html.= '<th scope="col" style="text-align: center;">Hora Salida Trabajar</th>';
					$html.= '<th scope="col" style="text-align: center;">Tiempo Trabajo</th>';
				}
				$fecha=date( "Y-m-d" , strtotime ( "+1 day" , strtotime($fecha) ) );
			}
		  
	    $html.='</tr>
		 </thead>
	  <tbody>';

	
		$totales = array();
		$i = 0;
		foreach($res as $row){
	
	  $html.='  <tr>
	      <td align="left">'.utf8_encode($row['plaza']).'</td>
	      <td align="center">'. $row['numero'].'</td>
	      <td align="left">'. utf8_encode($row['nombre']).'</td>
	      <td align="center">'. $row['rfc'].'</td>
	      <td align="center" style="border-left: 2px solid #000000;">'. $row['faltas'].'</td>';
	      
	        $fecha=$_POST['busquedafechaini'];
			while($fecha<=$_POST['busquedafechafin']){
				$arfecha=explode("-",$fecha);
				$dia=date("w", mktime(0, 0, 0, intval($arfecha[1]), intval($arfecha[2]), $arfecha[0]));
				if($dia != 0){
					$html.='<td align="center" style="border-left: 2px solid #000000;">'.$row['fechas'][$fecha]['hora_entrada'].'</td>';
					$html.='<td align="center">'.$row['fechas'][$fecha]['hora_salida'].'</td>';
					$html.='<td align="center">'.$row['fechas'][$fecha]['tiempo_trabajo'].'</td>';
				}
				$fecha=date( "Y-m-d" , strtotime ( "+1 day" , strtotime($fecha) ) );
			}
		  
	      $html.='<td align="center" style="border-left: 2px solid #000000;">'. $row['tiempo_trabajo'].'</td>';
	    $html.='</tr>';
	
		}
	
	 $html.=' </tbody>';
	$html.='</table>';

echo $html;
	exit();
	

}


?>