# Safe Documents — versión MongoDB

Proyecto educativo en PHP que demuestra cómo construir un sistema de almacenamiento y consulta de documentos usando **MongoDB como backend**. Forma parte de un par de proyectos que comparan MongoDB con MySQL resolviendo exactamente el mismo problema.

> Ver también: [versión MySQL](../Sefe-Documents-MySQL/)

## ¿Qué hace?

Permite subir archivos acompañados de título, descripción y etiquetas. Una vez subidos, se pueden buscar por palabras clave y descargar. Funciona como una versión simplificada de lo que fue Megaupload o RapidShare.

## Requisitos

- PHP 8.0 o superior
- Extensión PECL `mongodb` instalada (`pecl install mongodb`)
- Composer
- MongoDB 4.0 o superior
- Apache con `mod_rewrite` habilitado

## Instalación

```bash
# 1. Instalar dependencias PHP
composer install

# 2. Iniciar MongoDB
#    Si usas la configuración incluida:
mongod --config extras/mongodb.conf
#    O simplemente:
mongod

# 3. No hay schema que importar: MongoDB crea la colección al insertar el primer documento.
```

La URI de conexión y el timezone se configuran en `configuration.php`:

```php
$client = new MongoDB\Client('mongodb://localhost:27017');
```

## Acceso

Abre el proyecto en tu servidor web Apache y accede desde el navegador.

| Campo | Valor |
|-------|-------|
| Usuario | `demo` |
| Contraseña | `demo` |

## Cómo funciona la búsqueda

El buscador implementa dos algoritmos según la entrada del usuario, ilustrando las capacidades de búsqueda de MongoDB:

**Una sola palabra** — expresión regular (equivalente al `LIKE` de SQL):

```php
$regex  = new MongoDB\BSON\Regex(preg_quote($buscar, '/'), 'i');
$cursor = MongoCon()->find([
    '$or' => [
        ['titulo'      => $regex],
        ['descripcion' => $regex],
        ['etiquetas'   => $regex],
    ],
]);
```

**Dos o más palabras** — índice de texto completo (`$text`):

```php
// El índice se crea automáticamente si no existe (idempotente)
MongoCon()->createIndex(
    ['titulo' => 'text', 'descripcion' => 'text', 'etiquetas' => 'text'],
    ['name' => 'text_search']
);

$cursor = MongoCon()->find(['$text' => ['$search' => $buscar]]);
```

A diferencia de MySQL, en MongoDB no es necesario declarar el índice en el schema de antemano: se crea en tiempo de ejecución la primera vez que se realiza una búsqueda multipalabra.

## Modelo del documento

Cada archivo subido genera un documento con esta estructura en la colección `documentos`:

```json
{
  "_id":           ObjectId("..."),
  "titulo":        "Nombre del documento",
  "descripcion":   "Descripción del contenido",
  "etiquetas":     "php,mongodb,ejemplo",
  "idusuario":     777,
  "archivo":       "4K9XZABCDEF...",
  "nombrearchivo": "mi-documento.pdf",
  "fecha":         ISODate("2026-03-26T...")
}
```

Base de datos: `safedocuments` · Colección: `documentos`

## Estructura del proyecto

```
Safe-Documents-MongoDB/
├── css/                   Estilos (reset, composición, global)
├── images/                Recursos gráficos
├── extras/
│   └── mongodb.conf       Configuración del servidor mongod
├── KWE54O31MDORBOJRFRPLMM8C7H24LQQR/  Almacenamiento de archivos (protegido)
├── composer.json          Dependencia: mongodb/mongodb ^1.17
├── configuration.php      Conexión MongoDB\Client + helpers de rutas
├── lib.php                Lógica de dominio: formularios, CRUD, búsqueda
├── index.php              Front controller
└── download.php           Descarga autenticada de archivos
```

## Diferencias respecto a la versión original (2013)

| Aspecto | Antes | Ahora |
|---------|-------|-------|
| Driver MongoDB | Extensión `mongo` (obsoleta desde PHP 7) | `mongodb/mongodb` ^1.17 vía Composer |
| Clases BSON | `MongoRegex`, `MongoDate`, `MongoId` | `BSON\Regex`, `BSON\UTCDateTime`, `BSON\ObjectId` |
| Índice de texto | `ensureIndex()` + `db->command(['text'=>...])` | `createIndex()` + operador `$text` |
| Conexión | Nueva instancia en cada función | Singleton en `MongoCon()` |
| HTML | XHTML 1.0 Transitional | HTML5 semántico (`header`, `nav`, `main`, `footer`) |
| SEO | Sin meta tags | `viewport`, `meta description`, `lang` |
| CSRF | Sin protección | Token por sesión con `hash_equals()` |
| Entropía aleatoria | `mt_rand()` | `random_int()` (criptográficamente seguro) |

---

© Electroge32 — publicado originalmente en 2013, refactorizado en 2026.
