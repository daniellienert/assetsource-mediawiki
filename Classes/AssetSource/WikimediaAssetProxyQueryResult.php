<?php
namespace DL\AssetSource\Wikimedia\AssetSource;

/*
 * This file is part of the DL.AssetSource.Wikimedia package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use DL\AssetSource\Wikimedia\Api\WikimediaQueryResult;
use Neos\Media\Domain\Model\AssetSource\AssetProxy\AssetProxyInterface;
use Neos\Media\Domain\Model\AssetSource\AssetProxyQueryInterface;
use Neos\Media\Domain\Model\AssetSource\AssetProxyQueryResultInterface;

final class WikimediaAssetProxyQueryResult implements AssetProxyQueryResultInterface
{
    /**
     * @var WikimediaAssetSource
     */
    private $assetSource;

    /**
     * @var WikimediaQueryResult
     */
    private $wikimediaQueryResult = null;

    /**
     * @var \Iterator
     */
    private $wikimediaQueryResultIterator;

    /**
     * @var WikimediaAssetProxyQuery
     */
    private $WikimediaAssetProxyQuery;

    /**
     * @param WikimediaAssetProxyQuery $query
     * @param WikimediaQueryResult $wikimediaQueryResult
     * @param WikimediaAssetSource $assetSource
     */
    public function __construct(WikimediaAssetProxyQuery $query, WikimediaQueryResult $wikimediaQueryResult, WikimediaAssetSource $assetSource)
    {
        $this->WikimediaAssetProxyQuery = $query;
        $this->assetSource = $assetSource;
        $this->wikimediaQueryResult = $wikimediaQueryResult;
        $this->wikimediaQueryResultIterator = $wikimediaQueryResult->getAssetIterator();
    }

    /**
     * Returns a clone of the query object
     *
     * @return AssetProxyQueryInterface
     */
    public function getQuery(): AssetProxyQueryInterface
    {
        return clone $this->WikimediaAssetProxyQuery;
    }

    /**
     * Returns the first asset proxy in the result set
     *
     * @return AssetProxyInterface|null
     */
    public function getFirst(): ?AssetProxyInterface
    {
        return $this->offsetGet(0);
    }

    /**
     * Returns an array with the asset proxies in the result set
     *
     * @return AssetProxyInterface[]
     */
    public function toArray(): array
    {
        return $this->wikimediaQueryResult->getArrayCopy();
    }

    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current()
    {
        $asset = $this->wikimediaQueryResultIterator->current();

        if(is_array($asset)) {
            return new WikimediaAssetProxy($asset, $this->assetSource);
        } else {
            return null;
        }
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        $this->wikimediaQueryResultIterator->next();
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        return $this->wikimediaQueryResultIterator->key();
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid()
    {
        return $this->wikimediaQueryResultIterator->valid();
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        $this->wikimediaQueryResultIterator->rewind();
    }

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return $this->wikimediaQueryResultIterator->offsetExists($offset);
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        return new WikimediaAssetProxy($this->wikimediaQueryResultIterator->offsetGet($offset), $this->assetSource);
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        $this->wikimediaQueryResultIterator->offsetSet($offset, $value);
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        $this->wikimediaQueryResultIterator->offsetUnset($offset);
    }

    /**
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        return $this->wikimediaQueryResult->getTotalResults();
    }
}
