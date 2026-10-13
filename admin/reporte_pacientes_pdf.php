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

$sql = "SELECT p.id_paciente, p.cedula, p.nombre, p.apellido, p.email, p.telefono, p.fecha_registro, 
        COUNT(r.id_reserva) as total_reservas,
        SUM(CASE WHEN r.estado IN ('completada', 'atendida') THEN 1 ELSE 0 END) as total_atendidas
        FROM pacientes p
        LEFT JOIN reservas r ON p.id_paciente = r.id_paciente
        GROUP BY p.id_paciente
        ORDER BY total_reservas DESC, p.nombre ASC";

$resultado = $conn->query($sql);
$pacientes = [];
$total_pacientes = 0;
$total_reservas = 0;
$total_atendidas = 0;

if($resultado) {
    while($row = $resultado->fetch_assoc()) {
        $pacientes[] = $row;
        $total_pacientes++;
        $total_reservas += $row['total_reservas'];
        $total_atendidas += $row['total_atendidas'];
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Pacientes - RINOMEDIC</title>
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
            <p>ESTAMOS  PARA TI </p>
        </div>

        <div class="report-title">Reporte de Pacientes Atendidos</div>

        <div class="stats-box">
            <div class="stat-item">
                <div class="number"><?php echo $total_pacientes; ?></div>
                <div class="label">Total de Pacientes</div>
            </div>
            <div class="stat-item">
                <div class="number"><?php echo $total_reservas; ?></div>
                <div class="label">Total de Reservas</div>
            </div>
            <div class="stat-item">
                <div class="number"><?php echo $total_atendidas; ?></div>
                <div class="label">Citas Completadas</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>CÉDULA</th>
                    <th>PACIENTE</th>
                    <th>TELÉFONO</th>
                    <th>EMAIL</th>
                    <th class="text-center">TOTAL RESERVAS</th>
                    <th class="text-center">ATENDIDAS</th>
                    <th class="text-center">FECHA REGISTRO</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $contador = 1;
                foreach($pacientes as $paciente): 
                ?>
                <tr>
                    <td><?php echo $contador; ?></td>
                    <td><?php echo htmlspecialchars($paciente['cedula']); ?></td>
                    <td><strong><?php echo htmlspecialchars($paciente['nombre']) . ' ' . htmlspecialchars($paciente['apellido']); ?></strong></td>
                    <td><?php echo htmlspecialchars($paciente['telefono']); ?></td>
                    <td><?php echo htmlspecialchars($paciente['email']); ?></td>
                    <td class="text-center"><?php echo $paciente['total_reservas']; ?></td>
                    <td class="text-center"><?php echo $paciente['total_atendidas']; ?></td>
                    <td class="text-center"><?php echo date('d/m/Y', strtotime($paciente['fecha_registro'])); ?></td>
                </tr>
                <?php 
                $contador++;
                endforeach; 
                ?>
            </tbody>
        </table>

        <div class="footer">
            <p>Reporte generado el <?php echo date('d/m/Y H:i:s'); ?> | RINOMEDIC - Sistema de Gestión</p>
        </div>
    </div>
</body>
</html>
