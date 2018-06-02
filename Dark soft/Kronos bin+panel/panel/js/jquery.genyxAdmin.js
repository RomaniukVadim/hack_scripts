/* 
  Author: SuggeElson
    Site: www.suggeelson.com
    Plugin: genyxAdmin
    Version: 1.1.0
    This plugin is created special for themeforest admin template - Genyx Admin
    http://themeforest.net/item/genyx-responsive-admin-template/4874839?ref=SuggeElson
    ---------------------------------------------------------------------------
    ChangeLog: First release
    1.1.0 - Added new option fixedWidth for fixed version of template
*/

;(function($) {	
    // Using it without an object
    $.genyxAdmin = function(options) { return $.fn.genyxAdmin(options); };
    
    $.fn.genyxAdmin = function(options) { 
        // plugin default options
        var settings = { 
            fixedWidth: false,// make true if you want to use fixed widht instead of fluid version.
            customScroll: false,
            responsiveTablesCustomScroll: false,
            backToTop: true,//show back to top
            navigation: {
                useNavMore: true,
                navMoreIconDown: 'i-arrow-down-2',
                navMoreIconUp: 'i-arrow-up-2',
                rotateIcon: true//rotate icon on hover
            },
            setCurrent: {
                absoluteUrl: false, //put true if use absolute path links. example http://www.host.com/dashboard instead of /dashboard
                subDir: '' //if you put template in sub dir you need to fill here. example '/html'
            },
            collapseNavIcon: 'i-arrow-left-7',
            collapseNavRestoreIcon: 'i-arrow-right-8',
            rememberNavState: true, //remember if menu is collapsed or hided
            remeberExpandedSub: false, //remeber expanded sub menu by user
            hoverDropDown: true, //set false if not want to show dropdown on hover ( click instead)
            accordionIconShow: 'i-arrow-down-2',
            accordionIconHide: 'i-arrow-up-2',
            debug: false,
            showThemer: false
        }; 	

        // extends settings with provided options
        if (options) {
            $.extend(settings, options);
        }    

        var _this;
        var navMoreDown = '<span class="more"><i class="icon16 ' + settings.navigation.navMoreIconDown + '"></i></span>';
        var navMoreUp = '<span class="more"><i class="icon16 ' + settings.navigation.navMoreIconUp + '"></i></span>';
        var collapseNavIcon = '<i class="icon16 ' + settings.collapseNavIcon + '"></i>';
        var collapseNavRestoreIcon = '<i class="icon16 ' + settings.collapseNavRestoreIcon + '"></i>';
        var accordionIconShow = '<i class="icon16 ' + settings.accordionIconShow + '"></i>';
        var accordionIconHide = '<i class="icon16 ' + settings.accordionIconHide + '"></i>';

        _this = this; 

        //themer
        if(settings.showThemer) {
            createThemer(localStorage.getItem('fixedWidth'));
            $('#themerBtn').click(function() {
                if($(this).hasClass('open')) {
                    //close themer
                    $('#themer').css('right', '-200px');
                    $(this).removeClass('open');
                } else {
                    //open themer
                    $('#themer').css('right', '0px');
                    $(this).addClass('open');
                }
            });
            if(localStorage.getItem('fixedWidth') == 1) {
                makeFixedWidth();
            } else {
                removeFixedWidth();
            }
            //listen for options 
            $('#fixedWidth').bind('change', function () { 
                if ($(this).is(':checked')) {
                    makeFixedWidth();
                    localStorage.setItem('fixedWidth', 1);
                } else {
                    removeFixedWidth();
                    localStorage.setItem('fixedWidth', 0);
                }
            });
        }

        //fixed width
        if(settings.fixedWidth) {
            $('html').addClass('fixedWidth');
            $('body').addClass('container');
            $('#header>.navbar-inner>.container-fluid').removeClass('container-fluid').addClass('container');
            $('.main').prepend('<div id="sidebarbg">');
        }

        //use custom scroll
        if(settings.customScroll){
            
            $("body").niceScroll({
                cursoropacitymax: 0.8,
                cursorborderradius: 0,
                cursorwidth: "15px"
            });
            
        }

        //hoverdropdown
        if(settings.hoverDropDown) {
            hoverDropDown(500);
        }

        //back to top
        if(settings.backToTop) {
            //check if exist
            if(!$('#back-to-top').length) {
                //create back to top
                $('body').append('<div id="back-to-top"><a href="#">Back to Top</a></div>');
            }
            backToTop();
        }

        //debug turn of in production site
        if(settings.debug) {
            //Window scroll events
            $(window).scroll(function() {
                scroll = $(window).scrollTop();
                windowHeight = $(window).height();
            });

            //window resize events
            $(window).resize(function() {
                var windowWidth = $(window).width();
                var windowHeight = $(window).height();
                //get the window size
                var size ="Window size is:" + windowWidth + " height:" + windowHeight;
                console.log(size);
            });
        }

        //mainnavigation function
        function mainNav () {
             //cache the elements
            var nav = $('#mainnav>ul');
            var navCurrent = nav.find('li.current');
            var navLink = nav.find('a');
            var navSub = nav.find('li>ul.sub');

            //add more icon
            if(settings.navigation.useNavMore) {
                navSub.prev('a').append(navMoreDown);
            }
            //put hasSub class
            navSub.closest('li').addClass('hasSub');
            //put notExpand class
            navSub.prev('a').addClass('notExpand');

            if(settings.rememberNavState) {
                //remember nav is activated 
                if(localStorage.getItem("collapseNav") == 1) {
                    //nav is collapsed
                    $("#collapse-nav").toggleClass('collapse');
                    $("#collapse-nav").text("");
                    $("#collapse-nav").append(collapseNavRestoreIcon);
                    $('#sidebar').toggleClass('isCollapse');
                    $('.sidebar-widget').toggleClass('hided');
                    $('#content').toggleClass('isCollapse');
                    if(settings.fixedWidth || localStorage.getItem('fixedWidth') == 1) {
                        $('.main').toggleClass('isCollapse');
                    }
                }
            }

            navLink.hover(
                function () {
                    if($(this).closest('li').hasClass('isCollapse')) {
                        var $this = $(this);
                        $(this).addClass('showUp');
                        $this.find('span.txt').addClass('showUp');
                    }  
                    //in
                    if(settings.navigation.rotateIcon) {
                        $(this).addClass('rotateIn').removeClass('rotateOut');
                    }		
                },
                function () {
                    //out
                    if($(this).closest('li').hasClass('isCollapse')) {
                        if($(this).hasClass('expand')) {
                            $(this).removeClass('showUp');
                        } else {
                            $(this).removeClass('showUp');
                        }
                        $(this).find('span.txt').removeClass('showUp');    
                    }  
                    if(settings.navigation.rotateIcon) {
                        $(this).addClass('rotateOut').removeClass('rotateIn');
                    }                   
                }
            );

            navLink.on("click", function(){
                var _this = $(this);
                if(_this.hasClass('notExpand')) {
                    //expand ul and change class to expand
                    _this.next('ul').slideDown(250);
                    _this.next('ul').addClass('show');
                    _this.addClass('expand').removeClass('notExpand');

                    if(settings.navigation.useNavMore) {
                        _this.find('span.more').remove();
                        _this.append(navMoreUp);
                    }

                } else if (_this.hasClass('expand')) {
                    //collapse ul and change class to notExpand
                    _this.next('ul').removeClass('show');
                    _this.next('ul').slideUp(250);
                    _this.addClass('notExpand').removeClass('expand');

                    if(settings.navigation.useNavMore) {
                        _this.find('span.more').remove();
                        _this.append(navMoreDown);
                    }

                }
            });


       }// end mainnavigation function

       //set current nav element
       function setCurrentNav () {
            var domain = document.domain;
            var navig = $('#mainnav>ul');
            var navLinks = navig.find('a');
           //var absoluteUrl = settings.setCurrent.absoluteUrl
           //var subdir = settings.setCurrent.subDir
            if(domain === '') {
                //domain not found
                var pageUrl = window.location.pathname.split( '/' );
                var winLoc = pageUrl.pop(); // get last item
                setCurrentClass(navLinks, winLoc);

            } else {
                if(settings.setCurrent.absoluteUrl) {
                    //absolute url is enabled
                    var newDomain = 'http://' + domain + window.location.pathname;
                    setCurrentClass(navLinks, newDomain);
                
                } else {
                    //absolute url is disabled
                    var afterDomain = window.location.pathname.split( '/' );
                    var afterDomain = afterDomain.pop();
                    
                    if(settings.setCurrent.subDir != ''){
                        var afterDomain = window.location.pathname + settings.setCurrent.subDir;
                    }
                    setCurrentClass(navLinks, afterDomain);
                }
            }
       }

        function setCurrentClass(mainNavLinkAll, url) {
            mainNavLinkAll.each(function(index) {
                //convert href to array and get last element
                var href= $(this).attr('href');
                if(href === url) {
                    //set new current class
                    $(this).closest('li').addClass('current')

                    ulElem = $(this).closest('ul');
                    if(ulElem.hasClass('sub')) {
                        //its a part of sub menu need to expand this menu
                        aElem = ulElem.prev('a.hasUl').addClass('drop');
                        ulElem.addClass('expand');
                        ulElem.addClass('show');
                        var _this = $(this).closest('li.hasSub').children('a.notExpand');
                        _this.removeClass('notExpand').addClass('expand');
                        _this.closest('li.hasSub').addClass('current');
                        
                        if(settings.navigation.useNavMore) {
                            _this.find('span.more').remove();
                            _this.append(navMoreUp);
                        }
                    } 
                } else {
                    //console.log('not found')
                }
            });
        }
       
        function collapseNav () {
            $("#collapse-nav").on("click", function(){
                _this = $(this);
                _this.toggleClass('collapse');

                if(_this.hasClass('collapse')) {
                    _this.text("");
                    _this.append(collapseNavRestoreIcon);
                } else {
                    _this.text("");
                    _this.append(collapseNavIcon);
                }
                        
                $('#sidebar').toggleClass('isCollapse');
                $('#content').toggleClass('isCollapse');
                $('.sidebar-widget').toggleClass('hided');

                if(settings.fixedWidth || localStorage.getItem('fixedWidth') ==1) {
                    $('.main').toggleClass('isCollapse');
                }
                
                if(settings.rememberNavState) {
                    //remember nav state
                    var collapse = localStorage.getItem("collapseNav");
                    if(collapse == 1) {
                        //nav is collapsed
                        localStorage.setItem("collapseNav", 0);
                    } else {
                        //nav is not collapsed
                        localStorage.setItem("collapseNav", 1);
                    }
                }
            });
        }

        function widgetBox () {
            var widget = $('.widget');
            var w_minimize = widget.find('.widget-title a.minimize');
            var widgetClosed = $('.widget.closed');
            var widgetDrag = $('.widget.drag');

            //Drag widget
            if(widgetDrag) {
                widgetDrag.find('.widget-title').addClass('drag');
                dragWidgets();
            }
            
            //close widget
            widgetClosed.each(function(index) {
               $(this).find('.widget-title a.minimize').removeClass('minimize').addClass('maximize');
               wcontent = $(this).find('div.widget-content');
               wcontent.removeClass('slideDown').addClass('slideUp');
            });
            
            w_minimize.on('click', function() {
                _this = $(this);
                if($(this).hasClass('minimize')) {
                    $(this).removeClass('minimize').addClass('maximize').closest('div.widget-title').next('div.widget-content').slideUp('200')
                } else {
                    $(this).removeClass('maximize').addClass('minimize').closest('div.widget-title').next('div.widget-content').slideDown('200');
                }
            }); 
        }

        function dragWidgets() {
            //check if #sort exist
            if($('#sort').length) {

                $('#sort div[class*="span"]').sortable({
                    connectWith: '#sort div[class*="span"]',
                    handle: '.widget-title.drag',
                    placeholder: "sortable-placeholder",
                    forcePlaceholderSize: true,
                    helper: 'original',
                    forceHelperSize: true,
                    cursor: "move",
                    opacity: 0.8,
                    tolerance: "pointer"
                });
                
            }
        }

        //check all checkboxes in table
        function checkAll () {
            //add class .checkAll to container who hold all checkboxes
            $('.master-check').on('click', function () {
                $(this).closest('.checkAll').find('input:checkbox').prop('checked', this.checked).closest('.checker>span').toggleClass('checked')
            });

            $('.select-all').on('click', function () {
               $(this).closest('.widget-content').find('.checkAll').find('input:checkbox').prop('checked', this.checked).closest('.checker>span').addClass('checked');
            });

            $('.deselect-all').on('click', function () {
                $(this).closest('.widget-content').find('.checkAll').find('input:checkbox').prop('checked', this.checked).closest('.checker>span').removeClass('checked');
            });

        }

        //prevent font flickering in some browsers 
        function fontFlicker () {
            //if firefox 3.5+, hide content till load (or 3 seconds) to prevent FOUT
            var d = document, e = d.documentElement, s = d.createElement('style');
            if (e.style.MozTransform === ''){ // gecko 1.9.1 inference
              s.textContent = 'body{visibility:hidden}';
              e.firstChild.appendChild(s);
              function f(){ s.parentNode && s.parentNode.removeChild(s); }
              addEventListener('load',f,false);
              setTimeout(f,3000); 
            }
        }

        //hover dropdown instead of click
        function hoverDropDown(val) {
            (function(e,t,n){e('<span class="visible-desktop" style="font-size:1px !important" id="vis">.</span>').appendTo("body");var r=function(){return e("#vis").is(":visible")},i=e();e.fn.dropdownHover=function(n){i=i.add(this.parent());return this.each(function(){var s=e(this).parent(),o={delay:500,instantlyCloseOthers:!0},u={delay:e(this).data("delay"),instantlyCloseOthers:e(this).data("close-others")},a=e.extend(!0,{},o,n,u),f;s.hover(function(){if(r()){a.instantlyCloseOthers===!0&&i.removeClass("open");
            t.clearTimeout(f);e(this).addClass("open")}},function(){r()&&(f=t.setTimeout(function(){s.removeClass("open")},a.delay))})})};e(document).ready(function(){e('[data-hover="dropdown"]').dropdownHover()})})(jQuery,this);$(".dropdown-toggle").dropdownHover({delay:val});
        }

        //fix orientation change
        function orientationFix () {
            (function(a){function j(){c.setAttribute("content",f),document.body.innerHTML=document.body.innerHTML,g=!0}function k(){c.setAttribute("content",e),g=!1}function l(b){h=Math.abs(a.orientation),i=Math.abs(b.accelerationIncludingGravity.x),i>8&&h===0?g&&k():g||j()}var b=a.document;if(!b.querySelectorAll)return;var c=b.querySelectorAll("meta[name=viewport]")[0],d=c&&c.getAttribute("content"),e=d+", maximum-scale=1.0",f=d+", maximum-scale=10.0",g=!0,h=a.orientation,i=0;if(!c)return;a.addEventListener("orientationchange",j,!1),a.addEventListener("devicemotion",l,!1)})(this);
        }

        //put icons in accordion
        function accordionIcon () {
            var acc = $('.accordion'); //get all accordions
            var accHeading = acc.find('.accordion-heading');
            var accBody = acc.find('.accordion-body');

            //put icon function
            accPutIcon = function () {
                acc.each(function(index) {
                   accExp = $(this).find('.accordion-body.in');
                   accExp.prev().find('a.accordion-toggle').append(accordionIconHide);
                   accNor = $(this).find('.accordion-body').not('.accordion-body.in');
                   accNor.prev().find('a.accordion-toggle').append(accordionIconShow);
                });
            }

            //function to update icons
            accUpdIcon = function() {
                acc.each(function(index) {
                   accExp = $(this).find('.accordion-body.in');
                   accExp.prev().find('i').remove();
                   accExp.prev().find('a.accordion-toggle').append(accordionIconHide);
                   accNor = $(this).find('.accordion-body').not('.accordion-body.in');
                   accNor.prev().find('i').remove();
                   accNor.prev().find('a.accordion-toggle').append(accordionIconShow);
                });
            }

            //put icons
            accPutIcon();
            //listen for change
            $('.accordion').on('shown', function () {
                accUpdIcon();
            }).on('hidden', function () {
                accUpdIcon();
            })
        }

        //Back to top
        function backToTop () {
            $(window).scroll(function(){
                if($(window).scrollTop() > 200){
                    $("#back-to-top").fadeIn(200);
                } else{
                    $("#back-to-top").fadeOut(200);
                }
            });
            
            $('#back-to-top, .back-to-top').click(function() {
                  $('html, body').animate({ scrollTop:0 }, '800');
                  return false;
            });
        }

        //Jrespond functions
        function respond() {

            var jRes = jRespond([
                {
                    label: 'smart',
                    enter: 0,
                    exit: 684
                },
                {
                    label: 'small',
                    enter: 0,
                    exit: 767
                },{
                    label: 'tablet',
                    enter: 768,
                    exit: 1024
                },{
                    label: 'laptop',
                    enter: 1025,
                    exit: 1280
                },{
                    label: 'desktop',
                    enter: 1281,
                    exit: 10000
                }
            ]);

            jRes.addFunc({
                breakpoint: 'desktop',
                enter: function() {
                   resBtnDestroy();
                   resBtnSearchRestore();
                   offCanvasMenuOff();
                   $('.chart-pie-social').css('width', '50%');
                   $('#content').removeClass('offCanvas');
                   $("#collapse-nav").removeClass('hided');
                   $("#top-search").removeClass('shown');
                },
                exit: function() {
                    
                }
            });
            jRes.addFunc({
                breakpoint: 'laptop',
                enter: function() {
                   $('.chart-pie-social').css('width', '100%');
                   resBtnDestroy();
                   resBtnSearchRestore();
                   offCanvasMenuOff();
                   $('#content').removeClass('offCanvas');
                   $("#collapse-nav").removeClass('hided');
                   $("#top-search").removeClass('shown');
                },
                exit: function() {
                    $('.chart-pie-social').css('width', '50%');
                }
            });
            jRes.addFunc({
                breakpoint: 'tablet',
                enter: function() {
                   $('.chart-pie-social').css('width', '100%');
                   resBtn();
                   resBtnSearch();
                   offCanvasMenuOn();
                },
                exit: function() {
                    resBtnDestroy();
                    resBtnSearchRestore();
                    offCanvasMenuOff();
                }
            });
            jRes.addFunc({
                breakpoint: 'small',
                enter: function() {
                    resBtn();
                    resBtnSearch();
                    offCanvasMenuOn();
                    responsiveTables();
                },
                exit: function() {
                    resBtnDestroy();
                    resBtnSearchRestore();
                    offCanvasMenuOff();
                    responsiveTablesOff();
                    restoreTooltip();
                }
            });

            jRes.addFunc({
                breakpoint: 'smart',
                enter: function() {
                    changeTooltip();
                    resBtn();
                    resBtnSearch();
                    offCanvasMenuOn();
                    responsiveTables();
                    $('.chart-pie-social').css('width', '100%');
                },
                exit: function() {
                    $('.chart-pie-social').css('width', '50%');
                    resBtnDestroy();
                    resBtnSearchRestore();
                    offCanvasMenuOff();
                    responsiveTablesOff();
                    restoreTooltip();
                }
            });

        }
        //replace some tooltip direction in login page
        function changeTooltip () {
            $('#bar .tipR').each(function( index ) {
                $(this).addClass('tip').removeClass('tipR');
            });
        }
        //restore tooltip direction
        function restoreTooltip () {
            $('#bar .tip').each(function( index ) {
                $(this).addClass('tipR').removeClass('tip');
            });
        }

        //create responsive search button
        function resBtnSearch () {
            $('#top-search').addClass('hide');
            $('#header .nav-no-collapse').append('<a href="#" id="resBtnSearch" class="btn btn-danger"><i class="icon16 i-search-3"></i></a>');
            resBtnSearchClick();
        }
        //Destroy responsive search button
        function resBtnSearchRestore () {
            $('#header #resBtnSearch').remove();
            $('#top-search').removeClass('hide');
        }

        //create offcanvas menu button
        function resBtn () {
            $('#header .nav-no-collapse').append('<a href="#" id="resBtn" class="btn btn-danger"><i class="icon16 i-menu-6"></i></a>');
            resBtnClick();
        }
        //destroy responsive button
        function resBtnDestroy () {
            $('#header #resBtn').remove();
        }

        //hide sidbear and pull left content
        function offCanvasMenuOn() {
            $('#sidebar').addClass('hided');
            $('#content').addClass('hided');
            if(settings.fixedWidth){
                $('#sidebarbg').addClass('hided');
            }
        }
        //restore sidebar and margin content
        function offCanvasMenuOff() {
            $('#sidebar').removeClass('hided');
            $('#content').removeClass('hided');
            if(settings.fixedWidth){
                $('#sidebarbg').removeClass('hided');
            }
        }
        //handle the resBtnClick
        function resBtnClick () {
            $("#resBtn").on("click", function(){
                $("#content").toggleClass('hided offCanvas');
                $("#sidebar").toggleClass('hided');
                $("#collapse-nav").toggleClass('hided');
                if(settings.fixedWidth){
                    $('#sidebarbg').toggleClass('hided');
                }
            });
        }
        //handle the resBtnSearch click
        function resBtnSearchClick () {
            $("#resBtnSearch").on("click", function(){
                $("#top-search").toggleClass('hide shown');
            });
        }
        //responsive tables
        function responsiveTables() {
            var tables = $('.table');
            tables.each(function( index ) {
                $(this).wrap('<div class="responsive" />');
            });
            if(settings.responsiveTablesCustomScroll) {
                $('.responsive').niceScroll({
                    cursoropacitymax: 0.8,
                    cursorborderradius: 0,
                    cursorwidth: "7px"
                });
            }
        }
        //destroy responsive tables
        function responsiveTablesOff () {
            var tables = $('.table');
            tables.each(function( index ) {
                $(this).unwrap();
            });
        }

        //IE placeholder fallback
        function IEplaceholder () {
            /*! http://mths.be/placeholder v2.0.7 by @mathias */
            ;(function(f,h,$){var a='placeholder' in h.createElement('input'),d='placeholder' in h.createElement('textarea'),i=$.fn,c=$.valHooks,k,j;if(a&&d){j=i.placeholder=function(){return this};j.input=j.textarea=true}else{j=i.placeholder=function(){var l=this;l.filter((a?'textarea':':input')+'[placeholder]').not('.placeholder').bind({'focus.placeholder':b,'blur.placeholder':e}).data('placeholder-enabled',true).trigger('blur.placeholder');return l};j.input=a;j.textarea=d;k={get:function(m){var l=$(m);return l.data('placeholder-enabled')&&l.hasClass('placeholder')?'':m.value},set:function(m,n){var l=$(m);if(!l.data('placeholder-enabled')){return m.value=n}if(n==''){m.value=n;if(m!=h.activeElement){e.call(m)}}else{if(l.hasClass('placeholder')){b.call(m,true,n)||(m.value=n)}else{m.value=n}}return l}};a||(c.input=k);d||(c.textarea=k);$(function(){$(h).delegate('form','submit.placeholder',function(){var l=$('.placeholder',this).each(b);setTimeout(function(){l.each(e)},10)})});$(f).bind('beforeunload.placeholder',function(){$('.placeholder').each(function(){this.value=''})})}function g(m){var l={},n=/^jQuery\d+$/;$.each(m.attributes,function(p,o){if(o.specified&&!n.test(o.name)){l[o.name]=o.value}});return l}function b(m,n){var l=this,o=$(l);if(l.value==o.attr('placeholder')&&o.hasClass('placeholder')){if(o.data('placeholder-password')){o=o.hide().next().show().attr('id',o.removeAttr('id').data('placeholder-id'));if(m===true){return o[0].value=n}o.focus()}else{l.value='';o.removeClass('placeholder');l==h.activeElement&&l.select()}}}function e(){var q,l=this,p=$(l),m=p,o=this.id;if(l.value==''){if(l.type=='password'){if(!p.data('placeholder-textinput')){try{q=p.clone().attr({type:'text'})}catch(n){q=$('<input>').attr($.extend(g(this),{type:'text'}))}q.removeAttr('name').data({'placeholder-password':true,'placeholder-id':o}).bind('focus.placeholder',b);p.data({'placeholder-textinput':q,'placeholder-id':o}).before(q)}p=p.removeAttr('id').hide().prev().attr('id',o).show()}p.addClass('placeholder');p[0].value=p.attr('placeholder')}else{p.removeClass('placeholder')}}}(this,document,jQuery));
            $('input, textarea').placeholder();
        }

        //Retina support
        function retina() {
            // retina.js, a high-resolution image swapper (http://retinajs.com), v0.0.2
            (function(){function t(e){this.path=e;var t=this.path.split("."),n=t.slice(0,t.length-1).join("."),r=t[t.length-1];this.at_2x_path=n+"@2x."+r}function n(e){this.el=e,this.path=new t(this.el.getAttribute("src"));var n=this;this.path.check_2x_variant(function(e){e&&n.swap()})}var e=typeof exports=="undefined"?window:exports;e.RetinaImagePath=t,t.confirmed_paths=[],t.prototype.is_external=function(){return!!this.path.match(/^https?\:/i)&&!this.path.match("//"+document.domain)},t.prototype.check_2x_variant=function(e){var n,r=this;if(this.is_external())return e(!1);if(this.at_2x_path in t.confirmed_paths)return e(!0);n=new XMLHttpRequest,n.open("HEAD",this.at_2x_path),n.onreadystatechange=function(){return n.readyState!=4?e(!1):n.status>=200&&n.status<=399?(t.confirmed_paths.push(r.at_2x_path),e(!0)):e(!1)},n.send()},e.RetinaImage=n,n.prototype.swap=function(e){function n(){t.el.complete?(t.el.setAttribute("width",t.el.offsetWidth),t.el.setAttribute("height",t.el.offsetHeight),t.el.setAttribute("src",e)):setTimeout(n,5)}typeof e=="undefined"&&(e=this.path.at_2x_path);var t=this;n()},e.devicePixelRatio>1&&(window.onload=function(){var e=document.getElementsByTagName("img"),t=[],r,i;for(r=0;r<e.length;r++)i=e[r],t.push(new n(i))})})();
        }

        function last_child() {
           $(".nav-tabs > li:last-child").addClass('last-child');
           $(".recent-activity li:last-child").addClass('last-child');
        }

        //function to create themer
        function createThemer(storage) {
            $('body').append('<div id="themer" style="width:200px; border: 1px solid #000; background: url(../images/patterns/low_contrast_linen.png) repeat; padding:10px; border-radius:1px; position:fixed;top:70px;right:-200px;z-index:99999;color:#fff;">');
            $('#themer').append('<h3>Theme options</h3>');
            $('#themer').append('<ul class="unstyled">');
            if(storage == 1) {
                $('#themer ul.unstyled').append('<li><label class="checkbox inline"><input type="checkbox" name="fixed" id="fixedWidth" checked> Fixed version</label></li>');
            } else {
                $('#themer ul.unstyled').append('<li><label class="checkbox inline"><input type="checkbox" name="fixed" id="fixedWidth"> Fixed version</label></li>');
            }
            $('#themer').append('<a href="#" id="themerBtn" class="white"><i class="icon20 i-cog-2" style="margin-left:-50px;background: url(../images/patterns/low_contrast_linen.png) repeat;padding:8px 10px;border:1px solid #000;border-right:0;float:left;margin-top:-55px;"></i></a>');
        }

        function makeFixedWidth() {
            $('html').addClass('fixedWidth');
            $('body').addClass('container');
            $('#header>.navbar-inner>.container-fluid').removeClass('container-fluid').addClass('container');
            $('.main').prepend('<div id="sidebarbg">');
        }

        function removeFixedWidth() {
            $('html').removeClass('fixedWidth');
            $('body').removeClass('container');
            $('#header>.navbar-inner>div').addClass('container-fluid').removeClass('container');
            $('#sidebarbg').remove();
        }

        //execute independ functions
        fontFlicker();
        mainNav();
        setCurrentNav();
        collapseNav();
        widgetBox();
        checkAll();
        accordionIcon();
        respond();
        last_child()

        //check for touch device
        if($('html').hasClass('touch')) {
            orientationFix();
        }
         //check for retina support
        if($('html').hasClass('retina')) {
            retina();
        }
         //check for ie browser
        if($('html').hasClass('ie9') || $('html').hasClass('ie8')) {
            IEplaceholder();
        }

        if($('html').hasClass('ie8')) {
            last_child();
        }
        return _this;

    }; 
})(jQuery);