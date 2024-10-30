const COMPANY_LOGO_MISSING = "Company Logo is Mandatory";
jQuery(document).ready(function() {
  jQuery("#owl-slider2,#owl-slider1").owlCarousel({
	 margin:20,
		nav:true,
		//touchDrag:false,
		mouseDrag:false,
		autoWidth:true,
		responsive:{
			0:{
					items:1,
					autoWidth:false
				},
				640:{
					items:1
				},
				1240:{
					items:2
				},
				1580:{
					items:2
				},
				1940:{
					items:2
				}
			}
  });
  
  var owl =  jQuery("#owl-logo-slider1").owlCarousel({
	 margin:20,
		touchDrag:false,
		mouseDrag:false,
		nav:true,
		items:3,
		center:true,
		loop: true,
		//singleItem: true,
		
		
  });
  
  jQuery( '.owl-logo-slider' ).each( function(){
	  var owl =  jQuery( this ).owlCarousel({
			 margin:10,
				touchDrag:false,
				mouseDrag:false,
				nav:true,
				items: 1,
				//center:true,
				//loop: true,
		  });
  });
	
  if (jQuery('#tmmengravingdetail').is(':checked') == true) {
      jQuery("#tmm-logo-upload").show();
	  jQuery('#company_logo').hide();
	  if( !jQuery( '#tmmengravingwithlogo' ).is( ':checked' ) &&  !jQuery( '#tmmengravingnologo' ).is( ':checked' ) ){
		jQuery( '#tmmengravingexistinglogo' ).prop( 'checked', true ); //default checked
	  }
  } else {
      jQuery("#tmm-logo-upload").hide();
  }
  if ( jQuery( "#tmmengravingemail" ).is( ':checked' ) ){
	  jQuery( '#email_engraving_process' ).show();
	  var step3 = jQuery( '#step_3_url' ).val();
	   jQuery( '#tmmengravingsettings' ).attr( 'action',step3 );
	  uncheckall( 'tmmengravingemail' );
  }else{
	  jQuery( '#email_engraving_process' ).hide();
  }
  
  if ( jQuery( '#tmmnoengraving' ).is( ':checked' )  || jQuery( '#tmmengravingemail' ).is( ':checked' ) ){
	   var step3 = jQuery( '#step_3_url' ).val();
	   jQuery( '#tmmengravingsettings' ).attr( 'action',step3 );
	   jQuery( '#step_2' ).attr( 'style', 'pointer-events: none;' );
  }
  if ( jQuery( '#tmmnoengraving' ).is( ':checked' )  ){
		uncheckall( 'tmmnoengraving' );
   }
    if ( jQuery( '#tmmengravingemail' ).is( ':checked' )  ){
		uncheckall( 'tmmengravingemail' );
   }
  
 jQuery( '#tmmengravingdetail' ).click( function (){
	 if( jQuery(this).is(':checked') ){
		uncheckall( 'tmmengravingdetail' );
		jQuery( '#tmmengravingwithlogo' ).prop( 'checked', true );
		var self = jQuery('input[name="tmmengravinglogoupload[]"]');
		var result =  previewImage(self,'#company_logo');
		if( result == false ){
			jQuery( '#company_logo' ).find( 'img' ).attr( 'src','' );
			jQuery( '#downloadable' ).html( " " );
		}
		 jQuery('#company_logo').hide();
		 jQuery( '#email_engraving_process' ).hide();
		 var step2 = jQuery( '#step_2_url' ).val();
		 jQuery( '#tmmengravingsettings' ).attr( 'action',step2 );
		 jQuery( '#step_2' ).attr( 'style', '' );
	 }
 } );
  jQuery( '#tmmengravingemail' ).click( function (){
	 if ( jQuery(this).is(':checked') ){
		 jQuery( '#tmmengravingdetail' ).prop( 'checked', false );
		 jQuery( '#tmmnoengraving' ).prop( 'checked', false );
		 jQuery( '.upload_error' ).remove();
		 jQuery( '#upload_issue' ).val(0);
	 }
		if ( jQuery(this).attr( 'id' ) ==  'tmmengravingemail' && jQuery(this).is(':checked') ){
			jQuery( '#email_engraving_process' ).show();
			 uncheckall( 'tmmengravingemail' );
		}else if ( jQuery(this).attr( 'id' ) ==  'tmmengravingemail' && !jQuery(this).is(':checked') ){
			jQuery( '#email_engraving_process' ).hide();
		}
 });
 jQuery( '#tmmnoengraving' ).click( function (){
	 if( jQuery(this).is(':checked') ){
		 jQuery( '#tmmengravingdetail' ).prop( 'checked', false );
		 jQuery( '#tmmengravingemail' ).prop( 'checked', false );
		 jQuery( '#email_engraving_process' ).hide();
		 uncheckall( 'tmmnoengraving' );
		 jQuery( '.upload_error' ).remove();
		 jQuery( '#upload_issue' ).val(0);
	 }
 } );

  var existinglogo 	= jQuery( '#existinglogo' ).val();
  var forgetlogo 	= jQuery( '#tmmengravingnologo' ).is(':checked');
  var newlogo 		= jQuery( '#tmmengravingwithlogo' ).is(':checked');
	if ( typeof existinglogo !== 'undefined' && existinglogo != '' && existinglogo != 0  &&  !newlogo &&  !forgetlogo  ){
			jQuery( "#company_logo" ).find( 'img' ).attr( 'src', existinglogo );
			jQuery( '#downloadable' ).html( " <a href='"+existinglogo+"' download > download </a>" );
			var file_name_array = existinglogo.split( '/' );
			jQuery( '.company_logo_name' ).html( file_name_array.pop() );
			jQuery( "#company_logo" ).show();
			jQuery( "#tmmengravingexistinglogo ").prop( 'checked', true );
			
	}
	
  if ( newlogo == true ) {
      jQuery("#tmmengravinglogoupload").show();
	 var src =  jQuery('#company_logo').find('img').attr('src');
	 if( src == '' || src == 0 || typeof src === 'undefined' ){
		 jQuery( '#company_logo' ).hide();
	 }else{
		 jQuery( '#company_logo' ).show();
	 }
	  // if( jQuery.trim(show_preview) != 'yes' ){
		  // jQuery( '#company_logo' ).show();
	  // }else{
		  // jQuery( '#company_logo' ).hide();
	  // }
  } else {
      jQuery("#tmmengravinglogoupload").hide();
  }
  if ( forgetlogo == true ){
	  jQuery('#company_logo').hide();
	  
  }
  

  jQuery("#tmmengravingdetail").click(function(){
    jQuery("#tmm-logo-upload").show();
    if (jQuery('#tmmengravingwithlogo').is(':checked') == true) {
        jQuery("#tmmengravinglogoupload").show();
    } else {
        jQuery("#tmmengravinglogoupload").hide();
    }
  });

  jQuery("#tmmengravingemail, #tmmnoengraving ").click(function(){
    jQuery("#tmm-logo-upload").hide();
	if ( jQuery( this ).is(':checked') ){
		var step3 = jQuery( '#step_3_url' ).val();
		jQuery( '#tmmengravingsettings' ).attr('action',step3);
		 jQuery( '#step_2' ).attr( 'style', 'pointer-events: none;' );
	}
  });

  jQuery("#tmmengravingwithlogo").on( "click", function() {
    jQuery("#tmmengravinglogoupload").show();
	jQuery("#tmmengravingexistinglogo").prop('checked',false);
	jQuery("#tmmengravingnologo").prop('checked',false);
	jQuery(".errors_holder").remove();
		var self = jQuery('input[name="tmmengravinglogoupload[]"]');
		jQuery('#company_logo').find('img').attr('src','');
		jQuery( '#downloadable' ).html( " " );
		jQuery('#company_logo').hide();
		previewImage(self,'#company_logo');
		
  });
  jQuery("#tmmengravingexistinglogo, #tmmengravingnologo ").on( "click", function() {
    jQuery("#tmmengravinglogoupload").hide();
	jQuery("#tmmengravingwithlogo").prop('checked',false);
	if( jQuery(this).attr( 'id' ) == 'tmmengravingexistinglogo' ){
		jQuery("#tmmengravingnologo").prop('checked',false);
		var existinglogo = jQuery('#existinglogo').val();
		if( existinglogo != 0 && jQuery.trim(existinglogo) != '' ){
			jQuery("#company_logo").find('img').attr('src',existinglogo);
			jQuery( '#downloadable' ).html( " <a href='"+existinglogo+"' download > download </a>" );
			var file_name_array = existinglogo.split( '/' );
			jQuery( '.company_logo_name' ).html( file_name_array.pop() );
			jQuery("#company_logo").show();
		}else if( existinglogo == 0 || jQuery.trim(existinglogo) == '' ){
			jQuery('#company_logo').find('img').attr('src','');
			jQuery( '#downloadable' ).html( " " );
			jQuery('#company_logo').hide();
		}
	}
	if( jQuery(this).attr( 'id' ) == 'tmmengravingnologo' ){
		jQuery("#tmmengravingexistinglogo").prop('checked',false);
		jQuery("#company_logo").hide();
	}
	jQuery(".upload_error").remove();
	jQuery(".errors_holder").remove();
	jQuery( '#upload_issue' ).val(0);
	jQuery( '#trophy_upload_id' ).val('');
  });	
  jQuery("#tmmengravingsettingssubmit").on( "click", function() {
	  var canuploadlogo = jQuery('#tmmengravingwithlogo').is(':checked');
	  var existinglogo = jQuery('#tmmengravingexistinglogo').is(':checked');
	  var existing_logo = jQuery.trim(jQuery('#existinglogo').val());
	  var elementsarr = new Array();
	  var deliverydate 		= jQuery.trim( jQuery( '#deliverydate' ).val() );
	  var presentationdate 		= jQuery.trim( jQuery( '#presentationdate' ).val() );
	  var correct_date =  checkDate(deliverydate,presentationdate);
	  var src = jQuery.trim(jQuery("#company_logo").find('img').attr('src'));
	  if( canuploadlogo  &&  jQuery('input[name="tmmengravinglogoupload[]"]').val()  == '' && src == ''  ){
		  elementsarr.push('#tmmengravinglogoupload');
		   jQuery('.upload_error').remove();
		  jQuery('#tmmengravinglogoupload').after( '<div style="margin-top: 15%" class="upload_error errors_holder"><label  style="color:red;"> '+COMPANY_LOGO_MISSING+'</label> </div>' );
	  }
		jQuery('.deliverydate_alert').remove();
		jQuery('.no_existing_img_alert').remove();
		jQuery('.deliverydate_alert').remove();
		var login_status = jQuery( '#login_status' ).val(); 
	  if( existinglogo && existing_logo == '' && login_status == 1 ){
		  elementsarr.push('#tmm-logo-upload');
		  var html = " <div class='no_existing_img_alert errors_holder' style='text-aligm:center;color:red;width:100%;'> <span> No logo found for the logged user. </span> </div>";
		  jQuery('#tmm-logo-upload').after( html );
	  }
	  if( jQuery( '#upload_issue' ).val() == 1 ){
		   elementsarr.push('#tmmengravinglogoupload');
	  }
	  if( deliverydate == '' ){
		  var html = " <div class='deliverydate_alert errors_holder' style='text-aligm:center;color:red;width:100%;'> <span> Customer Date Required . </span> </div>";
		  jQuery('#deliverydate').after( html );
		  elementsarr.push('#deliverydate');
	  }
	  if( correct_date ){
		  var html = " <div class='deliverydate_alert errors_holder' style='text-aligm:center;color:red;width:100%;'> <span> Customer date required must be ahead than presentation date. </span> </div>";
		  jQuery('#deliverydate').after( html );
		  elementsarr.push('#deliverydate');
	  }
	  if( elementsarr.length > 0 ){
		  var lastElement = elementsarr.pop();
		  jQuery(lastElement).scroll();
		  return false;
	  }else{
		 jQuery("#tmmengravingsettings").submit();  
	  }
  });
 
 function checkDate(r1,p1){
	 var required_date = new Date( r1 ); 
	 var presentation_date = new Date( p1 ); 
	return (required_date > presentation_date);
 }

  jQuery("#tmmengravingdetailssubmit").on( "click", function() {
    jQuery("#tmmengravingdetailssubmit").submit();
  });



  jQuery("#deliverydate, #presentationdate").datepicker( {
    minDate: 0,
	todayHighlight: true,
	gotoCurrent: true,
  } );
  //billing
	var firstname = jQuery( '#billing_first_name' ).val();
	var lastname = jQuery( '#billing_last_name' ).val();
	jQuery( '#billing_company' ).val( firstname + " " + lastname);
	jQuery( '#billing_first_name' ).keyup( function (){
		var firstname = jQuery( this ).val();
		var lastname = jQuery( '#billing_last_name' ).val();
		jQuery( '#billing_company' ).val( firstname + " " + lastname);
	});
  
  jQuery( '#billing_last_name' ).keyup( function (){
	 var firstname = jQuery( '#billing_first_name' ).val();
	 var lastname = jQuery( this ).val();
	 jQuery( '#billing_company' ).val( firstname + " " + lastname);
  });
  //end
  //shipping
  jQuery( '#shipping_first_name' ).keyup( function (){
		var firstname = jQuery( this ).val();
		var lastname = jQuery( '#shipping_last_name' ).val();
		jQuery( '#shipping_company' ).val( firstname + " " + lastname);
	});
  
  jQuery( '#shipping_last_name' ).keyup( function (){
	 var firstname = jQuery( '#shipping_first_name' ).val();
	 var lastname = jQuery( this ).val();
	 jQuery( '#shipping_company' ).val( firstname + " " + lastname);
  });
  //end
  
  jQuery( '#ship-to-different-address-checkbox' ).click( function (){
	  if( jQuery( this ).is( ':checked' ) ){
		  var billing_first_name 	= jQuery( '#billing_first_name' ).val();
		  var billing_last_name 	= jQuery( '#billing_last_name' ).val();
		  var billing_company		= jQuery( '#billing_company' ).val();
		  var billing_address_1 	= jQuery( '#billing_address_1' ).val();
		  var billing_address_2 	= jQuery( '#billing_address_2' ).val();
		  var billing_city 			= jQuery( '#billing_city' ).val();
		  var billing_state 		= jQuery( '#billing_state' ).val();
		  var billing_country 		= jQuery( '#billing_country' ).val();
		  var billing_postcode 		= jQuery( '#billing_postcode' ).val();
		  jQuery( '#shipping_first_name' ).val( billing_first_name );
		  jQuery( '#shipping_last_name' ).val( billing_last_name );
		  jQuery( '#shipping_company' ).val( billing_company );
		  jQuery( '#shipping_country' ).val( billing_country );
		  jQuery( '#shipping_country' ).select2().trigger('change');
		  jQuery( '#shipping_address_1' ).val( billing_address_1 );
		  jQuery( '#shipping_address_2' ).val( billing_address_2 );
		  jQuery( '#shipping_city' ).val( billing_city );
		  jQuery( '#shipping_postcode' ).val( billing_postcode );
		  jQuery( '#shipping_state' ).val( billing_state );
		  jQuery( '#shipping_state' ).select2().trigger('change');
	  }
  });
  
  function uncheckall( self ){
	  var radio_id_array = [ 'tmmengravingdetail','tmmengravingemail','tmmnoengraving','tmmengravingwithlogo','tmmengravingexistinglogo','tmmengravingnologo' ];
	  for( var i = 0; i< radio_id_array.length;i++){
		  if( self != radio_id_array[i] ){
			jQuery( "#"+radio_id_array[i] ).prop('checked', false);
		  }
	  }
  }

});

