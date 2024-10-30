( function ( $ ) {
	$( document ).ready( function () {
		setTimeout(function(){
			tinymce.get('content').getBody().setAttribute('contenteditable', false);
			tinymce.get('excerpt').getBody().setAttribute('contenteditable', false);
			tinymce.get('content').setMode('readonly');
			tinymce.get('excerpt').setMode('readonly');
		}, 1000);
    $('form :input').prop('disabled', true);

    $("body").on('DOMSubtreeModified', ".woocommerce_variable_attributes", function() {
        $('form :input').prop('disabled', true);
        $('.remove_variation').contents().unwrap();
    });

    $('#publish, #save-post, .edit-tag-actions>.button').click(function(e){
			$("#permission_denied").show();
			return false;
		});
		$('#publish, #save-post, .edit-tag-actions>.button').hide();
		$('.edit-tag-actions>.button').hide();
		$('#delete-link').hide();
		$('#delete-action').hide();
		$('#duplicate-action').hide();
		$('#product_cat-add-toggle').hide();
		$('.edit-post-status').hide();
		$('#visibility').hide();
		$('.edit-timestamp').hide();
		$('#product_cat-add-toggle').hide();
		$('#link-product_tag').hide();
		$('#trophymonsta_brand-add-toggle').hide();
		$('#remove-post-thumbnail').hide();
		$('.add_product_images').hide();
		$('.edit-catalog-visibility').hide();

	});

}( jQuery ) );
