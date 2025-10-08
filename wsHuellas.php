<?php
require_once("cnx_db.php");
require_once("nusoap/nusoap.php");
set_time_limit(0);
$base='enero_aaz';
$namespace = "http://https://puebla.2ai.io//huellaservices";
// create a new soap server
$server = new soap_server();
// configure our WSDL
$server->configureWSDL("wshuella");
// set our namespace
$server->wsdl->schemaTargetNamespace = $namespace;
// Get our posted data if the service is being consumed
$server->wsdl->addComplexType(
    'persona',
    'complexType',
    'struct',
    'all',
    '',
    array(
		'cve'         => array('name'=>'cve',      'type'=>'xsd:integer'),
		'clave'       => array('name'=>'clave',    'type'=>'xsd:integer'),
		'apaterno'    => array('name'=>'apaterno', 'type'=>'xsd:string'),
		'amaterno'    => array('name'=>'amaterno', 'type'=>'xsd:string'),
		'nombre'      => array('name'=>'nombre',   'type'=>'xsd:string'),
		'foto'        => array('name'=>'foto',   'type'=>'xsd:string'),
		'huella'      => array('name'=>'huella',   'type'=>'xsd:string'),
		'huella2'      => array('name'=>'huella2',   'type'=>'xsd:string'),
		'huella3'      => array('name'=>'huella3',   'type'=>'xsd:string')
	)
);
$server->wsdl->addComplexType(
    'personas',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:persona[]')),
    'tns:persona'
);
$server->wsdl->addComplexType(
    'Response',
    'complexType',
    'struct',
    'all',
    '',
    array(
		'resultado'       => array('name'=>'resultado',  'type'=>'xsd:boolean'),
		'personas'        => array('name'=>'personas',   'type'=>'tns:personas'),
		'mensaje'         => array('name'=>'mensaje',    'type'=>'xsd:string')
	)
);

$server->wsdl->addComplexType(
    'motivo',
    'complexType',
    'struct',
    'all',
    '',
    array('cve'                => array('name'=>'cve',                  'type'=>'xsd:integer'),
          'nombre'              => array('name'=>'nombre',              'type'=>'xsd:string'))
    );

$server->wsdl->addComplexType(
    'motivos',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:motivo[]')),
    'tns:motivo'
);  

$server->wsdl->addComplexType(
    'listamotivos',
    'complexType',
    'struct',
    'all',
    '',
    array('resultado' => array('name'=>'resultado', 'type'=>'xsd:integer'),
    		'mensaje' => array('name'=>'mensaje', 'type'=>'xsd:string'),
          'datos'     => array('name'=>'datos',     'type'=>'tns:motivos'))
    );

$server->register(
    // nombre del metodo
    'getMotivos',          
    // lista de parametros
    array('usuario'=>'xsd:string','password'=>'xsd:string'), 
    // valores de return
    array('return'=>'tns:listamotivos'),
    // namespace
    $namespace,
    // soapaction: (use default)
    false,
    // style: rpc or document
    'rpc',
    // use: encoded or literal
    'encoded',
    // descripcion: documentacion del metodo
    'Obtener Listado de Motivos de Checada');

// registar WebMethod para Consultar los operadores
$server->register(
                // nombre del metodo
                'ConsultarPersonas',
                // lista de parametros
                array('usuario'=>'xsd:string','password'=>'xsd:string','id'=>'xsd:integer','empresa'=>'xsd:integer'), 
                // valores de return
                array('return'=>'tns:Response'),
                // namespace
                $namespace,
                // soapaction: (use default)
                false,
                // style: rpc or document
                'rpc',
                // use: encoded or literal
                'encoded',
                // descripcion: documentacion del metodo
                'Regresa los registros de las personas sin huella registrada'
			);
// registar WebMethod para Consultar los operadores
$server->register(
                // nombre del metodo
                'ConsultarHuellas',
                // lista de parametros
                array('usuario'=>'xsd:string','password'=>'xsd:string','id'=>'xsd:integer','empresa'=>'xsd:integer'), 
                // valores de return
                array('return'=>'tns:Response'),
                // namespace
                $namespace,
                // soapaction: (use default)
                false,
                // style: rpc or document
                'rpc',
                // use: encoded or literal
                'encoded',
                // descripcion: documentacion del metodo
                'Regresa el registro de la siguiente persona con huella registrada en base al id'
			);
