<form name="trophymonsta_activate" action="<?php echo TROPHYMONSTA_API_URL; ?>freetrial/add/" method="GET" target="_blank">
	<input type="submit" class="<?php echo isset( $classes ) && count( $classes ) > 0 ? implode( ' ', $classes ) : 'trophymonsta-button';?>" value="<?php echo esc_attr( $text ); ?>"/>
</form>
