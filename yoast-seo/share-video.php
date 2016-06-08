<?php
/**
 * Adds markup to Yoast SEO plugin output
 */
class Oak_YS_Share_Video {
	static $url;
	static $vid;
	static $img;
	static $src;

	/**
	 * Constructor
	 *
	 * @since 0.1.0
	 * @return  null
	 */
	public function __construct() {
		self::init();
	}

	public function init() {
	//	$content = do_shortcode( apply_filters( 'the_content', $post->post_content ) );
	//	preg_match('#http://w?w?w?.?youtube.com/watch\?v=([A-Za-z0-9\-_]+)#s', $posts->post_content, $matches);
		global $post;
		if ( is_singular() && 'video' === get_post_format( $post->ID ) ) {

			preg_match('#htt((p|ps)\:)?//((m|www)\.)?youtube\.com/watch.*#i', $post->post_content, $matches);
			if ( $matches[0] ) {
				$url = $matches[0];
				$type = 'youtube';
			} else {
				preg_match( '#https?://(.+\.)?vimeo\.com/.*#i', $post->post_content, $matches );
				if ( $matches[0] ){
					$url = $matches[0];
					$type = 'vimeo';
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
					$hash = unserialize( file_get_contents( 'http://vimeo.com/api/v2/video/' . $vid . '.php' ) );
					$img = $hash[0]['thumbnail_large'];
				}

				self::$url = $url;
				self::$vid = $vid;
				self::$img = $img;
				self::$src = $src;
				self::hooks();
			}
		}
	}
	/**
	 * Initiate our hooks
	 *
	 * @since 0.1.0
	 * @return  null
	 */
	public function hooks() {
		add_filter( 'wpseo_opengraph_image', 	array( __CLASS__, 'oak_opengraph_image' ), 5, 1 );
		add_filter( 'wpseo_twitter_image', 		array( __CLASS__, 'oak_opengraph_image' ), 5, 1 );
		add_filter( 'wpseo_opengraph_type', 	array( __CLASS__, 'oak_opengraph_type' ), 10, 1 );
	//	add_filter( 'wpseo_twitter_card_type', 	array( __CLASS__, 'oak_twitter_card_type' ), 10, 1 );
		global $wpseo_og;
		remove_action( 'wpseo_opengraph', array( $wpseo_og, 'tags' ), 16 );
		remove_action( 'wpseo_opengraph', array( $wpseo_og, 'category' ), 17 );
		remove_action( 'wpseo_opengraph', array( $wpseo_og, 'publish_date' ), 19 );
		add_action( 'wpseo_opengraph', array( __CLASS__, 'oak_opengraph_video' ) );
		add_action( 'wpseo_opengraph', array( __CLASS__, 'oak_video_tags' ), 16 );
	//	add_action( 'wpseo_twitter', array( __CLASS__, 'oak_opengraph1' ), 50 );

	}

	function oak_opengraph_image( $img ) {
		return self::$img;
	}
	function oak_opengraph_video() {
		echo '<meta property="og:video" content="' . self::$url . '" />' . "\n";
	}

	function oak_opengraph1() {
		echo '<meta property="twitter:player" content="' . self::$url . '" />' . "\n";
	}

	function oak_opengraph_type( $type ) {
		return 'video.movie';
	}

	function oak_twitter_card_type( $type ) {
		return 'summary';
	}

	function oak_video_tags() {
		$tags = get_the_tags();
		if ( ! is_wp_error( $tags ) && ( is_array( $tags ) && $tags !== array() ) ) {
			foreach ( $tags as $tag ) {
				echo '<meta property="video:tag" content="' . $tag->name . '" />' . "\n";
			}
		}
	}
}

// Hook in early and fire it up
function oak_ys_share_video_opengraph(){
	Oak_YS_Share_Video::init();
}
add_action( 'wpseo_opengraph', 'oak_ys_share_video_opengraph', -1 );
