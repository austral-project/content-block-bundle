<?php
/*
 * This file is part of the Austral ContentBlock Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ContentBlockBundle\Mapping;

use Austral\EntityBundle\Mapping\EntityClassMapping;

/**
 * Austral ObjectContentBlockMapping.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
final Class ObjectContentBlockMapping extends EntityClassMapping
{

  /**
   * @var string|null
   */
  protected ?string $name = null;

  /**
   * @var string|null
   */
  protected ?string $orderBy = null;

  /**
   * @var string|null
   */
  protected ?string $orderType = null;

  /**
   * @var string|null
   */
  protected ?string $repositoryFunction = null;

  /**
   * Constructor.
   */
  public function __construct(?string $name = null, ?string $orderBy = "root.id", ?string $orderType = "ASC", ?string $repositoryFunction = "")
  {
    $this->name = $name;
    $this->orderBy = $orderBy;
    $this->orderType = $orderType;
    $this->repositoryFunction = $repositoryFunction;
  }

  /**
   * @return string|null
   */
  public function getName(): ?string
  {
    return $this->name;
  }

  /**
   * @return string|null
   */
  public function getOrderBy(): ?string
  {
    return $this->orderBy;
  }

  /**
   * @return string|null
   */
  public function getOrderType(): ?string
  {
    return $this->orderType;
  }

  /**
   * @return string|null
   */
  public function getRepositoryFunction(): ?string
  {
    return $this->repositoryFunction;
  }

}
