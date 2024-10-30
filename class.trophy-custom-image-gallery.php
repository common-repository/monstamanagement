<?php
class Trophy_Custom_Image_Gallery  {
	public function __construct() {
		do_action( 'woo_variation_gallery_loaded', $this );
	}
	
	public static function enqueue_scripts() {
				//$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
				$suffix = '';
				$single_image_width     = absint( wc_get_theme_support( 'single_image_width', get_option( 'woocommerce_single_image_width', 600 ) ) );
				$gallery_thumbnails_gap = absint( get_option( 'woo_variation_gallery_thumbnails_gap', apply_filters( 'woo_variation_gallery_default_thumbnails_gap', 0 ) ) );
				$gallery_width          = absint( get_option( 'woo_variation_gallery_width', apply_filters( 'woo_variation_gallery_default_width', 30 ) ) );
				$gallery_margin         = absint( get_option( 'woo_variation_gallery_margin', apply_filters( 'woo_variation_gallery_default_margin', 30 ) ) );
				$gallery_medium_device_width      = absint( get_option( 'woo_variation_gallery_medium_device_width', apply_filters( 'woo_variation_gallery_medium_device_width', 0 ) ) );
				$gallery_small_device_width       = absint( get_option( 'woo_variation_gallery_small_device_width', apply_filters( 'woo_variation_gallery_small_device_width', 720 ) ) );
				$gallery_extra_small_device_width = absint( get_option( 'woo_variation_gallery_extra_small_device_width', apply_filters( 'woo_variation_gallery_extra_small_device_width', 320 ) ) );
				
				
				//if ( wvg_is_ie11() ) {
					//wp_enqueue_script( 'bluebird', $this->assets_uri( "js/variation-image/bluebird{$suffix}.js" ), array(), '3.5.3' );
				//}
				
				wp_enqueue_script( 'woo-variation-gallery-slider', esc_url( self::assets_uri( "js/variation-image/slick{$suffix}.js" ) ), array( 'jquery' ), '1.8.1', true );
				
				wp_enqueue_style( 'woo-variation-gallery-slider', esc_url( self::assets_uri( "css/variation-image/slick{$suffix}.css" ) ), array(), '' );
				
				wp_enqueue_script( 'woo-variation-gallery', esc_url( self::assets_uri( "js/variation-image/frontend{$suffix}.js" ) ), array( 'jquery', 'wp-util', 'woo-variation-gallery-slider', 'imagesloaded' ), '', true );
				
				wp_localize_script( 'woo-variation-gallery', 'woo_variation_gallery_options', apply_filters( 'woo_variation_gallery_js_options', array(
					'gallery_reset_on_variation_change' => ( 'yes' === get_option( 'woo_variation_gallery_reset_on_variation_change', 'yes' ) ),
					'enable_gallery_zoom'               => ( 'yes' === get_option( 'woo_variation_gallery_zoom', 'yes' ) ),
					'enable_gallery_lightbox'           => ( 'yes' === get_option( 'woo_variation_gallery_lightbox', 'yes' ) ),
					'enable_gallery_preload'            => ( 'yes' === get_option( 'woo_variation_gallery_image_preload', 'yes' ) ),
					'enable_thumbnail_slide'            => false,
					'gallery_thumbnails_columns'        => absint( get_option( 'woo_variation_gallery_thumbnails_columns', apply_filters( 'woo_variation_gallery_default_thumbnails_columns', 4 ) ) ),
					'is_vertical'                       => false,
					'is_mobile'                         => function_exists( 'wp_is_mobile' ) && wp_is_mobile(),
					'gallery_default_device_width'      => $gallery_width,
					'gallery_medium_device_width'       => $gallery_medium_device_width,
					'gallery_small_device_width'        => $gallery_small_device_width,
					'gallery_extra_small_device_width'  => $gallery_extra_small_device_width,
				) ) );
				
				// Stylesheet
				wp_enqueue_style( 'woo-variation-gallery', esc_url( self::assets_uri( "css/variation-image/frontend{$suffix}.css" ) ), array( 'dashicons' ), '' );
				
				wp_enqueue_style( 'woo-variation-gallery-theme-support', esc_url( self::assets_uri( "css/variation-image/theme-support{$suffix}.css" ) ), array( 'woo-variation-gallery' ),'' );
				
			}
			
