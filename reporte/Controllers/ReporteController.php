<?php

require_once 'Models/ReporteModel.php';
require_once 'libs/fpdf/fpdf.php';


// 1. Crear una clase personalizada extendiendo FPDF
class PDF extends FPDF
{
    // Encabezado automático
    function Header()
    {
        // Logotipo: archivo, x, y, ancho (en mm)
        // Calculamos la ruta raíz desde la carpeta Controllers
        $rootPath = dirname(__DIR__);

        $logoPath = $rootPath . '/public/assets/img/logo.jpg';

        // Ajusta 'logo.jpg' por la ruta real de tu imagen
        if (file_exists($logoPath)) {
            $this->Image($logoPath, 8, 5, 33);
        }

        $this->SetFont('Arial', 'B', 16);

        $this->Cell(60);

        // Título
        $this->Cell(
            130,
            10,
            utf8_decode('REPORTE DETALLADO DE VENTAS'),
            0,
            1,
            'C'
        );

        $this->SetFont('Arial', 'I', 10);

        $this->Cell(60);

        $this->Cell(
            130,
            5,
            utf8_decode('Sistema de Control de Inventario y Ventas'),
            0,
            1,
            'C'
        );

        $this->Ln(15); // Salto de línea antes de comenzar el contenido
    }


    // Pie de página automático (Paginación X de Y)
    function Footer()
    {
        $this->SetY(-15); // Posición a 1.5 cm del final de la página

        $this->SetFont('Arial', 'I', 8);

        // Texto de paginación dinámica utilizando el alias definido más abajo
        $this->Cell(
            0,
            10,
            utf8_decode('Página ') . $this->PageNo() . '/{nb}',
            0,
            0,
            'C'
        );
    }
}


// 2. Procesar la petición del formulario
if (isset($_GET['action']) && $_GET['action'] == 'generar') {

    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'];

    $model = new ReporteModel();

    $datos = $model->getVentasPorFecha(
        $fecha_inicio,
        $fecha_fin
    );


    // Inicializar la clase personalizada PDF
    $pdf = new PDF();

    // IMPORTANTE: Define el alias para el total de páginas ({nb}) requerido en el Footer
    $pdf->AliasNbPages();

    $pdf->AddPage();


    // Subtítulo del reporte con el rango de fechas
    $pdf->SetFont('Arial', '', 10);

    $pdf->Cell(
        190,
        8,
        "Rango consultado: $fecha_inicio al $fecha_fin",
        0,
        1,
        'L'
    );

    $pdf->Ln(2);


    // Encabezados de la tabla
    $pdf->SetFont('Arial', 'B', 10);

    $pdf->SetFillColor(230, 230, 230);

    $pdf->Cell(25, 8, 'Venta ID', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Fecha', 1, 0, 'C', true);
    $pdf->Cell(65, 8, 'Producto', 1, 0, 'L', true);
    $pdf->Cell(20, 8, 'Cant.', 1, 0, 'C', true);
    $pdf->Cell(25, 8, 'P. Unit', 1, 0, 'R', true);
    $pdf->Cell(25, 8, 'Subtotal', 1, 1, 'R', true);


    $pdf->SetFont('Arial', '', 10);

    $id_venta_actual = null;
    $total_venta = 0;
    $gran_total_periodo = 0;


    foreach ($datos as $fila) {

        // Controlar el cambio de venta para imprimir
        // el total acumulado de la venta anterior
        if (
            $id_venta_actual !== null &&
            $id_venta_actual != $fila['venta_id']
        ) {

            $pdf->SetFont('Arial', 'B', 10);

            $pdf->Cell(
                165,
                8,
                'TOTAL VENTA #' . $id_venta_actual . ':',
                1,
                0,
                'R'
            );

            $pdf->Cell(
                25,
                8,
                '$' . number_format($total_venta, 2),
                1,
                1,
                'R'
            );

            $pdf->SetFont('Arial', '', 10);

            $pdf->Ln(2);

            $total_venta = 0;
        }


        $id_venta_actual = $fila['venta_id'];

        $total_venta += $fila['subtotal'];

        $gran_total_periodo += $fila['subtotal'];


        // Escribir fila de producto
        $pdf->Cell(
            25,
            8,
            $fila['venta_id'],
            1,
            0,
            'C'
        );

        $pdf->Cell(
            30,
            8,
            $fila['fecha'],
            1,
            0,
            'C'
        );

        $pdf->Cell(
            65,
            8,
            utf8_decode($fila['producto']),
            1,
            0,
            'L'
        );

        $pdf->Cell(
            20,
            8,
            $fila['cantidad'],
            1,
            0,
            'C'
        );

        $pdf->Cell(
            25,
            8,
            '$' . number_format($fila['precio_unitario'], 2),
            1,
            0,
            'R'
        );

        $pdf->Cell(
            25,
            8,
            '$' . number_format($fila['subtotal'], 2),
            1,
            1,
            'R'
        );
    }


    // Imprimir el total de la última venta procesada
    if ($id_venta_actual !== null) {

        $pdf->SetFont('Arial', 'B', 10);

        $pdf->Cell(
            165,
            8,
            'TOTAL VENTA #' . $id_venta_actual . ':',
            1,
            0,
            'R'
        );

        $pdf->Cell(
            25,
            8,
            '$' . number_format($total_venta, 2),
            1,
            1,
            'R'
        );
    }


    // Gran total de todo el rango seleccionado
    $pdf->Ln(5);

    $pdf->SetFont('Arial', 'B', 11);

    $pdf->SetFillColor(200, 220, 255);

    $pdf->Cell(
        165,
        10,
        'TOTAL GENERAL DEL PERIODO:',
        1,
        0,
        'R',
        true
    );

    $pdf->Cell(
        25,
        10,
        '$' . number_format($gran_total_periodo, 2),
        1,  
        1,
        'R',
        true
    );


    // Enviar PDF al navegador (Modo 'I' para previsualizar)
    $pdf->Output('I', 'reporte_ventas.pdf');
}
?>