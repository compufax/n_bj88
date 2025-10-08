<?php
error_reporting(E_ERROR | E_PARSE);
require_once('cnx_db.php');
require_once('globales.php');

function obtener_informacion($datos){
	$rechazos = 19;
	$_POST['fecha_ini'] = $datos['busquedafechaini'];
	$_POST['fecha_fin'] = $datos['busquedafechafin'];
	$resultado = array();
	$array_plazas=array();
	$resP=mysql_query("SELECT a.* FROM plazas a INNER JOIN datosempresas b ON a.cve = b.plaza WHERE a.estatus!='I' ORDER BY a.numero");
	while($rowP=mysql_fetch_array($resP)){
		$k=$rowP['cve'];
		$renglon = array();
		$renglon['plaza'] = $rowP['numero'].' '.$rowP['nombre'];
		$select= " SELECT COUNT(a.cve),SUM(IF(a.estatus!='C' AND a.tipo_pago NOT IN (2,6,12),a.monto,0)),SUM(IF(a.estatus='C',1,0)),SUM(IF(a.estatus!='C' AND a.tipo_pago='6',a.monto,0)) as p_ant
			FROM cobro_engomado as a WHERE a.plaza='".$k."' AND a.fecha>='".$_POST['fecha_ini']."' AND a.fecha<='".$_POST['fecha_fin']."' ";
		$res=mysql_query($select) or die(mysql_error());
		$row=mysql_fetch_array($res);
		
		$select1= " SELECT COUNT(a.cve),SUM(a.monto),SUM(IF(a.estatus!='C' AND a.tipo_pago='6',a.monto,0)),SUM(IF(a.estatus!='C' AND a.tipo_pago='2',a.monto,0))
			FROM pagos_caja as a WHERE a.plaza='".$k."' AND a.fecha>='".$_POST['fecha_ini']."' AND a.fecha<='".$_POST['fecha_fin']."' AND a.estatus!='C'";
		$res1=mysql_query($select1) or die(mysql_error());
		$row1=mysql_fetch_array($res1);
		//$row[1]+=$row1[1];
		$select = "SELECT SUM(IF(engomado NOT IN ($rechazos),1,0)), SUM(IF(engomado IN ($rechazos),1,0)) FROM certificados WHERE plaza='".$k."' AND fecha>='".$_POST['fecha_ini']."' AND fecha<='".$_POST['fecha_fin']."'";
		$res2=mysql_query($select) or die(mysql_error());
		$row2=mysql_fetch_array($res2);
		
		$renglon['aforo'] = $row2[0];
		$renglon['rechazo'] = $row2[1];
		$renglon['total'] = $row2[0]+$row2[1];
		$renglon['p_anticipado'] = $row1[2];
		$renglon['r_credito'] = $row1[3];
		
		$select= " SELECT COUNT(a.cve),SUM(IF(a.estatus!='C',a.devolucion,0)),SUM(IF(a.estatus='C',1,0))
		FROM devolucion_certificado as a WHERE a.plaza='".$k."' AND a.fecha>='".$_POST['fecha_ini']."' AND a.fecha<='".$_POST['fecha_fin']."' ";
		$res1=mysql_query($select) or die(mysql_error());
		$row1=mysql_fetch_array($res1);
		
		$select3= " SELECT SUM(IF(a.estatus!='C',a.total,0))
		FROM vales_externos as a WHERE a.plaza='".$k."' AND a.fecha>='".$_POST['fecha_ini']."' AND a.fecha<='".$_POST['fecha_fin']."' ";
		$res3=mysql_query($select3) or die(mysql_error());
		$row3=mysql_fetch_array($res3);
		$select4= " SELECT SUM(IF(a.estatus!='C',a.monto,0))
		FROM recibos_salida as a WHERE a.plaza='".$k."' AND a.fecha>='".$_POST['fecha_ini']."' AND a.fecha<='".$_POST['fecha_fin']."' ";
		$res4=mysql_query($select4) or die(mysql_error());
		$row4=mysql_fetch_array($res4);

		$renglon['importe'] = $row[1]-$row1[1];
		$renglon['vales'] = $row3[0];
		$renglon['gastos'] = $row4[0];
		$renglon['total_venta'] = $renglon['importe']+$renglon['p_anticipado']+$renglon['r_credito']+$renglon['vales']-$renglon['gastos'];
		
		$resultado[] = $renglon;
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
		  url: 'informe_ventas2.php',
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
	      <th scope="col" style="text-align: center;">Centro</th>
	      <th scope="col" style="text-align: center;">Aforo</th>
	      <th scope="col" style="text-align: center;">Rechazo</th>
		  <th scope="col" style="text-align: center;">Total</th> 
	      <th scope="col" style="text-align: center;">Importe</th> 
	      <th scope="col" style="text-align: center;">Pago Anticipado</th> 
	      <th scope="col" style="text-align: center;">Recuperaci&oacute;n de Cr&eacute;dito</th> 
	      <th scope="col" style="text-align: center;">Vales<br>Externos</th> 
	      <th scope="col" style="text-align: center;">Gastos</th> 
	      <th scope="col" style="text-align: center;">Venta Total</th> 
	    </tr>
	  </thead>
	  <tbody>
	<?php
		$totales = array();
		$i = 0;
		foreach($res as $row){
	?>
	    <tr>
	      <td align="left"><?php echo $row['plaza'];?></td>
	      <td align="right"><?php echo number_format($row['aforo'],0);?></td>
	      <td align="right"><?php echo number_format($row['rechazo'],0);?></td>
	      <td align="right"><?php echo number_format($row['total'],0);?></td>
	      <td align="right"><?php echo number_format($row['importe'],2);?></td>
	      <td align="right"><?php echo number_format($row['p_anticipado'],2);?></td>
	      <td align="right"><?php echo number_format($row['r_credito'],2);?></td>
	      <td align="right"><?php echo number_format($row['vales'],2);?></td>
	      <td align="right"><?php echo number_format($row['gastos'],2);?></td>
	      <td align="right"><?php echo number_format($row['total_venta'],2);?></td>
	    </tr>
	<?php
		$i++;
		$totales[0]+=$row['aforo'];
		$totales[1]+=$row['rechazo'];
		$totales[2]+=$row['total'];
		$totales[3]+=$row['importe'];
		$totales[4]+=$row['p_anticipado'];
		$totales[5]+=$row['r_credito'];
		$totales[6]+=$row['vales'];
		$totales[7]+=$row['gastos'];
		$totales[8]+=$row['total_venta'];
	}
	?>
		<tr>
			<th style="text-align: left;"><?php echo $i;?> Registro(s)</th>
			<th style="text-align: right;"><?php echo number_format($totales[0],0);?></th>
			<th style="text-align: right;"><?php echo number_format($totales[1],0);?></th>
			<th style="text-align: right;"><?php echo number_format($totales[2],0);?></th>
			<th style="text-align: right;"><?php echo number_format($totales[3],2);?></th>
			<th style="text-align: right;"><?php echo number_format($totales[4],2);?></th>
			<th style="text-align: right;"><?php echo number_format($totales[5],2);?></th>
			<th style="text-align: right;"><?php echo number_format($totales[6],2);?></th>
			<th style="text-align: right;"><?php echo number_format($totales[7],2);?></th>
			<th style="text-align: right;"><?php echo number_format($totales[8],2);?></th>
		</tr>
	  </tbody>
	</table>
	

<?php
}

?>