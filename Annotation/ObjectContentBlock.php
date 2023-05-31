<?php
/*
 * This file is part of the Austral EntityTranslate Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Austral\ContentBlockBundle\Annotation;

use Austral\EntityBundle\Annotation\AustralEntityAnnotation;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target({"CLASS"})
 */
final class ObjectContentBlock extends AustralEntityAnnotation
{

  /**
   * @var string|null
   */
  public ?string $name = null;

  /**
   * @var string|null
   */
  public ?string $orderBy = null;

  /**
   * @var string|null
   */
  public ?string $orderType = null;

  /**
   * @var string|null
   */
  public ?string $repositoryFunction = null;

  /**
   * @param string|null $name
   * @param string|null $orderBy
   * @param string|null $orderType
   * @param string|null $repositoryFunction
   */
  public function __construct(?string $name = null, ?string $orderBy = "root.id", ?string $orderType = "ASC", ?string $repositoryFunction = "") {
    $this->name = $name;
    $this->orderBy = $orderBy;
    $this->orderType = $orderType;
    $this->repositoryFunction = $repositoryFunction;
  }

}