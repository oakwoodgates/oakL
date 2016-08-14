<?php

// ========================================================
// Head
// ========================================================

/**
 * Add css or js to wp-login screen
 * @return [type] [description]
 */
function oak_login_scripts() {
	wp_enqueue_style( 'custom-login', get_stylesheet_directory_uri() . '/style-login.css' );
	wp_enqueue_script( 'custom-login', plugin_dir_url( __FILE__ ) . 'style-login.js' );
}
add_action( 'login_enqueue_scripts', 'oak_login_scripts' );

/**
 * Add css to wp-login.php screen.
 * @return string  html/css
 * @link   https://codex.wordpress.org/Customizing_the_Login_Form#Styling_Your_Login
 *
 * Here's some helpful, highly specifc CSS selectors for the login page:
 * body.login {}														// body background image
 * body.login div#login {}												// content wrapper
 * body.login div#login h1 {}
 * body.login div#login h1 a {} 										// logo
 * body.login div#login form#loginform {}
 * body.login div#login form#loginform p {}
 * body.login div#login form#loginform p label {}
 * body.login div#login form#loginform input {}
 * body.login div#login form#loginform input#user_login {}
 * body.login div#login form#loginform input#user_pass {}
 * body.login div#login form#loginform p.forgetmenot {}
 * body.login div#login form#loginform p.forgetmenot input#rememberme {}
 * body.login div#login form#loginform p.submit {}
 * body.login div#login form#loginform p.submit input#wp-submit {}
 * body.login div#login p#nav {}
 * body.login div#login p#nav a {}
 * body.login div#login p#backtoblog {}
 * body.login div#login p#backtoblog a {}
 */
function oak_login_css() { ?>
	<?php
	//	$img = content_url() . '/uploads/2016/04/logo_btc.png';
		$img = get_stylesheet_directory_uri() . '/images/site-login-logo.png';
	?>
	<style type="text/css">
		body.login div#login h1 a {
			background-image: url(<?php echo $img ?>);
		}
	</style>
<?php }
add_action( 'login_head', 'oak_login_css' );

// ========================================================
// Logo
// ========================================================

/**
 * Filter the wp-login.php logo link
 * @return [type] [description]
 */
function oak_login_logo_url() {
	return home_url();
}
add_filter( 'login_headerurl', 'oak_login_logo_url' );

/**
 * Filter the wp-login.php logo link title
 * @return [type] [description]
 */
function oak_login_logo_url_title() {
	return bloginfo( 'name' );
}
add_filter( 'login_headertitle', 'oak_login_logo_url_title' );

// ========================================================
// After opening <form> tag
// ========================================================

/**
 * [oak_login_form_top description]
 * @param  string $content [description]
 * @param  arry   $args    [description]
 * @return string          [description]
 */
function oak_login_form_top( $content, $args ) {
	return $content;
}
add_filter( 'login_form_top', 'oak_login_form_top', 10, 2 );

// ========================================================
// After inputs
// ========================================================

/**
 * [oak_login_form_middle description]
 * @param  string $content [description]
 * @param  arry   $args    [description]
 * @return string          [description]
 */
function oak_login_form_middle( $content, $args ) {
	return $content;
}
add_filter( 'login_form_top', 'oak_login_form_middle', 10, 2 );

// ========================================================
// Before closing </form> tag
// ========================================================

/**
 * [oak_login_form_bottom description]
 * @param  string $content [description]
 * @param  arry   $args    [description]
 * @return string          [description]
 */
function oak_login_form_bottom( $content, $args ) {
	return $content;
}
add_filter( 'login_form_top', 'oak_login_form_bottom', 10, 2 );

// ========================================================
// Links
// ========================================================

/**
 * Edit "Register" link on wp-login.php. This example shows adding a class and
 * changing the anchor text. Recommended to use wp_registration_url() as link.
 *
 * @return string  Link
 */
function oak_register_link() {
	return sprintf( '<a href="%s" class="oak_register">%s</a>', esc_url( wp_registration_url() ), __( 'Click here to sign up', 'textdomain' ) );
}
add_filter( 'register', 'oak_register_link', $url );

// ========================================================
// After the login form
// ========================================================

/**
 * [oak_login_footer description]
 * @return [type] [description]
 */
function oak_login_footer() {
	echo '';
}
add_action( 'login_footer', 'oak_login_footer' );

// ========================================================
// Redirects
// ========================================================

/**
 * Redirect user on login
 * @param string 			$url 		          	The redirect destination URL.
 * @param string 			$requested_redirect_to 	The requested redirect destination URL passed as a parameter.
 * @param WP_User|WP_Error 	$user                  	WP_User object if login was successful, WP_Error object otherwise.
 * @return string      		The redirect destination URL
 */
function oak_login_redirect( $url, $requested_redirect_to, $user ) {
	return esc_url( home_url( 'my-account' ) );
}
add_filter( 'login_redirect', 'oak_login_redirect', 10, 3 );

/**
 * Redirect user after logout
 * @param string 			$url 		          	The redirect destination URL.
 * @param string 			$requested_redirect_to 	The requested redirect destination URL passed as a parameter.
 * @param WP_User|WP_Error 	$user                  	WP_User object if login was successful, WP_Error object otherwise.
 * @return string      		The redirect destination URL
 */
function oak_logout_redirect( $url, $requested_redirect_to, $user ) {
	$url = esc_url( home_url() );
	return $url;
}
add_filter( 'login_redirect', 'oak_logout_redirect', 10, 3 );
