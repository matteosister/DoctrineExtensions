<?php

namespace Sortable\Fixture;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @ORM\Document(repositoryClass="Gedmo\Sortable\Document\Repository\SortableRepository")
 */
class Node
{
    /**
     * @MongoDB\Id
     * @MongoDB\GeneratedValue
     */
    private $id;

    /**
     * @MongoDB\String
     */
    private $name;

    /**
     * @Gedmo\SortableGroup
     * @MongoDB\String
     */
    private $path;

    /**
     * @Gedmo\SortablePosition
     * @MongoDB\String
     */
    private $position;

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function getPosition()
    {
        return $this->position;
    }
}