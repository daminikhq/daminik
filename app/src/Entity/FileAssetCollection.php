<?php

namespace App\Entity;

use App\Repository\FileAssetCollectionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FileAssetCollectionRepository::class)]
#[ORM\UniqueConstraint(
    name: 'file_asset_collection_idx',
    columns: ['file_id', 'asset_collection_id']
)]
class FileAssetCollection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'fileAssetCollections')]
    #[ORM\JoinColumn(nullable: false)]
    private ?File $file = null;

    #[ORM\ManyToOne(inversedBy: 'fileAssetCollections')]
    #[ORM\JoinColumn(nullable: false)]
    private ?AssetCollection $assetCollection = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $addedBy = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $addedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(?File $file): static
    {
        $this->file = $file;

        return $this;
    }

    public function getAssetCollection(): ?AssetCollection
    {
        return $this->assetCollection;
    }

    public function setAssetCollection(?AssetCollection $assetCollection): static
    {
        $this->assetCollection = $assetCollection;

        return $this;
    }

    public function getAddedBy(): ?User
    {
        return $this->addedBy;
    }

    public function setAddedBy(?User $addedBy): static
    {
        $this->addedBy = $addedBy;

        return $this;
    }

    public function getAddedAt(): ?\DateTimeImmutable
    {
        return $this->addedAt;
    }

    public function setAddedAt(\DateTimeImmutable $addedAt): static
    {
        $this->addedAt = $addedAt;

        return $this;
    }
}
