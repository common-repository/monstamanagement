jQuery(document).ready(function() {
	if(jQuery("*:contains('Ungrouped')").length)
	{
		jQuery("a:contains('Ungrouped')").parent().hide();
	}
	if(jQuery("*:contains('ungrouped')").length)
	{
		jQuery("a:contains('ungrouped')").parent().hide();
	}
});
