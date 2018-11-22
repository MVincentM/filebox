<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Template;

/**
 * @ORM\Entity(repositoryClass="App\Repository\FileRepository")
 */
class File extends Template
{
    /**
     * @ORM\Column(type="integer", length=255)
     */
    private $currentVersion;
    
    public function getCurrentVersion(): ?int
    {
        return $this->currentVersion;
    }

    public function setCurrentVersion(int $currentVersion): self
    {
        $this->currentVersion = $currentVersion;

        return $this;
    }
}
