<?php
require_once('cnx_db.php');
require_once('globales.php'); 
if($_POST['cmd']==3000){
	$res = mysql_query("SELECT a.id, a.nombre FROM usuarios a WHERE a.id = '{$_POST['registro_id']}'");
	$row = mysql_fetch_assoc($res);
	echo json_encode($row);
	exit();
}

if($_GET['ajax'] == 1000){
	$result = array();
	$filtro = "";
	if($_GET['filtro']!=''){
		$filtros = explode('|', $_GET['filtro']);
		foreach($filtros as $f){
			$datosfiltro = explode(':', $f);
			$filtro .= " AND {$datosfiltro[0]}='{$datosfiltro[1]}'";
		}
	}
	$res = mysql_query("SELECT a.id, a.nombre FROM usuarios a WHERE nombre LIKE '%{$_GET['term']}%' ORDER BY a.nombre LIMIT 10");
	while($row = mysql_fetch_assoc($res)){
		$result[] = array(
			'id' => $row['id'],
			'usuario_id' => $row['id'],
			'value' => utf8_encode($row['nombre']),
			'label' => utf8_encode($row['nombre'])
		);
	}
	echo json_encode($result);
	exit();
}

if($_POST['cmd']==2000){
?>
<table class="table table-bordered" id="dataTableBusqueda" width="100%" cellspacing="0">
	<thead>
			<tr>
				<th>Nombre</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Nombre</th>
			</tr>
		</tfoot>
</table>
<script>
	tablabusqueda = $('#dataTableBusqueda').DataTable( {
        "ajax": {
        	url: 'accesos.php',
        	type: "POST",
        	"data": {
        		"cmd": 2010,
        		"campo_id": "<?php echo $_POST['campo_id'];?>",
        		"campo_autocomplete": "<?php echo $_POST['campo_autocomplete'];?>",
        		"callback": "<?php echo $_POST['callback'];?>",
        		"filtro": "<?php echo $_POST['filtro'];?>"
        	}
        },
        "processing": true,
        "serverSide": true,
        "order": [[0, "ASC"]],
        "columnDefs": [
        	{ className: "dt-head-center dt-body-left", "targets": 0 }
		  ]
    } );
</script>
<?php
	exit();
}

if($_POST['cmd']==2010){
	$columnas=array("a.nombre");

	$orderby = "";
	foreach($_POST['order'] as $dato){
		$orderby .= ",{$columnas[$dato['column']]} {$dato['dir']}";
	}

	if($orderby == ""){
		$orderby = " ORDER BY a.nombre";
	}
	else{
		$orderby = " ORDER BY ".substr($orderby, 1);
	}

	$where = "";

	$where = "";

	if($_POST['search']['value'] != ''){
		$where .= " AND a.nombre LIKE '%{$_POST['search']['value']}%'";
	}

	
	if($_POST['filtro']!=''){
		$filtros = explode('|', $_POST['filtro']);
		foreach($filtros as $f){
			$datosfiltro = explode(':', $f);
			$where .= " AND {$datosfiltro[0]}='{$datosfiltro[1]}'";
		}
	}

	if($where != ''){
		$where = " WHERE ".substr($where, 5);
	}

	$res = mysql_query("SELECT COUNT(a.id) as registros FROM usuarios a{$where}");
	$registros = mysql_fetch_assoc($res);
	$resultado = array(
		'data' => array(),
		'draw'=> $_POST['draw'],
		'recordsTotal'=> $registros['registros'],
		'recordsFiltered'=> $registros['registros'],
	);
	$res = mysql_query("SELECT a.id, a.nombre FROM usuarios a {$where}{$orderby} LIMIT {$_POST['start']},{$_POST['length']}");
	$tmonto = 0;
	while($row = mysql_fetch_assoc($res)){
		$regresar = '$(\'#'.$_POST['campo_id'].'\').val(\''.$row['id'].'\');$(\'#'.$_POST['campo_autocomplete'].'\').val(\''.utf8_encode($row['nombre']).'\');';
		if($_POST['callback']!=''){
			$regresar .= $_POST['callback'].'(\''.$_POST['campo_id'].'\');';
		}
		$nombre = '<span style="cursor: pointer; color: BLUE;" onClick="'.$regresar.'$(\'#modalbusquedas\').modal(\'hide\');">'.utf8_encode($row['nombre']).'</span>';
		$resultado['data'][] = array(
			$nombre
		);
	}
	echo json_encode($resultado);

	
	exit();
}

