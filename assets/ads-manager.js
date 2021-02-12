jQuery(function($) {
	// simple multiple select
	// $('.ads-select').select2();
 
	// multiple select with AJAX search
	$('.ads-select').select2({
  		ajax: {
            url: ajaxurl, // AJAX URL is predefined in WordPress admin
            dataType: 'json',
            delay: 250, // delay in ms while typing when to perform a AJAX search
            data: (params) => {
                return {
                    search: params.term, // search query
                    action: 'ads_search' // AJAX action for admin-ajax.php
                };
            },
            processResults: (data) => {
				var options = [];

				if(data.length) {
					// data is the array of arrays, and each of them contains ID and the Label of the option
					$.each(data, function(index, post) { // do not forget that "index" is just auto incremented value
                        options.push({ id: post.ID, text: post.post_title });
					});
				}

				return { results: options };
			},
			cache: false,
		},
		minimumInputLength: 3, // the minimum of symbols to input before perform a search
	});
});