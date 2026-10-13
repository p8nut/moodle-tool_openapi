<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Spanish language strings for tool_openapi
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['accesscontrolheading'] = 'Métodos de acceso';
$string['accesscontrolheading_desc'] = 'Activa o desactiva cada forma de autorizar una petición al documento OpenAPI. Los cuatro están desactivados en una instalación nueva.';
$string['addiprule'] = 'Añadir regla de IP';
$string['allowedfunctions'] = 'Funciones permitidas';
$string['allowedfunctions_help'] = 'Busca y selecciona las funciones a las que debe tener acceso.';
$string['backtoaccesscontrol'] = 'Volver a los métodos de acceso';
$string['backtodocumentation'] = 'Volver a la documentación';
$string['cacheheading'] = 'Caché';
$string['cacheheading_desc'] = 'El catálogo completo de servicios web se guarda en caché; consulta la documentación del plugin para saber cuándo se purga automáticamente.';
$string['cachepurged'] = 'Se ha purgado la caché del catálogo OpenAPI.';
$string['catalogheading'] = 'Catálogo';
$string['catalogheading_desc'] = 'Descarga el documento OpenAPI generado o consúltalo en el visor interactivo.';
$string['configuregate'] = 'Configurar {$a}';
$string['confirmdeleteiprule'] = '¿Eliminar la regla de IP de «{$a}»? Esta acción no se puede deshacer.';
$string['confirmdeletetoken'] = '¿Eliminar el token «{$a}»? Cualquier integración que lo use perderá el acceso de inmediato, y esta acción no se puede deshacer.';
$string['created'] = 'Creado';
$string['createtoken'] = 'Crear token';
$string['deletetoken'] = 'Eliminar token';
$string['disabled'] = 'Desactivado';
$string['downloadjson'] = 'Descargar JSON';
$string['downloadyaml'] = 'Descargar YAML';
$string['editiprule'] = 'Editar regla de IP';
$string['emptyallowedfunctions'] = 'Selecciona al menos una función o desactiva la restricción para permitir el catálogo completo.';
$string['enabled'] = 'Activado';
$string['endpointheading'] = 'Qué controlan estos métodos';
$string['endpointheading_desc'] = 'Deciden quién puede leer el documento OpenAPI en la dirección de abajo, y nada más. Las páginas de este plugin, incluidos el visor y las descargas, requieren la capacidad {$a}, así que un administrador siempre puede llegar al catálogo aunque estén todos los métodos apagados.';
$string['eventtokencreated'] = 'Token de OpenAPI creado';
$string['eventtokendeleted'] = 'Token de OpenAPI eliminado';
$string['fullcatalog'] = 'Catálogo completo';
$string['gateip'] = 'Dirección IP';
$string['gateip_desc'] = 'Una petición desde una dirección IP o un rango CIDR permitidos se autoriza sin ninguna credencial.';
$string['gatesession'] = 'Sesión de Moodle';
$string['gatesession_desc'] = 'Un usuario identificado con la capacidad adecuada puede consultar el catálogo desde la sesión de su navegador.';
$string['gatetogglefailed'] = 'No se ha podido cambiar el ajuste, inténtalo de nuevo.';
$string['gatetoken'] = 'Token del plugin';
$string['gatetoken_desc'] = 'Una petición con un token emitido por este plugin, enviado en la cabecera Authorization: Bearer.';
$string['gatewstoken'] = 'Token de servicio web existente';
$string['gatewstoken_desc'] = 'Una petición con un token de servicio web de Moodle ya existente, reutilizado para leer la documentación en lugar de emitir un segundo secreto.';
$string['invalidiprange'] = 'No es una dirección IP ni un rango CIDR válidos.';
$string['invalidservice'] = 'No existe ningún servicio externo con el nombre corto «{$a}».';
$string['iprange'] = 'Dirección IP o rango CIDR';
$string['iprange_desc'] = 'Una dirección concreta (192.0.2.1) o un rango CIDR (192.0.2.0/24). Se pueden indicar varias separadas por comas.';
$string['iprestrictionheading'] = 'Restricción por IP';
$string['ipruledeleted'] = 'Se ha eliminado la regla de IP.';
$string['iprulesaved'] = 'Se ha guardado la regla de IP.';
$string['lastused'] = 'Último uso';
$string['manageaccesscontrol'] = 'Control de acceso';
$string['managedocumentation'] = 'Documentación';
$string['manageiprules'] = 'Reglas de IP';
$string['managetokens'] = 'Tokens';
$string['never'] = 'Nunca';
$string['noiprestriction'] = 'Cualquiera';
$string['noiprules'] = 'Todavía no hay reglas de IP.';
$string['notokens'] = 'Todavía no hay tokens.';
$string['openapi:manage'] = 'Gestionar los ajustes, los tokens y las reglas de IP de la documentación OpenAPI';
$string['openapi:view'] = 'Ver la documentación OpenAPI';
$string['openapi:viewfullcatalog'] = 'Ver el catálogo completo de servicios web con una sesión de Moodle';
$string['pluginname'] = 'Documentación OpenAPI';
$string['privacy:metadata:tokens'] = 'Tokens emitidos por este plugin para leer la documentación OpenAPI.';
$string['privacy:metadata:tokens:createdby'] = 'El usuario que emitió el token.';
$string['privacy:metadata:tokens:iprestriction'] = 'Las direcciones desde las que se puede usar el token, si está restringido.';
$string['privacy:metadata:tokens:lastused'] = 'Cuándo se usó el token por última vez.';
$string['privacy:metadata:tokens:name'] = 'La etiqueta asignada al token.';
$string['privacy:metadata:tokens:timecreated'] = 'Cuándo se emitió el token.';
$string['privacy:tokennotexported'] = 'El token en sí no se exporta, por motivos de seguridad.';
$string['purgecache'] = 'Purgar la caché del catálogo OpenAPI';
$string['regeneratespectask'] = 'Regenerar el documento del catálogo OpenAPI en caché';
$string['restrictfunctions'] = 'Restringir a servicios web concretos';
$string['restrictfunctions_help'] = 'Desactivado por defecto, lo que significa acceso a todas las funciones del catálogo. Actívalo para elegir exactamente qué funciones se permiten.';
$string['restrictip'] = 'Permitir solo peticiones desde determinadas direcciones IP';
$string['restrictip_help'] = 'Desactivado por defecto, lo que significa que el token funciona desde cualquier dirección. Actívalo para limitarlo a una dirección o a un rango CIDR, igual que se puede limitar un token de servicio web de Moodle.';
$string['ruledescription'] = 'Descripción';
$string['ruleenabled'] = 'Activada';
$string['saverule'] = 'Guardar regla';
$string['tokencreatedonce'] = 'Este token se muestra una sola vez. Cópialo ahora: no se podrá recuperar cuando salgas de esta página.';
$string['tokendeleted'] = 'Se ha eliminado el token «{$a}».';
$string['tokenname'] = 'Nombre';
$string['viewer'] = 'Abrir el visor interactivo';
$string['viewerheading'] = 'Visor interactivo';
$string['viewerheading_desc'] = 'Consulta todas las funciones que expone este sitio, con sus parámetros, la forma de su respuesta y las capacidades que requiere.';
$string['viewerrestdisabled'] = '«Try it out» fallará mientras este sitio tenga los servicios web o el protocolo REST desactivados: Moodle responde a esas peticiones con un 403 vacío. Consultar el catálogo de abajo funciona igual. <a href="{$a}">Activar los servicios web</a>.';
$string['viewertokenhint'] = 'Las peticiones enviadas desde aquí van al endpoint REST de este sitio. Introduce un token de servicio web una vez en «Authorize» antes de usar «Try it out» o Moodle rechazará la petición.';
