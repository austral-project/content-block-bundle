<?php
/*
 * This file is part of the Austral ContentBlock Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Austral\ContentBlockBundle\Model\Editor;

use Austral\EntityBundle\Entity\Entity;
use Austral\EntityBundle\Entity\EntityInterface;
use Ramsey\Uuid\Uuid;

/**
 * Austral Size Model.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class Size extends Entity implements EntityInterface
{

  /**
   * @var string
   */
  protected $id;

  /**
   * @var string|null
   */
  protected ?string $keyname = null;

  /**
   * @var int|null
   */
  protected ?int $position = null;

  /**
   * @var string|null
   */
  protected ?string $title = null;

  /**
   * @var bool|null
   */
  protected ?bool $isDefault = false;

  /**
   * @var bool|null
   */
  protected ?bool $isEnabled = false;

  /**
   * Theme constructor.
   */
  public function __construct()
  {
    parent::__construct();
    $this->id = Uuid::uuid4()->toString();
  }

  public function __toString()
  {
    return $this->title;
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
  public function setId(string $id): self
  {
    $this->id = $id;
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
   * @return $this
   */
  public function setKeyname(?string $keyname): self
  {
    $this->keyname = $this->keynameGenerator($keyname);
    return $this;
  }


  /**
   * Get title
   * @return string|null
   */
  public function getTitle(): ?string
  {
    return $this->title;
  }

  /**
   * Set title
   *
   * @param string|null $title
   *
   * @return $this
   */
  public function setTitle(?string $title): self
  {
    $this->title = $title;
    return $this;
  }

  /**
   * @return int|null
   */
  public function getPosition(): ?int
  {
    return $this->position;
  }

  /**
   * @param int|null $position
   *
   * @return $this
   */
  public function setPosition(?int $position): self
  {
    $this->position = $position;
    return $this;
  }


  /**
   * getIsDefault
   *
   * @return bool|null
   */
  public function getIsDefault(): ?bool
  {
    return $this->isDefault;
  }

  /**
   * @param bool|null $isDefault
   * @return $this
   */
  public function setIsDefault(?bool $isDefault): self
  {
    $this->isDefault = $isDefault;
    return $this;
  }

  /**
   * getIsEnabled
   *
   * @return bool|null
   */
  public function getIsEnabled(): ?bool
  {
    return $this->isEnabled;
  }

  /**
   * @param bool|null $isEnabled
   * @return $this
   */
  public function setIsEnabled(?bool $isEnabled): self
  {
    $this->isEnabled = $isEnabled;
    return $this;
  }

}