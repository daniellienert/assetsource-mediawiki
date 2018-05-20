<?php
namespace DL\AssetSource\Wikimedia\Api\SearchStrategies;

use DL\AssetSource\Wikimedia\Api\Dto\ImageSearchResult;

interface SearchStrategyInterface
{

    /**
     * @param string $term
     * @param int $offset
     * @return ImageSearchResult
     */
    public function search(string $term, int $offset = 0): ImageSearchResult;
}