require_once('validarloging.php');

if($_POST['cmd']==0){
?>

<div class="row justify-content-center">
	<div class="col-xl-6 col-lg-6 col-md-6">
		<div class="form-group row">
			<label class="col-sm-2 col-form-label">Usuario</label>
			<div class="col-sm-4">
            	<input type="text" class="form-control" id="busquedausuario" placeholder="Usuario">
        	</div>
        </div>
        <div class="form-group row">
			<label class="col-sm-2 col-form-label">Nombre</label>
			<div class="col-sm-4">
            	<input type="text" class="form-control" id="busquedanombre" placeholder="Nombre">
        	</div>
        </div>
        <div class="form-group row">
        	<div class="col-sm-6" align="center">
	        	<button type="button" class="btn btn-primary" onClick="buscar();">
	            	Buscar
	        	</button>&nbsp;
	        	<button type="button" class="btn btn-success" onClick="atcr('accesos.php','',1,0);">
	            	Nuevo
	        	</button>
        	</div>
        </div>
    </div>
</div>
<div class="table-responsive">
	<table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
    	<thead>
			<tr>
				<th>Nombre</th>
				<th>Usuario</th>
				<th>Plantilla</th>
				<th>Plazas</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Nombre</th>
				<th>Usuario</th>
				<th>Plantilla</th>
				<th>Plazas</th>
			</tr>
		</tfoot>
	</table>
</div>
<script>
	var tablalistado = $('#dataTable').DataTable( {
        "ajax": {
        	url: 'accesos.php',
        	type: "POST",
        	"data": {
        		"cmd": 10,
        		"busquedausuario": $("#busquedausuario").val(),
        		"busquedanombre": $("#busquedanombre").val(),
        		"cveusuario": $('#cveusuario').val()
        	}
        },
        "processing": true,
        "serverSide": true,
        "bFilter": false,
        "order": [[0, "ASC"]],
        "columnDefs": [
        	{ className: "dt-head-center dt-body-left", "targets": 0 },
        	{ className: "dt-head-center dt-body-left", "targets": 1 },
        	{ className: "dt-head-center dt-body-left", "targets": 2 },
        	{ className: "dt-head-center dt-body-left", "targets": 3 },
        	{ orderable: false, "targets": 3 }
		  ]
    } );
	function buscar(){
		tablalistado.ajax.data({
    		"cmd": 10,
    		"busquedausuario": $("#busquedausuario").val(),
        	"busquedanombre": $("#busquedanombre").val(),
        	"cveusuario": $('#cveusuario').val()
        });
        tablalistado.ajax.reload();
	}
</script>
<?php
}

