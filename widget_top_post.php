<?php
/*
Plugin Name:  Top Post Widget
Plugin URI: http://www.vjcatkick.com/?page_id=4892
Description: display top post entries and most active entries from wordpress.com
Version: 0.0.5
Author: V.J.Catkick, mnagaku
Author URI: http://www.vjcatkick.com/
*/

/*
Requires: wordpress-stat plugin 
http://wordpress.org/extend/plugins/stats/
*/

/*
License: GPL
Compatibility: WordPress 2.6 with Widget-plugin.

Installation:
Place the widget_single_photo folder in your /wp-content/plugins/ directory
and activate through the administration panel, and then go to the widget panel and
drag it to where you would like to have it!
*/

/*  Copyright V.J.Catkick - http://www.vjcatkick.com/

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/


/* Changelog
* Dec 29 2008 - v0.0.1
- Initial release
* Jan 23 2009 - v0.0.2
- fixed, - if title size == 0, skip
* Mar 16 2009 - v0.0.3
- refactoring by mnagaku
- fixed, - The method of getting the title is changed.
* Aprl 12 2010 - v0.0.4
- fixed, mis-counting
* Jul 12 2011 - v0.0.5
- fixed, link issue

*/

function widget_top_and_active_vjck_get_entries() {
	if(time() > get_option('widget_top_and_active_vjck_cache_date') + 1) {
		$modes = array('top' => 'top_post', 'active' => 'most_active'); // config
		$options = stats_dashboard_widget_options();
		$entries = array();
		foreach($modes as $mode => $str) {
			$entries[$mode] = array();
			$option_vjck = get_option('widget_'.$str.'_vjck');
			$limit = (int)$option_vjck[$str.'_vjck_max_entries'];

			$cc = $limit;	// 0.0.4
			$limit = 20;

			foreach(stats_get_csv('postviews', 'days='.$options[$mode].'&limit='.$limit) as $post) {
				$title = get_the_title($post['post_id']);
				if($post['post_id'] > 0 && strlen($title) > 0) {
//					$entries[$mode][] = array('title' => $title, 'link' => $post['post_permalink']);
					$entries[$mode][] = array('title' => $title, 'link' => get_permalink( $post['post_id'] ));  // fix 0.0.5

					$cc -= 1;
					if( $cc <= 0 ) break;
				} /* fixed: 0.0.4 */
			}
		}
		update_option('widget_top_and_active_vjck_cache', $entries);
		update_option('widget_top_and_active_vjck_cache_date', time());
		return $entries;
	} else return get_option('widget_top_and_active_vjck_cache');
}

function widget_top_and_active_vjck_main($mode, $mode_str, $args) {
	extract($args);

	$output = '<div id="widget_'.$mode_str.'_vjck"><ul>';

	// section main logic from here 
	if( !function_exists('stats_get_csv') ) {
		$output .= 'need WordPress Stat plugin';
		return;
	} /* if */

	$data = widget_top_and_active_vjck_get_entries();

	foreach( $data[$mode] as $post )
		$output .= '<li><a href="' . $post['link'] . '">' . $post['title'] . '</a></li>';

	// These lines generate the output
	$output .= '</ul></div>';

	$option = get_option('widget_'.$mode_str.'_vjck');

	echo $before_widget . $before_title . $option[$mode_str.'_vjck_src_title'] . $after_title;
	echo $output;
	echo $after_widget;
}

function widget_top_and_active_vjck_control($mode_str) {
	$options = $newoptions = get_option('widget_'.$mode_str.'_vjck');
		if ( $_POST[$mode_str.'_vjck_src_submit'] ) {
			$newoptions[$mode_str.'_vjck_src_title'] = strip_tags(stripslashes($_POST[$mode_str.'_vjck_src_title']));
			$newoptions[$mode_str.'_vjck_max_entries'] = (int) $_POST[$mode_str.'_vjck_max_entries'];
		}
		if ( $options != $newoptions ) {
			$options = $newoptions;
			update_option('widget_'.$mode_str.'_vjck', $options);
		}

		// those are default value
		if ( !$options[$mode_str.'_vjck_max_entries'] ) $options[$mode_str.'_vjck_max_entries'] = 10;

		$max_entries = $options[$mode_str.'_vjck_max_entries'];

		$src_title = htmlspecialchars($options[$mode_str.'_vjck_src_title'], ENT_QUOTES);
?>

	    <?php _e('Title:'); ?> <input style="width: 170px;" id="<?php echo $mode_str; ?>_vjck_src_title" name="<?php echo $mode_str; ?>_vjck_src_title" type="text" value="<?php echo $src_title; ?>" /><br />

        <?php _e('Max Entries:'); ?> <input style="width: 75px;" id="<?php echo $mode_str; ?>_vjck_max_entries" name="<?php echo $mode_str; ?>_vjck_max_entries" type="text" value="<?php echo $max_entries; ?>" /><br />

  	    <input type="hidden" id="<?php echo $mode_str; ?>_vjck_src_submit" name="<?php echo $mode_str; ?>_vjck_src_submit" value="1" />

<?php
}

function widget_top_post_vjck_init() {
	if ( !function_exists('register_sidebar_widget') )
		return;

	function widget_top_post_vjck( $args ) {
		widget_top_and_active_vjck_main('top', 'top_post', $args);
	} /* widget_top_post_vjck() */

	function widget_top_post_vjck_control() {
		widget_top_and_active_vjck_control('top_post');
	} /* widget_top_post_vjck_control() */

	register_sidebar_widget('Top Post', 'widget_top_post_vjck');
	register_widget_control('Top Post', 'widget_top_post_vjck_control' );
} /* widget_top_post_vjck_init() */

function widget_most_active_vjck_init() {
	if ( !function_exists('register_sidebar_widget') )
		return;

	function widget_most_active_vjck( $args ) {
		widget_top_and_active_vjck_main('active', 'most_active', $args);
	} /* widget_most_active_vjck() */

	function widget_most_active_vjck_control() {
		widget_top_and_active_vjck_control('most_active');
	} /* widget_most_active_vjck_control() */

	register_sidebar_widget('Most Active', 'widget_most_active_vjck');
	register_widget_control('Most Active', 'widget_most_active_vjck_control' );
} /* widget_most_active_vjck_init() */

// Run our code later in case this loads prior to any required plugins.
add_action('plugins_loaded', 'widget_top_post_vjck_init');
add_action('plugins_loaded', 'widget_most_active_vjck_init');

?>
