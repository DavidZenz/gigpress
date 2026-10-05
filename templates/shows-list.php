<?php
/*
	STOP! DO NOT MODIFY THIS FILE!
	If you wish to customize the output, you can safely do so by COPYING this file into a new folder called 'gigpress-templates' in your 'wp-content' directory	and then making your changes there. When in place, that file will load in place of this one.
	
	This template displays all of our individual show data in the main shows listing (upcoming and past).

	If you're curious what all variables are available in the $showdata array, have a look at the docs: http://gigpress.com/docs/
*/
?>

<tbody>
	
	<tr class="gigpress-row <?php echo esc_attr($class); ?>" data-show-id="<?php echo (int) $showdata['id']; ?>">
	
		<td class="gigpress-date"><span class="gigpress-mobile-label"><?php esc_html_e("Date", "gigpress"); ?>:</span> <?php echo $showdata['date']; ?>
			<?php if($showdata['end_date']) : ?> - <?php echo $showdata['end_date']; ?><?php endif; ?>
		</td>
		
	<?php if((!$artist && $group_artists == 'no') && $total_artists > 1) : ?>
		<td class="gigpress-artist"><span class="gigpress-mobile-label"><?php echo gigpress_public_text_fragment($gpo['artist_label']); ?>:</span>
			<?php echo $showdata['artist']; ?>
		</td>
	<?php endif; ?>
	
		<td class="gigpress-city"><span class="gigpress-mobile-label"><?php esc_html_e("City", "gigpress"); ?>:</span> <?php echo $showdata['city']; if(!empty($showdata['state'])) echo ', '.$showdata['state']; ?></td>
		
		<td class="gigpress-venue"><span class="gigpress-mobile-label"><?php esc_html_e("Venue", "gigpress"); ?>:</span> <?php echo $showdata['venue']; ?></td>
		
	<?php if(!empty($gpo['display_country'])) : ?>
		<td class="gigpress-country"><span class="gigpress-mobile-label"><?php esc_html_e("Country", "gigpress"); ?>:</span> <?php echo $showdata['country']; ?></td>
	<?php endif; ?>
	
	</tr>
	
	<tr class="gigpress-info <?php echo esc_attr($class); ?>" data-show-id="<?php echo (int) $showdata['id']; ?>">
	
		<td colspan="<?php echo (int) $cols; ?>" class="gigpress-details">
		
			<?php if($showdata['time']) : ?>
				<span class="gigpress-info-item"><span class="gigpress-info-label"><?php esc_html_e("Time", "gigpress"); ?>:</span> <?php echo $showdata['time']; ?>.</span>
			<?php endif; ?>
			<?php
			// Keep per-show actions beside the saved date/time data and directly usable without JavaScript.
			if($scope != 'past') : ?>
				<span class="gigpress-info-item gigpress-calendar-actions"><?php echo $showdata['gcal']; ?> | <?php echo str_replace(__('Download iCal', 'gigpress'), __('Download iCalendar', 'gigpress'), $showdata['ical']); ?></span>
			<?php endif; ?>
			
			<?php if($showdata['price']) : ?>
				<span class="gigpress-info-item"><span class="gigpress-info-label"><?php esc_html_e("Admission", "gigpress"); ?>:</span> <?php echo $showdata['price']; ?>.</span>
			<?php endif; ?>
			
			<?php if($showdata['admittance']) : ?>
				<span class="gigpress-info-item"><span class="gigpress-info-label"><?php esc_html_e("Age restrictions", "gigpress"); ?>:</span> <?php echo $showdata['admittance']; ?>.</span>
			<?php endif; ?>
			
			<?php if($showdata['ticket_phone']) : ?>
				<span class="gigpress-info-item"><span class="gigpress-info-label"><?php esc_html_e("Box office", "gigpress"); ?>:</span> <?php echo $showdata['ticket_phone']; ?>.</span>
			<?php endif; ?>
			
			<?php if($showdata['address']) : ?> 
				<span class="gigpress-info-item"><span class="gigpress-info-label"><?php esc_html_e("Address", "gigpress"); ?>:</span> <?php echo $showdata['address']; ?>.</span>
			<?php endif; ?>
			
			<?php if($showdata['venue_phone']) : ?>
				<span class="gigpress-info-item"><span class="gigpress-info-label"><?php esc_html_e("Venue phone", "gigpress"); ?>:</span> <?php echo $showdata['venue_phone']; ?>.</span>
			<?php endif; ?>				
			
			<?php if($showdata['notes']) : ?>
				<span class="gigpress-info-item"><?php echo $showdata['notes']; ?></span>
			<?php endif; ?>
			
			<?php if($showdata['related_link'] && !empty($gpo['relatedlink_notes'])) : ?>
				<span class="gigpress-info-item"><?php echo $showdata['related_link']; ?></span> 
			<?php endif; ?>
			
				<?php if(!empty($showdata['ticket_link'])) : ?>
				<span class="gigpress-info-item"><?php echo $showdata['ticket_link']; ?></span>
			<?php endif; ?>

			<?php if($showdata['external_link']) : ?>
				<span class="gigpress-info-item"><?php echo $showdata['external_link']; ?></span>
			<?php endif; ?>					
		
		</td>
	
	</tr>
</tbody>	
