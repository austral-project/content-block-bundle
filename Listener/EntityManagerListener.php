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

use Austral\ContentBlockBundle\Annotation\ObjectContentBlock;
use Austral\ContentBlockBundle\Annotation\ObjectContentBlocks;
use Austral\ContentBlockBundle\Entity\Interfaces\ComponentInterface;
use Austral\ContentBlockBundle\Entity\Interfaces\ComponentValueInterface;
use Austral\ContentBlockBundle\Entity\Interfaces\ComponentValuesInterface;
use Austral\ContentBlockBundle\Entity\Interfaces\EditorComponentInterface;
use Austral\ContentBlockBundle\Entity\Interfaces\EditorComponentTypeInterface;
use Austral\ContentBlockBundle\Mapping\ObjectContentBlockMapping;
use Austral\ContentBlockBundle\Mapping\ObjectContentBlocksMapping;
use Austral\EntityBundle\Entity\Interfaces\ComponentsInterface;
use Austral\ContentBlockBundle\EntityManager\ComponentEntityManager;
use Austral\EntityBundle\EntityAnnotation\EntityAnnotations;
use Austral\EntityBundle\Event\EntityManagerEvent;
use Austral\EntityBundle\Event\EntityMappingEvent;
use Austral\EntityBundle\Mapping\EntityMapping;
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

    if(AustralTools::usedImplements(get_class($entityManagerEvent->getObject()), EditorComponentInterface::class))
    {
      /** @var EditorComponentInterface $editorComponentSource */
      $editorComponentSource = $entityManagerEvent->getSourceObject();

      /** @var EditorComponentInterface $editorComponentNew */
      $editorComponentNew = $entityManagerEvent->getObject();

      $editorComponentNew->duplicateInit();
      $editorComponentTypeSourceIdsToNewIds = array();

      /** @var EditorComponentTypeInterface $editorComponentType */
      foreach ($editorComponentSource->getEditorComponentTypes() as $editorComponentType)
      {
        /** @var EditorComponentTypeInterface $duplicateEditorComponentType */
        $duplicateEditorComponentType = $entityManagerEvent->getEntityManager()->duplicate($editorComponentType);
        $editorComponentNew->addEditorComponentType($duplicateEditorComponentType);
        $editorComponentTypeSourceIdsToNewIds[$editorComponentType->getId()] = $duplicateEditorComponentType->getId();
      }

      /** @var EditorComponentTypeInterface $editorComponentType */
      foreach ($editorComponentNew->getEditorComponentTypes() as $editorComponentType)
      {
        if($editorComponentType->getParentId())
        {
          $editorComponentType->setParentId($editorComponentTypeSourceIdsToNewIds[$editorComponentType->getParentId()]);
        }
      }
    }

  }

  /**
   * @param EntityMappingEvent $entityAnnotationEvent
   *
   * @return void
   * @throws \Exception
   */
  public function mapping(EntityMappingEvent $entityAnnotationEvent)
  {
    $initialiseEntitesAnnotations = $entityAnnotationEvent->getEntitiesAnnotations();
    /**
     * @var EntityAnnotations $entityAnnotation
     */
    foreach($initialiseEntitesAnnotations->all() as $entityAnnotation)
    {

      if(array_key_exists(ObjectContentBlocks::class, $entityAnnotation->getClassAnnotations()))
      {
        if(!$entityMapping = $entityAnnotationEvent->getMapping()->getEntityMapping($entityAnnotation->getClassname()))
        {
          $entityMapping = new EntityMapping($entityAnnotation->getClassname(), $entityAnnotation->getSlugger());
        }

        $objectContentBlocksMapping = array();
        /** @var ObjectContentBlock $objectContentBlock */
        foreach($entityAnnotation->getClassAnnotations()[ObjectContentBlocks::class]->objectContentBlocks as $objectContentBlock)
        {
          $objectContentBlockMapping = new ObjectContentBlockMapping(
            $objectContentBlock->name,
            $objectContentBlock->orderBy,
            $objectContentBlock->orderType,
            $objectContentBlock->repositoryFunction,
          );
          $objectContentBlockMapping->setEntityMapping($entityMapping);
          $objectContentBlocksMapping[$objectContentBlock->name] = $objectContentBlockMapping;
        }
        $objectContentBlocksMapping = new ObjectContentBlocksMapping($objectContentBlocksMapping);
        $entityMapping->addEntityClassMapping($objectContentBlocksMapping);
        $entityAnnotationEvent->getMapping()->addEntityMapping($entityAnnotation->getClassname(), $entityMapping);
      }
      elseif(array_key_exists(ObjectContentBlock::class, $entityAnnotation->getClassAnnotations()))
      {
        if(!$entityMapping = $entityAnnotationEvent->getMapping()->getEntityMapping($entityAnnotation->getClassname()))
        {
          $entityMapping = new EntityMapping($entityAnnotation->getClassname(), $entityAnnotation->getSlugger());
        }
        $objectContentBlockMapping = new ObjectContentBlockMapping(
          $entityAnnotation->getClassAnnotations()[ObjectContentBlock::class]->name,
          $entityAnnotation->getClassAnnotations()[ObjectContentBlock::class]->orderBy,
          $entityAnnotation->getClassAnnotations()[ObjectContentBlock::class]->orderType,
          $entityAnnotation->getClassAnnotations()[ObjectContentBlock::class]->repositoryFunction,
        );
        $entityMapping->addEntityClassMapping($objectContentBlockMapping);
        $entityAnnotationEvent->getMapping()->addEntityMapping($entityAnnotation->getClassname(), $entityMapping);
      }
    }
  }

}