//added on 16122019 Google Autocomplete

var placeSearch, autocomplete,shipping_autocomplete;

var componentForm = {
  billing_address_1: 'long_name',
  billing_address_2: 'short_name',
  billing_city: 'long_name',
  billing_state: 'short_name',
  billing_postcode: 'long_name',
  postal_code: 'short_name',
};
var componentForm2 = {
  shipping_address_1: 'long_name',
  shipping_address_2: 'short_name',
 shipping_city: 'long_name',
  shipping_state: 'short_name',
 shipping_postcode: 'long_name',
  shipping_code: 'short_name',
};

jQuery( document ).on('focus','#billing_address_1',function(){
	initAutocomplete();
});
jQuery( document ).on('focus','#shipping_address_1',function(){
	shippingAutocomplete();
});
function initAutocomplete() {
	var targetEle = jQuery( '#billing_country' );
	autocomplete = undefined;
	var billing_country = jQuery( '#billing_country' ).val();
	if( typeof billing_country !== 'undefined'  &&  billing_country != '' ){
		var filter = billing_country.toLowerCase();
	}else{
		var filter = 'aus';
	}
  autocomplete = new google.maps.places.Autocomplete(
      document.getElementById('billing_address_1'), {types: ['geocode'],componentRestrictions: {country: filter }});
	autocomplete.addListener('place_changed', fillInAddress);

		targetEle[0].addEventListener("focus", function(element){
				autocomplete.setComponentRestrictions( {country: filter });
		});
}
function shippingAutocomplete(){
		var targetEle = jQuery( '#shipping_country' );
		shipping_autocomplete = undefined;
		var shipping_country = jQuery( '#shipping_country' ).val();
		if( typeof shipping_country !== 'undefined' && shipping_country != '' ){
		var filter = shipping_country.toLowerCase();
	}else{
		var filter = 'aus';
	}
	 shipping_autocomplete = new google.maps.places.Autocomplete(
      document.getElementById('shipping_address_1'), {types: ['geocode'],componentRestrictions: {country: filter }});
	  shipping_autocomplete.addListener('place_changed', fillInAddress2);
	  targetEle[0].addEventListener("focus", function(element){
			autocomplete.setComponentRestrictions( {country: filter });
	  });
}

