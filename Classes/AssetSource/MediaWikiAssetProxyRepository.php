<?php
namespace DL\AssetSource\MediaWiki\AssetSource;

/*
 * This file is part of the DL.AssetSource.MediaWiki package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use DL\AssetSource\MediaWiki\Api\Dto\ImageSearchResult;
use GuzzleHttp\Exception\GuzzleException;
use Neos\Cache\Exception;
use Neos\Media\Domain\Model\AssetSource\AssetNotFoundExceptionInterface;
use Neos\Media\Domain\Model\AssetSource\AssetProxy\AssetProxyInterface;
use Neos\Media\Domain\Model\AssetSource\AssetProxyQueryResultInterface;
use Neos\Media\Domain\Model\AssetSource\AssetProxyRepositoryInterface;
use Neos\Media\Domain\Model\AssetSource\AssetSourceConnectionExceptionInterface;
use Neos\Media\Domain\Model\AssetSource\AssetTypeFilter;
use Neos\Media\Domain\Model\Tag;

final class MediaWikiAssetProxyRepository implements AssetProxyRepositoryInterface
{
    /**
     * @var MediaWikiAssetSource
     */
    private $assetSource;

    /**
     * @param MediaWikiAssetSource $assetSource
     */
    public function __construct(MediaWikiAssetSource $assetSource)
    {
        $this->assetSource = $assetSource;
    }

    /**
     * @param string $identifier
     * @return AssetProxyInterface
     * @throws AssetNotFoundExceptionInterface
     * @throws AssetSourceConnectionExceptionInterface
     * @throws GuzzleException
     * @throws Exception
     */
    public function getAssetProxy(string $identifier): AssetProxyInterface
    {
        $queryResult = $this->assetSource->getMediaWikiClient()->getAssetDetails(new ImageSearchResult([$identifier], 1));
        return new MediaWikiAssetProxy($queryResult->getAssetIterator()->current(), $this->assetSource);
    }

    /**
     * @param AssetTypeFilter $assetType
     */
    public function filterByType(AssetTypeFilter $assetType = null): void
    {
    }

    /**
     * @return AssetProxyQueryResultInterface
     * @throws AssetSourceConnectionExceptionInterface
     * @throws GuzzleException
     */
    public function findAll(): AssetProxyQueryResultInterface
    {
        $query = new MediaWikiAssetProxyQuery($this->assetSource);
        return $query->execute();
    }

    /**
     * @param string $searchTerm
     * @return AssetProxyQueryResultInterface
     * @throws AssetSourceConnectionExceptionInterface
     * @throws \Exception
     * @throws GuzzleException
     */
    public function findBySearchTerm(string $searchTerm): AssetProxyQueryResultInterface
    {
        $query = new MediaWikiAssetProxyQuery($this->assetSource);
        $query->setSearchTerm($searchTerm);
        return $query->execute();
    }

    /**
     * @param Tag $tag
     * @return AssetProxyQueryResultInterface
     * @throws \Exception
     */
    public function findByTag(Tag $tag): AssetProxyQueryResultInterface
    {
        throw new \Exception(__METHOD__ . ' to filter ' .  $tag->getLabel() . 'is not yet implemented');
    }

    /**
     * @return AssetProxyQueryResultInterface
     * @throws \Exception
     */
    public function findUntagged(): AssetProxyQueryResultInterface
    {
        throw new \Exception(__METHOD__ . 'is not yet implemented');
    }

    /**
     * Count all assets, regardless of tag or collection
     *
     * @return int
     * @throws GuzzleException
     * @throws Exception
     */
    public function countAll(): int
    {
        return $this->assetSource->getMediaWikiClient()->countAll();
    }
}
