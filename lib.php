<?php

require_once 'configuration.php';

/* ── Formularios ─────────────────────────────────────────────────────────── */

function formLoadFile(): void
{
    $token = htmlspecialchars($_SESSION['csrf_token'] ?? '');
    echo <<<HTML
    <form method="post" id="subirdocumento" name="subirdocumento"
          action="index.php" enctype="multipart/form-data">
      <fieldset>
        <legend>Información del documento</legend>
        <ul>
          <li>
            <label for="titulo">Título:</label>
            <input id="titulo" name="titulo" type="text"
                   placeholder="Título del documento" required>
          </li>
          <li class="textarea">
            <label for="descripcion">Descripción:</label>
            <textarea id="descripcion" name="descripcion" cols="50" rows="5"
                      placeholder="Descripción del documento" required></textarea>
          </li>
          <li>
            <label for="etiqueta">Etiquetas:</label>
            <input id="etiqueta" name="etiqueta" type="text"
                   placeholder="etiqueta1,etiqueta2,...">
          </li>
          <li>
            <label for="documento">Documento:</label>
            <input name="documento" type="file" id="documento" required>
          </li>
        </ul>
      </fieldset>
      <input type="hidden" name="upLoad"      value="formUpLoad">
      <input type="hidden" name="csrf_token"  value="{$token}">
      <input type="submit" id="submit" name="submit" value="Subir Documento">
    </form>
    HTML;
}

function formSearch(?string $search): void
{
    $buscar = htmlspecialchars(trim((string) $search));
    $token  = htmlspecialchars($_SESSION['csrf_token'] ?? '');
    echo <<<HTML
    <form method="post" id="formSearch" name="formSearch" action="./">
      <fieldset>
        <label for="search">Buscar Documentos</label>
        <input id="search" name="search" type="search"
               value="{$buscar}" placeholder="Palabra o frase a buscar">
      </fieldset>
      <input type="hidden" name="formVSearch" value="formSearch">
      <input type="hidden" name="csrf_token"  value="{$token}">
      <input type="submit" id="submit" name="submit" value="Buscar">
    </form>
    HTML;
}

function formLogin(): void
{
    echo <<<HTML
    <form method="post" id="formLogin" name="formLogin" action="./">
      <fieldset>
        <legend>Inicia sesión para acceder al sistema</legend>
        <ul>
          <li>
            <label for="user">Usuario</label>
            <input id="user" name="user" type="text"
                   autocomplete="username" required>
          </li>
          <li>
            <label for="password">Contraseña</label>
            <input id="password" name="password" type="password"
                   autocomplete="current-password" required>
          </li>
        </ul>
      </fieldset>
      <input type="hidden" name="Login" value="formLogin">
      <input type="submit" id="submit" name="submit" value="Iniciar sesión">
    </form>
    HTML;
}

/* ── Autenticación ───────────────────────────────────────────────────────── */

function loginMember(string $user, string $password): string
{
    if (!$user || !$password) {
        return 'Se requiere un usuario y contraseña válidos.';
    }
    // Credenciales de demostración: demo / demo
    if ($user === 'demo' && $password === 'demo') {
        $_SESSION['Safe-Documents'] = serialize([
            'member' => ['usuario' => 'demo', 'nombre' => 'Demo', 'id' => 777],
        ]);
        $_SESSION['SESION_TIME'] = time();
        $_SESSION['csrf_token']  = bin2hex(random_bytes(32));
        header('Location: ./');
        exit;
    }
    return 'El usuario o la contraseña no son válidos.';
}

/* ── Archivos ────────────────────────────────────────────────────────────── */

/**
 * Mueve el archivo subido al directorio de almacenamiento.
 * Devuelve [nombre_interno, nombre_original] o ['NO','NO'] en caso de error.
 */
function sefeFile(array $files, string $campo): array
{
    $nombreUsuario = str_replace(' ', '-', $files[$campo]['name']);

    do {
        $nombreInterno = randString();
    } while (file_exists(pathFiles() . $nombreInterno));

    if (move_uploaded_file($files[$campo]['tmp_name'], pathFiles() . $nombreInterno)) {
        return [$nombreInterno, $nombreUsuario];
    }
    return ['NO', 'NO'];
}

