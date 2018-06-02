function CBoxHacker($cbox_prev, $cbox_next){
	this.$current = null;

	// Key navigation simulation
	this.bindKeys = function(){
		$(document).on('keydown.cboxhacks', function (e) {
			if (e.keyCode === 37) $cbox_prev.click();
			if (e.keyCode === 39) $cbox_next.click();
			if (e.keyCode === 37 || e.keyCode === 39) {
				e.preventDefault();
				return false;
			}
		});
	};
	this.unbindKeys = function(){
		$(document).off('.cboxhacks');
	};

	// Next/Prev buttons click
	this.bindClicks = function($li){
		if (!$li.next().length)
			$cbox_next.hide();
		else
			$cbox_next.show().on('click.cboxhacks', function(){
				$li.next().click();
				return false;
			});
		if (!$li.prev().length)
			$cbox_prev.hide();
		else
			$cbox_prev.show().on('click.cboxhacks', function(){
				$li.prev().click();
				return false;
			});
	};
	this.unbindClicks = function(){
		$cbox_next.off('.cboxhacks');
		$cbox_prev.off('.cboxhacks');
	};

	// Shortcuts
	this.bind = function(li){
		if (this.$current)
			this.$current.removeClass('brief');
		this.$current = $(li).addClass('brief seen');

		this.bindKeys();
		this.bindClicks(this.$current);
	};
	this.unbind = function(){
		this.unbindKeys();
		this.unbindClicks();
	};

	return this;
}


// Reports searcher
if (window.q && window.datelist)
    $(function(){
        // Prepare an array of callbacks, where each launches the search for a single date
        var dateLoader = function(yymmdd){
            var $display = $('#dt' + yymmdd);
            return $.get(window.q + '&date=' + yymmdd, {}, function(data){
                    $display.empty().append(data);
                });
        };

        // Display the SQL-killer
        var $sql_killer = $('#kill-db-query').show();

        // Now launch the callbacks sliced by 4
        var launchBatch = function(start, step){
            // Get the callbacks
            var batch = [];
            for (var i = start; i < (start + step) && i < window.datelist.length; i++)
                batch.push(  dateLoader(window.datelist[i])  );

            // Empty list case
            if (!batch.length){
                $sql_killer.hide();
                return;
            }

            // Init the deferred
            $.when.apply(null, batch)
                .then(
                function(){ launchBatch(start + step, step); },
                function(){ console.log('Error: ', arguments); }
            );
        };
        launchBatch(0, 4);
    });


$(function(){
	// Search button: when text/plain - open in new window
	$('form#filter input:submit').on('click', function(){
		var $submit = $(this);
		var $form = $submit.closest('form');
		var $cb_plaintext = $form.find('input:checkbox[name="plain"]');

		if ($cb_plaintext.is(':checked')){
			// Open in new window
			$form.attr('target', '_blank');
			return true;
		}
		return true;
	});

	// Report [+] link: open in new window
	$('#botslist .botnet-search-results').on('click', 'ul.bot-search-results ol.bot-reports li a', function(e){
		$(this).attr('target', '_blank').closest('li').addClass('seen');
		e.stopPropagation();
	});

	// Disable the AJAX spinner here (useless)
	$(document).off('.ajax-spinner');

	// Brief view mode: colorbox hacks
	var $cbox = $('#colorbox'); // Colorbox display
	var cboxhacks = new CBoxHacker($cbox.find('#cboxPrevious'), $cbox.find('#cboxNext'));

	// Brief view mode
	$('#botslist .botnet-search-results').on('click', 'ul.bot-search-results ol.bot-reports li', function(){
		var $this = $(this);
		var href = $this.find('a').attr('href');

		// Init dynamic colorbox here
		$this.colorbox({
			loop: false,
			width: '90%',
			height: '90%',

			href: href + '&viewmode=brief',
			title: $('<a href="'+href+'" target="_blank">'+$this.text()+'</a>'),

			open: true,

			onComplete: function(){
				cboxhacks.unbind();
				cboxhacks.bind(this);
			},
			onClosed: function(){
				cboxhacks.unbind();
			}
		});
		return false;
	});
});


/**
 * Found reports callback
 */
function reports_db_found(display){

    // Lighlight words
    var $container = $(display).find('> ul.bot-search-results');
    var $bots = $container.children(); // <li>s of each single bot
    var current = 0;
    var sleepyIterate = function(){
        // Collect some entries this time and highlight them
        var collected = 0;
        while (collected<1000 && current<$bots.length){
            // Get reports of the current bot
            var $reports = $($bots[current]).find('ol.bot-reports li');
            // Highlight
            for (var i = 0; i<window.hilite.length; i++)
                $reports.highlight(window.hilite[i], 'hilite');
            // Increase the counters
            collected += $reports.length;
            current++;
        }

        // Now sleep till next iteration
        if (current < $bots.length)
            setTimeout(sleepyIterate, 300);
    };
    sleepyIterate();
}


// --- Data-Miner
$(function(){
    // Search form: enable/disable & show/hide
    var $dataminer = $('#tr-dataminer');
    $dataminer.find('input:checkbox').change(function(){
        var $checkbox = $(this);
        var checked = $checkbox.is(':checked');
        $dataminer.find('#dataminer')[  checked? 'show' : 'hide'  ]('slow');

        // Hide extra
        var $hideNclear = $('#tr-search-string, #tr-hilite, #tr-smart, #tr-grouping, #tr-nonames, #tr-plain');
        $hideNclear[  checked? 'hide' : 'show'  ]('slow');
        if (checked)
            $hideNclear
                .find(':input')
                .filter(':not(:checkbox)').val(null).end() // empty text in inputs
                .filter(':checkbox').attr('checked', false); // unselect checkboxes

        // Disable the report type input
        var $blt = $('#tr-blt select').attr('disabled', checked);
        if (checked)
            $blt.val(-1); // http | https

        return true;
    }).change();

    // Results brief mode preview
    $('#dt2DATAMINER').on('click', '#dataminer-results TBODY td a', function(e){
        var $this = $(this);
        var $all = $this.closest('td').find('a');

        /** GUID generator
         * @returns {string}
         */
        var guid = function() {
            var S4 = function() { return (((1+Math.random())*0x10000)|0).toString(16).substring(1); };
            return (S4()+S4()+"-"+S4()+"-"+S4()+"-"+S4()+"-"+S4()+S4()+S4());
        };

        $all.colorbox({
            rel: guid(),
            loop: false,
            onComplete: function(){
                var pat = $(this).closest('td').find('a:first').text();
                console.log(pat);
                $('#cboxLoadedContent').highlight(pat);
            }
        });
        e.preventDefault();
    });
});
