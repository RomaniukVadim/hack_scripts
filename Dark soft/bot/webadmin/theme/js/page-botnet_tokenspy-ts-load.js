'use strict';

require.config({
    paths: {
        'socket.io': window.global.nodejs.socketio.replace(/\.js$/, '')
    },
    shim: {
        'socket.io': { exports: 'io' }
    }
});


requirejs.onError = function (err) {
    // Log
    console.log(err.requireType);
    if (err.requireType === 'timeout')
        console.log('modules: ' + err.requireModules);

    // Notify
    if (err.requireModules == 'socket.io')
        $.jlog('err', 'NodeJS connection failed!');
    else
        $.jlog('err', 'Failed to load modules: '+ err.requireModules +'!');

    // Effect
    $('#tokenspy-app-loading').addClass('err');
    throw err;
};






(function(){
    // Loader
    var started = new Date();
    $('#tokenspy-app-loading').fadeIn(1000);

    // Reference project that uses Angular: https://github.com/elsom25/angular-requirejs-html5boilerplate-seed/tree/master/app

    require(['page-botnet_tokenspy-ts'], function(App){
        // Init app
        var app = App.initialize();

        // Init Angular
        var node = document.getElementById('tokenSpy');
        angular.bootstrap(node, ['TokenSpy']);

        // Fade after >1sec of wait
        var finished = new Date();
        var sleep = Math.max(1000 - (finished.getTime() - started.getTime()), 0);
        setTimeout(function(){
            $('#tokenspy-app-loading')
                .addClass('ok')
                .fadeOut(2000).hide(2000);
            $('#tokenSpy').show(1000);
        }, sleep);
    });
})();
