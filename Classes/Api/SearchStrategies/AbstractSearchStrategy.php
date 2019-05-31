<?php
declare(strict_types=1);
namespace DL\AssetSource\MediaWiki\Api\SearchStrategies;

/*
 * This file is part of the DL.AssetSource.MediaWiki package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use DL\AssetSource\MediaWiki\Api\MediaWikiClient;
use DL\AssetSource\MediaWiki\AssetSource\MediaWikiAssetSource;

abstract class AbstractSearchStrategy implements SearchStrategyInterface
{
    /**
     * The result limit for the simple filename query
     * @var int
     */
    protected $totalResultLimit = 500;

    /**
     * @var int
     */
    protected $itemsPerPage = 30;

    /**
     * @var MediaWikiAssetSource
     */
    protected $assetSource;

    /**
     * @var MediaWikiClient
     */
    protected $mediaWikiClient;

    /**
     * AbstractSearchStrategy constructor.
     * @param MediaWikiAssetSource $assetSource
     */
    public function __construct(MediaWikiAssetSource $assetSource)
    {
        $this->assetSource = $assetSource;
        $this->mediaWikiClient = $assetSource->getMediaWikiClient();
    }

    /**
     * @param string[] $images
     */
    protected function filterExcludedImages(array &$images): void
    {
        $excludedIdentifierPatterns = $this->assetSource->getOption('excludedIdentifierPatterns');

        $images = array_filter($images, function ($image) use ($excludedIdentifierPatterns) {
            foreach ($excludedIdentifierPatterns as $excludedIdentifierPattern) {
                if (fnmatch($excludedIdentifierPattern, $image)) {
                    return false;
                }
            }
            return true;
        });
    }
}
