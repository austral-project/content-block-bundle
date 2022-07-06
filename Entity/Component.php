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

use Austral\ContentBlockBundle\Entity\Interfaces\ComponentInterface;
use Austral\ContentBlockBundle\Entity\Interfaces\ComponentValueInterface;

use Austral\ContentBlockBundle\Entity\Interfaces\EditorComponentInterface;
use Austral\ContentBlockBundle\Entity\Interfaces\EditorComponentTypeInterface;
use Austral\ContentBlockBundle\Entity\Interfaces\LibraryInterface;
use Austral\ContentBlockBundle\Model\Editor\Layout;
use Austral\ContentBlockBundle\Model\Editor\Option;
use Austral\ContentBlockBundle\Model\Editor\Theme;
use Austral\EntityBundle\Entity\Entity;
use Austral\EntityBundle\Entity\EntityInterface;
use Austral\EntityBundle\Entity\Traits\EntityTimestampableTrait;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

use Exception;

/**
 * Austral Component Entity.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @abstract
 * @ORM\MappedSuperclass
 */
abstract class Component extends Entity implements ComponentInterface, EntityInterface
{

  use EntityTimestampableTrait;

  /**
   * @var string
   * @ORM\Column(name="id", type="string", length=40)
   * @ORM\Id
   */
  protected $id;
  
  /**
   * @var string
   * @ORM\Column(name="object_classname", type="string", length=255, nullable=true )
   */
  protected string $objectClassname;

  /**
   * @var string|null
   * @ORM\Column(name="object_id", type="string", length=255, nullable=true )
   */
  protected ?string $objectId = null;

  /**
   * @var string|null
   * @ORM\Column(name="object_container_name", type="string", length=255, nullable=true, options={"default": "master"} )
   */
  protected ?string $objectContainerName = "master";

  /**
   * @var EditorComponentInterface|null
   * @ORM\ManyToOne(targetEntity="Austral\ContentBlockBundle\Entity\Interfaces\EditorComponentInterface", inversedBy="components")
   * @ORM\JoinColumn(name="editor_component_id", referencedColumnName="id", onDelete="CASCADE")
   */
  protected ?EditorComponentInterface $editorComponent = null;

  /**
   * @var LibraryInterface|null
   * @ORM\ManyToOne(targetEntity="Austral\ContentBlockBundle\Entity\Interfaces\LibraryInterface", inversedBy="componentsLibrary")
   * @ORM\JoinColumn(name="library_id", referencedColumnName="id", onDelete="CASCADE")
   */
  protected ?LibraryInterface $library = null;

  /**
   * @ORM\OneToMany(targetEntity="Austral\ContentBlockBundle\Entity\Interfaces\ComponentValueInterface", indexBy="id", mappedBy="component", cascade={"persist", "remove"}, orphanRemoval=true)
   * @ORM\OrderBy({"position" = "ASC"})
   */
  protected Collection $componentValues;

  /**
   * @var string|null
   * @ORM\Column(name="theme_id", type="string", length=255, nullable=true )
   */
  protected ?string $themeId = null;

  /**
   * @var string|null
   * @ORM\Column(name="option_id", type="string", length=255, nullable=true )
   */
  protected ?string $optionId = null;

