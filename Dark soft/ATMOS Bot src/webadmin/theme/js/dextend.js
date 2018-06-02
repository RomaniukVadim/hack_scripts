if (false){
/** @typedef {(String|jQuery|Node|Array.<Node>)} */
jQuery.Arg; // A jQuery argument
/** @typedef {String} */
jQuery.Selector; // A jQuery selector
}

//TODO: check whether anything duplicates underscore.js. Especially arrays!

/* ==========[ FUNCTION:   INHERITANCE ]========== */

/** Declare that the class inherits another class' methods and properties.
 * Example: Call parent constructor: this.constructor.superClass.apply(this, arguments)
 * @param {Object}	superClass	The parent class
 * @addon
 */
Function.prototype.inherits = function(superClass) {
	// http://javascript.ru/tutorial/object/inheritance
	var Inheritance = function(){};
	Inheritance.prototype = superClass.prototype;

	this.prototype = new Inheritance();
	this.prototype.constructor = this;
	this.superClass = superClass; // You can use 'this.superClass' to invoke parent methods
	};

/**
 * var delay = setTimeout.partial(undefined, 10);
 * delay(function(){ alert('delayed 10'); });
 */
Function.prototype.partial = function(){
	var fn = this, args = Array.prototype.slice.call(arguments);
	return function(){
		var arg = 0;
		for ( var i = 0; i < args.length && arg < arguments.length; i++ )
			if ( args[i] === undefined )
				args[i] = arguments[arg++];
		return fn.apply(this, args);
	};
};

/**
 * var f = function(a) { console.log(this, a); }
 * var g = f.appliedTo('lol')
 * g(1) -> ["lol", 1]
 */
Function.prototype.appliedTo = function(thisArg){
	var fn = this;
	return function(){
		return fn.apply(thisArg, arguments);
	};
};






/* ==========[ FUNCTION:   CONSTRUCTING & EXTENDING ]========== */

/** Create an object by passing an array of arguments to its constructor
 * @param {Array}	data	The array of constructor arguments
 */
Function.prototype.fromArray = function(data) {
	var obj = new this();
	this.apply(obj, data);
	return obj;
	};

/** Add ALL (up to Object) properties and methods from the provided object to the parent class' prototype.
 * This is handy to update some class' prototype massively.
 * @param {Object}	src_obj	The source object: {property: value, }
 * @addon
 */
Function.prototype.extend = function(src_obj) {
	var tobj = {}; // Helper to detect 'Object' class' props & methods
	for(var x in src_obj){
		if((typeof tobj[x] == "undefined") || (tobj[x] != src[x])){ // Skip 'Object' props & methods
			this.prototype[x] = src_obj[x]; // array-like access to use function name in a variable
			}
		}
	// HACK: There's no '.toString()' in IE for 'for..in'
	if(document.all && !document.isOpera){
		var p = this.prototype.toString;
		if(		   typeof p == "function"
				&& p != this.prototype.toString
				&& p != tobj.toString
				&& p != "\nfunction toString() {\n    [native code]\n}\n"
				)
			this.prototype.toString = src_obj.toString;
		}
	};






/* ==========[ FUNCTION:   INVOCATION ]========== */

/** Safely invoke a callback: this guarantees args snapshot will be correctly COPIED, not referenced.
 * @param {Function}	callback	The callback function
 * @param {Array?}		args		Arguments to snapshot
 * @param {Object?}		thisArg		A variable to be bound as 'this'. Leave empty to bind to the callback. This won't be copied!
 * @return {Function}
 * @addon
 */
Function.prototype.applyCopy = function(args, thisArg){
	var self = this; // This will preserve context if `thisArg` not provided
	return function(){  this.apply(thisArg || self, args || []);  };
	};

/** Partial function call. `undefined` arguments gets filled
 * var delay = setTimeout.partial(undefined, 10);
 * delay(function(){ alert('delayed 10'); });
 */
Function.prototype.partial = function(){
	var fn = this, args = Array.prototype.slice.call(arguments);
	return function(){
		var arg = 0;
		for ( var i = 0; i < args.length && arg < arguments.length; i++ )
		if ( args[i] === undefined )
			args[i] = arguments[arg++];
		return fn.apply(this, args);
		};
	};

/** Returns the same function which's always applied to the same `this` arg
 * var f = function(a) { console.log(this, a); }
 * var g = f.appliedTo('lol')
 * g(1) -> ["lol", 1]
 */
Function.prototype.appliedTo = function(thisArg){
	var fn = this;
	return function(){
		return fn.apply(thisArg, arguments);
		};
	};






/* ==========[ Number ]========== */

/** Format a number with grouped thousands
 * @param {Number} decimals			Sets the number of decimal points
 * @param {String} dec_point		Sets the separator for the decimal point.
 * @param {String} thousands_sep	Sets the thousands separator.
 * @return {String}
 */
