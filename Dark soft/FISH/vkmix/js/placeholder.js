/*!

 * placeholder.js

*/
 
function _placeholder(a) {
 var _placeholder = $(a).attr('iplaceholder');
 var _value = $(a).val();
 if(!_value) {
  setTimeout(function() {$(a).val(_placeholder)}, 0);
  $(a).addClass('placeholder');
 }
 $(a).focus(function() {
  $(a).removeClass('placeholder');
  if($(this).val() == _placeholder) $(this).val('');
 });
 $(a).blur(function() {
  if(!$(this).val()) {
   $(this).val(_placeholder);
   $(a).addClass('placeholder');
  }
 });
}

 
function _htmlplaceholder(a) {
 var _placeholder = $(a).attr('iplaceholder');
 var _value = $(a).html();
 if(!_value) {
  setTimeout(function() {$(a).html(_placeholder)}, 0);
  $(a).addClass('placeholder');
 }
 $(a).focus(function() {
  $(a).removeClass('placeholder');
  if($(this).html() == _placeholder) $(this).html('');
 });
 $(a).blur(function() {
  if(!$(this).html()) {
   $(this).html(_placeholder);
   $(a).addClass('placeholder');
  }
 });
}

function _val(a) {
 return ($(a).attr('iplaceholder') == $(a).val()) ? 0 : 1;
}