// registar WebMethod para registrar la huella
$server->register(
                // nombre del metodo
                'RegistraHuella',
                // lista de parametros
                array('usuario'=>'xsd:string','password'=>'xsd:string','id'=>'xsd:integer','huella'=>'xsd:string','empresa'=>'xsd:integer'), 
                // valores de return
                array('return'=>'tns:Response'),
                // namespace
                $namespace,
                // soapaction: (use default)
                false,
                // style: rpc or document
                'rpc',
                // use: encoded or literal
                'encoded',
                // descripcion: documentacion del metodo
                'Registra la huella de la persona indicada en el id'
			);
$server->register(
                // nombre del metodo
                'Registra3Huellas',
                // lista de parametros
                array('usuario'=>'xsd:string','password'=>'xsd:string','id'=>'xsd:integer','huella'=>'xsd:string','huella2'=>'xsd:string','huella3'=>'xsd:string','empresa'=>'xsd:integer'), 
                // valores de return
                array('return'=>'tns:Response'),
                // namespace
                $namespace,
                // soapaction: (use default)
                false,
                // style: rpc or document
                'rpc',
                // use: encoded or literal
                'encoded',
                // descripcion: documentacion del metodo
                'Registra la huella de la persona indicada en el id'
			);
// registar WebMethod para Consultar los operadores
$server->register(
                // nombre del metodo
                'Ping',
                // lista de parametros
                array('usuario'=>'xsd:string','password'=>'xsd:string','serie'=>'xsd:string'), 
                // valores de return
                array('return'=>'tns:Response'),
                // namespace
                $namespace,
                // soapaction: (use default)
                false,
                // style: rpc or document
                'rpc',
                // use: encoded or literal
                'encoded',
                // descripcion: documentacion del metodo
                'Regresa un mensaje Pong al cliente'
			);
// registar WebMethod para Consultar los operadores
$server->register(
                // nombre del metodo
                'RegistraChecada',
                // lista de parametros
                array('usuario'=>'xsd:string','password'=>'xsd:string','lector'=>'xsd:integer','operador'=>'xsd:integer','tipo'=>'xsd:integer'), 
                // valores de return
                array('return'=>'tns:Response'),
                // namespace
                $namespace,
                // soapaction: (use default)
                false,
                // style: rpc or document
                'rpc',
                // use: encoded or literal
                'encoded',
                // descripcion: documentacion del metodo
                'Regresa un mensaje Pong al cliente'
			);

function getMotivos($usuario, $password){
	global $base;
	$strcnn=ConectarDB();
	$respuesta['resultado']=false;
	$respuesta['mensaje']='';
	$respuesta['datos']=array();
	if($strcnn!="OK")
		$respuesta['mensaje']=$strcnn;
	if($respuesta['mensaje']=='')
	{
		if($usuario!='usrwebservices')
			$respuesta['mensaje']='Usuario invalido';
	}
	if($respuesta['mensaje']=='')
	{
		if($password!='usrw3bs3rv1c3s')
			$respuesta['mensaje']='Password invalido';
	}
	if($respuesta['mensaje']=='')
	{
		$respuesta['resultado']=true;
		$res = mysql_query("SELECT * FROM motivos_checada WHERE estatus!=1 ORDER BY orden");
		while($row = mysql_fetch_array($res)){
			
			$respuesta['datos'][] = array(
					'cve'=>$row['cve'],
					'nombre'=>$row['nombre']);
			
		}
		$respuesta['datos'] = array();
	}
	return $respuesta;
}

