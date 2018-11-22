<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"template" = "Template", "file" = "File", "folder" = "Folder"})
 */
class Template
{
    public function toJSON(){
        $type = get_class($this);
        if($type == 'App\Entity\Folder') $type = "folder";
        else $type = "file";
        $json = array(
            'type' => $type,
            'path' => $this->getPath(),
            'name' => $this->getName(),
            'lastUpdate' => $this->getLastUpdate(),
            'lastUpdator' => $this->getLastModificator(),
            'creator' => $this->getCreator(),
        );
    
        return json_encode($json,JSON_UNESCAPED_SLASHES);
    }
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer", length=255)
     */
    private $creator;
    /**
     * @ORM\Column(type="integer", length=255)
     */
    private $lastModificator;
    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;
    /**
     * @ORM\Column(type="string", length=255)
     */
    private $path;
    /**
     * @ORM\Column(type="integer", length=255, nullable=true)
     */
    private $parent;
    /**
     * @ORM\Column(type="datetime")
     */
    private $lastUpdate;
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreator(): ?string
    {
        return $this->creator;
    }

    public function setCreator(string $creator): self
    {
        $this->creator = $creator;

        return $this;
    }

    public function getLastModificator(): ?int
    {
        return $this->lastModificator;
    }

    public function setLastModificator(int $lastModificator): self
    {
        $this->lastModificator = $lastModificator;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getParent(): ?int
    {
        return $this->parent;
    }

    public function setParent(int $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getLastUpdate(): ?\DateTimeInterface
    {
        return $this->lastUpdate;
    }

    public function setLastUpdate(\DateTimeInterface $lastUpdate): self
    {
        $this->lastUpdate = $lastUpdate;

        return $this;
    }
}