Number.prototype.numberFormat = function(decimals, dec_point, thousands_sep){
	var i, j, kw, kd, km;
	var number = this;
	// Input
	var minus = '';
	if(number < 0){
		minus = "-";
		number = number*-1;
		}
	if( isNaN(decimals = Math.abs(decimals)) )	decimals = 2;
	if( dec_point == undefined )				dec_point = ",";
	if( thousands_sep == undefined )			thousands_sep = " ";
	// Calc
	i = parseInt(number = (+number || 0).toFixed(decimals)) + "";
	j = ( (j = i.length) > 3 )? j % 3 : 0;
	km = (j ? i.substr(0, j) + thousands_sep : "");
	kw = i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousands_sep);
	kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).replace(/-/, 0).slice(2) : "");2
	return minus + km + kw + kd;
	};






/* ==========[ String ]========== */

/** Convert all applicable characters to HTML entities
 * @return {String}
 * @addon
 */
String.prototype.htmlEntities = function(str){
	return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
	};

/** Filter a string with the help of predefined filters/translators
 * @param {String}	name	Name of the filter to call
 * @param {String}	replace	Replace invalid characters with the specified value
 * @return {String}
 * @addOn
 */
String.prototype.filter = function(name, replace){
	return this.filter.prototype[name](this,replace == undefined ? '' : replace);
	};
String.prototype.filter.extend({
		'URI':		function(s,r){ return s.replace(/[;/?:@&=+$,\\]+/g, r); }, // replace invalid URI-Component characters
		'URI!':		function(s,r){ return s.replace(/[^a-z0-9_-]+/ig, r).replace(/^_*|_*$/g, ''); }, // very strict 'URI': only simple ASCII
		'09':		function(s,r){ return s.replace(/[^0-9]+/g, r); }, // Number only
		'0.9':		function(s,r){ return s.replace(/[^0-9.]+/g, r); } // Floating-point number only
		});






/* ==========[ String: Templates ]========== */

/** Use the string as a template and replace all {key} occurrences with values from data[key].
 * Missing {key}s are printed as just '{key}' to allow template chaining.
 * Filters syntax allowed: |filter(param,...)|...
 * @param {Object}	data		Object of {key: value} pairs to use
 * @param {Object?}	ufilters	You can provide additional filters: { name: function(){}, ... } which will override the default ones
 * @return {String} A string with {key}s replaced
 * @addon
 */
String.prototype.template = function(data, ufilters) {
		return this.replace('%7B','{').replace('%7D','}')
			.replace(/{([^{}]*)}/g,
				function(match, key) {
					var filters = [];
					// Parse filters
					if (key.indexOf('|') != -1){
						// Remove filters' strings & store them
						key = key.replace(
							/\|([a-z0-9_]+)(?:\(([^\)]*)\))?/gi, // parse '|filter', '|filter(args)'
							function(m, filter, args){
								var filt = {
									filter: filter,
									args: []
									};
								if (args === ',')
									filt.args = [args];
									else if (args === '')
									filt.args = []
									else {
									// Parse "a,b,'c,c',\"d,d\",e,\"f,f\"" into array of params
									var r = /(?:"([^"]+)")|(?:'([^']+)')|([^,]+)/g;
									while (null !== (m = r.exec(args)))
										filt.args.push(  (m[1]===undefined)?  ((m[2]==undefined)? m[3] : m[2])  : m[1]  ); // args[] = coalesce(m[1],m[2],m[3])
									}
								// Store the filter
								filters.push(filt);
								return ''; // replace
								});
						}
					// Perform replacements
					var replace = data[key];
					if (replace === null)		return ''; // empty
					if (replace === undefined)	return match; // preserve template syntax
					// Apply filters
					for (var i=0; i<filters.length; i++){
						var filt = filters[i].filter; // filter name
						var func = (ufilters && ufilters[filt])? ufilters[filt] : String.prototype.template.prototype[filt];
						replace = func(replace, filters[i].args );
						}
					// Finish
					return replace;
					}
		);
	};
String.prototype.template.extend({
		lcase:	function(val, args){  return val.toLowerCase();  }, // Lower case
		ucase:	function(val, args){  return val.toUpperCase();  }, // Upper case
		e:		function(val, args){  return val.htmlEntities(val);  },// HTML escape
		join:	function(arr, args){  return Array.prototype.join.apply(arr, args);  }, // Implode an array
		toStr:	function(obj, args){  return obj.toString();  }, // Call an object's `toString` method
		fmtNum:	function(val, args){  return Number.prototype.numberFormat.apply(val+0.0, args);  } // Format a number
		});






/* ==========[ String: Transliteration ]========== */

/** Transliterate a string into ASCII caracters
 * @param {String?}	lang	Language || default.
 * @param {Number?}	ver		Version || default
 * @return {String}
 * @addon
 */
String.prototype.translit = function(lang, ver){
	var map = this.translit.prototype.trTable(lang, ver);
	var str = '';
	for (var i=0, L=this.length; i<L; i++){
		var c = this[i];
		str += (map[c] === undefined)? c : map[c];
		}
	return str;
	};

