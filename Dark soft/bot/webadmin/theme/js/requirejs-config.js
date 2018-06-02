(function(){
    // Helper stuff
    var include = {
        /** Include a custom CSS file
         * @param {String} href
         * @returns {HTMLElement}
         */
        css: function(href){
            var link = document.createElement("link");
            link.type = "text/css";
            link.rel = "stylesheet";
            link.href = href;
            document.getElementsByTagName("head")[0].appendChild(link);
            return link;
        }
    };

    // Socket.io path from `window.global`
    var socketio_js = null;
    try { socketio_js = window.global.nodejs.socketio.replace(/\.js$/, ''); }
    catch(e){ window.console && console.warn('requirejs-config: `window.global.nodejs.socketio` is empty'); }

    // RequireJS config
    require.config({
        baseUrl: "theme/js",
        paths: {
            angular: ['angular-1.0.7/angular.min'],
            'angular-resource': 'angular-1.0.7/angular-resource.min',
            'angular-extensions': 'angular-1.0.7/angular-extensions',
            underscore: 'underscore-min',
            jquery: 'jquery-1.9.1.min',
            bootstrap: '../bootstrap/js/bootstrap.min',
            'socket.io': socketio_js,
            ckeditor: 'ckeditor/ckeditor',
            colorbox: 'colorbox-1.4.15/jquery.colorbox-min',
            'jqueryui': 'jquery-ui-1.10.3.custom/js/jquery-ui-1.10.3.custom.min',
            'jquery.contextMenu': 'contextMenu/src/jquery.contextMenu',
            'jquery.colorpicker': 'colorpicker/jquery.colorpicker'
        },
        shim: {
            underscore: { exports: '_' },
            angular: {
                deps: ["jquery"],
                exports: 'angular'
            },
            'angular-extensions': { deps: ['angular'] },
            'angular-resource': { deps: ['angular'] },
            bootstrap: {
                deps: ["jquery"],
                init: function(){
                    include.css('theme/bootstrap/css/bootstrap.min.css');
                }
            },
            'socket.io': { exports: 'io' },
            ckeditor: {
                exports: 'CKEDITOR'
            },
            colorbox: {
                deps: ['jquery'],
                exports: 'jQuery.colorbox',
                init: function(){
                    include.css('theme/js/colorbox-1.4.15/example1/colorbox.css');
                }
            },
            'jqueryui': {
                deps: ['jquery'],
                init: function(){
                    include.css('theme/js/jquery-ui-1.10.3.custom/css/smoothness/jquery-ui-1.10.3.custom.min.css');
                }
            },
            'jquery.contextMenu': {
                deps: ['jquery', 'contextMenu/src/jquery.ui.position'],
                exports: 'jQuery.contextMenu',
                init: function(){
                    include.css('theme/js/contextMenu/src/jquery.contextMenu.css');
                }
            },
            'jquery.colorpicker': {
                deps: ['jquery', 'jqueryui'],
                exports: 'jQuery.colorpicker',
                init: function(){
                    include.css('theme/js/colorpicker/jquery.colorpicker.css');
                }
            }
        }
    //    urlArgs: 'v=1.0' // handy, but commented out as it loads jQuery twice
    });

    // jQuery should use existing implementations
    if (window.jQuery)  define('jquery', [], function() { return window.jQuery; });
})();
