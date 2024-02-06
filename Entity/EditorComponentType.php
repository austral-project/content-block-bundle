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

use Austral\ContentBlockBundle\Entity\Interfaces\EditorComponentInterface;
use Austral\ContentBlockBundle\Entity\Interfaces\EditorComponentTypeInterface;

use Austral\EntityBundle\Entity\Entity;
use Austral\EntityBundle\Entity\EntityInterface;
use Austral\EntityBundle\Entity\Traits\EntityTimestampableTrait;

use Austral\ToolsBundle\AustralTools;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

use Exception;

/**
 * Austral EditorComponentType Entity.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @abstract
 * @ORM\MappedSuperclass
 */
abstract class EditorComponentType extends Entity implements EditorComponentTypeInterface, EntityInterface
{

  use EntityTimestampableTrait;

  /**
   * @var string
   * @ORM\Column(name="id", type="string", length=40)
   * @ORM\Id
   */
  protected $id;

  /**
   * @var EditorComponent|null
   * @ORM\ManyToOne(targetEntity="Austral\ContentBlockBundle\Entity\Interfaces\EditorComponentInterface", inversedBy="editorComponentTypes")
   * @ORM\JoinColumn(name="editor_component_id", referencedColumnName="id", onDelete="CASCADE")
   */
  protected ?EditorComponent $editorComponent = null;

  /**
   * @ORM\OneToMany(targetEntity="Austral\ContentBlockBundle\Entity\Interfaces\ComponentValueInterface", mappedBy="editorComponentType", cascade={"persist", "remove"})
   */
  protected Collection $componentValues;

  /**
   * @var string|null
   * @ORM\Column(name="parent_id", type="string", nullable=true )
   */
  protected ?string $parentId = null;

  /**
   * @var EditorComponentType|null
   */
  protected ?EditorComponentType $parent = null;

  /**
   * @var array
   */
  protected array $children = array();

  /**
   * @var int
   * @ORM\Column(name="position", type="integer", nullable=true, options={"default" : 0} )
   */
  protected int $position = 0;

  /**
   * @var string|null
   * @ORM\Column(name="type", type="string", length=255, nullable=false)
   */
  protected ?string $type;

  /**
   * @var string|null
   * @ORM\Column(name="block_direction", type="string", length=15, nullable=true)
   */
  protected ?string $blockDirection = null;

  /**
   * @var string|null
   * @ORM\Column(name="keyname", type="string", length=255, nullable=false)
   */
  protected ?string $keyname = null;

  /**
   * @var string|null
   * @ORM\Column(name="entitled", type="string", length=255, nullable=false)
   */
  protected ?string $entitled = null;

  /**
   * @var string|null
   * @ORM\Column(name="css_class", type="string", length=255, nullable=true)
   */
  protected ?string $cssClass;

  /**
   * @var boolean
   * @ORM\Column(name="can_has_link", type="boolean", nullable=true, options={"default" : false} )
   */
  protected bool $canHasLink = false;
  
  /**
   * @var array
   * @ORM\Column(name="parameters", type="json", nullable=false)
   */
  protected array $parameters = array();

  /**
   * Constructor
   * @throws Exception
   */
  public function __construct()
  {
    parent::__construct();
    $this->id = Uuid::uuid4()->toString();
    $this->type = null;
  }

