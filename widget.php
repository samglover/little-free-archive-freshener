<?php
/**
 * Freshen Up Your Archives widget
 *
 * @file options.php
 * @package LFAF
 * @since 1.0.0
 */

namespace ARCHIVE_FRESHENER;

add_action( 'admin_init', __NAMESPACE__ . '\process_query_args' );
/**
 * Process query args
 */
function process_query_args() {
	if ( ! isset( $_GET['lfaf_nonce'] ) ) {
		return;
	}

	$lfaf_nonce = sanitize_key( $_GET['lfaf_nonce'] );

	if ( ! wp_verify_nonce( $lfaf_nonce, 'lfaf_nonce' ) ) {
		die( esc_attr_e( 'Invalid nonce', 'little-free-archive-freshener' ) );
	}

	if (
		isset( $_GET['lfaf_archive_action'] )
		&& isset( $_GET['lfaf_post_id'] )
	) {
		$action  = sanitize_key( $_GET['lfaf_archive_action'] );
		$post_id = absint( intval( $_GET['lfaf_post_id'] ) );

		switch ( $action ) {
			case 'skip':
				skip_post( $post_id );
				break;
			case 'ignore':
				ignore_post( $post_id );
				break;
		}
	}
}


add_action( 'wp_dashboard_setup', __NAMESPACE__ . '\add_widget' );
/**
 * Add Freshen Up Your Archives dashboard widget
 */
function add_widget() {
	wp_add_dashboard_widget(
		'archive_freshener_widget',
		'Freshen Up Your Archives <a href="' . get_admin_url( '', 'options-writing.php' ) . '">' . __( 'Options', 'little-free-archive-freshener' ) . '</a>',
		__NAMESPACE__ . '\widget',
		null,
		null,
		'normal',
		'high',
	);
}

/**
 * Outputs the Freshen Up Your Archives dashboard widget
 */
function widget() {
	/**
	 * Checks to see if the lfaf_clear_ignored option has come true and if so,
	 * deletes the wp_archive_updater_ignored and lfaf_clear_ignored options,
	 * clearing the list of ignored posts.
	 */
	if ( get_option( 'lfaf_clear_ignored' ) ) {
		delete_option( 'wp_archive_updater_ignored' );
		delete_option( 'lfaf_clear_ignored' );
	}

	/**
	 * Checks for today's post ID, or else gets a new post ID.
	 */
	if ( get_transient( 'wp_archive_updater_todays_page' ) ) {
		$post_id       = intval( get_transient( 'wp_archive_updater_todays_page' ) );
		$ignored_posts = get_option( 'wp_archive_updater_ignored' );

		if ( $ignored_posts && in_array( $post_id, $ignored_posts, true ) ) {
			get_new_post();
		}

		/**
		 * Checks to see whether the currently stored post ID has been updated more
		 * recently than the expiration date option and gets a new one if so.
		 */
		$current_date           = new \DateTime( strtotime( gmdate( get_option( 'Y-m-d' ) ) ) );
		$last_update_date       = new \DateTime( get_the_modified_date( 'Y-m-d', $post_id ) );
		$days_since_last_update = $last_update_date->diff( $current_date )->days;
		$expiration_date        = get_option( 'lfaf_expiration_date' );

		if ( $days_since_last_update <= $expiration_date ) {
			delete_transient( 'wp_archive_updater_todays_page' );
			$post_id = get_new_post();
		}
	} else {
		$post_id = get_new_post();
	}

	/**
	 * Outputs the widget.
	 */
	if ( current_user_can( 'edit_others_posts' ) ) {
		if ( $post_id ) {
			$post_url           = get_edit_post_link( $post_id );
			$post_title         = get_the_title( $post_id );
			$post_last_modified = esc_html( human_time_diff( get_post_modified_time( 'U', true, $post_id ), time() ) );

			if ( empty( $post_title ) ) {
				$post_title = __( 'Untitled', 'little-free-archive-freshener' );
			}
			?>

			<p>
				<?php esc_html_e( 'This looks like it could use an update:', 'little-free-archive-freshener' ); ?>
			</p>
			<strong style="font-size: 150%;">
				<a 
					href="<?php echo esc_url( $post_url ); ?>" 
					title="<?php echo esc_attr( __( 'Click to edit this.', 'little-free-archive-freshener' ) ); ?>"
				>
					<?php echo esc_html( $post_title ); ?>
				</a>
			</strong>
			<br />
			<small>
				<?php
				echo esc_html(
					sprintf(
						// Translators: %s is the time since the post was last updated.
						__( 'It was last updated %s ago.', 'little-free-archive-freshener' ),
						$post_last_modified
					)
				);
				?>
			</small>

			<?php
			$lfaf_nonce = wp_create_nonce( 'lfaf_nonce' );

			$skip_link = '<a href="' . add_query_arg(
				array(
					'lfaf_nonce'          => $lfaf_nonce,
					'lfaf_post_id'        => $post_id,
					'lfaf_archive_action' => 'skip',
				)
			) . '">' . __( 'skip it for now', 'little-free-archive-freshener' ) . '</a>';

			$ignore_link = '<a href="' . add_query_arg(
				array(
					'lfaf_nonce'          => $lfaf_nonce,
					'lfaf_post_id'        => $post_id,
					'lfaf_archive_action' => 'ignore',
				)
			) . '">' . __( 'ignore it forever', 'little-free-archive-freshener' ) . '</a>';

			$post_query = new \WP_Query( get_new_post_args() );
			?>

			<p>
				<small>
					<?php
					echo wp_kses_post(
						sprintf(
							// Translators: %1$s is the number of other posts that could use an update. %2$s is the skip link. %3$s is the ignore link.
							__( 'Update it to get a new suggestion (there are %1$s more). You can also %2$s or %3$s.', 'little-free-archive-freshener' ),
							$post_query->found_posts - 1,
							$skip_link,
							$ignore_link
						)
					);
					?>
				</small>
			</p>
			<?php
		} else {
			?>
			<p>
				<?php esc_html_e( 'There\'s nothing left to update!', 'little-free-archive-freshener' ); ?>
			</p>
			<p>
				<small>
					<?php
					echo wp_kses_post(
						sprintf(
							// Translators: %1$s and %2$s contain an `<a>` tag with a link to the Settings > Writing page.
							__( '(If this doesn\'t seem right, try %1$sadding more post types%2$s.)', 'little-free-archive-freshener' ),
							'<a href="' . get_admin_url( '', 'options-writing.php' ) . '">',
							'</a>'
						)
					);
					?>
				</small>
			</p>
			<?php
		}
	} else {
		global $wp_roles;
		$roles_with_edit_others_posts = array();

		foreach ( $wp_roles->roles as $role ) {
			if ( $role['capabilities']['edit_others_posts'] ) {
				$roles_with_edit_others_posts[] = '<strong>' . $role['name'] . '</strong>';
			}
		}

		$formatted_roles = get_formatted_list( $roles_with_edit_others_posts );

		?>
		<p>
			<strong>
				<?php echo wp_kses_post( 'Sorry, you don\'t have permision to update posts.', 'little-free-archive-freshener' ); ?>
			</strong>
		</p>
		<p>
			<?php
			echo wp_kses_post( 'In order to use this plugin you must have one of these user roles:', 'little-free-archive-freshener' );
			echo ' ' . wp_kses_post( $formatted_roles );
			?>
		</p>
		<p>
			<small>
				<?php echo wp_kses_post( 'You can hide this widget by clicking on the <strong>Screen Options</strong> tab at the top of this page.' ); ?>
			</small>
		</p>
		<?php
	}
}


