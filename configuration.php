<?php

function MongoCon() {
	
	try {
    $connection = new Mongo();
    $collection = $connection->safedocuments->documentos;
    return $collection;
} catch (MongoConnectionException $e) {
    die("Fallo en la conexión a la base de datos " . $e->getMessage());
    
}
	
}

// Retorna la ruta donde se almacenan los archivos
function pathFiles()
{
// Definir directorio donde almacenar archivos, debe terminar eb "/"
$directorio="KWE54O31MDORBOJRFRPLMM8C7H24LQQR/";

try { 
$path="./".$directorio;	

if (!file_exists($path)) {
mkdir($path, 0755);
}

writeHtaccess($path);

return $path;
  } 

catch (Exception $e) 
 {
	 echo $e;
  return false;
 }
}


function writeHtaccess($path)
{
// htaccess documentos
if(!file_exists($path.'.htaccess'))
{
$htaccess_content="Order allow,deny
Deny from all";
$file = fopen($path.'.htaccess' , "w+");
fwrite($file, $htaccess_content);
}
// htaccess Raiz
if(!file_exists('./.htaccess'))
{
$htaccess_content="Options -Indexes
Options +FollowSymlinks
RewriteEngine on
#RewriteBase /safeDocumentsMongoDB/
RewriteRule ^([a-zA-Z]+).html$ index.php?req=$1";
$file = fopen('./.htaccess' , "w+");
fwrite($file, $htaccess_content);
}

}

?>
