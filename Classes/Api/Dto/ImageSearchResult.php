<?php
namespace DL\AssetSource\MediaWiki\Api\Dto;

/*
 * This file is part of the DL.AssetSource.MediaWiki package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Collections\ArrayCollection;

class ImageSearchResult
{
    /**
     * @var ArrayCollection
     */
    protected $imageTitles;

    /**
     * @var int
     */
    protected $totalResults;

    public function __construct(array $imageTitles = [], int $totalResults = 0)
    {
        $this->imageTitles = new ArrayCollection($imageTitles);
        $this->totalResults = $totalResults;
    }

    /**
     * @return int
     */
    public function getTotalResults(): int
    {
        return $this->totalResults;
    }

    /**
     * @param int $totalResults
     */
    public function setTotalResults(int $totalResults): void
    {
        $this->totalResults = $totalResults;
    }

    /**
     * @return ArrayCollection
     */
    public function getImageTitles(): ArrayCollection
    {
        return $this->imageTitles;
    }

    /**
     * @param string $imageTitle
     */
    public function addImageTitle(string $imageTitle) {
        $this->imageTitles->add($imageTitle);
    }
}