if($_POST['cmd']==10){
	$columnas=array("a.nombre", 'a.usuario', 'b.nombre');

	$orderby = "";
	foreach($_POST['order'] as $dato){
		$orderby .= ",{$columnas[$dato['column']]} {$dato['dir']}";
	}

	if($orderby == ""){
		$orderby = " ORDER BY a.nombre";
	}
	else{
		$orderby = " ORDER BY ".substr($orderby, 1);
	}

	$where = "";

	if($_POST['busquedanombre'] != ''){
		$where .= " AND a.nombre LIKE '%{$_POST['busquedanombre']}%'";
	}

	if($_POST['busquedausuario'] != ''){
		$where .= " AND a.usuario LIKE '%{$_POST['busquedausuario']}%'";
	}

	if($_POST['cveusuario']!=1){
		$where .= " AND a.estatus!='I'";
	}

	if($where!=""){
		$where = " WHERE ".substr($where, 5);
	}

	$res = mysql_query("SELECT COUNT(cve) as registros FROM usuarios a{$where}");
	$registros = mysql_fetch_assoc($res);
	$resultado = array(
		'data' => array(),
		'draw'=> $_POST['draw'],
		'recordsTotal'=> $registros['registros'],
		'recordsFiltered'=> $registros['registros'],
	);
	$res = mysql_query("SELECT a.cve, a.nombre, a.usuario, b.nombre as nomplantilla, a.tipo, a.estatus FROM usuarios a LEFT JOIN cat_plantillas b ON b.cve = a.plantilla{$where} {$orderby} LIMIT {$_POST['start']},{$_POST['length']}");
	$tmonto = 0;
	
	while($row = mysql_fetch_assoc($res)){
		$plazas='';
		if($row['estatus']=='I'){
			$plazas='Inactivo';
		}
		elseif($row['tipo']==0 && $row['cve']>1){
			$res1=mysql_query("SELECT b.numero FROM usuario_accesos a INNER JOIN plazas b ON b.cve = a.plaza WHERE a.usuario='{$row['cve']}' AND a.acceso > 0 GROUP BY a.plaza ORDER BY b.lista");
			while($row1 = mysql_fetch_assoc($res1)){
				$plazas .= '<li>'.utf8_encode($row1['numero']).'</li>';
			}
			if ($plazas != ''){
				$plazas = '<ul>'.$plazas.'</ul>';
			}
		}
		$nombre = utf8_encode($row['nombre']);
		if($row['cve']!=1 || $_POST['cveusuario']==1){
			$nombre = '<span style="cursor: pointer" onClick="atcr(\'accesos.php\', \'\', 1, '.$row['cve'].')">'.utf8_encode($row['nombre']).'</span>';
		}
		$resultado['data'][] = array(
			$nombre,
			utf8_encode($row['usuario']),
			utf8_encode($row['nomplantilla']),
			$plazas
		);
	}
	echo json_encode($resultado);

}

