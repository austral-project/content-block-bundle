<?php
/*
 * This file is part of the Austral ContentBlock Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ContentBlockBundle\Entity;

use Austral\ContentBlockBundle\Entity\Interfaces\ComponentValueInterface;
use Austral\ContentBlockBundle\Entity\Interfaces\ComponentValuesInterface;

use Austral\EntityBundle\Entity\Entity;
use Austral\EntityBundle\Entity\EntityInterface;
use Austral\EntityBundle\Entity\Traits\EntityTimestampableTrait;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

use Exception;

/**
 * Austral ComponentValues Entity.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @abstract
 * @ORM\MappedSuperclass
 */
abstract class ComponentValues extends Entity implements ComponentValuesInterface, EntityInterface
{

  use EntityTimestampableTrait;

  /**
   * @var string
   * @ORM\Column(name="id", type="string", length=40)
   * @ORM\Id
   */
  protected $id;

  /**
   * @var ComponentValue
   * @ORM\ManyToOne(targetEntity="Austral\ContentBlockBundle\Entity\Interfaces\ComponentValueInterface", inversedBy="children")
   * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
   */
  protected ComponentValue $parent;

  /**
   * @ORM\OneToMany(targetEntity="Austral\ContentBlockBundle\Entity\Interfaces\ComponentValueInterface", indexBy="id", mappedBy="parent", cascade={"persist", "remove"}, orphanRemoval=true)
   * @ORM\OrderBy({"position" = "ASC"})
   */
  protected Collection $children;

  /**
   * @var int
   * @ORM\Column(name="position", type="integer", nullable=true, options={"default" : 0} )
   */
  protected int $position = 0;

  /**
   * Constructor
   * @throws Exception
   */
  public function __construct()
  {
    parent::__construct();
    $this->id = Uuid::uuid4()->toString();
    $this->children = new ArrayCollection();
  }

  /**
   * @return string
   */
  public function getId(): string
  {
    return $this->id;
  }

  /**
   * @param string $id
   *
   * @return $this
   */
  public function setId(string $id): ComponentValues
  {
    $this->id = $id;
    return $this;
  }

  /**
   * @return ComponentValue
   */
  public function getParent(): ComponentValue
  {
    return $this->parent;
  }

  /**
   * @param ComponentValue $parent
   *
   * @return $this
   */
  public function setParent(ComponentValue $parent): ComponentValues
  {
    $this->parent = $parent;
    return $this;
  }

  /**
   * @return Collection
   */
  public function getChildren(): Collection
  {
    return $this->children;
  }

  /**
   * @param EditorComponentType $editorComponentType
   *
   * @return \App\Entity\Austral\ContentBlockBundle\ComponentValue|ComponentValueInterface
   */
  public function getChildrenByEditorComponentType(EditorComponentType $editorComponentType)
  {
    /** @var ComponentValueInterface $componentValue */
    foreach($this->children as $componentValue)
    {
      if($componentValue->getEditorComponentType()->getId() === $editorComponentType->getId())
      {
        return $componentValue;
      }
    }
    $componentValue = new \App\Entity\Austral\ContentBlockBundle\ComponentValue();
    $componentValue->setEditorComponentType($editorComponentType);
    return $componentValue;
  }

  /**
   * @param Collection $children
   *
   * @return $this
   */
  public function setChildren(Collection $children): ComponentValues
  {
    $this->children = $children;
    return $this;
  }

  /**
   * Add child
   *
   * @param ComponentValueInterface $child
   *
   * @return $this
   */
  public function addChildren(ComponentValueInterface $child): ComponentValues
  {
    $child->setParent($this);
    if(!$this->children->contains($child))
    {
      $this->children->add($child);
    }
    return $this;
  }

  /**
   * @return int
   */
  public function getPosition(): int
  {
    return $this->position;
  }

  /**
   * @param int $position
   *
   * @return $this
   */
  public function setPosition(int $position): ComponentValues
  {
    $this->position = $position;
    return $this;
  }

}