# Adopting the bundled public show layout

GigPress keeps the existing public template lookup order:

1. Child theme: `gigpress-templates/{filename}.php`
2. Parent theme: `gigpress-templates/{filename}.php`
3. `wp-content/gigpress-templates/{filename}.php`
4. The plugin's bundled `templates/{filename}.php`

The main listing's structural files are `shows-list-start.php`, `shows-list.php`, and `shows-list-end.php`. Each file resolves separately. Artist and tour headings and the subscription footer remain separate overrides in `shows-artist-heading.php`, `shows-tour-heading.php`, and `shows-list-footer.php`.

## Bundled responsive layout

When all three structural files resolve to GigPress's bundled files, GigPress adds the `gigpress-layout-bundled` class to the bundled table. The bundled stylesheet retains the wide table at wider viewports. At viewports up to 42em it stacks each show into labelled date, artist (when not already grouped), city, venue, country, and details rows. The actual labels are in the HTML. Long unbroken table values and grouped artist headings wrap within the content width. Time, admission, address, notes, ticket and external links, statuses, and available calendar actions remain visible and can wrap. Artist and tour headings remain outside their show blocks.

The table and its established hooks remain available to themes: `.gigpress-table`, `.gigpress-header`, `.gigpress-row`, `.gigpress-info`, `.gigpress-tour`, `.gigpress-heading`, `.gigpress-date`, `.gigpress-artist`, `.gigpress-city`, `.gigpress-venue`, `.gigpress-country`, `.gigpress-info-item`, and `.gigpress-info-label`. The bundled start partial still exposes `$cols` for later partials.

The show partial continues to receive `$showdata` and `$class`, along with `$artist`, `$group_artists`, `$total_artists`, `$scope`, `$cols`, and `$gpo`. Heading and footer partials keep their existing caller variables. Custom partials can keep using their current filenames, variables, and hooks.

## Existing custom templates

Any owner-controlled start, body, or end partial prevents automatic bundled-layout opt-in for that listing. This applies to complete custom sets and mixed sets, such as a bundled start with an owner body or an owner start with a bundled body. Reusing legacy class names alone does not opt custom markup into the responsive rules. This keeps mixed partial combinations under owner control.

To adopt the bundled responsive CSS intentionally, first check that the custom structural markup has the same main-table and show-row structure. Add `gigpress-layout-bundled` to the outer show table, for example:

```php
<table class="gigpress-table <?php echo $scope; ?> gigpress-layout-bundled" cellspacing="0">
```

The class is the explicit opt-in marker. Keep the existing class and hook names when copying or adapting the bundled partials. The responsive labels in the bundled body partial use `.gigpress-mobile-label`; add equivalent labelled markup to an owner body partial if its phone view needs those labels.

The rules are part of the public `css/gigpress.css` stylesheet. They inherit the active theme's fonts, link colors, and content width. With GigPress CSS enabled, the plugin stylesheet loads before a child theme's `gigpress.css`, or the parent theme's file when no child file exists. When `disable_css` is enabled, GigPress enqueues neither stylesheet; if you still want the responsive layout, copy the scoped `.gigpress-layout-bundled` rules into a stylesheet your theme normally loads. Existing `disable_js` behavior does not control the layout or calendar-link visibility; the bundled calendar anchors remain ordinary links. The legacy calendar toggle and hidden-panel rules are scoped to tables without the bundled opt-in marker, so owner templates that retain those hooks can continue using the existing script.

Do not assume an older custom template receives the stacked layout simply because it uses the original filenames, classes, or CSS variables. Recheck complete and mixed override combinations after changing any of the three structural files.