function ConsultarPersonas($usuario, $password, $id, $empresa = 4)
{
	global $base;
	$respuesta['resultado']=false;
	$respuesta['mensaje']='';
	$strcnn=ConectarDB();
	if($strcnn!="OK")
		$respuesta['mensaje']=$strcnn;
	if($respuesta['mensaje']=='')
	{
		if($usuario!='usrwebservices')
			$respuesta['mensaje']='Usuario invalido';
	}
	if($respuesta['mensaje']=='')
	{
		if($password!='usrw3bs3rv1c3s')
			$respuesta['mensaje']='Password invalido';
	}
	if($respuesta['mensaje']=='')
	{
		//Tomar la informacion de la tabla
		$query="SELECT cve,cve as credencial,nombre,huella,huella2,huella3 FROM personal where 1 ";
		if($id>0)
			$query.=" and cve='$id'";
		else
			$query.=" and huella=''"; 
		$rs = mysql_query($query);
		$i=0;
		$aregistros=array();
		while($row=mysql_fetch_array($rs))
		{
			$aregistros[$i]['cve']        =$row['cve'];
			$aregistros[$i]['clave']      =$row['credencial'];
			$aregistros[$i]['apaterno']   =$row['apaterno'];
			$aregistros[$i]['amaterno']   =$row['amaterno'];
			$aregistros[$i]['nombre']     =$row['nombre'];
			//Verificar la Foto
			$nomfoto="imgpersonal/foto{$row['cve']}.jpg";
			if(file_exists ( $nomfoto ))
				$aregistros[$i]['foto']     =base64_encode(file_get_contents($nomfoto));
			else
				$aregistros[$i]['foto']     ="";
			$aregistros[$i]['huella']     =$row['huella'];
			$aregistros[$i]['huella2']     =$row['huella2'];
			$aregistros[$i]['huella3']     =$row['huella3'];
			$i++;
		}
		$respuesta['personas']=$aregistros;
		$respuesta['resultado']=true;
	}
	return $respuesta;
}
function ConsultarHuellas($usuario, $password, $id, $empresa = 4)
{
	global $base;
	$respuesta['resultado']=false;
	$respuesta['mensaje']='';
	$strcnn=ConectarDB();
	if($strcnn!="OK")
		$respuesta['mensaje']=$strcnn;
	if($respuesta['mensaje']=='')
	{
		if($usuario!='usrwebservices')
			$respuesta['mensaje']='Usuario invalido';
	}
	if($respuesta['mensaje']=='')
	{
		if($password!='usrw3bs3rv1c3s')
			$respuesta['mensaje']='Password invalido';
	}
	if($respuesta['mensaje']=='')
	{
		$aregistros=array();
		//Tomar la informacion de la tabla
		$query="SELECT cve,cve as credencial,nombre,huella,huella2,huella3 FROM personal where cve>'$id' and huella<>'' order by cve limit 1"; 
		$rs = mysql_query($query);
		if($row=mysql_fetch_array($rs))
		{
			$i=0;
			$aregistros[$i]['cve']        =$row['cve'];
			$aregistros[$i]['clave']      =$row['credencial'];
			$aregistros[$i]['apaterno']   =$row['apaterno'];
			$aregistros[$i]['amaterno']   =$row['amaterno'];
			$aregistros[$i]['nombre']     =$row['nombre'];
			//Verificar la Foto
			$nomfoto="imgpersonal/foto{$row['cve']}.jpg";
			if(file_exists ( $nomfoto ))
				$aregistros[$i]['foto']     =base64_encode(file_get_contents($nomfoto));
			else
				$aregistros[$i]['foto']     ="";
			$aregistros[$i]['huella']     =$row['huella'];
			$aregistros[$i]['huella2']     =$row['huella2'];
			$aregistros[$i]['huella3']     =$row['huella3'];
		}
		$respuesta['personas']=$aregistros;
		$respuesta['resultado']=true;
	}
	return $respuesta;
}
function RegistraHuella($usuario, $password, $id, $huella, $empresa = 4)
{
	global $base;
	$respuesta['resultado']=false;
	$respuesta['mensaje']='';
	$strcnn=ConectarDB();
	if($strcnn!="OK")
		$respuesta['mensaje']=$strcnn;
	if($respuesta['mensaje']=='')
	{
		if($usuario!='usrwebservices')
			$respuesta['mensaje']='Usuario invalido';
	}
	if($respuesta['mensaje']=='')
	{
		if($password!='usrw3bs3rv1c3s')
			$respuesta['mensaje']='Password invalido';
	}
	if($respuesta['mensaje']=='')
	{
		$res2 = mysql_query("SELECT validar_huella FROM usuarios WHERE cve=1");
		$row2=mysql_fetch_array($res2);
		//Tomar la informacion de la tabla
		$query="SELECT cve,cve as credencial,nombre FROM personal where cve='$id'";
		$rs = mysql_query($query);
		if($row=mysql_fetch_array($rs))
		{
			$row1= mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM checada_lector WHERE cvepersonal='$id'"));
			if($row1[0] <= 10 || $row2['validar_huella']==0)
			{
				$res2=mysql_query("SELECT cve,cve as credencial,nombre FROM personal where huella='$huella'");
				if($row2=mysql_fetch_array($res2)){
					$respuesta['mensaje']='La huella esta registrada en el empleado '.$row2['nombre'];
				}
				else{
					$query="Update personal set huella='$huella' where cve='$id'";
					mysql_query($query);
					$respuesta['resultado']=true;
				}
			}
			else
			{
				$respuesta['mensaje']='Huella bloqueada';
			}
		}
		else
			$respuesta['mensaje']='Clave de Persona no registrada';
			
		//$respuesta['mensaje']='Clave2 de Persona no registrada'.$query;
		//$respuesta['resultado']=false;
		
	}
	return $respuesta;
}

