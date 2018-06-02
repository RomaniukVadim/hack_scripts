$(document).ready(function() {

	//------------- Elastic text area -------------//
 	$('textarea.elastic').autosize();  

 	//------------- Limit text area -------------//
 	$('textarea.limit').inputlimiter({limit: 100});

 	//------------- Masked input fields -------------//
	$('.date').mask('11/11/1111');
	$('.time').mask('00:00:00');
	$('.date_time').mask('99/99/9999 00:00:00');
	$('.cep').mask('99999-999');
	$('.phone').mask('9999-9999');
	$('.phone_with_ddd').mask('(99) 9999-9999');
	$('.phone_us').mask('(999) 999-9999');
	$('.mixed').mask('AAA 000-S0S');
	$('.cpf').mask('999.999.999-99', {reverse: true});
	$('.money').mask('000.000.000.000.000,00', {reverse: true});
	$('.ip_address').mask('0ZZ.0ZZ.0ZZ.0ZZ', {translation: {'Z': "[0-9]?"}});

	//Callback example
	var options =  { onComplete: function(cep) {
	  alert('Mask is done!:' + cep);
	},
	onKeyPress: function(cep, event, currentField, options){
	  console.log('An key was pressed!:', cep, ' event: ', event, 
	              'currentField: ', currentField, ' options: ', options);
	}};

	$('.cep_with_callback').mask('00000-000', options);

	//on fly mask change
	var options =  {onKeyPress: function(cep){
	  var masks = ['00000-000', '0-00-00-00'];
	    mask = (cep.length>7) ? masks[1] : masks[0];
	  $('.crazy_cep').mask(mask, this);
	}};

	$('.crazy_cep').mask('00000-000', options);

	//Mask as function
	var SaoPauloCelphoneMask = function(phone, e, currentField, options){
	  return phone.match(/^(\(?11\)? ?9(5[0-9]|6[0-9]|7[01234569]|8[0-9]|9[0-9])[0-9]{1})/g) ? 
	  '(00) 00000-0000' : '(00) 0000-0000';
	};

	$(".sp_celphones").mask(SaoPauloCelphoneMask);

	// now the digit 0 on your mask pattern will be interpreted 
	// as valid characters like 0,1,2,3,4,5,6,7,8,9 and *
	$('.custom').mask('00/00/0000', {'translation': {0: '[0-9*]'}});
 	
 	//------------- Spinners -------------//
 	$("#spinner").spinner();
 	$("#spinner-decimal").spinner({
		step: 0.01,
		numberFormat: "n"
	});
	$("#spinner-currency").spinner({
		min: 5,
		max: 2500,
		step: 25,
		start: 1000,
		numberFormat: "C"
    });

    $.widget("ui.timespinner", $.ui.spinner, {
	    options: {
	      // seconds
	      step: 60 * 1000,
	      // hours
	      page: 60
	    },
 
	    _parse: function( value ) {
	      if ( typeof value === "string" ) {
	        // already a timestamp
	        if ( Number( value ) == value ) {
	          return Number( value );
	        }
	        return +Globalize.parseDate( value );
	      }
	      return value;
	    },
 
	    _format: function( value ) {
	      return Globalize.format( new Date(value), "t" );
	    }
  	});
 
   $("#spinner-time").timespinner();

   //------------- Color picker -------------//
   $("#color-picker").spectrum({
   	 	preferredFormat: "hex6",
	    color: "#f00",
	    showInput: true,
	    showInitial: true,	   
	    clickoutFiresChange: true,
	    chooseText: "Select",
    	cancelText: "Close"
	});

    $("#color-picker-flat").spectrum({
    	preferredFormat: "hex6",
    	flat:true,
    	showInput: true
    });

    //------------- Datepicker -------------//
    $('#datepicker').datepicker({

    });
    $('#datepicker-inline').datepicker({
    	todayBtn: true,
    	todayHighlight: true
    });

    //------------- Select2 -------------//
    $("#select1").select2({
	    placeholder: "Select a State"
	});

	$("#select2").select2();

    //tags
	$("#tags").select2({tags:["red", "green", "blue"]});

	//------------- Dual multi select -------------//
	$(".multiselect").multiselect();

	//------------- WysiHtml5 editor -------------//
	$('#text-editor').wysihtml5();

});