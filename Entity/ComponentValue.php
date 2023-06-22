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

use Austral\ContentBlockBundle\Entity\Interfaces\ComponentValuesInterface;
use Austral\ContentBlockBundle\Entity\Interfaces\EditorComponentTypeInterface;
use Austral\EntityBundle\Entity\Entity;
use Austral\EntityBundle\Entity\EntityInterface;
use Austral\EntityBundle\Entity\Traits\EntityTimestampableTrait;

use Austral\EntityFileBundle\Entity\Traits\EntityFileCropperTrait;
use Austral\EntityFileBundle\Entity\Traits\EntityFileTrait;
use Austral\EntityFileBundle\Annotation as AustralFile;

use Austral\ToolsBundle\AustralTools;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

use Exception;

/**
 * Austral ComponentValue Entity.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @abstract
 * @ORM\MappedSuperclass
 */
abstract class ComponentValue extends Entity implements ComponentValueInterface, EntityInterface
{

  use EntityTimestampableTrait;
  use EntityFileTrait;
  use EntityFileCropperTrait;

  /**
   * @var array|string[]
   */
  protected array $linksTypeAvailable = array(
    "internal"  =>  "internal",
    "interne"   =>  "internal",
    "external"  =>  "external",
    "externe"   =>  "external",
    "file"      =>  "file",
    "phone"     =>  "phone",
    "email"     =>  "email"
  );

  /**
   * @var string
   * @ORM\Column(name="id", type="string", length=40)
   * @ORM\Id
   */
  protected $id;

  /**
   * @var EditorComponentTypeInterface|null
   * @ORM\ManyToOne(targetEntity="Austral\ContentBlockBundle\Entity\Interfaces\EditorComponentTypeInterface", inversedBy="componentValues")
   * @ORM\JoinColumn(name="editor_component_type_id", referencedColumnName="id", onDelete="CASCADE")
   */
  protected ?EditorComponentTypeInterface $editorComponentType = null;

  /**
   * @var ComponentInterface
   * @ORM\ManyToOne(targetEntity="Austral\ContentBlockBundle\Entity\Interfaces\ComponentInterface", inversedBy="componentValues")
   * @ORM\JoinColumn(name="component_id", referencedColumnName="id", onDelete="CASCADE")
   */
  protected ComponentInterface $component;

  /**
   * @var ComponentValuesInterface|null
   * @ORM\ManyToOne(targetEntity="Austral\ContentBlockBundle\Entity\Interfaces\ComponentValuesInterface", inversedBy="children")
   * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
   */
  protected ?ComponentValuesInterface $parent = null;

  /**
   * @ORM\OneToMany(targetEntity="Austral\ContentBlockBundle\Entity\Interfaces\ComponentValuesInterface", indexBy="id", mappedBy="parent", cascade={"persist", "remove"}, orphanRemoval=true)
   * @ORM\OrderBy({"position" = "ASC"})
   */
  protected Collection $children;

  /**
   * @var int
   * @ORM\Column(name="position", type="integer", nullable=true, options={"default" : 0} )
   */
  protected int $position = 0;
  
  /**
   * @var array
   * @ORM\Column(name="options", type="json", nullable=true )
   */
  protected array $options = array();

  /**
   * @var string|null
   * @ORM\Column(name="content", type="text", nullable=true)
   */
  protected ?string $content = null;

  /**
   * @var \DateTime|null
   * @ORM\Column(name="date", type="datetime", nullable=true)
   */
  protected ?\DateTime $date = null;
  
  /**
   * @var string|null
   * @ORM\Column(name="image", type="string", length=255, nullable=true)
   * @AustralFile\UploadParameters(configName="default_image")
   * @AustralFile\ImageSize()
   */
  protected ?string $image = null;

  /**
   * @var string|null
   * @ORM\Column(name="file", type="string", length=255, nullable=true)
   * @AustralFile\UploadParameters(configName="default_file")
   */
  protected ?string $file = null;

  /**
   * @var string|null
   * @ORM\Column(name="link_type", type="string", length=255, nullable=true)
   */
  protected ?string $linkType = null;

  /**
   * @var string|null
   * @ORM\Column(name="link_url", type="string", length=255, nullable=true)
   */
  protected ?string $linkUrl = null;

  /**
   * @var string|null
   * @ORM\Column(name="link_email", type="string", length=255, nullable=true)
   */
  protected ?string $linkEmail = null;

  /**
   * @var string|null
   * @ORM\Column(name="link_phone", type="string", length=255, nullable=true)
   */
  protected ?string $linkPhone = null;

  /**
   * @var string|null
   * @ORM\Column(name="link_picto", type="string", length=255, nullable=true)
   */
  protected ?string $linkPicto = null;

  /**
   * @var string|null
   * @ORM\Column(name="link_entity_key", type="string", length=255, nullable=true)
   */
  protected ?string $linkEntityKey = null;

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
   * @return ComponentValue
   */
  public function setId(string $id): ComponentValue
  {
    $this->id = $id;
    return $this;
  }

  /**
   * @return EditorComponentTypeInterface|null
   */
  public function getEditorComponentType(): ?EditorComponentTypeInterface
  {
    return $this->editorComponentType;
  }