if($_POST['cmd']==1){
	$res = mysql_query("SELECT * FROM usuarios WHERE cve='{$_POST['reg']}'");
	$row = mysql_fetch_assoc($res);
	$row = convertir_a_utf8($row);
?>
<div class="row justify-content-center">
	<div class="col-sm-12" align="center">
	<?php if (nivelUsuario() > 1) { ?>
		<button type="button" class="btn btn-success" onClick="atcr('accesos.php','',2,'<?php echo $_POST['reg']; ?>');">Guardar</button>
	<?php } ?>
	&nbsp;&nbsp;&nbsp;
		<button type="button" class="btn btn-primary" onClick="$('#contenedorprincipal').html('');atcr('accesos.php','',0,0);">Volver</button>
	</div>
</div><br>
<div class="row">
	<div class="col-xl-12 col-lg-12 col-md-12">
		<div class="card shadow">
			<div class="card-header">
				<h6 class="m-0 font-weight-bold text-secondary">Datos Generales</h6>
			</div>
			<div class="card-body">
				<div class="form-row">
			    	<div class="form-group col-sm-7">
						<label for="nombre">Nombre</label>
			            <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo $row['nombre'];?>">
			        </div>
			        <?php if($_POST['reg']>0 && $_POST['cveusuario']==1){?>
			        <div class="form-group col-sm-3">
						<label for="estatus">Estatus</label>
			            <select name="estatus" id="estatus" class="form-control">
			            	<option value="A">Activo</option>
			            	<option value="I"<?php if($row['estatus']=='I'){?> selected<?php }?>>Inactivo</option>
			            </select>
			        </div>
			    	<?php }?>
			    </div>
				<div class="form-row">
			        <div class="form-group col-sm-6">
						<label for="usuario">Usuario</label>
			            <input type="text" class="form-control" id="usuario" name="usuario" value="<?php echo $row['usuario'];?>">
			        </div>
			        <div class="form-group col-sm-5">
						<label for="contrasena">Contrase&ntilde;a</label>
			            <input type="password" class="form-control" id="contrasena" name="contrasena" value="">
			        </div>
			    </div>
			    
			    <div class="form-row"<?php if($_POST['cveusuario']!=1 && $_POST['reg']==1){ ?> style="display:none;"<?php } ?>>
			    	<div class="form-group col-sm-2">
						<label for="cerrar_portal">Cerrar Portal</label><br>
						<input type="checkbox" id="cerrar_portal" class="form-control" onClick="cambiar_check('cerrar_portal')" onChange="cambiar_check('cerrar_portal')"<?php if($row['cerrar_portal']==1){ ?> checked<?php } ?>>
						<input type="hidden" class="form-control" id="cerrar_portal_h" name="cerrar_portal" value="<?php echo $row['cerrar_portal'];?>">
			        </div>
			        <div class="form-group col-sm-10">
						<label for="correotimbres">Correo Timbres</label>
			            <input type="text" class="form-control" id="correotimbres" name="correotimbres" value="<?php echo $row['correotimbres'];?>">
			        </div>
			    </div>
			    <div class="form-row"<?php if($_POST['cveusuario']!=1 && $_POST['reg']==1){ ?> style="display:none;"<?php } ?>>
			        <div class="form-group col-sm-8">
						<label for="correo_reporte_ingresos_plazas">Correo Reporte de Ingreso Por Plaza</label>
			            <input type="text" class="form-control" id="correo_reporte_ingresos_plazas" name="correo_reporte_ingresos_plazas" value="<?php echo $row['correo_reporte_ingresos_plazas'];?>">
			        </div>
			        <div class="form-group col-sm-2">
						<label for="validar_huella">Validar Huella</label><br>
						<input type="checkbox" id="validar_huella" class="form-control" onClick="cambiar_check('validar_huella')" onChange="cambiar_check('validar_huella')"<?php if($row['validar_huella']==1){ ?> checked<?php } ?>>
						<input type="hidden" class="form-control" id="validar_huella_h" name="validar_huella" value="<?php echo $row['validar_huella'];?>">
			        </div>
			        <div class="form-group col-sm-2">
						<label for="recargar_facturas">Recargar Facturas</label><br>
						<input type="checkbox" id="recargar_facturas" class="form-control" onClick="cambiar_check('recargar_facturas')" onChange="cambiar_check('recargar_facturas')"<?php if($row['validar_huella']==1){ ?> checked<?php } ?>>
						<input type="hidden" class="form-control" id="recargar_facturas_h" name="recargar_facturas" value="<?php echo $row['recargar_facturas'];?>">
			        </div>
			    </div>
			    <div class="form-row"<?php if($_POST['cveusuario']!=1){ ?> style="display:none;"<?php } ?>>
			    	<div class="form-group col-sm-4">
						<label for="plantilla">Plantilla</label>
			            <select  name="plantilla" class="form-control" data-container="body" data-live-search="true" title="Plantilla" data-hide-disabled="true" data-actions-box="true" data-virtual-scroll="false" id="plantilla">
						<?php
						$res2 = mysql_query("SELECT cve, nombre FROM cat_plantillas ORDER BY nombre");
						while($row2 = mysql_fetch_assoc($res2)){
			            	echo '<option value="'.$row2['cve'].'"';
			            	if ($row2['cve']==$row['plantilla']) {
			            		echo ' selected';
			            	}
			            	echo '>'.utf8_encode($row2['nombre']).'</option>';
			            }
						?>
						</select>
						<script>
							$("#plantilla").selectpicker();	
						</script>
			        </div>
			        <div class="form-group col-sm-4">
						<label for="proveedor_mantenimiento">Proveedor Mantenimiento</label>
			            <select  name="proveedor_mantenimiento" class="form-control" data-container="body" data-live-search="true" title="Proveedor Mantenimiento" data-hide-disabled="true" data-actions-box="true" data-virtual-scroll="false" id="proveedor_mantenimiento">
						<?php
						$res2 = mysql_query("SELECT cve, nombre FROM proveedores ORDER BY nombre");
						while($row2 = mysql_fetch_assoc($res2)){
			            	echo '<option value="'.$row2['cve'].'"';
			            	if ($row2['cve']==$row['proveedor_mantenimiento']) {
			            		echo ' selected';
			            	}
			            	echo '>'.utf8_encode($row2['nombre']).'</option>';
			            }
						?>
						</select>
						<script>
							$("#proveedor_mantenimiento").selectpicker();	
						</script>
			        </div>
			    </div>
			    <div class="form-row"<?php if($_POST['cveusuario']!=1){ ?> style="display:none;"<?php } ?>>
			    	<div class="form-group col-sm-5">
						<label for="ide">IDE</label>
			            <input type="text" class="form-control" id="ide" name="ide" value="<?php echo $row['ide'];?>">
			        </div>
			        <div class="form-group col-sm-2">
			        	<label for="permite_editar">Permite Editar</label><br>
						<input type="checkbox" id="permite_editar" class="form-control" onClick="cambiar_check('permite_editar')" onChange="cambiar_check('permite_editar')"<?php if($row['permite_editar']==1){ ?> checked<?php } ?>>
						<input type="hidden" class="form-control" id="permite_editar_h" name="permite_editar" value="<?php echo $row['permite_editar'];?>">
					</div>
			    </div>
			    <div class="form-row"<?php if($_POST['cveusuario']!=1 || $row['cve']!=1){ ?> style="display:none;"<?php } ?>>
			    	<div class="form-group col-sm-5">
						<label for="descuento10dias">Descuento 10 dias</label>
			            <input type="numeric" class="form-control" id="descuento10dias" name="descuento10dias" value="<?php echo $row['descuento10dias'];?>">
			        </div>
			        <div class="form-group col-sm-2">
			        	<label for="descuento15dias">Descuento 15 dias</label><br>
						<input type="numeric" class="form-control" id="descuento15dias" name="descuento15dias" value="<?php echo $row['descuento15dias'];?>">
					</div>
			    </div>
			    <div class="form-row">
			    	<div class="form-group col-sm-3"<?php if($_POST['cveusuario']!=1){ ?> style="display:none;"<?php } ?>>
						<label for="tipo">Tipo Usuario</label>
			            <select name="tipo" class="form-control" id="tipo" onChange="mostrar_permisos()">
			            	<option value="0">Normal</option>
			            	<option value="1"<?php if($row['tipo']==1){ ?> selected<?php } ?>>Administrador</option>
			            </select>
			        </div>
					<div class="form-group col-sm-5"<?php if($row['tipo']==1){ ?> style="display:none;"<?php } ?>>
						<label for="plaza">Plaza</label>
			            <select name="plaza" class="form-control" data-container="body" data-live-search="true" title="Plaza" data-hide-disabled="true" data-actions-box="true" data-virtual-scroll="false" id="plaza" onChange="traerPermisosPlaza()"><option value="">Seleccione para ver Permisos</option>
						<?php
						$res2 = mysql_query("SELECT cve, numero, nombre FROM plazas ORDER BY numero, nombre");
						while($row2 = mysql_fetch_assoc($res2)){
			            	echo '<option value="'.$row2['cve'].'">'.utf8_encode($row2['numero'].' '.$row2['nombre']).'</option>';
			            }
						?>
						</select>
						<script>
							$("#plaza").selectpicker();	
						</script>
			        </div>
			        
			    </div>
			</div>
		</div>
		<br>
		<div class="card shadow" id="divpermisos" style="display: none;">
			<div class="card-header">
				<h6 class="m-0 font-weight-bold text-secondary">Permisos</h6>
			</div>
		</div>
		
    </div>
</div>
<script>
	function mostrar_permisos(){
		if($('#tipo').val() == '0' && $('#plaza').val() != ""){
			$('#divpermisos').show();
		}
		else{
			$('#divpermisos').hide();	
		}
	}

	function traerPermisosPlaza(){
		$('.permisosplaza').hide();
		if($('#plaza').val()!=''){
			if($('#divpermisosplaza_'+$('#plaza').val()).length > 0) {
				$('#divpermisosplaza_'+$('#plaza').val()).show();
				mostrar_permisos();
			}
			else{
				waitingDialog.show();
				$.ajax({
					url: 'accesos.php',
					type: "POST",
					data: {
						cmd: 20,
						usuario: '<?php echo $_POST['reg'];?>',
						plaza: $('#plaza').val()
					},
					success: function(data) {
						waitingDialog.hide();
						$('#divpermisos').append(data);
						mostrar_permisos();
					}
				});
			}
		}
		else{
			mostrar_permisos();
		}
	}

	function activar_acceso(clase) {
		$('.'+clase).each(function(){
			this.checked = true;
		});
	}

	$('input[type=checkbox]').bootstrapToggle()
</script>
<?php
}

