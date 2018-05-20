<?php
namespace DL\AssetSource\Wikimedia\Api\SearchStrategies;

/*
 * This file is part of the DL.AssetSource.Wikimedia package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
