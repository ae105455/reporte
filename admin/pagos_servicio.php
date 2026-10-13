<?php
session_start();
if(!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'aureamed');
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$nombre_usuario = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Usuario';
$rol_usuario = isset($_SESSION['rol']) ? ucfirst($_SESSION['rol']) : 'Personal';

// Crear tabla de pagos si no existe
$createPagosTable = "CREATE TABLE IF NOT EXISTS pagos (
    id_pago INT AUTO_INCREMENT PRIMARY KEY,
    id_reserva INT NOT NULL,
    id_usuario INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    fecha_pago DATE NOT NULL,
    metodo VARCHAR(100) NOT NULL,
    nota TEXT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$conn->query($createPagosTable);

$columnCheck = $conn->query("SHOW COLUMNS FROM pagos LIKE 'nota'");
if($columnCheck && $columnCheck->num_rows === 0) {
    $conn->query("ALTER TABLE pagos ADD nota TEXT NULL AFTER metodo");
}

$payment_saved = false;
$payment_id = 0;
$payment_data = null;
$id_reserva = 0;

// Registrar pago
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_pago'])) {
    $id_reserva = isset($_POST['id_reserva']) ? (int)$_POST['id_reserva'] : 0;
    $fecha_pago = isset($_POST['fecha_pago']) ? $conn->real_escape_string($_POST['fecha_pago']) : date('Y-m-d');
    $monto_pago = isset($_POST['monto_pago']) ? (float)$_POST['monto_pago'] : 0.00;
    $metodo_pago = isset($_POST['metodo_pago']) ? $conn->real_escape_string($_POST['metodo_pago']) : 'Efectivo';
    $nota_pago = isset($_POST['nota_pago']) ? $conn->real_escape_string($_POST['nota_pago']) : '';
    $id_usuario = (int)$_SESSION['usuario_id'];

    if($id_reserva > 0 && $id_usuario > 0) {
        $insert = $conn->prepare("INSERT INTO pagos (id_reserva, id_usuario, monto, fecha_pago, metodo, nota) VALUES (?, ?, ?, ?, ?, ?)");
        $insert->bind_param('iddsss', $id_reserva, $id_usuario, $monto_pago, $fecha_pago, $metodo_pago, $nota_pago);
        if($insert->execute()) {
            $payment_id = $insert->insert_id;
            $conn->query("UPDATE reservas SET estado = 'completada' WHERE id_reserva = $id_reserva");
            $insert->close();
            header('Location: pagos_servicio.php?id_reserva=' . $id_reserva . '&last_payment=' . $payment_id);
            exit();
        }
        $insert->close();
    }
}

if(!$id_reserva) {
    $id_reserva = isset($_POST['id_reserva']) ? (int)$_POST['id_reserva'] : 0;
}
if(!$id_reserva) {
    $id_reserva = isset($_GET['id_reserva']) ? (int)$_GET['id_reserva'] : 0;
}
if(!$id_reserva) {
    $id_reserva = isset($_GET['id']) ? (int)$_GET['id'] : 0;
}

if($id_reserva <= 0) {
    header("Location: reservas.php");
    exit();
}

$sql = "SELECT r.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido, p.cedula, p.telefono, p.email, s.nombre_servicio, s.precio 
        FROM reservas r 
        JOIN pacientes p ON r.id_paciente = p.id_paciente 
        JOIN servicios s ON r.id_servicio = s.id_servicio 
        WHERE r.id_reserva = $id_reserva";
$result = $conn->query($sql);
if(!$result || $result->num_rows == 0) {
    header("Location: reservas.php");
    exit();
}
$reserva = $result->fetch_assoc();

if(!$payment_id && isset($_GET['last_payment'])) {
    $payment_id = (int)$_GET['last_payment'];
}