function fillInAddress() {
  var place = autocomplete.getPlace();
  for (var component in componentForm) {
	jQuery( '#'+component ).val( '' );
  }
  console.log( place )
  for (var i = 0; i < place.address_components.length; i++) {
    var addressType = place.address_components[i].types[0];
	if(addressType=='street_number' || addressType=='route'){
		addressType='billing_address_1';
	  } else if(addressType=='sublocality' || addressType=='sublocality_level_1' ){
		  addressType='billing_address_2';
	  } else if(addressType=='locality'){
		  addressType='billing_city';
	  } else if(addressType=='postal_code'){
		  addressType='billing_postcode';
	  } else if(addressType=='administrative_area_level_1'){
		  addressType='billing_state';
	  } 
    if (componentForm[addressType]) {
		  var val = place.address_components[i][componentForm[addressType]];
		  if( jQuery( '#'+addressType ).is('select')   ){
			   var selectOption = place.address_components[i]['short_name'];
					jQuery( '#'+addressType ).val( selectOption );
					jQuery( '#'+addressType ).select2().trigger('change');
		  }else{
			  if( jQuery( '#'+addressType ).val() != '' ){
					var txtbox = jQuery( '#'+addressType ).val() ;
					jQuery( '#'+addressType ).val( txtbox+", "+val );
				}else{
					jQuery( '#'+addressType ).val( val );
				}
		  }
    }
  }
}
jQuery(document).on('focus','#shipping_address_1',function(){
	shippingAutocomplete();
});
jQuery( document ).on( 'keyup blur click','.engraving_process_chars_lines',function (){
	var self = jQuery( this );
	if( self.is( 'input[type="text"]' ) && jQuery.trim( self.val() ) != ''  ){
		self.closest('li').find( 'input[type="checkbox"]' ).attr('disabled','disabled');
	}else if( self.is( 'input[type="text"]' )  ){
		self.closest('li').find( 'input[type="checkbox"]' ).removeAttr('disabled');
	}
	if( self.is( 'input[type="checkbox"]' ) && self.is( ':checked' ) ){
		self.closest('li').find( 'input[type="text"]' ).attr('readonly',true);
	}else if( self.is( 'input[type="checkbox"]' ) && !self.is( ':checked' ) ){
		self.closest('li').find( 'input[type="text"]' ).removeAttr('readonly');
	}
} );

