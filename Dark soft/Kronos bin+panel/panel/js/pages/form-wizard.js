$(document).ready(function() {

	function createSuccessMsg (loc, msg) {
		loc.append(
			'<div class="alert alert-success"><button type="button" class="close" data-dismiss="alert">&times;</button><strong><i class="icon24 i-checkmark-circle"></i> Well done!</strong> '+ msg + ' </div>'
		);
	}

 	//------------- Form wizard with steps-------------//
 	$("#wizard").formwizard({ 
	 	formPluginEnabled: true,
	 	validationEnabled: false,
	 	focusFirstInput : true,
	 	formOptions :{
			success: function(data){
				//produce success message
				createSuccessMsg($("#wizard .msg"), "You successfully submit this form");
			},
			resetForm: false
	 	},
	 	disableUIStyles: true,
	 	showSteps: true //show the step
	});

	//------------- Form wizard without steps -------------//
 	$("#wizard1").formwizard({ 
	 	formPluginEnabled: true,
	 	validationEnabled: false,
	 	focusFirstInput : true,
	 	formOptions :{
			success: function(data){
				//produce success message
				createSuccessMsg($("#wizard1 .msg"), "You successfully submit this form");
			},
			resetForm: false
	 	},
	 	disableUIStyles: true,
	 	showSteps: false //show the step
	});

 	//------------- Vertical Form wizard with steps-------------//
 	$("#wizard2").formwizard({ 
	 	formPluginEnabled: true,
	 	validationEnabled: false,
	 	focusFirstInput : true,
	 	formOptions :{
			success: function(data){
				//produce success message
				createSuccessMsg($("#wizard2 .msg"), "You successfully submit this form");
			},
			resetForm: false
	 	},
	 	disableUIStyles: true,
	 	showSteps: true, //show the step
	 	vertical: true //activate vertical wizard
	});

	//------------- Wizard with validation -------------//
 	$("#wizard3").formwizard({ 
	 	formPluginEnabled: true,
	 	validationEnabled: true,
	 	validationOptions: {
	 		rules: {
	 			firstname: {
	 				required: true
	 			},
	 			email: {
	 				required: true,
	 				email: true
	 			},
	 			username: {
	 				required: true
	 			},
	 			password: {
	 				required: true,
	 				minlength: 5
	 			},
	 			password_2: {
	 				required: true,
					minlength: 5,
					equalTo: "#password"
	 			}

	 		}, 
	 		messages: {
	 			firstname: {
	 				required: "I need to know your first name Sir"
	 			},
	 			email: {
	 				required: "You email is required Sir"
	 			}
	 		}
	 	},
	 	focusFirstInput : true,
	 	formOptions :{
			success: function(data){
				//produce success message
				createSuccessMsg($("#wizard2 .msg"), "You successfully submit this form");
			},
			resetForm: false
	 	},
	 	disableUIStyles: true,
	 	showSteps: true //show the step
	});	
});