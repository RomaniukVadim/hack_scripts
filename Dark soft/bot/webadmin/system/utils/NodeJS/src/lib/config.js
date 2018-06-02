'use strict';

var ini = require('ini'),
    _ = require('underscore')
    ;

/** Application configuration storage
 * @param {String} filename Ini-file to read the config from
 * @constructor
 *
 * @property {{host: String, port: Number, token: String}} nodejs
 *      Global daemon settings
 */
var Configuration = exports.Configuration = function(filename){
    // Provide the defaults
    this.nodejs = {
        host: 'localhost',
        port: 8080,
        token: 'XXX'
    };

    // Read the config & extend self
    var config = ini.parse(require('fs').readFileSync(filename, 'utf8'));
    for (var name in config)
        if (config.hasOwnProperty(name))
            _.extend(this[name], config[name]);

    // Prepare

    // Process: [nodejs]
    this.nodejs.port = parseInt(this.nodejs.port);
};
