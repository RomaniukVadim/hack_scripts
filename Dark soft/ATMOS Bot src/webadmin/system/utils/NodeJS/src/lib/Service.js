'use strict';

var util = require('util'),
    events = require('events')
    ;

/** Base service
 * @param {Application} app
 *      Main application
 * @param {String} serviceName
 *      The name of the service
 * @constructor
 * @extends {EventEmitter}
 */
var Service = exports.Service = function(app, serviceName){
    /** Main application instance
     * @type {Application}
     */
    this.app = app;

    /** Service name, taken from the module name
     * @type {String}
     */
    this.serviceName = serviceName;
};
util.inherits(Service, events.EventEmitter);

/** Get the service up and running
 */
Service.prototype.start = function(){
//    console.log('Starting "'+ this.serviceName +'"');
};

/** Make the service halt
 */
Service.prototype.stop = function(){
//    console.log('Stopping "'+ this.serviceName +'"');
};

/** Initialize the service
 * Any custom initialization goes here
 */
Service.prototype.init = function(){
};
