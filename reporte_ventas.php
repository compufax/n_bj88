<?php
error_reporting(E_ERROR | E_PARSE);
require_once('cnx_db.php');
require_once('globales.php');

function obtener_informacion($datos){
	$select .= "SELECT a.cve, CONCAT(a.fecha,' ',a.hora) as fecha, CONCAT(b.fecha,' ',b.hora) as fechaentrega, TIMEDIFF(IFNULL(CONCAT(b.fecha,' ',b.hora),NOW()),CONCAT(a.fecha,' ',a.hora)) as diferencia, a.placa, c.nombre as nomengomado, IF(a.tipo_venta=2, CONCAT(d.nombre,' ',IF(a.tipo_cortesia=1, 'Autorizada', 'Vale Anticipado')), d.nombre) as nomtipoventa, IF(a.estatus NOT IN ('C','D'), a.monto, 0) as monto, a.copias, e.nombre as nomanio, f.nombre as nomtipopago, IF(a.tipo_pago=6, 'Vale Anticipado', '') as tipo_vale, IF(a.tipo_pago=6 AND a.tipo_venta IN (0,2), IF(a.tipo_venta=0, a.vale_pago_anticipado, a.codigo_cortesia), '') as vale, g.nombre as nomdepositante, h.nombre as nomcombustible, IF(a.factura=0, '', a.factura) as factura, IFNULL(b.cve, '') as entrega, IFNULL(b.certificado,'') as holograma, 
	IFNULL(i.nombre, '') as nomengomadoentregado, j.usuario, a.obscan, k.nombre as nomintento, a.obs, IF(a.estatus='C','Cancelado',IF(a.estatus='D', 'Devuelto', 'Activo')) as nomestatus, a.certificado_anterior,a.voluntario,a.entidad,a.multa, l.nombre as nomtecnico, m.nombre as nomlinea, n.nombre as nommodelo, o.nombre as nommarca
	FROM cobro_engomado a 
	LEFT JOIN certificados b ON a.plaza = b.plaza AND a.cve = b.ticket AND b.estatus!='C' 
	INNER JOIN engomados c ON c.cve = a.engomado 
	INNER JOIN tipo_venta d ON d.cve = a.tipo_venta
	INNER JOIN anios_certificados e ON e.cve = a.anio
	INNER JOIN tipos_pago f ON f.cve = a.tipo_pago
	LEFT JOIN depositantes g ON g.cve = a.depositante
	INNER JOIN tipo_combustible h ON h.cve = a.tipo_combustible
	LEFT JOIN engomados i ON i.cve = b.engomado 
	INNER JOIN usuarios j ON j.cve = a.usuario
	LEFT JOIN motivos_intento k ON k.cve = a.motivo_intento
	LEFT JOIN tecnicos l ON l.plaza = b.plaza AND l.cve = b.tecnico
	LEFT JOIN cat_lineas m ON m.plaza = b.plaza AND m.cve = b.linea
	LEFT JOIN cat_modelo n ON n.cve = b.modelo 
	LEFT JOIN cat_marcas o ON o.cve = b.marca
	WHERE a.plaza = {$datos['cveplaza']}";
	if ($datos['busquedaticket'] != ''){
		$select .= " AND a.cve='{$datos['busquedaticket']}'";
	}
	else{
		if ($datos['busquedaplaca'] != ''){
			$select .= " AND a.placa='{$datos['busquedaplaca']}'";
		}
		if ($datos['busquedafechaini'] != ''){
			$select .= " AND a.fecha>='{$datos['busquedafechaini']}'";
		}
		if ($datos['busquedafechafin'] != ''){
			$select .= " AND a.fecha<='{$datos['busquedafechafin']}'";
		}
		if ($datos['busquedausuario']!="") { 
			$select.=" AND a.usuario='{$datos['busquedausuario']}' "; 
		}
		if ($datos['busquedaengomado']!="") {
			$select.=" AND a.engomado='{$datos['busquedaengomado']}' "; 
		}
		if ($datos['busquedaestatus']!="") { 
			$select.=" AND a.estatus='{$datos['busquedaestatus']}' "; 
		}
		if ($datos['busquedatipo_pago']!="") { 
			$select.=" AND a.tipo_pago='{$datos['busquedatipo_pago']}' "; 
		}
		if ($datos['busquedatipoventa']!="") { 
			$select.=" AND a.tipo_venta='{$datos['busquedatipoventa']}' "; 
		}
		if ($datos['busquedadepositante']!="") { 
			$select.=" AND a.depositante='{$datos['busquedadepositante']}' "; 
		}
		if ($datos['busquedatipo_combustible']!="") { 
			$select.=" AND a.tipo_combustible='{$datos['busquedatipo_combustible']}' "; 
		}
		if ($datos['busquedaanio']!="") { 
			$select.=" AND a.anio='{$datos['busquedaanio']}' "; 
		}
		if ($datos['busquedatipo_cortesia']!="") { 
			$select.=" AND a.tipo_cortesia='{$datos['busquedatipo_cortesia']}' "; 
		}
		if ($datos['voluntario']!="") { 
			$select.=" AND a.voluntario='{$datos['voluntario']}' "; 
		}
		if ($datos['multa']!="") { 
			$select.=" AND a.multa='{$datos['multa']}' "; 
		}
		if($datos['mostrar']==1) $select.=" AND IFNULL(b.cve,0)>0";
		elseif($datos['mostrar']==2) $select.=" AND IFNULL(b.cve,0)=0 AND a.estatus='A'";
	}
	
	$select.=" ORDER BY a.fecha DESC, a.cve DESC";
	$res = mysql_query($select);
	return $res;
}
require_once('validarloging.php');
$res1 = mysql_query("SELECT cve, nombre FROM cat_entidades ORDER BY nombre");
	while($row1=mysql_fetch_array($res1)){
		$array_entidad[$row1['cve']]=$row1['nombre'];
	}

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
			<label class="col-sm-2 col-form-label">Ticket</label>
			<div class="col-sm-4">
            	<input type="number" class="form-control" id="busquedaticket" name="busquedaticket" placeholder="Ticket" value="">
        	</div>
			<label class="col-sm-2 col-form-label">Placa</label>
			<div class="col-sm-4">
            	<input type="text" class="form-control" id="busquedaplaca" name="busquedaplaca" placeholder="Placa" value="">
        	</div>
        </div>
        <div class="form-group row">
			<label class="col-sm-2 col-form-label">Tipo de Certificado</label>
			<div class="col-sm-4">
            	<select name="busquedatipocertificado" id="busquedatipocertificado" class="form-control"><option value="">Todos</option>
            	<?php
            	$res1 = mysql_query("SELECT a.cve, a.nombre FROM engomados a inner join engomados_plazas b on a.cve = b.engomado WHERE b.venta=1 AND b.plaza = '{$_POST['cveplaza']}' ORDER BY a.nombre");
				while($row1=mysql_fetch_array($res1)){
					echo '<option value="'.$row1['cve'].'">'.$row1['nombre'].'</option>';
				}
				?>
            	</select>
        	</div>
        	<label class="col-sm-2 col-form-label">Usuario</label>
			<div class="col-sm-4">
            	<select name="busquedausuario" id="busquedausuario" class="form-control" data-container="body" data-live-search="true" title="Usuario" data-hide-disabled="true" data-actions-box="true" data-virtual-scroll="false"><option value="">Todos</option>
            	<?php
            	$res1 = mysql_query("SELECT b.cve, b.usuario FROM (SELECT usuario FROM cobro_engomado WHERE plaza='{$_POST['cveplaza']}' GROUP BY usuario) a INNER JOIN usuarios b ON b.cve = a.usuario ORDER BY b.usuario");
				while($row1=mysql_fetch_array($res1)){
					echo '<option value="'.$row1['cve'].'">'.$row1['usuario'].'</option>';
				}
				?>
            	</select>
            	<script>
					$("#busquedausuario").selectpicker();	
				</script>
        	</div>
        </div>
        <div class="form-group row">
			<label class="col-sm-2 col-form-label">Tipo de Venta</label>
			<div class="col-sm-4">
            	<select name="busquedatipoventa" id="busquedatipoventa" class="form-control"><option value="">Todos</option>
            	<?php
            	$res1 = mysql_query("SELECT cve, nombre FROM tipo_venta ORDER BY nombre");
				while($row1=mysql_fetch_array($res1)){
					echo '<option value="'.$row1['cve'].'">'.$row1['nombre'].'</option>';
				}
				?>
            	</select>
        	</div>
        	<label class="col-sm-2 col-form-label">Tipo de Pago</label>
			<div class="col-sm-4">
            	<select name="busquedatipopago" id="busquedatipopago" class="form-control"><option value="">Todos</option>
            	<?php
            	$res1 = mysql_query("SELECT cve, nombre FROM tipos_pago WHERE mostrar_ventas=1 ORDER BY nombre");
				while($row1=mysql_fetch_array($res1)){
					echo '<option value="'.$row1['cve'].'">'.$row1['nombre'].'</option>';
				}
				?>
            	</select>
        	</div>
        </div>
        <div class="form-group row">
			<label class="col-sm-2 col-form-label">Tipo de Combustible</label>
			<div class="col-sm-4">
            	<select name="busquedatipo_combustible" id="busquedatipo_combustible" class="form-control"><option value="">Todos</option>
            	<?php
            	$res1 = mysql_query("SELECT cve, nombre FROM tipo_combustible ORDER BY nombre");
				while($row1=mysql_fetch_array($res1)){
					echo '<option value="'.$row1['cve'].'">'.$row1['nombre'].'</option>';
				}
				?>
            	</select>
        	</div>
        	<label class="col-sm-2 col-form-label">A&ntilde;o de Certificaci&oacute;n</label>
			<div class="col-sm-4">
            	<select name="busquedaanio" id="busquedaanio" class="form-control"><option value="">Todos</option>
            	<?php
            	$res1 = mysql_query("SELECT cve, nombre FROM anios_certificados WHERE venta=1 ORDER BY nombre");
				while($row1=mysql_fetch_array($res1)){
					echo '<option value="'.$row1['cve'].'">'.$row1['nombre'].'</option>';
				}
				?>
            	</select>
        	</div>
        </div>
        <div class="form-group row">
			<label class="col-sm-2 col-form-label">Depositante</label>
			<div class="col-sm-4">
            	<select name="busquedadepositante" id="busquedadepositante" class="form-control"><option value="">Todos</option>
            	<?php
            	$res1 = mysql_query("SELECT cve, nombre, IF(tipo_depositante=0, 'Pago Anticipado', IF(tipo_depositante=2,'Acumulado', 'Credito')) as nomtipodepositante FROM depositantes WHERE plaza='{$_POST['cveplaza']}' ORDER BY nombre");
				while($row1=mysql_fetch_array($res1)){
					echo '<option value="'.$row1['cve'].'">'.utf8_encode($row1['nombre']).' ('.$row1['nomtipodepositante'].')</option>';
				}
				?>
            	</select>
        	</div>
        	<label class="col-sm-2 col-form-label">Mostrar</label>
			<div class="col-sm-4">
            	<select name="mostrar" id="mostrar" class="form-control"><option value="">Todos</option>
            		<option value="1">Con certificado</option>
            		<option value="2">Sin certificado</option>
            	</select>
        	</div>
        </div>
        <div class="form-group row">
        	<label class="col-sm-2 col-form-label">Tipo Cortesia</label>
			<div class="col-sm-4">
            	<select name="busquedatipo_cortesia" id="busquedatipo_cortesia" class="form-control"><option value="">Todos</option>
            		<option value="1">Autorizada</option>
            		<option value="2">Vale Anticipado</option>
            	</select>
        	</div>
			<label class="col-sm-2 col-form-label">Voluntario</label>
			<div class="col-sm-4">
            	<select name="voluntario" id="voluntario" class="form-control"><option value="">Todos</option>
            		<option value="0">No</option>
            		<option value="1">Si</option>
            	</select>
        	</div>
        </div>
		<div class="form-group row">
        	<label class="col-sm-2 col-form-label">Multa</label>
			<div class="col-sm-4">
            	<select name="busquedamulta" id="busquedamulta" class="form-control"><option value="">Todos</option>
            		<option value="0">No</option>
            		<option value="1">Si</option>
            	</select>
        	</div>
			
        </div>
        <div class="form-group row">
        	<div class="col-sm-12" align="center">
	        	<button type="button" class="btn btn-primary" onClick="buscar();">
	            	Buscar
	        	</button>
	        	&nbsp;&nbsp;
	        	<button type="button" class="btn btn-primary" onClick="atcr('reporte_ventas.php','_blank',100,0);">
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
		  url: 'reporte_ventas.php',
		  type: "POST",
		  data: {
			menu: $('#cvemenu').val(),
			cmd: 10,
			cveusuario: $('#cveusuario').val(),
			busquedafechaini: $('#busquedafechaini').val(),
			busquedafechafin: $('#busquedafechafin').val(),
			busquedaticket: $("#busquedaticket").val(),
    		busquedaplaca: $("#busquedaplaca").val(),
    		busquedausuario: $("#busquedausuario").val(),
    		busquedatipocertificado: $("#busquedatipocertificado").val(),
    		busquedatipoventa: $("#busquedatipoventa").val(),
    		busquedatipopago: $("#busquedatipopago").val(),
    		busquedatipo_cortesia: $("#busquedatipo_cortesia").val(),
    		mostrar: $("#mostrar").val(),
			voluntario: $("#voluntario").val(),
    		busquedadepositante: $("#busquedadepositante").val(),
    		busquedaanio: $('#busquedaanio').val(),
    		busquedatipo_combustible: $("#busquedatipo_combustible").val(),
    		cvemenu: $('#cvemenu').val(),
    		cveplaza: $('#cveplaza').val(),
    		cveusuario: $('#cveusuario').val(),
			multa: $("#busquedamulta").val()
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
	      <th scope="col" style="text-align: center;">Ticket</th>
	      <th scope="col" style="text-align: center;">Fecha</th>
	      <th scope="col" style="text-align: center;">Fecha Entrega</th>
		  <th scope="col" style="text-align: center;">Placa</th> 
		     <th scope="col" style="text-align: center;">Multa</th> 
	      <th scope="col" style="text-align: center;">Tipo de Certificado</th> 
	      <th scope="col" style="text-align: center;">Tipo de Venta</th> 
		    <th scope="col" style="text-align: center;">Voluntario</th> 
			  <th scope="col" style="text-align: center;">Entidad</th> 
	      <th scope="col" style="text-align: center;">Monto</th> 
	      <th scope="col" style="text-align: center;">Copias</th> 
	      <th scope="col" style="text-align: center;">Total</th> 
	      <th scope="col" style="text-align: center;">A&ntilde;o de Certificaci&oacute;n</th> 
	      <th scope="col" style="text-align: center;">Tipo de Pago</th> 
	      <th scope="col" style="text-align: center;">Tipo de Vale</th> 
	      <th scope="col" style="text-align: center;">Vale</th> 
	      <th scope="col" style="text-align: center;">Depositante</th> 
	      <th scope="col" style="text-align: center;">Certificado Anterior</th> 
	      <th scope="col" style="text-align: center;">Estatus</th> 
	      <th scope="col" style="text-align: center;">Factura</th> 
	      <th scope="col" style="text-align: center;">Entrega Certificado</th> 
	      <th scope="col" style="text-align: center;">Holograma Entregado</th> 
	      <th scope="col" style="text-align: center;">Tipo de Verificaci&oacute;n Entregada</th> 
	      <th scope="col" style="text-align: center;">Tecnico</th> 
	      <th scope="col" style="text-align: center;">Linea</th> 
	      <th scope="col" style="text-align: center;">Modelo</th> 
	      <th scope="col" style="text-align: center;">Marca</th> 
	      <th scope="col" style="text-align: center;">Tipo de Combustible</th> 
	      <th scope="col" style="text-align: center;">Usuario</th> 
	      <th scope="col" style="text-align: center;">Motivo Cancelaci&oacute;n</th> 
	      <th scope="col" style="text-align: center;">Motivo Intento</th> 
	      <th scope="col" style="text-align: center;">Observaciones</th> 
	    </tr>
	  </thead>
	  <tbody>
	<?php
		$totales = array();
		$i = 0;
		while($row = mysql_fetch_assoc($res)){
	?>
	    <tr>
	      <td align="center"><?php echo $row['cve'];?></td>
	      <td align="center"><?php echo $row['fecha'];?></td>
	      <td align="center"><?php echo $row['fechaentrega'];?></td>
	      <td align="center"><?php echo $row['placa'];?></td>
		  <td align="left"><?php echo (utf8_encode ($array_nosi[ $row['multa']]));?></td>
	      <td align="left"><?php echo ($row['nomengomado']);?></td>
	      <td align="left"><?php echo ($row['nomtipoventa']);?></td>
		  <td align="left"><?php echo (utf8_encode ($array_nosi[ $row['voluntario']]));?></td>
		  <td align="left"><?php echo ($array_entidad[$row['entidad']]);?></td>
	      <td align="right"><?php echo number_format($row['monto'],2);?></td>
	      <td align="right"><?php echo number_format($row['copias'],2);?></td>
	      <td align="right"><?php echo number_format($row['monto']+$row['copias'],2);?></td>
	      <td align="left"><?php echo ($row['nomanio']);?></td>
	      <td align="left"><?php echo ($row['nomtipopago']);?></td>
	      <td align="left"><?php echo $row['tipo_vale'];?></td>
	      <td align="center"><?php echo $row['vale'];?></td>
	      <td align="left"><?php echo $row['nomdepositante'];?></td>
	      <td align="center"><?php echo ($row['certificado_anterior']);?></td>
	      <td align="left"><?php echo $row['nomestatus'];?></td>
	      <td align="center"><?php echo $row['factura'];?></td>
	      <td align="center"><?php echo $row['entrega'];?></td>
	      <td align="center"><?php echo ($row['holograma']);?></td>
	      <td align="left"><?php echo ($row['nomengomadoentregado']);?></td>
	      <td align="left"><?php echo ($row['nomtecnico']);?></td>
	      <td align="left"><?php echo ($row['nomlinea']);?></td>
	      <td align="left"><?php echo ($row['nommodelo']);?></td>
	      <td align="left"><?php echo ($row['nommarca']);?></td>
	      <td align="left"><?php echo ($row['nomcombustible']);?></td>
	      <td align="left"><?php echo ($row['usuario']);?></td>
	      <td align="left"><?php echo $row['obscan'];?></td>
	      <td align="left"><?php echo $row['nomintento'];?></td>
	      <td align="left"><?php echo $row['obs'];?></td>
	    </tr>
	<?php
		$i++;
		$totales[0]+=$row['monto'];
		$totales[1]+=$row['copias'];
		$totales[2]+=$row['monto']+$row['copias'];
	}
	?>
		<tr>
			<th colspan="8" style="text-align: left;"><?php echo $i;?> Registro(s)</th>
			<th style="text-align: right;">Totales:</th>
			<th style="text-align: right;"><?php echo number_format($totales[0],2);?></th>
			<th style="text-align: right;"><?php echo number_format($totales[1],2);?></th>
			<th style="text-align: right;"><?php echo number_format($totales[2],2);?></th>
			<th colspan="20" style="text-align: right;">&nbsp;</th>
		</tr>
	  </tbody>
	</table>
	

<?php
}

