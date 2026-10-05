<?php
/* Disposable owner partials for the public structural override ladder. */
return array(
	'structural' => array('shows-list-start', 'shows-list', 'shows-list-end'),
	'files' => array(
		'shows-list-start' => <<<'PHP'
<?php
$cols = 3;
echo '<table class="compat-override-start"><tbody><tr><th>Owner start</th></tr></tbody>';
?>
PHP,
		'shows-list-start-explicit' => <<<'PHP'
<?php
$cols = 3;
echo '<table class="compat-override-start gigpress-layout-bundled"><tbody><tr><th>Owner explicit start</th></tr></tbody>';
?>
PHP,
		'shows-list' => <<<'PHP'
<?php
$ownerContext = array(
	'artist' => $artist,
	'group_artists' => $group_artists,
	'total_artists' => (int) $total_artists,
	'scope' => $scope,
	'cols' => (int) $cols,
	'artist_label' => $gpo['artist_label'],
);
echo '<tbody><!-- compat-__LOCATION__-body --><tr class="gigpress-row ' . esc_attr($class) . '" data-show-id="' . (int) $showdata['id'] . '" data-owner-context="' . esc_attr(wp_json_encode($ownerContext)) . '"><td class="gigpress-date">' . $showdata['date'] . '</td></tr></tbody>';
?>
PHP,
		'shows-list-end' => <<<'PHP'
<?php echo '<!-- compat-__LOCATION__-end -->'; ?></table>
PHP,
		'shows-artist-heading' => <<<'PHP'
<?php echo '<h3 class="gigpress-artist-heading compat-__LOCATION__-artist-heading" id="artist-' . (int) $showdata['artist_id'] . '">' . $showdata['artist'] . '</h3>'; ?>
PHP,
		'shows-tour-heading' => <<<'PHP'
<tbody><tr><th colspan="<?php echo (int) $cols; ?>" class="gigpress-heading compat-__LOCATION__-tour-heading"><?php echo $showdata['tour']; ?></th></tr></tbody>
PHP,
		'shows-list-footer' => <<<'PHP'
<?php echo '<p class="gigpress-subscribe compat-__LOCATION__-footer">Subscribe</p>'; ?>
PHP,
	),
	'locations' => array('child', 'parent', 'wp-content'),
	'expected_variables' => array('artist', 'group_artists', 'total_artists', 'scope', 'cols', 'artist_label'),
);
