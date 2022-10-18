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
use Austral\EntityBundle\Entity\EntityInterface;
use Austral\EntityBundle\Entity\Interfaces\ComponentsInterface;
use Austral\ContentBlockBundle\Entity\Traits\EntityComponentsTrait;
use Austral\ContentBlockBundle\EntityManager\ComponentEntityManager;

use Austral\ToolsBundle\AustralTools;

use Doctrine\Common\EventArgs;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

/**
 * Austral Doctrine Listener.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class DoctrineListener implements EventSubscriber
{

  /**
   * @var mixed
   */
  protected $name;

  /**
   * @var ComponentEntityManager
   */
  protected ComponentEntityManager $componentEntityManager;


  /**
   * DoctrineListener constructor.
   */
  public function __construct(ComponentEntityManager $componentEntityManager)
  {
    $this->componentEntityManager = $componentEntityManager;
    $parts = explode('\\', $this->getNamespace());
    $this->name = end($parts);
  }

  /**
   * @return string[]
   */
  public function getSubscribedEvents(): array
  {
      return array(
        "prePersist",
        "preUpdate",
        "preFlush"
      );
  }

  /**
   * @param LifecycleEventArgs $args
   *
   * @return void
   * @throws ORMException
   * @throws OptimisticLockException
   */
  public function prePersist(LifecycleEventArgs $args)
  {
    $ea = $this->getEventAdapter($args);
    $object = $ea->getObject();
    if(AustralTools::usedClass(get_class($object), EntityComponentsTrait::class))
    {
      $this->updateComponents($object);
    }
  }

  /**
   * @param LifecycleEventArgs $args
   *
   * @return void
   * @throws ORMException
   * @throws OptimisticLockException
   */
  public function preUpdate(LifecycleEventArgs $args)
  {
    $ea = $this->getEventAdapter($args);
    $object = $ea->getObject();
    if(AustralTools::usedClass(get_class($object), EntityComponentsTrait::class))
    {
      $this->updateComponents($object);
    }
  }

  /**
   * @param ComponentsInterface $object
   *
   * @return void
   */
  protected function updateComponents(ComponentsInterface $object)
  {
    /** @var ComponentInterface|EntityInterface $component */
    foreach($object->getComponents() as $componentsByContainer)
    {
      foreach ($componentsByContainer as $component)
      {
        $this->componentEntityManager->update($component, false);
      }
    }
    /** @var ComponentInterface|EntityInterface $component */
    foreach($object->getComponentsRemoved() as $component)
    {
      $this->componentEntityManager->delete($component, false);
    }
  }


  /**
   * @param PreFlushEventArgs $args
   *
   * @return void
   */
  public function preFlush(PreFlushEventArgs $args)
  {
  }

  /**
   * @param EventArgs $args
   *
   * @return EventArgs
   */
  protected function getEventAdapter(EventArgs $args)
  {
    return $args;
  }

  /**
   * @return string
   */
  protected function getNamespace()
  {
    return __NAMESPACE__;
  }
}