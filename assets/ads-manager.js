var renderAds = function(template, node) {
    if(!node) 
		return;

    node.innerHTML = template;
	
	var elements = node.querySelectorAll('.ads-manager div');

	if(!elements.length)
		return;

	elements.forEach(element => {
		if(window.googletag && googletag.apiReady) 
			googletag.display(element.id);
	});
};
