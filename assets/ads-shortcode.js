(function() {
    drawButton();
})();

function drawButton() {     
    tinymce.PluginManager.add('ads_shortcode_button', function(editor, url) {
        // Add a button that opens a window and adds a URL
        editor.addButton('ads_shortcode', {
            title: 'Anúncio',
            icon: 'ads-shortcode dashicons-before dashicons-money-alt',
            onclick: function() {
                availableElements = [
                    {
                        text: 'Digite no campo acima para buscar anúncios',
                        value: 0,
                    }
                ];
                listbox = null;

                // Open window
                editor.windowManager.open( {
                    title: 'Inserir anúncio',
                    body: [
                        {
                            type: 'textbox',
                            name: 'title',
                            label: 'Busca',
                            onkeyup: debounce(function(event) {
                                jQuery.get(
                                    ajaxurl, 
                                    {
                                        'action': 'ads_search',
                                        'search': event.target.value,
                                    },
                                    null,
                                    'json',
                                ).done((response) => {
                                    for(i in availableElements)
                                        availableElements.pop();

                                    if(response.length) {
                                        for(i in response) {
                                            
                                            availableElements.push({
                                                value: response[i].ID,
                                                text: response[i].post_title,
                                            });
                                        }

                                        listbox.value(response[0].ID);
                                    }
                                    else {
                                        availableElements.push({
                                            text: 'Não há resultados para a busca',
                                            value: -1,
                                        });

                                        listbox.value(-1);
                                    }

                                    listbox._values = availableElements;
                                });
                            }, 250)
                        },
                        {
                            type: 'listbox',
                            name: 'id',
                            label: 'Anúncio',
                            fixedWidth: !0,
                            values: availableElements,
                            onPostRender: function() {
                                listbox = this;
                                listbox.value(0);
                            }
                        }
                    ],
                    onsubmit: function(event) {
                        if(event.data.id != 0)
                            editor.insertContent(`[ads id="${event.data.id}"]`);
                    }
                });
            }
        });
    });
}

function debounce(func, wait, immediate) {
	var timeout;
	return function() {
		var context = this, args = arguments;
		var later = function() {
			timeout = null;
			if (!immediate) func.apply(context, args);
		};
		var callNow = immediate && !timeout;
		clearTimeout(timeout);
		timeout = setTimeout(later, wait);
		if (callNow) func.apply(context, args);
	};
};