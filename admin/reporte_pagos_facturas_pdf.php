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

// Filtros de fecha
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01', strtotime('-11 months'));
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-t');
$filtro_metodo = isset($_GET['metodo']) ? $conn->real_escape_string($_GET['metodo']) : '';

$where = "WHERE p.fecha_pago BETWEEN '$fecha_inicio' AND '$fecha_fin'";
if($filtro_metodo) {
    $where .= " AND p.metodo = '$filtro_metodo'";
}

// Reporte mensual
$sql_mensual = "SELECT 
    DATE_FORMAT(p.fecha_pago, '%Y-%m') as mes,
    DATE_FORMAT(p.fecha_pago, '%d/%m/%Y') as fecha_formateada,
    COUNT(p.id_pago) as total_pagos,
    SUM(p.monto) as total_ingresado,
    p.metodo
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

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Financiero - Pagos de Facturas</title>
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
            height: 297mm;
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
            padding: 10px;
            border: 1px solid #ddd;
            font-size: 11px;
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
        .amount {
            text-align: right;
            font-weight: bold;
            color: #2d5016;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .summary-section {
            margin: 15px 0;
            padding: 10px;
            background-color: #f9f9f9;
            border-left: 4px solid #3d4a5c;
        }
        .summary-section p {
            margin: 4px 0;
            font-size: 10px;
            color: #333;
        }
        .summary-section strong {
            color: #2d5016;
            font-size: 11px;
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
            <p>ESTAMOS PARA TI</p>
        </div>

        <div class="report-title">Reporte Financiero</div>
        
        <div class="filters">
            <strong>Período:</strong> <?php echo date('d/m/Y', strtotime($fecha_inicio)); ?> al <?php echo date('d/m/Y', strtotime($fecha_fin)); ?>
            <?php if($filtro_metodo): ?>
            <br><strong>Método de Pago:</strong> <?php echo htmlspecialchars($filtro_metodo); ?>
            <?php endif; ?>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th class="text-center">Cantidad</th>
                    <th class="text-center">Método</th>
                    <th class="text-right">Monto</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($datos_mensuales as $dato): ?>
                <tr>
                    <td><?php echo $dato['mes']; ?></td>
                    <td class="text-center"><?php echo $dato['total_pagos']; ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($dato['metodo']); ?></td>
                    <td class="amount">Bs. <?php echo number_format($dato['total_ingresado'], 2, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="3"><strong>TOTAL INGRESOS</strong></td>
                    <td class="amount">Bs. <?php echo number_format($total_general, 2, ',', '.'); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="summary-section">
            <p><strong>Total de Pagos:</strong> <?php echo $total_pagos_general; ?></p>
            <p><strong>Total Ingresado:</strong> Bs. <?php echo number_format($total_general, 2, ',', '.'); ?></p>
            <p><strong>Promedio por Pago:</strong> Bs. <?php echo number_format($total_pagos_general > 0 ? $total_general / $total_pagos_general : 0, 2, ',', '.'); ?></p>
        </div>

        <div class="footer">
            <p>Reporte generado el <?php echo date('d/m/Y H:i:s'); ?> | AUREAMED - Sistema de Gestión</p>
        </div>
    </div>
</body>
</html>