function fillInAddress2() {
	if( typeof shipping_autocomplete === 'undefined'){
		return false;
	}
  var place = shipping_autocomplete.getPlace();
  for (var component in componentForm2) {
	jQuery( '#'+component ).val( '' );
  }
  for (var i = 0; i < place.address_components.length; i++) {
    var addressType = place.address_components[i].types[0];
	if(addressType=='street_number' || addressType=='route'){
		addressType='shipping_address_1';
	  } else if(addressType=='sublocality' || addressType=='sublocality_level_1' ){
		  addressType='shipping_address_2';
	  } else if(addressType=='locality'){
		  addressType='shipping_city';
	  } else if(addressType=='postal_code'){
		  addressType='shipping_postcode';
	  } else if(addressType=='administrative_area_level_1'){
		  addressType='shipping_state';
	  } 
    if (componentForm2[addressType]) {
		  var val = place.address_components[i][componentForm2[addressType]];
		  if( jQuery( '#'+addressType ).is('select')   ){
			   var selectOption = place.address_components[i]['short_name'];
					jQuery( '#'+addressType ).val( selectOption );
					jQuery( '#'+addressType ).select2().trigger('change');
		  }else{
			if( jQuery( '#'+addressType ).val() != '' ){
				var txtbox = jQuery( '#'+addressType ).val() ;
				jQuery( '#'+addressType ).val( txtbox+", "+val );
			}else{
				jQuery( '#'+addressType ).val( val );
			}
			  
		  }
    }
  }
}

