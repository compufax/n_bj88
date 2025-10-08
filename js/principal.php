<?php 
require_once('cnx_db.php');
require_once('globales.php'); 
require_once('validarloging.php');
?>
<!DOCTYPE html>
<html lang="es">

<head>

  <meta charset="iso-8859-1">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="">
  <meta name="author" content="">

  <title><?php echo $NOMBRE; ?></title>

  <!-- Custom fonts for this template-->
  <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

  <!-- Custom styles for this template-->
  <link href="css/sb-admin-2.css?d=<?php echo date('H:i:s');?>" rel="stylesheet">
  <link href="css/ui.css?d=<?php echo date('H:i:s');?>" rel="stylesheet">
  <link href="vendor/datatables/jquery.dataTables.css?d=<?php echo date('H:i:s');?>" rel="stylesheet">
  <link href="vendor/bootstrap/bootstrap-toggle-master/css/bootstrap-toggle.css?d=<?php echo date('H:i:s');?>" rel="stylesheet">
  <link href="css/sweetalert.css?d=<?php echo date('H:i:s');?>" rel="stylesheet">
  <link href="css/estilos.css?d=<?php echo date('H:i:s');?>" rel="stylesheet">
  <link href="css/bootstrap-select.css?d=<?php echo date('H:i:s');?>" rel="stylesheet">
  <style>
    #contenedorprincipal {
      height: calc(100% - 60px);
      overflow: auto;
    }
  </style>
</head>

