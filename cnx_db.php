<?php
error_reporting(E_ERROR | E_PARSE);
//$zona_horaria = file_get_contents('https://pendientes.hgaribay.com/zonahoraria.txt');
//date_default_timezone_set ($zona_horaria);
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

/*$base='bj88';
mysql_select_db($base);
$now = new DateTime();
$mins = $now->getOffset()/60;
$sgn = ($mins < 0 ? -1 : 1);
$mins = abs($mins);
$hrs = floor($mins/60);
$mins -= $hrs*60;
$offset = sprintf('%+d:%02d', $hrs*$sgn, $mins);
mysql_query("SET time_zone='$offset'");*/
GLOBAL $mysqli;
$mysqli = new mysqli('mysql', 'bj88', 'skYYoung73', 'bj88');

function mysql_query($query){
   GLOBAL $mysqli;
   $result = $mysqli->query($query) or die($mysqli->error.'<br>'.$query);

   return $result;
}

function mysql_fetch_assoc($result)
{
   GLOBAL $mysqli;
   $row = $result->fetch_assoc();
   return $row;
}

function mysql_fetch_array($result)
{
   GLOBAL $mysqli;
   $row = $result->fetch_array();
   return $row;
}

function mysql_num_rows($result)
{
   GLOBAL $mysqli;
   $rows = $result->num_rows;
   return $rows;
}

function mysql_insert_id(){
   GLOBAL $mysqli;
   return $mysqli->insert_id;
}*/

if (!function_exists("GetSQLValueString")) {
    function GetSQLValueString($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "") 
    {
        if (PHP_VERSION < 6) {
            $theValue = get_magic_quotes_gpc() ? stripslashes($theValue) : $theValue;
        }

        $theValue = addslashes($theValue);

        switch ($theType) {
            case "text":
                $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
                break;    
            case "long":
            case "int":
                $theValue = ($theValue != "") ? intval($theValue) : "NULL";
                break;
            case "double":
                $theValue = ($theValue != "") ? doubleval($theValue) : "NULL";
                break;
            case "date":
                $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
                break;
            case "defined":
                $theValue = ($theValue != "") ? $theDefinedValue : $theNotDefinedValue;
                break;
        }
        return $theValue;
    }
}
?>
