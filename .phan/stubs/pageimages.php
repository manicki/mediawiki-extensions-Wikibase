<?php

namespace PageImages;

use File;
use Title;

/**
 * Minimal set of classes necessary to fulfill needs of parts of Wikibase relying on
 * the PageImages extension.
 * @codingStandardsIgnoreFile
 */

class PageImages {
	public const PROP_NAME_FREE = 'page_image_free';

	/**
	 * @param Title $title
	 * @return File|null
	 */
	public static function getPageImage( Title $title ) {
	}
}