<body id="page-top">
  <!-- Page Wrapper -->
  <div id="wrapper">

    <!-- Sidebar -->
    <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

      <!-- Sidebar - Brand -->
      <a class="sidebar-brand d-flex align-items-center justify-content-center" href="principal.php">
        <div class="sidebar-brand-text mx-3"><?php echo $NOMBRE; ?></div>
      </a>

      <!-- Divider -->
      <hr class="sidebar-divider my-0">

      <!-- Heading -->
      <div class="sidebar-heading">
        Menu
      </div>
      <?php include('menu.php'); ?>
      <!-- Divider -->
      <hr class="sidebar-divider d-none d-md-block">

      <!-- Sidebar Toggler (Sidebar) -->
      <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
      </div>

    </ul>
    <!-- End of Sidebar -->

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">

      <!-- Main Content -->
      <div id="content">

        <!-- Topbar -->
        <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

          <!-- Sidebar Toggle (Topbar) -->
          <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
            <i class="fa fa-bars"></i>
          </button>

          <div class="input-group" id="nombrepagina">
              Inicio
            </div>

          
          <!-- Topbar Navbar -->
          <ul class="navbar-nav ml-auto">

            <!-- Nav Item - Search Dropdown (Visible Only XS) -->
            

            <!-- Nav Item - Alerts -->
            
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="companyDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="mr-2 d-none d-lg-inline text-danger small" id="nombreempresatop"></span>
                <i class="fas fa-building fa-lg fa-fw mr-2 text-gray-400"></i>
              </a>
              <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="companyDropdown">
                <?php
                  $empresa = array();
                  $asignar = false;
                  if($_SESSION['TiendaUsuario'] > 0){
                    $res = mysql_query($MySQL, "SELECT cve, nombre FROM {$basefac}.empresa WHERE cve = '{$_SESSION['TiendaUsuario']}' ORDER BY nombre");
                  }
                  else{
                    $res = mysql_query($MySQL, "SELECT cve, nombre FROM {$basefac}.empresa ORDER BY nombre"); 
                  }
                  if(mysql_num_rows($res)==1){
                    $asignar = true;
                  }
                  while($row = mysql_fetch_assoc($res)){
                    if($asignar){
                      $empresa = $row;
                    }
                ?>
                  <span class="dropdown-item text-danger" onClick="cambiarEmpresa(<?php echo $row['cve'];?>, $(this));">
                    <?php echo $row['nombre']; ?>
                  </span>
                <?php
                  }
                ?>
              </div>
            </li>
            <div class="topbar-divider d-none d-sm-block"></div>

            <!-- Nav Item - User Information -->
            <li class="nav-item dropdown no-arrow">
              <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo $_SESSION['NomUsuario'];?></span>
                <i class="fas fa-user fa-lg fa-fw mr-2 text-gray-400"></i>
              </a>
              <!-- Dropdown - User Information -->
              <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                <!--<a class="dropdown-item" href="#" onClick="menu('0', '3', 'perfil.php', 'Datos personales', '<?php echo $_SESSION['reg_sistema'];?>');">
                  <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                  Datos personales
                </a>-->
                <a class="dropdown-item" href="#" onClick="menu('0', '2', 'cambiar_password.php', 'Cambiar Contraseña', '<?php echo $_SESSION['reg_sistema'];?>');">
                  <i class="fas fa-exclamation fa-sm fa-fw mr-2 text-gray-400"></i>
                  Cambiar Contraseña
                </a>
                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                  <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                  Salir
                </a>
              </div>
            </li>

          </ul>

        </nav>
        <!-- End of Topbar -->

        <!-- Begin Page Content -->
        <form id="formaprincipal" name="formaprincipal" method="POST"" enctype="multipart/form-data">
          <div style="display:none;">
            <input type="hidden" name="cmd" value="">
            <input type="hidden" name="cveempresa" id="cveempresa" value="<?php echo $empresa['cve']; ?>">
            <input type="hidden" name="reg" value="" id="reg">
            <input type="hidden" name="cvemenu" value="" id="cvemenu">
            <input type="hidden" name="cveusuario" id="cveusuario" value="<?php echo $_SESSION['CveUsuario'];?>">
            <input type="hidden" name="tipousuario" value="<?php echo $_SESSION['TipoUsuario'];?>">
          </div>

          <div class="container-fluid" id="contenedorprincipal">

          
          </div>
        <!-- /.container-fluid -->
        </form>
      </div>
      <!-- End of Main Content -->

    </div>
    <!-- End of Content Wrapper -->

  </div>
  <!-- End of Page Wrapper -->

  <!-- Scroll to Top Button-->
  <a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
  </a>

  <!-- Logout Modal-->
  <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">¿Seguro que desea salir?</h5>
          <button class="close" type="button" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">×</span>
          </button>
        </div>
        <div class="modal-body">Seleccione "Cerrar Sesión" si esta listo para cerrar la sesión.</div>
        <div class="modal-footer">
          <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancelar</button>
          <a class="btn btn-primary" href="logout.php?cveregistro=<?php echo $_SESSION['reg_sistema'];?>">Cerrar Sesión</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap core JavaScript-->
  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.bundle.js"></script>
  <script src="js/jquery-ui.min.js"></script>

  <!-- Core plugin JavaScript-->
  <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

  <!-- Custom scripts for all pages-->
  <script src="js/sb-admin-2.min.js"></script>

  <!-- Page level plugins -->
  <script src="vendor/datatables/jquery.dataTables.js?d=<?php echo date('H:i:s');?>"></script>
  <script src="vendor/datatables/dataTables.bootstrap4.js"></script>
  <script src="vendor/bootstrap/bootstrap-toggle-master/js/bootstrap-toggle.js?d=<?php echo date('H:i:s');?>"></script>
  <script src="js/funciones.js?d=<?php echo date('H:i:s');?>"></script>
  <script src="js/sweetalert.js?d=<?php echo date('H:i:s');?>"></script>
  <script src="js/bootstrap-select.js"></script>
  <script>
    <?php
      if($empresa['cve']>0){
    ?>
    $('#nombreempresatop').html('<?php echo $empresa['nombre'];?>');
    <?php
      }
    ?>
    //$("#contenedorprincipal").css('height', '450px');
    //$("#contenedorprincipal").css('overflow-y', 'scroll');

  </script>

</body>

</html>
