<?php
/*
 * This file is part of the Austral ContentBlock Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
namespace Austral\ContentBlockBundle\Configuration;

use Austral\ToolsBundle\Configuration\BaseConfiguration;

/**
 * Austral ContentBlock Configuration.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
Class ContentBlockConfiguration extends BaseConfiguration
{
  /**
   * @var int|null
   */
  protected ?int $niveauMax = null;

  /**
   * @var string|null
   */
  protected ?string $prefix = "content_block";


}