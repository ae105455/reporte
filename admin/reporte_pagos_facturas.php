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

// Filtros de fecha
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01', strtotime('-11 months'));
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-t');
$filtro_metodo = isset($_GET['metodo']) ? $conn->real_escape_string($_GET['metodo']) : '';

// Reporte mensual de dinero ingresado
$where = "WHERE p.fecha_pago BETWEEN '$fecha_inicio' AND '$fecha_fin'";
if($filtro_metodo) {
    $where .= " AND p.metodo = '$filtro_metodo'";
}

$sql_mensual = "SELECT 
    DATE_FORMAT(p.fecha_pago, '%Y-%m') as mes,
    DATE_FORMAT(p.fecha_pago, '%b %Y') as mes_formato,
    COUNT(p.id_pago) as total_pagos,
    SUM(p.monto) as total_ingresado,
    GROUP_CONCAT(DISTINCT p.metodo) as metodos
    FROM pagos p
    $where
    GROUP BY DATE_FORMAT(p.fecha_pago, '%Y-%m')
    ORDER BY p.fecha_pago DESC";

$resultado_mensual = $conn->query($sql_mensual);
$datos_mensuales = [];
$total_general = 0;
$total_pagos_general = 0;

if($resultado_mensual) {
    while($row = $resultado_mensual->fetch_assoc()) {
        $datos_mensuales[] = $row;
        $total_general += $row['total_ingresado'];
        $total_pagos_general += $row['total_pagos'];
    }
}

// Resumen por método de pago
$sql_metodos = "SELECT 
    p.metodo,
    COUNT(p.id_pago) as cantidad,
    SUM(p.monto) as subtotal
    FROM pagos p
    $where
    GROUP BY p.metodo
    ORDER BY subtotal DESC";

$resultado_metodos = $conn->query($sql_metodos);
$datos_metodos = [];
if($resultado_metodos) {
    while($row = $resultado_metodos->fetch_assoc()) {
        $datos_metodos[] = $row;
    }
}

// Servicios más solicitados con pagos
$sql_servicios = "SELECT 
    s.nombre_servicio,
    COUNT(p.id_pago) as cantidad_pagos,
    SUM(p.monto) as ingresos,
    s.precio as precio_unitario
    FROM pagos p
    JOIN reservas r ON p.id_reserva = r.id_reserva
    JOIN servicios s ON r.id_servicio = s.id_servicio
    $where
    GROUP BY s.id_servicio
    ORDER BY ingresos DESC";

