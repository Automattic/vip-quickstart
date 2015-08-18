<?php
/**
 * @see pmc-branch-switch.php
*/

// execute this code in local-config before anything else
if ( ! function_exists( 'add_action' ) && !defined('PMC_BRANCH_SWITCH') ) {

	// set flag to indicate code has been run
	define('PMC_BRANCH_SWITCH',true);

	// use anonymous function to create a local scope to avoid global variables conflict
	call_user_func(function(){
		$network_domain    = 'qa.pmc.com';
		$cookie_domain     = 'qa.pmc.com';
		$site_slug         = '';
		$prefix            = '';
		$is_network_domain = false;
		$redirect_to_host  = false;
		$is_vip_local      = false;

		if ( $network_domain == $_SERVER['HTTP_HOST'] ) {
			define( 'COOKIE_DOMAIN', $cookie_domain );
			define( 'COOKIEHASH', md5( $cookie_domain ) );
			return;
		}

		if ( preg_match( '/(?:(.+)\.)?([^.]+)\.'. preg_quote( $network_domain ).'$/si',$_SERVER['HTTP_HOST'], $matches ) ) {
			$site_slug         = $matches[2];
			$prefix            = $matches[1];
			$is_network_domain = true;
		}
		elseif ( preg_match( '/(?:(.+)\.)?qa\.([^.]+)\.com$/si',$_SERVER['HTTP_HOST'], $matches ) ) {
			$site_slug     = $matches[2];
			$prefix        = $matches[1];
			$cookie_domain = "qa.{$site_slug}.com";
		} else {
			// vip.local development
			if ( 'vip.local' == $_SERVER['HTTP_HOST'] ) {
				define( 'COOKIE_DOMAIN', 'vip.local' );
				define( 'COOKIEHASH', md5( 'vip.local' ) );
				return;
			}
			if ( preg_match( '/(?:(.+)\.)?([^.]+)\.vip.local$/si',$_SERVER['HTTP_HOST'], $matches ) ) {
				$network_domain    = 'vip.local';
				$cookie_domain     = 'vip.local';
				$site_slug         = $matches[2];
				$prefix            = $matches[1];
				$is_network_domain = true;
				$is_vip_local      = true;
			}
		}

		if ( !empty( $site_slug ) ) {
			if ( ! preg_match('/(wp-login|admin-ajax)/',$_SERVER['REQUEST_URI']) ) {
				if ( preg_match('/^\/(wp-admin)/', $_SERVER['REQUEST_URI'] ) ) {
					if ( preg_match('/^\/wp-admin\/network/', $_SERVER['REQUEST_URI'] ) ) {
						if ( $network_domain != $_SERVER['HTTP_HOST'] ) {
							$redirect_to_host = $network_domain;
						}
					} elseif ( ! $is_network_domain ) {
						$redirect_to_host = ( $prefix ? $prefix .'.' : '' ) ."{$site_slug}.{$network_domain}";
					}
				} else {
					if ( $is_network_domain && ! $is_vip_local ) {
						$redirect_to_host = ( $prefix ? $prefix .'.' : '' ) ."qa.{$site_slug}.com";
					}
				}

				if ( ( empty( $_SERVER['REQUEST_METHOD'] ) || 'POST' != $_SERVER['REQUEST_METHOD'] )
						&& $redirect_to_host && ! ( defined('WP_CLI') && WP_CLI )
					) {
					$redirect_to = 'http' . ( !empty( $_SERVER['HTTPS'] ) ? 's' : '') .'://'. $redirect_to_host . $_SERVER['REQUEST_URI'];
					header('Location: '. $redirect_to, true, 302);
					die();
				}

			}

			// save the original host name to be restore later
			$_SERVER['REQUEST_HOST'] = $_SERVER['HTTP_HOST'];
			// force subdomain to allow wp lookup blog correctly
			// We can use sunrise.php to look up the host, but we need to define WP_CONTENT_DIR before wp default constant does
			$_SERVER['SERVER_NAME']  = $_SERVER['HTTP_HOST'] = "{$site_slug}.{$network_domain}";
		}

		define( 'COOKIE_DOMAIN', $cookie_domain );
		define( 'COOKIEHASH', md5( $cookie_domain ) );

		if ( ! empty( $prefix ) ) {
			$_SERVER['HTTP_HOST_PREFIX'] = $prefix;
			define( 'WWW_DIR', dirname(dirname( __DIR__ )) );
			define( 'WP_HOME', 'http://' . $_SERVER['HTTP_HOST'] );
			define( 'WP_CONTENT_DIR', WWW_DIR . '/wp-content-sites/' . $_SERVER['HTTP_HOST_PREFIX'] );
			define( 'WP_CONTENT_URL', WP_HOME . '/wp-content-sites/' . $_SERVER['HTTP_HOST_PREFIX'] );
			define( 'WP_PLUGIN_DIR', WWW_DIR .'/wp-content/plugins' );
			define( 'WP_PLUGIN_URL', WP_HOME . '/wp-content/plugins' );
			define( 'WPMU_PLUGIN_DIR', WWW_DIR . '/wp-content/mu-plugins' );
			define( 'WPMU_PLUGIN_URL', WP_HOME . '/wp-content/mu-plugins' );

			if ( ! file_exists( WP_CONTENT_DIR ) ) {
				echo 'Error: Site content folder not found for qa branch '. $_SERVER['HTTP_HOST_PREFIX'];
				die();
			}
		}

	});

	if ( ! function_exists( 'wp_validate_auth_cookie' ) ) {

		/**
		 * Override wp_validate_auth_cookie function to bypass auth verification to allow cross domain authentication
		 * @see wp-includes/pluggable.php
		 *
		 * @global int $login_grace_period
		 *
		 * @param string $cookie Optional. If used, will validate contents instead of cookie's
		 * @param string $scheme Optional. The cookie scheme to use: auth, secure_auth, or logged_in
		 * @return false|int False if invalid cookie, User ID if valid.
		 */
		function wp_validate_auth_cookie($cookie = '', $scheme = '') {
			if ( ! $cookie_elements = wp_parse_auth_cookie($cookie, $scheme) ) {
				/**
				 * Fires if an authentication cookie is malformed.
				 *
				 * @since 2.7.0
				 *
				 * @param string $cookie Malformed auth cookie.
				 * @param string $scheme Authentication scheme. Values include 'auth', 'secure_auth',
				 *                       or 'logged_in'.
				 */
				do_action( 'auth_cookie_malformed', $cookie, $scheme );
				return false;
			}

			$scheme = $cookie_elements['scheme'];
			$username = $cookie_elements['username'];
			$hmac = $cookie_elements['hmac'];
			$token = $cookie_elements['token'];
			$expired = $expiration = $cookie_elements['expiration'];

			// Allow a grace period for POST and AJAX requests
			if ( defined('DOING_AJAX') || 'POST' == $_SERVER['REQUEST_METHOD'] ) {
				$expired += HOUR_IN_SECONDS;
			}

			// Quick check to see if an honest cookie has expired
			if ( $expired < time() ) {
				/**
				 * Fires once an authentication cookie has expired.
				 *
				 * @since 2.7.0
				 *
				 * @param array $cookie_elements An array of data for the authentication cookie.
				 */
				do_action( 'auth_cookie_expired', $cookie_elements );
				return false;
			}

			$user = get_user_by('login', $username);
			if ( ! $user ) {
				/**
				 * Fires if a bad username is entered in the user authentication process.
				 *
				 * @since 2.7.0
				 *
				 * @param array $cookie_elements An array of data for the authentication cookie.
				 */
				do_action( 'auth_cookie_bad_username', $cookie_elements );
				return false;
			}

			$pass_frag = $user->ID;

			$key = wp_hash( $username . '|' . $pass_frag . '|' . $expiration . '|' . $token, $scheme );

			// If ext/hash is not present, compat.php's hash_hmac() does not support sha256.
			$algo = function_exists( 'hash' ) ? 'sha256' : 'sha1';
			$hash = hash_hmac( $algo, $username . '|' . $expiration . '|' . $token, $key );

			if ( ! hash_equals( $hash, $hmac ) ) {
				/**
				 * Fires if a bad authentication cookie hash is encountered.
				 *
				 * @since 2.7.0
				 *
				 * @param array $cookie_elements An array of data for the authentication cookie.
				 */
				do_action( 'auth_cookie_bad_hash', $cookie_elements );
				return false;
			}

			// AJAX/POST grace period set above
			if ( $expiration < time() ) {
				$GLOBALS['login_grace_period'] = 1;
			}

			/**
			 * Fires once an authentication cookie has been validated.
			 *
			 * @since 2.7.0
			 *
			 * @param array   $cookie_elements An array of data for the authentication cookie.
			 * @param WP_User $user            User object.
			 */
			do_action( 'auth_cookie_valid', $cookie_elements, $user );

			return $user->ID;
		}
	} // if ! function exists

	if ( !function_exists('wp_generate_auth_cookie') ) {
		/**
		 * Override wp_generate_auth_cookie to to work in conjunction with wp_validate_auth_cookie
		 * @see wp-includes/pluggable.php
		 *
		 * @since 2.5.0
		 *
		 * @param int    $user_id    User ID
		 * @param int    $expiration Cookie expiration in seconds
		 * @param string $scheme     Optional. The cookie scheme to use: auth, secure_auth, or logged_in
		 * @param string $token      User's session token to use for this cookie
		 * @return string Authentication cookie contents. Empty string if user does not exist.
		 */
		function wp_generate_auth_cookie( $user_id, $expiration, $scheme = 'auth', $token = '' ) {
			$user = get_userdata($user_id);
			if ( ! $user ) {
				return '';
			}

			$pass_frag = $user->ID;

			$key = wp_hash( $user->user_login . '|' . $pass_frag . '|' . $expiration . '|' . $token, $scheme );

			// If ext/hash is not present, compat.php's hash_hmac() does not support sha256.
			$algo = function_exists( 'hash' ) ? 'sha256' : 'sha1';
			$hash = hash_hmac( $algo, $user->user_login . '|' . $expiration . '|' . $token, $key );

			$cookie = $user->user_login . '|' . $expiration . '|' . $token . '|' . $hash;

			/**
			 * Filter the authentication cookie.
			 *
			 * @since 2.5.0
			 *
			 * @param string $cookie     Authentication cookie.
			 * @param int    $user_id    User ID.
			 * @param int    $expiration Authentication cookie expiration in seconds.
			 * @param string $scheme     Cookie scheme used. Accepts 'auth', 'secure_auth', or 'logged_in'.
			 * @param string $token      User's session token used.
			 */
			return apply_filters( 'auth_cookie', $cookie, $user_id, $expiration, $scheme, $token );
		}
	} // if ! function exists

}

// EOF