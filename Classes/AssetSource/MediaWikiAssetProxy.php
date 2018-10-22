<?php
namespace DL\AssetSource\MediaWiki\AssetSource;

/*
 * This file is part of the DL.AssetSource.MediaWiki package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Http\Uri;
use Neos\Media\Domain\Model\AssetSource\AssetProxy\AssetProxyInterface;
use Neos\Media\Domain\Model\AssetSource\AssetProxy\HasRemoteOriginalInterface;
use Neos\Media\Domain\Model\AssetSource\AssetProxy\SupportsIptcMetadataInterface;
use Neos\Media\Domain\Model\AssetSource\AssetSourceInterface;
use Neos\Media\Domain\Model\ImportedAsset;
use Neos\Media\Domain\Repository\ImportedAssetRepository;
use Neos\Utility\Arrays;
use Neos\Utility\MediaTypes;
use Psr\Http\Message\UriInterface;

final class MediaWikiAssetProxy implements AssetProxyInterface, HasRemoteOriginalInterface, SupportsIptcMetadataInterface
{
    /**
     * @var string[]
     */
    private $assetData;

    /**
     * @var MediaWikiAssetSource
     */
    private $assetSource;

    /**
     * @var ImportedAsset
     */
    private $importedAsset;

    /**
     * @var string[]
     */
    private $iptcProperties;

    /**
     * MediaWikiAssetProxy constructor.
     * @param string[] $assetData
     * @param MediaWikiAssetSource $assetSource
     */
    public function __construct(array $assetData, MediaWikiAssetSource $assetSource)
    {
        $this->assetData = $assetData;
        $this->assetSource = $assetSource;
        $this->importedAsset = (new ImportedAssetRepository)->findOneByAssetSourceIdentifierAndRemoteAssetIdentifier($assetSource->getIdentifier(), $this->getIdentifier());
    }

    /**
     * @return AssetSourceInterface
     */
    public function getAssetSource(): AssetSourceInterface
    {
        return $this->assetSource;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return (string)$this->getProperty('identifier');
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->getProperty('extmetadata.ObjectName.value');
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->getProperty('filename');
    }

    /**
     * @return \DateTimeInterface
     */
    public function getLastModified(): \DateTimeInterface
    {
        return \DateTime::createFromFormat('Y-m-d H:i:s', $this->getProperty('extmetadata.DateTime.value'));
    }

    /**
     * @return int
     */
    public function getFileSize(): int
    {
        return (int)$this->getProperty('size');
    }

    /**
     * @return string
     */
    public function getMediaType(): string
    {
        return MediaTypes::getMediaTypeFromFilename($this->getFilename());
    }

    /**
     * @return int|null
     */
    public function getWidthInPixels(): ?int
    {
        return (int)$this->getProperty('width');
    }

    /**
     * @return int|null
     */
    public function getHeightInPixels(): ?int
    {
        return (int)$this->getProperty('height');
    }

    /**
     * @return null|UriInterface
     */
    public function getThumbnailUri(): ?UriInterface
    {
        return new Uri($this->getProperty('thumburl'));
    }

    /**
     * @return null|UriInterface
     */
    public function getPreviewUri(): ?UriInterface
    {
        return new Uri($this->getProperty('url'));
    }

    /**
     * @return resource
     */
    public function getImportStream()
    {
        return fopen($this->getProperty('url'), 'r');
    }

    /**
     * @return null|string
     */
    public function getLocalAssetIdentifier(): ?string
    {
        return $this->importedAsset instanceof ImportedAsset ? $this->importedAsset->getLocalAssetIdentifier() : '';
    }

    /**
     * Returns true if the binary data of the asset has already been imported into the Neos asset source.
     *
     * @return bool
     */
    public function isImported(): bool
    {
        return $this->importedAsset !== null;
    }

    /**
     * @param string $propertyPath
     * @return mixed|null
     */
    protected function getProperty(string $propertyPath)
    {
        return Arrays::getValueByPath($this->assetData, $propertyPath);
    }

    /**
     * Returns true, if the given IPTC metadata property is available, ie. is supported and is not empty.
     *
     * @param string $propertyName
     * @return bool
     */
    public function hasIptcProperty(string $propertyName): bool
    {
        return isset($this->getIptcProperties()[$propertyName]);
    }

    /**
     * Returns the given IPTC metadata property if it exists, or an empty string otherwise.
     *
     * @param string $propertyName
     * @return string
     */
    public function getIptcProperty(string $propertyName): string
    {
        return $this->getIptcProperties()[$propertyName] ?? '';
    }

    /**
     * Returns all known IPTC metadata properties as key => value (e.g. "Title" => "My Photo")
     *
     * @return string[]
     */
    public function getIptcProperties(): array
    {
        if ($this->iptcProperties === null) {
            $this->iptcProperties = [
                'Title' => strip_tags($this->getProperty('extmetadata.ImageDescription.value')),
                'Creator' => strip_tags($this->getProperty('extmetadata.Artist.value')),
            ];
        }

        return $this->iptcProperties;
    }
}
