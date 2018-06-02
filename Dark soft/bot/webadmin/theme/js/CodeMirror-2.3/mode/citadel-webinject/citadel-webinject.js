CodeMirror.defineMode("citadel-webinject", function (config, parserConfig) {
	var html_mplex = CodeMirror.multiplexingMode(CodeMirror.getMode(config, "text/plain"),
		{open:"data_before", close:"data_end", mode:CodeMirror.getMode(config, "text/html"), delimStyle:"delimit"},
		{open:"data_inject", close:"data_end", mode:CodeMirror.getMode(config, "text/html"), delimStyle:"delimit"},
		{open:"data_after", close:"data_end", mode:CodeMirror.getMode(config, "text/html"), delimStyle:"delimit"}
	);

	var injectOverlay = {
		startState: function(){
			return {};
		},
		token:function (stream, state) {
			var ch;

			// More complex rule to highlight the rule
			if (state.token === undefined){
				if (stream.match(/^set_url /, true)){ // match & consume
					state.token = 'set_url';
					return 'keyword';
				}
			} else {
				switch (state.token){
					case 'set_url':
						if (stream.match(/[^\s]+/, true)){ // first argument is a string. Consume it
							delete state.token; // unset the state: colorize nothing here
							stream.skipToEnd(); // skip till the end: match nothing more at this line
							return 'strong string';
						}
						break;
				}
			}

			// If the current position matches - return the css className
			var region_pattern = /^(data_before|data_inject|data_after|data_end)$/;
			if (stream.match(region_pattern)){
				stream.skipToEnd(); // Move forward
				return 'keyword';
			}

			// Skip until the next occurence of any token
			var skip_pattern = /^(set_url .+|data_before|data_inject|data_after|data_end)$/;
			while (stream.next() != null && !stream.match(skip_pattern, false)) {}
			return null;
		}
	};
	var inj_overlay = CodeMirror.overlayMode(html_mplex, injectOverlay);
	return inj_overlay;
});
CodeMirror.defineMIME("text/x-citadel-webinject", "citadel-webinject");
