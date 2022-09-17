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

use Austral\ContentBlockBundle\Entity\Interfaces\ComponentInterface;
use Austral\ContentBlockBundle\Entity\Interfaces\ComponentValueInterface;
use Austral\ContentBlockBundle\Entity\Interfaces\ComponentValuesInterface;
use Austral\EntityBundle\Entity\Interfaces\ComponentsInterface;
use Austral\ContentBlockBundle\EntityManager\ComponentEntityManager;
use Austral\EntityBundle\Event\EntityManagerEvent;
use Austral\ToolsBundle\AustralTools;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\QueryException;

/**
 * Austral EntityManager Listener.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class EntityManagerListener
{

  /**
   * @var ComponentEntityManager
   */
  protected ComponentEntityManager $componentEntityManager;

  /**
   * @param ComponentEntityManager $componentEntityManager
   */
  public function __construct(ComponentEntityManager $componentEntityManager)
  {
    $this->componentEntityManager = $componentEntityManager;
  }

  /**
   * @param EntityManagerEvent $entityManagerEvent
   *
   * @throws QueryException
   */
  public function duplicate(EntityManagerEvent $entityManagerEvent)
  {

    if(AustralTools::usedImplements(get_class($entityManagerEvent->getObject()), ComponentsInterface::class))
    {
      $componentsBlocks = $this->componentEntityManager->selectComponentsByObjectIdAndClassname($entityManagerEvent->getSourceObject()->getId(), $entityManagerEvent->getSourceObject()->getClassname());
      /**
       * @var ComponentInterface $component
       */
      foreach($componentsBlocks as $component)
      {
        $duplicateComponent = $entityManagerEvent->getEntityManager()->duplicate($component);
        $duplicateComponent->setObjectId($entityManagerEvent->getObject()->getId());
        $entityManagerEvent->getObject()->addComponents($duplicateComponent->getObjectContainerName(), $duplicateComponent);
        $entityManagerEvent->getEntityManager()->update($duplicateComponent, false);
      }
    }

    if(AustralTools::usedImplements(get_class($entityManagerEvent->getObject()), ComponentInterface::class))
    {
      $entityManagerEvent->getObject()->setComponentValues(new ArrayCollection());

      /**
       * @var ComponentValueInterface $componentValue
       */
      foreach($entityManagerEvent->getSourceObject()->getComponentValues() as $componentValue)
      {
        $duplicateComponentValue = $entityManagerEvent->getEntityManager()->duplicate($componentValue);
        $duplicateComponentValue->setComponent($entityManagerEvent->getObject());
        $entityManagerEvent->getObject()->addComponentValues($duplicateComponentValue);
        $entityManagerEvent->getEntityManager()->update($duplicateComponentValue, false);
      }
    }

    if(AustralTools::usedImplements(get_class($entityManagerEvent->getObject()), ComponentValueInterface::class))
    {
      $entityManagerEvent->getObject()->setChildren(new ArrayCollection());
      /**
       * @var ComponentValuesInterface $componentValue
       */
      foreach($entityManagerEvent->getSourceObject()->getChildren() as $componentValues)
      {
        $duplicateComponentValues = $entityManagerEvent->getEntityManager()->duplicate($componentValues);
        $duplicateComponentValues->setParent($entityManagerEvent->getObject());
        $entityManagerEvent->getObject()->addChildren($duplicateComponentValues);
        $entityManagerEvent->getEntityManager()->update($duplicateComponentValues, false);
      }
    }

    if(AustralTools::usedImplements(get_class($entityManagerEvent->getObject()), ComponentValuesInterface::class))
    {
      $entityManagerEvent->getObject()->setChildren(new ArrayCollection());

      /**
       * @var ComponentValueInterface $componentValue
       */
      foreach($entityManagerEvent->getSourceObject()->getChildren() as $componentValue)
      {
        $duplicateComponentValue = $entityManagerEvent->getEntityManager()->duplicate($componentValue);
        $duplicateComponentValue->setParent($entityManagerEvent->getObject());
        $entityManagerEvent->getObject()->addChildren($duplicateComponentValue);
        $entityManagerEvent->getEntityManager()->update($duplicateComponentValue, false);
      }
    }
  }

}