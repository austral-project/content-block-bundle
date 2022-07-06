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

use Austral\ContentBlockBundle\Entity\Interfaces\EntityContentBlockInterface;
use Austral\ContentBlockBundle\Entity\Interfaces\LibraryInterface;
use Austral\ContentBlockBundle\Entity\Interfaces\LibraryTranslateInterface;

use Austral\ContentBlockBundle\Entity\Traits\EntityComponentsTrait;
use Austral\EntityBundle\Entity\Entity;
use Austral\EntityBundle\Entity\EntityInterface;

use Austral\EntityFileBundle\Annotation as AustralFile;

use Austral\EntityBundle\Entity\Traits\EntityTimestampableTrait;
use Austral\EntityTranslateBundle\Entity\Interfaces\EntityTranslateChildInterface;
use Austral\EntityTranslateBundle\Entity\Interfaces\EntityTranslateMasterInterface;
use Austral\EntityTranslateBundle\Entity\Traits\EntityTranslateChildTrait;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

use Exception;

/**
 * Austral Library Entity.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @abstract
 * @ORM\MappedSuperclass
 */
abstract class LibraryTranslate extends Entity implements LibraryTranslateInterface, EntityInterface, EntityTranslateChildInterface, EntityContentBlockInterface
{

  use EntityTranslateChildTrait;
  use EntityTimestampableTrait;
  use EntityComponentsTrait;

  /**
   * @var string
   * @ORM\Column(name="id", type="string", length=40)
   * @ORM\Id
   */
  protected $id;

  /**
   * @var LibraryInterface|EntityTranslateMasterInterface
   *
   * @ORM\ManyToOne(targetEntity="\Austral\ContentBlockBundle\Entity\Interfaces\LibraryInterface", inversedBy="translates", cascade={"persist"})
   * @ORM\JoinColumn(name="master_id", referencedColumnName="id")
   */
  protected EntityTranslateMasterInterface $master;

  /**
   * @var string|null
   * @ORM\Column(name="name", type="string", length=255, nullable=false)
   */
  protected ?string $name = null;

  /**
   * @var boolean
   * @ORM\Column(name="is_enabled", type="boolean", nullable=true, options={"default" : true} )
   */
  protected bool $isEnabled = true;

  /**
   * @var string|null
   * @ORM\Column(name="image", type="string", length=255, nullable=true)
   * @AustralFile\UploadParameters(isRequired=false, configName="editor_component")
   */
  protected ?string $image = null;

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
   * __ToString
   *
   * @return string
   */
  public function __toString()
  {
    return $this->getName();
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
   * @return LibraryTranslate
   */
  public function setName(?string $name): LibraryTranslate
  {
    $this->name = $name;
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
   * @return LibraryTranslate
   */
  public function setIsEnabled(bool $isEnabled): LibraryTranslate
  {
    $this->isEnabled = $isEnabled;
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
  public function setImage(?string $image): LibraryTranslate
  {
    $this->image = $image;
    return $this;
  }


}