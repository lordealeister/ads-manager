window.renderAds = function(template, node) {
    if(!node) 
		return;

    node.innerHTML = template;
	
	var elements = node.querySelectorAll('.ads-manager div');

	if(!elements.length)
		return;

	for(var i = 0; i < elements.length; i++) {
		var element = elements[i];
		
		googletag.cmd.push(function() {
			googletag.display(element.id);
		});
	}
};
