<?php

session_start();
require_once 'lib.php';

// Inicializar token CSRF para esta sesión
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* ── Cerrar sesión ───────────────────────────────────────────────────────── */
if (isset($_GET['req']) && $_GET['req'] === 'logout' && isset($_SESSION['Safe-Documents'])) {
    session_unset();
    session_destroy();
    header('Location: ./');
    exit;
}

/* ── Procesar login ──────────────────────────────────────────────────────── */
$message = null;
if (isset($_POST['Login']) && $_POST['Login'] === 'formLogin' && !isset($_SESSION['Safe-Documents'])) {
    $message = loginMember($_POST['user'] ?? '', $_POST['password'] ?? '');
}

/* ── Validar sesión (timeout: 700 s) ────────────────────────────────────── */
$session = false;
if (isset($_SESSION['Safe-Documents'])) {
    if (time() - $_SESSION['SESION_TIME'] > 700) {
        session_unset();
        session_destroy();
    } else {
        $session = true;
        $_SESSION['SESION_TIME'] = time();
    }
}

?><!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description"
        content="Safe Documents (MongoDB) — sube, busca y descarga documentos. Demo educativo que compara MongoDB vs MySQL.">
  <title>Safe Documents — MongoDB</title>
  <link rel="stylesheet" href="./css/global.css">
  <link rel="icon" href="./favicon.ico">
</head>
<body>
<div id="contenedor">

  <header id="cabecera">
    <p><a href="./">Safe Documents</a></p>
    <?php if ($session): ?>
    <nav>
      <ul>
        <li><a href="upload.html">Subir un documento</a></li>
        <li><a href="search.html">Buscar documentos</a></li>
        <li><a href="logout.html">Salir</a></li>
      </ul>
    </nav>
    <?php endif; ?>
  </header>

  <div id="contenido"></div>

  <main>
    <?php if ($message): ?>
      <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <?php if (!$session): ?>

      <?php formLogin(); ?>

    <?php else: ?>

      <?php
      // Rutas GET: upload, search
      if (isset($_GET['req'])) {
          $req = $_GET['req'];
          if ($req === 'upload') {
              formLoadFile();
          } elseif ($req === 'search') {
              formSearch(null);
          } else {
              echo '<p>El elemento solicitado no está disponible.</p>';
          }
      }

      // Eliminar documento
      if (isset($_GET['del'])) {
          $del = htmlspecialchars($_GET['del']);
          if (del_file($del)) {
              del_document($del);
              echo '<p>Elemento eliminado correctamente.</p>';
          } else {
              echo '<p>No se pudo eliminar el elemento.</p>';
          }
      }

      // Buscar
      if (isset($_POST['formVSearch']) && $_POST['formVSearch'] === 'formSearch') {
          if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
              echo '<p>Error de validación del formulario. Recarga la página.</p>';
          } else {
              $search = trim($_POST['search'] ?? '');
              formSearch($search);
              if ($search) {
                  echo '<h2>Resultados de la búsqueda</h2>';
                  busqueda(htmlspecialchars($search));
              } else {
                  echo '<p>Introduce una palabra o frase para buscar.</p>';
              }
          }
      }

      // Subir documento
      if (isset($_POST['upLoad']) && $_POST['upLoad'] === 'formUpLoad') {
          if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
              echo '<p>Error de validación del formulario. Recarga la página.</p>';
          } else {
              $titulo      = htmlspecialchars(trim($_POST['titulo']      ?? ''));
              $descripcion = htmlspecialchars(trim($_POST['descripcion'] ?? ''));
              $etiqueta    = htmlspecialchars(trim($_POST['etiqueta']    ?? ''));
              $vectorFile  = sefeFile($_FILES, 'documento');
              $fecha       = date('Y-m-d H:i:s'); // referencia; MongoDB guarda UTCDateTime

              if (reg_document($titulo, $descripcion, $etiqueta, 777, $vectorFile[0], $fecha, trim($vectorFile[1]))) {
                  echo '<p>Documento registrado correctamente.</p>';
              } else {
                  echo '<p>Fallo al registrar el documento.</p>';
              }
          }
      }

      // Página de inicio: sin parámetros GET ni POST
      if (!$_GET && !$_POST) {
          formSearch(null);
          newsDocumentos();
      }
      ?>

    <?php endif; ?>
  </main>

  <footer id="pie"><p>Electroge32 todos los derechos reservados.</p></footer>

</div>
</body>
</html>
