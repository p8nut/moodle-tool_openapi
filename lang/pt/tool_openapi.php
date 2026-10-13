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
 * Portuguese language strings for tool_openapi
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['accesscontrolheading'] = 'Métodos de acesso';
$string['accesscontrolheading_desc'] = 'Ative ou desative cada forma de autorizar um pedido ao documento OpenAPI. Os quatro estão desativados numa instalação nova.';
$string['addiprule'] = 'Adicionar regra de IP';
$string['allowedfunctions'] = 'Funções permitidas';
$string['allowedfunctions_help'] = 'Pesquise e selecione as funções a que isto deve ter acesso.';
$string['backtoaccesscontrol'] = 'Voltar aos métodos de acesso';
$string['backtodocumentation'] = 'Voltar à documentação';
$string['cacheheading'] = 'Cache';
$string['cacheheading_desc'] = 'O catálogo completo de serviços web é guardado em cache; consulte a documentação do plugin para saber quando é purgado automaticamente.';
$string['cachepurged'] = 'A cache do catálogo OpenAPI foi purgada.';
$string['catalogheading'] = 'Catálogo';
$string['catalogheading_desc'] = 'Descarregue o documento OpenAPI gerado ou consulte-o no visualizador interativo.';
$string['configuregate'] = 'Configurar {$a}';
$string['confirmdeleteiprule'] = 'Eliminar a regra de IP de «{$a}»? Esta ação não pode ser anulada.';
$string['confirmdeletetoken'] = 'Eliminar o token «{$a}»? Qualquer integração que o utilize perderá o acesso de imediato, e esta ação não pode ser anulada.';
$string['created'] = 'Criado';
$string['createtoken'] = 'Criar token';
$string['deletetoken'] = 'Eliminar token';
$string['disabled'] = 'Desativado';
$string['downloadjson'] = 'Descarregar JSON';
$string['downloadyaml'] = 'Descarregar YAML';
$string['editiprule'] = 'Editar regra de IP';
$string['emptyallowedfunctions'] = 'Selecione pelo menos uma função ou desative a restrição para permitir o catálogo completo.';
$string['enabled'] = 'Ativado';
$string['endpointheading'] = 'O que estes métodos controlam';
$string['endpointheading_desc'] = 'Decidem quem pode ler o documento OpenAPI no endereço abaixo, e nada mais. As páginas deste plugin, incluindo o visualizador e as descargas, exigem a capacidade {$a}, por isso um administrador consegue sempre chegar ao catálogo mesmo com todos os métodos desligados.';
$string['eventtokencreated'] = 'Token OpenAPI criado';
$string['eventtokendeleted'] = 'Token OpenAPI eliminado';
$string['fullcatalog'] = 'Catálogo completo';
$string['gateip'] = 'Endereço IP';
$string['gateip_desc'] = 'Um pedido a partir de um endereço IP ou intervalo CIDR permitido é autorizado sem qualquer credencial.';
$string['gatesession'] = 'Sessão do Moodle';
$string['gatesession_desc'] = 'Um utilizador autenticado com a capacidade adequada pode consultar o catálogo através da sessão do seu navegador.';
$string['gatetogglefailed'] = 'Não foi possível alterar a definição, tente novamente.';
$string['gatetoken'] = 'Token do plugin';
$string['gatetoken_desc'] = 'Um pedido com um token emitido por este plugin, enviado no cabeçalho Authorization: Bearer.';
$string['gatewstoken'] = 'Token de serviço web existente';
$string['gatewstoken_desc'] = 'Um pedido com um token de serviço web do Moodle já existente, reutilizado para ler a documentação em vez de emitir um segundo segredo.';
$string['invalidiprange'] = 'Não é um endereço IP nem um intervalo CIDR válido.';
$string['invalidservice'] = 'Não existe nenhum serviço externo com o nome abreviado «{$a}».';
$string['iprange'] = 'Endereço IP ou intervalo CIDR';
$string['iprange_desc'] = 'Um endereço único (192.0.2.1) ou um intervalo CIDR (192.0.2.0/24). Podem indicar-se vários separados por vírgulas.';
$string['iprestrictionheading'] = 'Restrição por IP';
$string['ipruledeleted'] = 'A regra de IP foi eliminada.';
$string['iprulesaved'] = 'A regra de IP foi guardada.';
$string['lastused'] = 'Última utilização';
$string['manageaccesscontrol'] = 'Controlo de acesso';
$string['managedocumentation'] = 'Documentação';
$string['manageiprules'] = 'Regras de IP';
$string['managetokens'] = 'Tokens';
$string['never'] = 'Nunca';
$string['noiprestriction'] = 'Qualquer';
$string['noiprules'] = 'Ainda não há regras de IP.';
$string['notokens'] = 'Ainda não há tokens.';
$string['openapi:manage'] = 'Gerir as definições, os tokens e as regras de IP da documentação OpenAPI';
$string['openapi:view'] = 'Ver a documentação OpenAPI';
$string['openapi:viewfullcatalog'] = 'Ver o catálogo completo de serviços web com uma sessão do Moodle';
$string['pluginname'] = 'Documentação OpenAPI';
$string['privacy:metadata:tokens'] = 'Tokens emitidos por este plugin para ler a documentação OpenAPI.';
$string['privacy:metadata:tokens:createdby'] = 'O utilizador que emitiu o token.';
$string['privacy:metadata:tokens:iprestriction'] = 'Os endereços a partir dos quais o token pode ser usado, se estiver restringido.';
$string['privacy:metadata:tokens:lastused'] = 'Quando o token foi usado pela última vez.';
$string['privacy:metadata:tokens:name'] = 'A etiqueta atribuída ao token.';
$string['privacy:metadata:tokens:timecreated'] = 'Quando o token foi emitido.';
$string['privacy:tokennotexported'] = 'O token em si não é exportado, por motivos de segurança.';
$string['purgecache'] = 'Purgar a cache do catálogo OpenAPI';
$string['regeneratespectask'] = 'Regenerar o documento do catálogo OpenAPI em cache';
$string['restrictfunctions'] = 'Restringir a serviços web específicos';
$string['restrictfunctions_help'] = 'Desativado por omissão, o que significa acesso a todas as funções do catálogo. Ative-o para escolher exatamente que funções são permitidas.';
$string['restrictip'] = 'Permitir apenas pedidos de determinados endereços IP';
$string['restrictip_help'] = 'Desativado por omissão, o que significa que o token funciona a partir de qualquer endereço. Ative-o para o limitar a um endereço ou a um intervalo CIDR, tal como se pode limitar um token de serviço web do Moodle.';
$string['ruledescription'] = 'Descrição';
$string['ruleenabled'] = 'Ativada';
$string['saverule'] = 'Guardar regra';
$string['tokencreatedonce'] = 'Este token é mostrado apenas uma vez. Copie-o agora: não poderá ser recuperado depois de sair desta página.';
$string['tokendeleted'] = 'O token «{$a}» foi eliminado.';
$string['tokenname'] = 'Nome';
$string['viewer'] = 'Abrir o visualizador interativo';
$string['viewerheading'] = 'Visualizador interativo';
$string['viewerheading_desc'] = 'Consulte todas as funções que este site expõe, com os seus parâmetros, a forma da resposta e as capacidades que exigem.';
$string['viewerrestdisabled'] = 'O «Try it out» falhará enquanto este site tiver os serviços web ou o protocolo REST desativados: o Moodle responde a esses pedidos com um 403 vazio. Consultar o catálogo abaixo funciona à mesma. <a href="{$a}">Ativar os serviços web</a>.';
$string['viewertokenhint'] = 'Os pedidos enviados a partir daqui vão para o endpoint REST deste site. Introduza um token de serviço web uma vez em «Authorize» antes de usar «Try it out», caso contrário o Moodle rejeitará o pedido.';
