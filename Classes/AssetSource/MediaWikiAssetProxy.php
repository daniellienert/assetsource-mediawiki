<?php
declare(strict_types=1);
namespace DL\AssetSource\MediaWiki\AssetSource;

/*
 * This file is part of the DL.AssetSource.MediaWiki package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Eel\EelEvaluatorInterface;
use Neos\Eel\Utility;
use Neos\Flow\Annotations as Flow;
use Neos\Http\Factories\UriFactory;
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
     * @Flow\Inject
     * @var UriFactory
     */
    protected $uriFactory;

    /**
     * @var array
     * @Flow\InjectConfiguration(path="defaultContext", package="Neos.Fusion")
     */
    protected $defaultContextConfiguration;

    /**
     * @var EelEvaluatorInterface
     * @Flow\Inject(lazy=false)
     */
    protected $eelEvaluator;

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
     * @throws \Neos\Eel\Exception
     */
    public function getLabel(): string
    {
        return $this->resolveValue(['extmetadata.ObjectName.value', 'extmetadata.ImageDescription.value']);
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return (string)$this->getProperty('filename');
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
        return $this->uriFactory->createUri((string)$this->getProperty('thumburl'));
    }

    /**
     * @return null|UriInterface
     */
    public function getPreviewUri(): ?UriInterface
    {
        return $this->uriFactory->createUri((string)$this->getProperty('url'));
    }

    /**
     * @return resource
     */
    public function getImportStream()
    {
        return fopen($this->getProperty('url'), 'rb');
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
     * @throws \Neos\Eel\Exception
     */
    public function hasIptcProperty(string $propertyName): bool
    {
        return isset($this->getIptcProperties()[$propertyName]) && !empty($this->getIptcProperties()[$propertyName]);
    }

    /**
     * Returns the given IPTC metadata property if it exists, or an empty string otherwise.
     *
     * @param string $propertyName
     * @return string
     * @throws \Neos\Eel\Exception
     */
    public function getIptcProperty(string $propertyName): string
    {
        return $this->getIptcProperties()[$propertyName] ?? '';
    }

    /**
     * Returns all known IPTC metadata properties as key => value (e.g. "Title" => "My Photo")
     *
     * @return string[]
     * @throws \Neos\Eel\Exception
     */
    public function getIptcProperties(): array
    {
        if ($this->iptcProperties === null) {
            $this->iptcProperties = [
                'Title' => $this->resolveValue(['extmetadata.ImageDescription.value', 'extmetadata.ObjectName.value']),
                'Creator' => $this->resolveValue(['extmetadata.Artist.value']),
                'CopyrightNotice' => $this->compileCopyrightNotice(),
            ];
        }

        return $this->iptcProperties;
    }

    /**
     * @return string
     * @throws \Neos\Eel\Exception
     */
    private function compileCopyrightNotice(): string
    {
        $context = [
            'LicenseUrl' => $this->resolveValue(['extmetadata.LicenseUrl.value', 'descriptionurl']),
            'Title' => $this->resolveValue(['extmetadata.ImageDescription.value', 'extmetadata.ObjectName.value']),
            'Creator' => $this->resolveValue(['extmetadata.Artist.value']),
        ];

        return Utility::evaluateEelExpression($this->assetSource->getCopyRightNoticeTemplate(), $this->eelEvaluator, $context, $this->defaultContextConfiguration);
    }

    /**
     * @param array $candidatePaths
     * @return string
     */
    private function resolveValue(array $candidatePaths): string
    {
        foreach ($candidatePaths as $candidatePath) {
            if ($this->getProperty($candidatePath) !== null && trim($this->getProperty($candidatePath)) !== '') {
                return strip_tags((string)$this->getProperty($candidatePath));
            }
        }

        return '';
    }


}
