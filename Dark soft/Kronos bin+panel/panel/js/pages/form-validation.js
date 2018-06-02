$(document).ready(function() {
 	//------------- Form validation -------------//
 	$('#select1').select2({placeholder: "Select"});

 	$("#validate").validate({
 		ignore: null,
    	ignore: 'input[type="hidden"]',
 		rules: {
 			select1: "required",
 			password: {
				required: true,
				minlength: 5
			},
			confirm_password: {
				required: true,
				minlength: 5,
				equalTo: "#password"
			},
			textarea: {
				required: true,
				minlength: 10
			},
			rangelenght: {
		      required: true,
		      rangelength: [10, 20]
		    },
		    range: {
		      required: true,
		      range: [5, 10]
		    },
		    minval: {
		      required: true,
		      min: 13
		    },
		    maxval: {
		      required: true,
		      max: 13
		    },
		    date: {
		      required: true,
		      date: true
		    },
		    number: {
		      required: true,
		      number: true
		    },
		    digits: {
		      required: true,
		      digits: true
		    },
		    ccard: {
		      required: true,
		      creditcard: true
		    },
			agree: "required"
 		},
 		messages: {
 			password: {
				required: "Please provide a password",
				minlength: "Your password must be at least 5 characters long"
			},
			confirm_password: {
				required: "Please provide a password",
				minlength: "Your password must be at least 5 characters long",
				equalTo: "Please enter the same password as above"
			},
			agree: "Please accept our policy",
			textarea: "Write some info for you",
 		}
 	});
});