if($_POST['cmd']==2){
	$_POST['usuario']=trim($_POST['usuario']);
	$resultado = array('error' => 0, 'mensaje' => '');
	if(trim($_POST['nombre'])==''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar el nombre');
	}
	elseif(trim($_POST['usuario']) == ''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar el usuario');
	}
	else{
		$res = mysql_query("SELECT cve FROM usuarios WHERE usuario = '{$_POST['usuario']}' AND cve != '{$_POST['reg']}'");
		if($row = mysql_fetch_assoc($res)){
			$resultado = array('error' => 1, 'mensaje' => 'El usuario ya esta registrado');
		}
	}
	if($resultado['error']==1){
		echo json_encode($resultado);
	}
	else{
		$campo_contrasena = "";
		if ($_POST['contrasena'] != '') {
			$contrasena = $_POST['contrasena'];
			$campo_contrasena = ", password='{$contrasena}'";
		}
		

		if($_POST['reg']>0){
			$select=" SELECT * FROM usuarios WHERE cve='".$_POST['reg']."' ";
			$rssuario=mysql_query($select);
			$Usuario=mysql_fetch_array($rssuario);
			if($Usuario['plantilla']!=$_POST['plantilla']){
				mysql_query("INSERT historial SET menu='".$_POST['cvemenu']."',cveaux='".$_POST['reg']."',fecha=NOW(),
				dato='Plantilla',nuevo='".$_POST['plantilla']."',anterior='".$Usuario['plantilla']."',arreglo='plantilla',usuario='".$_POST['cveusuario']."'");
			}
			if($Usuario['nombre']!=$_POST['nombre']){
				mysql_query("INSERT historial SET menu='".$_POST['cvemenu']."',cveaux='".$_POST['reg']."',fecha=NOW(),
				dato='Nombre',nuevo='".$_POST['nombre']."',anterior='".$Usuario['nombre']."',arreglo='',usuario='".$_POST['cveusuario']."'");
			}
			if($Usuario['password']!=$_POST['password']){
				mysql_query("INSERT historial SET menu='".$_POST['cvemenu']."',cveaux='".$_POST['reg']."',fecha=NOW(),
				dato='Password',nuevo='".$_POST['password']."',anterior='".$Usuario['password']."',arreglo='',usuario='".$_POST['cveusuario']."'");
			}
			if($Usuario['tipo']!=intval($_POST['tipo'])){
				mysql_query("INSERT historial SET menu='".$_POST['cvemenu']."',cveaux='".$_POST['reg']."',fecha=NOW(),
				dato='Tipo',nuevo='".intval($_POST['tipo'])."',anterior='".$Usuario['tipo']."',arreglo='tipo',usuario='".$_POST['cveusuario']."'");
			}

			if (isset($_POST['estatus'])){
				$campo_contrasena.=", estatus='{$_POST['estatus']}'";
			}
			
			mysql_query("UPDATE usuarios 
					SET nombre='{$_POST['nombre']}', usuario='{$_POST['usuario']}', permite_editar='{$_POST['permite_editar']}', cerrar_portal='{$_POST['cerrar_portal']}',
					tipo='{$_POST['tipo']}', ide='{$_POST['ide']}', categoria='{$_POST['categoria']}', validar_huella='{$_POST['validar_huella']}',
					recargar_facturas='{$_POST['recargar_facturas']}', plantilla='{$_POST['plantilla']}',
					proveedor_mantenimiento='{$_POST['proveedor_mantenimiento']}', correotimbres='{$_POST['correotimbres']}', correo_reporte_ingresos_plazas='{$_POST['correo_reporte_ingresos_plazas']}', descuento10dias='{$_POST['descuento10dias']}', descuento15dias='{$_POST['descuento15dias']}'{$campo_contrasena}
					WHERE cve='{$_POST['reg']}'");
			$mensaje = 'Se actualizo exitosamente';
			$id = $_POST['reg'];
		}
		else{
			mysql_query("INSERT usuarios 
					SET nombre='{$_POST['nombre']}', usuario='{$_POST['usuario']}', permite_editar='{$_POST['permite_editar']}', cerrar_portal='{$_POST['cerrar_portal']}',
					tipo='{$_POST['tipo']}', ide='{$_POST['ide']}', categoria='{$_POST['categoria']}', validar_huella='{$_POST['validar_huella']}',
					recargar_facturas='{$_POST['recargar_facturas']}', plantilla='{$_POST['plantilla']}',
					proveedor_mantenimiento='{$_POST['proveedor_mantenimiento']}', correotimbres='{$_POST['correotimbres']}', correo_reporte_ingresos_plazas='{$_POST['correo_reporte_ingresos_plazas']}', descuento10dias='{$_POST['descuento10dias']}', descuento15dias='{$_POST['descuento15dias']}'{$campo_contrasena}");
			$mensaje = 'Se registro exitosamente';
			$id = mysql_insert_id();

			mysql_query("INSERT historial SET menu='{$_POST['cvemenu']}', cveaux='{$id}', fecha=NOW(), dato='Estatus', nuevo='A', anterior='', arreglo='', usuario='{$_POST['cveusuario']}'");
		}

		if(is_array($_POST['acceso'])){
			foreach($_POST['acceso'] as $plaza => $menus){
				foreach($menus as $menu => $nivel){
					$res1 = mysql_query("SELECT cve, acceso FROM usuario_accesos WHERE menu='{$menu}' AND plaza='{$plaza}' AND usuario='{$id}'");
					if($row1 = mysql_fetch_assoc($res1)){
						mysql_query("UPDATE usuario_accesos SET acceso='{$nivel}' WHERE cve='{$row1['cve']}'");
						if($nivel!=$row1['acceso']){
							mysql_query("INSERT historial SET menu='{$_POST['cvemenu']}', cveaux='{$id}', fecha=NOW(), dato='{$menu}', nuevo='{$nivel}',anterior='{$row1['acceso']}',arreglo='',usuario='".$_POST['cveusuario']."'");
						}
					}
					else{
						mysql_query("INSERT usuario_accesos SET usuario='{$id}',menu='{$menu}',acceso='{$nivel}',plaza='{$plaza}'");
						if($nivel>0){
							mysql_query("INSERT historial SET menu='{$_POST['cvemenu']}', cveaux='{$id}', fecha=NOW(), dato='{$menu}', nuevo='{$nivel}',anterior='',arreglo='',usuario='".$_POST['cveusuario']."'");
						}
					}
				}
			}
		}

		
	

		echo '<script>sweetAlert("Existoso","'.$mensaje.'", "success");$("#contenedorprincipal").html("");atcr("accesos.php","",0,"'.$_POST['cveusuario'].'");</script>';
	}
	
}

if($_POST['cmd']==20){
?>
	<div class="card-body permisosplaza" id="divpermisosplaza_<?php echo $_POST['plaza'];?>">
		<div class="row">
			<div class="col-sm-4 text-primary">&nbsp;</div>
			<div class="col-sm-2 text-primary" align="center"><input type="radio" onClick="activar_acceso('acceso_<?php echo $_POST['plaza'].'_0';?>')" class="form-check-input" name=" acceso_<?php echo $_POST['plaza'];?>" value="0"></div>
			<div class="col-sm-2 text-primary" align="center"><input type="radio" onClick="activar_acceso('acceso_<?php echo $_POST['plaza'].'_1';?>')" class="form-check-input" name=" acceso_<?php echo $_POST['plaza'];?>" value="0"></div>
			<div class="col-sm-2 text-primary" align="center"><input type="radio" onClick="activar_acceso('acceso_<?php echo $_POST['plaza'].'_2';?>')" class="form-check-input" name=" acceso_<?php echo $_POST['plaza'];?>" value="0"></div>
			<div class="col-sm-2 text-primary" align="center"><input type="radio" onClick="activar_acceso('acceso_<?php echo $_POST['plaza'].'_3';?>')" class="form-check-input" name=" acceso_<?php echo $_POST['plaza'];?>" value="0"></div>
		</div>
<?php
	foreach($array_modulos as $k=>$v){
		if($k!=99 && $k!=3){
?>
			<div class="col-sm-12 text-danger"><h5><?php echo ($v);?></h5></div>
			<div class="row">
				<div class="col-sm-4 text-primary">Menu</div>
				<div class="col-sm-2 text-primary" align="center">Sin Acceso</div>
				<div class="col-sm-2 text-primary" align="center">Lectura</div>
				<div class="col-sm-2 text-primary" align="center">Escritura</div>
				<div class="col-sm-2 text-primary" align="center">Supervisor</div>
			</div>

<?php
			$res1 = mysql_query("SELECT a.cve, a.nombre, IFNULL(b.acceso,0) as acceso FROM menu a LEFT JOIN usuario_accesos b ON a.cve = b.menu AND b.usuario = '{$_POST['usuario']}' AND b.plaza='{$_POST['plaza']}' AND b.usuario>0 WHERE a.modulo='{$k}' ORDER BY a.orden");
			while($row1 = mysql_fetch_assoc($res1)){
?>
			<div class="form-row">
				<div class="col-sm-4"><?php echo ($row1['nombre']);?></div>
				<div class="col-sm-2" align="center"><input type="radio" class="form-check-input acceso_<?php echo $_POST['plaza'].'_0';?>" name="acceso[<?php echo $_POST['plaza'];?>][<?php echo $row1['cve'];?>]" value="0"<?php if($row1['acceso'] == 0) echo ' checked'; ?>></div>
				<div class="col-sm-2" align="center"><input type="radio" class="form-check-input acceso_<?php echo $_POST['plaza'].'_1';?>" name="acceso[<?php echo $_POST['plaza'];?>][<?php echo $row1['cve'];?>]" value="1"<?php if($row1['acceso'] == 1) echo ' checked'; ?>></div>
				<div class="col-sm-2" align="center"><input type="radio" class="form-check-input acceso_<?php echo $_POST['plaza'].'_2';?>" name="acceso[<?php echo $_POST['plaza'];?>][<?php echo $row1['cve'];?>]" value="2"<?php if($row1['acceso'] == 2) echo ' checked'; ?>></div>
				<div class="col-sm-2" align="center"><input type="radio" class="form-check-input acceso_<?php echo $_POST['plaza'].'_3';?>" name="acceso[<?php echo $_POST['plaza'];?>][<?php echo $row1['cve'];?>]" value="3"<?php if($row1['acceso'] == 3) echo ' checked'; ?>></div>
			</div>
<?php
			}
		}
	}
?>
	</div>
<?php
}
?>