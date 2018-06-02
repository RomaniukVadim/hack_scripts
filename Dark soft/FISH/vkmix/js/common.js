var stManager = { // http://litelike.ru
 callbacks: [],
 resources: [],
 load: function(href, type) {
  if(inArray(href, this.resources)) return;
  var file = this.parsePath(href), self = this;
  this.resources.push(href);
  switch(type) {
   case 'js':
    var script = create('script', {
     src: href,
     type: 'text/javascript'
    });
   document.getElementsByTagName('head')[0].appendChild(script);
   break;
   case 'css':
    var link = create('link', {
     href: href,
     rel: 'stylesheet',
     type: 'text/css'
    });
    link.onload = function() {
     self.done(file);
    }
   document.getElementsByTagName('head')[0].appendChild(link);
   break;
  }
 },
 init: function() {
  var scripts = document.getElementsByTagName('script');
  for(var i in scripts) {
   if(scripts[i].src) {
    this.resources.push(scripts[i].src);
   }
  }
  var links = document.getElementsByTagName('link');
  for(var i in links) {
   if(links[i].href && links[i].rel == 'stylesheet') {
    this.resources.push(links[i].href);
   }
  }
 },
 done: function(name) {
  this.callbacks[name] = isArray(this.callbacks[name]) ? this.callbacks[name] : [];
  for(var i = 0; i < this.callbacks[name].length; i++) {
   this.callbacks[name][i]();
  }
 },
 ral: function(name, callback) {
  this.callbacks[name] = isArray(this.callbacks[name]) ? this.callbacks[name] : [];
  this.callbacks[name].push(callback);
 },
 parsePath: function(path) {
  var match = path.match(/^(https?:\/\/([\w\.-]+))?(\/.*)$/);
  if(!match || !match[3]) return path;
  path = basename(match[3]);
  var pos = path.indexOf('?');
  if(pos != -1) {
   path = path.substring(0, pos);
  }
  return path;
 }
}

function isArray(obj) {
 return Object.prototype.toString.call(obj) === '[object Array]'; 
}
function isObject(obj) { 
 return Object.prototype.toString.call(obj) === '[object Object]'; 
}

function create(tagName, attributes) { 
 var el = document.createElement(tagName);
 if(isObject(attributes)) {
  for(var i in attributes) {
   el.setAttribute(i, attributes[i]);
   if(i.toLowerCase() == 'class') {
    el.className = attributes[i]; 
   } else if(i.toLowerCase() == 'style') {
    el.style.cssText = attributes[i];
   }
  }
 }
 return el;
}

function inArray(needle, haystack) {
 for(var i = 0, l = haystack.length; i < l; i++) {
  if(needle == haystack[i])
  return true;
 }
 return false;
}

function basename(path, suffix) {
 var b = path.replace(/^.*[\/\\]/g, '');
 if(typeof(suffix) == 'string' && b.substr(b.length-suffix.length) == suffix) {
  b = b.substr(0, b.length-suffix.length);
 }
 return b;
}