  /**
   * @return string|null
   */
  public function __toString()
  {
    return $this->type;
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
   * @return EditorComponentType
   */
  public function setId(string $id): EditorComponentType
  {
    $this->id = $id;
    return $this;
  }

  /**
   * @return ?EditorComponent
   */
  public function getEditorComponent(): ?EditorComponent
  {
    return $this->editorComponent;
  }

  /**
   * @param ?EditorComponentInterface $editorComponent
   *
   * @return EditorComponentType
   */
  public function setEditorComponent(?EditorComponentInterface $editorComponent): EditorComponentType
  {
    $this->editorComponent = $editorComponent;
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
   * @param Collection $componentValues
   *
   * @return EditorComponentType
   */
  public function setComponentValues(Collection $componentValues): EditorComponentType
  {
    $this->componentValues = $componentValues;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getParentId(): ?string
  {
    return $this->parentId !== $this->id ? $this->parentId : null;
  }

  /**
   * @param string|null $parentId
   *
   * @return EditorComponentType
   */
  public function setParentId(?string $parentId): EditorComponentType
  {
    $this->parentId = $parentId;
    return $this;
  }

  /**
   * @return EditorComponentType|null
   */
  public function getParent(): ?EditorComponentType
  {
    return $this->parent;
  }

  /**
   * @param EditorComponentTypeInterface|null $parent
   *
   * @return EditorComponentType
   */
  public function setParent(?EditorComponentTypeInterface $parent): EditorComponentType
  {
    $this->parent = $parent;
    $this->parentId = $parent->getId();
    return $this;
  }

  /**
   * @return array
   */
  public function getChildren(): array
  {
    return $this->children;
  }

  /**
   * @param array $children
   *
   * @return EditorComponentType
   */
  public function setChildren(array $children = array()): EditorComponentType
  {
    $this->children = $children;
    return $this;
  }

  /**
   * @param EditorComponentTypeInterface $editorComponentType
   *
   * @return $this
   */
  public function addChildren(EditorComponentTypeInterface $editorComponentType): EditorComponentType
  {
    $this->children[$editorComponentType->getId()] = $editorComponentType;
    $editorComponentType->setParent($this);
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
   * @return EditorComponentType
   */
  public function setPosition(int $position): EditorComponentType
  {
    $this->position = $position;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getType(): ?string
  {
    return $this->type;
  }

  /**
   * @param string|null $type
   *
   * @return EditorComponentType
   */
  public function setType(?string $type): EditorComponentType
  {
    $this->type = $type;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getKeyname(): ?string
  {
    return $this->keyname;
  }

  /**
   * @param string|null $keyname
   *
   * @return EditorComponentType
   */
  public function setKeyname(?string $keyname): EditorComponentType
  {
    $this->keyname = $this->keynameGenerator($keyname);
    return $this;
  }

  /**
   * @return string|null
   */
  public function getEntitled(): ?string
  {
    return $this->entitled;
  }

  /**
   * @param string|null $entitled
   *
   * @return EditorComponentType
   */
  public function setEntitled(?string $entitled): EditorComponentType
  {
    $this->entitled = $entitled;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getCssClass(): ?string
  {
    return $this->cssClass;
  }

  /**
   * @param string|null $cssClass
   *
   * @return EditorComponentType
   */
  public function setCssClass(?string $cssClass): EditorComponentType
  {
    $this->cssClass = $cssClass;
    return $this;
  }

  /**
   * @return bool
   */
  public function getCanHasLink(): bool
  {
    return $this->type == "button" ? true : $this->canHasLink;
  }

  /**
   * @param bool $canHasLink
   *
   * @return EditorComponentType
   */
  public function setCanHasLink(bool $canHasLink): EditorComponentType
  {
    $this->canHasLink = $canHasLink;
    return $this;
  }

  /**
   * @return array
   */
  public function getParameters(): array
  {
    return $this->parameters;
  }

  /**
   * @param array $parameters
   *
   * @return EditorComponentType
   */
  public function setParameters(array $parameters): EditorComponentType
  {
    $this->parameters = $parameters;
    return $this;
  }

  /**
   * @param string $key
   * @param null $default
   *
   * @return array|mixed|string|null
   */
  public function getParameterByKey(string $key, $default = null)
  {
    return AustralTools::getValueByKey($this->parameters, $key, $default);
  }

  /**
   * @param string $key
   * @param null $value
   *
   * @return $this
   */
  public function setParameterByKey(string $key, $value = null): EditorComponentType
  {
    $this->parameters[$key] = $value;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getBlockDirection(): ?string
  {
    return $this->blockDirection;
  }

  /**
   * @param string|null $blockDirection
   *
   * @return $this
   */
  public function setBlockDirection(?string $blockDirection): EditorComponentType
  {
    $this->blockDirection = $blockDirection;
    return $this;
  }

}