			public static function assets_uri( $file ) {
						return TROPHYMONSTA_PLUGIN_URL. $file;
			}
			public static function wvg_get_product_default_attributes( $product_id ) {
						
						$product = wc_get_product( $product_id );
						
						if ( ! $product->is_type( 'variable' ) ) {
							return array();
						}
						
						$variable_product = new WC_Product_Variable( absint( $product_id ) );
						
						return $variable_product->get_default_attributes();
			}
			public static function wvg_get_product_default_variation_id( $product, $attributes ) {
					
					if ( is_numeric( $product ) ) {
						$product = wc_get_product( $product );
					}
					
					if ( ! $product->is_type( 'variable' ) ) {
						return 0;
					}
					
					foreach ( $attributes as $key => $value ) {
						if ( strpos( $key, 'attribute_' ) === 0 ) {
							continue;
						}
						
						unset( $attributes[ $key ] );
						$attributes[ sprintf( 'attribute_%s', $key ) ] = $value;
					}
					
					$data_store = WC_Data_Store::load( 'product' );
					
					return $data_store->find_matching_product_variation( $product, $attributes );
				}
			public static function wvg_get_product_variation( $product_id, $variation_id ) {
				$variable_product = new WC_Product_Variable( absint( $product_id ) );
				$variation        = $variable_product->get_available_variation( absint( $variation_id ) );
				//echo "<pre>";print_r($variation); echo "</pre>";
				return $variation;
			}
			public static function wvg_get_gallery_image_html( $attachment_id, $options = array() ,$terms_id = array() ) {
			
				$defaults = array( 'is_main_thumbnail' => false, 'has_only_thumbnail' => false );
				$options  = wp_parse_args( $options, $defaults );
				
				$image = self::wvg_get_gallery_image_props( $attachment_id );
				
				$classes = apply_filters( 'wvg_gallery_image_html_class', array(
					'wvg-gallery-image',
				), $attachment_id, $image );
				
				
				$template = '<div class="wvg-single-gallery-image-container"><img width="%d" height="%d" src="%s" class="%s" alt="%s" title="%s" data-caption="%s" data-src="%s" data-large_image="%s" data-large_image_width="%d" data-large_image_height="%d" srcset="%s" sizes="%s" /></div>';
				
				$inner_html = sprintf( $template, $image[ 'src_w' ], $image[ 'src_h' ], $image[ 'src' ], $image[ 'class' ], $image[ 'alt' ], $image[ 'title' ], $image[ 'caption' ], $image[ 'full_src' ], $image[ 'full_src' ], $image[ 'full_src_w' ], $image[ 'full_src_h' ], $image[ 'srcset' ], $image[ 'sizes' ] );
				
				$inner_html = apply_filters( 'woo_variation_gallery_image_inner_html', $inner_html, $image, $template, $attachment_id, $options );
				
				// If require thumbnail
				if ( ! $options[ 'is_main_thumbnail' ] ) {
					$classes = apply_filters( 'woo_variation_gallery_thumbnail_image_html_class', array(
						'wvg-gallery-thumbnail-image',
					), $attachment_id, $image );
					
					/*if ( $loop_index < 1 ) {
					//	$classes[] = 'current-thumbnail';
					}*/
					
					$template   = '<img width="%d" height="%d" src="%s" class="%s" alt="%s" title="%s" />';
					$inner_html = sprintf( $template, $image[ 'gallery_thumbnail_src_w' ], $image[ 'gallery_thumbnail_src_h' ], $image[ 'gallery_thumbnail_src' ], $image[ 'gallery_thumbnail_class' ], $image[ 'alt' ], $image[ 'title' ] );
					//echo "<pre>";print_r($inner_html);echo "</pre>";die;
					$inner_html = apply_filters( 'woo_variation_gallery_thumbnail_image_inner_html', $inner_html, $image, $template, $attachment_id, $options );
				}
				return '<div class="' . esc_attr( implode( ' ', array_unique( $classes ) ) ) . '"><div>' . $inner_html . '</div></div>';
			}
			public static function wvg_get_gallery_image_props( $attachment_id, $product_id = false ) {
				$props      = array(
					'image_id'                => '',
					'title'                   => '',
					'caption'                 => '',
					'url'                     => '',
					'alt'                     => '',
					'full_src'                => '',
					'full_src_w'              => '',
					'full_src_h'              => '',
					'full_class'              => '',
					//'full_srcset'              => '',
					//'full_sizes'               => '',
					'gallery_thumbnail_src'   => '',
					'gallery_thumbnail_src_w' => '',
					'gallery_thumbnail_src_h' => '',
					'gallery_thumbnail_class' => '',
					//'gallery_thumbnail_srcset' => '',
					//'gallery_thumbnail_sizes'  => '',
					'archive_src'             => '',
					'archive_src_w'           => '',
					'archive_src_h'           => '',
					'archive_class'           => '',
					//'archive_srcset'           => '',
					//'archive_sizes'            => '',
					'src'                     => '',
					'class'                   => '',
					'src_w'                   => '',
					'src_h'                   => '',
					'srcset'                  => '',
					'sizes'                   => '',
				);
				//echo "==========attachment_id============>".$attachment_id;echo "<br>";
				$attachment = get_post( $attachment_id );
				//echo "<pre>";print_r($attachment);echo "</pre>";
				if( $_SERVER[ 'REMOTE_ADDR' ] == '172.21.4.147'  ){
					//echo "======>$attachment_id<============";
					//echo wp_get_attachment_url( $attachment_id );
				}
				if ( $attachment ) {
					
					$props[ 'image_id' ] = $attachment_id;
					$props[ 'title' ]    = wp_strip_all_tags( $attachment->post_title );
					$props[ 'caption' ]  = wp_strip_all_tags( $attachment->post_excerpt );
					$props[ 'url' ]      = wp_get_attachment_url( $attachment_id );
					
					// Alt text.
					$alt_text = array( wp_strip_all_tags( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ), $props[ 'caption' ], wp_strip_all_tags( $attachment->post_title ) );
					
					if ( $product_id ) {
						$product    = wc_get_product( $product_id );
						$alt_text[] = wp_strip_all_tags( get_the_title( $product->get_id() ) );
					}
					
					$alt_text       = array_filter( $alt_text );
					$props[ 'alt' ] = isset( $alt_text[ 0 ] ) ? $alt_text[ 0 ] : '';
					
					// Large version.
					$full_size             = apply_filters( 'woocommerce_gallery_full_size', apply_filters( 'woocommerce_product_thumbnails_large_size', 'full' ) );
					$full_size_src         = wp_get_attachment_image_src( $attachment_id, $full_size );
					$props[ 'full_src' ]   = $full_size_src[ 0 ];
					$props[ 'full_src_w' ] = $full_size_src[ 1 ];
					$props[ 'full_src_h' ] = $full_size_src[ 2 ];
					
					$full_size_class = $full_size;
					if ( is_array( $full_size_class ) ) {
						$full_size_class = implode( 'x', $full_size_class );
					}
					
					$props[ 'full_class' ] = "attachment-$full_size_class size-$full_size_class";
					//$props[ 'full_srcset' ] = wp_get_attachment_image_srcset( $attachment_id, $full_size );
					//$props[ 'full_sizes' ]  = wp_get_attachment_image_sizes( $attachment_id, $full_size );
					
					
					// Gallery thumbnail.
					$gallery_thumbnail                  = wc_get_image_size( 'gallery_thumbnail' );
					$gallery_thumbnail_size             = apply_filters( 'woocommerce_gallery_thumbnail_size', array( $gallery_thumbnail[ 'width' ], $gallery_thumbnail[ 'height' ] ) );
					$gallery_thumbnail_src              = wp_get_attachment_image_src( $attachment_id, $gallery_thumbnail_size );
					//echo "<pre>";print_r($gallery_thumbnail_src);echo "</pre>";
					$props[ 'gallery_thumbnail_src' ]   = $gallery_thumbnail_src[ 0 ];
					//$props[ 'gallery_thumbnail_src_w' ] = $gallery_thumbnail_src[ 1 ];
					$props[ 'gallery_thumbnail_src_w' ] = 200;
					//$props[ 'gallery_thumbnail_src_h' ] = $gallery_thumbnail_src[ 2 ];
					$props[ 'gallery_thumbnail_src_h' ] = 200;
					
					$gallery_thumbnail_class = $gallery_thumbnail_size;
					if ( is_array( $gallery_thumbnail_class ) ) {
						$gallery_thumbnail_class = implode( 'x', $gallery_thumbnail_class );
					}
					
					$props[ 'gallery_thumbnail_class' ] = "attachment-$gallery_thumbnail_class size-$gallery_thumbnail_class";
					//$props[ 'gallery_thumbnail_srcset' ] = wp_get_attachment_image_srcset( $attachment_id, $gallery_thumbnail );
					//$props[ 'gallery_thumbnail_sizes' ]  = wp_get_attachment_image_sizes( $attachment_id, $gallery_thumbnail );
					
					
					// Archive/Shop Page version.
					$thumbnail_size           = apply_filters( 'woocommerce_thumbnail_size', 'woocommerce_thumbnail' );
					$thumbnail_size_src       = wp_get_attachment_image_src( $attachment_id, $thumbnail_size );
					$props[ 'archive_src' ]   = $thumbnail_size_src[ 0 ];
					$props[ 'archive_src_w' ] = $thumbnail_size_src[ 1 ];
					$props[ 'archive_src_h' ] = $thumbnail_size_src[ 2 ];
					
					$archive_thumbnail_class = $thumbnail_size;
					if ( is_array( $archive_thumbnail_class ) ) {
						$archive_thumbnail_class = implode( 'x', $archive_thumbnail_class );
					}
					
					$props[ 'archive_class' ] = "attachment-$archive_thumbnail_class size-$archive_thumbnail_class";
					//$props[ 'archive_srcset' ] = wp_get_attachment_image_srcset( $attachment_id, $thumbnail_size );
					//$props[ 'archive_sizes' ]  = wp_get_attachment_image_sizes( $attachment_id, $thumbnail_size );
					
					
					// Image source.
					$image_size       = apply_filters( 'woocommerce_gallery_image_size', 'woocommerce_single' );
					$src              = wp_get_attachment_image_src( $attachment_id, $image_size );
					$props[ 'src' ]   = $src[ 0 ];
					$props[ 'src_w' ] = $src[ 1 ];
					$props[ 'src_h' ] = $src[ 2 ];
					
					$image_size_class = $image_size;
					if ( is_array( $image_size_class ) ) {
						$image_size_class = implode( 'x', $image_size_class );
					}
					$props[ 'class' ]  = "wp-post-image wvg-post-image attachment-$image_size_class size-$image_size_class ";
					$props[ 'srcset' ] = wp_get_attachment_image_srcset( $attachment_id, $image_size );
					$props[ 'sizes' ]  = wp_get_attachment_image_sizes( $attachment_id, $image_size );
				}
				
				return apply_filters( 'woo_variation_gallery_get_image_props', $props, $attachment_id, $product_id );
			}
			public static function wc_get_gallery_image_html( $attachment_id, $main_image = false ) {
				$flexslider        = (bool) apply_filters( 'woocommerce_single_product_flexslider_enabled', get_theme_support( 'wc-product-gallery-slider' ) );
				$gallery_thumbnail = wc_get_image_size( 'gallery_thumbnail' );
				$thumbnail_size    = apply_filters( 'woocommerce_gallery_thumbnail_size', array( $gallery_thumbnail['width'], $gallery_thumbnail['height'] ) );
				$image_size        = apply_filters( 'woocommerce_gallery_image_size', $flexslider || $main_image ? 'woocommerce_single' : $thumbnail_size );
				$full_size         = apply_filters( 'woocommerce_gallery_full_size', apply_filters( 'woocommerce_product_thumbnails_large_size', 'full' ) );
				$thumbnail_src     = wp_get_attachment_image_src( $attachment_id, $thumbnail_size );
				$full_src          = wp_get_attachment_image_src( $attachment_id, $full_size );
				$alt_text          = trim( wp_strip_all_tags( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) );
				$image             = wp_get_attachment_image(
				$attachment_id,
				$image_size,
				false,
				apply_filters(
					'woocommerce_gallery_image_html_attachment_image_params',
					array(
						'title'                   => _wp_specialchars( get_post_field( 'post_title', $attachment_id ), ENT_QUOTES, 'UTF-8', true ),
						'data-caption'            => _wp_specialchars( get_post_field( 'post_excerpt', $attachment_id ), ENT_QUOTES, 'UTF-8', true ),
						'data-src'                => esc_url( $full_src[0] ),
						'data-large_image'        => esc_url( $full_src[0] ),
						'data-large_image_width'  => esc_attr( $full_src[1] ),
						'data-large_image_height' => esc_attr( $full_src[2] ),
						'class'                   => esc_attr( $main_image ? 'wp-post-image' : '' ),
					),
					$attachment_id,
					$image_size,
					$main_image
				)
			);

			return '<div data-thumb="' . esc_url( $thumbnail_src[0] ) . '" data-thumb-alt="' . esc_attr( $alt_text ) . '" class="woocommerce-product-gallery__image"><a href="' . esc_url( $full_src[0] ) . '">' . $image . '</a></div>';
		}
		public static function wvg_generate_inline_style( $styles = array() ) {
			
			$generated = array();
			
			foreach ( $styles as $property => $value ) {
				$generated[] = "{$property}: $value";
			}
			
			return implode( '; ', array_unique( apply_filters( 'wvg_generate_inline_style', $generated ) ) );
		}
	public static function wvg_get_default_gallery() {
		$product_id = absint( $_POST[ 'product_id' ] );
		
		$images = self::wvg_get_default_gallery_images( $product_id );
		
		wp_send_json_success( apply_filters( 'wvg_get_default_gallery', $images, $product_id ) );
	}
	public static function wvg_get_available_variation_images( $product_id = false ) {
			$product_id           = $product_id ? $product_id : absint( $_POST[ 'product_id' ] );
			$images               = array();
			$product = wc_get_product( absint( $product_id ) );
			$available_variations = self::wvg_get_product_variations( $product_id );
			if( count($available_variations) > 0 ){
				foreach ( $available_variations as $i => $variation ) {
					array_push( $variation[ 'variation_gallery_images' ], $variation[ 'image' ] );
				}
				foreach ( $available_variations as $i => $variation ) {
					foreach ( $variation[ 'variation_gallery_images' ] as $image ) {
						array_push( $images, $image );
					}
				}
			}
			//Custom Code
			if( count($images) == 0  && isset( $available_variations[0][ 'variation_id' ] ) ){
				$variation_id = $available_variations[0][ 'variation_id' ];
				$variation_image_array = get_post_meta( $variation_id, 'monsta_variation_gallery_images',true );
				array_push($variation_image_array,$variation_id);
				if( count($variation_image_array) > 0 ){
					foreach( $variation_image_array as $k => $variation_images_id ){
						$image_temp = self::wvg_get_gallery_image_props( $variation_images_id );
						array_push( $images, $image_temp );
					}
				}
			}
			//End
			
			wp_send_json_success( apply_filters( 'wvg_get_available_variation_images', $images, $product_id ) );
	}
	
