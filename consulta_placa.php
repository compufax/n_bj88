<?php
error_reporting(E_ERROR | E_PARSE);
require_once('cnx_db.php');
require_once('globales.php');

function obtener_informacion($datos){
	$select .= "SELECT p.numero as nomplaza, a.cve, CONCAT(a.fecha,' ',a.hora) as fecha, CONCAT(b.fecha,' ',b.hora) as fechaentrega, TIMEDIFF(IFNULL(CONCAT(b.fecha,' ',b.hora),NOW()),CONCAT(a.fecha,' ',a.hora)) as diferencia, a.placa, c.nombre as nomengomado, d.nombre as nomtipoventa, IF(a.estatus NOT IN ('C','D'), a.monto, 0) as monto, a.copias, e.nombre as nomanio, f.nombre as nomtipopago, IF(a.tipo_pago=6, 'Vale Anticipado', '') as tipo_vale, IF(a.tipo_pago=6 AND a.tipo_venta IN (0,2), IF(a.tipo_venta=0, a.vale_pago_anticipado, a.codigo_cortesia), '') as vale, g.nombre as nomdepositante, h.nombre as nomcombustible, IF(a.factura=0, '', a.factura), IFNULL(b.cve, '') as entrega, IFNULL(b.certificado,'') as holograma, 
	IFNULL(i.nombre, '') as nomengomadoentregado, j.usuario, a.obscan, k.nombre as nomintento, a.obs, IF(a.estatus='C','Cancelado',IF(a.estatus='D', 'Devuelto', 'Activo')) as nomestatus, a.certificado_anterior, cf.cve as clave_facturacion
	FROM cobro_engomado a 
	INNER JOIN plazas p ON p.cve = a.plaza
	LEFT JOIN claves_facturacion cf ON a.plaza = cf.plaza AND a.cve =cf.ticket
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
	WHERE a.placa='{$datos['busquedaplaca']}'";
		
	
	$select.=" ORDER BY a.fecha DESC, a.cve DESC";
	$res = mysql_query($select);
	return $res;
}
require_once('validarloging.php');

if($_POST['cmd']==0){
?>

<div class="row justify-content-center">
	<div class="col-xl-12 col-lg-12 col-md-12">
        <div class="form-group row">
			<label class="col-sm-2 col-form-label">Placa</label>
			<div class="col-sm-4">
            	<input type="text" class="form-control" id="busquedaplaca" name="busquedaplaca" placeholder="Placa" value="">
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
		  url: 'consulta_placa.php',
		  type: "POST",
		  data: {
			menu: $('#cvemenu').val(),
			cmd: 10,
			cveusuario: $('#cveusuario').val(),
    		busquedaplaca: $("#busquedaplaca").val(),
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
	<h3>Ventas</h3>
	<table class="table">
	  <thead>
	    <tr>
	      <th scope="col" style="text-align: center;">Plaza</th>
	      <th scope="col" style="text-align: center;">Ticket</th>
	      <th scope="col" style="text-align: center;">Fecha</th>
	      <th scope="col" style="text-align: center;">Fecha Entrega</th>
		  <th scope="col" style="text-align: center;">Placa</th> 
	      <th scope="col" style="text-align: center;">Tipo de Certificado</th> 
	      <th scope="col" style="text-align: center;">Tipo de Venta</th> 
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
	      <th scope="col" style="text-align: center;">Usuario</th> 
	      <th scope="col" style="text-align: center;">Motivo Cancelaci&oacute;n</th> 
	      <th scope="col" style="text-align: center;">Motivo Intento</th> 
	      <th scope="col" style="text-align: center;">Observaciones</th> 
	      <th scope="col" style="text-align: center;">Clave Facturacion</th> 
	    </tr>
	  </thead>
	  <tbody>
	<?php
		$totales = array();
		$i = 0;
		while($row = mysql_fetch_assoc($res)){
	?>
	    <tr>
	      <td align="left"><?php echo utf8_encode($row['nomplaza']);?></td>
	      <td align="center"><?php echo $row['cve'];?></td>
	      <td align="center"><?php echo $row['fecha'];?></td>
	      <td align="center"><?php echo $row['fechaentrega'];?></td>
	      <td align="center"><?php echo $row['placa'];?></td>
	      <td align="left"><?php echo utf8_encode($row['nomengomado']);?></td>
	      <td align="left"><?php echo utf8_encode($row['nomtipoventa']);?></td>
	      <td align="right"><?php echo number_format($row['monto'],2);?></td>
	      <td align="right"><?php echo number_format($row['copias'],2);?></td>
	      <td align="right"><?php echo number_format($row['monto']+$row['copias'],2);?></td>
	      <td align="left"><?php echo ($row['nomanio']);?></td>
	      <td align="left"><?php echo utf8_encode($row['nomtipopago']);?></td>
	      <td align="left"><?php echo $row['tipo_vale'];?></td>
	      <td align="center"><?php echo $row['vale'];?></td>
	      <td align="left"><?php echo utf8_encode($row['nomdepositante']);?></td>
	      <td align="center"><?php echo ($row['certificado_anterior']);?></td>
	      <td align="left"><?php echo $row['nomestatus'];?></td>
	      <td align="center"><?php echo $row['factura'];?></td>
	      <td align="center"><?php echo $row['entrega'];?></td>
	      <td align="center"><?php echo ($row['holograma']);?></td>
	      <td align="left"><?php echo ($row['nomengomadoentregado']);?></td>
	      <td align="left"><?php echo ($row['usuario']);?></td>
	      <td align="left"><?php echo $row['obscan'];?></td>
	      <td align="left"><?php echo $row['nomintento'];?></td>
	      <td align="left"><?php echo $row['obs'];?></td>
	      <td align="left"><?php echo $row['clave_facturacion'];?></td>
	    </tr>
	<?php
		$i++;
		$totales[0]+=$row['monto'];
		$totales[1]+=$row['copias'];
		$totales[2]+=$row['monto']+$row['copias'];
	}
	?>
		<tr>
			<th colspan="6" style="text-align: left;"><?php echo $i;?> Registro(s)</th>
			<th style="text-align: right;">Totales:</th>
			<th style="text-align: right;"><?php echo number_format($totales[0],2);?></th>
			<th style="text-align: right;"><?php echo number_format($totales[1],2);?></th>
			<th style="text-align: right;"><?php echo number_format($totales[2],2);?></th>
			<th colspan="15" style="text-align: right;">&nbsp;</th>
		</tr>
	  </tbody>
	</table>
	

<?php
}

?>