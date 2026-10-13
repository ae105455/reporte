<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 50px;
        }

        form {
            background: #f4f4f4;
            padding: 20px;
            border-radius: 5px;
            width: 300px;
        }

        label, input {
            display: block;
            margin-bottom: 10px;
            width: 100%;
        }

        button {
            background: #007BFF;
            color: white;
            border: none;
            padding: 10px;
            cursor: pointer;
        }
    </style>
</head>

<body>

    <h2>Generar Reporte de Ventas</h2>

    <!-- Apunta al controlador -->
    <form action="index.php?action=generar" method="POST">

        <label>Fecha Inicio:</label>
        <input type="date" name="fecha_inicio" required>

        <label>Fecha Fin:</label>
        <input type="date" name="fecha_fin" required>

        <button type="submit">Generar PDF</button>

    </form>

</body>
</html>