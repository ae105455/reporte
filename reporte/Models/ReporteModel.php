<?php

class ReporteModel
{
    private $db;

    public function __construct()
    {
        // Parámetros para la base de datos
        $this->db = new PDO(
            'mysql:host=localhost;dbname=dbventas;charset=utf8',
            'root',
            ''
        );
    }

    public function getVentasPorFecha($fecha_inicio, $fecha_fin)
    {
        $sql = "SELECT
                    v.id AS venta_id,
                    v.fecha,
                    p.nombre AS producto,
                    dv.cantidad,
                    dv.precio_unitario,
                    (dv.cantidad * dv.precio_unitario) AS subtotal
                FROM ventas v
                INNER JOIN detalle_ventas dv ON v.id = dv.venta_id
                INNER JOIN productos p ON dv.producto_id = p.id
                WHERE v.fecha BETWEEN :inicio AND :fin
                ORDER BY v.id ASC";

        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':inicio', $fecha_inicio);
        $stmt->bindParam(':fin', $fecha_fin);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}