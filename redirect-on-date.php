<?php
/**
 * WPGHF Redirects
 *
 * @since 0.0.1
 * @package WPGHF
 */
class WPGHF_Redirects {
	/**
	 * Parent plugin class
	 *
	 * @var   class
	 * @since 0.0.1
	 */
	protected $plugin = null;

	/**
	 * Prefix for meta
	 * @var string
	 * @since 0.0.1
	 */
	public static $p = 'wpghf_';

	/**
	 * Custom Post Types to support
	 * @var array
	 */
	public static $types = array( 'page', 'post' );

	/**
	 * Constructor
	 *
	 * @since  0.0.1
	 * @param  object $plugin Main plugin object.
	 * @return void
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();
	}
	/**
	 * Initiate our hooks
	 *
	 * @since  0.0.1
	 * @return void
	 */
	public function hooks() {

		// CMB2 metabox
		add_action( 'cmb2_admin_init', array( $this,'metabox' ) );

		// Only run our customization on the 'edit.php' page in the admin.
		add_action( 'load-edit.php',   array( $this, 'edit_page_load' ) );

		// Load for each post type
		foreach ( self::$types as $type ) {
			add_filter( 'manage_edit-'	. $type . '_columns', 				array( $this, 'add_columns' ) );
			add_filter( 'manage_edit-'	. $type . '_sortable_columns', 		array( $this, 'make_sortable' ) );
			add_action( 'manage_'		. $type . '_posts_custom_column', 	array( $this, 'columns_content' ), 10, 2 );
		}

		// Redirect logic
		add_action( 'template_redirect', array( $this, 'maybe_redirect' ) );

	}

	/**
	 * Add metabox to posts and pages for redirect metadata.
	 * 
	 * @since  	0.0.1 redirect_metabox
	 * @since  	0.0.1 redirect_to, time_to_redirect
	 * @see 	maybe_redirect();
	 */
	function metabox() {

		$p = self::$p;

		$cmb = new_cmb2_box( array(
			'id' 			=> $p . 'redirect_metabox',
			'title' 		=> __( 'Redirect Meta', 'wpghf' ),
			'object_types'	=> self::$types,
			'context' 		=> 'normal',
			'priority' 		=> 'default',
		) );

		$now = date( 'm-d-y, H:i' );

		$cmb->add_field( array(
			'name' 	=> __( 'Time and date to start redirecting', 'wpghf' ),
			'desc' 	=> __( 'Will redirect any time after this. Compares to webserver time, currently: ' . $now . ' (mm-dd-yy, hh:mm)', 'wpghf' ),
			'id' 	=> $p . 'time_to_redirect',
			'type' 	=> 'text_datetime_timestamp',
		) );

		$cmb->add_field( array(
			'name' 				=> __( 'Redirect to...', 'wpghf' ),
			'id' 				=> $p . 'redirect_to',
			'type' 				=> 'post_search_text', // This field type
			'post_type' 		=> array( 'page' ),
			'desc' 				=> __( 'Use search icon popup to find a page, or enter the <code>Post ID</code> to redirect to. You may also enter a fully qualified url. For example: <code>https://wpguru4u.com</code>', 'wpghf' ),
			'select_type' 		=> 'radio',
			'select_behavior' 	=> 'replace',
		) );

	}

	/**
	 * Add redirect column to admin
	 * 
	 * @param  array $columns 	array of columns
	 * @return array          	array of columns with our redirect column
	 * @since  0.0.1
	 */
	public function add_columns( $columns ) {

		$new_column = array(
			'redirect' => __( 'Redirect' ),
		);

		return array_merge( $columns, $new_column );
	}

	/**
	 * Only run our customization on the 'edit.php' page in the admin
	 * 
	 * @return void
	 * @since  0.0.1
	 */
	public function edit_page_load() {
		add_filter( 'request', array( $this, 'sort_redirect_column' ) );
	}

	/**
	 * Add our Redirect column to sortable columns
	 *  
	 * @param  array $columns array of sortable columns
	 * @return array          array with redirect column
	 * @since  0.0.1
	 */
	public function make_sortable( $columns ) {
		$columns['redirect'] = 'redirect';
		return $columns;
	}

	/**
	 * Show redirect meta in admin columns
	 * 
	 * @param  string $column  	post type
	 * @param  int $post_id 	Post ID
	 * @return string   
	 * @since  0.0.1
	 */
	public function columns_content( $column, $post_id ) {
		global $post;

		switch( $column ) {

			/* If displaying the 'redirect' column. */
			case 'redirect' :
				$p = self::$p;

				/* Get the post meta. */
				$where = get_post_meta( $post->ID, $p . 'redirect_to', true );

				if ( ! empty( $where ) ) {
					$now   = time();
					$then  = get_post_meta( $post->ID, $p . 'time_to_redirect', true );


					if ( (int) $where ) {
						$link = get_permalink( $where );
					} else {
						$link = $where;
					}	

					if ( $now > $then ) {
						$color = '#4caf50';
						$icon  = 'external';
						$title = $link;			
					} else {
						$color = '#888';
						$icon  = 'calendar-alt';
						$title = date("F j, Y, g:i a", $then );
					}

					echo '<a href=" ' . $link . ' " target="_blank"><span class="dashicons dashicons-' . $icon . '" style="color:' . $color . ';" title="' . $title . '"></span></a>';

				}
				break;

			/* Just break out of the switch statement for everything else. */
			default :
				break;
		}
	}

	/** 
	 * Sorts the redirects.
	 * @since  0.0.1
	 */
	public function sort_redirect_column( $vars ) {

		if ( ! self::$types )
			return;

		foreach ( self::$types as $type ) {

			/* Check if we're viewing the post type. */
			if ( isset( $vars['post_type'] ) && $type == $vars['post_type'] ) {
				/* Check if 'orderby' is set to 'redirect'. */
				if ( isset( $vars['orderby'] ) && 'redirect' == $vars['orderby'] ) {

					/* Merge the query vars with our custom variables. */
					$vars = array_merge(
						$vars,
						array(
							'meta_key' => self::$p . 'redirect_to',
							'orderby' => 'meta_value_num'
						)
					);
				}
			}

		}

		return $vars;
	}

	/**
	 * Maybe redirect 
	 * 
	 * @since 0.0.1
	 */
	public function maybe_redirect() {
		global $post;
		if ( ! is_object( $post ) ) {
			return;
		}

		$where = get_post_meta( $post->ID, self::$p . 'redirect_to', true );
		if ( ! $where ) 
			return;

		$now = time();
		$then =  get_post_meta( $post->ID, self::$p . 'time_to_redirect', true );

		if ( $now > $then && $where ) {
			self::redirect( $where );
		}
	}

	/**
	 * Redirect 
	 * 
	 * @since 0.0.1
	 */
	public static function redirect( $where ) {
		if ( $where ) {
			if ( (int) $where ) {
				wp_safe_redirect( esc_url( get_permalink( $where ) ) );
			} else {
				wp_redirect( esc_url( $where ) );
			}
			exit;
		}
	}

}
