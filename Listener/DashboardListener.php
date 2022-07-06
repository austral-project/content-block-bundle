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

use Austral\AdminBundle\Event\DashboardEvent;

use Austral\AdminBundle\Dashboard\Values as DashboardValues;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Austral DashboardListener Listener.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class DashboardListener
{

  /**
   * @var ContainerInterface
   */
  protected ContainerInterface $container;

  /**
   * @param ContainerInterface $container
   */
  public function __construct(ContainerInterface $container)
  {
    $this->container = $container;
  }


  /**
   * @param DashboardEvent $dashboardEvent
   *
   * @throws \Exception
   */
  public function dashboard(DashboardEvent $dashboardEvent)
  {
    $modules = $this->container->get('austral.admin.modules');

    if($modules->getModuleByKey("navigation")->isGranted("create"))
    {
      $dashboardActionAdminUser = new DashboardValues\Action("austral_action_navigation");
      $dashboardActionAdminUser->setEntitled("actions.create")
        ->setPosition(10)
        ->setPicto($modules->getModuleByKey("navigation")->getPicto())
        ->setIsTranslatableText(true)
        ->setUrl($modules->getModuleByKey("navigation")->generateUrl("create"))
        ->setTranslateParameters(array(
            "module_gender" =>  $modules->getModuleByKey("navigation")->translateGenre(),
            "module_name"   =>  $modules->getModuleByKey("navigation")->translateSingular()
          )
        );

      $dashboardEvent->getDashboardBlock()->getChild("austral_actions")
        ->addValue($dashboardActionAdminUser);
    }

  }

}