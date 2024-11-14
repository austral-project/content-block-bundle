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
use Austral\ContentBlockBundle\Entity\Interfaces\EditorComponentInterface;
use Austral\ContentBlockBundle\Entity\Interfaces\GuidelineInterface;
use Austral\ContentBlockBundle\Entity\Traits\EntityComponentsTrait;
use Austral\EntityBundle\Entity\Interfaces\ComponentsInterface;

use Austral\EntityBundle\Entity\Entity;
use Austral\EntityBundle\Entity\EntityInterface;
use Austral\EntityBundle\Entity\Traits\EntityTimestampableTrait;

use Austral\HttpBundle\Entity\Traits\FilterByDomainTrait;
use Austral\HttpBundle\Annotation\DomainFilter;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

use Exception;

/**
 * Austral Guideline Entity.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @abstract
 * @ORM\MappedSuperclass
 * @DomainFilter(forAllDomainEnabled=true, autoDomainId=true)
 */
abstract class Guideline extends Entity implements GuidelineInterface,
  EntityInterface,
  ComponentsInterface
{

  use EntityTimestampableTrait;
  use FilterByDomainTrait;
  use EntityComponentsTrait;

  /**
   * @var string
   * @ORM\Column(name="id", type="string", length=40)
   * @ORM\Id
   */
  protected $id;

  /**
   * @var EditorComponentInterface|null
   * @ORM\ManyToOne(targetEntity="Austral\ContentBlockBundle\Entity\Interfaces\EditorComponentInterface", inversedBy="guidelines")
   * @ORM\JoinColumn(name="editor_component_id", referencedColumnName="id", onDelete="CASCADE")
   */
  protected ?EditorComponentInterface $editorComponent = null;

  /**
   * @var string|null
   * @ORM\Column(name="name", type="string", length=255, nullable=true)
   */
  protected ?string $name = null;

  /**
   * @var string|null
   * @ORM\Column(name="category", type="string", length=255, nullable=false, options={"default" : "default"})
   */
  protected ?string $category = null;

  /**
   * @var string|null
   * @ORM\Column(name="keyname", type="string", length=255, nullable=true)
   */
  protected ?string $keyname = null;

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
   * @return Guideline
   */
  public function setId(string $id): Guideline
  {
    $this->id = $id;
    return $this;
  }

  /**
   * getEditorComponent
   *
   * @return EditorComponentInterface|null
   */
  public function getEditorComponent(): ?EditorComponentInterface
  {
    return $this->editorComponent;
  }

  /**
   * @param EditorComponentInterface $editorComponent
   * @return $this
   */
  public function setEditorComponent(EditorComponentInterface $editorComponent): Guideline
  {
    $this->editorComponent = $editorComponent;
    return $this;
  }

  /**
   * getName
   *
   * @return string|null
   */
  public function getName(): ?string
  {
    return $this->name;
  }

  /**
   * @param string|null $name
   * @return $this
   */
  public function setName(?string $name): Guideline
  {
    $this->name = $name;
    return $this;
  }

  /**
   * getCategory
   *
   * @return string|null
   */
  public function getCategory(): ?string
  {
    return $this->category;
  }

  /**
   * @param string|null $category
   * @return $this
   */
  public function setCategory(?string $category): Guideline
  {
    $this->category = $category;
    return $this;
  }

  /**
   * getKeyname
   *
   * @return string|null
   */
  public function getKeyname(): ?string
  {
    return $this->keyname;
  }

  /**
   * @param string|null $keyname
   * @return $this
   */
  public function setKeyname(?string $keyname): Guideline
  {
    $this->keyname = $keyname;
    return $this;
  }

  /**
   * getPosition
   *
   * @return int
   */
  public function getPosition(): int
  {
    return $this->position;
  }

  /**
   * @param int $position
   * @return $this
   */
  public function setPosition(int $position): Guideline
  {
    $this->position = $position;
    return $this;
  }

}