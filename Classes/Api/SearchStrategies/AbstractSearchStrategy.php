<?php
namespace DL\AssetSource\Wikimedia\Api\SearchStrategies;

use DL\AssetSource\Wikimedia\Api\WikimediaClient;
use DL\AssetSource\Wikimedia\AssetSource\WikimediaAssetSource;

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
    protected $itemsPerPage = 20;

    /**
     * @var WikimediaAssetSource
     */
    protected $assetSource;

    /**
     * @var WikimediaClient
     */
    protected $wikimediaClient;

    /**
     * AbstractSearchStrategy constructor.
     * @param WikimediaAssetSource $assetSource
     */
    public function __construct(WikimediaAssetSource $assetSource)
    {
        $this->assetSource = $assetSource;
        $this->wikimediaClient = $assetSource->getWikimediaClient();
    }

    /**
     * @param array $images
     */
    protected function filterExcludedImages(array &$images)
    {
        $excludedIdentifierPatterns = $this->assetSource->getOption('excludedIdentifierPatterns');

        $images= array_filter($images,
            function ($image) use ($excludedIdentifierPatterns) {
                foreach ($excludedIdentifierPatterns as $excludedIdentifierPattern) {
                    if (fnmatch($excludedIdentifierPattern, $image)) {
                        return false;
                    }
                }
                return true;
            }
        );
    }
}
