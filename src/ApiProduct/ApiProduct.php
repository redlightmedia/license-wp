<?php

namespace Never5\LicenseWP\ApiProduct;

/**
 * Class ApiProduct
 * @package Never5\LicenseWP\ApiProduct
 */
class ApiProduct {

	/** @var int */
	private $id = 0;

	/** @var string */
	private $name = '';

	/** @var string */
	private $slug = '';

	/** @var string */
	private $version = '';

	/** @var string */
	private $date = '';

	/** @var string */
	private $package = '';

	/** @var string */
	private $uri = '';

	/** @var string */
	private $author = '';

	/** @var string */
	private $author_uri = '';

	/** @var string */
	private $requires_at_least = '';

	/** @var string */
	private $tested_up_to = '';

	/** @var string */
	private $description = '';

	/** @var string */
	private $changelog = '';

	/** @var string */
	private $installation_instruction = '';

	/** @var string */
	private $icon_high = '';

	/** @var string */
	private $icon_low = '';

	/** @var string */
	private $banner_high = '';

	/** @var string */
	private $banner_low = '';

	/**
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * @param int $id
	 */
	public function set_id( $id ) {
		$this->id = $id;
	}

	/**
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function set_name( $name ) {
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function get_slug() {
		return $this->slug;
	}

	/**
	 * @param string $slug
	 */
	public function set_slug( $slug ) {
		$this->slug = $slug;
	}

	/**
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * @param string $version
	 */
	public function set_version( $version ) {
		$this->version = $version;
	}

	/**
	 * @return string
	 */
	public function get_date() {
		return $this->date;
	}

	/**
	 * @param string $date
	 */
	public function set_date( $date ) {
		$this->date = $date;
	}

	/**
	 * @return string
	 */
	public function get_package() {
		return $this->package;
	}

	/**
	 * @param string $package
	 */
	public function set_package( $package ) {
		$this->package = $package;
	}

	/**
	 * @return string
	 */
	public function get_uri() {
		return $this->uri;
	}

	/**
	 * @param string $uri
	 */
	public function set_uri( $uri ) {
		$this->uri = $uri;
	}

	/**
	 * @return string
	 */
	public function get_author() {
		return $this->author;
	}

	/**
	 * @param string $author
	 */
	public function set_author( $author ) {
		$this->author = $author;
	}

	/**
	 * @return string
	 */
	public function get_author_uri() {
		return $this->author_uri;
	}

	/**
	 * @param string $author_uri
	 */
	public function set_author_uri( $author_uri ) {
		$this->author_uri = $author_uri;
	}

	/**
	 * @return string
	 */
	public function get_requires_at_least() {
		return $this->requires_at_least;
	}

	/**
	 * @param string $requires_at_least
	 */
	public function set_requires_at_least( $requires_at_least ) {
		$this->requires_at_least = $requires_at_least;
	}

	/**
	 * @return string
	 */
	public function get_tested_up_to() {
		return $this->tested_up_to;
	}

	/**
	 * @param string $tested_up_to
	 */
	public function set_tested_up_to( $tested_up_to ) {
		$this->tested_up_to = $tested_up_to;
	}

	/**
	 * @return string
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 * @param string $description
	 */
	public function set_description( $description ) {
		$this->description = $description;
	}

	/**
	 * @return string
	 */
	public function get_changelog() {
		return $this->changelog;
	}

	/**
	 * @param string $changelog
	 */
	public function set_changelog( $changelog ) {
		$this->changelog = $changelog;
	}

	/**
	 * @return string
	 */
	public function get_installation_instruction() {
		return $this->installation_instruction;
	}

	/**
	 * @param string $changelog
	 */
	public function set_installation_instruction( $installation_instruction ) {
		$this->installation_instruction = $installation_instruction;
	}

	/**
	 * @return string
	 */
	public function get_icon_high() {
		return $this->icon_high;
	}

	/**
	 * @param string $icon_high
	 */
	public function set_icon_high( $icon_high ) {
		$this->icon_high = $icon_high;
	}

	/**
	 * @return string
	 */
	public function get_icon_low() {
		return $this->icon_low;
	}

	/**
	 * @param string $icon_low
	 */
	public function set_icon_low( $icon_low ) {
		$this->icon_low = $icon_low;
	}

	/**
	 * @return string
	 */
	public function get_banner_high() {
		return $this->banner_high;
	}

	/**
	 * @param string $icon_high
	 */
	public function set_banner_high( $banner_high ) {
		$this->banner_high = $banner_high;
	}

	/**
	 * @return string
	 */
	public function get_banner_low() {
		return $this->banner_low;
	}

	/**
	 * @param string $banner_low
	 */
	public function set_banner_low( $banner_low ) {
		$this->banner_low = $banner_low;
	}

	/**
	 * Get API product download URL
	 *
	 * @param \Never5\LicenseWP\License\License $license
	 *
	 * @return string
	 */
	public function get_download_url( $license ) {
		return add_query_arg( array(
			'download_api_product' => $this->get_id(),
			'license_key'          => $license->get_key(),
			'activation_email'     => $license->get_activation_email()
		), home_url( '/' ) );
	}
}