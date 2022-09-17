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

use Austral\ContentBlockBundle\Entity\Interfaces\LibraryInterface;
use Austral\EntityBundle\Entity\Interfaces\ComponentsInterface;

use Austral\ContentBlockBundle\Model\Editor\Restriction;
use Austral\EntityBundle\Entity\Entity;
use Austral\EntityBundle\Entity\EntityInterface;
use Austral\EntityBundle\Entity\Interfaces\FilterByDomainInterface;
use Austral\EntityBundle\Entity\Traits\EntityTimestampableTrait;

use Austral\EntityBundle\Entity\Interfaces\FileInterface;
use Austral\EntityFileBundle\Entity\Traits\EntityFileTrait;
use Austral\EntityBundle\Entity\Interfaces\TranslateMasterInterface;
use Austral\EntityTranslateBundle\Entity\Traits\EntityTranslateMasterComponentsTrait;
use Austral\EntityTranslateBundle\Entity\Traits\EntityTranslateMasterTrait;
use Austral\EntityTranslateBundle\Annotation\Translate;

use Austral\HttpBundle\Entity\Traits\FilterByDomainTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

use Exception;

/**
 * Austral Library Entity.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @abstract
 * @ORM\MappedSuperclass
 * @Translate(relationClass="Austral\ContentBlockBundle\Entity\Interfaces\LibraryTranslateInterface")
 */
abstract class Library extends Entity implements
  LibraryInterface,
  EntityInterface,
  TranslateMasterInterface,
  ComponentsInterface,
  FileInterface,
  FilterByDomainInterface
{

  use EntityTimestampableTrait;
  use EntityTranslateMasterTrait;
  use EntityTranslateMasterComponentsTrait;
  use EntityFileTrait;
  use FilterByDomainTrait;

  /**
   * @var string
   * @ORM\Column(name="id", type="string", length=40)
   * @ORM\Id
   */
  protected $id;

  /**
   * @ORM\OneToMany(targetEntity="Austral\ContentBlockBundle\Entity\Interfaces\ComponentInterface", mappedBy="library", cascade={"persist", "remove"})
   */
  protected Collection $componentsLibrary;

  /**
   * @var string|null
   * @ORM\Column(name="keyname", type="string", length=255, nullable=false)
   */
  protected ?string $keyname = null;
  
  /**
   * @var boolean
   * @ORM\Column(name="accessible_in_content", type="boolean", nullable=false, options={"default": false})
   */
  protected bool $accessibleInContent = false;

  /**
   * @var boolean
   * @ORM\Column(name="is_navigation_menu", type="boolean", nullable=false, options={"default": false})
   */
  protected bool $isNavigationMenu = false;

  /**
   * @var string|null
   * @ORM\Column(name="template_path", type="string", length=255, nullable=true)
   */
  protected ?string $templatePath = null;

  /**
   * @ORM\OneToMany(targetEntity="\Austral\ContentBlockBundle\Entity\Interfaces\LibraryTranslateInterface", mappedBy="master", cascade={"persist", "remove"})
   */
  protected Collection $translates;

  /**
   * @var array|null
   * @ORM\Column(name="restrictions", type="json", nullable=true )
   */
  protected ?array $restrictions = array();

  /**
   * @var string|null
   * @ORM\Column(name="css_class", type="string", length=255, nullable=true)
   */
  protected ?string $cssClass;

  /**
   * Constructor
   * @throws Exception
   */
  public function __construct()
  {
    parent::__construct();
    $this->id = Uuid::uuid4()->toString();
    $this->translates = new ArrayCollection();
    $this->componentsLibrary = new ArrayCollection();
    $this->cssClass=null;
  }

  /**
   * @return array
   */
  public function getFieldsToDelete(): array
  {
    return array("image");
  }

  /**
   * @return int|string|null
   */
  public function __toString()
  {
    return $this->getTranslateCurrent() ? $this->getTranslateCurrent()->__toString() : "";
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
   * @return Library
   */
  public function setId(string $id): Library
  {
    $this->id = $id;
    return $this;
  }

  /**
   * @return Collection
   */
  public function getComponentsLibrary(): Collection
  {
    return $this->componentsLibrary;
  }

  /**
   * @param Collection $componentsLibrary
   *
   * @return Library
   */
  public function setComponentsLibrary(Collection $componentsLibrary): Library
  {
    $this->componentsLibrary = $componentsLibrary;
    return $this;
  }

  /**
   * @return string|null
   * @throws Exception
   */
  public function getName(): ?string
  {
    return $this->getTranslateCurrent() ? $this->getTranslateCurrent()->getName() : null;
  }

  /**
   * @param string|null $name
   *
   * @return Library
   * @throws Exception
   */
  public function setName(?string $name): Library
  {
    $this->getTranslateCurrent()->setName($name);
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
   * @return Library
   */
  public function setKeyname(?string $keyname): Library
  {
    $this->keyname = $this->keynameGenerator($keyname);
    return $this;
  }

  /**
   * @return bool
   */
  public function getIsNavigationMenu(): bool
  {
    return $this->isNavigationMenu;
  }

  /**
   * @param bool $isNavigationMenu
   *
   * @return $this
   */
  public function setIsNavigationMenu(bool $isNavigationMenu): Library
  {
    $this->isNavigationMenu = $isNavigationMenu;
    return $this;
  }

  /**
   * @return bool
   */
  public function getAccessibleInContent(): bool
  {
    return $this->accessibleInContent;
  }

  /**
   * @param bool $accessibleInContent
   *
   * @return $this
   */
  public function setAccessibleInContent(bool $accessibleInContent): Library
  {
    $this->accessibleInContent = $accessibleInContent;
    return $this;
  }

  /**
   * @return bool
   * @throws Exception
   */
  public function getIsEnabled(): bool
  {
    return $this->getTranslateCurrent() ? $this->getTranslateCurrent()->getIsEnabled() : false;
  }

  /**
   * @param bool $isEnabled
   *
   * @return Library
   * @throws Exception
   */
  public function setIsEnabled(bool $isEnabled): Library
  {
    $this->getTranslateCurrent()->setIsEnabled($isEnabled);
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
   * @param string|null $templatePath
   *
   * @return $this
   */
  public function setTemplatePath(?string $templatePath): Library
  {
    $this->templatePath = $templatePath;
    return $this;
  }

  /**
   * Get themes
   * @return array
   */
  public function getRestrictions(): array
  {
    $restrictions = array();
    foreach($this->restrictions ? : array() as $restrictionValues)
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
   * @return $this
   */
  public function setRestrictions(array $restrictions): Library
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
   * @return string|null
   */
  public function getCssClass(): ?string
  {
    return $this->cssClass;
  }

  /**
   * @param string|null $cssClass
   *
   * @return Library
   */
  public function setCssClass(?string $cssClass): Library
  {
    $this->cssClass = $cssClass;
    return $this;
  }

  /**
   * @return string
   * @throws Exception
   */
  public function getImage(): ?string
  {
    return $this->getTranslateCurrent() ? $this->getTranslateCurrent()->getImage() : null;
  }

  /**
   * @param $image
   *
   * @return $this
   * @throws Exception
   */
  public function setImage($image): Library
  {
    $this->getTranslateCurrent()->setImage($image);
    return $this;
  }

}