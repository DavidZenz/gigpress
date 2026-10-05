<?php

function gigpress_settings() {
    global $gpo;
    require ABSPATH . 'wp-admin/options-head.php';

    /* These are the established option keys. Unrendered metadata stays in storage. */
    $sections = array(
        'display-formatting' => array(__('Display & formatting', 'gigpress'), array(
            'date_format' => array('format', __('Short date format', 'gigpress'), __('Used in show lists. For example, m/d/y gives a short numeric date.', 'gigpress')),
            'date_format_long' => array('format', __('Long date format', 'gigpress'), __('Used when a full date is needed. For example, l, F jS Y includes the weekday.', 'gigpress')),
            'time_format' => array('format', __('Time format', 'gigpress'), __('Controls published show times. For example, g:ia uses a 12 hour clock and H:i uses a 24 hour clock.', 'gigpress')),
            'alternate_clock' => array('checkbox', __('Use a 24 hour clock for show entry', 'gigpress'), __('Changes the hour choices in the show form; published times use the time format above.', 'gigpress')),
            'display_country' => array('checkbox', __('Display country column', 'gigpress'), __('Include the venue country in your show lists.', 'gigpress')),
            'country_view' => array('select', __('Country names', 'gigpress'), __('Choose full country names or two-letter country codes.', 'gigpress'), array('' => __('Two-letter codes', 'gigpress'), 'long' => __('Full country names', 'gigpress'))),
        )),
        'show-labels-links' => array(__('Show labels & links', 'gigpress'), array(
            'shows_page' => array('url', __('Full URL to your Upcoming Shows page', 'gigpress'), __('Enter the full http or https address of the page containing your shows, or leave it empty.', 'gigpress')),
            'noupcoming' => array('text', __('No upcoming shows message', 'gigpress'), __('Shown when there are no upcoming shows.', 'gigpress')),
            'nopast' => array('text', __('No past shows message', 'gigpress'), __('Shown when the show archive is empty.', 'gigpress')),
            'artist_label' => array('text', __('Artist label', 'gigpress'), __('The heading used for artists in show lists.', 'gigpress')),
            'tour_label' => array('text', __('Tour label', 'gigpress'), __('The heading used for tours in show lists.', 'gigpress')),
            'external_link_label' => array('text', __('External link label', 'gigpress'), __('The text used for a show’s external information link.', 'gigpress')),
            'buy_tickets_label' => array('text', __('Buy tickets label', 'gigpress'), __('The text used for a show’s ticket link.', 'gigpress')),
            'age_restrictions' => array('text', __('Age restrictions', 'gigpress'), __('Separate the available choices with a pipe, for example: All Ages | No Minors.', 'gigpress')),
            'artist_link' => array('checkbox', __('Link artist names to their URLs', 'gigpress'), __('Artists with a saved URL will have a link in show listings.', 'gigpress')),
            'target_blank' => array('checkbox', __('Open external links in new windows', 'gigpress'), __('Applies to external links published by GigPress.', 'gigpress')),
        )),
        'related-posts' => array(__('Related posts', 'gigpress'), array(
            'related_position' => array('radio', __('Display show information in related posts', 'gigpress'), __('Choose where linked show details appear. For manual placement, use the [gigpress_related_shows] shortcode.', 'gigpress'), array('before' => __('Before the post content', 'gigpress'), 'after' => __('After the post content', 'gigpress'), 'nowhere' => __('Use the shortcode', 'gigpress'))),
            'related_heading' => array('text', __('Related show heading', 'gigpress'), __('Appears before the show details in a related post.', 'gigpress')),
            'autocreate_post' => array('checkbox', __('Automatically create a related post for every new show', 'gigpress'), __('New shows receive a related post using the category below.', 'gigpress')),
            'related_category' => array('category', __('Related posts category', 'gigpress'), __('Put newly created related posts in this category.', 'gigpress')),
            'category_exclude' => array('checkbox', __('Exclude this category from normal post listings', 'gigpress'), __('Related posts remain accessible directly while being omitted from normal listings.', 'gigpress')),
            'relatedlink_date' => array('checkbox', __('Link the show date to its related post', 'gigpress'), __('Use the date as a link when a show has a related post.', 'gigpress')),
            'relatedlink_city' => array('checkbox', __('Link the show city to its related post', 'gigpress'), __('Use the city as a link when a show has a related post.', 'gigpress')),
            'relatedlink_notes' => array('checkbox', __('Link the show notes to its related post', 'gigpress'), __('Use the notes as a link when a show has a related post.', 'gigpress')),
            'related' => array('text', __('Related post phrase', 'gigpress'), __('The link text that appears in your show listing.', 'gigpress')),
        )),
        'feeds' => array(__('Feeds', 'gigpress'), array(
            'rss_head' => array('checkbox', __('Make the GigPress RSS feed discoverable', 'gigpress'), __('Add the feed address to your site’s page headers for feed readers.', 'gigpress')),
            'display_subscriptions' => array('checkbox', __('Show RSS and iCalendar subscription links', 'gigpress'), __('Display subscription links alongside your shows. Feed redirect plugins may affect these feeds.', 'gigpress')),
            'rss_title' => array('text', __('RSS and iCalendar feed title', 'gigpress'), __('The title shown in feed readers and calendar applications.', 'gigpress')),
            'rss_limit' => array('number', __('RSS and iCalendar feed limit', 'gigpress'), __('Maximum shows included in each feed. An empty or zero limit uses the existing default of 100.', 'gigpress')),
        )),
        'permissions' => array(__('Permissions', 'gigpress'), array(
            'user_level' => array('select', __('User level required to use GigPress', 'gigpress'), __('Choose who may manage shows. Saving these settings still requires a WordPress administrator.', 'gigpress'), array('activate_plugins' => __('Administrator', 'gigpress'), 'edit_published_posts' => __('Editor', 'gigpress'), 'publish_posts' => __('Author', 'gigpress'), 'edit_posts' => __('Contributor', 'gigpress'))),
        )),
        'advanced' => array(__('Advanced', 'gigpress'), array(
            'output_schema_json' => array('radio', __('Include Schema.org Event structured data', 'gigpress'), __('Add JSON-LD event details to published shows for search engines.', 'gigpress'), array('y' => __('Yes', 'gigpress'), 'n' => __('No', 'gigpress'))),
            'load_jquery' => array('checkbox', __('Load jQuery into my theme', 'gigpress'), __('Disable this if your theme already loads the jQuery library itself.', 'gigpress')),
            'disable_css' => array('checkbox', __('Disable the default GigPress CSS', 'gigpress'), __('Use your own styles for published show displays.', 'gigpress')),
            'disable_js' => array('checkbox', __('Disable the default GigPress JavaScript', 'gigpress'), __('Use your own scripts for published show displays.', 'gigpress')),
        )),
    );
    ?>
    <div class="wrap gigpress gp-options">
        <h1><?php esc_html_e('Settings', 'gigpress'); ?></h1>
        <nav class="gp-settings-jump" aria-label="<?php esc_attr_e('Jump to section', 'gigpress'); ?>">
            <p><strong><?php esc_html_e('Jump to section', 'gigpress'); ?></strong></p>
            <ul>
            <?php foreach ($sections as $slug => $section) { ?>
                <li><a href="#gp-settings-<?php echo esc_attr($slug); ?>"><?php echo esc_html($section[0]); ?></a></li>
            <?php } ?>
            </ul>
        </nav>
        <form method="post" action="options.php">
            <?php settings_fields('gigpress'); ?>
            <?php foreach ($sections as $slug => $section) { ?>
                <section class="gp-settings-section" aria-labelledby="gp-settings-<?php echo esc_attr($slug); ?>">
                    <h2 id="gp-settings-<?php echo esc_attr($slug); ?>" tabindex="-1"><?php echo esc_html($section[0]); ?></h2>
                    <table class="gp-table form-table" role="presentation">
                    <?php foreach ($section[1] as $key => $field) {
                        list($type, $label, $help) = $field;
                        $raw = array_key_exists($key, $gpo) ? $gpo[$key] : '';
                        $value = is_scalar($raw) ? (string) $raw : '';
                        $disabled = !is_scalar($raw);
                        $input_type = $type === 'format' ? 'text' : $type;
                        // Native controls must be able to submit an unchanged stored value.
                        if ($type === 'number' && $value !== '' && !preg_match('/\A[0-9]+\z/', $value)) $input_type = 'text';
                        if ($type === 'url' && $value !== '' && (!preg_match('/\Ahttps?:\/\/[^\s]+\z/i', $value) || !filter_var($value, FILTER_VALIDATE_URL))) $input_type = 'text';
                        $id = 'gp-setting-' . $key;
                        $help_id = $id . '-help';
                        $name = 'gigpress_settings[' . $key . ']';
                        ?>
                        <tr id="gp-setting-row-<?php echo esc_attr($key); ?>">
                            <th scope="row">
                                <?php if ($type === 'radio') { echo esc_html($label); } else { ?>
                                    <label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?></label>
                                <?php } ?>
                            </th>
                            <td>
                            <?php if ($type === 'checkbox') { ?>
                                <input type="hidden" name="<?php echo esc_attr($name); ?>" value="0" <?php disabled($disabled); ?> />
                                <input type="checkbox" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr(!empty($raw) ? $value : '1'); ?>" <?php checked(!empty($raw)); disabled($disabled); ?> aria-describedby="<?php echo esc_attr($help_id); ?>" />
                            <?php } elseif ($type === 'radio' || $type === 'select' || $type === 'category') {
                                $choices = $field[3] ?? array();
                                if ($type === 'category') {
                                    foreach (get_categories(array('hide_empty' => false)) as $category) $choices[(string) $category->term_id] = apply_filters('the_title', $category->name);
                                }
                                if (!array_key_exists($value, $choices)) $choices = array($value => sprintf(__('Current value: %s', 'gigpress'), $value)) + $choices;
                                if ($type === 'radio') { ?>
                                    <fieldset>
                                        <legend class="screen-reader-text"><?php echo esc_html($label); ?></legend>
                                        <?php $index = 0; foreach ($choices as $choice => $choice_label) { $choice_id = $id . '-' . $index++; ?>
                                            <p><input type="radio" id="<?php echo esc_attr($choice_id); ?>" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($choice); ?>" <?php checked($value, (string) $choice); disabled($disabled); ?> aria-describedby="<?php echo esc_attr($help_id); ?>" />
                                            <label for="<?php echo esc_attr($choice_id); ?>"><?php echo esc_html($choice_label); ?></label></p>
                                        <?php } ?>
                                    </fieldset>
                                <?php } else { ?>
                                    <select id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" <?php disabled($disabled); ?> aria-describedby="<?php echo esc_attr($help_id); ?>">
                                    <?php foreach ($choices as $choice => $choice_label) { ?>
                                        <option value="<?php echo esc_attr($choice); ?>" <?php selected($value, (string) $choice); ?>><?php echo esc_html($choice_label); ?></option>
                                    <?php } ?>
                                    </select>
                                <?php }
                            } else { ?>
                                <input type="<?php echo esc_attr($input_type); ?>" class="regular-text" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>" <?php disabled($disabled); ?> <?php if ($input_type === 'number') echo 'min="0" step="1"'; ?> aria-describedby="<?php echo esc_attr($help_id); ?>" />
                            <?php } ?>
                                <p class="description" id="<?php echo esc_attr($help_id); ?>"><?php echo esc_html($help); ?>
                                <?php if ($type === 'format') {
                                    $example = $key === 'time_format' ? gmdate($value, current_time('timestamp')) : mysql2date($value, current_time('mysql'));
                                    ?>
                                    <br /><?php esc_html_e('Output', 'gigpress'); ?>: <strong><?php echo esc_html($example); ?></strong>
                                    <br /><a href="<?php echo esc_url('https://wordpress.org/documentation/article/customize-date-and-time-format/'); ?>"><?php esc_html_e('Date and time formatting guide', 'gigpress'); ?></a>
                                <?php } ?>
                                <?php if ($disabled) { echo ' ' . esc_html__('This stored value cannot be edited here and will be kept when you save.', 'gigpress'); } ?>
                                </p>
                            </td>
                        </tr>
                    <?php } ?>
                    </table>
                </section>
            <?php } ?>
            <?php submit_button(__('Save changes', 'gigpress')); ?>
        </form>
    </div>
<?php
}
