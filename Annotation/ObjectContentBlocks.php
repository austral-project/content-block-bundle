<?php
/*
 * This file is part of the Austral ContentBlock Bundle package.
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
final class ObjectContentBlocks extends AustralEntityAnnotation
{

  /**
   * @var array
   */
  public array $objectContentBlocks = array();

  /**
   * @param array $objectContentBlocks
   */
  public function __construct(array $objectContentBlocks = array()) {
    $this->objectContentBlocks = $objectContentBlocks;
  }

}