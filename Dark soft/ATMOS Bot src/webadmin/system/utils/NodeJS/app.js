/** Lotus Node
 */

"use strict";

// Requirements
var src = require('./src'),
    _ = require('underscore')
    ;

var Configuration = src.lib.config.Configuration
    ;

/** Main application
 * @property {Configuration} config
 * @property {socket.io.Socket} io
 * @property {Object.<String, IOService>} services
 * @constructor
 */
var Application = function(){
    var App = this;

    // Config
    this.citra_root = __dirname + '/../../../';
    var config_file = this.citra_root + '/system/config.php.ini';
    this.config = new Configuration(config_file);

    // Express
    var express = require('express');
    this.express = express();
    this.http = require('http').createServer(this.express);
    this.express.use(express.bodyParser());
    this.express.use(express.cookieParser());

    // Socket.io
    this.io = require('socket.io').listen(this.http);
    this.io.enable('browser client minification');  // send minified client
    this.io.enable('browser client etag');          // apply etag caching logic based on version number
    this.io.enable('browser client gzip');          // gzip the file
    this.io.set('log level', 1);                    // reduce logging

    this.http.listen(this.config.nodejs.port, this.config.nodejs.host);

    // Stop application
    this.stopApplication = function(callback){
        _.chain(App.services).values().invoke('stop');
        App.http.close(function(){
            callback();
        });
    };

    // Instantiate all services
    this.services = _.chain(src.app.services).map(function(mod, serviceName){
        return [serviceName, new mod.Service(this, serviceName)];
    }.bind(this)).object().value();

    // Start all services
    _.chain(this.services).values().invoke('start');

    // Initialize all services
    _.chain(this.services).values().invoke('init');

    // Global exceptions handler (this way, the daemon lives forever)
    process.on('uncaughtException', function (err) {
        console.error('EXCEPTION: ' + err + "\n" + err.stack);
    });

    // Reload on config file change
    // http://stackoverflow.com/a/12411705/134904
    var fs = require('fs');

    fs.watch(config_file, function (event, filename) {
        console.log('Config file changed. Exiting.');
        App.stopApplication(function(){
            process.exit();
        });
    });
};

if (require.main === module)
    exports.App = new Application();
