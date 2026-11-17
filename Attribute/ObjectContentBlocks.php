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
 * Austral ObjectContentBlocks attribute.
 * Usage : #[ObjectContentBlocks([new ObjectContentBlock(name: "..."), ...])]
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class ObjectContentBlocks extends AustralEntityAttribute
{

  /**
   * @param array<ObjectContentBlock> $objectContentBlocks
   */
  public function __construct(public array $objectContentBlocks = [])
  {
  }

}
