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
 * Austral Theme Model.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class Theme extends Entity implements EntityInterface
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
   * @return Theme
   */
  public function setId(string $id): Theme
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
   * @return Theme
   */
  public function setKeyname(?string $keyname): Theme
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
   * @return Theme
   */
  public function setTitle(?string $title): Theme
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
   * @return Theme
   */
  public function setPosition(?int $position): Theme
  {
    $this->position = $position;
    return $this;
  }

}