/**
 * Gets a new post when requested
 *
 * @return int/bool The new post ID or false
 */
function get_new_post() {
	$post_query = new \WP_Query( get_new_post_args() );
	if ( $post_query->have_posts() ) :
		while ( $post_query->have_posts() ) :
			$post_query->the_post();
			$post_id = get_the_ID();
		endwhile;
	endif;

	if ( isset( $post_id ) && $post_id ) {
		$now                 = time();
		$midnight            = mktime( 0, 0, 0, gmdate( 'm' ), gmdate( 'd' ) + 1, gmdate( 'Y' ) );
		$seconds_to_midnight = $midnight - $now;

		set_transient( 'wp_archive_updater_todays_page', $post_id, $seconds_to_midnight );
		return $post_id;
	} else {
		return false;
	}
}


/**
 * Constructs an `$args` array for `get_new_post()`
 *
 * @return array An array of query args
 */
function get_new_post_args() {
	if ( get_option( 'wp_archive_updater_ignored' ) ) {
		$exclude = get_option( 'wp_archive_updater_ignored' );
	} else {
		$exclude = array();
	}

	$expiration_date = get_option( 'lfaf_expiration_date' );
	$post_types      = get_option( 'lfaf_included_post_types' );

	$args = array(
		'date_query'     => array(
			array(
				'column' => 'post_modified_gmt',
				'before' => $expiration_date . ' days ago',
			),
		),
		'fields'         => 'ids',
		'orderby'        => 'rand',
		'post__not_in'   => $exclude,
		'posts_per_page' => 1,
		'post_status'    => 'publish',
		'post_type'      => $post_types,
	);
	return $args;
}


/**
 * Handles the skip post logic
 *
 * @param int $post_id The ID of the post to skip.
 */
function skip_post( $post_id ) {
	$todays_page = intval( get_transient( 'wp_archive_updater_todays_page' ) );

	if (
		$todays_page
		&& $todays_page === $post_id
	) {
		delete_transient( 'wp_archive_updater_todays_page' );
	}
}


/**
 * Handles the ignore post logic
 *
 * @param int $post_id The ID of the post to ignore.
 */
function ignore_post( $post_id ) {
	if ( get_option( 'wp_archive_updater_ignored' ) ) {
		$ignored_posts = get_option( 'wp_archive_updater_ignored' );

		if ( ! in_array( $post_id, $ignored_posts, true ) ) {
			$ignored_posts[] = $post_id;
			update_option( 'wp_archive_updater_ignored', $ignored_posts );
		}
	} else {
		update_option( 'wp_archive_updater_ignored', array( $post_id ) );
	}
	skip_post( $post_id );
}


/**
 * Converts an array of strings into a comma-separated list for output
 *
 * @param array  $array_of_items The array of strings to format.
 * @param string $conjunction Generally "and" or "or".
 * @return string The formatted list of items.
 */
function get_formatted_list( $array_of_items, $conjunction = 'or' ) {
	$count          = count( $array_of_items );
	$formatted_list = '';

	switch ( $count ) {
		case 1:
			$formatted_list .= $array_of_items[0];
			break;
		case 2:
			$formatted_list .= $array_of_items[0] . ' ' . $conjunction . ' ' . $array_of_items[1];
			break;
		default:
			$key = 1;
			foreach ( $array_of_items as $entry ) {
				$formatted_list .= $entry;
				if ( $key < $count ) {
					$formatted_list .= ', ';
				}
				if ( $key === $count - 1 ) {
					$formatted_list .= $conjunction . ' ';
				}
				++$key;
			}
	}
	return $formatted_list;
}
