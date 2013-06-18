<?php require_once("configuration.php");

function formLoadFile()
{?>
<p> <form method="post" id="subirdocumento" name="subirdocumento" action="index.php" enctype="multipart/form-data">
  <fieldset>
<legend>Información del documento</legend>

<ul>
<li><label for="titulo">Titulo:</label><input id="titulo" name="titulo" type="text" value="Titulo"/></li>

<li class="textarea"><label for="descripcion">Descripción:</label><textarea id="descripcion" name="descripcion" cols="50" rows="5">Descripción</textarea></li>
<li><label for="etiqueta">Etiquetas:</label><input id="etiqueta" name="etiqueta" type="text" value="etiqueta1,etiqueta2,..."/></li>

<li><label for="documento">Documento:</label>
<input name="documento" type="file" id="documento">
</li>

</ul>
</fieldset>
<input type="submit" id="submit" name="submit" value="Subir Documento" />
<input name="upLoad" id="upLoad" type="hidden" value="formUpLoad" />
</form>
</p>
<?php
}

function formSearch($Search)
{
$buscar=htmlspecialchars(trim($Search));
?>

<p> <form method="post" id="formSearch" name="formSearch" action="./" >
<fieldset>

<label for="search">Buscar Documentos</label><input id="search" name="search" type="text" value="<?php if($buscar) {print($buscar); } ?>" />

</fieldset>
<input type="submit" id="submit" name="submit" value="Buscar" />
<input name="formVSearch" id="formVSearch" type="hidden" value="formSearch" />
</form>
</p>

<?php
}

function formLogin()
{
?>
<form method="post" id="formLogin" name="formLogin" action="./" >
<fieldset>
<legend>Inicia sesión para acceder al sistema</legend>
<ul>
<li><label for="user">Usuario</label><input id="user" name="user" type="text" /></li>
<li><label for="password">Contraseña</label><input id="password" name="password" type="password" /></li>
</ul>
</fieldset>
<input type="submit" id="submit" name="submit" value="Inicir sesión" />
<input name="Login" id="Login" type="hidden" value="formLogin" />
</form>
<?php
}

function loginMember($user, $password)
{
$message;	
if($user && $password)
	{
	if($user=='demo'&&$password=='demo')
	{
	$member=array("member"=>array(
	"usuario"=>'demo',
	"nombre"=>'Demo',
	"id"=>'777'));
	$_SESSION["Safe-Documents"] = serialize($member);	
	$_SESSION["SESION_TIME"] = time();
	header( "Location: ./" );
	}
	else 
	{
	$message='El Usuario o la Contraseña no son validos';
	}	
	}
	else
   {
	$message='Se requiere un usuario y contraseña validos.'; 
   }	
	return $message;
}

// función para guardar documentos
function sefeFile ($arrayDoc,$documento)
{

// Sustituir especios por guion
$archivo_usuario = str_replace(' ','-',$arrayDoc[$documento]['name']); 

$tipo_archivo = $arrayDoc[$documento]['type']; 
$tamano_archivo = $arrayDoc[$documento]['size'];
$extencion = strrchr($arrayDoc[$documento]['name'],'.');

// Rutina que asegura que no se sobre-escriban documentos
$nuevo_archivo;
$flag= true;
while ($flag)
 {
$nuevo_archivo=randString(); //.$extencion;
if (!file_exists(pathFiles().$nuevo_archivo))
{
$flag= false;
}
 }
//compruebo si las características del archivo son las que deseo 
try {

   if (move_uploaded_file($arrayDoc[$documento]['tmp_name'], pathFiles().$nuevo_archivo))
   { 
     //return $nuevo_archivo;
	return $vector = array ( $nuevo_archivo, $archivo_usuario );
   }
    else
     { 
     // return 'NO.png';
	 return $vector = array ( "NO", "NO" );
     } 


}
catch(Exception $e)
{
echo 'Error en la Función sefeFile --> lib.php ', $e->getMessage(), "\n";

exit;
}
}


// función que genera una cadena aleatoria
function randString ($length = 32)
{  
$string = "";
$possible = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXY";
$i = 0;
while ($i < $length)
 {    
$char = substr($possible, mt_rand(0, strlen($possible)-1), 1);
$string .= $char;    
$i++;  
}  
return $string;
}


function nameFile($cod_file)
{
$vname_files=null;


try {
	
    $connection = new Mongo();
    $collection = $connection->safedocuments->documentos;
    
} catch (MongoConnectionException $e) {
    die("Failed to connect to database " . $e->getMessage());
}
$doc = $collection->findOne(array('_id' => new MongoId($cod_file)));
 
      $vname_files[1]=$doc['nombrearchivo'];
      $vname_files[0]=$doc['archivo'];
 

return $vname_files;

}



