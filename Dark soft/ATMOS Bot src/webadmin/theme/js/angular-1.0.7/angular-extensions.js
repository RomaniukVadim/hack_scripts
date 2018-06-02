define(['angular', 'jquery', 'underscore'], function(angular, $, _){
    'use strict';

    //region Functions

    /** Display Date as timeago string
     * @param {Date} date
     *      The date to use
     * @param {Boolean} p_period
     *      Period mode
     * @returns {*}
     */
    var timeago = function(date, p_period) {
        var pot = {
            en: {
                0: 'sec', 1: 'min', 2: 'h',
                3: 'days', 4: 'months', 5: 'years', 6: 'cent', 7: 'eras',
                ago: 'ago',
                future: 'from now',
                now: 'just now',
                instantly: 'instantly',
                never: 'never'
            },
            ru: {
                0: 'сек', 1: 'мин', 2: 'ч',
                3: 'дн', 4: 'мес', 5: 'лет', 6: 'столетей', 7: 'эр',
                ago: 'назад',
                future: 'в будущем',
                now: 'только что',
                instantly: 'мгновенно',
                never: 'никогда'
            }
        };
        var l = pot['en']; // TODO: i18n!

        // Never?
        if (!date) return l.never;

        // Get delta
        var now = new Date();
        var seconds = Math.round((now.getTime() - date.getTime())/1000);
        var negative = seconds < 0;
        if (negative)
            seconds *= 1;

        // Units
        var K = [1, 60, 60, 24, 30, 12, 365, 100, 1000];

        // Calc
        var d = [seconds];
        for (var i = 1; i<K.length; i++){
            d[i] = Math.floor(d[i-1]/K[i]);
            d[i-1] -= d[i]*K[i];
        }

        // Output
        var ago = negative ? l.future : l.ago;
        if (p_period) ago = '';

        // Much time left
        for (var i = K.length - 1; i>0; i--)
            if (d[i] > 2 || (d[i]>0 && d[i-1]==0))
                return d[i] + ' ' + l[i] + ' ' + ago;
            else if (d[i] > 0)
                return d[i]+ ' '+ l[i]+ ' '+ d[i-1]+ ' '+ l[i-1]+ ' '+ ago;

        // Seconds left
        if (d[0] > 5)
            return d[0] + ' ' + l[0] + ' '+ ago;

        // Instantly
        if (p_period)
            return l.instantly;
        return l.now;
    };
    //endregion



    //region Filters
    var filters = angular.module('Extensions.filters', []);

    /** Timeago filter: render Date as timeago
     */
    filters.filter('timeago', function() {
        return timeago;
        });

    /** Interactive timeago filter: render Date as timeago and update it interactively
     */
    filters.filter('itimeago', function() {
        /** Timeago objects registry
         * [$scope.id] = { $scope: $scope, dates: {'DATEID': Date} }
         * @type Object.<String, {{$scope: Object, dates: Object.<String, Date>}}>
         */
        var registry = {};

        /** Generate an identifier for a timeago
         * @param date
         * @returns {string}
         */
        var timeagoid = function(date){
//            return '(((TIMEAGO:' + date.getTime() + ')))';
            return timeago(date);
        };

        /** Collect all child text nodes
         * @param {textNode} root
         * @param {function(node:textNode):Boolean} filter
         * @returns {Array}
         */
        var textNodesUnder = function(root, filter){
            var n, a=[], walk=document.createTreeWalker(root,NodeFilter.SHOW_TEXT,null,false);
            while(n=walk.nextNode())
                if (!filter || filter(n))
                    a.push(n);
            return a;
        };

        // Launch a timer which updates these
        var timeagoUpdater = function(){
            // Update map
            // As we can potentially find items in a sub-scope, we collect the changes and then update them in bulk
            var updates = [];

            // Build scope map
            $('.ng-scope').each(function(){
                var $root = $(this); // scope root
                var scope_id = $root.scope().$id;

                // Skip scopes with no intresting strings
                if (!registry[scope_id]) return;
                var reg = registry[scope_id];

                // Reset the list of dates
                var dates = reg.dates;
                reg.dates = [];

                // Now walk down and search all text nodes for anything that's like TimeAgo strings
                textNodesUnder(this, function(node){
                    // Update the node if we know it
                    var date = dates[node.nodeValue];
                    if (!date) return false; // skip unknown text

                    // Schedule an update
                    var value = timeago(date);
                    updates.push([node, value]);

                    // Store the new string
                    reg.dates[value] = date;

                    // Finish
                    return true;
                });
            });

            // Now fire-off the updates
            for (var i = 0; i<updates.length; i++)
                updates[i][0].nodeValue = updates[i][1];
        };
        var timeagoTimer = setInterval(timeagoUpdater, 10*1000);

        return function(date){
            var dateid = timeagoid(date);

            // Add it to the registry
            if (!registry[this.$id])
                registry[this.$id] = { $scope: this, dates: {} };
            registry[this.$id].dates[dateid] = date;

            return dateid;
        };
    });

    /** Convert an object to array
     */
    filters.filter('toArray', function() {
        return function(obj) {
            if (!(obj instanceof Object))
                return obj;
            return _.map(obj, function(val, key) {
                val.$key = key;
                return val;
            });
        }
    });

    /** Reverse an array
     */
    filters.filter('reverse', function() {
        return function(items) {
            return items.slice().reverse();
        };
    });
    //endregion

    //region Directives:WYSIWYG
    var directives_wysiwyg = angular.module('Extensions.directives.wysiwyg', []);

    /** Two-way data-binding to contentEditable elements
     * @example <div contenteditable="sync"></div>
     */
    directives_wysiwyg.directive('contenteditable', function() {
        return {
            require: 'ngModel',
            link: function(scope, elm, attrs, ctrl) {
                if (!ctrl) return;
                if (attrs.contenteditable !== 'sync') return; // do nothing

                // view -> model
                var updh = _.debounce(function(e) {
                    scope.$apply(function() {
                        ctrl.$setViewValue(elm.html());
                    });
                }, 300);
                elm.on('blur keyup paste input change', updh);
                elm.on('blur keyup paste input change', '*', updh);

                // model -> view
                ctrl.$render = function() {
                    elm.html(ctrl.$viewValue);
                };

                // load the initial value from DOM
//                ctrl.$setViewValue(elm.html());
            }
        };
    });

    /** Bind CKEditor changes to scope
     * @param {CKEDITOR} ck
     * @param {Scope} scope
     * @param {jQuery} elm
     * @param {Attributes} attrs
     * @param {NgModelController} ctrl
     */
    var angular_ckeditor = function(ck, scope, elm, attrs, ctrl){
        if (!ctrl) return;

        ck.on('instanceReady', function() {
            ck.setData(ctrl.$viewValue);
        });

        ck.on('pasteState', function() {
            scope.$apply(function() {
                ctrl.$setViewValue(ck.getData());
            });
        });

        ctrl.$render = function(value) {
            ck.setData(ctrl.$viewValue);
        };
    };

    /** Make the current element editable with CKEditor editor (should be preloaded)
     * Requires: ckeditor
     * @see <http://stackoverflow.com/questions/11997246/bind-ckeditor-value-to-model-text-in-angularjs-and-rails>
     * @example <textarea ng-model="page.data.text" ckeditor></textarea>
     */
    directives_wysiwyg.directive('ckeditor', function() {
        return {
            require: 'ngModel',
            restrict: 'AC',
            link: function(scope, elm, attrs, ctrl) {
                var ck = CKEDITOR.replace(elm[0]);
                angular_ckeditor(ck, scope, elm, attrs, ctrl);
            }
        };
    });

    /** Make the current element editable inline with CKEditor editor (should be preloaded)
     * Requires: ckeditor
     * @see <http://stackoverflow.com/questions/11997246/bind-ckeditor-value-to-model-text-in-angularjs-and-rails>
     * @example <div ng-model="page.data.text" contenteditable ckeditor-inline></div>
     */
    directives_wysiwyg.directive('ckeditorInline', function() {
        return {
            require: 'ngModel',
            restrict: 'AC',
            link: function(scope, elm, attrs, ctrl) {
//                var ck_config = {};
//                if (attrs.ckeditorInline) // TODO: receive {expression} that gives the current config

                var ck = CKEDITOR.inline(elm[0]);
                angular_ckeditor(ck, scope, elm, attrs, ctrl);
            }
        };
    });
    //endregion

    //region Directives
    var directives = angular.module('Extensions.directives', ['Extensions.directives.wysiwyg']);

    /** preventDefault() on events
     * @example  <a href="#" ng-click="editRule();" eat-event="click"></a>
     */
    directives.directive('eatEvent', function(){
        return {
            link: function(scope, elm, attrs, ctrl){
                var name = attrs.eatEvent || 'click';
                elm.on(name, function(e){
                    e.preventDefault();
                });
            }
        };
    });

    /** like ngList, but allows to use custom strings
     * <textarea ng-model="pattern.post" ng-list-ex="'\n'"></textarea>
     */
    directives.directive('ngListEx', function(){
        return {
            require: 'ngModel',
            link: function(scope, elm, attrs, ctrl) {
                var separator = scope.$eval(attrs.ngListEx) || ',';

                var parse = function(viewValue) {
//                    return _.invoke(viewValue.split(separator), 'trim');
                    viewValue = viewValue.trim();
                    if (!viewValue.length) return []; // otherwise we get [""]
                    var val = viewValue.split(separator);
                    for (var i=0; i<val.length; i++)
                        val[i] = val[i].trim();
                    return val;
                };

                ctrl.$parsers.push(parse);
                ctrl.$formatters.push(function(value) {
                    if (angular.isArray(value))
                        return value.join(separator);
                    return undefined;
                });
            }
        };
    });

    /** jQuery.contextMenu
     * @example <TBODY context-menu="cMenus.rule">
     */
    directives.directive('contextMenu', function(){
        return {
            link: function(scope, elm, attrs, ctrl){
                var options = scope.$eval(attrs.contextMenu);
                elm.contextMenu(options);
            }
        };
    });
    //endregion

    // Depend the module on all others
    return angular.module('Extensions', ['Extensions.filters', 'Extensions.directives']);
});
