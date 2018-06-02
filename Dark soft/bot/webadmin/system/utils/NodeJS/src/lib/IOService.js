'use strict';

var util = require('util'),
    crypto = require('crypto'),
    _ = require('underscore'),

    Service = require('./Service.js').Service
    ;

/** Base socket.io service
 * @constructor
 * @extends {Service}
 */
var IOService = exports.IOService = function(app, serviceName){
    Service.apply(this, arguments);

    /** Namespaced socket for this service
     * @type {socket.io.SocketNamespace}
     */
    this.io = null;
};
util.inherits(IOService, Service);

/** Start
 * Ionize the service: initialize the socket.io part of the service
 * This implementation binds the service to a namespace with an auth callback
 */
IOService.prototype.start = function(){
    Service.prototype.start.call(this);

    // Create a namespaced io
    this.io = this.app.io.of('/' + this.serviceName);
    // Bind handlers: connection
    this.io.on('connection', this.connection.bind(this));
    // Bind handlers: auth
    this.io.authorization(
        // authorization callback with an extra argument to keep some context
        _.partial(this.authentication, this.app.config.nodejs.token)
    );
};

/** Authentication callback on a handshake
 * @param {String} nodejs_token
 *      Configuration.nodejs.token
 * @param {{ headers: Object, time: String, address: Object, xdomain: Boolean, secure: Boolean, issued: Number, url: String, query: Object }} handshake
 *      HTTP handshake data
 * @param {function(error: String?, authorized: Boolean)} accept
 * @this {SocketNamespace}
 */
IOService.prototype.authentication = function(nodejs_token, handshake, accept){
    var login = IOService.authtoken_check(handshake.query.token, nodejs_token);

    if (login === undefined)
        accept('Wrong authentication token');
    else {
        // Store the login
        this.user_login = login;

        // Accept the user
        accept(null, true);
    }
};

/** Acceptation callback on the socket: once it's authorized
 * @param {socket.io.Socket} socket
 */
IOService.prototype.connection = function(socket){
};


/** Check an auth token validity
 * @param {String} auth
 *      A string in the form of "login:token"
 * @param {String} nodejs_token
 *      Configuration.nodejs.token
 * @return {String?}
 *      The user login, or `undefined` when auth can't be checked
 */
IOService.authtoken_check = exports.authtoken_check = function(auth, nodejs_token){
    // Split
    if (!auth)
        return undefined;
    auth = auth.split(':'); // [login, token]

    // Calculate our hash: "<login>:<config.token>"
    var hashable = auth[0] + ':' + nodejs_token;
    var expectedToken = crypto.createHash('md5').update(hashable).digest('hex');

    // Check
    if (auth[1] != expectedToken)
        return undefined;
    return auth[0];
};