if($payment_id > 0) {
    $receiptResult = $conn->query("SELECT p.*, r.id_reserva, r.fecha_reserva, r.hora_reserva, s.nombre_servicio, s.precio, x.nombre as paciente_nombre, x.apellido as paciente_apellido, x.cedula, x.telefono, x.email 
        FROM pagos p 
        JOIN reservas r ON p.id_reserva = r.id_reserva 
        JOIN servicios s ON r.id_servicio = s.id_servicio 
        JOIN pacientes x ON r.id_paciente = x.id_paciente 
        WHERE p.id_pago = $payment_id");
    if($receiptResult && $receiptResult->num_rows > 0) {
        $payment_data = $receiptResult->fetch_assoc();
        $payment_saved = true;
    }
}

$payment_history = [];
$historyResult = $conn->query("SELECT p.*, u.nombre as usuario_nombre FROM pagos p JOIN usuarios u ON p.id_usuario = u.id_usuario WHERE p.id_reserva = $id_reserva ORDER BY p.fecha_pago DESC");
if($historyResult) {
    while($row = $historyResult->fetch_assoc()) {
        $payment_history[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AureaMed | Pago y Comprobante #<?php echo $id_reserva; ?></title>

    <link rel="stylesheet" href="./css/normalize.css">
    <link rel="stylesheet" href="./css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/all.css">
    <link rel="stylesheet" href="./css/sweetalert2.min.css">
    <link rel="stylesheet" href="./css/style.css">
    <style>
        .receipt-box {
            background: #ffffff;
            border: 2px dashed #cbd5e1;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        .receipt-header {
            border-bottom: 2px solid #c84028;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }
        @media print {
            .nav-lateral, .navbar-info, .page-header, .page-nav-tabs, .form-pago-col, .btn-print-hide {
                display: none !important;
            }
            .page-content {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }
            .receipt-box {
                border: 1px solid #000 !important;
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body>
    
    <main class="full-box main-container">
        <!-- Sidebar -->
        <section class="nav-lateral">
            <div class="nav-lateral-bg show-nav-lateral"></div>
            <div class="nav-lateral-content">
                <figure class="nav-lateral-avatar">
                    <i class="far fa-times-circle show-nav-lateral"></i>
                    <img src="./assets/avatar/Avatar.png" alt="Avatar">
                    <figcaption>
                        <?php echo htmlspecialchars($nombre_usuario); ?><br>
                        <small><?php echo htmlspecialchars($rol_usuario); ?> &bull; AureaMed</small>
                    </figcaption>
                </figure>
                <div class="nav-lateral-bar"></div>
                <nav class="nav-lateral-menu">
                    <ul>
                        <li><a href="panel.php"><i class="fas fa-th-large fa-fw"></i> &nbsp; Dashboard</a></li>
                        <li>
                            <a href="#" class="nav-btn-submenu"><i class="fas fa-users fa-fw"></i> &nbsp; Pacientes <i class="fas fa-chevron-down"></i></a>
                            <ul>
                                <li><a href="paciente_nuevo.php"><i class="fas fa-user-plus fa-fw"></i> &nbsp; Registrar Paciente</a></li>
                                <li><a href="pacientes.php"><i class="fas fa-clipboard-list fa-fw"></i> &nbsp; Lista de Pacientes</a></li>
                                <li><a href="paciente_buscar.php"><i class="fas fa-search fa-fw"></i> &nbsp; Buscar Paciente</a></li>
                            </ul>
                        </li>
                        <li>
                            <a href="#" class="nav-btn-submenu"><i class="fas fa-stethoscope fa-fw"></i> &nbsp; Servicios <i class="fas fa-chevron-down"></i></a>
                            <ul>
                                <li><a href="servicio_nuevo.php"><i class="fas fa-plus-circle fa-fw"></i> &nbsp; Registrar Servicio</a></li>
                                <li><a href="servicios.php"><i class="fas fa-clipboard-list fa-fw"></i> &nbsp; Lista de Servicios</a></li>
                                <li><a href="servicio_buscar.php"><i class="fas fa-search fa-fw"></i> &nbsp; Buscar Servicio</a></li>
                            </ul>
                        </li>
                        <li>
                            <a href="#" class="nav-btn-submenu active"><i class="fas fa-calendar-alt fa-fw"></i> &nbsp; Reservas <i class="fas fa-chevron-down"></i></a>
                            <ul class="show-nav-lateral-submenu">
                                <li><a href="reserva_nueva.php"><i class="fas fa-calendar-plus fa-fw"></i> &nbsp; Nueva Reserva</a></li>
                                <li><a href="reservas.php"><i class="fas fa-calendar-check fa-fw"></i> &nbsp; Lista de Reservas</a></li>
                                <li><a href="reserva_pendientes.php"><i class="fas fa-clock fa-fw"></i> &nbsp; Reservas Pendientes</a></li>
                                <li><a href="reserva_buscar.php"><i class="fas fa-search fa-fw"></i> &nbsp; Buscar Reserva</a></li>
                            </ul>
                        </li>
                        <li>
                            <a href="#" class="nav-btn-submenu"><i class="fas fa-file-invoice-dollar fa-fw"></i> &nbsp; Facturación <i class="fas fa-chevron-down"></i></a>
                            <ul>
                                <li><a href="facturas.php"><i class="fas fa-list-alt fa-fw"></i> &nbsp; Lista de Facturas</a></li>
                                <li><a href="reservas.php"><i class="fas fa-dollar-sign fa-fw"></i> &nbsp; Registrar Pago</a></li>
                            </ul>
                        </li>
                        <li><a href="mensajes.php"><i class="fas fa-envelope-open-text fa-fw"></i> &nbsp; Citas Web / Mensajes</a></li>
                        <li>
                            <a href="#" class="nav-btn-submenu"><i class="fas fa-chart-bar fa-fw"></i> &nbsp; Reportes <i class="fas fa-chevron-down"></i></a>
                            <ul>
                                <li><a href="reporte_citas.php"><i class="fas fa-calendar-alt fa-fw"></i> &nbsp; Reporte de Citas</a></li>
                                <li><a href="reporte_pacientes.php"><i class="fas fa-user-check fa-fw"></i> &nbsp; Pacientes Atendidos</a></li>
                                <li><a href="reporte_servicios.php"><i class="fas fa-chart-pie fa-fw"></i> &nbsp; Servicios Solicitados</a></li>
                            </ul>
                        </li>
                        <?php if(isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                        <li>
                            <a href="#" class="nav-btn-submenu"><i class="fas fa-user-shield fa-fw"></i> &nbsp; Usuarios <i class="fas fa-chevron-down"></i></a>
                            <ul>
                                <li><a href="usuario_nuevo.php"><i class="fas fa-user-plus fa-fw"></i> &nbsp; Nuevo Usuario</a></li>
                                <li><a href="usuarios.php"><i class="fas fa-users-cog fa-fw"></i> &nbsp; Lista de Usuarios</a></li>
                                <li><a href="usuario_buscar.php"><i class="fas fa-search fa-fw"></i> &nbsp; Buscar Usuario</a></li>
                            </ul>
                        </li>
                        <?php endif; ?>
                        <li><a href="empresa.php"><i class="fas fa-hospital-alt fa-fw"></i> &nbsp; Centro Auditivo</a></li>
                    </ul>
                </nav>
            </div>
        </section>

        <!-- Contenido -->
        <section class="page-content">
            <nav class="navbar-info">
                <div class="navbar-info-left">
                    <button type="button" class="show-nav-lateral btn-icon" title="Ocultar/Mostrar Menú">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h4 class="navbar-brand-title d-none d-sm-flex">
                        <i class="fas fa-dollar-sign text-primary"></i> Cobro de Consulta / Tratamiento
                    </h4>
                </div>
                <div class="navbar-info-right">
                    <a href="perfil.php" title="Mi Perfil"><i class="fas fa-user-circle"></i> <span class="d-none d-md-inline"><?php echo htmlspecialchars($nombre_usuario); ?></span></a>
                    <a href="logout.php" class="btn-exit-system" title="Cerrar Sesión"><i class="fas fa-power-off"></i> <span class="d-none d-md-inline">Salir</span></a>
                </div>
            </nav>

            <div class="page-header">
                <h3><i class="fas fa-file-invoice-dollar"></i> PAGO Y COMPROBANTE DE ATENCIÓN</h3>
                <p>Emita el recibo oficial de pago para la cita médica #<?php echo $id_reserva; ?>.</p>
            </div>

            <div class="container-fluid mb-3 btn-print-hide">
                <a href="reservas.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Volver a Reservas</a>
            </div>

            <?php if($payment_saved): ?>
                <div class="container-fluid btn-print-hide">
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>✅ ¡Pago Registrado Exitosamente!</strong> Se ha generado el comprobante de atención médica.
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                </div>
            <?php endif; ?>

            <div class="container-fluid">
                <div class="row">
                    <!-- Formulario de Registro de Pago -->
                    <div class="col-lg-5 mb-4 form-pago-col">
                        <form method="POST" action="pagos_servicio.php" class="form-neon">
                            <fieldset>
                                <legend><i class="fas fa-cash-register"></i> Registrar Cobro</legend>
                                <input type="hidden" name="id_reserva" value="<?php echo $id_reserva; ?>">
                                <input type="hidden" name="guardar_pago" value="1">

                                <div class="form-group">
                                    <label>Paciente:</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($reserva['paciente_nombre'] . ' ' . $reserva['paciente_apellido']); ?>" readonly style="background-color: #f1f5f9;">
                                </div>

                                <div class="form-group">
                                    <label>Servicio Realizado:</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($reserva['nombre_servicio']); ?>" readonly style="background-color: #f1f5f9;">
                                </div>

                                <div class="form-group">
                                    <label for="monto_pago">Monto a Cobrar (Bs.) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.50" min="0" class="form-control font-weight-bold" id="monto_pago" name="monto_pago" value="<?php echo htmlspecialchars($reserva['precio']); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="fecha_pago">Fecha de Cobro <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="fecha_pago" name="fecha_pago" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="metodo_pago">Método de Pago <span class="text-danger">*</span></label>
                                    <select class="form-control" id="metodo_pago" name="metodo_pago" required>
                                        <option value="Efectivo">Efectivo</option>
                                        <option value="Transferencia / QR">Transferencia Bancaria / QR</option>
                                        <option value="Tarjeta de Débito/Crédito">Tarjeta de Débito / Crédito</option>
                                        <option value="Seguro Médico">Convenio / Seguro Médico</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="nota_pago">Notas del Comprobante</label>
                                    <textarea class="form-control" id="nota_pago" name="nota_pago" rows="2" placeholder="Número de transferencia, referencia o detalle..."></textarea>
                                </div>

                                <div class="form-group text-center mt-4 mb-0">
                                    <button type="submit" class="btn btn-success btn-block btn-lg">
                                        <i class="fas fa-check-circle"></i> GUARDAR PAGO Y COMPLETAR CITA
                                    </button>
                                </div>
                            </fieldset>
                        </form>
                    </div>

                    <!-- Vista Previa de Comprobante / Recibo -->
                    <div class="col-lg-7 mb-4">
                        <div class="receipt-box">
                            <div class="receipt-header d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-1" style="color: #c84028; font-weight: 800;">AUREAMED</h4>
                                    <p class="text-muted small mb-0">Centro Auditivo y Fonoaudiología Especializada</p>
                                </div>
                                <div class="text-right">
                                    <span class="badge badge-primary" style="font-size: 13px;">COMPROBANTE #<?php echo $id_reserva; ?></span>
                                    <p class="text-muted small mb-0 mt-1"><?php echo date('d/m/Y'); ?></p>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-6">
                                    <small class="text-muted text-uppercase d-block font-weight-bold">Paciente:</small>
                                    <strong><?php echo htmlspecialchars($reserva['paciente_nombre'] . ' ' . $reserva['paciente_apellido']); ?></strong>
                                    <small class="d-block text-muted">CI: <?php echo htmlspecialchars($reserva['cedula']); ?></small>
                                </div>
                                <div class="col-6 text-right">
                                    <small class="text-muted text-uppercase d-block font-weight-bold">Fecha de Atención:</small>
                                    <strong><?php echo date('d/m/Y', strtotime($reserva['fecha_reserva'])); ?> - <?php echo substr($reserva['hora_reserva'], 0, 5); ?></strong>
                                    <small class="d-block text-muted">Tel: <?php echo htmlspecialchars($reserva['telefono']) ?: 'S/N'; ?></small>
                                </div>
                            </div>

                            <table class="table table-bordered mb-3">
                                <thead style="background: #f8fafc;">
                                    <tr>
                                        <th>Descripción del Servicio</th>
                                        <th class="text-right" style="width: 130px;">Total (Bs.)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($reserva['nombre_servicio']); ?></strong>
                                            <?php if(!empty($reserva['observaciones'])): ?>
                                                <small class="d-block text-muted"><?php echo htmlspecialchars($reserva['observaciones']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-right"><strong>Bs. <?php echo number_format($reserva['precio'], 2); ?></strong></td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th class="text-right">TOTAL PAGADO:</th>
                                        <th class="text-right text-success" style="font-size: 16px;">Bs. <?php echo number_format($reserva['precio'], 2); ?></th>
                                    </tr>
                                </tfoot>
                            </table>

                            <?php if(!empty($payment_history)): ?>
                                <div class="mb-3">
                                    <h6 class="font-weight-bold text-muted small text-uppercase mb-2"><i class="fas fa-history"></i> Historial de Transacciones:</h6>
                                    <ul class="list-group list-group-flush small">
                                        <?php foreach($payment_history as $hist): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-1">
                                                <span><i class="fas fa-check text-success mr-1"></i> <?php echo date('d/m/Y', strtotime($hist['fecha_pago'])); ?> &bull; <?php echo htmlspecialchars($hist['metodo']); ?> (Cobrado por: <?php echo htmlspecialchars($hist['usuario_nombre']); ?>)</span>
                                                <strong>Bs. <?php echo number_format($hist['monto'], 2); ?></strong>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <div class="receipt-footer text-center pt-3 border-top btn-print-hide">
                                <button type="button" class="btn btn-primary btn-lg mr-2" onclick="window.print();">
                                    <i class="fas fa-print"></i> Imprimir Comprobante
                                </button>
                                <a href="reservas.php" class="btn btn-secondary btn-lg">
                                    <i class="fas fa-check"></i> Finalizar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    
    <script src="./js/jquery-3.4.1.min.js"></script>
    <script src="./js/popper.min.js"></script>
    <script src="./js/bootstrap.min.js"></script>
    <script src="./js/sweetalert2.min.js"></script>
    <script src="./js/main.js"></script>
</body>
</html>
<?php $conn->close(); ?>