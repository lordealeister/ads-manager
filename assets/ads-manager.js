jQuery(function($) {
	const $positions = $('.cmb2-id-ads-positions'),
		$manage = $('#ads_manage');

	if(!$manage.is(':checked'))
		$positions.hide();

	$manage.on('change', () => $manage.is(':checked') ? $positions.show() : $positions.hide());
});