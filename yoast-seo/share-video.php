<?php
/**
 * Adds markup to Yoast SEO plugin output
 */
class Oak_Video_Seo {
	static $url;
	static $vid;
	static $img;
	static $src;

	/**
	 * Singleton instance of plugin
	 *
	 * @var Oak_Video_Seo
	 * @since  0.1.0
	 */
	protected static $single_instance = null;

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @since  0.1.0
	 * @return A single instance of this class.
	 */
	public static function get_instance() {
		if ( null === self::$single_instance ) {
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}

	/**
	 * Constructor
	 *
	 * @since 0.1.0
	 * @return  null
	 */
	private function __construct() {
		self::init();
	}

	private function init() {
		$url = $vid = $img = $src = '';
		global $post;
		if ( is_singular() && 'video' === get_post_format( $post->ID ) ) {

			preg_match('#htt((p|ps)\:)?//((m|www)\.)?youtube\.com/watch.*#i', $post->post_content, $matches);
			if ( $matches && $matches[0] ) {
				$url = $matches[0];
				$src = 'youtube';
			} else {
				preg_match( '#https?://(.+\.)?vimeo\.com/.*#i', $post->post_content, $matches );
				if ( $matches && $matches[0] ){
					$url = $matches[0];
					$src = 'vimeo';
				}
			}
			if ( $url ) {
				$parts = parse_url( $url );
				if ( isset( $parts['query'] ) ) {
					parse_str( $parts['query'], $qs );
					if ( isset( $qs['v'] ) ) {
						$vid = $qs['v'];
						(substr($vid,-1)=='_') ? $vid=substr($vid, 0, -1) : $vid;
						$img = 'https://i.ytimg.com/vi/'.$vid.'/hqdefault.jpg';
					} else if ( isset( $qs['vi'] ) ) {
						$vid = $qs['vi'];
						(substr($vid,-1)=='_') ? $vid=substr($vid, 0, -1) : $vid;
						$img = 'https://i.ytimg.com/vi/'.$vid.'/hqdefault.jpg';
					}

				}

				if ( ! $vid && isset( $parts['path'] ) ) {
					$path = explode( '/', trim( $parts['path'], '/' ) );
					$vid = $path[count($path)-1];
					$vid = str_replace('_', '', $vid);
					$hash = unserialize( file_get_contents( 'http://vimeo.com/api/v2/video/' . $vid . '.php' ) );
					$img = $hash[0]['thumbnail_large'];
					$src = 'vimeo';
				}
			}
		} else if ( is_singular() && 'status' === get_post_format( $post->ID ) ) {
			preg_match('#https?://(www\.)?instagr(\.am|am\.com)/p/.*#i', $post->post_content, $matches);
			if ( $matches && $matches[0] ) {
				$url = $matches[0];
				$src = 'instagram';
				$parts = parse_url( $url );
				if ( isset( $parts['path'] ) ) {
					$path = explode( '/', trim( $parts['path'], '/' ) );
					$vid = $path[1];
					// https://instagram.com/p/tsxp1hhQTG/media
					$new = 'https://instagram.com/p/' . $vid . '/media';
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $new);
					curl_setopt($ch, CURLOPT_HEADER, 1);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
					curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);
					curl_setopt($ch, CURLOPT_TIMEOUT, 300);
					$data = curl_exec($ch);
					curl_close($ch);

					preg_match( '#https?://.*jpg#i', $data, $img_match );

					if ( $img_match && $img_match[0] ) {
						$img = $img_match[0];
					}
				}
			}
		}

		if ( $img ) {
			self::$img = $img;
			self::$url = $url;
			self::$vid = $vid;
			self::$src = $src;
			self::hooks();
		}

	}

	/**
	 * Initiate our hooks
	 *
	 * @since 0.1.0
	 * @return  null
	 */
	private function hooks() {
		add_filter( 'wpseo_opengraph_image', 	array( __CLASS__, 'oak_opengraph_image' ), 5, 1 );
		add_filter( 'wpseo_twitter_image', 		array( __CLASS__, 'oak_opengraph_image' ), 5, 1 );
		if ( self::$src && ( 'youtube' === self::$src || 'vimeo' === self::$src ) ) {
			global $wpseo_og;
			remove_action( 'wpseo_opengraph', array( $wpseo_og, 'tags' ), 16 );
			remove_action( 'wpseo_opengraph', array( $wpseo_og, 'category' ), 17 );
			remove_action( 'wpseo_opengraph', array( $wpseo_og, 'publish_date' ), 19 );
			add_action( 'wpseo_opengraph', array( __CLASS__, 'oak_tags' ), 16 );
			add_action( 'wpseo_opengraph', array( __CLASS__, 'oak_opengraph_video' ) );
			add_filter( 'wpseo_opengraph_type', 	array( __CLASS__, 'oak_opengraph_type' ), 10, 1 );
		//	add_action( 'wpseo_twitter', array( __CLASS__, 'oak_opengraph1' ), 50 );
		//	add_filter( 'wpseo_twitter_card_type', 	array( __CLASS__, 'oak_twitter_card_type' ), 10, 1 );
		}
	}

	public static function oak_opengraph_image( $img ) {
		if ( self::$img ) {
			$img = self::$img;
		}
		return $img;
	}

	public static function oak_opengraph_video() {
		echo '<meta property="og:video" content="' . self::$url . '" />' . "\n";
	}

//	public static function oak_opengraph1() {
//		echo '<meta property="twitter:player" content="' . self::$url . '" />' . "\n";
//	}

	public static function oak_opengraph_type( $type ) {
	//	if ( self::$src && ( 'youtube' === self::$src || 'vimeo' === self::$src ) ) {
			$type = 'video.movie';
	//	}
		return $type;
	}

//	public static function oak_twitter_card_type( $type ) {
//		return 'summary';
//	}

	public static function oak_tags() {
		$tags = get_the_tags();
		if ( ! is_wp_error( $tags ) && ( is_array( $tags ) && $tags !== array() ) ) {
			foreach ( $tags as $tag ) {
				echo '<meta property="video:tag" content="' . $tag->name . '" />' . "\n";
			}
		}
	}
}

// Hook in early and fire it up
function oak_opengraph(){
	Oak_Video_Seo::get_instance();
}
add_action( 'wpseo_opengraph', 'oak_opengraph', -1 );
