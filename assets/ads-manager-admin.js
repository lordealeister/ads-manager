jQuery(function($) {
	const $positions = $('.cmb2-id-ads-positions'),
		$manage = $('#ads_manage');
	let $selectPositions = $('select[id^="ads_positions_"]');

	const groupHeader = function(select) {
		const $select = $(select);
		$select.parents('.cmb-repeatable-grouping').first().find('.cmbhandle-title').text($select.find('option:selected').text()); 
	};

	const setGroupHeaders = function() {
		$selectPositions = $('select[id^="ads_positions_"]');
		$selectPositions.each((index) => groupHeader($selectPositions.get(index)));

		$selectPositions.off('change.positions');
		$selectPositions.on('change.positions', (event) => groupHeader(event.currentTarget));
	};

	if(!$manage.is(':checked'))
		$positions.hide();

	$manage.on('change', () => $manage.is(':checked') ? $positions.show() : $positions.hide());

	setGroupHeaders();

	$(document).on('click', '[data-selector="ads_positions_repeat"]', () => setTimeout(() => setGroupHeaders(), 50));
});