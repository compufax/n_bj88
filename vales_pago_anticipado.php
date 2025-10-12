<?php
error_reporting(E_ERROR | E_PARSE);
require_once('cnx_db.php');
require_once('globales.php');

function obtener_informacion($datos){
	$select .= "SELECT a.cve, a.fecha, a.pago, b.numero_cliente as numdepositante, b.nombre as nomdepositante, IF(a.tipo=0, 'Pago Anticipado', 'Cortesia') as nomtipo, c.usuario, a.estatus";
	if($datos['mostrar']<2){
		$select .= ", d.cve as ticket, d.fecha as fecha_ticket";
	}
	$select .= " FROM vales_pago_anticipado a";
	if($datos['mostrar']<2){
		if($datos['mostrar']!=1){
			$select .= " LEFT";
		}
		else{
			$select .= " INNER";
		}
		$select .= " JOIN cobro_engomado d ON a.plaza = d.plaza AND a.cve = IF(d.tipo_venta=0, d.vale_pago_anticipado, d.codigo_cortesia) AND d.estatus!='C' AND ((d.tipo_venta=0 AND d.tipo_pago = 6) OR (d.tipo_venta=2 AND d.tipo_cortesia=2))";
	}
	$select .= " INNER JOIN depositantes b ON b.cve = a.depositante INNER JOIN usuarios c ON c.cve = a.usuario WHERE a.plaza='{$datos['cveplaza']}'";
	if($datos['busquedafolio'] != '') {
		$select .= " AND a.cve='{$datos['busquedafolio']}'";
	}

	else{
		if ($datos['busquedafechaini'] != ''){
			$select .= " AND a.fecha>='{$datos['busquedafechaini']}'";
		}
		if ($datos['busquedafechafin'] != ''){
			$select .= " AND a.fecha<='{$datos['busquedafechafin']}'";
		}
		if ($datos['busquedausuario']!="") { 
			$select.=" AND a.usuario='{$datos['busquedausuario']}' "; 
		}
		if ($datos['busquedaestatus']!="") { 
			$select.=" AND a.estatus='{$datos['busquedaestatus']}' "; 
		}
		if ($datos['busquedatipo_vale']!="") { 
			$select.=" AND a.tipo='{$datos['busquedatipo_vale']}' "; 
		}
		if ($datos['busquedadepositante']!="") { 
			$select.=" AND a.depositante='{$datos['busquedadepositante']}' "; 
		}

		if($datos['mostrar']==1) $select.=" AND a.usado=1";
		elseif($datos['mostrar']==2) $select.=" AND a.usado=0";
	}
	
	$select.=" ORDER BY a.cve DESC";
	$res = mysql_query($select);
	return $res;
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
			<label class="col-sm-2 col-form-label">Folio</label>
			<div class="col-sm-4">
            	<input type="number" class="form-control" id="busquedafolio" name="busquedafolio" placeholder="Folio" value="">
        	</div>
        	<label class="col-sm-2 col-form-label">Usuario</label>
			<div class="col-sm-4">
            	<select name="busquedausuario" id="busquedausuario" class="form-control" data-container="body" data-live-search="true" title="Usuario" data-hide-disabled="true" data-actions-box="true" data-virtual-scroll="false"><option value="">Todos</option>
            	<?php
            	$res1 = mysql_query("SELECT b.cve, b.usuario FROM (SELECT usuario FROM vales_pago_anticipado WHERE plaza='{$_POST['cveplaza']}' GROUP BY usuario) a INNER JOIN usuarios b ON b.cve = a.usuario ORDER BY b.usuario");
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
			<label class="col-sm-2 col-form-label">Depositante</label>
			<div class="col-sm-4">
            	<select name="busquedadepositante" id="busquedadepositante" class="form-control"><option value="">Todos</option>
            	<?php
            	$res1 = mysql_query("SELECT cve, nombre FROM depositantes WHERE plaza='{$_POST['cveplaza']}' AND tipo_depositante=0 ORDER BY nombre");
				while($row1=mysql_fetch_array($res1)){
					echo '<option value="'.$row1['cve'].'">'.utf8_encode($row1['nombre']).'</option>';
				}
				?>
            	</select>
        	</div>
        	<label class="col-sm-2 col-form-label">Mostrar</label>
			<div class="col-sm-4">
            	<select name="mostrar" id="mostrar" class="form-control"><option value="">Todos</option>
            		<option value="1">Usado</option>
            		<option value="2">Sin Usar</option>
            	</select>
        	</div>
        </div>
        <div class="form-group row">
        	<label class="col-sm-2 col-form-label">Tipo Vale</label>
			<div class="col-sm-4">
            	<select name="busquedatipo_vale" id="busquedatipo_vale" class="form-control"><option value="">Todos</option>
            		<option value="0">Pago Anticipado</option>
            		<option value="1">Cortesia</option>
            	</select>
        	</div>
        	<label class="col-sm-2 col-form-label">Estatus</label>
			<div class="col-sm-4">
            	<select name="busquedaestatus" id="busquedaestatus" class="form-control"><option value="">Todos</option>
            		<option value="A" selected>Activos</option>
            		<option value="C">Cancelados</option>
            	</select>
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
		  url: 'vales_pago_anticipado.php',
		  type: "POST",
		  data: {
			menu: $('#cvemenu').val(),
			cmd: 10,
			cveusuario: $('#cveusuario').val(),
			busquedafechaini: $('#busquedafechaini').val(),
			busquedafechafin: $('#busquedafechafin').val(),
			busquedafolio: $("#busquedafolio").val(),
    		busquedausuario: $("#busquedausuario").val(),
    		busquedaestatus: $("#busquedaestatus").val(),
    		busquedatipo_vale: $("#busquedatipo_vale").val(),
    		mostrar: $("#mostrar").val(),
    		busquedadepositante: $("#busquedadepositante").val(),
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
	      <th scope="col" style="text-align: center;">Folio</th>
	      <th scope="col" style="text-align: center;">Fecha</th>
	      <th scope="col" style="text-align: center;">Folio de Pago</th>
	      <th scope="col" style="text-align: center;">Nombre de Cliente</th> 
	      <th scope="col" style="text-align: center;">Tipo</th> 
	      <th scope="col" style="text-align: center;">Usuario</th> 
	      <th scope="col" style="text-align: center;">Ticket</th> 
	      <th scope="col" style="text-align: center;">Fecha Ticket</th> 
	    </tr>
	  </thead>
	  <tbody>
	<?php
		$i = 0;
		while($row = mysql_fetch_assoc($res)){
	?>
	    <tr>
	      <td align="center"><?php echo $row['cve'];?></td>
	      <td align="center"><?php echo $row['fecha'];?></td>
	      <td align="center"><?php echo $row['pago'];?></td>
	      <td align="left"><?php echo utf8_encode($row['nomdepositante']);?></td>
	      <td align="left"><?php echo ($row['nomtipo']);?></td>
	      <td align="left"><?php echo utf8_encode($row['usuario']);?></td>
	    <?php if($row['estatus']=='C'){ ?>
	      <td align="center" colspan="2">CANCELADO</td>
	    <?php } else { ?>
	      <td align="center"><?php echo $row['ticket'];?></td>
	      <td align="center"><?php echo $row['fecha_ticket'];?></td>
	  <?php } ?>
	    </tr>
	<?php
		$i++;
	}
	?>
		<tr>
			<th colspan="9" style="text-align: left;"><?php echo $i;?> Registro(s)</th>
		</tr>
	  </tbody>
	</table>
	

<?php
}

?>