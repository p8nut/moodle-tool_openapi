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
 * Mounts the bundled Swagger UI on pages/documentation/viewer.php.
 *
 * The library itself is a plain browser bundle loaded in the page head
 * (thirdparty/swagger-ui), not an AMD dependency, so it arrives as a global
 * and this module only configures and starts it.
 *
 * @module     tool_openapi/viewer
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Rewrites a Try-it-out request to the URL Moodle actually serves.
 *
 * The document gives every function a path of its own because OpenAPI
 * cannot describe one path as many operations, but Moodle has a single REST
 * endpoint and selects the function with the wsfunction parameter. The path
 * already names the function, so the document does not repeat it as a
 * parameter: it is taken from the path here, and the path swapped for the
 * real endpoint, keeping the query string (wstoken, moodlewsrestformat) the
 * operation built.
 *
 * Swagger UI puts the fetch of the document itself through this same
 * interceptor, flagged with loadSpec; that one is left alone.
 *
 * @param {String} endpoint Absolute URL of Moodle's REST server
 * @param {Object} request The request Swagger UI is about to send
 * @returns {Object} The same request, retargeted if it is an operation
 */
const retarget = (endpoint, request) => {
    // Same-origin credentials: the document is served by an admin-only page,
    // so the fetch has to carry the session cookie.
    request.credentials = 'same-origin';

    if (request.loadSpec) {
        return request;
    }

    const url = new URL(request.url);
    const target = new URL(endpoint);
    url.searchParams.forEach((value, name) => target.searchParams.append(name, value));
    target.searchParams.set('wsfunction', url.pathname.split('/').pop());
    request.url = target.toString();

    return request;
};

/**
 * Renders the catalog into the viewer container.
 *
 * @param {String} specUrl Where to fetch the OpenAPI document from
 * @param {String} endpoint Absolute URL of Moodle's REST server
 * @param {String} elementId Id of the element to render into
 */
export const init = (specUrl, endpoint, elementId) => {
    const container = document.getElementById(elementId);
    const swaggerUi = window.SwaggerUIBundle;

    if (!container || !swaggerUi) {
        return;
    }

    swaggerUi({
        url: specUrl,
        domNode: container,
        presets: [swaggerUi.presets.apis],
        deepLinking: true,
        docExpansion: 'none',
        defaultModelsExpandDepth: 0,
        tryItOutEnabled: true,
        withCredentials: true,
        requestInterceptor: (request) => retarget(endpoint, request),
    });
};
