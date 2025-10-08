<?php
	set_time_limit(0);
	global $base,$PHP_SELF;
	require_once("nusoap/nusoap.php");	
	

	
	echo '<html><head><title>Sincronizar Boletos</title></head>
	<body leftmargin=0 marginwidth=0 topmargin=0 marginheight=0>';
	//Consumir el WS		
	if($_POST['cmd']>0){
		echo 'entra';
		$oSoapClient = new nusoap_client("http://morverifica.com/wsHuellas.php?wsdl", true);		
		$err = $oSoapClient->getError();
		if($err!=""){
			echo "error1:".$err;
		}
		else{
			echo 'entra2';
			//Tomar los Boletos de los ultimos 5 dias
			$parametros['usuario']='usrwebservices';
			$parametros['password']='usrw3bs3rv1c3s';
			if($_POST['cmd']==1){
				$parametros['serie'] = $_POST['texto'];
			}
			print_r($parametros);
			$respuesta = $oSoapClient->call("Ping", $parametros);
			echo $oSoapClient->getDebug();
			print_r($respuesta);
			$err = $oSoapClient->getError();
			if (!$err){

				echo 'Request:'.$oSoapClient->request.'<br>';
				echo 'Response:'.$oSoapClient->response.'<br>';
				echo $respuesta['mensaje'];
			}
			else{
				echo 'entra4'.$err.'<br>';
				echo 'Request:'.$oSoapClient->request.'<br>';
				echo 'Response:'.$oSoapClient->response.'<br>';
			}
		}
	}
	echo '<form name="forma" enctype="multipart/form-data" method="POST" action="probarhuellas.php">';
	echo 'Probar<select name="cmd">';
	echo '<option value="1">Ping</option>';
	echo '<input type="text" name="texto" value="">';

	echo '</select><input type="submit" value="Probar"></form></body></html>';
?>