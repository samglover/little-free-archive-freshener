<?php
/**
 * Options
 *
 * @file options.php
 * @package LFAF
 * @since 1.0.0
 */

namespace ARCHIVE_FRESHENER;

add_action( 'admin_init', __NAMESPACE__ . '\options_init' );
/**
 * Initialize options
 */
function options_init() {
	add_settings_section(
		'lfaf-options',
		__( 'Little Free Archive Freshener Options', 'little-free-archive-freshener' ),
		__NAMESPACE__ . '\lfaf_options_section',
		'writing',
	);

	register_setting( 'writing', 'lfaf_expiration_date' );
	/**
	 * Set expiration date
	 */
	add_settings_field(
		'lfaf_expiration_date',
		'<label for="lfaf_expiration_date">' . __( 'Freshen After', 'little-free-archive-freshener' ) . '</label>',
		__NAMESPACE__ . '\lfaf_expiration_date',
		'writing',
		'lfaf-options',
	);

	register_setting( 'writing', 'lfaf_included_post_types' );
	/**
	 * Select post types
	 */
	add_settings_field(
		'lfaf_included_post_types',
		'<label for="lfaf_included_post_types">' . __( 'Include Post Types', 'little-free-archive-freshener' ) . '</label>',
		__NAMESPACE__ . '\lfaf_included_post_types',
		'writing',
		'lfaf-options',
	);

	register_setting( 'writing', 'lfaf_clear_ignored' );
	/**
	 * Clear ignored posts
	 */
	add_settings_field(
		'lfaf_clear_ignored',
		'<label for="lfaf_clear_ignored">' . __( 'Clear Ignored Posts', 'little-free-archive-freshener' ) . '</label>',
		__NAMESPACE__ . '\lfaf_clear_ignored',
		'writing',
		'lfaf-options',
	);
}


/**
 * Options section
 */
function lfaf_options_section() {
	?>
	<p>
		<?php esc_html_e( 'Options for the Freshen Up Your Archives dashboard widget.', 'little-free-archive-freshener' ); ?>
	</p>
	<?php
}


/**
 * Expiration date field
 */
function lfaf_expiration_date() {
	$expiration_date = get_option( 'lfaf_expiration_date' );
	?>
	<input 
		name="lfaf_expiration_date" 
		class="small-text" 
		type="number" 
		required aria-required="true" 
		value="<?php echo esc_html( $expiration_date ); ?>"
	>
	<?php esc_html_e( 'days', 'little-free-archive-freshener' ); ?>
	<p class="description">
		<?php
		echo esc_html(
			sprintf(
				// Translators: %s is the current expiration date value.
				__( 'Items older than this will be fetched. Items that have been updated within the last %s days are "fresh" and will not be fetched.', 'little-free-archive-freshener' ),
				$expiration_date
			)
		);
		?>
	</p>
	<?php
}

/**
 * Included post types checkboxes
 */
function lfaf_included_post_types() {
	$post_types          = get_post_types( array( 'public' => true ) );
	$included_post_types = get_option( 'lfaf_included_post_types' );
	?>
	<fieldset>
		<?php foreach ( $post_types as $key => $post_type ) { ?>
			<?php
			if ( in_array( $post_type, $included_post_types, true ) ) {
				$checked = ' checked';
			} else {
				$checked = '';
			}
			?>
			<p>
				<label for="lfaf_included_post_types[<?php echo esc_attr( $key ); ?>]">
					<input 
						name="lfaf_included_post_types[<?php echo esc_attr( $key ); ?>]"
						id="lfaf_included_post_types[<?php echo esc_attr( $key ); ?>]"
						type="checkbox" 
						value="<?php esc_attr( $post_type ); ?>"
						<?php echo esc_attr( $checked ); ?>
					>
					<?php echo esc_html( $post_type ); ?>
				</label>
			</p>
			<?php unset( $checked ); ?>
		<?php } ?>
	</fieldset>
	<?php
}


/**
 * Clear ignored posts checkbox
 */
function lfaf_clear_ignored() {
	?>
	<fieldset>
		<p>
			<label for="lfaf_clear_ignored">
				<input
					name="lfaf_clear_ignored" 
					id="lfaf_clear_ignored" 
					type="checkbox" value="1"
				>
				<?php esc_html_e( 'Clear the list of ignored posts.', 'little-free-archive-freshener' ); ?>
			</label>
		</p>
		<p class="description">
			<?php esc_html_e( 'If you check this box and then save changes, it will clear the list of items for which you have previously clicked "ignore forever." This box will not remain checked after you save changes.', 'little-free-archive-freshener' ); ?>
		</p>
	</fieldset>
	<?php
}
