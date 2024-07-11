<?php

declare(strict_types=1);

namespace App\Service\File;

use App\Dto\File\Metadata;
use App\Entity\File;
use App\Entity\Revision;
use App\Enum\MimeType;
use App\Exception\File\MissingWorkspaceException;
use App\Exception\FileHandlerException;
use App\Util\StringUtil;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

readonly class MetadataHandler
{
    public const PUBLIC_KEYS = [
        'CreateDate',
        'Make',
        'Model',
        'LensID',
        'Artist',
        'ISO',
        'ShutterSpeed',
        'Aperture',
        'FocalLength',
        'ExposureProgram',
        'ColorSpace',
        'GPSLatitude',
        'GPSLongitude',
    ];

    public function __construct(
        private FileHandler $fileHandler,
    ) {
    }

    /**
     * @throws FileHandlerException
     * @throws \JsonException
     * @throws MissingWorkspaceException
     */
    public function extractMetadata(Revision $revision): ?Metadata
    {
        return match ($revision->getMime()) {
            'image/jpeg' => $this->extractJpegRawData($revision),
            default => $this->extractDefaultMetadata($revision),
        };
    }

    /**
     * @throws FileHandlerException
     * @throws MissingWorkspaceException
     * @throws \JsonException
     */
    private function extractJpegRawData(Revision $revision): ?Metadata
    {
        $file = $revision->getFile();
        if (!$file instanceof File) {
            return null;
        }
        $localPath = $this->fileHandler->provideLocalFile($file, $revision);
        if (null === $localPath) {
            return null;
        }
        $metadata = new Metadata();
        $process = new Process([
            'exiftool',
            '-s',
            '-c',
            '%.6f',
            '-json',
            $localPath,
        ]);

        $process->run();

        if (!$process->isSuccessful()) {
            $this->fileHandler->removeLocalFile($file, $revision);
            throw new ProcessFailedException($process);
        }

        $output = $process->getOutput();
        $result = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

        if (is_array($result)) {
            $result = $result[0];
        }

        if (is_array($result)) {
            $metadata->setExif($result);

            if (array_key_exists('Title', $result)) {
                $metadata->setTitle($result['Title']);
            }

            if (array_key_exists('Description', $result)) {
                $metadata->setDescription($result['Description']);
            }

            if (array_key_exists('Keywords', $result)) {
                $tagString = null;
                if (is_array($result['Keywords'])) {
                    $tagString = implode(', ', $result['Keywords']);
                } elseif (is_string($result['Keywords'])) {
                    $tagString = $result['Keywords'];
                }
                $metadata->setTagString($tagString);
            }

            if (array_key_exists('Artist', $result)) {
                $metadata->setArtist($result['Artist']);
            }

            if (array_key_exists('Copyright', $result)) {
                $metadata->setCopyrightNotice($result['Copyright']);
            }
        }

        if (null === $metadata->getWidth() || null === $metadata->getHeight()) {
            $metadata = $this->getImageSizes($metadata, $localPath);
        }

        /*
        $hex = $this->getHex($localPath);
        if($hex !== null) {
            $metadata->setAccentColor($hex);
        }
        */
        $this->fileHandler->removeLocalFile($file, $revision);

        return $metadata;
    }

    /**
     * @throws FileHandlerException
     * @throws MissingWorkspaceException
     */
    private function extractDefaultMetadata(Revision $revision): ?Metadata
    {
        $file = $revision->getFile();
        if (!$file instanceof File) {
            return null;
        }
        $localPath = $this->fileHandler->provideLocalFile($file, $revision);
        if (null === $localPath) {
            return null;
        }
        $metadata = $this->getImageSizes(new Metadata(), $localPath);

        /*
        $hex = $this->getHex($localPath);
        if($hex !== null) {
            $metadata->setAccentColor($hex);
        }
        */

        $this->fileHandler->removeLocalFile($file, $revision);

        return $metadata;
    }

    private function getImageSizes(Metadata $metadata, string $localPath): Metadata
    {
        if (!file_exists($localPath)) {
            return $metadata;
        }
        $imageSizes = getimagesize($localPath);
        if (is_array($imageSizes)) {
            if ($imageSizes['mime'] === MimeType::JPG->value) {
                $exif = exif_read_data($localPath);
                if (
                    is_array($exif)
                    && array_key_exists('Orientation', $exif)
                    && $exif['Orientation'] > 4
                ) {
                    $metadata->setWidth($imageSizes[1]);
                    $metadata->setHeight($imageSizes[0]);
                }
            }
            if (null === $metadata->getWidth()) {
                $metadata->setWidth($imageSizes[0]);
            }
            if (null === $metadata->getHeight()) {
                $metadata->setHeight($imageSizes[1]);
            }
        }

        return $metadata;
    }

    /**
     * @return array<string, string>|null
     */
    public function getMetadata(?Revision $revision): ?array
    {
        if (!$revision instanceof Revision || null === $revision->getRawExif()) {
            return null;
        }
        $metadata = [];
        $rawExif = $revision->getRawExif();
        foreach (self::PUBLIC_KEYS as $key) {
            if (!array_key_exists($key, $rawExif)) {
                continue;
            }

            try {
                $value = StringUtil::strictCastToString($rawExif[$key]);
            } catch (\Exception) {
                continue;
            }

            /* @noinspection DegradedSwitchInspection */
            switch ($key) {
                case 'CreateDate':
                    try {
                        $createDate = new \DateTimeImmutable($value);
                        $metadata[$key] = $createDate->format('d.m.Y H:i:s');
                        break;
                    } catch (\Exception) {
                        $metadata[$key] = $value;
                        break;
                    }
                default:
                    $metadata[$key] = $value;
                    break;
            }
        }

        return $metadata;
    }
}
