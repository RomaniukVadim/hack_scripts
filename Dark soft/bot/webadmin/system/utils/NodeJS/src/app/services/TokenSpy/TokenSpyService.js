'use strict';

var util = require('util'),
    fs = require('fs'),
    _ = require('underscore'),

    IOService = require.main.require('./src').lib.IOService.IOService
    ;

/** TokenSpy Service
 * Very simple: room events are just enough here :)
 * @constructor
 * @extends {IOService}
 */
var TokenSpyService = exports.Service = IOService; // same constructor
util.inherits(TokenSpyService, IOService);

TokenSpyService.prototype.init = function(){
//    IOService.prototype.init.call(this);

    // HTTP JSON events source (for the ts.php gate)
    // Test me with `/misc/scripts/TokenSpy/PostEvent.php`
    var self = this;
    this.app.express.post('/TokenSpy/event/:room/:event', function(req, res){
        // Auth check
        if (!IOService.authtoken_check(req.cookies.authToken, self.app.config.nodejs.token)){
            res.send(403, 'Wrong auth token');
            return;
        }

        // Emit to the room
        self.io.in(req.params.room).emit(req.params.event, req.body);
        // Respond
        res.send({ ok: 1 });
    });

    // TS auto-off timer
    var ts_off_timer = null;
    this.io.on('connection', function(socket){
        // Reset any previous timer
        if (ts_off_timer){
            clearTimeout(ts_off_timer);
            ts_off_timer = null;
        }

        // Set up a new timer
        socket.on('disconnect', function(){
            // On disconnect, launch the auto-off timer

            ts_off_timer = setTimeout(function(){
                // On disconnect, force disable TS
                var ts_state_file = self.app.citra_root + '/system/data/TokenSpy/state';
                fs.unlink(ts_state_file, function(err){});

                ts_off_timer = null;
            }, 60*1000);
        });
    });
};

TokenSpyService.prototype.connection = function(socket){
    // Rooms support
    socket.on('subscribe',   function(msg) { socket.join(msg.room); });
    socket.on('unsubscribe', function(msg) { socket.leave(msg.room); });

    // Now broadcast the following events for the 'gate' room members
    var io = this.io;
    _([
        'test', // broadcast test
        'rule', // a rule has matched
        'page', // Page transition
        'visit', // the bot has refreshed the page
        'POST' // POST data was captured
    ]).each(function(e){
        socket.on(e, function(msg){
            io.in('gate').emit(e, msg);
        });
    });
};
