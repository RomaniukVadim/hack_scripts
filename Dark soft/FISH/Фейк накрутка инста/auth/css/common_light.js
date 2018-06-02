function ge(el) {
  return (typeof el == 'string' || typeof el == 'number') ? document.getElementById(el) : el;
}

function trim(text) { return (text || '').replace(/^\s+|\s+$/g, ''); }

function hasClass(obj, name) {
  obj = ge(obj);
  return obj && (new RegExp('(\\s|^)' + name + '(\\s|$)')).test(obj.className);
}

function addClass(obj, name) {
  if (obj = ge(obj)) {
    if (obj && !hasClass(obj, name)) obj.className = (obj.className ? obj.className + ' ' : '') + name;
  }
}

function removeClass(obj, name) {
  if (obj = ge(obj)) {
    obj.className = trim((obj.className || '').replace((new RegExp('(\\s|^)' + name + '(\\s|$)')), ' '));
  }
}

function isChecked(el) {
  el = ge(el);
  return hasClass(el, 'on') ? 1 : '';
}

function checkbox(el, val) {
  el = ge(el);
  if (!el || hasClass(el, 'disabled')) return;

  if (val === undefined) {
    val = !isChecked(el);
  }
  if (val) {
    addClass(el, 'on');
  } else {
    removeClass(el, 'on');
  }
  return false;
}

var parseJSON = (window.JSON && JSON.parse) ? function (obj) {
  try { return JSON.parse(obj); } catch (e) {
    return eval('('+obj+')');
  }
} : function(obj) {
  return eval('('+obj+')');
}

function show(elem) {
  if (arguments.length > 1) {
    for (var i = 0, l = arguments.length; i < l; ++i) {
      show(arguments[i]);
    }
    return;
  }
  elem = ge(elem);
  if (!elem || !elem.style) return;
  elem.style.display = 'block';
}

function hide(elem) {
  var l = arguments.length;
  if (l > 1) {
    for (var i = 0; i < l; i++) {
      hide(arguments[i]);
    }
    return;
  }
  elem = ge(elem);
  if (!elem || !elem.style) return;
  elem.style.display = 'none';
}

function domInsertBefore(el, before) {
  var parent = domPN(before);
  return parent && parent.insertBefore(el, before);
}

function showProgress(el, id, cls, doInsertBefore) {
  el = ge(el);
  if (!el) return;

  var prel;

  if (hasClass(el, 'pr')) {
    prel = el;
  } else {
    prel = document.createElement('div');
    prel.innerHTML = '<div class="pr ' + (cls || '') + '" id="' + (id || '') + '"><div class="pr_bt"></div><div class="pr_bt"></div><div class="pr_bt"></div></div>';
    prel = prel.firstChild;

    if (doInsertBefore) {
      domInsertBefore(prel, el);
    } else {
      el.appendChild(prel);
    }
  }

  setTimeout(function(){
    prel.style.filter = '';
    prel.style.zoom = 1;
    prel.style.opacity = 1;
  });

  return prel;
}

function re(el) {
  el = ge(el);
  if (el && el.parentNode) el.parentNode.removeChild(el);
  return el;
}

function hideProgress(el) {
  if (el) {
    if (hasClass(el, 'pr')) {
      el.style.filter = 'alpha(opacity=0)';
      el.style.zoom = 1;
      el.style.opacity = 0;
    } else {
      var pr = el.querySelector('.pr');
      pr && re(pr);
    }
  }
}

function lockButton(el) {
  if (!(el = ge(el))) return;

  if (el.tagName.toLowerCase() != 'button' && !hasClass(el, 'flat_button') && !hasClass(el, 'wr_header') || isButtonLocked(el)) return;

  addClass(el, 'flat_btn_lock');
  el.inner = el.innerHTML;

  el.style.width = el.offsetWidth + 'px';
  el.style.height = el.offsetHeight + 'px';

  el.innerHTML = '';

  showProgress(el, 'btn_lock');
}

function unlockButton(el) {
  if (!(el = ge(el))) return;

  if (!isButtonLocked(el)) return;

  hideProgress(el);
  el.innerHTML = el.inner;
  removeClass(el, 'flat_btn_lock');

  el.style.width = '';
  el.style.height = '';
}

function isButtonLocked(el) {
  if (!(el = ge(el))) return;
  return hasClass(el, 'flat_btn_lock');
}
