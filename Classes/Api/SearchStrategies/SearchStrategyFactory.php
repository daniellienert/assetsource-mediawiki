<?php
namespace DL\AssetSource\Wikimedia\Api\SearchStrategies;


use DL\AssetSource\Wikimedia\AssetSource\WikimediaAssetSource;
use Neos\Flow\Configuration\Exception\InvalidConfigurationException;

class SearchStrategyFactory
{

    /**
     * @param WikimediaAssetSource $assetSource
     * @return SearchStrategyInterface
     * @throws InvalidConfigurationException
     */
    public function getInstanceForAssetSource(WikimediaAssetSource $assetSource): SearchStrategyInterface
    {
        $strategyClass = $assetSource->getOption('searchStrategy');

        if (empty($strategyClass)) {
            throw new InvalidConfigurationException(sprintf('The search strategy class for asset source %s is not defined.', $assetSource->getIdentifier()), 1526800804);
        }

        if(!class_exists($strategyClass)) {
            throw new InvalidConfigurationException(sprintf('The search strategy class %s for asset source %s does not exist.', $strategyClass, $assetSource->getIdentifier()), 1526800805);
        }

        return new $strategyClass($assetSource);
    }
}
