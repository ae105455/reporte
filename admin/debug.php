<?php
session_start();

echo "<h1>DEBUG</h1>";
echo "Sesión usuario_id: " . ($_SESSION['usuario_id'] ?? 'NO EXISTE');
echo "<br>";
echo "<br><a href='index.php'>Index (Login)</a>";
echo "<br><a href='panel.php'>Panel</a>";
echo "<br><a href='reservas.php'>Reservas</a>";
echo "<br><a href='reserva_pendientes.php'>Reservas Pendientes</a>";
echo "<br><a href='logout.php'>Cerrar Sesión</a>";
?>