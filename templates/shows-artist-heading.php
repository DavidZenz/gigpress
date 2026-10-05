<?php
	
// 	STOP! DO NOT MODIFY THIS FILE!
//	If you wish to customize the output, you can safely do so by COPYING this file
//	into a new folder called 'gigpress-templates' in your 'wp-content' directory
//	and then making your changes there. When in place, that file will load in place of this one.

// This template displays before each group of artist shows when grouping your shows by artist.

?>

<h3 class="gigpress-artist-heading<?php if (!empty($gigpress_bundled_layout)) echo ' gigpress-layout-bundled-heading'; ?>" id="artist-<?php echo (int) $showdata['artist_id']; ?>">
<?php if (!empty($gpo['artist_link']) && !empty($showdata['artist_url'])) : ?>
	<a href="<?php echo esc_url($showdata['artist_url']); ?>"<?php echo gigpress_target($showdata['artist_url']); ?>><?php echo gigpress_public_text_fragment($showdata['artist_plain']); ?></a>
<?php else : ?>
	<?php echo gigpress_public_text_fragment($showdata['artist_plain']); ?>
<?php endif; ?>
<?php if(!empty($gpo['display_subscriptions'])) : ?>
	<span class="gigpress-artist-subscriptions">
		<a href="<?php echo esc_url(GIGPRESS_RSS . '&artist=' . (int) $showdata['artist_id']); ?>" title="<?php echo esc_attr($showdata['artist_plain']); ?> RSS"><img src="<?php echo esc_url(plugins_url('/gigpress/images/feed-icon-12x12.png')); ?>" alt="" /></a>
		&nbsp;
		<a href="<?php echo esc_url(GIGPRESS_WEBCAL . '&artist=' . (int) $showdata['artist_id']); ?>" title="<?php echo esc_attr($showdata['artist_plain']); ?> iCalendar"><img src="<?php echo esc_url(plugins_url('/gigpress/images/icalendar-icon.gif')); ?>" alt="" /></a>
	</span>
<?php endif; ?>
</h3>