// Bias the autocomplete object to the user's geographical location,
// as supplied by the browser's 'navigator.geolocation' object.
function geolocate() {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function(position) {
      var geolocation = {
        lat: position.coords.latitude,
        lng: position.coords.longitude
      };
      var circle = new google.maps.Circle(
          {center: geolocation, radius: position.coords.accuracy});
      autocomplete.setBounds(circle.getBounds());
    });
  }
}


function copyToNext(self,order,position,itemPosition){
	var inputValue = jQuery( self ).closest( '.engravedItems_'+itemPosition ).find("li:eq("+order+")").find('input[type="text"]').val();
	var input_attr = jQuery( self ).closest( '.engravedItems_'+itemPosition ).find("li:eq("+order+")").find('input[type="text"]').attr('readonly');
	var checkBoxElement = jQuery( self ).closest( '.engravedItems_'+itemPosition ).find("li:eq("+order+")").find('input[type="checkbox"]');
	var checkBox_attr = jQuery( self ).closest( '.engravedItems_'+itemPosition ).find("li:eq("+order+")").find('input[type="checkbox"]').attr( 'disabled' );
	var canCheckIt = jQuery(checkBoxElement).is(":checked");
	jQuery(  '.engravedItems_'+itemPosition ).each(function(i,element){
		if( i > position ){
			jQuery(this).find("li:eq("+order+")").find('input[type="text"]').val(inputValue);
			jQuery(this).find("li:eq("+order+")").find('input[type="checkbox"]').prop('checked',canCheckIt);
			jQuery(this).find("li:eq("+order+")").find('input[type="text"]').removeAttr('readonly');
			jQuery(this).find("li:eq("+order+")").find('input[type="checkbox"]').removeAttr( 'disabled' );
			if( typeof input_attr !== 'undefined' ){
				jQuery(this).find("li:eq("+order+")").find('input[type="text"]').attr('readonly','readonly')
			}
			if( typeof checkBox_attr !== 'undefined' ){
				jQuery(this).find("li:eq("+order+")").find('input[type="checkbox"]').attr('disabled','disabled')
			}
		}
	});
	
}

