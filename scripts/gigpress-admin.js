$gp=jQuery.noConflict();

$gp(document).ready(function()
	{
		// The server renders every correction field for requests without JavaScript.
		$gp('tr.gigpress-inactive, tbody.gigpress-inactive').hide();
		var revealEndDate = function() {
			$gp('#expire').toggle($gp('#show_multi').prop('checked') || $gp('#expire [aria-invalid="true"]').length > 0);
		};
		$gp('#show_multi').on('change', revealEndDate);
		revealEndDate();
		var enableMinutes = function() {
			var minutes = $gp('.gigpress-entry #gp_min');
			minutes.prop('disabled', $gp('.gigpress-entry #gp_hh').val() === 'na' && minutes.attr('aria-invalid') !== 'true');
		};
		$gp('.gigpress-entry #gp_hh').on('change', enableMinutes);
		enableMinutes();
		$gp('select.can-add-new').each(function() {
			var control = $gp(this);
			var section = $gp(document.getElementById(this.id + '_new'));
			var reveal = function() {
				section.toggle(control.val() === 'new' || section.find('[aria-invalid="true"]').length > 0);
			};
			control.on('change', reveal);
			reveal();
		});
		if ($gp('.gigpress-entry').length) {
			$gp('#gigpress-errors a[href^="#"]').on('click', function(event) {
				var target = document.getElementById(this.hash.slice(1));
				if (target) {
					event.preventDefault();
					$gp(target).closest('#expire, tbody.gigpress-addition').show();
					target.focus();
				}
			});
			// These notices exist only after a save attempt, never on an ordinary load.
			var feedback = document.getElementById('gigpress-errors') || document.querySelector('#message.notice-success');
			if (feedback) feedback.focus();
		}
		
		// Return a helper with preserved width of cells
		var fixHelper = function(e, ui) {
			ui.children().each(function() {
				$gp(this).width($gp(this).width());
			});
			return ui;
		};
				
		// Sortable artist table
		$gp('img.gp-sort-handle').show();
		$gp('.gigpress-artist-sort').sortable({
			handle: '.gp-sort-handle', 
			axis: 'y',
			helper: fixHelper,
			update : function () { 
		      var order = $gp('.gigpress-artist-sort').sortable('serialize');
		      $gp("#artist-sort-update").load(ajaxurl, order + '&action=gigpress_reorder_artists&cachebuster=' + Math.floor(Math.random()*99999), function()
		      	{
		   			$gp("#artist-sort-update").fadeIn(100, function(){$gp(this).fadeOut(1500)});  
		      	}
		      ); 
		    } 
		});
					
	}
);
