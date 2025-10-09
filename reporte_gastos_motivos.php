<?php
error_reporting(E_ERROR | E_PARSE);
require_once('cnx_db.php');
require_once('globales.php');

function obtener_informacion($datos){
	$resultado = array();
	$res = mysql_query("SELECT plaza, motivo, SUM(monto) as importe FROM recibos_salida WHERE estatus!='C' AND fecha BETWEEN '{$datos['busquedafechaini']}' AND '{$datos['busquedafechafin']}' GROUP BY plaza, motivo");
	while($row=mysql_fetch_array($res)){
		$resultado[$row['plaza']][$row['motivo']] = $row['importe'];
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
		  url: 'reporte_gastos_motivos.php',
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
	$motivos = array();
?>
	<table class="table">
	  <thead>
	    <tr>
	      <th scope="col" style="text-align: center;">Plaza</th>
	    <?php 
	    	$res1 = mysql_query("SELECT cve, nombre FROM motivos ORDER BY nombre");
	    	while($row1 = mysql_fetch_assoc($res1)){
	    ?>
	    		<th scope="col" style="text-align: center;"><?php echo utf8_encode($row1['nombre']);?></th>
	    <?php		
	    		$motivos[] = $row1['cve'];
	    	}
	    ?>
	      <th scope="col" style="text-align: center;">Total</th>
	    </tr>
	  </thead>
	  <tbody>
	<?php
		$totales = array();

		$res1 = mysql_query("SELECT cve, numero, nombre FROM plazas ORDER BY lista");
	    while($row1 = mysql_fetch_assoc($res1)){
	?>
	    <tr>
	      <td align="left"><?php echo utf8_encode($row1['numero'].' '.$row1['nombre']);?></td>
	      <?php 
	      $total=0;
	      $c=0;
	      foreach($motivos as $motivo){
	      ?>
	      	<td align="right"><?php echo number_format($res[$row1['cve']][$motivo],2);?></td>
	      <?php 
	      	$totales[$c]+=$res[$row1['cve']][$motivo];$c++;
	      	$total+=$res[$row1['cve']][$motivo];
	  	  }
	      ?>
	      <td align="right"><?php echo number_format($total,2);?></td>
	    </tr>
	<?php
		$totales[$c]+=$total;
		
	}
	?>
		<tr>
			<th style="text-align: right;">Totales:</th>
			<?php 
		      foreach($totales as $total){
		      ?>
				<th style="text-align: right;"><?php echo number_format($total,2);?></th>
			<?php 
		  	  }
		      ?>
		</tr>
	  </tbody>
	</table>
	

<?php
}

?>