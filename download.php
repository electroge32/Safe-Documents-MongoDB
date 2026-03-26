<?php

session_start();
require_once './lib.php';

// Solo usuarios autenticados pueden descargar
if (!isset($_SESSION['Safe-Documents'])) {
    header('Location: ./');
    exit;
}

if (!isset($_GET['doc'])) {
    header('Location: ./');
    exit;
}

$id   = htmlspecialchars($_GET['doc']);
$path = pathFiles();
$info = nameFile($id);

if (!$info || !$info[0]) {
    echo 'El documento solicitado no existe. Contacta al administrador del sistema.';
    exit;
}

$vBarras    = ['/', '\\'];
$rutaFisica = $path . str_replace($vBarras, '_', $info[0]);

if (!file_exists($rutaFisica)) {
    echo 'El documento solicitado no está disponible. Contacta al administrador del sistema.';
    exit;
}

$nombreDescarga = basename($info[1]);
$tamano         = filesize($rutaFisica);

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $nombreDescarga . '"');
header('Content-Transfer-Encoding: binary');
header('Content-Length: ' . $tamano);
header('Cache-Control: no-store');

readfile($rutaFisica);