/** Set the default language & its version for transliteration.
 * @param {String}	lang	Input language. Will use `String.translit(lang,id)` as the default.
 * @param {Number}	ver		Transtation table version id: when multiple choice, the specified version will be used
 * @addon
 */
String.prototype.translitLang = function(lang, ver){
	this.translit.prototype.def = { lang: lang, ver: ver };
	};

String.prototype.translit.extend({
		// Characters translation table
		table: {
			"ru": { // [0] GOST 16876-71, [1] nice, [2] simplified
				off: 1072, // Small letters Unicode offset
				vers: 3, // The number of versions in this table
				// Translation ' '-sep map.
				// '[shh,sch,sh]' indicates three versions of a letter
				// '~' will prevent the engine from translating this character
				map: 'a b v g d e [zh,zh,j] z i [jj,j,j] k l m n o p r s t u f [kh,h,h] [c,c,ts] ch sh [shh,sch,sh] [",,] [y,y,i] \' [eh,e,e] ju ja ~ [jo,jo,e]'
				},
			"en": {
				}
			},
		// Default settings
		def: {lang: "ru", ver: 1},
		// ====== HELPERS
		/** Prepare a translation table
		* @return {Object}
		*/
		trTable: function(lang,ver){
				var TP = String.prototype.translit.prototype; // shortcut
				if (!lang) lang=TP.def.lang;
				if (isNaN(ver)) ver=TP.def.ver;
				//=== Check
				if (!TP.table[lang])
					throw('String.translit(): undefined input language "'+lang+'"!');
				var Table = TP.table[lang]; // shortcut
				if (ver<0 || ver >= Table.vers)
					throw('String.translit(): undefined version "'+ver+'" for language "'+lang+'"!');
				//=== Exists? Return!
				if (Table.maps && Table.maps[ver])
					return Table.maps[ver];
				//=== Prepare
				// Prepare parent map
				if (!Table.maps)
					Table.maps = [];
				Table.maps[ver] = {};
				var Map = Table.maps[ver]; // Shortcut
				function addMap(from,to){
						// Prepare
						from = String.fromCharCode(  Table.off+from  );
						if (to === null) to = from; // preserve
						// Add
						Map[from] = to; // lowercase
						Map[from.toUpperCase()] = (to.length<=1)? to.toUpperCase() : (to[0].toUpperCase()+to.substr(1)); // UPPERCASE
						};
				// Prepare version map
				var map = Table.map.split(' ');
				for (var i=0, N=map.length; i<N; i++)
					if (map[i] == '~')
						addMap(i, null); // Preserve
						else if (map[i][0] == '[')
						addMap(i,  map[i].substr(1,map[i].length-2).split(',')[ver]  ); // Version
						else
						addMap(i, map[i]); // Single
				//=== Return!
				return Map;
				}
		});






/* ==========[ Array ]========== */

/** Apply a callback function to each array element
 * @param {function(this: Object, index=, value=)}	callback	The callback function to apply. `this` is the Array element
 * @addon
 */
Array.prototype.each = function(callback) {
	for (var i in this)
		if(!isNaN(i)) // Only numeric
			callback.call(this[i], i/1, this[i]);
	};

/** Translate all items in an array to another array of items.
 * @param {function(this: Object, index=, value=):*}    callback    The callback function that is applied to an array element and that returns the new value.
 * @return {Array}  The translated array
 * @override
 * @addon
 */
Array.prototype.map = function(callback) {
	var ret = [];
	for (var i in this)
		if(!isNaN(i)) // Only numeric
			ret[i] = callback.call(this[i], i/1, this[i]);
	return ret;
	};

/** Find an element with a callback
 * @param {function(this: Object, index=, value=):Boolean}    callback    The callback function to apply to elements. It returns `true` when finds something
 * @return {(*|null)}
 * @addon
 */
Array.prototype.find = function(callback) {
	for (var i in this)
		if(!isNaN(i)) // Only numeric
			if (callback.call(this[i], i/1, this[i]))
				return this[i];
	return null;
	};

/** Find an element's index with a callback
 * @param {function(this: Object, index=, value=):Boolean}    callback    The callback function to apply to elements. It returns `true` when finds something
 * @return {(*|null)}
 * @addon
 */
Array.prototype.search = function(callback) {
	for (var i in this)
		if(!isNaN(i)) // Only numeric
			if (callback.call(this[i], i/1, this[i]))
				return i;
	return null;
	};

/** Get the max element
 * @return {Number}
 */
Array.prototype.max = function(){
	return Math.max.apply( Math, this );
	};

/** Get the min element
 * @return {Number}
 */
Array.prototype.min = function(){
	return Math.min.apply( Math, this );
	};










/* ==========[ IE ADDONS ]========= */
if (!Array.prototype.indexOf) {
	Array.prototype.indexOf = function(needle) {
		var ret = this.search(function(key,value){  return value===needle;  });
		return (ret === null)? -1 : ret;
		};
	}