// muestra los ultimos 10 documentos publicados
function newsDocumentos()
{

 $cursor = MongoCon()->find()->sort(array('fecha'=>-1))->limit(10);
  while ($cursor->hasNext()):
  $doc= $cursor->getNext();
 
 /****************************************************************************************/
 ?>
  <p><h3><?php echo $doc['titulo']; ?></h3> <b>Nombre Archivo:</b> <?php echo $doc['nombrearchivo']; ?> </br>
         <?php echo $doc['descripcion']; ?>
    <br /><a href="download.php?doc=<?php echo $doc['_id']; ?>" target="_blank">Descargar</a>|<a href="./?del=<?php echo $doc['_id']; ?>" target="_blank">Eliminar</a>|Fecha de publicación: <?php echo date('g:i a - d/m/Y', $doc['fecha']->sec); ?>
  </p>
 
  <?php
 
 /****************************************************************************************/
 endwhile;

}


function busqueda($buscar)
{

$buscar=htmlspecialchars(trim($buscar));

$cursor=0;

  $trozos=explode(" ",$buscar);
   $numero=count($trozos);
   if($numero==1) // Algoritmo de búsqueda con una palabra 
   {
   $regex = new MongoRegex('/'.$buscar.'/'); // ~= lyke
     
 $cursor = MongoCon()->find(array('$or' => array(
  array("titulo" => $regex),
  array("descripcion" => $regex),
  array("etiquetas" => $regex)
)));


  while ($cursor->hasNext()):
  $doc= $cursor->getNext();
 
 /****************************************************************************************/
 ?>
  <p><h3><?php echo $doc['titulo']; ?></h3>
         <?php echo $doc['descripcion']; ?>
    <br /><a href="download.php?doc=<?php echo $doc['_id']; ?>" target="_blank">Descargar</a>|<a href="./?del=<?php echo $doc['_id']; ?>" target="_blank">Eliminar</a>|Fecha de publicación: <?php echo date('g:i a - d/m/Y', $doc['fecha']->sec); ?>
  </p>
 
  <?php
 /****************************************************************************************/
 endwhile;
  
   }
   else // Algoritmo de búsqueda con más de una palabra 
   {
   $m = new MongoClient(); // connect
   $db = $collection = $m->safedocuments; // get the database named "safedocuments"
   $collection = $db->documentos; // get the collection "bar" from database named "documentos"
	$collection->ensureIndex(
    array(
        'titulo' => 'text',
        'descripcion' => 'text',
        'etiquetas' => 'text'
    ));

$cursor = $db->command(
    array(
        'text' => 'documentos', //this is the name of the collection where we are searching
        'search' => $buscar, //the string to search
        'limit' => 10, //the number of results, by default is 1000
        'project' => Array( //the fields to retrieve from db
                            'titulo' => 1,
                            'descripcion' => 1,
                            'fecha' => 1,
        )
    )
); 

//print_r($cursor);
//print_r($cursor['results'][0]['obj']['_id']);
//echo '</br> =>'.$cursor['results'][0]['obj']['_id'];

foreach ($cursor['results'] as $array) {
    
   /****************************************************************************************/
 ?>
  <p><h3><?php echo $array['obj']['titulo']; ?></h3>
         <?php echo $array['obj']['descripcion']; ?>
    <br /><a href="download.php?doc=<?php echo $array['obj']['_id']; ?>" target="_blank">Descargar</a>|<a href="./?del=<?php echo $array['obj']['_id']; ?>" target="_blank">Eliminar</a>|Fecha de publicación: <?php echo date('g:i a - d/m/Y', $array['obj']['fecha']->sec); ?>
  </p>
 
  <?php
 /****************************************************************************************/

}
	
   } 

}

function reg_document($titulo,$descripcion,$palabras_clave,$idusuario, $file,$fecha,$file_name)
{
   $documento=array(  
                        'titulo' => $titulo, 
                        'descripcion' => $descripcion, 
                        'etiquetas' => $palabras_clave,
                        'idusuario' => $idusuario,
                        'archivo' => $file,
                        'nombrearchivo' => $file_name,
                        'fecha'  => new MongoDate());                        
 
 try{
    // MongoCon()->insert($documento);
      MongoCon()->save($documento);
        return true;
    } catch (MongoCursorException $e)
    {
        return false;
    }                      

	}
	
function del_document($idDocument)
{
	try{
MongoCon()->remove(array('_id' => new MongoId($idDocument)));
       }
catch (Exception $e) {
    die("Se produjo un error al intentar eliminar el documento => " . $e->getMessage());
}
}		
	
function del_file($cod_file)
{
if (exist_file($cod_file))
  {	
   try
      {
$vname_file=nameFile($cod_file);

 if (!unlink(pathFiles().$vname_file[0]))
	     {
          return false;
         }
		else { return true; }
      }
    catch (Exception $e) { return false; }
 }
else {return true;}
}

function exist_file($cod_file)
{
$vname_file=nameFile($cod_file);
if (file_exists(pathFiles().$vname_file[0]))
{return true; }
else {return false; }
}
?>