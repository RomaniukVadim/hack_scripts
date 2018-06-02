$(document).ready(function() {
	alert();
	//------------- Data tables -------------//
	$('#dataTable').dataTable( {
		"sDom": "<'row-fluid'<'span6'l><'span6'f>r>t<'row-fluid'<'span6'i><'span6'p>>",
		"sPaginationType": "bootstrap",
		"bJQueryUI": false,
		"bAutoWidth": false,
		"oLanguage": {
			"sSearch": "<span>Filter:</span> _INPUT_",
			"sLengthMenu": "<span>_MENU_ entries</span>",
			"oPaginate": { "sFirst": "First", "sLast": "Last" }
		}
	});

	$('.dataTables_length select').uniform();

	/*$('.res-table').stacktable();*/
});