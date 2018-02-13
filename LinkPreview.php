<?php

namespace dfwood\WordPress;

/**
 * Class LinkPreview
 *
 * Based on/inspired by the https://wordpress.org/plugins/wp-link-preview/ plugin.
 *
 * @author David Wood <david@davidwood.ninja>
 * @link https://davidwood.ninja/
 * @license GPLv3
 * @package dfwood\WordPress
 */
class LinkPreview {

	/**
	 * @var string The url we are creating a preview for.
	 */
	protected $url;

	/**
	 * @var \DOMDocument The fetched document.
	 */
	protected $document;

	/**
	 * @var array Stores HTML meta tags from fetched document.
	 */
	protected $meta = [];

	/**
	 * LinkPreview constructor.
	 *
	 * @param string $url
	 */
	public function __construct( $url = null ) {
		if ( $url ) {
			$this->url = $url;
			$this->fetch();
		}
	}

	/**
	 * Fetches the document.
	 *
	 * @param string $url URL to fetch from
	 *
	 * @return bool True on success, false on failure.
	 */
	public function fetch( $url = null ) {
		if ( $url ) {
			$this->url = $url;
		}
		if ( $this->url ) {
			$response = wp_safe_remote_get( $this->url, [
				'timeout' => 120,
			] );

			if ( ! is_wp_error( $response ) ) {
				$this->document = new \DOMDocument();
				// Silence errors caused by invalid markup.
				@$this->document->loadHTML( wp_remote_retrieve_body( $response ) );
				$metaTags = $this->document->getElementsByTagName( 'meta' );
				foreach ( $metaTags as $metaTag ) {
					/* @var \DOMElement $metaTag */
					$name = $metaTag->getAttribute( 'name' );
					if ( empty( $name ) ) {
						$name = $metaTag->getAttribute( 'property' );
					}

					if ( $name ) {
						$this->meta[ $name ] = $metaTag->getAttribute( 'content' );
					}
				}

				return true;
			}
		}

		return false;
	}

	/**
	 * Get the document title.
	 *
	 * Returns the first item found:
	 * 1) open graph title
	 * 2) title tag value
	 * 3) value of the first h1 tag
	 *
	 * @return string Document title or empty string if no title found OR if document hasn't been fetched yet.
	 */
	public function title() {
		$title = '';

		if ( $this->document ) {
			if ( ! empty( $this->meta['og:title'] ) ) {
				$title = $this->meta['og:title'];
			} else {
				$nodes = $this->document->getElementsByTagName( 'title' );
				if ( $nodes ) {
					$title = $nodes->item( 0 )->nodeValue;
				}
			}

			if ( empty( $title ) ) {
				$nodes = $this->document->getElementsByTagName( 'h1' );
				if ( $nodes ) {
					$title = $nodes->item( 0 )->nodeValue;
				}
			}
		}

		return wp_strip_all_tags( utf8_decode( $title ) );
	}

	/**
	 * Get the document description.
	 *
	 * Returns the value of the first tag found:
	 * 1) open graph description
	 * 2) description meta tag
	 *
	 * @return string
	 */
	public function description() {
		$description = '';

		if ( $this->document ) {
			if ( ! empty( $this->meta['og:description'] ) ) {
				$description = $this->meta['og:description'];
			} else {
				$description = $this->meta['description'];
			}
		}

		return wp_strip_all_tags( utf8_decode( $description ) );
	}

	/**
	 * Get the document URL.
	 *
	 * Returns the first value found in order:
	 * 1) open graph URL
	 * 2) URL used to make request
	 *
	 * @return string
	 */
	public function url() {
		if ( ! empty( $this->meta['og:url'] ) ) {
			$url = $this->meta['og:url'];
		}

		return $url ? : $this->url;
	}

	/**
	 * Checks if the document has an image or not.
	 *
	 * @return bool
	 */
	public function hasImage() {
		return ! empty( $this->meta['og:image'] );
	}

	/**
	 * Returns the image source URL or an empty string if no image found.
	 *
	 * @return string
	 */
	public function imageSrc() {
		return ! empty( $this->meta['og:image'] ) ? $this->meta['og:image'] : '';
	}

}