if($_POST['cmd']==100){
	require_once('PHPExcel/Classes/PHPExcel.php');
	include 'PHPExcel/Classes/PHPExcel/Writer/Excel2007.php'; 
	$objPHPExcel = new PHPExcel(); 
	$filename = "reporteventas.xlsx"; 
	$res = obtener_informacion($_POST);
	$tmonto = 0;
	header('Content-Type: application/vnd.ms-excel'); 
	header('Content-Disposition: attachment;filename="' . $filename . '"'); 
	header('Cache-Control: max-age=0'); 
	$F=$objPHPExcel->getActiveSheet(); 
	$Line = 1;
	$F->setCellValue('A'.$Line, 'Ticket'); 
	$F->setCellValue('B'.$Line, 'Fecha'); 
	$F->setCellValue('C'.$Line, 'Fecha Entrega'); 
	$F->setCellValue('D'.$Line, 'Placa'); 
	$F->setCellValue('E'.$Line, 'Multa'); 
	$F->setCellValue('F'.$Line, 'Tipo Certificado'); 
	$F->setCellValue('G'.$Line, 'Tipo Venta');
	$F->setCellValue('H'.$Line, 'Voluntario');
	$F->setCellValue('I'.$Line, 'Entidad');
	
	$F->setCellValue('J'.$Line, 'Monto'); 
	$F->setCellValue('K'.$Line, 'Copias'); 
	$F->setCellValue('L'.$Line, 'Total'); 
	$F->setCellValue('M'.$Line, 'Año Certificacion'); 
	$F->setCellValue('N'.$Line, 'Tipo Pago'); 
	$F->setCellValue('O'.$Line, 'Tipo Vale'); 
	$F->setCellValue('P'.$Line, 'Vale'); 
	$F->setCellValue('Q'.$Line, 'Depositante'); 
	$F->setCellValue('R'.$Line, 'Certificado Anterior'); 
	$F->setCellValue('S'.$Line, 'Estatus'); 
	$F->setCellValue('T'.$Line, 'Factura'); 
	$F->setCellValue('U'.$Line, 'Entrega Certificado'); 
	$F->setCellValue('V'.$Line, 'Holograma Entregado'); 
	$F->setCellValue('W'.$Line, 'Tipo Verificacion Entregada'); 
	$F->setCellValue('X'.$Line, 'Tecnico'); 
	$F->setCellValue('Y'.$Line, 'Linea'); 
	$F->setCellValue('Z'.$Line, 'Modelo'); 
	$F->setCellValue('AA'.$Line, 'Marca'); 
	$F->setCellValue('AB'.$Line, 'Tipo de Combustible'); 
	$F->setCellValue('AC'.$Line, 'Usuario'); 
	$F->setCellValue('AD'.$Line, 'Motivo Cancelacion'); 
	$F->setCellValue('AE'.$Line, 'Motivo Intento'); 
	$F->setCellValue('AF'.$Line, 'Observaciones'); 
	$totales = array();

	while($row=mysql_fetch_assoc($res)){//extract each record 
	    ++$Line; 
	    $F->setCellValue('A'.$Line, $row['cve']); 
		$F->setCellValue('B'.$Line, $row['fecha']); 
		$F->setCellValue('C'.$Line, $row['fechaentrega']); 
		$F->setCellValue('D'.$Line, $row['placa']);
		$F->setCellValue('E'.$Line, utf8_encode($array_nosi[$row['multa']])); 		
		$F->setCellValue('F'.$Line, $row['nomengomado']); 
		$F->setCellValue('G'.$Line, $row['nomtipoventa']); 
		$F->setCellValue('H'.$Line, utf8_encode($array_nosi[$row['voluntario']])); 
		$F->setCellValue('I'.$Line, $array_entidad[$row['entidad']]); 
		
		$F->setCellValue('J'.$Line, number_format($row['monto'],2)); 
		$F->setCellValue('K'.$Line, number_format($row['copias'],2)); 
		$F->setCellValue('L'.$Line, number_format($row['monto']+$row['copias'],2)); 
		$F->setCellValue('M'.$Line, $row['nomanio']); 
		$F->setCellValue('N'.$Line, $row['nomtipopago']); 
		$F->setCellValue('O'.$Line, $row['tipo_vale']); 
		$F->setCellValue('P'.$Line, $row['vale']); 
		$F->setCellValue('Q'.$Line, $row['nomdepositante']); 
		$F->setCellValue('R'.$Line, $row['certificado_anterior']); 
		$F->setCellValue('S'.$Line, $row['nomestatus']); 
		$F->setCellValue('T'.$Line, $row['factura']); 
		$F->setCellValue('U'.$Line, $row['entrega']); 
		$F->setCellValue('V'.$Line, $row['holograma']); 
		$F->setCellValue('W'.$Line, $row['nomengomadoentregado']); 
		$F->setCellValue('X'.$Line, $row['nomtecnico']); 
		$F->setCellValue('Y'.$Line, $row['nomlinea']); 
		$F->setCellValue('Z'.$Line, $row['nommodelo']); 
		$F->setCellValue('AA'.$Line, $row['nommarca']); 
		$F->setCellValue('AB'.$Line, $row['nomcombustible']); 
		$F->setCellValue('AC'.$Line, $row['usuario']); 
		$F->setCellValue('AD'.$Line, $row['obscan']); 
		$F->setCellValue('AE'.$Line, $row['nomintento']); 
		$F->setCellValue('AF'.$Line, $row['obs']); 
		$i++;
		$totales[0]+=$row['monto'];
		$totales[1]+=$row['copias'];
		$totales[2]+=$row['monto']+$row['copias'];
		
		
	} 
	++$Line;
	$F->setCellValue('A'.$Line, $i.' Registro(s)'); 
	$F->setCellValue('G'.$Line, 'TOTAL:'); 
	$F->setCellValue('J'.$Line, number_format($totales[0],2)); 
	$F->setCellValue('K'.$Line, number_format($totales[1],2)); 
	$F->setCellValue('L'.$Line, number_format($totales[2],2)); 
	// Redirect output to a client’s web browser (Excel5) 
	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); 
	header('Content-Disposition: attachment;filename="'.$filename.'"'); 
	header('Cache-Control: max-age=0'); 

	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007'); 
	$objWriter->save('php://output'); 
	exit; 

}

?>