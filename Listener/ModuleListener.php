<?php
/*
 * This file is part of the Austral ContentBlock Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ContentBlockBundle\Listener;

use Austral\AdminBundle\Event\ModuleEvent;

/**
 * Austral ModuleListener Listener.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class ModuleListener
{

  /**
   */
  public function __construct()
  {
  }


  /**
   * @param ModuleEvent $moduleEvent
   *
   * @throws \Exception
   */
  public function moduleAdd(ModuleEvent $moduleEvent)
  {
    if($moduleEvent->getModule()->getModuleKey() === "navigation")
    {
      $moduleEvent->getModule()->getQueryBuilder()
        ->where("root.isNavigationMenu = :isNavigationMenu")
        ->setParameter("isNavigationMenu", true);
    }
    elseif($moduleEvent->getModule()->getModuleKey() === "library")
    {
      $moduleEvent->getModule()->getQueryBuilder()
        ->where("root.isNavigationMenu = :isNavigationMenu")
        ->setParameter("isNavigationMenu", false);
    }
  }

}