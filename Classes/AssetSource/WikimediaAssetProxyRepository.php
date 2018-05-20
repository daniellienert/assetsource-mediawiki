<?php
namespace DL\AssetSource\Wikimedia\AssetSource;

/*
 * This file is part of the DL.AssetSource.Wikimedia package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use DL\AssetSource\Wikimedia\Api\Dto\ImageSearchResult;
use Neos\Media\Domain\Model\AssetSource\AssetNotFoundExceptionInterface;
use Neos\Media\Domain\Model\AssetSource\AssetProxy\AssetProxyInterface;
use Neos\Media\Domain\Model\AssetSource\AssetProxyQueryResultInterface;
use Neos\Media\Domain\Model\AssetSource\AssetProxyRepositoryInterface;
use Neos\Media\Domain\Model\AssetSource\AssetSourceConnectionExceptionInterface;
use Neos\Media\Domain\Model\AssetSource\AssetTypeFilter;
use Neos\Media\Domain\Model\Tag;

final class WikimediaAssetProxyRepository implements AssetProxyRepositoryInterface
{
    /**
     * @var WikimediaAssetSource
     */
    private $assetSource;

    /**
     * @param WikimediaAssetSource $assetSource
     */
    public function __construct(WikimediaAssetSource $assetSource)
    {
        $this->assetSource = $assetSource;
    }

    /**
     * @param string $identifier
     * @return AssetProxyInterface
     * @throws AssetNotFoundExceptionInterface
     * @throws AssetSourceConnectionExceptionInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getAssetProxy(string $identifier): AssetProxyInterface
    {
        $queryResult = $this->assetSource->getWikimediaClient()->getAssetDetails(new ImageSearchResult([$identifier], 1));
        return new WikimediaAssetProxy($queryResult->getAssetIterator()->current(), $this->assetSource);
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
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function findAll(): AssetProxyQueryResultInterface
    {
        $query = new WikimediaAssetProxyQuery($this->assetSource);
        return $query->execute();
    }

    /**
     * @param string $searchTerm
     * @return AssetProxyQueryResultInterface
     * @throws AssetSourceConnectionExceptionInterface
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function findBySearchTerm(string $searchTerm): AssetProxyQueryResultInterface
    {
        $query = new WikimediaAssetProxyQuery($this->assetSource);
        $query->setSearchTerm($searchTerm);
        return $query->execute();
    }

    /**
     * @param Tag $tag
     * @return AssetProxyQueryResultInterface
     */
    public function findByTag(Tag $tag): AssetProxyQueryResultInterface
    {
        throw new \Exception(__METHOD__ . 'is not yet implemented');
    }

    /**
     * @return AssetProxyQueryResultInterface
     */
    public function findUntagged(): AssetProxyQueryResultInterface
    {
        throw new \Exception(__METHOD__ . 'is not yet implemented');
    }

    /**
     * Count all assets, regardless of tag or collection
     *
     * @return int
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function countAll(): int
    {
        return $this->assetSource->getWikimediaClient()->countAll();
    }
}
