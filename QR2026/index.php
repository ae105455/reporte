<?php
include('phpqrcode/qrlib.php');
$dir ='qrs/';
if (!file_exists($dir)) {
    # code...
    mkdir($dir);

}
$filename = $dir .'qr01.php';
$size =10; //tamano de imagen 
$level ='M'; // presion mediama

$framesize = 3; // marco blaco 
$contenido = 'https://www.iseclp.edu.bo';
QRCode::png($contenido, $filename,$level,$size,$framesize);
echo '<img src= " ' . $filename .  ' " />';
?>



