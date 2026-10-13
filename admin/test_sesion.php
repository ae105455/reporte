<?php
session_start();
echo "<h1>Test de Sesión</h1>";
echo "Usuario ID: " . ($_SESSION['usuario_id'] ?? 'NO HAY');
echo "<br><a href='reservas.php'>Ir a Reservas</a>";
echo "<br><a href='reserva_pendientes.php'>Ir a Pendientes</a>";
echo "<br><a href='reserva_cancelar.php?id=1'>Cancelar reserva 1</a>";
?>