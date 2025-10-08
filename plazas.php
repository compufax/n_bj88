<?php
require_once('cnx_db.php');
require_once('globales.php'); 
if($_POST['cmd']==3000){
	$res = mysql_query("SELECT a.id, a.numero, a.nombre FROM plazas a WHERE a.id = '{$_POST['registro_id']}'");
	$row = mysql_fetch_assoc($res);
	foreach($row as &$valor) {
		$valor = utf8_encode($valor);
	}
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
	$res = mysql_query("SELECT a.id, a.nombre FROM plazas a WHERE nombre LIKE '%{$_GET['term']}%' OR numero LIKE '%{$_GET['term']}%' ORDER BY a.nombre LIMIT 10");
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
				<th>N&uacte;mero</th><th>Nombre</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>N&uacte;mero</th><th>Nombre</th>
			</tr>
		</tfoot>
</table>
<script>
	tablabusqueda = $('#dataTableBusqueda').DataTable( {
        "ajax": {
        	url: 'plazas.php',
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
        	{ className: "dt-head-center dt-body-left", "targets": 0 },
        	{ className: "dt-head-center dt-body-left", "targets": 1 }
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
		$where .= " AND (a.nombre LIKE '%{$_POST['search']['value']}%' OR a.numero LIKE '%{$_POST['search']['value']}%')";
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

	$res = mysql_query("SELECT COUNT(a.id) as registros FROM plazas a{$where}");
	$registros = mysql_fetch_assoc($res);
	$resultado = array(
		'data' => array(),
		'draw'=> $_POST['draw'],
		'recordsTotal'=> $registros['registros'],
		'recordsFiltered'=> $registros['registros'],
	);
	$res = mysql_query("SELECT a.id, a.nombre, a.numero FROM plazas a {$where}{$orderby} LIMIT {$_POST['start']},{$_POST['length']}");
	$tmonto = 0;
	while($row = mysql_fetch_assoc($res)){
		$regresar = '$(\'#'.$_POST['campo_id'].'\').val(\''.$row['id'].'\');$(\'#'.$_POST['campo_autocomplete'].'\').val(\''.utf8_encode($row['nombre']).'\');';
		if($_POST['callback']!=''){
			$regresar .= $_POST['callback'].'(\''.$_POST['campo_id'].'\');';
		}
		$numero = '<span style="cursor: pointer; color: BLUE;" onClick="'.$regresar.'$(\'#modalbusquedas\').modal(\'hide\');">'.utf8_encode($row['numero']).'</span>';
		$nombre = '<span style="cursor: pointer; color: BLUE;" onClick="'.$regresar.'$(\'#modalbusquedas\').modal(\'hide\');">'.utf8_encode($row['nombre']).'</span>';
		$resultado['data'][] = array(
			$numero, 
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
	<div class="col-xl-12 col-lg-12 col-md-12">
		<div class="form-group row">
			<label class="col-sm-2 col-form-label">N&uacute;mero</label>
			<div class="col-sm-4">
            	<input type="text" class="form-control" id="busquedanumero" placeholder="N&uacute;mero">
        	</div>
			<label class="col-sm-2 col-form-label">Nombre</label>
			<div class="col-sm-4">
            	<input type="text" class="form-control" id="busquedanombre" placeholder="Nombre">
        	</div>
        </div>
        <div class="form-group row">
        	<div class="col-sm-12" align="center">
	        	<button type="button" class="btn btn-primary" onClick="buscar();">
	            	Buscar
	        	</button>&nbsp;
	        	<button type="button" class="btn btn-success" onClick="atcr('plazas.php','',1,0);">
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
				<th>N&uacute;mero</th>
				<th>ID Plaza</th>
				<th>ID Certificado</th>
				<th>Tipo Plaza</th>
				<th>Serie Factura</th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Nombre</th>
				<th>N&uacute;mero</th>
				<th>ID Plaza</th>
				<th>ID Certificado</th>
				<th>Tipo Plaza</th>
				<th>Serie Factura</th>
				<th>&nbsp;</th>
			</tr>
		</tfoot>
	</table>
</div>
<script>
	var tablalistado = $('#dataTable').DataTable( {
        "ajax": {
        	url: 'plazas.php',
        	type: "POST",
        	"data": {
        		"cmd": 10,
        		"busquedanumero": $("#busquedanumero").val(),
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
        	{ className: "dt-head-center dt-body-center", "targets": 2 },
        	{ className: "dt-head-center dt-body-center", "targets": 3 },
        	{ className: "dt-head-center dt-body-center", "targets": 4 },
        	{ className: "dt-head-center dt-body-center", "targets": 5 },
        	{ className: "dt-head-center dt-body-center", "targets": 6 },
        	{ orderable: false, "targets": 6 }
		  ]
    } );
	function buscar(){
		tablalistado.ajax.data({
    		"cmd": 10,
    		"busquedanumero": $("#busquedanumero").val(),
        	"busquedanombre": $("#busquedanombre").val(),
        	"cveusuario": $('#cveusuario').val()
        });
        tablalistado.ajax.reload();
	}
</script>
<?php
}

if($_POST['cmd']==10){
	$columnas=array("a.nombre", 'a.numero', 'd.idplaza', 'd.idcertificado', 'b.nombre', 'c.serie');

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

	if($_POST['busquedanumero'] != ''){
		$where .= " AND a.numero LIKE '%{$_POST['busquedanumero']}%'";
	}

	$res = mysql_query("SELECT COUNT(a.cve) as registros FROM plazas a WHERE 1{$where}");
	$registros = mysql_fetch_assoc($res);
	$resultado = array(
		'data' => array(),
		'draw'=> $_POST['draw'],
		'recordsTotal'=> $registros['registros'],
		'recordsFiltered'=> $registros['registros'],
	);
	$res = mysql_query("SELECT a.cve, a.nombre, a.numero, d.idplaza, d.idcertificado, IFNULL(b.nombre,'') as nomtipo, IFNULL(c.serie,'') as serie FROM plazas a LEFT JOIN tipo_plaza b ON b.cve = a.tipo_plaza LEFT JOIN foliosiniciales c ON a.cve = c.plaza AND c.tipo=0 AND c.tipodocumento=1 LEFT JOIN datosempresas d ON a.cve = d.plaza WHERE 1{$where}{$orderby} LIMIT {$_POST['start']},{$_POST['length']}");
	$tmonto = 0;
	while($row = mysql_fetch_assoc($res)){
		foreach($row as &$valor){
			$valor = utf8_encode($valor);
		}
		$resultado['data'][] = array(
			$row['nombre'],
			($row['numero']),
			$row['idplaza'],
			$row['idcertificado'],
			$row['nomtipo'],
			$row['serie'],
			'<span class="btn btn-circle btn-info" style="cursor:pointer;"><i class="fas fa-edit" onClick="atcr(\'plazas.php\',\'\',1,'.$row['cve'].')" title="Editar"></i></span>'
		);
	}
	echo json_encode($resultado);

}

if($_POST['cmd']==1){
	$res = mysql_query("SELECT * FROM plazas WHERE cve='{$_POST['reg']}'");
	$row = mysql_fetch_assoc($res);
	$row = convertir_a_utf8($row);
	$resd = mysql_query("SELECT * FROM datosempresas WHERE plaza='{$_POST['reg']}'");
	$rowd = mysql_fetch_assoc($resd);
	$rowd = convertir_a_utf8($rowd);
	$logo ="logos/logo{$_POST['reg']}.jpg";
	if(!file_exists($logo)){
		$logo = "img/noimage.png";
	}
?>
<div class="row justify-content-center">
	<div class="col-sm-12" align="center">
	<?php if (nivelUsuario() > 1) { ?>
		<button type="button" class="btn btn-success" onClick="atcr('plazas.php','',2,'<?php echo $_POST['reg']; ?>');">Guardar</button>
	&nbsp;&nbsp;&nbsp;
	<?php } ?>
		<button type="button" class="btn btn-primary" onClick="$('#contenedorprincipal').html('');atcr('plazas.php','',0,0);">Volver</button>
	</div>
</div><br>
<div class="row">
	<div class="col-xl-9 col-lg-9 col-md-9">
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
			        <div class="form-group col-sm-2">
						<label for="numero">N&uacute;mero</label>
			            <input type="text" class="form-control" id="numero" name="camposp[numero]" value="<?php echo $row['numero'];?>">
			        </div>
			    </div>
			    <div class="form-row">
			    	<div class="form-group col-sm-7">
						<label for="tipo_plaza">Tipo Plaza</label>
			            <select name="camposp[tipo_plaza]" id="tipo_plaza" class="form-control"><option value="">Seleccione</option>
			            <?php
			            	$res1 = mysql_query("SELECT cve, nombre FROM tipo_plaza ORDER BY nombre");
			            	while($row1 = mysql_fetch_assoc($res1)){
			            		echo '<option value="'.$row1['cve'].'"';
			            		if($row['tipo_plaza'] == $row1['cve']) echo ' selected';
			            		echo '>'.utf8_encode($row1['nombre']).'</option>';
			            	}
			            ?>
			            </select>
			        </div>
			        <div class="form-group col-sm-2">
						<label for="estatus">Estatus</label>
			            <select name="camposp[estatus]" id="estatus" class="form-control"><option value="A">Activa</option>
			            <?php if($_POST['reg']>0){ ?>
			            	<option value="I"<?php if($row['estatus']=='I'){ ?> selected<?php } ?>>Inactiva</option>
			            <?php } ?>
			        	</select>
			        </div>
			    </div>
			    <div class="form-row">
			    	<div class="form-group col-sm-6">
						<label for="email">Email</label>
			            <input type="email" class="form-control" id="email" name="camposd[email]" value="<?php echo $rowd['email'];?>">
			        </div>
			    </div>
			    <div class="form-row">
			    	<div class="form-group col-sm-6">
						<label for="calle">Calle</label>
			            <input type="text" class="form-control" id="calle" name="camposd[calle]" value="<?php echo $rowd['calle'];?>">
			        </div>
			        <div class="form-group col-sm-3">
						<label for="numexterior">No. Exterior</label>
			            <input type="text" class="form-control" id="numexterior" name="camposd[numexterior]" value="<?php echo $rowd['numexterior'];?>">
			        </div>
			        <div class="form-group col-sm-3">
						<label for="numinterior">No. Interior</label>
			            <input type="text" class="form-control" id="numinterior" name="camposd[numinterior]" value="<?php echo $rowd['numinterior'];?>">
			        </div>
			    </div>
			    <div class="form-row">
			    	<div class="form-group col-sm-6">
						<label for="colonia">Colonia</label>
			            <input type="text" class="form-control" id="colonia" name="camposd[colonia]" value="<?php echo $rowd['colonia'];?>">
			        </div>
			        <div class="form-group col-sm-6">
						<label for="municipio">Municipio</label>
			            <input type="text" class="form-control" id="municipio" name="camposd[municipio]" value="<?php echo $rowd['municipio'];?>">
			        </div>
			    </div>
			    <div class="form-row">
			    	<div class="form-group col-sm-6">
						<label for="estado">Estado</label>
			            <input type="text" class="form-control" id="estado" name="camposd[estado]" value="<?php echo $rowd['estado'];?>">
			        </div>
			        <div class="form-group col-sm-2">
						<label for="codigopostal">C&oacute;digo Postal</label>
			            <input type="text" class="form-control" id="codigopostal" name="camposd[codigopostal]" value="<?php echo $rowd['codigopostal'];?>">
			        </div>
			    </div>
			</div>
		</div>
	</div>
	<div class="col-xl-3 col-lg-3 col-md-3">
    	<div class="card shadow">
    		<div class="card-header">
				<h6 class="m-0 font-weight-bold text-secondary">Logo</h6>
			</div>
			<div class="card-body">
				<div class="form-group">
					<!--<input type="file" class="form-control" id="foto" name="foto" value="">-->
					<div class="custom-file">
		            	<input name="foto" id="foto" class="custom-file-input" multiple="" type="file" accept="image/jpeg">
			            <label class="custom-file-label" for="foto" data-browse="Buscar">Seleccione Logo</label>
		            </div>
				</div>
				<div class="form-group row">
					<div class="col-sm-2">
						<input type="hidden" name="borrar_foto" id="borrar_foto_hidden" value="0">
			            <input type="checkbox" class="form-control" id="borrar_foto" onClick="if(this.checked) $('#borrar_foto_hidden').val('1'); else $('#borrar_foto_hidden').val('0');" value="1">
			        </div>
					<label for="borrar_foto" class="col-sm-10 col-form-label">Borrar Logo</label>
				</div>
				<div class="form-group">
					<img width="100%" height="100%" src="<?php echo $logo;?>?<?php echo date('h:i:s');?>" border="1">
				</div>
			</div>
    	</div>
    </div>
	<div class="col-xl-12 col-lg-12 col-md-12">
		<div class="card shadow">
			<div class="card-header">
				<h6 class="m-0 font-weight-bold text-secondary">Datos Fiscales</h6>
			</div>
			<div class="card-body">
				<div class="form-row">
			    	<div class="form-group col-sm-4">
						<label for="rfc">RFC</label>
			            <input type="text" class="form-control" id="rfc" name="camposd[rfc]" value="<?php echo $rowd['rfc'];?>">
			        </div>
			        <div class="form-group col-sm-8">
						<label for="regimensat">R&eacute;gimen SAT</label>
			            <select class="form-control" data-container="body" name="camposp[regimensat]" data-live-search="true" title="Regimen SAT" data-hide-disabled="true" data-actions-box="true" data-virtual-scroll="false" id="regimensat"><option value="">Seleccione</option>
						<?php
						$res1 = mysql_query("SELECT clave, nombre FROM regimen_sat ORDER BY clave, nombre");
						while($tipo = mysql_fetch_assoc($res1)){
							echo '<option value="'.$tipo['clave'].'"';
							if($tipo['clave']==$row['regimensat']) echo ' selected';
							echo '>'.$tipo['clave'].' '.utf8_encode($tipo['nombre']).'</option>';
						}
						?>
						</select>
						<script>
							$("#regimensat").selectpicker();	
						</script>
			        </div>
			    </div>
				<div class="form-row">
			        <div class="form-group col-sm-6">
						<label for="regimen">R&eacute;gimen</label>
			            <input type="text" class="form-control" id="regimen" name="camposd[regimen]" value="<?php echo $rowd['regimen'];?>">
			        </div>
			        <div class="form-group col-sm-3">
						<label for="registro_patronal">Registro Patronal</label>
			            <input type="text" class="form-control" id="registro_patronal" name="camposd[registro_patronal]" value="<?php echo $rowd['registro_patronal'];?>">
			        </div>
			    </div>
			</div>
		</div>
		<div class="card shadow">
			<div class="card-header">
				<h6 class="m-0 font-weight-bold text-secondary">Datos Timbrado</h6>
			</div>
			<div class="card-body">
			    <div class="form-row">
			        <div class="form-group col-sm-6">
						<label for="usuariows">Usuario Timbrar</label>
						<input type="text" class="form-control" id="usuariows" name="camposd[usuario]" value="<?php echo $rowd['usuario'];?>">
					</div>
					<div class="form-group col-sm-6">
						<label for="passws">Password Timbrar</label>
						<input type="text" class="form-control" id="passws" name="camposd[pass]" value="<?php echo $rowd['pass'];?>">
					</div>
				</div>
				<div class="form-row">
			        <div class="form-group col-sm-2">
						<label for="idplaza">ID Plaza</label>
						<input type="text" class="form-control" id="idplaza" name="camposd[idplaza]" value="<?php echo $rowd['idplaza'];?>">
					</div>
					<div class="form-group col-sm-2">
						<label for="idcertificado">ID Certificado</label>
						<input type="text" class="form-control" id="idcertificado" name="camposd[idcertificado]" value="<?php echo $rowd['idcertificado'];?>">
					</div>
					<?php
					$resf = mysql_query("SELECT * FROM foliosiniciales WHERE plaza='{$_POST['reg']}' AND tipo=0 AND tipodocumento=1");
					$rowf = mysql_fetch_assoc($resf);
					?>
					<div class="form-group col-sm-3">
						<label for="folio_inicial_1">Folio Inicial Factura</label>
						<input type="text" class="form-control" id="folio_inicial_1" name="camposf[1][folio_inicial]" value="<?php echo $rowf['folio_inicial'];?>">
					</div>
					<div class="form-group col-sm-3">
						<label for="serie_inicial_1">Serie Factura</label>
						<input type="text" class="form-control" id="serie_inicial_1" name="camposf[1][serie]" value="<?php echo $rowf['serie'];?>">
					</div>
				</div>
				<div class="form-row">
					<?php
					$resf = mysql_query("SELECT * FROM foliosiniciales WHERE plaza='{$_POST['reg']}' AND tipo=0 AND tipodocumento=2");
					$rowf = mysql_fetch_assoc($resf);
					?>
					<div class="form-group col-sm-3">
						<label for="folio_inicial_2">Folio Inicial NC</label>
						<input type="text" class="form-control" id="folio_inicial_2" name="camposf[2][folio_inicial]" value="<?php echo $rowf['folio_inicial'];?>">
					</div>
					<div class="form-group col-sm-3">
						<label for="serie_inicial_2">Serie NC</label>
						<input type="text" class="form-control" id="serie_inicial_2" name="camposf[2][serie]" value="<?php echo $rowf['serie'];?>">
					</div>
					<div class="form-group col-sm-3"<?php if($_POST['cveusuario']!=1){ ?> style="display: none;"<?php } ?>>
						<label for="cveclientefactura">ID Cliente HoyFactura</label>
						<input type="text" class="form-control" id="cveclientefactura" name="camposp[cveclientefactura]" value="<?php echo $row['cveclientefactura'];?>">
					</div>
				</div>
			</div>
		</div>
    </div>
    <div class="col-xl-8 col-lg-8 col-md-8"<?php if($_POST['cveusuario']!=1){ ?> style="display: none;"<?php } ?>>
    	<div class="card shadow">
			<div class="card-header">
				<h6 class="m-0 font-weight-bold text-secondary">Par&aacute;metros Operativos</h6>
			</div>
			<div class="card-body">
			    <div class="form-row">
			        <div class="form-group col-sm-6">
						<label for="bloquear_impresion">Bloquear Impresion</label><br>
						<input type="checkbox" id="bloquear_impresion" class="form-control" onClick="cambiar_check('bloquear_impresion')" onChange="cambiar_check('bloquear_impresion')"<?php if($row['bloquear_impresion']==1){ ?> checked<?php } ?>>
						<input type="hidden" class="form-control" id="bloquear_impresion_h" name="camposp[bloquear_impresion]" value="<?php echo $row['bloquear_impresion'];?>">
					</div>
					<div class="form-group col-sm-6">
						<label for="lista">Lista</label>
						<input type="text" class="form-control" id="lista" name="camposp[lista]" value="<?php echo $row['lista'];?>">
					</div>
			        
				</div>
				<div class="form-row">
			        <div class="form-group col-sm-6">
						<label for="genera_devolucion">Genera Devoluci&oacute;n</label><br>
						<input type="checkbox" id="genera_devolucion" class="form-control" onClick="cambiar_check('genera_devolucion')" onChange="cambiar_check('genera_devolucion')"<?php if($row['genera_devolucion']==1){ ?> checked<?php } ?>>
						<input type="hidden" class="form-control" id="genera_devolucion_h" name="camposp[genera_devolucion]" value="<?php echo $row['genera_devolucion'];?>">
					</div>
					<div class="form-group col-sm-6">
						<label for="pagos_para_cortesia">N&uacute;mero Pagos Acumulados Para Cortesia</label>
						<input type="text" class="form-control" id="pagos_para_cortesia" name="camposp[pagos_para_cortesia]" value="<?php echo $row['pagos_para_cortesia'];?>">
					</div>
			        
				</div>
				<div class="form-row">
					<div class="form-group col-sm-6">
						<label for="intentoporcertificadodif">Permitir intento por certificado diferente</label><br>
						<input type="checkbox" id="intentoporcertificadodif" class="form-control" onClick="cambiar_check('intentoporcertificadodif')" onChange="cambiar_check('intentoporcertificadodif')"<?php if($row['intentoporcertificadodif']==1){ ?> checked<?php } ?>>
						<input type="hidden" class="form-control" id="intentoporcertificadodif_h" name="camposp[intentoporcertificadodif]" value="<?php echo $row['intentoporcertificadodif'];?>">
					</div>
			        <div class="form-group col-sm-6">
						<label for="orden_reporte">Orden en Reportes</label>
						<input type="text" class="form-control" id="orden_reporte" name="camposp[orden_reporte]" value="<?php echo $row['orden_reporte'];?>">
					</div>
				</div>
				<div class="form-row">
					<div class="form-group col-sm-6">
						<label for="num_depositante_acumulado_automatico">N&uacute;mero Depositante Acumulado Autom&aacute;tico</label><br>
						<input type="checkbox" id="num_depositante_acumulado_automatico" class="form-control" onClick="cambiar_check('num_depositante_acumulado_automatico')" onChange="cambiar_check('num_depositante_acumulado_automatico')"<?php if($row['num_depositante_acumulado_automatico']==1){ ?> checked<?php } ?>>
						<input type="hidden" class="form-control" id="num_depositante_acumulado_automatico_h" name="camposp[num_depositante_acumulado_automatico]" value="<?php echo $row['num_depositante_acumulado_automatico'];?>">
					</div>
			        <div class="form-group col-sm-6">
						<label for="num_intentos">N&uacute;mero Intentos</label>
						<input type="text" class="form-control" id="num_intentos" name="camposp[num_intentos]" value="<?php echo $row['num_intentos'];?>">
					</div>
				</div>
				<div class="form-row">
			        <div class="form-group col-sm-6">
						<label for="maneja_medio_pago">Maneja Descuento 3 y 4 Intento</label><br>
						<input type="checkbox" id="maneja_medio_pago" class="form-control" onClick="cambiar_check('maneja_medio_pago')" onChange="cambiar_check('maneja_medio_pago')"<?php if($row['maneja_medio_pago']==1){ ?> checked<?php } ?>>
						<input type="hidden" class="form-control" id="maneja_medio_pago_h" name="camposp[maneja_medio_pago]" value="<?php echo $row['maneja_medio_pago'];?>">
					</div>
			        <div class="form-group col-sm-6">
						<label for="monto_medio_pago">Importe 3 y 4 Intento</label>
						<input type="text" class="form-control" id="monto_medio_pago" name="camposp[monto_medio_pago]" value="<?php echo $row['monto_medio_pago'];?>">
					</div>
				</div>
				<div class="form-row">
					<div class="form-group col-sm-6">
						<label for="validar_certificado_anterior">Validar Certificado Anterior</label><br>
						<input type="checkbox" id="validar_certificado_anterior" class="form-control" onClick="cambiar_check('validar_certificado_anterior')" onChange="cambiar_check('validar_certificado_anterior')"<?php if($row['validar_certificado_anterior']==1){ ?> checked<?php } ?>>
						<input type="hidden" class="form-control" id="validar_certificado_anterior_h" name="camposp[validar_certificado_anterior]" value="<?php echo $row['validar_certificado_anterior'];?>">
					</div>
			        <div class="form-group col-sm-6">
						<label for="num_intentosanticipados">N&uacute;mero Intentos Pago Anticipado</label>
						<input type="text" class="form-control" id="num_intentosanticipados" name="camposp[num_intentosanticipados]" value="<?php echo $row['num_intentosanticipados'];?>">
					</div>
			        
				</div>
				<div class="form-row">
			        <div class="form-group col-sm-6">
						<label for="pagos_cortesia_acumulado">N&uacute;mero Pagos Para Cortesia Acumulado</label>
						<input type="text" class="form-control" id="pagos_cortesia_acumulado" name="camposp[pagos_cortesia_acumulado]" value="<?php echo $row['pagos_cortesia_acumulado'];?>">
					</div>
			        
				</div>
				
				<div class="form-row">
					<div class="form-group col-sm-6">
						<label for="constancia_rechazo">Constancia Rechazo</label><br>
						<input type="checkbox" id="constancia_rechazo" class="form-control" onClick="cambiar_check('constancia_rechazo')" onChange="cambiar_check('constancia_rechazo')"<?php if($row['constancia_rechazo']==1){ ?> checked<?php } ?>>
						<input type="hidden" class="form-control" id="constancia_rechazo_h" name="camposp[constancia_rechazo]" value="<?php echo $row['constancia_rechazo'];?>">
					</div>
			        
			        
				</div>
				<div class="form-row">
			        
				</div>
			</div>
		</div>
    </div>
    <div class="col-xl-4 col-lg-4 col-md-4"<?php if($_POST['cveusuario']!=1){ ?> style="display: none;"<?php } ?>>
    	<div class="card shadow">
			<div class="card-header">
				<h6 class="m-0 font-weight-bold text-secondary">Par&aacute;metros Timbrado</h6>
			</div>
			<div class="card-body">
				<div class="form-row">
			        <div class="form-group col-sm-12">
						<label for="bloqueada_sat">Bloqueada SAT</label><br>
						<input type="checkbox" id="bloqueada_sat" class="form-control" onClick="cambiar_check('bloqueada_sat')" onChange="cambiar_check('bloqueada_sat')"<?php if($row['bloqueada_sat']==1){ ?> checked<?php } ?>>
						<input type="hidden" class="form-control" id="bloqueada_sat_h" name="camposp[bloqueada_sat]" value="<?php echo $row['bloqueada_sat'];?>">
					</div>
				</div>
				<div class="form-row">
			        <div class="form-group col-sm-12">
						<label for="validar_timbres">Validar Timbres</label><br>
						<input type="checkbox" id="validar_timbres" class="form-control" onClick="cambiar_check('validar_timbres')" onChange="cambiar_check('validar_timbres')"<?php if($row['validar_timbres']==1){ ?> checked<?php } ?>>
						<input type="hidden" class="form-control" id="validar_timbres_h" name="camposp[validar_timbres]" value="<?php echo $row['validar_timbres'];?>">
					</div>
				</div>
				<div class="form-row">
			        <div class="form-group col-sm-12">
						<label for="timbres_exis">Ver en Listado Timbres</label><br>
						<input type="checkbox" id="timbres_exis" class="form-control" onClick="cambiar_check('timbres_exis')" onChange="cambiar_check('timbres_exis')"<?php if($row['timbres_exis']==1){ ?> checked<?php } ?>>
						<input type="hidden" class="form-control" id="timbres_exis_h" name="camposp[timbres_exis]" value="<?php echo $row['timbres_exis'];?>">
					</div>
				</div>
				<div class="form-row">
			        <div class="form-group col-sm-12">
						<label for="genera_factura_mostrador">Genera Factura Mostrador</label><br>
						<input type="checkbox" id="genera_factura_mostrador" class="form-control" onClick="cambiar_check('genera_factura_mostrador')" onChange="cambiar_check('genera_factura_mostrador')"<?php if($row['genera_factura_mostrador']==1){ ?> checked<?php } ?>>
						<input type="hidden" class="form-control" id="genera_factura_mostrador_h" name="camposp[genera_factura_mostrador]" value="<?php echo $row['genera_factura_mostrador'];?>">
					</div>
				</div>
			</div>
		</div>
    </div>
</div>

<script>
	$('input[type=checkbox]').bootstrapToggle()
</script>

<?php
}

if($_POST['cmd']==2){
	$resultado = array('error' => 0, 'mensaje' => '');
	if(trim($_POST['nombre'])==''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar el nombre');
	}
	elseif(trim($_POST['camposd']['rfc']) == ''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar el rfc');
	}
	if($resultado['error']==1){
		echo json_encode($resultado);
	}
	else{
		
		$camposdatosempresas = "";
		foreach($_POST['camposd'] as $campo => $valor) {
			$valor = addslashes($valor);
			$camposdatosempresas .= ",{$campo}='{$valor}'";
		}
		$camposplaza = "";
		foreach($_POST['camposp'] as $campo => $valor) {
			$valor = addslashes($valor);
			$camposplaza .= ",{$campo}='{$valor}'";
		}
		if($_POST['reg']>0){
			mysql_query("UPDATE plazas SET nombre='".addslashes($_POST['nombre'])."'{$camposplaza} WHERE cve='{$_POST['reg']}'");
			$mensaje = 'Se actualizo exitosamente';
			$id = $_POST['reg'];
		}
		else{
			mysql_query("INSERT plazas SET nombre='".addslashes($_POST['nombre'])."'{$camposplaza}");
			$mensaje = 'Se registro exitosamente';
			$id = mysql_insert_id();
		}

		$resd = mysql_query("SELECT cve FROM datosempresas WHERE plaza='{$id}'");
		if($rowd = mysql_fetch_assoc($resd)){
			mysql_query("UPDATE datosempresas SET nombre='".addslashes($_POST['nombre'])."'{$camposdatosempresas} WHERE cve='{$rowd['cve']}'");
		}
		else{
			mysql_query("INSERT datosempresas SET plaza='{$id}', nombre='".addslashes($_POST['nombre'])."'{$camposdatosempresas}");	
		}

		foreach($_POST['camposf'] as $tipodocumento => $datos){
			$resf = mysql_query("SELECT cve FROM foliosiniciales WHERE plaza='{$id}' AND tipo=0 AND tipodocumento='{$tipodocumento}'");
			if($rowf=mysql_fetch_assoc($resf)){
				mysql_query("UPDATE foliosiniciales SET serie = '{$datos['serie']}', folio_inicial='{$_POST['folio_inicial']}' WHERE cve='{$rowf['cve']}'");
			}
			else{
				mysql_query("INSERT foliosiniciales SET plaza='{$id}', tipodocumento='{$tipodocumento}', serie = '{$datos['serie']}', folio_inicial='{$_POST['folio_inicial']}'");	
			}
		}


		if($_POST['borrar_foto']=="1"){
			unlink("logos/logo".$id.".jpg");
		}
		if(is_uploaded_file ($_FILES['foto']['tmp_name'])){
			$arch = $_FILES['foto']['tmp_name'];
			copy($arch,"logos/logo".$id.".jpg");
			chmod("logos/logo".$id.".jpg", 0777);
		}
	

		echo '<script>sweetAlert("Existoso","'.$mensaje.'", "success");$("#contenedorprincipal").html("");atcr("plazas.php","",0,"");</script>';
	}
	
}
?>