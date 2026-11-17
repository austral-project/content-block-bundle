<?php
/*
 * This file is part of the Austral ContentBlock Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Austral\ContentBlockBundle\Attribute;

use Austral\EntityBundle\Attribute\AustralEntityAttribute;

/**
 * Austral ObjectContentBlock attribute.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class ObjectContentBlock extends AustralEntityAttribute
{

  /**
   * @param string|null $name
   * @param string|null $orderBy
   * @param string|null $orderType
   * @param string|null $repositoryFunction
   */
  public function __construct(
    public ?string $name = null,
    public ?string $orderBy = "root.id",
    public ?string $orderType = "ASC",
    public ?string $repositoryFunction = ""
  ) {
  }

}
