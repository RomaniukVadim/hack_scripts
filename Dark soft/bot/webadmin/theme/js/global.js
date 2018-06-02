/** AJAX context menu
 */
window.AJAXcontextMenu = function(params){
    // lexicon
    if (params['lexicon'] === undefined)
        params['lexicon'] = window.lexicon;
	// Items tuning
	for (var name in params['items'])
        if (params['items'].hasOwnProperty(name)){
		var p = params['items'][name];
		// auto-icon
		if (p['icon'] === undefined)
			p['icon'] = name;
		// auto-name
		if (p['name'] === undefined){
            var lexicon = params['lexicon'];
            if (lexicon instanceof Lexicon)
                p['name'] = lexicon.get(name);
            else
                p['name'] = lexicon[name]; // compatibility with the previous implementation
        }
		// auto-ajax
		if (p['ajaxSuccess'] !== undefined && p['callback'] === undefined)
			p['callback'] = function(p){ 
					return function(key,opt){
						// Call the default callback & give him the ajaxSuccess function
						return params['callback'].call(this, key, opt, p['ajaxSuccess'].appliedTo(this));
						};
					}(p);
		}
	return params;
	};

// Inits
$(function(){
	// Init jLog
	$.jlog && $('#jlog').jlog();
});

if (window.global && window.global.width){
    var n = window.global.width, C = [0,0,0,0,0,0], p = 0, r;
    while (n > 0){
        r = n%6;
        n = Math.floor(n/6);
        C[p++] += r*40;
    }
    var i = 0;
    var $styles = $('<style></style>').appendTo('head');
    $styles.append('#main-header h2 span { color: rgb('+ C.slice(i*3,(i+1)*3).join(',') +'); }');
}


// jQuery templating
jQuery.fn.extend({
	template: function(data, ufilters){
		var html = '' + this.clone().removeClass('js-template').wrapAll('<div />').parent().html();
		return html.template(data, ufilters);
	}
});

// jQuery highlight
// http://www.gotoquiz.com/web-coding/programming/javascript/highlight-words-in-text-with-jquery/
jQuery.fn.highlight = function (str, className) {
    var regex = new RegExp(str, "gi");
    return this.each(function () {
        $(this).contents().filter(function() {
            return this.nodeType == 3 && regex.test(this.nodeValue);
        }).replaceWith(function() {
                return (this.nodeValue || "").replace(regex, function(match) {
                    return "<span class=\"" + className + "\">" + match + "</span>";
                });
            });
    });
};

// PHP append
window.phpAppend = function(map){
	for (var sel in map){
		$(sel).append(map[sel]);
	}
};

// SuperMenu
$(function(){
    var $menu = $('#supermenu');
    if (!$menu.length) return;

    // OnlineBots
    $menu.find('li.onlinebots a').colorbox({resize: true});
    $('form#supermenu-onlinebots').live('submit', function(){
        var $form = $(this);
        $.post($form.attr('action'), $form.serialize(), function(data){
            $.colorbox({html: data});
        });
        return false;
    });
});

// Lexicon
function Lexicon(defaultLanguage, subkey){
    this.defaultLanguage = defaultLanguage;
    this.subkey = subkey;

    /** Translations
     * @type {Object}
     */
    this.trans = {};
}

/** Add more translations, keyed by language
 * @param {Object.<String, Object>} data
 */
Lexicon.prototype.extend = function(data){
    jQuery.extend(true, this.trans, data);
    return this;
};

/** Get a sub-Lexicon by a key name
 * @param key
 * @returns {Lexicon}
 */
Lexicon.prototype.sub = function(key){
    var lex = new Lexicon(this.defaultLanguage, key);
    lex.trans = this.trans; // same
    return lex;
};

/** Get the translation for an item
 * @param {String} key Translation key
 * @param {String?} lang Language to translate for. The default is used when undefined.
 * @return {String?}
 */
Lexicon.prototype.get = function(key, lang){
    if (!lang) lang = this.defaultLanguage;
    try {
        return this.subkey
            ? this.trans[lang][this.subkey][key]
            : this.trans[lang][key];
    }
    catch (e){ return undefined; }
};

window.lexicon = new Lexicon($('html').attr('lang'));

// Report brief viewmode
$('.report-view-brief').live('click', function(){
    var $this = $(this);
    $.colorbox({
        open: true,
        href: $this.attr('href')
    });
    return false;
});

// GlobalNotes
lexicon.extend({
    ru: {
        globalnotes: {
            'ok': 'Заметка сохранена',
            'err': 'Не удалось сохранить заметку: {error}'
        }
    },
    en: {
        globalnotes: {
            'ok': 'Note saved',
            'err': 'Note save failed: {error}'
        }
    }
});

$(document).on('click', '.globalnotes-add', function(){
    var $this = $(this);
    $this
        .hide('slow')
        .next('.globalnotes-edit').show('slow');
    return false;
});
$(document).on('blur', '.globalnotes-edit', function(){
    var $this = $(this);
    var lex = lexicon.sub('globalnotes');

    $this.addClass('pending-action');

    var target = $this.data('href');
    $.post( target, { action: 'set', note: $this.html() }, function(data, status, xhr){
        $this.removeClass('pending-action');
        if (data.ok)
            $.jlog('ok', lex.get('ok'));
        else
            $.jlog('err', lex.get('err'), data);
    });
});

// Kill DB Query
$(document).on('click', '#kill-db-query', function(){
    var $this = $(this);
    $.get('?', { killDbQuery: $this.data('token') }, function(data){
        $this.hide();
        $.jlog('warn', 'Killed: {num}', {num: data});
    });
    return true;
});

// NodeJS
/** Get a connection to NodeJS
 * @returns {io.Socket}
 * @throws {Error}
 */
function io_connect(namespace){
    try {
        var nodejs = window.global.nodejs;
        var sio = io.connect(
            '//' + nodejs.host +
            ':' + nodejs.port +
            (namespace.indexOf('/') == 0  ? ''  : '/') + namespace +
            (namespace.indexOf('?') == -1 ? '?' : '&') + 'token=' + encodeURIComponent(nodejs.token) + '&'
        );
        sio.on('error', function (reason){
            $.jlog('err', 'Unable to connect to NodeJS: {reason}', {reason: reason});
        });
        sio.on('connect_failed', function (reason){
            $.jlog('err', 'NodeJS connection failure: {reason}', {reason: reason});
        });
        return sio;
    } catch (e){
        $.jlog('err', 'NodeJS connection failed: '+ e +'!');
        throw e;
    }
}
