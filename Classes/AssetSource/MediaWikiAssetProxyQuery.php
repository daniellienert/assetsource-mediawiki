<?php
namespace DL\AssetSource\MediaWiki\AssetSource;

/*
 * This file is part of the DL.AssetSource.MediaWiki package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use DL\AssetSource\MediaWiki\Api\SearchStrategies\SearchStrategyFactory;
use Neos\Media\Domain\Model\AssetSource\AssetProxyQueryInterface;
use Neos\Media\Domain\Model\AssetSource\AssetProxyQueryResultInterface;
use Neos\Media\Domain\Model\AssetSource\AssetSourceConnectionExceptionInterface;

final class MediaWikiAssetProxyQuery implements AssetProxyQueryInterface
{
    /**
     * @var MediaWikiAssetSource
     */
    private $assetSource;

    /**
     * @var SearchStrategyFactory
     * @Flow\Inject
     */
    protected $searchStrategyFactory;

    /**
     * MediaWikiAssetProxyQuery constructor.
     * @param MediaWikiAssetSource $assetSource
     */
    public function __construct(MediaWikiAssetSource $assetSource)
    {
        $this->assetSource = $assetSource;
    }

    /**
     * @var int
     */
    private $limit = 20;

    /**
     * @var int
     */
    private $offset = 0;

    /**
     * @var string
     */
    private $searchTerm = '';

    /**
     * @param int $offset
     */
    public function setOffset(int $offset): void
    {
        $this->offset = $offset;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @param int $limit
     */
    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @return string
     */
    public function getSearchTerm(): string
    {
        return $this->searchTerm;
    }

    /**
     * @param string $searchTerm
     */
    public function setSearchTerm(string $searchTerm): void
    {
        $this->searchTerm = $searchTerm;
    }

    /**
     * @return AssetProxyQueryResultInterface
     * @throws AssetSourceConnectionExceptionInterface
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function execute(): AssetProxyQueryResultInterface
    {
        if(empty($this->searchTerm)) {
            $imageCollection = $this->assetSource->getMediaWikiClient()->findAll();
        } else {
            $searchStrategy = $this->searchStrategyFactory->getInstanceForAssetSource($this->assetSource);
            $imageCollection = $searchStrategy->search($this->searchTerm, $this->offset);
        }

        $assetData = $this->assetSource->getMediaWikiClient()->getAssetDetails($imageCollection);
        return new MediaWikiAssetProxyQueryResult($this, $assetData, $this->assetSource);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        throw new \Exception(__METHOD__ . ' is not yet implemented and never used?');
    }
}