	public static function wvg_get_default_gallery_images( $product_id , $raw = false ) {
			
			$images = array();
			$product        = wc_get_product( $product_id );
			if(!$product ){
				return apply_filters( 'wvg_get_default_gallery_images', $images );
			}
			$product_id     = $product->get_id();
			$gallery_images = $product->get_gallery_image_ids();
			$default_image  = $product->get_image_id();
			
			
			/*if ( has_post_thumbnail( $product_id ) ) {
				array_unshift( $gallery_images, get_post_thumbnail_id( $product_id ) );
			}*/
			
			if ( ! empty( $default_image ) ) {
				array_unshift( $gallery_images, $default_image );
			}
			
			
			if ( is_array( $gallery_images ) && ! empty( $gallery_images ) ) {
				
				foreach ( $gallery_images as $i => $image_id ) {
					$images[ $i ] = self::wvg_get_gallery_image_props( $image_id );
				}
			}
			if( $raw ){
				return json_encode($images);
			}
			
			return apply_filters( 'wvg_get_default_gallery_images', $images, $product );
	}
	public static function wvg_get_product_variations( $product ) {
			if ( is_numeric( $product ) ) {
				$product = wc_get_product( absint( $product ) );
				if( $product ){
					return $product->get_available_variations();
				}else{
					$empty = array();
					return $empty;
				}
			}
	}
	public static function slider_template_js() {
			ob_start();
			require_once TROPHYMONSTA_PLUGIN_DIR.'templates/slider-template-js.php';
			$data = ob_get_clean();
			echo apply_filters( 'woo_variation_gallery_slider_template_js', $data );
	}
	public static function thumbnail_template_js() {
		ob_start();
		require_once TROPHYMONSTA_PLUGIN_DIR.'templates/thumbnail-template-js.php';
		$data = ob_get_clean();
		echo apply_filters( 'woo_variation_gallery_thumbnail_template_js', $data );
	}
	public static function wvg_available_variation_gallery( $available_variation, $variationProductObject, $variation ) {
			
			$product_id         = absint( $variation->get_parent_id() );
			$variation_id       = absint( $variation->get_id() );
			$variation_image_id = absint( $variation->get_image_id() );
			
			$has_variation_gallery_images = (bool) get_post_meta( $variation_id, 'monsta_variation_gallery_images', true );
			//  $product                      = wc_get_product( $product_id );
			
			if ( $has_variation_gallery_images ) {
				$gallery_images = (array) get_post_meta( $variation_id, 'monsta_variation_gallery_images', true );
			} else {
				// $gallery_images = $product->get_gallery_image_ids();
				$gallery_images = $variationProductObject->get_gallery_image_ids();
			}
			
			
			if ( $variation_image_id ) {
				// Add Variation Default Image
				array_unshift( $gallery_images, $variation_image_id );
			} else {
				// Add Product Default Image
				
				/*if ( has_post_thumbnail( $product_id ) ) {
					array_unshift( $gallery_images, get_post_thumbnail_id( $product_id ) );
				}*/
				$parent_product          = wc_get_product( $product_id );
				$parent_product_image_id = $parent_product->get_image_id();
				
				if ( ! empty( $parent_product_image_id ) ) {
					array_unshift( $gallery_images, $parent_product_image_id );
				}
			}
			
			$available_variation[ 'variation_gallery_images' ] = array();
			
			foreach ( $gallery_images as $i => $variation_gallery_image_id ) {
				$available_variation[ 'variation_gallery_images' ][ $i ] = self::wvg_get_gallery_image_props( $variation_gallery_image_id );
			}
			
			return apply_filters( 'wvg_available_variation_gallery', $available_variation, $variation, $product_id );
		}
	
}
return new Trophy_Custom_Image_Gallery;

	