function Registra3Huellas($usuario, $password, $id, $huella, $huella2, $huella3, $empresa = 0)
{
	global $base;
	$respuesta['resultado']=false;
	$respuesta['mensaje']='';
	$strcnn=ConectarDB();
	if($strcnn!="OK")
		$respuesta['mensaje']=$strcnn;
	if($respuesta['mensaje']=='')
	{
		if($usuario!='usrwebservices')
			$respuesta['mensaje']='Usuario invalido';
	}
	if($respuesta['mensaje']=='')
	{
		if($password!='usrw3bs3rv1c3s')
			$respuesta['mensaje']='Password invalido';
	}
	if($respuesta['mensaje']=='')
	{
		$res2 = mysql_query("SELECT validar_huella FROM usuarios WHERE cve=1");
		$row2=mysql_fetch_array($res2);
		//Tomar la informacion de la tabla
		$query="SELECT cve,cve as credencial,nombre FROM personal where cve='$id'";
		$rs = mysql_query($query);
		if($row=mysql_fetch_array($rs))
		{
			$row1= mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM checada_lector WHERE cvepersonal='$id'"));
			if($row1[0] <= 10 || $row2['validar_huella']==0)
			{
				$res2=mysql_query("SELECT cve,cve as credencial,nombre FROM personal where (huella='$huella'or huella='$huella2' or huella='$huella3' or huella2='$huella' or huella2='$huella2' or huella2='$huella3' or huella3='$huella' or huella3='$huella2' or huella3='$huella3')");
				if($row2=mysql_fetch_array($res2)){
					$respuesta['mensaje']='La huella esta registrada en el empleado '.$row2['nombre'];
				}
				else{
					$query="Update personal set huella='$huella', huella2='$huella2', huella3='$huella3' where cve='$id'";
					mysql_query($query);
					$respuesta['resultado']=true;
				}
			}
			else
			{
				$respuesta['mensaje']='Huella bloqueada';
			}
		}
		else
			$respuesta['mensaje']='Clave de Persona no registrada';
			

		
	}
	return $respuesta;
}


