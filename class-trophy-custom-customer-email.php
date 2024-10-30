<?php
/**
 * Class WC_Email_New_Order file
 *
 * @package WooCommerce\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'WC_Email' ) ) {
	return;
}

if ( ! class_exists( 'Trophy_Custom_Email_Order' ) ) :


	class Trophy_Custom_Email_Order extends WC_Email {

		/**
		 * Constructor.
		 */
	  public static $mail_count = 0;
		public function __construct() {
			$this->id             = 'customer_processing_order';
			$this->customer_email = true;

			$this->title          = __( 'Processing order', 'woocommerce' );
			$this->description    = __( 'This is an order notification sent to customers containing order details after payment.', 'woocommerce' );
			$this->template_base   = TROPHYMONSTA_PLUGIN_DIR.'templates/';
			$this->template_html  = 'emails/custom-processing-order-template.php';
			$this->template_plain = 'emails/plain/custom-processing-order-template.php';
			$this->placeholders   = array(
				'{order_date}'   => '',
				'{order_number}' => '',
			);
			// Triggers for this email.
			add_action( 'woocommerce_order_status_pending_to_processing_notification', array( $this, 'trigger' ), 10, 2 );
			add_action( 'woocommerce_order_status_pending_to_on-hold_notification', array( $this , 'trigger' ), 10, 2 );
		
			//on 20-02-2020
			//add_action( 'woocommerce_order_status_pending_to_on-hold_notification', array( $this, 'trigger' ), 10, 2 );
			//add_action( 'woocommerce_order_status_failed_to_on-hold_notification', array( $this, 'trigger' ), 10, 2 );
			//add_action( 'woocommerce_order_status_cancelled_to_on-hold_notification', array( $this, 'trigger' ), 10, 2 );
			//end
			//self::$mail_count = 0;
			//remove_action( 'woocommerce_email_order_details', array( 'WC_Emails', 'order_details' ), 10, 4 );
			//add_action( 'woocommerce_email_order_details', array( $this, 'order_details' ), 10, 4 );
			// Call parent constructor.
			parent::__construct();

			// Other settings.
			$this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
		}


		public function trigger( $order_id, $order = false ) {
			$this->setup_locale();

			if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
				$order = wc_get_order( $order_id );
			}

			if ( is_a( $order, 'WC_Order' ) ) {
				$this->object                         = $order;
				$this->recipient                      = $this->object->get_billing_email();
				$this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
				$this->placeholders['{order_number}'] = $this->object->get_order_number();
			}
			
			if ( $this->is_enabled() && $this->get_recipient() && self::$mail_count == 0 ) { //&& self::$mail_count == 0
				self::$mail_count++;
				$this->send( $this->get_recipient(),$this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() ); //$this->get_subject()
			}

			$this->restore_locale();
		}


		/**
		 * Get content html.
		 *
		 * @return string
		 */
		public function get_content_html() {
			return wc_get_template_html(
				$this->template_html,
				array(
					'order'              => $this->object,
					'email_heading'      => "Thank you for your order",//$this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin'      => true,
					'plain_text'         => false,
					'email'              => $this,
				), '', $this->template_base
			);
		}



		/**
		 * Get content plain.
		 *
		 * @return string
		 */
		public function get_content_plain() {
			return wc_get_template_html(
				$this->template_plain,
				array(
					'order'              => $this->object,
					'email_heading'      => "Thank you for your order",//$this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin'      => true,
					'plain_text'         => true,
					'email'              => $this,
				) , '', $this->template_base
			);
		}

		/**
		 * Default content to show below main email content.
		 *
		 * @since 3.7.0
		 * @return string
		 */
		public function get_default_additional_content() {
			return __( 'Thanks for using {site_address}!', 'woocommerce' );
		}


	}

endif;

return new Trophy_Custom_Email_Order();
