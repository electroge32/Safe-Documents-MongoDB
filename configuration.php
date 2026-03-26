<?php

require_once __DIR__ . '/vendor/autoload.php';

date_default_timezone_set('America/Bogota');

/**
 * Retorna la colección MongoDB (singleton).
 * Edita la URI de conexión si tu servidor no corre en localhost:27017.
 */
function MongoCon(): MongoDB\Collection
{
    static $collection = null;
    if ($collection !== null) {
        return $collection;
    }
    try {
        $client     = new MongoDB\Client('mongodb://localhost:27017');
        $collection = $client->safedocuments->documentos;
        return $collection;
    } catch (MongoDB\Driver\Exception\ConnectionException $e) {
        die('Fallo en la conexión a MongoDB: ' . $e->getMessage());
    }
}

/**
 * Retorna la ruta del directorio de almacenamiento de archivos.
 * Lo crea si no existe y le aplica protección .htaccess.
 */
function pathFiles(): string
{
    $path = './KWE54O31MDORBOJRFRPLMM8C7H24LQQR/';
    if (!file_exists($path)) {
        mkdir($path, 0755, true);
    }
    writeHtaccess($path);
    return $path;
}

function writeHtaccess(string $path): void
{
    if (!file_exists($path . '.htaccess')) {
        file_put_contents($path . '.htaccess', "Order allow,deny\nDeny from all\n");
    }
    if (!file_exists('./.htaccess')) {
        file_put_contents('./.htaccess',
            "Options -Indexes\n" .
            "Options +FollowSymlinks\n" .
            "RewriteEngine on\n" .
            "#RewriteBase /safeDocumentsMongoDB/\n" .
            "RewriteRule ^([a-zA-Z]+)\\.html$ index.php?req=\$1 [L]\n"
        );
    }
}
