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
 * Appends one value to a form body in PHP's bracket syntax, the only
 * nesting Moodle reads: arrays and objects become name[0], name[key],
 * booleans 1 or 0 (PARAM_BOOL refuses "true"), and an empty or null value
 * is left out so that Moodle applies the parameter's default.
 *
 * @param {URLSearchParams} out
 * @param {String} name
 * @param {*} value
 */
const appendBracketed = (out, name, value) => {
    if (value === null || value === undefined || value === '') {
        return;
    }
    if (typeof value === 'object') {
        Object.entries(value).forEach(([key, item]) => appendBracketed(out, `${name}[${key}]`, item));
        return;
    }
    out.append(name, typeof value === 'boolean' ? (value ? '1' : '0') : String(value));
};

/**
 * Rebuilds an operation's form body from the values typed into the viewer.
 *
 * Swagger UI's own encoding is not one Moodle reads: it joins an array's
 * items with commas, sends an object as a JSON string and a boolean as
 * "true". So the body is rebuilt from the raw values Swagger UI keeps in
 * its state, where an array is still an array; object fields hold the JSON
 * typed in, and a top-level boolean field holds text, read through the
 * operation's schema.
 *
 * @param {Object} system Swagger UI's system
 * @param {String} path The operation's documented path
 * @returns {String|null} The encoded body, or null when there is nothing to rebuild from
 */
const encodeBody = (system, path) => {
    const values = system.oas3Selectors.requestBodyValue(path, 'post');
    if (!values || !values.toJS) {
        return null;
    }
    const properties = system.specSelectors.specJson().getIn([
        'paths', path, 'post', 'requestBody', 'content', 'application/x-www-form-urlencoded', 'schema', 'properties',
    ]);

    const out = new URLSearchParams();
    Object.entries(values.toJS()).forEach(([name, field]) => {
        let value = field && typeof field === 'object' && 'value' in field ? field.value : field;
        // A nullable field's type is an Immutable list ([X, "null"]).
        const declared = properties?.getIn([name, 'type']);
        const type = [].concat(declared?.toJS ? declared.toJS() : declared ?? []);
        if (typeof value === 'string' && (type.includes('object') || type.includes('array'))) {
            try {
                value = JSON.parse(value);
            } catch (e) {
                // Not JSON: sent as typed, and Moodle says what is wrong with it.
            }
        } else if (type.includes('boolean') && (value === 'true' || value === 'false')) {
            value = value === 'true';
        }
        appendBracketed(out, name, value);
    });

    return out.toString();
};

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
 * The form body is rebuilt on the way, see encodeBody().
 *
 * @param {String} endpoint Absolute URL of Moodle's REST server
 * @param {Object} request The request Swagger UI is about to send
 * @param {Function} getSystem Returns Swagger UI's system
 * @returns {Object} The same request, retargeted if it is an operation
 */
const retarget = (endpoint, request, getSystem) => {
    // Same-origin credentials: the document is served by an admin-only page,
    // so the fetch has to carry the session cookie.
    request.credentials = 'same-origin';

    if (request.loadSpec) {
        return request;
    }

    const url = new URL(request.url);
    const name = url.pathname.split('/').pop();
    const target = new URL(endpoint);
    url.searchParams.forEach((value, key) => target.searchParams.append(key, value));
    target.searchParams.set('wsfunction', name);
    request.url = target.toString();

    const body = encodeBody(getSystem(), '/' + name);
    if (body !== null) {
        request.body = body;
    }

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

    // The interceptor reads the viewer's own state, so it needs the instance
    // this call returns: hence a getter, only called once a request is sent.
    const ui = swaggerUi({
        url: specUrl,
        domNode: container,
        presets: [swaggerUi.presets.apis],
        deepLinking: true,
        docExpansion: 'none',
        defaultModelsExpandDepth: 0,
        tryItOutEnabled: true,
        withCredentials: true,
        requestInterceptor: (request) => retarget(endpoint, request, () => ui.getSystem()),
    });
};
