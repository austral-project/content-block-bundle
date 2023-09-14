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
final Class ObjectContentBlocksMapping extends EntityClassMapping
{

  /**
   * @var array
   */
  protected array $objectContentBlocksMapping = array();

  /**
   * Constructor.
   */
  public function __construct(array $objectContentBlocksMapping = array())
  {
    $this->objectContentBlocksMapping = $objectContentBlocksMapping;
  }

  /**
   * getObjectContentBlocksMapping
   *
   * @return array
   */
  public function getObjectContentBlocksMapping(): array
  {
    return $this->objectContentBlocksMapping;
  }

  /**
   * getObjectContentBlockMapping
   *
   * @param string $name
   * @return ObjectContentBlockMapping|null
   */
  public function getObjectContentBlockMapping(string $name): ?ObjectContentBlockMapping
  {
    return array_key_exists($name, $this->objectContentBlocksMapping) ? $this->objectContentBlocksMapping[$name] : null;
  }

}