  /**
   * @var string|null
   * @ORM\Column(name="layout_id", type="string", length=255, nullable=true )
   */
  protected ?string $layoutId = null;

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
    $this->componentValues = new ArrayCollection();
  }

  /**
   * @return string
   */
  public function getId(): string
  {
    return $this->id;
  }

  /**
   * @return string|null
   */
  public function getType(): ?string
  {
    return $this->editorComponent->getKeyname();
  }

  /**
   * @return string|null
   */
  public function getTheme(): ?string
  {
    return $this->editorComponent->getThemeById($this->themeId);
  }

  /**
   * @return string|null
   */
  public function getThemeKeyname(): ?string
  {
    /** @var Theme $theme */
    if($theme = $this->getTheme())
    {
      return $theme->getKeyname();
    }
    return null;
  }

  /**
   * @return string
   */
  public function getComponentType(): string
  {
    return $this->library ? "library" : "default";
  }

  /**
   * @return string|null
   */
  public function getOption(): ?string
  {
    return $this->editorComponent->getOptionById($this->optionId);
  }

  /**
   * @return string|null
   */
  public function getOptionKeyname(): ?string
  {
    /** @var Option $option */
    if($option = $this->getOption())
    {
      return $option->getKeyname();
    }
    return null;
  }

  /**
   * @return Layout|null
   */
  public function getLayout(): ?Layout
  {
    return $this->editorComponent->getLayoutById($this->layoutId);
  }

  /**
   * @return string|null
   */
  public function getLayoutKeyname(): ?string
  {
    /** @var Layout $layout */
    if($layout = $this->getLayout())
    {
      return $layout->getKeyname();
    }
    return null;
  }

  /**
   * @param string $id
   *
   * @return Component
   */
  public function setId(string $id): Component
  {
    $this->id = $id;
    return $this;
  }

  /**
   * @return string
   */
  public function getObjectClassname(): string
  {
    return $this->objectClassname;
  }

  /**
   * @param string $objectClassname
   *
   * @return Component
   */
  public function setObjectClassname(string $objectClassname): Component
  {
    $this->objectClassname = $objectClassname;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getObjectId(): ?string
  {
    return $this->objectId;
  }

  /**
   * @param string|null $objectId
   *
   * @return Component
   */
  public function setObjectId(?string $objectId): Component
  {
    $this->objectId = $objectId;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getObjectContainerName(): ?string
  {
    return $this->objectContainerName;
  }

  /**
   * @param string|null $objectContainerName
   *
   * @return Component
   */
  public function setObjectContainerName(?string $objectContainerName): Component
  {
    $this->objectContainerName = $objectContainerName;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getLayoutId(): ?string
  {
    return $this->layoutId;
  }

  /**
   * @param string|null $layoutId
   *
   * @return $this
   */
  public function setLayoutId(?string $layoutId): Component
  {
    $this->layoutId = $layoutId;
    return $this;
  }

  /**
   * @return EditorComponentInterface|null
   */
  public function getEditorComponent(): ?EditorComponentInterface
  {
    return $this->editorComponent;
  }

  /**
   * @param EditorComponentInterface $editorComponent
   *
   * @return Component
   */
  public function setEditorComponent(EditorComponentInterface $editorComponent): Component
  {
    $this->editorComponent = $editorComponent;
    return $this;
  }

  /**
   * @return LibraryInterface|null
   */
  public function getLibrary(): ?LibraryInterface
  {
    return $this->library;
  }

  /**
   * @param LibraryInterface $library
   *
   * @return $this
   */
  public function setLibrary(LibraryInterface $library): Component
  {
    $this->library = $library;
    return $this;
  }

  /**
   * @return Collection
   */
  public function getComponentValues(): Collection
  {
    return $this->componentValues;
  }

  /**
   * @param EditorComponentTypeInterface $editorComponentType
   *
   * @return ComponentValueInterface
   */
  public function getComponentValuesByEditorComponentType(EditorComponentTypeInterface $editorComponentType)
  {
    /** @var ComponentValueInterface $componentValue */
    foreach($this->componentValues as $componentValue)
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
   * @param Collection $componentValues
   *
   * @return Component
   */
  public function setComponentValues(Collection $componentValues): Component
  {
    $this->componentValues = $componentValues;
    return $this;
  }

  /**
   * Add child
   *
   * @param ComponentValueInterface $child
   *
   * @return $this
   */
  public function addComponentValues(ComponentValueInterface $child): Component
  {
    if(!$this->componentValues->contains($child))
    {
      $child->setComponent($this);
      $this->componentValues->add($child);
    }
    return $this;
  }

  /**
   * Remove child
   *
   * @param ComponentValueInterface $child
   *
   * @return $this
   */
  public function removeComponentValues(ComponentValueInterface $child): Component
  {
    if($this->componentValues->contains($child))
    {
      $child->setComponent(null);
      $this->componentValues->removeElement($child);
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
   * @return Component
   */
  public function setPosition(int $position): Component
  {
    $this->position = $position;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getThemeId(): ?string
  {
    return $this->themeId;
  }

  /**
   * @param string|null $themeId
   *
   * @return Component
   */
  public function setThemeId(?string $themeId): Component
  {
    $this->themeId = $themeId;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getOptionId(): ?string
  {
    return $this->optionId;
  }

  /**
   * @param string|null $optionId
   *
   * @return Component
   */
  public function setOptionId(?string $optionId): Component
  {
    $this->optionId = $optionId;
    return $this;
  }

}