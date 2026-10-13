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
 * Italian language strings for tool_openapi
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['accesscontrolheading'] = 'Metodi di accesso';
$string['accesscontrolheading_desc'] = 'Attiva o disattiva ciascun modo di autorizzare una richiesta al documento OpenAPI. In una nuova installazione tutti e quattro sono disattivati.';
$string['addiprule'] = 'Aggiungi regola IP';
$string['allowedfunctions'] = 'Funzioni consentite';
$string['allowedfunctions_help'] = 'Cerca e seleziona le funzioni a cui deve avere accesso.';
$string['backtoaccesscontrol'] = 'Torna ai metodi di accesso';
$string['backtodocumentation'] = 'Torna alla documentazione';
$string['cacheheading'] = 'Cache';
$string['cacheheading_desc'] = 'Il catalogo completo dei web service viene messo in cache; consulta la documentazione del plugin per sapere quando viene svuotata automaticamente.';
$string['cachepurged'] = 'La cache del catalogo OpenAPI è stata svuotata.';
$string['catalogheading'] = 'Catalogo';
$string['catalogheading_desc'] = 'Scarica il documento OpenAPI generato oppure consultalo nel visualizzatore interattivo.';
$string['configuregate'] = 'Configura {$a}';
$string['confirmdeleteiprule'] = 'Eliminare la regola IP di «{$a}»? L\'operazione non può essere annullata.';
$string['confirmdeletetoken'] = 'Eliminare il token «{$a}»? Qualsiasi integrazione che lo utilizza perderà subito l\'accesso e l\'operazione non può essere annullata.';
$string['created'] = 'Creato';
$string['createtoken'] = 'Crea token';
$string['deletetoken'] = 'Elimina token';
$string['disabled'] = 'Disattivato';
$string['downloadjson'] = 'Scarica JSON';
$string['downloadyaml'] = 'Scarica YAML';
$string['editiprule'] = 'Modifica regola IP';
$string['emptyallowedfunctions'] = 'Seleziona almeno una funzione oppure disattiva la restrizione per consentire il catalogo completo.';
$string['enabled'] = 'Attivato';
$string['endpointheading'] = 'Che cosa controllano questi metodi';
$string['endpointheading_desc'] = 'Decidono chi può leggere il documento OpenAPI all\x27indirizzo qui sotto, e nient\x27altro. Le pagine di questo plugin, compresi il visualizzatore e i download, richiedono invece la capacità {$a}, quindi un amministratore può sempre raggiungere il catalogo anche con tutti i metodi disattivati.';
$string['eventtokencreated'] = 'Token OpenAPI creato';
$string['eventtokendeleted'] = 'Token OpenAPI eliminato';
$string['fullcatalog'] = 'Catalogo completo';
$string['gateip'] = 'Indirizzo IP';
$string['gateip_desc'] = 'Una richiesta proveniente da un indirizzo IP o da un intervallo CIDR consentito viene autorizzata senza alcuna credenziale.';
$string['gatesession'] = 'Sessione Moodle';
$string['gatesession_desc'] = 'Un utente autenticato con la capacità adeguata può consultare il catalogo tramite la sessione del proprio browser.';
$string['gatetogglefailed'] = 'Non è stato possibile modificare l\'impostazione, riprova.';
$string['gatetoken'] = 'Token del plugin';
$string['gatetoken_desc'] = 'Una richiesta con un token emesso da questo plugin, inviato nell\'intestazione Authorization: Bearer.';
$string['gatewstoken'] = 'Token di web service esistente';
$string['gatewstoken_desc'] = 'Una richiesta con un token di web service Moodle già esistente, riutilizzato per leggere la documentazione invece di emettere un secondo segreto.';
$string['invalidiprange'] = 'Non è un indirizzo IP né un intervallo CIDR valido.';
$string['invalidservice'] = 'Non esiste alcun servizio esterno con nome breve «{$a}».';
$string['iprange'] = 'Indirizzo IP o intervallo CIDR';
$string['iprange_desc'] = 'Un singolo indirizzo (192.0.2.1) o un intervallo CIDR (192.0.2.0/24). È possibile indicarne diversi separati da virgole.';
$string['iprestrictionheading'] = 'Restrizione per IP';
$string['ipruledeleted'] = 'La regola IP è stata eliminata.';
$string['iprulesaved'] = 'La regola IP è stata salvata.';
$string['lastused'] = 'Ultimo utilizzo';
$string['manageaccesscontrol'] = 'Controllo degli accessi';
$string['managedocumentation'] = 'Documentazione';
$string['manageiprules'] = 'Regole IP';
$string['managetokens'] = 'Token';
$string['never'] = 'Mai';
$string['noiprestriction'] = 'Qualsiasi';
$string['noiprules'] = 'Non ci sono ancora regole IP.';
$string['notokens'] = 'Non ci sono ancora token.';
$string['openapi:manage'] = 'Gestire impostazioni, token e regole IP della documentazione OpenAPI';
$string['openapi:view'] = 'Consultare la documentazione OpenAPI';
$string['openapi:viewfullcatalog'] = 'Consultare il catalogo completo dei web service con una sessione Moodle';
$string['pluginname'] = 'Documentazione OpenAPI';
$string['privacy:metadata:tokens'] = 'Token emessi da questo plugin per leggere la documentazione OpenAPI.';
$string['privacy:metadata:tokens:createdby'] = 'L\'utente che ha emesso il token.';
$string['privacy:metadata:tokens:iprestriction'] = 'Gli indirizzi da cui il token può essere usato, se è soggetto a restrizioni.';
$string['privacy:metadata:tokens:lastused'] = 'Quando il token è stato usato l\'ultima volta.';
$string['privacy:metadata:tokens:name'] = 'L\'etichetta assegnata al token.';
$string['privacy:metadata:tokens:timecreated'] = 'Quando il token è stato emesso.';
$string['privacy:tokennotexported'] = 'Il token stesso non viene esportato, per motivi di sicurezza.';
$string['purgecache'] = 'Svuota la cache del catalogo OpenAPI';
$string['regeneratespectask'] = 'Rigenerare il documento del catalogo OpenAPI in cache';
$string['restrictfunctions'] = 'Limitare a web service specifici';
$string['restrictfunctions_help'] = 'Disattivato per impostazione predefinita, il che significa accesso a tutte le funzioni del catalogo. Attivalo per scegliere esattamente quali funzioni sono consentite.';
$string['restrictip'] = 'Consentire solo richieste da determinati indirizzi IP';
$string['restrictip_help'] = 'Disattivato per impostazione predefinita, il che significa che il token funziona da qualsiasi indirizzo. Attivalo per limitarlo a un indirizzo o a un intervallo CIDR, come si può fare con un token di web service Moodle.';
$string['ruledescription'] = 'Descrizione';
$string['ruleenabled'] = 'Attivata';
$string['saverule'] = 'Salva regola';
$string['tokencreatedonce'] = 'Questo token viene mostrato una sola volta. Copialo adesso: non potrà più essere recuperato dopo aver lasciato questa pagina.';
$string['tokendeleted'] = 'Il token «{$a}» è stato eliminato.';
$string['tokenname'] = 'Nome';
$string['viewer'] = 'Apri il visualizzatore interattivo';
$string['viewerheading'] = 'Visualizzatore interattivo';
$string['viewerheading_desc'] = 'Consulta tutte le funzioni esposte da questo sito, con i relativi parametri, la forma della risposta e le capacità richieste.';
$string['viewerrestdisabled'] = '«Try it out» fallirà finché questo sito ha i web service o il protocollo REST disattivati: Moodle risponde a quelle richieste con un 403 vuoto. Consultare il catalogo qui sotto funziona comunque. <a href="{$a}">Attivare i web service</a>.';
$string['viewertokenhint'] = 'Le richieste inviate da qui vanno all\'endpoint REST di questo sito. Inserisci un token del servizio web una volta in «Authorize» prima di usare «Try it out», altrimenti Moodle rifiuterà la richiesta.';
$string['viewertokenhint_restful'] = 'Le richieste inviate da qui vanno all\'endpoint webservice_restful di questo sito, in JSON. Inserisci un token del servizio web una volta in «Authorize» prima di usare «Try it out»; il suo utente deve avere webservice/restful:use.';