  /**
   * @param EditorComponentTypeInterface|null $editorComponentType
   *
   * @return ComponentValue
   */
  public function setEditorComponentType(?EditorComponentTypeInterface $editorComponentType): ComponentValue
  {
    $this->editorComponentType = $editorComponentType;
    return $this;
  }

  /**
   * @return Component
   */
  public function getComponent(): Component
  {
    return $this->component;
  }

  /**
   * @param ComponentInterface $component
   *
   * @return ComponentValue
   */
  public function setComponent(ComponentInterface $component): ComponentValue
  {
    $this->component = $component;
    return $this;
  }

  /**
   * @return ComponentValues|null
   */
  public function getParent(): ?ComponentValues
  {
    return $this->parent;
  }

  /**
   * @param ComponentValues|null $parent
   *
   * @return ComponentValue
   */
  public function setParent(?ComponentValuesInterface $parent): ComponentValue
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
   * @param Collection $children
   *
   * @return ComponentValue
   */
  public function setChildren(Collection $children): ComponentValue
  {
    $this->children = $children;
    return $this;
  }

  /**
   * Add child
   *
   * @param ComponentValuesInterface $child
   *
   * @return $this
   */
  public function addChildren(ComponentValuesInterface $child): ComponentValue
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
   * @return ComponentValue
   */
  public function setPosition(int $position): ComponentValue
  {
    $this->position = $position;
    return $this;
  }

  /**
   * @return array
   */
  public function getOptions(): array
  {
    return $this->options;
  }

  /**
   * @param string $key
   * @param null $default
   *
   * @return mixed|null
   */
  public function getOptionsByKey(string $key, $default = null)
  {
    if(array_key_exists($key, $this->options))
    {
      return  $this->options[$key];
    }
    elseif($editorComponentType = $this->getEditorComponentType())
    {
      $editorComponentTypeParameter = $editorComponentType->getParameterByKey($key);
      if(is_array($editorComponentTypeParameter))
      {
        return AustralTools::first($editorComponentTypeParameter);
      }
      else
      {
        return $editorComponentTypeParameter;
      }
    }
    return $default;
  }

  /**
   * @param array $options
   *
   * @return ComponentValue
   */
  public function setOptions(array $options): ComponentValue
  {
    $this->options = $options;
    return $this;
  }

  /**
   * @param string $key
   * @param $value
   *
   * @return $this
   */
  public function setOptionsByKey(string $key, $value): ComponentValue
  {
    $this->options[$key] = $value;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getContent(): ?string
  {
    return $this->content;
  }

  /**
   * @param string|null $content
   *
   * @return ComponentValue
   */
  public function setContent(?string $content): ComponentValue
  {
    $this->content = $content;
    return $this;
  }

  /**
   * @return \DateTime|null
   */
  public function getDate(): ?\DateTime
  {
    return $this->date;
  }

  /**
   * @param \DateTime|null $date
   *
   * @return ComponentValue
   */
  public function setDate(?\DateTime $date): ComponentValue
  {
    $this->date = $date;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getImage(): ?string
  {
    return $this->image;
  }

  /**
   * @param string|null $image
   *
   * @return ComponentValue
   */
  public function setImage(?string $image): ComponentValue
  {
    $this->image = $image;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getFile(): ?string
  {
    return $this->file;
  }

  /**
   * @param string|null $file
   *
   * @return ComponentValue
   */
  public function setFile(?string $file): ComponentValue
  {
    $this->file = $file;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getLinkType(): ?string
  {
    return AustralTools::getValueByKey($this->linksTypeAvailable, $this->linkType, null);
  }

  /**
   * @param string|null $linkType
   *
   * @return ComponentValue
   */
  public function setLinkType(?string $linkType): ComponentValue
  {
    $this->linkType = $linkType;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getLinkUrl(): ?string
  {
    return $this->linkUrl;
  }

  /**
   * @param string|null $linkUrl
   *
   * @return ComponentValue
   */
  public function setLinkUrl(?string $linkUrl): ComponentValue
  {
    $this->linkUrl = $linkUrl;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getLinkEmail(): ?string
  {
    return $this->linkEmail;
  }

  /**
   * @param string|null $linkEmail
   *
   * @return $this
   */
  public function setLinkEmail(?string $linkEmail): ComponentValue
  {
    $this->linkEmail = $linkEmail;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getLinkPhone(): ?string
  {
    return $this->linkPhone;
  }

  /**
   * @param string|null $linkPhone
   *
   * @return $this
   */
  public function setLinkPhone(?string $linkPhone): ComponentValue
  {
    $this->linkPhone = $linkPhone;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getLinkPicto(): ?string
  {
    return $this->linkPicto;
  }

  /**
   * @param string|null $linkPicto
   *
   * @return $this
   */
  public function setLinkPicto(?string $linkPicto): ComponentValue
  {
    $this->linkPicto = $linkPicto;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getLinkEntityKey(): ?string
  {
    return $this->linkEntityKey;
  }

  /**
   * @param string|null $linkEntityKey
   *
   * @return ComponentValue
   */
  public function setLinkEntityKey(?string $linkEntityKey): ComponentValue
  {
    $this->linkEntityKey = $linkEntityKey;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getFileFilenameRewrite(): ?string
  {
    return $this->getOptionsByKey("fileReelname");
  }

  /**
   * @return string|null
   */
  public function getImageFilenameRewrite(): ?string
  {
    return $this->getOptionsByKey("reelname");
  }

}