function randString(int $length = 32): string
{
    $chars  = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $result = '';
    for ($i = 0; $i < $length; $i++) {
        $result .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $result;
}

/**
 * Devuelve [archivo_interno, nombre_original] para el _id dado, o null.
 */
function nameFile(string $id): ?array
{
    try {
        $doc = MongoCon()->findOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
    } catch (MongoDB\BSON\InvalidArgumentException $e) {
        return null;
    }
    if (!$doc) {
        return null;
    }
    return [$doc['archivo'], $doc['nombrearchivo']];
}

/* ── Base de datos ───────────────────────────────────────────────────────── */

function reg_document(
    string $titulo,
    string $descripcion,
    string $palabras_clave,
    int    $idUsuario,
    string $archivo,
    string $fecha,        // no usado en MongoDB, se guarda UTCDateTime
    string $nombrearchivo
): bool {
    try {
        MongoCon()->insertOne([
            'titulo'        => $titulo,
            'descripcion'   => $descripcion,
            'etiquetas'     => $palabras_clave,
            'idusuario'     => $idUsuario,
            'archivo'       => $archivo,
            'nombrearchivo' => $nombrearchivo,
            'fecha'         => new MongoDB\BSON\UTCDateTime(),
        ]);
        return true;
    } catch (MongoDB\Driver\Exception\Exception $e) {
        return false;
    }
}

function del_document(string $id): void
{
    try {
        MongoCon()->deleteOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
    } catch (Exception $e) {
        die('Error al eliminar el documento: ' . $e->getMessage());
    }
}

function del_file(string $id): bool
{
    if (!exist_file($id)) {
        return true;
    }
    $vfile = nameFile($id);
    if (!$vfile) {
        return true;
    }
    return @unlink(pathFiles() . $vfile[0]);
}

function exist_file(string $id): bool
{
    $vfile = nameFile($id);
    return $vfile && file_exists(pathFiles() . $vfile[0]);
}

/* ── Presentación ────────────────────────────────────────────────────────── */

function newsDocumentos(): void
{
    $cursor = MongoCon()->find(
        [],
        ['sort' => ['fecha' => -1], 'limit' => 10]
    );
    echo '<h2>Últimas publicaciones</h2>';
    foreach ($cursor as $doc) {
        $fecha = $doc['fecha'] instanceof MongoDB\BSON\UTCDateTime
            ? $doc['fecha']->toDateTime()->format('g:i a - d/m/Y')
            : '—';
        _renderDocumento(
            htmlspecialchars($doc['titulo']  ?? ''),
            htmlspecialchars($doc['descripcion'] ?? ''),
            (string) $doc['_id'],
            $fecha
        );
    }
}

/**
 * Búsqueda en una o varias palabras.
 *   - 1 palabra  → Regex (equivalente a LIKE) en título, descripción y etiquetas.
 *   - 2+ palabras → Índice de texto completo ($text).
 */
function busqueda(string $buscar): void
{
    $terminos = array_filter(explode(' ', trim($buscar)));

    if (count($terminos) === 1) {
        $regex  = new MongoDB\BSON\Regex(preg_quote($buscar, '/'), 'i');
        $cursor = MongoCon()->find([
            '$or' => [
                ['titulo'      => $regex],
                ['descripcion' => $regex],
                ['etiquetas'   => $regex],
            ],
        ]);
    } else {
        // Crea el índice de texto (idempotente)
        MongoCon()->createIndex(
            ['titulo' => 'text', 'descripcion' => 'text', 'etiquetas' => 'text'],
            ['name' => 'text_search']
        );
        $cursor = MongoCon()->find(
            ['$text' => ['$search' => $buscar]],
            ['limit' => 10]
        );
    }

    foreach ($cursor as $doc) {
        $fecha = $doc['fecha'] instanceof MongoDB\BSON\UTCDateTime
            ? $doc['fecha']->toDateTime()->format('g:i a - d/m/Y')
            : '—';
        _renderDocumento(
            htmlspecialchars($doc['titulo']      ?? ''),
            htmlspecialchars($doc['descripcion'] ?? ''),
            (string) $doc['_id'],
            $fecha
        );
    }
}

function _renderDocumento(string $titulo, string $descripcion, string $id, string $fecha): void
{
    echo <<<HTML
    <article>
      <h3>{$titulo}</h3>
      <p>{$descripcion}</p>
      <p>
        <a href="download.php?doc={$id}" target="_blank">Descargar</a> |
        <a href="./?del={$id}">Eliminar</a> |
        Fecha de publicación: {$fecha}
      </p>
    </article>
    HTML;
}
