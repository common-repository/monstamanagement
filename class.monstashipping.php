<?php
class Monstashipping {

	private static $initiated = false;

	public static function init() {
		if ( ! self::$initiated ) {
			self::init_hooks();
		}
	}
	/**
	 * Initializes WordPress hooks
	 */
	private static function init_hooks() {
		self::$initiated = true;
		add_filter( 'woocommerce_package_rates', array( 'Monstashipping', 'monstamanagement_shipping_costs' ), 20, 2 );
	}

	public static function monstamanagement_shipping_costs( $rates, $package ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return $rates;
		$ip 		= null;
		$api_key 	= get_option( 'trophymonsta_api_key' );
		$response 	= Trophymonsta::http_get( Trophymonsta::build_query( array( 'key' => $api_key, 'site_url' => site_url() ) ), 'store/checkFreightCharge', $ip );
		$response	= json_decode($response);
		if ($response->code == '400' || $response->code == '403' || $response->code == '404' || $response->code == '405') {
			return $rates;
		}
		$monstamanagement_product = 0;
		if ( isset($package) && count($package)>0 ) {
			foreach( $package['contents'] as $key=>$contents ) {
				if ( isset($contents['product_id']) && $contents['product_id']>0 ) {
					$custompostmeta = get_post_meta( $contents['product_id'], '_trophymonsta_text_field', true );
					if ( $custompostmeta == 'trophymonsta' ) {
						$monstamanagement_product = 1;
						break;
					}
				}
			}
		}

		if ($monstamanagement_product==0) { // return normal shipping if not monstamanagement product
			foreach( $rates as $rate_key => $rate ){
				$monstalabel = strtolower($rate->label);
				if ( 'free_shipping' === $rate->method_id ) {
					unset( $rates[$rate->id] );
				}
			}

			return $rates;
		}
		$monsta_cart_subtotal 				= $package['cart_subtotal'];
		$monstamanagement_freight_charge	= 0;
		$freeshipping 						= array();
		$freeshipping_key 					= '';
		$faltrate_key 						= '';
		foreach( $rates as $rate_key => $rate ){
			if ( $rate->method_id != 'free_shipping' && 'flat_rate' === $rate->method_id ) {
				$monstamanagement_freight_charge = $rate->cost;
				$faltrate_key					 = $rate_key;
			} else if ( 'free_shipping' === $rate->method_id ) {
				$freeshipping[ $rate_key ] 	= $rate;
				$freeshipping_key 			= $rate_key;
			}
		}
		if ($response->code == '200') {
			if( $response->success->flat_fee>0 ) {
				$monstamanagement_freight_charge = $response->success->flat_fee;
			} else {
				$monstafreightcharge =  $response->success->freight_charge;
				foreach ($monstafreightcharge as $key => $charge) {
					if ($monsta_cart_subtotal > $charge->sales_price && $charge->sales_price>0) {
						$monstamanagement_freight_charge = $charge->freight_charge;
					}
				}
			}

			if ($monstamanagement_freight_charge <1) {
				 unset( $rates[$faltrate_key] );
				// To unset all methods except for free_shipping, do the following
				if(isset($freeshipping)) {
					//$rates	= $freeshipping;
				}
			} else {
				unset( $rates[$freeshipping_key] );
			}
		}

		// New shipping cost (can be calculated)
		$tax_rate = 0.0;
		foreach( $rates as $rate_key => $rate ){
			// Excluding free shipping methods
			if ( $rate->method_id != 'free_shipping' && 'flat_rate' === $rate->method_id ){
				// Set rate cost
				$rates[$rate_key]->cost = $monstamanagement_freight_charge;
				// Set taxes rate cost (if enabled)
				$taxes = array();
				foreach ($rates[$rate_key]->taxes as $key => $tax){
					if( $rates[$rate_key]->taxes[$key] > 0 )
						$taxes[$key] = $monstamanagement_freight_charge * $tax_rate;
				}
				$rates[$rate_key]->taxes = $taxes;
			}
		}
		return $rates;
	}
}
?>