function previewImage(self,container){
	var validFormat = [ 'jpg','jpeg','png','cdr','eps','gif','ai' ];
	//console.log( jQuery(self)[0].files )
	var filesArr = jQuery(self)[0].files ;
	var downloadLink = '';
	var sizeerror = formaterror =  0;
	if( typeof filesArr !== 'undefined' && filesArr.length > 0 ){
		console.log( '------------filesArr---------------'+filesArr.length  );
		jQuery.each(filesArr, function(i, file){
			var imgname = file.name;
			var src = file.src;
			var extensionarray = imgname.split('.');
			var extension = extensionarray.pop();
			var size = file.size;
			if( ! validFormat.includes(extension) ) {
			//jQuery('#tmmengravinglogoupload').after( '<div style="margin-top: 15%" class="upload_error errors_holder"><label  style="color:red;"> Invalid file format. </label> </div>' );
			//jQuery( '#upload_issue' ).val(1);
			//jQuery(container).hide();
			//return false;
			formaterror ++;
			}
			if( size > 5000000 ){
				//jQuery('#tmmengravinglogoupload').after( '<div style="margin-top: 15%" class="upload_error errors_holder"><label  style="color:red;"> File size cannot exceed 5 MB. </label> </div>' );
				//jQuery( '#upload_issue' ).val(1);
				//jQuery(container).hide();
				//return false;
				sizeerror++;
			} 
		});
		if( sizeerror > 0 ){
			jQuery('#tmmengravinglogoupload').after( '<div style="margin-top: 15%" class="upload_error errors_holder"><label  style="color:red;"> File size cannot exceed 5 MB. </label> </div>' );
			jQuery( '#upload_issue' ).val(1);
			jQuery(container).hide();
			return false;
		}
		if( formaterror > 0 ){
			jQuery('#tmmengravinglogoupload').after( '<div style="margin-top: 15%" class="upload_error errors_holder"><label  style="color:red;"> Invalid file format. </label> </div>' );
			jQuery( '#upload_issue' ).val(1);
			jQuery(container).hide();
			return false;
		}
		jQuery( '.upload_error' ).remove();
		jQuery( '#upload_issue' ).val(0);
		jQuery('#downloadable').html('');
		jQuery('.company_logo_name').remove();
		jQuery.each(filesArr, function(i, file){
			
			 var reader = new FileReader();
			reader.onload = function(e) {
				  if( typeof container !== 'undefined' ){
						//jQuery(container).find('img').attr('src', e.target.result);
						//jQuery( '#downloadable' ).html( " <a href='"+e.target.result+"' download > download </a>" );
						//jQuery(container).show();
						//jQuery('.company_logo_name').html( jQuery(self)[0].files[0].name ); 
						downloadLink =  " <span> <a href='"+e.target.result+"' download > download </a> "+file.name+" </span> <br> " ;
						jQuery( '#downloadable' ).append( downloadLink );
						jQuery(container).show();
						//console.log( "=============downloadLink============"+downloadLink)
				  }
			}
			reader.readAsDataURL(file);
			
		});
		//var imgname = jQuery(self)[0].files[0].name;
		//var src = jQuery(self)[0].files[0].src;
		//var extensionarray = imgname.split('.');
		//var extension = extensionarray.pop();
		//var size = jQuery(self)[0].files[0].size;
		//jQuery( '.upload_error' ).remove();
		//jQuery( '#upload_issue' ).val(0);
		//console.log( jQuery(self)[0].files[0] )
	/*	if( ! validFormat.includes(extension) ) {
			jQuery('#tmmengravinglogoupload').after( '<div style="margin-top: 15%" class="upload_error errors_holder"><label  style="color:red;"> Invalid file format. </label> </div>' );
			jQuery( '#upload_issue' ).val(1);
			jQuery(container).hide();
			return false;
		}
		if( size > 5000000 ){
			jQuery('#tmmengravinglogoupload').after( '<div style="margin-top: 15%" class="upload_error errors_holder"><label  style="color:red;"> File size cannot exceed 5 MB. </label> </div>' );
			jQuery( '#upload_issue' ).val(1);
			jQuery(container).hide();
			return false;
		} */
		/* var reader = new FileReader();
		  reader.onload = function(e) {
			  if( typeof container !== 'undefined' ){
					jQuery(container).find('img').attr('src', e.target.result);
					jQuery( '#downloadable' ).html( " <a href='"+e.target.result+"' download > download </a>" );
					jQuery(container).show();
					jQuery('.company_logo_name').html( jQuery(self)[0].files[0].name );
			  }
			}
			var file = jQuery(self)[0].files[0];
			reader.readAsDataURL(file);*/
			
			return true;
	}
	return false
}

function redirectUrl(self,url){
	
	var isChecked = jQuery(self).is(':checked');
	var amICheckBox = jQuery(self).is(':checkbox');
	if( isChecked && amICheckBox ){
		window.location.href = url;
	}
	if(!amICheckBox){
		window.location.href = url;
	}
	return false;
}

function stepSubmit( self,step ){
	if( step == 2 ) {
		if( jQuery('#tmmengravingsettingssubmit').length > 0 ){
			jQuery('#tmmengravingsettingssubmit').trigger('click');
		}else{
			 window.location.href = jQuery(self).closest('a').attr('data-link') ;
		}
	}
	
	if( step == 3 ) {
		if( jQuery('#tmmengravingdetailssubmit').length > 0 ){
			jQuery('#tmmengravingdetailssubmit').trigger('click');
		}else if (jQuery( '#tmmengravingsettingssubmit' ).length > 0 ){
			jQuery('#tmmengravingsettingssubmit').trigger('click');
		} else{
			 window.location.href = jQuery(self).closest('a').attr('data-link') ;
		}
	}
}
