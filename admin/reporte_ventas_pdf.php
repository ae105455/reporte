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

$ventas = [];
$total_citas = 0;
$total_ingresos = 0;

if($resultado) {
    while($row = $resultado->fetch_assoc()) {
        $ventas[] = $row;
        $total_citas += $row['citas'];
        $total_ingresos += $row['ingresos'];
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Ventas - AUREAMED</title>
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
        .filters {
            font-size: 10px;
            margin-bottom: 15px;
            color: #666;
            line-height: 1.6;
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
        .total-row {
            background-color: #d4a76a;
            font-weight: bold;
        }
        .total-row td {
            padding: 12px 10px;
            border: 2px solid #3d4a5c;
            background-color: #d4a76a;
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
            <p></p>
        </div>

        <div class="report-title">Reporte de Ventas</div>

        <div class="filters">
            <strong>Período:</strong> <?php echo date('d/m/Y', strtotime($fecha_inicio)); ?> al <?php echo date('d/m/Y', strtotime($fecha_fin)); ?>
        </div>

        <div class="stats-box">
            <div class="stat-item">
                <div class="number"><?php echo $total_citas; ?></div>
                <div class="label">Total de Citas</div>
            </div>
            <div class="stat-item">
                <div class="number">Bs. <?php echo number_format($total_ingresos, 2, ',', '.'); ?></div>
                <div class="label">Ingresos Totales</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>FECHA</th>
                    <th>SERVICIO</th>
                    <th class="text-center">CITAS</th>
                    <th class="text-right">INGRESOS</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                foreach($ventas as $venta): 
                ?>
                <tr>
                    <td><?php echo date('d/m/Y', strtotime($venta['fecha'])); ?></td>
                    <td><?php echo htmlspecialchars($venta['nombre_servicio']); ?></td>
                    <td class="text-center"><?php echo $venta['citas']; ?></td>
                    <td class="text-right">Bs. <?php echo number_format($venta['ingresos'], 2, ',', '.'); ?></td>
                </tr>
                <?php 
                endforeach; 
                ?>
                <tr class="total-row">
                    <td colspan="2"><strong>TOTAL</strong></td>
                    <td class="text-center"><strong><?php echo $total_citas; ?></strong></td>
                    <td class="text-right"><strong>Bs. <?php echo number_format($total_ingresos, 2, ',', '.'); ?></strong></td>
                </tr>
            </tbody>
        </table>

        <div class="footer">
            <p>Reporte generado el <?php echo date('d/m/Y H:i:s'); ?> | AUREAMED- Sistema de Gestión</p>
        </div>
    </div>
</body>
</html>
