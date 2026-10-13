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

$sql = "SELECT s.id_servicio, s.nombre_servicio, s.precio, s.duracion_minutos, s.estado,
        COUNT(r.id_reserva) as total_solicitudes,
        SUM(CASE WHEN r.estado IN ('confirmada', 'atendida', 'completada') THEN s.precio ELSE 0 END) as ingresos_generados
        FROM servicios s
        LEFT JOIN reservas r ON s.id_servicio = r.id_servicio
        GROUP BY s.id_servicio
        ORDER BY total_solicitudes DESC, s.nombre_servicio ASC";

$resultado = $conn->query($sql);
$servicios = [];
$total_servicios = 0;
$total_ingresos = 0;
$total_solicitudes = 0;

if($resultado) {
    while($row = $resultado->fetch_assoc()) {
        $servicios[] = $row;
        $total_servicios++;
        $total_ingresos += $row['ingresos_generados'];
        $total_solicitudes += $row['total_solicitudes'];
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Servicios - AUREAMED</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            background-color: #f5f5f5;
            padding: 20px;
        }
        @media print {
            body {
                background-color: white;
                padding: 0;
            }
            .print-button {
                display: none;
            }
        }
        .container {
            width: 210mm;
            height: auto;
            background-color: white;
            margin: 0 auto 20px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .print-button {
            margin-bottom: 20px;
            text-align: center;
        }
        .print-button button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .print-button button:hover {
            background-color: #0056b3;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px solid #3d4a5c;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 18px;
            font-weight: bold;
            color: #3d4a5c;
            margin-bottom: 3px;
        }
        .header p {
            font-size: 11px;
            color: #666;
        }
        .report-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            margin: 15px 0 15px 0;
            color: #333;
        }
        .stats-box {
            display: flex;
            justify-content: space-around;
            margin-bottom: 15px;
            gap: 10px;
        }
        .stat-item {
            flex: 1;
            padding: 10px;
            background-color: #f0f0f0;
            border-left: 4px solid #3d4a5c;
            text-align: center;
        }
        .stat-item .number {
            font-size: 18px;
            font-weight: bold;
            color: #2d5016;
        }
        .stat-item .label {
            font-size: 10px;
            color: #666;
            margin-top: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        thead {
            background-color: #3d4a5c;
            color: white;
        }
        thead th {
            padding: 10px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #3d4a5c;
            font-size: 11px;
        }
        tbody tr {
            border: 1px solid #ddd;
        }
        tbody td {
            padding: 8px 10px;
            border: 1px solid #ddd;
            font-size: 10px;
        }
        tbody tr:nth-child(odd) {
            background-color: #f9f9f9;
        }
        tbody tr:nth-child(even) {
            background-color: #ffffff;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }
        .badge-success {
            background-color: #28a745;
            color: white;
        }
        .badge-danger {
            background-color: #dc3545;
            color: white;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="print-button">
        <button onclick="window.print()">
            <i class="fas fa-print"></i> Imprimir / Guardar como PDF
        </button>
    </div>

    <div class="container">
        <div class="header">
            <h1>AUREAMED</h1>
            <p>Perfección y armonía en cada perfil</p>
        </div>

        <div class="report-title">Reporte de Servicios Solicitados</div>

        <div class="stats-box">
            <div class="stat-item">
                <div class="number"><?php echo $total_servicios; ?></div>
                <div class="label">Total de Servicios</div>
            </div>
            <div class="stat-item">
                <div class="number"><?php echo $total_solicitudes; ?></div>
                <div class="label">Total de Solicitudes</div>
            </div>
            <div class="stat-item">
                <div class="number">Bs. <?php echo number_format($total_ingresos, 2, ',', '.'); ?></div>
                <div class="label">Ingresos Generados</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>SERVICIO</th>
                    <th class="text-center">DURACIÓN</th>
                    <th class="text-right">PRECIO</th>
                    <th class="text-center">SOLICITUDES</th>
                    <th class="text-right">INGRESOS</th>
                    <th class="text-center">ESTADO</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $contador = 1;
                foreach($servicios as $servicio): 
                    $estado_class = 'badge-success';
                    if($servicio['estado'] === 'inactivo') $estado_class = 'badge-danger';
                ?>
                <tr>
                    <td><?php echo $contador; ?></td>
                    <td><strong><?php echo htmlspecialchars($servicio['nombre_servicio']); ?></strong></td>
                    <td class="text-center"><?php echo $servicio['duracion_minutos']; ?> min</td>
                    <td class="text-right">Bs. <?php echo number_format($servicio['precio'], 2, ',', '.'); ?></td>
                    <td class="text-center"><?php echo $servicio['total_solicitudes']; ?></td>
                    <td class="text-right">Bs. <?php echo number_format($servicio['ingresos_generados'], 2, ',', '.'); ?></td>
                    <td class="text-center">
                        <span class="badge <?php echo $estado_class; ?>">
                            <?php echo ucfirst($servicio['estado']); ?>
                        </span>
                    </td>
                </tr>
                <?php 
                $contador++;
                endforeach; 
                ?>
            </tbody>
        </table>

        <div class="footer">
            <p>Reporte generado el <?php echo date('d/m/Y H:i:s'); ?> | AUREAMED - Sistema de Gestión</p>
        </div>
    </div>
</body>
</html>
