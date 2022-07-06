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
use Exception;
use Ramsey\Uuid\Uuid;

/**
 * Austral Restriction Model.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class Restriction extends Entity implements EntityInterface
{

  /**
   * @var string
   */
  protected $id;

  /**
   * @var string|null
   */
  protected ?string $value = null;

  /**
   * @var string|null
   */
  protected ?string $condition = null;

  /**
   * @var string|null
   */
  protected ?string $containerName = "all";

  /**
   * @var int|null
   */
  protected ?int $position = null;


  /**
   * Theme constructor.
   *
   * @throws Exception
   */
  public function __construct()
  {
    parent::__construct();
    $this->id = Uuid::uuid4()->toString();
  }

  public function __toString()
  {
    return $this->value;
  }

  /**
   * @return string|null
   */
  public function getValue(): ?string
  {
    return $this->value;
  }

  /**
   * @param string|null $value
   *
   * @return Restriction
   */
  public function setValue(?string $value): Restriction
  {
    $this->value = $value;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getCondition(): ?string
  {
    return $this->condition;
  }

  /**
   * @param string|null $condition
   *
   * @return Restriction
   */
  public function setCondition(?string $condition): Restriction
  {
    $this->condition = $condition;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getContainerName(): string
  {
    return $this->containerName ? : "all";
  }

  /**
   * @param string|null $containerName
   *
   * @return Restriction
   */
  public function setContainerName(?string $containerName): Restriction
  {
    $this->containerName = $containerName;
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
   * @return Restriction
   */
  public function setPosition(?int $position): Restriction
  {
    $this->position = $position;
    return $this;
  }

}