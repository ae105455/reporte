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

$sql = "SELECT s.id_servicio, s.nombre_servicio, s.precio, s.duracion_minutos,
        COUNT(r.id_reserva) AS cantidad_solicitada,
        ROUND(COUNT(r.id_reserva) * 1.2, 0) AS stock_recomendado,
        SUM(CASE WHEN r.estado IN ('confirmada', 'atendida', 'completada') THEN s.precio ELSE 0 END) AS ingresos
        FROM servicios s
        LEFT JOIN reservas r ON r.id_servicio = s.id_servicio
        GROUP BY s.id_servicio, s.nombre_servicio, s.precio, s.duracion_minutos
        ORDER BY cantidad_solicitada DESC, s.nombre_servicio ASC";

$resultado = $conn->query($sql);
$total_servicios = $conn->query("SELECT COUNT(*) AS total FROM servicios WHERE estado = 'activo' OR estado = 1");
$total_ventas = $conn->query("SELECT COUNT(*) AS total FROM reservas WHERE estado IN ('confirmada','atendida','completada')");

$total_servicios_row = $total_servicios && $total_servicios->num_rows > 0 ? $total_servicios->fetch_assoc() : ['total' => 0];
$total_ventas_row = $total_ventas && $total_ventas->num_rows > 0 ? $total_ventas->fetch_assoc() : ['total' => 0];
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AureaMed | Inventario</title>
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
                        <li>
                            <a href="#" class="nav-btn-submenu active"><i class="fas fa-chart-bar fa-fw"></i> &nbsp; Reportes <i class="fas fa-chevron-down"></i></a>
                            <ul class="show-nav-lateral-submenu">
                                <li><a href="reporte_citas.php"><i class="fas fa-calendar-alt fa-fw"></i> &nbsp; Reporte de Citas</a></li>
                                <li><a href="reporte_pacientes.php"><i class="fas fa-user-check fa-fw"></i> &nbsp; Pacientes Atendidos</a></li>
                                <li><a href="reporte_servicios.php"><i class="fas fa-chart-pie fa-fw"></i> &nbsp; Servicios Solicitados</a></li>
                                <li><a href="reporte_ventas.php"><i class="fas fa-cash-register fa-fw"></i> &nbsp; Reporte de Ventas</a></li>
                                <li><a href="reporte_inventario.php" class="active"><i class="fas fa-boxes fa-fw"></i> &nbsp; Inventario y Productos</a></li>
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
                        <i class="fas fa-boxes text-primary"></i> Inventario y productos
                    </h4>
                </div>
                <div class="navbar-info-right">
                    <a href="perfil.php" title="Mi Perfil"><i class="fas fa-user-circle"></i> <span class="d-none d-md-inline"><?php echo htmlspecialchars($nombre_usuario); ?></span></a>
                    <a href="logout.php" class="btn-exit-system" title="Cerrar Sesión"><i class="fas fa-power-off"></i> <span class="d-none d-md-inline">Salir</span></a>
                </div>
            </nav>

            <div class="page-header">
                <h3><i class="fas fa-boxes"></i> INVENTARIO DE SERVICIOS Y DEMANDA</h3>
                <p>Control de servicios más solicitados, precios y nivel de reposición recomendado.</p>
            </div>

            <div class="container-fluid">
                <ul class="page-nav-tabs">
                    <li><a href="reporte_citas.php"><i class="fas fa-calendar-alt"></i> REPORTE DE CITAS</a></li>
                    <li><a href="reporte_servicios.php"><i class="fas fa-chart-pie"></i> SERVICIOS SOLICITADOS</a></li>
                    <li><a href="reporte_ventas.php"><i class="fas fa-cash-register"></i> REPORTE DE VENTAS</a></li>
                    <li><a class="active" href="reporte_inventario.php"><i class="fas fa-boxes"></i> INVENTARIO</a></li>
                </ul>
            </div>

            <div class="container-fluid mb-4">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="text-muted small">Servicios activos</div>
                                <h3 class="mb-0 mt-2 text-primary"><?php echo (int)$total_servicios_row['total']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="text-muted small">Citas confirmadas</div>
                                <h3 class="mb-0 mt-2 text-success"><?php echo (int)$total_ventas_row['total']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="text-muted small">Nivel de inventario</div>
                                <h4 class="mb-0 mt-2 text-warning">Revisión mensual</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="container-fluid">
                <div class="table-container">
                    <div class="table-container-header">
                        <h5 class="table-container-title"><i class="fas fa-warehouse text-primary"></i> Estado de servicio / stock recomendado</h5>
                        <button type="button" class="btn btn-sm btn-primary" onclick="window.print();"><i class="fas fa-print"></i> Imprimir</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>SERVICIO</th>
                                    <th>DURACIÓN</th>
                                    <th>PRECIO</th>
                                    <th>SOLICITUDES</th>
                                    <th>STOCK RECOMENDADO</th>
                                    <th>INGRESOS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($resultado && $resultado->num_rows > 0): ?>
                                    <?php $i = 1; while($row = $resultado->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong>#<?php echo $row['id_servicio']; ?></strong></td>
                                            <td><strong><?php echo htmlspecialchars($row['nombre_servicio']); ?></strong></td>
                                            <td><?php echo (int)$row['duracion_minutos']; ?> min</td>
                                            <td>Bs. <?php echo number_format((float)$row['precio'], 2); ?></td>
                                            <td><?php echo (int)$row['cantidad_solicitada']; ?></td>
                                            <td><span class="badge badge-secondary"><?php echo (int)$row['stock_recomendado']; ?> unidades</span></td>
                                            <td><strong>Bs. <?php echo number_format((float)$row['ingresos'], 2); ?></strong></td>
                                        </tr>
                                    <?php $i++; endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">No hay datos de inventario para mostrar.</td>
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
