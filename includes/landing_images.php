<?php
/**
 * Landing page images — loads from assets/images/ via includes/images.php
 *
 * @deprecated Prefer requiring includes/images.php and calling landingImages() directly.
 * @see assets/images/README.md
 */
require_once __DIR__ . '/images.php';

return landingImages();
