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

use DL\AssetSource\MediaWiki\AssetSource\MediaWikiAssetSource;
use Neos\Flow\Configuration\Exception\InvalidConfigurationException;

class SearchStrategyFactory
{
    /**
     * @param MediaWikiAssetSource $assetSource
     * @return SearchStrategyInterface
     * @throws InvalidConfigurationException
     */
    public function getInstanceForAssetSource(MediaWikiAssetSource $assetSource): SearchStrategyInterface
    {
        $strategyClass = $assetSource->getOption('searchStrategy');

        if (empty($strategyClass)) {
            throw new InvalidConfigurationException(sprintf('The search strategy class for asset source %s is not defined.', $assetSource->getIdentifier()), 1526800804);
        }

        if (!class_exists($strategyClass)) {
            throw new InvalidConfigurationException(sprintf('The search strategy class %s for asset source %s does not exist.', $strategyClass, $assetSource->getIdentifier()), 1526800805);
        }

        return new $strategyClass($assetSource);
    }
}
