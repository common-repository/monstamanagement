( function ( $ ) {
	$( document ).ready( function () {
		$('.check-column').find('input[type=checkbox]').attr('disabled',true);

		 $('#the-list tr:has(td)').find('span[class="edit"]').each(function () {
       $(this).closest('tr').find('input[type=checkbox]').attr('disabled',false);
		});

		/*$('.trophymonstahidden').each(function () {
			$(this).closest('tr').find('a.row-title').contents().unwrap();
			$(this).closest('tr').find('td.thumb a').contents().unwrap();
	 });*/

	});

}( jQuery ) );
