<?php
	defined( 'ABSPATH' ) or die( 'Keep Quit' );
?>

<script type="text/html" id="tmpl-woo-variation-gallery-slider-template">
    <div class="wvg-gallery-image">
        <div>
            <# if( data.srcset ){ #>
            <div class="wvg-single-gallery-image-container">
                <img class="{{data.class}}" width="{{data.src_w}}" height="{{data.src_h}}" src="{{data.src}}" alt="{{data.alt}}" title="{{data.title}}" data-caption="{{data.caption}}" data-src="{{data.full_src}}" data-large_image="{{data.full_src}}" data-large_image_width="{{data.full_src_w}}" data-large_image_height="{{data.full_src_h}}" srcset="{{data.srcset}}" sizes="{{data.sizes}}"/>
            </div>
            <# } #>

            <# if( !data.srcset ){ #>
            <div class="wvg-single-gallery-image-container">
                <img class="{{data.class}}" width="{{data.src_w}}" height="{{data.src_h}}" src="{{data.src}}" alt="{{data.alt}}" title="{{data.title}}" data-caption="{{data.caption}}" data-src="{{data.full_src}}" data-large_image="{{data.full_src}}" data-large_image_width="{{data.full_src_w}}" data-large_image_height="{{data.full_src_h}}" sizes="{{data.sizes}}"/>
            </div>
            <# } #>
        </div>
    </div>
</script>