$gp=jQuery.noConflict();

$gp(document).ready(function()
	{
		var exportFilters = document.querySelector('.gp-import-export .gp-csv-export-filters');
		var exportDownload = document.querySelector('.gp-import-export .gp-csv-download');
		var exportSummary = document.getElementById('gp-export-summary');
		var exportPreview = (window.gigpressAdmin || {}).exportPreview || {};
		if (exportFilters && exportDownload && exportSummary && exportPreview.nonce && window.ajaxurl) {
			var exportFields = ['artist_id', 'tour_id', 'scope'];
			var previewRequest = null;
			var previewRevision = 0;
			var selectedLabel = function(id) {
				var select = document.getElementById(id);
				return select && select.selectedIndex >= 0 ? select.options[select.selectedIndex].text : '';
			};
			var updateExportSummary = function(count) {
				var values = [selectedLabel('gp-export-artist'), selectedLabel('gp-export-tour'), selectedLabel('gp-export-scope'), String(count)];
				exportSummary.textContent = String(exportPreview.template || '').replace(/%([1-4])\$[sd]/g, function(match, index) {
					return values[parseInt(index, 10) - 1];
				});
			};
			var syncExportDownload = function() {
				exportFields.forEach(function(name) {
					var selection = exportFilters.querySelector('[name="' + name + '"]');
					var input = exportDownload.querySelector('[name="' + name + '"]');
					if (selection && input) input.value = selection.value;
				});
			};
			var refreshExportPreview = function() {
				var revision = ++previewRevision;
				if (previewRequest) previewRequest.abort();
				syncExportDownload();
				updateExportSummary(exportPreview.updating || '…');
				exportFilters.classList.add('gp-js-ready');
				previewRequest = $gp.ajax({
					url: window.ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'gigpress_export_preview',
						_ajax_nonce: exportPreview.nonce,
						artist_id: exportFilters.querySelector('[name="artist_id"]').value,
						tour_id: exportFilters.querySelector('[name="tour_id"]').value,
						scope: exportFilters.querySelector('[name="scope"]').value
					}
				}).done(function(response) {
					if (revision !== previewRevision) return;
					if (response && response.success && response.data && Number.isInteger(Number(response.data.count)) && Number(response.data.count) >= 0) {
						updateExportSummary(Number(response.data.count));
					} else {
						updateExportSummary(exportPreview.error || '');
						exportFilters.classList.remove('gp-js-ready');
					}
				}).fail(function(xhr, status) {
					if (revision !== previewRevision || status === 'abort') return;
					updateExportSummary(exportPreview.error || '');
					exportFilters.classList.remove('gp-js-ready');
				});
			};
			exportFilters.classList.add('gp-js-ready');
			exportFields.forEach(function(name) {
				var control = exportFilters.querySelector('[name="' + name + '"]');
				if (control) control.addEventListener('change', refreshExportPreview);
			});
			exportDownload.addEventListener('submit', syncExportDownload);
		}

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
		      var feedback = $gp('#artist-sort-update');
		      var config = window.gigpressAdmin || {};
		      $gp.ajax({url: ajaxurl, type: 'POST', dataType: 'json', data: order + '&action=gigpress_reorder_artists&_ajax_nonce=' + encodeURIComponent(config.reorderNonce || '')})
		        .done(function(response) {
		          feedback.text(response.data && response.data.message || config.reorderError).show();
		          if (response.success) feedback.delay(1500).fadeOut(100);
		        })
		        .fail(function(xhr) {
		          var response = xhr.responseJSON;
		          feedback.text(response && response.data && response.data.message || config.reorderError).show();
		        });
		    } 
		});
					
	}
);
