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

$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-d', strtotime('-30 days'));
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');

$stmt = $conn->prepare("SELECT DATE(r.fecha_reserva) AS fecha, s.nombre_servicio, COUNT(r.id_reserva) AS citas, SUM(s.precio) AS ingresos
    FROM reservas r
    INNER JOIN servicios s ON s.id_servicio = r.id_servicio
    WHERE r.estado IN ('confirmada', 'atendida', 'completada')
      AND DATE(r.fecha_reserva) BETWEEN ? AND ?
    GROUP BY DATE(r.fecha_reserva), s.id_servicio
    ORDER BY DATE(r.fecha_reserva) DESC, s.nombre_servicio ASC");

$stmt->bind_param('ss', $fecha_inicio, $fecha_fin);
$stmt->execute();
$resultado = $stmt->get_result();

$totales = $conn->query("SELECT COUNT(*) AS citas, COALESCE(SUM(s.precio),0) AS ingresos
    FROM reservas r
    INNER JOIN servicios s ON s.id_servicio = r.id_servicio
    WHERE r.estado IN ('confirmada','atendida','completada')
      AND DATE(r.fecha_reserva) BETWEEN '$fecha_inicio' AND '$fecha_fin'");
$totales_row = $totales && $totales->num_rows > 0 ? $totales->fetch_assoc() : ['citas' => 0, 'ingresos' => 0];

$servicio_top = $conn->query("SELECT s.nombre_servicio, COUNT(r.id_reserva) AS cantidad
    FROM reservas r
    INNER JOIN servicios s ON s.id_servicio = r.id_servicio
    WHERE r.estado IN ('confirmada','atendida','completada')
      AND DATE(r.fecha_reserva) BETWEEN '$fecha_inicio' AND '$fecha_fin'
    GROUP BY s.id_servicio
    ORDER BY cantidad DESC, s.nombre_servicio ASC
    LIMIT 1");
$top_row = $servicio_top && $servicio_top->num_rows > 0 ? $servicio_top->fetch_assoc() : ['nombre_servicio' => 'Sin datos', 'cantidad' => 0];

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AureaMed | Reporte de Ventas</title>
    <link rel="stylesheet" href="./css/normalize.css">
    <link rel="stylesheet" href="./css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/all.css">
    <link rel="stylesheet" href="./css/sweetalert2.min.css">
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>
    <main class="full-box main-container">
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
                        <li><a href="#" class="nav-btn-submenu"><i class="fas fa-chart-bar fa-fw"></i> &nbsp; Reportes <i class="fas fa-chevron-down"></i></a>
                            <ul class="show-nav-lateral-submenu">
                                <li><a href="reporte_citas.php"><i class="fas fa-calendar-alt fa-fw"></i> &nbsp; Reporte de Citas</a></li>
                                <li><a href="reporte_pacientes.php"><i class="fas fa-user-check fa-fw"></i> &nbsp; Pacientes Atendidos</a></li>
                                <li><a href="reporte_servicios.php"><i class="fas fa-chart-pie fa-fw"></i> &nbsp; Servicios Solicitados</a></li>
                                <li><a href="reporte_ventas.php" class="active"><i class="fas fa-cash-register fa-fw"></i> &nbsp; Reporte de Ventas</a></li>
                                <li><a href="reporte_inventario.php"><i class="fas fa-boxes fa-fw"></i> &nbsp; Inventario y Productos</a></li>
                            </ul>
                        </li>
                        <?php if(isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                        <li>
                            <a href="#" class="nav-btn-submenu"><i class="fas fa-user-shield fa-fw"></i> &nbsp; Usuarios <i class="fas fa-chevron-down"></i></a>
                            <ul>
                                <li><a href="usuario_nuevo.php"><i class="fas fa-user-plus fa-fw"></i> &nbsp; Nuevo Usuario</a></li>
                                <li><a href="usuarios.php"><i class="fas fa-users-cog fa-fw"></i> &nbsp; Lista de Usuarios</a></li>
                            </ul>
                        </li>
                        <?php endif; ?>
                        <li><a href="empresa.php"><i class="fas fa-hospital-alt fa-fw"></i> &nbsp; Centro Auditivo</a></li>
                    </ul>
                </nav>
            </div>
        </section>

        <section class="page-content">
            <nav class="navbar-info">
                <div class="navbar-info-left">
                    <button type="button" class="show-nav-lateral btn-icon" title="Ocultar/Mostrar Menú">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h4 class="navbar-brand-title d-none d-sm-flex">
                        <i class="fas fa-cash-register text-primary"></i> Reporte de ventas
                    </h4>
                </div>
                <div class="navbar-info-right">
                    <a href="perfil.php" title="Mi Perfil"><i class="fas fa-user-circle"></i> <span class="d-none d-md-inline"><?php echo htmlspecialchars($nombre_usuario); ?></span></a>
                    <a href="logout.php" class="btn-exit-system" title="Cerrar Sesión"><i class="fas fa-power-off"></i> <span class="d-none d-md-inline">Salir</span></a>
                </div>
            </nav>

            <div class="page-header">
                <h3><i class="fas fa-file-invoice-dollar"></i> REPORTE DE VENTAS Y INGRESOS</h3>
                <p>Resumen financiero del periodo con detalle de servicios atendidos y facturación.</p>
            </div>

            <div class="container-fluid">
                <ul class="page-nav-tabs">
                    <li><a href="reporte_citas.php"><i class="fas fa-calendar-alt"></i> REPORTE DE CITAS</a></li>
                    <li><a href="reporte_servicios.php"><i class="fas fa-chart-pie"></i> SERVICIOS SOLICITADOS</a></li>
                    <li><a class="active" href="reporte_ventas.php"><i class="fas fa-cash-register"></i> REPORTE DE VENTAS</a></li>
                    <li><a href="reporte_inventario.php"><i class="fas fa-boxes"></i> INVENTARIO</a></li>
                </ul>
            </div>

            <div class="container-fluid mb-4">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="text-muted small">Ingresos del periodo</div>
                                <h3 class="mb-0 mt-2 text-success">Bs. <?php echo number_format((float)$totales_row['ingresos'], 2); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="text-muted small">Citas atendidas</div>
                                <h3 class="mb-0 mt-2 text-primary"><?php echo (int)$totales_row['citas']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="text-muted small">Servicio más solicitado</div>
                                <h5 class="mb-0 mt-2"><?php echo htmlspecialchars($top_row['nombre_servicio']); ?></h5>
                                <small class="text-muted"><?php echo (int)$top_row['cantidad']; ?> consultas</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="container-fluid mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <form method="GET" class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label">Fecha inicio</label>
                                <input type="date" name="fecha_inicio" class="form-control" value="<?php echo htmlspecialchars($fecha_inicio); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Fecha fin</label>
                                <input type="date" name="fecha_fin" class="form-control" value="<?php echo htmlspecialchars($fecha_fin); ?>">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter"></i> Filtrar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="container-fluid">
                <div class="table-container">
                    <div class="table-container-header">
                        <h5 class="table-container-title"><i class="fas fa-list text-primary"></i> Detalle de ventas por fecha</h5>
                        <div>
                            <a href="reporte_ventas_pdf.php?fecha_inicio=<?php echo urlencode($fecha_inicio); ?>&fecha_fin=<?php echo urlencode($fecha_fin); ?>" class="btn btn-sm btn-danger" target="_blank">
                                <i class="fas fa-file-pdf"></i> PDF
                            </a>
                            <button type="button" class="btn btn-sm btn-primary" onclick="window.print();"><i class="fas fa-print"></i> Imprimir</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>FECHA</th>
                                    <th>SERVICIO</th>
                                    <th>CITAS</th>
                                    <th>INGRESOS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($resultado && $resultado->num_rows > 0): ?>
                                    <?php while($row = $resultado->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($row['fecha'])); ?></td>
                                            <td><strong><?php echo htmlspecialchars($row['nombre_servicio']); ?></strong></td>
                                            <td><?php echo (int)$row['citas']; ?></td>
                                            <td><strong>Bs. <?php echo number_format((float)$row['ingresos'], 2); ?></strong></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">No hay ventas registradas para ese rango.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
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
