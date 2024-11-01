<?php defined('ABSPATH') or die; // No Direct Script Access

/*
Plugin Name: WP Homepage Scheduler
Plugin URI:  https://helgesverre.com/products/wp-homepage-scheduler
Description: Plugin for allowing you to schedule which page you want to display as the homepage for your WordPress site.
Version:     1.0.0
Author:      Helge Sverre
Author URI:  https://helgesverre.com
*/


/**
 *  Add filters
 **********************************************************************/
add_filter('pre_option_page_on_front', 'wphs_change_page_on_front');

/**
 *  Add Actions
 **********************************************************************/
add_action('admin_menu', 'wphs_add_admin_menu');
add_action('admin_init', 'wphs_settings_init');


function wphs_add_admin_menu()
{

    $hook = add_options_page(
        'WP Homepage Scheduler',
        'WP Homepage Scheduler',
        'manage_options',
        'wp_homepage_scheduler',
        'wp_homepage_scheduler_options_page'
    );

    add_action("admin_print_scripts-$hook", function () {
        // Styles
        wp_enqueue_style('wphs-css', plugins_url('/assets/css/style.css', __FILE__));
        wp_enqueue_style('wphs-jquery-ui-theme', plugins_url('/assets/css/jquery-ui.theme.min.css', __FILE__));
        wp_enqueue_style('wphs-jquery-ui', plugins_url('/assets/css/jquery-ui.css', __FILE__));
        wp_enqueue_style('full-css', plugins_url('/assets/css/fullcalendar.css', __FILE__));

        // Scripts
        wp_register_script('moment-js', plugins_url('/assets/js/lib/moment.min.js', __FILE__));
        wp_register_script('full-js', plugins_url('/assets/js/fullcalendar.min.js', __FILE__));
        wp_register_script('fc-lang-js', plugins_url('/assets/js/lang-all.js', __FILE__));

        // Enqueue scripts
        wp_enqueue_script('start-js', plugins_url('/assets/js/custom.js', __FILE__), array(
            'jquery',
            'moment-js',
            'full-js',
            'jquery-ui-dialog',
            'jquery-ui-core',
            'fc-lang-js'
        ));
    });
}


function wphs_settings_init()
{

    register_setting('wp_homepage_scheduler_options_page', 'wphs_settings');

    add_settings_section(
        'wphs_wp_homepage_scheduler_options_page_section',
        'Schedule your homepage',
        '',
        'wp_homepage_scheduler_options_page'
    );

    add_settings_field(
        'wphs_default_page',
        'Default Page: ',
        'wphs_default_page_render',
        'wp_homepage_scheduler_options_page',
        'wphs_wp_homepage_scheduler_options_page_section'
    );

    add_settings_field(
        'wphs_events',
        '',
        'wphs_events_render',
        'wp_homepage_scheduler_options_page',
        'wphs_wp_homepage_scheduler_options_page_section'
    );
}


function wphs_default_page_render()
{

    $options = get_option('wphs_settings');
    $pages = wphs_get_pages();

    ?>
    <select name='wphs_settings[wphs_default_page]'>
        <?php foreach ($pages as $index => $page): ?>
            <option
                value='<?= $page->ID ?>' <?php selected($options['wphs_default_page'], $page->ID); ?>><?= $page->post_title ?></option>
        <?php endforeach; ?>
    </select>

    <?php

}


function wphs_events_render()
{

    $options = get_option('wphs_settings');
    ?>
    <input type="hidden" name="wphs_settings[wphs_events]" id="wphs-events" value="<?= $options["wphs_events"] ?>">
    <?php

}


function wp_homepage_scheduler_options_page()
{
    ?>
    <form action='options.php' method='post'>

        <h1><i class="dashicons dashicons-clock"></i> WP Homepage Scheduler</h1>

        <?php
        settings_fields('wp_homepage_scheduler_options_page');
        do_settings_sections('wp_homepage_scheduler_options_page');

        ?>

        <div id="wphs-calendar-wrap">
            <div id="wphs-calendar"></div>
        </div>

        <!-- Modal start -->
        <div id="wphs-dialog" style="display:none;">
            <p>Select a page to schedule for this selected time periode</p>

            <div class="row">
                <label for="wphs-page">Page</label>
                <select name="wphs-page" id="wphs-page">
                    <?php
                    $pages = wphs_get_pages();
                    foreach ($pages as $page): ?>
                        <option value="<?= $page->ID ?>"><?= $page->post_title ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <!-- Modal END -->

        <?php submit_button("Save Changes", "primary", "wphs-save-btn"); ?>

    </form>
    <?php

}


/**
 * Hook that gets executed before the page_on_front option is
 * "used" in WordPress, we take over this variable and use either
 * the default page in our plugin or the scheduled page if
 * we are inside an even'ts schedule
 * @return int the page ID to use as the homepage
 */
function wphs_change_page_on_front()
{
    // Get the options
    $options = get_option('wphs_settings');

    // Parse the event setting
    $events = json_decode(urldecode($options["wphs_events"]));

    // Set the homepage to the default page
    $homepage = $options["wphs_default_page"];

    // Get the datetime of now, use WordPress' timezone
    $timezoneString = get_option('timezone_string');
    $timezone = new DateTimeZone($timezoneString);
    $now = new DateTime("now", $timezone);

    // If have any events
    if ($events) {

        // Loop through each event
        foreach ($events as $event) {

            // Create a DateTime object for the eventStart and set the timezone to the same as WordPress
            $eventStart = new DateTime($event->start);
            $eventStart->setTimezone($timezone);

            // Create a DateTime object for the eventEnd and set the timezone to the same as WordPress
            $eventEnd = new DateTime($event->end);
            $eventEnd->setTimezone($timezone);

            // Check if we are between the event starting and ending date.
            if (($now > $eventStart) && ($now < $eventEnd)) {
                // We found our event
                $homepage = $event->pageid;
                // no need to check the rest of the events, there should never be overlapping events.
                break;
            }
        }

    }

    // $homepage will be the default page if it was not overwritten, or whatever page that was scheduled for this date.
    return $homepage;
}


/**
 * Gets the pages, except the one used for the 'page_for_posts' option
 * @return array|false the pages
 */
function wphs_get_pages()
{
    $postsPage = get_option('page_for_posts');
    $pages = get_pages();

    // Filter out the posts page, we can't select that one because it will break WordPress.
    $pages = array_filter($pages, function ($page) use ($postsPage) {
        return !((int)$page->ID == (int)$postsPage);
    });

    return $pages;
}