function Ping($usuario, $password, $serie)
{
	global $base;
	$strcnn=ConectarDB();
	$respuesta['resultado']=false;
	if($strcnn!="OK")
		$respuesta['mensaje']=$strcnn;
	if($respuesta['mensaje']=='')
	{
		if($usuario!='usrwebservices')
			$respuesta['mensaje']='Usuario invalido';
	}
	if($respuesta['mensaje']=='')
	{
		if($password!='usrw3bs3rv1c3s')
			$respuesta['mensaje']='Password invalido';
	}
	if($respuesta['mensaje']=='')
	{
		$query="select cve from series where serie='$serie'";
		$rs = mysql_query($query);
		if($row=mysql_fetch_array($rs))
		{
			$respuesta['mensaje']=$row['cve'];
			$respuesta['resultado']=true;
		}
		else
			$respuesta['mensaje']="Lector no registrado";
	}
	return $respuesta;
}
function RegistraChecada($usuario, $password, $lector, $operador, $tipo = 0)
{
	global $base;
	$strcnn=ConectarDB();
	$respuesta['resultado']=false;
	if($strcnn!="OK")
		$respuesta['mensaje']=$strcnn;
	if($respuesta['mensaje']=='')
	{
		if($usuario!='usrwebservices')
			$respuesta['mensaje']='Usuario invalido';
	}
	if($respuesta['mensaje']=='')
	{
		if($password!='usrw3bs3rv1c3s')
			$respuesta['mensaje']='Password invalido';
	}
	if($respuesta['mensaje']=='')
	{
		/*if($tipo==2 && date('H:i:s')>'16:00:00'){
			$respuesta['mensae'] = 'La hora limite de salida son las 16:00 horas';
			$respuesta['resultado'] = false;
		}
		else{*/
			$fechahora=date('Y-m-d H:i:s');
			$limite=date( "Y-m-d H:i:s" , strtotime ( "-2 hour" , strtotime(date("Y-m-d H:i:s")) ) );
			$res = mysql_query("SELECT  a.fechahora FROM checada_lector a inner join motivos_checada b on a.tipo = b.cve WHERE a.cvepersonal='$operador' AND DATE(a.fechahora)='".date("Y-m-d")."' AND a.cvelector='$lector' ORDER BY fechahora DESC");
			$checadas = mysql_num_rows($res);
			if($checadas>=2){
				$respuesta['mensaje']='El empleado ya checo su salida del dia';
			}
			else{
				$row=mysql_fetch_array($res);
				if($row[0]>$limite && $checadas > 0){
					$respuesta['mensaje']='Debe de pasar al menos una hora de la ultima checada para volver a hacerlo';
				}
				/*if($checadas > 0){
					$respuesta['mensaje']='Ya checo su '.$row['motivo'];
				}*/
				else{
					if($checadas == 0) $tipo=1;
					/*elseif($checadas == 1) $tipo=4;
					elseif($checadas == 2) $tipo=3;*/
					elseif($checadas == 1) $tipo=2;
					$query="Insert into checada_lector(cvelector,cvepersonal,fechahora,tipo) values('$lector','$operador','$fechahora','$tipo')";
					mysql_query($query);
					$res1 = mysql_query("SELECT cve FROM asistencia WHERE personal='{$operador}' AND fecha = '".substr($fechahora,0,10)."'");
					if(!$row1 = mysql_fetch_assoc($res1)){
						$row1 = mysql_fetch_assoc(mysql_query("SELECT plaza FROM personal WHERE cve = '{$operador}'"));
						mysql_query("INSERT asistencia SET plaza='{$row1['plaza']}', fecha=CURDATE(), personal='{$operador}', estatus=0");
					}
					mysql_query("UPDATE asistencia SET estatus=1 WHERE personal = '$operador' AND fecha = '".substr($fechahora,0,10)."' AND estatus = 0");
					if($checadas==0)
						$respuesta['mensaje']='Usted esta entrando a trabajar '.$fechahora;
					/*elseif($checadas==1)
						$respuesta['mensaje']='Usted esta saliendo a comer '.$fechahora;
					elseif($checadas==2)
						$respuesta['mensaje']='Usted esta entrando de comer '.$fechahora;*/
					elseif($checadas==2)
						$respuesta['mensaje']='Usted esta saliendo de trabajar '.$fechahora;
					$respuesta['mensaje']=$fechahora;
					$respuesta['resultado']=true;
				}
			}
		//}
	}
	return $respuesta;
}
function ConectarDB()
{
	$msg="OK";
	if (!$MySQL=@mysql_connect('mysql', 'bj88', 'skYYoung73')) {
	   $t=time();
	   while (time()<$t+5) {}
	   if (!$MySQL=@mysql_connect('mysql', 'bj88', 'skYYoung73')) {
	      $t=time();
	      while (time()<$t+10) {}
	      if (!$MySQL=@mysql_connect('mysql', 'bj88', 'skYYoung73')) {
	      echo '<br><br><br><h3 align=center">Hay problemas de comunicaci&oacute;n con la Base de datos.</h3>';
	      echo '<h4>Por favor intente mas tarde.-</h4>';
	      exit;
	      }
	   }
	}

	$base='bj88';
	mysql_select_db($base);
	//mysql_query("SET time_zone = CST6CDT;");
	return $msg;
}
// Get our posted data if the service is being consumed
// otherwise leave this data blank.                
$POST_DATA = isset($GLOBALS['HTTP_RAW_POST_DATA']) 
? $GLOBALS['HTTP_RAW_POST_DATA'] : '';

// pass our posted data (or nothing) to the soap service                    
$server->service($POST_DATA);
?>
