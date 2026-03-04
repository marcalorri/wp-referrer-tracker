<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function referrertracker_updater_register() {
	if ( ! is_admin() ) {
		return;
	}

	$updater = new ReferrerTracker_GitHub_Updater(
		REFERRERTRACKER_GITHUB_OWNER,
		REFERRERTRACKER_GITHUB_REPO,
		REFERRERTRACKER_PLUGIN_SLUG,
		REFERRERTRACKER_PLUGIN_FILE,
		REFERRERTRACKER_VERSION
	);

	$updater->register();
}

class ReferrerTracker_GitHub_Updater {
	private $owner;
	private $repo;
	private $slug;
	private $plugin_file;
	private $plugin_basename;
	private $current_version;
	private $cache_key;

	public function __construct( $owner, $repo, $slug, $plugin_file, $current_version ) {
		$this->owner           = (string) $owner;
		$this->repo            = (string) $repo;
		$this->slug            = (string) $slug;
		$this->plugin_file     = $plugin_file;
		$this->plugin_basename = plugin_basename( $plugin_file );
		$this->current_version = (string) $current_version;
		$this->cache_key       = 'referrertracker_gh_release_' . md5( $this->owner . '/' . $this->repo );
	}

	public function register() {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugins_api' ), 10, 3 );
		add_filter( 'upgrader_source_selection', array( $this, 'upgrader_source_selection' ), 10, 4 );
		add_filter( 'upgrader_post_install', array( $this, 'upgrader_post_install' ), 10, 3 );
	}

	public function upgrader_source_selection( $source, $remote_source, $upgrader, $hook_extra ) {
		if ( ! is_array( $hook_extra ) ) {
			return $source;
		}

		if ( empty( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->plugin_basename ) {
			return $source;
		}

		$source_basename = basename( untrailingslashit( $source ) );
		if ( $source_basename === $this->slug ) {
			return $source;
		}

		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		if ( ! $wp_filesystem ) {
			return new WP_Error( 'referrertracker_filesystem_unavailable', 'ReferrerTracker: filesystem unavailable during update.' );
		}

		$desired_source = trailingslashit( $remote_source ) . $this->slug;

		if ( $wp_filesystem->is_dir( $desired_source ) ) {
			$wp_filesystem->delete( $desired_source, true );
		}

		$moved = $wp_filesystem->move( $source, $desired_source, true );
		if ( ! $moved ) {
			return new WP_Error( 'referrertracker_rename_failed', 'ReferrerTracker: failed to rename the extracted folder during update.' );
		}

		return $desired_source;
	}

	public function check_for_update( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		if ( empty( $transient->checked ) || ! is_array( $transient->checked ) ) {
			return $transient;
		}

		if ( ! isset( $transient->checked[ $this->plugin_basename ] ) ) {
			return $transient;
		}

		$release = $this->get_latest_release();
		if ( ! $release ) {
			return $transient;
		}

		$remote_version = isset( $release['version'] ) ? (string) $release['version'] : '';
		if ( $remote_version === '' ) {
			return $transient;
		}

		if ( version_compare( $this->normalize_version( $remote_version ), $this->normalize_version( $this->current_version ), '<=' ) ) {
			return $transient;
		}

		$update              = new stdClass();
		$update->slug        = $this->slug;
		$update->plugin      = $this->plugin_basename;
		$update->new_version = $this->normalize_version( $remote_version );
		$update->url         = isset( $release['html_url'] ) ? (string) $release['html_url'] : '';
		$update->package     = isset( $release['download_url'] ) ? (string) $release['download_url'] : '';

		$transient->response[ $this->plugin_basename ] = $update;
		return $transient;
	}

	public function plugins_api( $result, $action, $args ) {
		if ( $action !== 'plugin_information' ) {
			return $result;
		}

		if ( ! is_object( $args ) || empty( $args->slug ) || $args->slug !== $this->slug ) {
			return $result;
		}

		$release = $this->get_latest_release();
		if ( ! $release ) {
			return $result;
		}

		$info              = new stdClass();
		$info->name        = 'ReferrerTracker';
		$info->slug        = $this->slug;
		$info->version     = isset( $release['version'] ) ? $this->normalize_version( (string) $release['version'] ) : $this->current_version;
		$info->author      = 'ReferrerTracker';
		$info->homepage    = isset( $release['html_url'] ) ? (string) $release['html_url'] : '';
		$info->download_link = isset( $release['download_url'] ) ? (string) $release['download_url'] : '';
		$info->sections    = array(
			'description' => 'Adds ReferrerTracker tracking script and helps populate tracking fields in supported form plugins.',
		);

		return $info;
	}

	public function upgrader_post_install( $return, $hook_extra, $result ) {
		if ( ! is_array( $result ) || empty( $result['destination'] ) ) {
			return $return;
		}

		if ( ! is_array( $hook_extra ) ) {
			return $return;
		}

		$is_target_plugin = false;
		if ( isset( $hook_extra['plugin'] ) && $hook_extra['plugin'] === $this->plugin_basename ) {
			$is_target_plugin = true;
		}

		if ( ! $is_target_plugin ) {
			return $return;
		}

		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		if ( ! $wp_filesystem ) {
			return $return;
		}

		return $return;
	}

	private function normalize_version( $version ) {
		return ltrim( trim( (string) $version ), 'v' );
	}

	private function get_latest_release() {
		$cached = get_site_transient( $this->cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$url  = 'https://api.github.com/repos/' . rawurlencode( $this->owner ) . '/' . rawurlencode( $this->repo ) . '/releases/latest';
		$args = array(
			'timeout' => 10,
			'headers' => array(
				'Accept'     => 'application/vnd.github+json',
				'User-Agent' => 'WordPress; ReferrerTracker/' . $this->current_version,
			),
		);

		$response = wp_remote_get( $url, $args );
		if ( is_wp_error( $response ) ) {
			return null;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( $code < 200 || $code >= 300 || ! is_string( $body ) || $body === '' ) {
			return null;
		}

		$data = json_decode( $body, true );
		if ( ! is_array( $data ) ) {
			return null;
		}

		$tag = isset( $data['tag_name'] ) ? (string) $data['tag_name'] : '';
		if ( $tag === '' ) {
			return null;
		}

		$download_url = '';
		if ( ! empty( $data['assets'] ) && is_array( $data['assets'] ) ) {
			foreach ( $data['assets'] as $asset ) {
				if ( ! is_array( $asset ) || empty( $asset['browser_download_url'] ) || empty( $asset['name'] ) ) {
					continue;
				}
				$name = (string) $asset['name'];
				if ( substr( $name, -4 ) !== '.zip' ) {
					continue;
				}
				$download_url = (string) $asset['browser_download_url'];
				break;
			}
		}

		if ( $download_url === '' && ! empty( $data['zipball_url'] ) ) {
			$download_url = (string) $data['zipball_url'];
		}

		$release = array(
			'version'      => $tag,
			'html_url'     => isset( $data['html_url'] ) ? (string) $data['html_url'] : '',
			'download_url' => $download_url,
		);

		set_site_transient( $this->cache_key, $release, 6 * HOUR_IN_SECONDS );
		return $release;
	}
}