$resultado_servicios = $conn->query($sql_servicios);
$datos_servicios = [];
if($resultado_servicios) {
    while($row = $resultado_servicios->fetch_assoc()) {
        $datos_servicios[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AureaMed | Reporte Mensual de Pagos de Facturas</title>
    <link rel="stylesheet" href="./css/normalize.css">
    <link rel="stylesheet" href="./css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/all.css">
    <link rel="stylesheet" href="./css/sweetalert2.min.css">
    <link rel="stylesheet" href="./css/style.css">
    <style>
        .table-hover tbody tr:hover {
            background-color: #f5f5f5;
        }
        .badge-success { background-color: #28a745; }
        .badge-info { background-color: #17a2b8; }
        .stat-box {
            border-left: 4px solid #28a745;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #f8f9fa;
        }
        .stat-box h3 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
        }
        .stat-box p {
            margin: 5px 0 0 0;
            font-size: 12px;
            color: #666;
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
                            <a href="#" class="nav-btn-submenu active"><i class="fas fa-chart-bar fa-fw"></i> &nbsp; Reportes <i class="fas fa-chevron-down"></i></a>
                            <ul class="show-nav-lateral-submenu">
                                <li><a href="reporte_citas.php"><i class="fas fa-calendar-alt fa-fw"></i> &nbsp; Reporte de Citas</a></li>
                                <li><a href="reporte_pacientes.php"><i class="fas fa-user-check fa-fw"></i> &nbsp; Pacientes Atendidos</a></li>
                                <li><a href="reporte_servicios.php"><i class="fas fa-chart-pie fa-fw"></i> &nbsp; Servicios Solicitados</a></li>
                                <li><a href="reporte_ventas.php"><i class="fas fa-cash-register fa-fw"></i> &nbsp; Reporte de Ventas</a></li>
                                <li><a href="reporte_pagos_facturas.php" class="active"><i class="fas fa-file-invoice-dollar fa-fw"></i> &nbsp; Pagos de Facturas</a></li>
                                <li><a href="reporte_inventario.php"><i class="fas fa-boxes fa-fw"></i> &nbsp; Inventario y Productos</a></li>
                            </ul>
                        </li>
                        <li><a href="facturas.php"><i class="fas fa-file-invoice-dollar fa-fw"></i> &nbsp; Facturas y Comprobantes</a></li>
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
                        <li><a href="logout.php"><i class="fas fa-power-off fa-fw"></i> &nbsp; Cerrar Sesión</a></li>
                    </ul>
                </nav>
            </div>
        </section>

        <!-- Contenido Principal -->
        <section class="full-box page-content">
            <nav class="full-box navbar-top text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <a href="#" class="float-left show-nav-lateral">
                    <i class="fas fa-exchange-alt"></i>
                </a>
                <form class="form-inline float-right" role="search" style="width: 100%;">
                    <input class="form-control mr-3" type="search" placeholder="Buscar" aria-label="Buscar" disabled>
                </form>
            </nav>

            <!-- Encabezado -->
            <div class="container-fluid">
                <div class="row mb-4 mt-4">
                    <div class="col-md-8">
                        <h2><i class="fas fa-file-invoice-dollar"></i> Reporte Mensual de Pagos - Dinero Ingresado</h2>
                    </div>
                    <div class="col-md-4 text-right">
                        <a href="reporte_pagos_facturas_pdf.php?fecha_inicio=<?php echo urlencode($fecha_inicio); ?>&fecha_fin=<?php echo urlencode($fecha_fin); ?>&metodo=<?php echo urlencode($filtro_metodo); ?>" class="btn btn-danger" target="_blank">
                            <i class="fas fa-file-pdf"></i> PDF
                        </a>
                        <button class="btn btn-primary" onclick="window.print()">
                            <i class="fas fa-print"></i> Imprimir
                        </button>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="form-inline">
                            <div class="form-group mr-3">
                                <label for="fecha_inicio" class="mr-2">Desde:</label>
                                <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>">
                            </div>
                            <div class="form-group mr-3">
                                <label for="fecha_fin" class="mr-2">Hasta:</label>
                                <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>">
                            </div>
                            <div class="form-group mr-3">
                                <label for="metodo" class="mr-2">Método de Pago:</label>
                                <select class="form-control" id="metodo" name="metodo">
                                    <option value="">Todos</option>
                                    <option value="Efectivo" <?php echo $filtro_metodo === 'Efectivo' ? 'selected' : ''; ?>>Efectivo</option>
                                    <option value="Tarjeta" <?php echo $filtro_metodo === 'Tarjeta' ? 'selected' : ''; ?>>Tarjeta</option>
                                    <option value="Cheque" <?php echo $filtro_metodo === 'Cheque' ? 'selected' : ''; ?>>Cheque</option>
                                    <option value="Transferencia" <?php echo $filtro_metodo === 'Transferencia' ? 'selected' : ''; ?>>Transferencia</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                            <a href="reporte_pagos_facturas.php" class="btn btn-secondary ml-2">
                                <i class="fas fa-redo"></i> Limpiar
                            </a>
                        </form>
                    </div>
                </div>

                <!-- Estadísticas Generales -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-box">
                            <h3>$<?php echo number_format($total_general, 2, ',', '.'); ?></h3>
                            <p>Total Ingresado</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-box">
                            <h3><?php echo $total_pagos_general; ?></h3>
                            <p>Total de Pagos</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-box">
                            <h3>$<?php echo $total_pagos_general > 0 ? number_format($total_general / $total_pagos_general, 2, ',', '.') : '0,00'; ?></h3>
                            <p>Promedio por Pago</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-box">
                            <h3><?php echo count($datos_mensuales); ?></h3>
                            <p>Meses Registrados</p>
                        </div>
                    </div>
                </div>

                <!-- Tabla Mensual -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-line"></i> Dinero Ingresado por Mes</h5>
                    </div>
                    <div class="card-body">
                        <?php if(count($datos_mensuales) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-success">
                                    <tr>
                                        <th>Mes</th>
                                        <th class="text-center">Cantidad de Pagos</th>
                                        <th class="text-right">Total Ingresado</th>
                                        <th class="text-center">Métodos de Pago</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($datos_mensuales as $dato): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($dato['mes_formato']); ?></strong></td>
                                        <td class="text-center">
                                            <span class="badge badge-info"><?php echo $dato['total_pagos']; ?></span>
                                        </td>
                                        <td class="text-right">
                                            <strong class="text-success">$<?php echo number_format($dato['total_ingresado'], 2, ',', '.'); ?></strong>
                                        </td>
                                        <td class="text-center">
                                            <?php echo htmlspecialchars($dato['metodos']); ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="2" class="text-right">TOTAL:</th>
                                        <th class="text-right">
                                            <strong class="text-success text-lg">$<?php echo number_format($total_general, 2, ',', '.'); ?></strong>
                                        </th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No hay datos de pagos para el período seleccionado.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Resumen por Método de Pago -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="fas fa-credit-card"></i> Resumen por Método de Pago</h5>
                            </div>
                            <div class="card-body">
                                <?php if(count($datos_metodos) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Método</th>
                                                <th class="text-center">Cantidad</th>
                                                <th class="text-right">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($datos_metodos as $metodo): ?>
                                            <tr>
                                                <td>
                                                    <?php 
                                                    $icono = 'fas fa-money-bill';
                                                    if($metodo['metodo'] === 'Tarjeta') $icono = 'fas fa-credit-card';
                                                    elseif($metodo['metodo'] === 'Cheque') $icono = 'fas fa-file-check';
                                                    elseif($metodo['metodo'] === 'Transferencia') $icono = 'fas fa-exchange-alt';
                                                    ?>
                                                    <i class="<?php echo $icono; ?>"></i> <?php echo htmlspecialchars($metodo['metodo']); ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-primary"><?php echo $metodo['cantidad']; ?></span>
                                                </td>
                                                <td class="text-right">
                                                    <strong class="text-success">$<?php echo number_format($metodo['subtotal'], 2, ',', '.'); ?></strong>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i> No hay datos disponibles.
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Servicios con Mayor Ingreso -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-warning text-white">
                                <h5 class="mb-0"><i class="fas fa-stethoscope"></i> Servicios - Top Ingresos</h5>
                            </div>
                            <div class="card-body">
                                <?php if(count($datos_servicios) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Servicio</th>
                                                <th class="text-center">Pagos</th>
                                                <th class="text-right">Ingresos</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($datos_servicios as $servicio): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($servicio['nombre_servicio']); ?></td>
                                                <td class="text-center">
                                                    <span class="badge badge-primary"><?php echo $servicio['cantidad_pagos']; ?></span>
                                                </td>
                                                <td class="text-right">
                                                    <strong class="text-success">$<?php echo number_format($servicio['ingresos'], 2, ',', '.'); ?></strong>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i> No hay datos disponibles.
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pie de página -->
                <div class="text-center text-muted small mt-5 mb-3">
                    <p>Reporte generado el <?php echo date('d/m/Y H:i:s'); ?> | AureaMed - Sistema de Gestión</p>
                </div>
            </div>
        </section>
    </main>

    <!-- Scripts -->
    <script src="./js/jquery-3.4.1.min.js"></script>
    <script src="./js/bootstrap.min.js"></script>
    <script src="./js/popper.min.js"></script>
    <script src="./js/bootstrap-material-design.min.js"></script>
    <script src="./js/sweetalert2.min.js"></script>
    <script src="./js/main.js"></script>
</body>
</html>
