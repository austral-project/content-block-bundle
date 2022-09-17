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

use Austral\ContentBlockBundle\Model\Editor\Layout;
use Austral\ContentBlockBundle\Model\Editor\Option;
use Austral\ContentBlockBundle\Model\Editor\Restriction;
use Austral\ContentBlockBundle\Model\Editor\Theme;
use Austral\EntityBundle\Entity\Entity;
use Austral\EntityBundle\Entity\EntityInterface;
use Austral\EntityBundle\Entity\Traits\EntityTimestampableTrait;
use Austral\EntityFileBundle\Annotation as AustralFile;

use Austral\EntityBundle\Entity\Interfaces\FileInterface;
use Austral\EntityFileBundle\Entity\Traits\EntityFileTrait;
use Austral\ToolsBundle\AustralTools;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

use Exception;
use function Symfony\Component\String\u;

/**
 * Austral EditorComponent Entity.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @abstract
 * @ORM\MappedSuperclass
 */
abstract class EditorComponent extends Entity implements EditorComponentInterface, EntityInterface, FileInterface
{

  use EntityTimestampableTrait;
  use EntityFileTrait;

  /**
   * @var string
   * @ORM\Column(name="id", type="string", length=40)
   * @ORM\Id
   */
  protected $id;

  /**
   * @ORM\OneToMany(targetEntity="Austral\ContentBlockBundle\Entity\Interfaces\ComponentInterface", mappedBy="editorComponent", cascade={"persist", "remove"})
   */
  protected Collection $components;

  /**
   * @ORM\OneToMany(targetEntity="Austral\ContentBlockBundle\Entity\Interfaces\EditorComponentTypeInterface",  indexBy="id", mappedBy="editorComponent", cascade={"persist", "remove"}, orphanRemoval=true)
   * @ORM\OrderBy({"position" = "ASC"})
   */
  protected Collection $editorComponentTypes;

  /**
   * @var string|null
   * @ORM\Column(name="category", type="string", length=255, nullable=false, options={"default" : "default"}  )
   */
  protected ?string $category = null;

  /**
   * @var string|null
   * @ORM\Column(name="name", type="string", length=255, nullable=false )
   */
  protected ?string $name = null;

  /**
   * @var boolean
   * @ORM\Column(name="is_container", type="boolean", nullable=true, options={"default" : false} )
   */
  protected bool $isContainer = false;

  /**
   * @var boolean
   * @ORM\Column(name="is_guideline_view", type="boolean", nullable=true, options={"default" : true} )
   */
  protected bool $isGuidelineView = true;

  /**
   * @var string|null
   * @ORM\Column(name="keyname", type="string", length=255, nullable=true )
   */
  protected ?string $keyname = null;

  /**
   * @var string|null
   * @ORM\Column(name="image", type="string", length=255, nullable=true)
   * @AustralFile\UploadParameters(isRequired=false, configName="editor_component")
   */
  protected ?string $image = null;

  /**
   * @var string|null
   * @ORM\Column(name="template_path", type="string", length=255, nullable=true )
   */
  protected ?string $templatePath = null;

  /**
   * @var array
   * @ORM\Column(name="themes", type="json", nullable=true)
   */
  protected array $themes = array();

  /**
   * @var array
   * @ORM\Column(name="options", type="json", nullable=true)
   */
  protected array $options = array();

  /**
   * @var array|null
   * @ORM\Column(name="layouts", type="json", nullable=true)
   */
  protected ?array $layouts = array();

  /**
   * @var boolean
   * @ORM\Column(name="is_enabled", type="boolean", nullable=true, options={"default" : true} )
   */
  protected bool $isEnabled = true;

  /**
   * @var array
   * @ORM\Column(name="restrictions", type="json", nullable=true )
   */
  protected array $restrictions = array();

  /**
   * Constructor
   * @throws Exception
   */
  public function __construct()
  {
    parent::__construct();
    $this->id = Uuid::uuid4()->toString();
    $this->editorComponentTypes = new ArrayCollection();
    $this->components = new ArrayCollection();
  }

  /**
   * @return array
   */
  public function getFieldsToDelete(): array
  {
    return array("image");
  }

