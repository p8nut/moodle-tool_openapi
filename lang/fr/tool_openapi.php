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
 * French language strings for tool_openapi
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['accesscontrolheading'] = 'Méthodes d\'accès';
$string['accesscontrolheading_desc'] = 'Activez ou désactivez chaque manière d\'autoriser une requête vers le document OpenAPI. Les quatre sont désactivées sur une installation neuve.';
$string['addiprule'] = 'Ajouter une règle d\'IP';
$string['allowedfunctions'] = 'Fonctions autorisées';
$string['allowedfunctions_help'] = 'Recherchez et sélectionnez les fonctions auxquelles cet accès doit être limité.';
$string['backtoaccesscontrol'] = 'Retour aux méthodes d\'accès';
$string['backtodocumentation'] = 'Retour à la documentation';
$string['cacheheading'] = 'Cache';
$string['cacheheading_desc'] = 'Le catalogue complet des services web est mis en cache ; consultez la documentation du plugin pour savoir quand il est purgé automatiquement.';
$string['cachepurged'] = 'Le cache du catalogue OpenAPI a été purgé.';
$string['catalogheading'] = 'Catalogue';
$string['catalogheading_desc'] = 'Téléchargez le document OpenAPI généré ou consultez-le dans la visionneuse interactive.';
$string['configuregate'] = 'Configurer {$a}';
$string['confirmdeleteiprule'] = 'Supprimer la règle d\'IP de « {$a} » ? Cette action est irréversible.';
$string['confirmdeletetoken'] = 'Supprimer le jeton « {$a} » ? Toute intégration qui l\'utilise perdra immédiatement l\'accès, et cette action est irréversible.';
$string['created'] = 'Créé';
$string['createtoken'] = 'Créer un jeton';
$string['deletetoken'] = 'Supprimer le jeton';
$string['disabled'] = 'Désactivé';
$string['downloadjson'] = 'Télécharger le JSON';
$string['downloadyaml'] = 'Télécharger le YAML';
$string['editiprule'] = 'Modifier la règle d\'IP';
$string['emptyallowedfunctions'] = 'Sélectionnez au moins une fonction ou désactivez la restriction pour autoriser le catalogue complet.';
$string['enabled'] = 'Activé';
$string['endpointheading'] = 'Ce que ces méthodes contrôlent';
$string['endpointheading_desc'] = 'Elles déterminent qui peut lire le document OpenAPI à l\x27adresse ci-dessous, et rien d\x27autre. Les pages de ce plugin, y compris la visionneuse et les téléchargements, exigent la capacité {$a}, de sorte qu\x27un administrateur peut toujours atteindre le catalogue même avec toutes les méthodes désactivées.';
$string['eventtokencreated'] = 'Jeton OpenAPI créé';
$string['eventtokendeleted'] = 'Jeton OpenAPI supprimé';
$string['fullcatalog'] = 'Catalogue complet';
$string['gateip'] = 'Adresse IP';
$string['gateip_desc'] = 'Une requête provenant d\'une adresse IP ou d\'une plage CIDR autorisée est acceptée sans aucune identification.';
$string['gatesession'] = 'Session Moodle';
$string['gatesession_desc'] = 'Un utilisateur connecté disposant de la capacité voulue peut consulter le catalogue depuis la session de son navigateur.';
$string['gatetogglefailed'] = 'Impossible de modifier le réglage, veuillez réessayer.';
$string['gatetoken'] = 'Jeton du plugin';
$string['gatetoken_desc'] = 'Une requête portant un jeton émis par ce plugin, envoyé dans l\'en-tête Authorization: Bearer.';
$string['gatewstoken'] = 'Jeton de service web existant';
$string['gatewstoken_desc'] = 'Une requête portant un jeton de service web Moodle existant, réutilisé pour lire la documentation plutôt que d\'émettre un second secret.';
$string['invalidiprange'] = 'Ce n\'est pas une adresse IP ni une plage CIDR valide.';
$string['invalidservice'] = 'Aucun service externe ne porte le nom abrégé « {$a} ».';
$string['iprange'] = 'Adresse IP ou plage CIDR';
$string['iprange_desc'] = 'Une adresse unique (192.0.2.1) ou une plage CIDR (192.0.2.0/24). Plusieurs entrées peuvent être séparées par des virgules.';
$string['iprestrictionheading'] = 'Restriction par IP';
$string['ipruledeleted'] = 'La règle d\'IP a été supprimée.';
$string['iprulesaved'] = 'La règle d\'IP a été enregistrée.';
$string['lastused'] = 'Dernière utilisation';
$string['manageaccesscontrol'] = 'Contrôle d\'accès';
$string['managedocumentation'] = 'Documentation';
$string['manageiprules'] = 'Règles d\'IP';
$string['managetokens'] = 'Jetons';
$string['never'] = 'Jamais';
$string['noiprestriction'] = 'Toutes';
$string['noiprules'] = 'Aucune règle d\'IP pour l\'instant.';
$string['notokens'] = 'Aucun jeton pour l\'instant.';
$string['openapi:manage'] = 'Gérer les réglages, les jetons et les règles d\'IP de la documentation OpenAPI';
$string['openapi:view'] = 'Consulter la documentation OpenAPI';
$string['openapi:viewfullcatalog'] = 'Consulter le catalogue complet des services web avec une session Moodle';
$string['pluginname'] = 'Documentation OpenAPI';
$string['privacy:metadata:tokens'] = 'Jetons émis par ce plugin pour lire la documentation OpenAPI.';
$string['privacy:metadata:tokens:createdby'] = 'L\'utilisateur qui a émis le jeton.';
$string['privacy:metadata:tokens:iprestriction'] = 'Les adresses depuis lesquelles le jeton peut être utilisé, s\'il est restreint.';
$string['privacy:metadata:tokens:lastused'] = 'La dernière utilisation du jeton.';
$string['privacy:metadata:tokens:name'] = 'L\'étiquette donnée au jeton.';
$string['privacy:metadata:tokens:timecreated'] = 'La date d\'émission du jeton.';
$string['privacy:tokennotexported'] = 'Le jeton lui-même n\'est pas exporté, pour des raisons de sécurité.';
$string['purgecache'] = 'Purger le cache du catalogue OpenAPI';
$string['regeneratespectask'] = 'Régénérer le document du catalogue OpenAPI en cache';
$string['restrictfunctions'] = 'Limiter à certains services web';
$string['restrictfunctions_help'] = 'Désactivé par défaut, ce qui donne accès à toutes les fonctions du catalogue. Activez-le pour choisir précisément quelles fonctions sont autorisées.';
$string['restrictip'] = 'N\'autoriser que les requêtes provenant de certaines adresses IP';
$string['restrictip_help'] = 'Désactivé par défaut, ce qui signifie que le jeton fonctionne depuis n\'importe quelle adresse. Activez-le pour le limiter à une adresse ou à une plage CIDR, comme on peut le faire pour un jeton de service web Moodle.';
$string['ruledescription'] = 'Description';
$string['ruleenabled'] = 'Activée';
$string['saverule'] = 'Enregistrer la règle';
$string['tokencreatedonce'] = 'Ce jeton n\'est affiché qu\'une seule fois. Copiez-le maintenant : il sera impossible de le récupérer après avoir quitté cette page.';
$string['tokendeleted'] = 'Le jeton « {$a} » a été supprimé.';
$string['tokenname'] = 'Nom';
$string['viewer'] = 'Ouvrir la visionneuse interactive';
$string['viewerheading'] = 'Visionneuse interactive';
$string['viewerheading_desc'] = 'Parcourez toutes les fonctions exposées par ce site, avec leurs paramètres, la forme de leur réponse et les capacités qu\'elles exigent.';
$string['viewerrestdisabled'] = '« Try it out » échouera tant que ce site aura les services web ou le protocole REST désactivés : Moodle répond à ces requêtes par un 403 vide. Parcourir le catalogue ci-dessous fonctionne quand même. <a href="{$a}">Activer les services web</a>.';
$string['viewertokenhint'] = 'Les requêtes envoyées d\'ici vont vers le point d\'accès REST de ce site. Renseignez une fois un jeton de service web dans « Authorize » avant d\'utiliser « Try it out », sinon Moodle rejettera la requête.';
