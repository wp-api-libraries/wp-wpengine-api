<?php
/**
 * Library for accessing the WPengine API on WordPress
 *
 * @package WP-API-Libraries\WP-WPengine-API
 */

/*
 * Plugin Name: WP WPengine API
 * Plugin URI: https://wp-api-libraries.com/
 * Description: Perform API requests.
 * Author: WP API Libraries
 * Version: 1.0.0
 * Author URI: https://wp-api-libraries.com
 * GitHub Plugin URI: https://github.com/imforza
 * GitHub Branch: master
 */

 // Exit if accessed directly.
 defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPWPengineAPI' ) ) {

	/**
	 * A WordPress API library for accessing the WPengine API.
	 *
	 * @version 1.1.0
	 * @link https://wpengineapi.com/reference API Documentation
	 * @package WP-API-Libraries\WP-WPengine-API
	 * @author Santiago Garza <https://github.com/sfgarza>
	 * @author imFORZA <https://github.com/imforza>
	 */
	class WPWPengineAPI {

		/**
		 * Basic auth username.
		 *
		 * @var string
		 */
		protected $username;

		/**
		 * Basic auth password.
		 *
		 * @var string
		 */
		protected $password;

		/**
		 * WPengine BaseAPI Endpoint
		 *
		 * @var string
		 * @access protected
		 */
		protected $base_uri = 'https://api.wpengineapi.com/v0/';

		/**
		 * Route being called.
		 *
		 * @var string
		 */
		protected $route = '';


		/**
		 * Class constructor.
		 *
		 * @param string $username  Auth username.
		 * @param string $password  Auth password.
		 */
		public function __construct( $username, $password ) {
			$this->username = $username;
			$this->password = $password;
		}

		/**
		 * Prepares API request.
		 *
		 * @param  string $route   API route to make the call to.
		 * @param  array  $args    Arguments to pass into the API call.
		 * @param  array  $method  HTTP Method to use for request.
		 * @return self            Returns an instance of itself so it can be chained to the fetch method.
		 */
		protected function build_request( $route, $args = array(), $method = 'GET' ) {
			// Start building query.
			$this->set_headers();
			$this->args['method'] = $method;
			$this->route          = $route;

			// Generate query string for GET requests.
			if ( 'GET' === $method ) {
				$this->route = add_query_arg( array_filter( $args ), $route );
			} elseif ( 'application/json' === $this->args['headers']['Content-Type'] ) {
				$this->args['body'] = wp_json_encode( $args );
			} else {
				$this->args['body'] = $args;
			}

			$this->args['timeout'] = 20;

			return $this;
		}


		/**
		 * Fetch the request from the API.
		 *
		 * @access private
		 * @return array|WP_Error Request results or WP_Error on request failure.
		 */
		protected function fetch() {
			// Make the request.
			$response = wp_remote_request( $this->base_uri . $this->route, $this->args );

			// Retrieve Status code & body.
			$code = wp_remote_retrieve_response_code( $response );
			$body = json_decode( wp_remote_retrieve_body( $response ) );

			$this->clear();
			// Return WP_Error if request is not successful.
			if ( ! $this->is_status_ok( $code ) ) {
				return new WP_Error( 'response-error', sprintf( __( 'Status: %d', 'wp-postmark-api' ), $code ), $body );
			}

			return $body;
		}

		/**
		 * Set request headers.
		 */
		protected function set_headers() {
			// Set request headers.
			$this->args['headers'] = array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Basic ' . base64_encode( "{$this->username}:{$this->password}" ),
			);
		}

		/**
		 * Clear query data.
		 */
		protected function clear() {
			$this->args       = array();
			$this->query_args = array();
		}

		/**
		 * Check if HTTP status code is a success.
		 *
		 * @param  int $code HTTP status code.
		 * @return boolean       True if status is within valid range.
		 */
		protected function is_status_ok( $code ) {
			return ( 200 <= $code && 300 > $code );
		}
		/*****************************************************************************************
											Status
		 ******************************************************************************************/

		public function get_api_status() {
			return $this->build_request( 'status' )->fetch();
		}

		/*****************************************************************************************
											Swagger
		 ******************************************************************************************/

		public function get_swagger_spec() {
			return $this->build_request( 'swagger' )->fetch();
		}

		/*****************************************************************************************
						  Accounts
		 ******************************************************************************************/

		/**
		 * List your WP Engine accounts
		 *
		 * @api GET
		 * @access public
		 * @param string $args  Additional query arguments.
		 * @return array        List of your WP Engine accounts
		 */
		public function get_accounts( $args = array() ) {
			return $this->build_request( 'accounts', $args )->fetch();
		}

		/**
		 * Get an account by ID
		 *
		 * @param  string $id Account ID
		 * @return mixed      Returns a single Account
		 */
		public function get_account_by_id( $id ) {
			return $this->build_request( "accounts/$id" )->fetch();
		}

		/*****************************************************************************************
											Sites
		 ******************************************************************************************/

		/**
		 * List your sites
		 *
		 * @param  array $args Additional arguments
		 * @return mixed        List of sites
		 */
		public function get_sites( $args = array() ) {
			return $this->build_request( 'sites', $args )->fetch();
		}

		/**
		 * Create a new site
		 *
		 * @param  string $name       The name of the site.
		 * @param  string $account_id The ID of the account that the site will belong to.
		 * @return mixed              The new site info.
		 */
		public function create_site( $name, $account_id ) {
			$args = compact( $name, $account_id );
			return $this->build_request( 'sites', $args, 'POST' )->fetch();
		}

		/**
		 * Get a site by ID
		 *
		 * @param  string $id The site ID.
		 * @return mixed      Returns a single site
		 */
		public function get_site_by_id( $id ) {
			return $this->build_request( "sites/$id" )->fetch();
		}

		/**
		 * Change a sites name.
		 *
		 * @param  string $id   The ID of the site to change the name of (For accounts with sites enabled).
		 * @param  string $name The new name for the site.
		 * @return mixed        The new site data.
		 */
		public function update_site( $id, $name ) {
			$args = compact( $name );
			return $this->build_request( "sites/$id", $args, 'PATCH' )->fetch();
		}

		/**
		 * This will delete the site and any installs associated with this site. This delete is permanent and there is no confirmation prompt.
		 *
		 * @param  string $id The ID of the site to delete (For accounts with sites enabled).
		 * @return string     Deleted
		 */
		public function delete_site( $id ) {
			return $this->build_request( "sites/$id", array(), 'DELETE' )->fetch();
		}

		/*****************************************************************************************
											Installs
		 ******************************************************************************************/

		/**
		 * List your WordPress installations
		 *
		 * @param  array $args Additional query arguments.
		 * @return mixed        List of WordPress installs.
		 */
		public function get_installs( $args = array() ) {
			return $this->build_request( 'installs', $args )->fetch();
		}

		/**
		 * Create a new WordPress installation
		 *
		 * @param  string $name        The name of the install.
		 * @param  string $account_id  The ID of the account that the install will belong to.
		 * @param  string $site_id     The ID of the site that the install will belong to.
		 * @param  string $environment The site environment that the install will fill.
		 * @return mixed               New install info
		 */
		public function create_install( $name, $account_id, $site_id, $environment ) {
			$args = compact( $name, $account_id, $site_id, $environment );
			return $this->build_request( 'installs', $args, 'POST' )->fetch();
		}

		/**
		 * Get an install by ID.
		 *
		 * @param  string $id Install ID.
		 * @return mixed      Returns a single Install
		 */
		public function get_install_by_id( $id ) {
			return $this->build_request( "installs/$id" )->fetch();
		}

		/**
		 * Update a WordPress installation
		 *
		 * @param  string $install_id Install ID.
		 * @param  array  $args       Fields to update.
		 * @return mixed              Updated install info
		 */
		public function update_install( $install_id, $args ) {
			return $this->build_request( "installs/$install_id", $args, 'PATCH' )->fetch();
		}

		/**
		 * Delete an install by ID
		 *
		 * @param  string $id Install ID.
		 * @return mixed      Deleted
		 */
		public function delete_install( $id ) {
			return $this->build_request( "installs/$id", array(), 'DELETE' )->fetch();
		}

		/*****************************************************************************************
											Domains
		 ******************************************************************************************/

		/**
		 * Get the domains for an install by install id.
		 *
		 * @param  string $install_id Install ID.
		 * @param  array  $args       Additional query arguments.
		 * @return mixed              Returns domains for a specific install
		 */
		public function get_domains( $install_id, $args = array() ) {
			return $this->build_request( "installs/$install_id/domains", $args )->fetch();
		}

		/**
		 * Add a new domain to an existing install.
		 *
		 * @param  string  $install_id ID of install.
		 * @param  string  $name       The name of the new domain.
		 * @param  boolean $primary    Sets the domain as the primary domain on the install.
		 * @return mixed               New domain info.
		 */
		public function create_domain( $install_id, $name, $primary = false ) {
			$args = compact( $name, $primary );
			return $this->build_request( "installs/$install_id/domains", $args, 'POST' )->fetch();
		}

		/**
		 * Get a specific domain for an install
		 *
		 * @param  string $install_id ID of install.
		 * @param  string $domain_id  ID of domain.
		 * @return mixed              Returns specific domain for an install.
		 */
		public function get_domain_by_id( $install_id, $domain_id ) {
			return $this->build_request( "installs/$install_id/domains/$domain_id" )->fetch();
		}

		/**
		 * Set an existing domain as primary.
		 *
		 * @param  string $install_id ID of install.
		 * @param  string $domain_id  ID of domain.
		 * @param  array  $args       Fields to update.
		 * @return mixed              Updated domain info.
		 */
		public function update_domain( $install_id, $domain_id, $args ) {
			return $this->build_request( "installs/$install_id/domains/$domain_id", $args, 'PATCH' )->fetch();
		}

		/**
		 * Delete a specific domain for an install.
		 *
		 * @param  string $install_id ID of install.
		 * @param  string $domain_id  ID of domain.
		 * @return mixed              Deleted.
		 */
		public function delete_domain( $install_id, $domain_id ) {
			return $this->build_request( "installs/$install_id/domains/$domain_id", array(), 'DELETE' )->fetch();
		}

		/*****************************************************************************************
											User
		 ******************************************************************************************/

		/**
		 * Get the current user.
		 *
		 * @return mixed Returns the currently authenticated user
		 */
		public function get_user() {
			return $this->build_request( 'user' )->fetch();
		}

	}
}
