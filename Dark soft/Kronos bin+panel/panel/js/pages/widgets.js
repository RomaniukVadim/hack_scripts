$(document).ready(function() {
 	
 	//------------- Mention.js ( extend typeahead ) -------------//
 	$("#multi-users").mention({
	    queryBy: ['name', 'username'],
	    delimiter: '@',
	    users: [{
	        username: "Jay",
	        name: "Jay Robinson",
	        image: "https://si0.twimg.com/profile_images/2820247531/ad30b5932f3bf9e089db3e9417a41376_normal.png"
	    }, 
	    { 
	        username: "bigRoy",
	        name: "Roy Barber",
	        image: "https://si0.twimg.com/profile_images/3406728806/aea5b28541ce71882252187a6473f62b_normal.png"
	    }, 
	    { 
	        username: "VinDisel",
	        name: "Vin Thomas",
	        image: "https://si0.twimg.com/profile_images/1907250901/vin-avatar_normal.jpg"
	    },
	    { 
	        username: "Louos",
	        name: "Louis Bullock",
	        image: "https://si0.twimg.com/profile_images/3580798446/296848b795e27b90bf10a7f6a735c815_normal.jpeg"
	    },
	    { 
	        username: "Cibelle",
	        name: "Cibelle Chalot",
	        image: "https://si0.twimg.com/profile_images/3580798446/296848b795e27b90bf10a7f6a735c815_normal.jpeg"
	    }
	    ]
	});

	//------------- ToDo -------------//
	//toDo 
    function toDo () {
        var todos = $('.toDo');
        var items = todos.find('.task-item');
        var chboxes = items.find('input[type="checkbox"]');
        var close = items.find('.act');

        chboxes.change(function() {
           if ($(this).is(':checked')) {
                $(this).closest('.task-item').addClass('done');
            } else {
                $(this).closest('.task-item').removeClass('done');
            }
        });

        items.hover(
          function () {
            $(this).addClass('show');
          },
          function () {
            $(this).removeClass('show');
          }
        );

        close.click(function() {
            $(this).closest('.task-item').fadeOut('500');
            //Do other stuff here..
        });

    }

    toDo();

	//sortable
	$(function() {
	    $( "#today, #tomorrow" ).sortable({
	      connectWith: ".todo-list"
	    }).disableSelection();
	});

	//------------- Spark stats in widget box title -------------//
	$('.spark>.positive').sparkline('html', { type:'bar', barColor:'#42b449'});
	$('.spark-line>.positive').sparkline('html', { type:'line', lineColor:'#42b449'});


	//------------- Custom scroll in widget box  -------------//

	$(".scroll").niceScroll({
		cursoropacitymax: 0.8,
        cursorborderradius: 0,
        cursorwidth: "10px"
	});

	//------------- Contact widet list nav plugin -------------//
	$('#contact-list').listnav({ 
	    includeNums: false,  
	    noMatchText: 'There are no matching entries.',
	    showCounts: false
	 });

	 	
});