  /**
   * @return string|null
   */
  public function __toString()
  {
    return $this->name ? : "";
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
   * @return EditorComponent
   */
  public function setId(string $id): EditorComponent
  {
    $this->id = $id;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getCategory(): ?string
  {
    return $this->category;
  }

  /**
   * @param string|null $category
   *
   * @return EditorComponent
   */
  public function setCategory(?string $category): EditorComponent
  {
    $this->category = $category;
    return $this;
  }

  /**
   * @return Collection
   */
  public function getComponents(): Collection
  {
    return $this->components;
  }

  /**
   * @param Collection $components
   *
   * @return EditorComponent
   */
  public function setComponents(Collection $components): EditorComponent
  {
    $this->components = $components;
    return $this;
  }

  /**
   * @param string|null $type
   *
   * @return array
   */
  public function getEditorComponentTypesByTypes(?string $type = "all"): array
  {
    $editorComponentTypesByType = array();
    /** @var EditorComponentTypeInterface $editorComponentType */
    foreach($this->editorComponentTypes as $editorComponentType)
    {
      /** @var EditorComponentTypeInterface $parent */
      if($editorComponentType->getParentId() && ($parent = $this->editorComponentTypes->get($editorComponentType->getParentId())))
      {
        $parent->addChildren($editorComponentType);
      }
      if(!array_key_exists($editorComponentType->getType(), $editorComponentTypesByType))
      {
        $editorComponentTypesByType[$editorComponentType->getType()] = array();
      }
      $editorComponentTypesByType[$editorComponentType->getType()][$editorComponentType->getId()] = $editorComponentType;
    }
    return $type === "all" ? $editorComponentTypesByType : (array_key_exists($type, $editorComponentTypesByType) ? $editorComponentTypesByType[$type] : array());
  }

  /**
   * @return array
   */
  public function getEditorComponentTypesWithChild(): array
  {
    $editorComponentTypes = array();
    /** @var EditorComponentTypeInterface $editorComponentType */
    foreach($this->editorComponentTypes as $editorComponentType)
    {
      /** @var EditorComponentTypeInterface $parent */
      if($editorComponentType->getParentId() && ($parent = $this->editorComponentTypes->get($editorComponentType->getParentId())))
      {
        $parent->addChildren($editorComponentType);
      }
      else
      {
        $editorComponentType->setParentId(null);
        $editorComponentTypes[$editorComponentType->getId()] = $editorComponentType;
      }
    }
    return $editorComponentTypes;
  }

  /**
   * @return Collection
   */
  public function getEditorComponentTypes(): Collection
  {
    return $this->editorComponentTypes;
  }

  /**
   * @param Collection $editorComponentTypes
   *
   * @return EditorComponent
   */
  public function setEditorComponentTypes(Collection $editorComponentTypes): EditorComponent
  {
    $this->editorComponentTypes = $editorComponentTypes;
    return $this;
  }

  /**
   * Add child
   *
   * @param EditorComponentTypeInterface $child
   *
   * @return $this
   */
  public function addEditorComponentType(EditorComponentTypeInterface $child): EditorComponent
  {
    if(!$this->editorComponentTypes->contains($child))
    {
      $child->setEditorComponent($this);
      $this->editorComponentTypes->add($child);
    }
    return $this;
  }

  /**
   * Remove child
   *
   * @param EditorComponentTypeInterface $child
   *
   * @return $this
   */
  public function removeEditorComponentType(EditorComponentTypeInterface $child): EditorComponent
  {
    if($this->editorComponentTypes->contains($child))
    {
      $child->setEditorComponent(null);
      $this->editorComponentTypes->removeElement($child);
    }
    return $this;
  }

  /**
   * @return string|null
   */
  public function getName(): ?string
  {
    return $this->name;
  }

  /**
   * @param string|null $name
   *
   * @return EditorComponent
   */
  public function setName(?string $name): EditorComponent
  {
    $this->name = $name;
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
   * @return EditorComponent
   */
  public function setKeyname(?string $keyname): EditorComponent
  {
    $this->keyname = $this->keynameGenerator($keyname);
    return $this;
  }

  /**
   * @return string|null
   */
  public function getTemplatePath(): ?string
  {
    return $this->templatePath;
  }

  /**
   * @return string|null
   */
  public function getTemplatePathOrDefault(): ?string
  {
    $template = $this->templatePath ?? "Components\\{$this->keyname}.html.twig";
    return str_replace("/", "\\", $template);
  }

  /**
   * @param string|null $templatePath
   *
   * @return EditorComponent
   */
  public function setTemplatePath(?string $templatePath): EditorComponent
  {
    $this->templatePath = $templatePath;
    return $this;
  }

  /**
   * Get themes
   * @return array
   */
  public function getThemes(): array
  {
    $themes = array();
    foreach($this->themes as $themeValues)
    {
      /** @var Theme $themeObject */
      $themeObject = unserialize($themeValues);
      $themes[$themeObject->getId()] = $themeObject;
    }
    return $themes;
  }

  /**
   * @param string|null $themeId
   *
   * @return Theme|null
   */
  public function getThemeById(?string $themeId): ?Theme
  {
    return AustralTools::getValueByKey($this->getThemes(), $themeId, null);
  }

  /**
   * @param array $themes
   *
   * @return EditorComponent
   */
  public function setThemes(array $themes): EditorComponent
  {
    $this->themes = array();
    /** @var Theme $theme */
    foreach ($themes as $id => $theme)
    {
      $theme->setId($id);
      if(!$theme->getKeyname()) {
        $theme->setKeyname(u($theme->getTitle())->snake()->replace("_", "-")->toString());
      }
      $this->themes[$theme->getPosition()] = serialize($theme);
    }
    ksort($this->themes);
    return $this;
  }

  /**
   * @return array
   */
  public function getOptions(): array
  {
    $options = array();
    foreach($this->options as $optionValues)
    {
      /** @var Option $optionObject */
      $optionObject = unserialize($optionValues);
      $options[$optionObject->getId()] = $optionObject;
    }
    return $options;
  }

  /**
   * @param string|null $optionId
   *
   * @return Option|null
   */
  public function getOptionById(?string $optionId): ?Option
  {
    return AustralTools::getValueByKey($this->getOptions(), $optionId, null);
  }

  /**
   * @param array $options
   *
   * @return EditorComponent
   */
  public function setOptions(array $options): EditorComponent
  {
    $this->options = array();
    /** @var Option $option */
    foreach ($options as $id => $option)
    {
      $option->setId($id);
      if(!$option->getKeyname()) {
        $option->setKeyname(u($option->getTitle())->snake()->replace("_", "-")->toString());
      }
      $this->options[$option->getPosition()] = serialize($option);
    }
    ksort($this->options);
    return $this;
  }

  /**
   * @return array
   */
  public function getLayouts(): array
  {
    $layouts = array();
    foreach($this->layouts ?? array() as $layoutValues)
    {
      /** @var Layout $layoutObject */
      $layoutObject = unserialize($layoutValues);
      $layouts[$layoutObject->getId()] = $layoutObject;
    }
    return $layouts;
  }

  /**
   * @param string|null $layoutId
   *
   * @return Layout|null
   */
  public function getLayoutById(?string $layoutId): ?Layout
  {
    return AustralTools::getValueByKey($this->getLayouts(), $layoutId, null);
  }

  /**
   * @param array $layouts
   *
   * @return EditorComponent
   */
  public function setLayouts(array $layouts): EditorComponent
  {
    $this->layouts = array();
    /** @var Layout $layout */
    foreach ($layouts as $id => $layout)
    {
      $layout->setId($id);
      if(!$layout->getKeyname()) {
        $layout->setKeyname(u($layout->getTitle())->snake()->replace("_", "-")->toString());
      }
      $this->layouts[$layout->getPosition()] = serialize($layout);
    }
    ksort($this->layouts);
    return $this;
  }

  /**
   * @return bool
   */
  public function getIsEnabled(): bool
  {
    return $this->isEnabled;
  }

  /**
   * @param bool $isEnabled
   *
   * @return EditorComponent
   */
  public function setIsEnabled(bool $isEnabled): EditorComponent
  {
    $this->isEnabled = $isEnabled;
    return $this;
  }

  /**
   * @return bool
   */
  public function getIsContainer(): bool
  {
    return $this->isContainer;
  }

  /**
   * @param bool $isContainer
   *
   * @return EditorComponent
   */
  public function setIsContainer(bool $isContainer): EditorComponent
  {
    $this->isContainer = $isContainer;
    return $this;
  }

  /**
   * @return bool
   */
  public function getIsGuidelineView(): bool
  {
    return $this->isGuidelineView;
  }

  /**
   * @param bool $isGuidelineView
   *
   * @return $this
   */
  public function setIsGuidelineView(bool $isGuidelineView): EditorComponent
  {
    $this->isGuidelineView = $isGuidelineView;
    return $this;
  }

  /**
   * Get themes
   * @return array
   */
  public function getRestrictions(): array
  {
    $restrictions = array();
    foreach($this->restrictions as $restrictionValues)
    {
      /** @var Restriction $restrictionObject */
      $restrictionObject = unserialize($restrictionValues);
      $restrictions[$restrictionObject->getId()] = $restrictionObject;
    }
    return $restrictions;
  }

  /**
   * @param array $restrictions
   *
   * @return EditorComponent
   */
  public function setRestrictions(array $restrictions): EditorComponent
  {
    $this->restrictions = array();
    /** @var Restriction $restriction */
    foreach ($restrictions as $id => $restriction)
    {
      $restriction->setId($id);
      $this->restrictions[$restriction->getPosition()] = serialize($restriction);
    }
    ksort($this->restrictions);
    return $this;
  }

  /**
   * Get image
   * @return string|null
   */
  public function getImage(): ?string
  {
    return $this->image;
  }

  /**
   * Set image
   *
   * @param string|null $image
   *
   * @return $this
   */
  public function setImage(?string $image): EditorComponent
  {
    $this->image = $image;
    return $this;
  }

}