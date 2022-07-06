<?php
/*
 * This file is part of the Austral ContentBlock Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ContentBlockBundle\Services;
use Austral\ContentBlockBundle\Entity\Component;
use Austral\ContentBlockBundle\Entity\Interfaces\EntityContentBlockInterface;
use Austral\EntityBundle\Entity\EntityInterface;
use Austral\ToolsBundle\AustralTools;
use Austral\ToolsBundle\Services\Debug;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Austral ContentBlockContainer service.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
Class ContentBlockContainer
{

  /**
   * @var string
   */
  protected string $debugContainer = "austral.content_block.services";

  /**
   * @var EntityManagerInterface
   */
  protected EntityManagerInterface $entityManager;

  /**
   * @var Request|null
   */
  protected ?Request $request;

  /**
   * @var string|null
   */
  protected ?string $currentLanguage;

  /**
   * @var array
   */
  protected array $entities = array();

  /**
   * @var array
   */
  protected array $entitiesWithReelName = array();

  /**
   * @var array
   */
  protected array $objects = array();

  /**
   * @var array
   */
  protected array $objectsByEntity = array();

  /**
   * @var array
   */
  protected array $objectsByEntityWithTranslate = array();

  /**
   * @var Debug
   */
  protected Debug $debug;

  /**
   * Page constructor.
   *
   * @param RequestStack $request
   * @param EntityManagerInterface $entityManager
   * @param Debug $debug
   */
  public function __construct(RequestStack $request, EntityManagerInterface $entityManager, Debug $debug)
  {
    $this->entityManager = $entityManager;
    $this->request = $request->getCurrentRequest();
    $this->currentLanguage = $this->request ? $this->request->attributes->get('language', $this->request->getLocale()) : null;
    $this->debug = $debug;
    $this->initEntity();
  }

  /**
   * @param bool $refresh
   *
   * @return $this
   */
  public function initEntity(bool $refresh = false): ContentBlockContainer
  {
    $this->debug->stopWatchStart("content_block_container.init_entity", $this->debugContainer);
    $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
    $entitiesWithReelName = array();
    if((count($this->entities) == 0) || $refresh)
    {
      $this->entities = array();
      foreach($metadata as $classMeta)
      {
        $className = $classMeta->getName();
        if(strpos($className,"Entity\Base") === false)
        {
          if(AustralTools::usedImplements($className, EntityContentBlockInterface::class))
          {
            $entityName = trim(str_replace($classMeta->namespace, "", $className), "\\");
            if(strpos($className,"Translate") === false)
            {
              if(!array_key_exists($entityName, $this->entities))
              {
                $this->entities[$entityName] = $className;
              }
              if(!array_key_exists($entityName, $entitiesWithReelName))
              {
                $entitiesWithReelName[$entityName] = $entityName;
              }
            }
            else
            {
              $entitiesWithReelName[str_replace("Translate", "", $entityName)] = $entityName;
            }
          }
        }
      }
    }
    $this->entitiesWithReelName = $entitiesWithReelName;
    $this->debug->stopWatchStop("content_block_container.init_entity");
    return $this;
  }

  /**
   * @param $currentLanguage
   *
   * @return $this
   */
  public function setCurrentLanguage($currentLanguage): ContentBlockContainer
  {
    $this->currentLanguage = $currentLanguage;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getCurrentLanguage(): ?string
  {
    return $this->currentLanguage;
  }

  /**
   * @return array
   */
  public function getEntities(): array
  {
    return $this->entities;
  }

  /**
   * @return array
   */
  public function getEntitiesWithReelName(): array
  {
    return $this->entitiesWithReelName;
  }

  /**
   * @return $this
   */
  public function reinitObject(): ContentBlockContainer
  {
    $this->objects = array();
    $this->objectsByEntity = array();
    $this->objectsByEntityWithTranslate = array();
    $this->initObjects();
    return $this;
  }

  /**
   * @return $this
   */
  protected function initObjects(): ContentBlockContainer
  {
    if(!$this->objects)
    {
      $this->objectsByEntity = array();
      foreach($this->entities as $entityName => $className)
      {
        $objects = $this->selectObjects($className);
        $this->objects = array_merge($this->objects, $objects);
        $this->objectsByEntity[$entityName] = array();
        $this->objectsByEntityWithTranslate[$entityName] = array();
        foreach($objects as $object)
        {
          $this->objectsByEntity[$entityName][$object->getId()] = $object;
          $this->objectsByEntityWithTranslate[$entityName][$object->getId()] = $object;
          if(AustralTools::usedImplements($object, "Austral\EntityTranslateBundle\Entity\Interfaces\EntityTranslateMasterInterface"))
          {
            foreach ($object->getTranslates() as $translate)
            {
              $this->objectsByEntityWithTranslate[$translate->getClassname()][$translate->getId()] = $translate;
            }
          }
        }
      }
    }
    return $this;
  }

  /**
   * @param $className
   *
   * @return array
   */
  protected function selectObjects($className): array
  {
    $queryBuilder = $this->entityManager->getRepository($className)->createQueryBuilder("root");
    if(method_exists($className, "getTranslateCurrent"))
    {
      $queryBuilder->leftJoin('root.translates', "translates")->addSelect('translates')
        ->where("translates.language = :language")
        ->setParameter("language", $this->getCurrentLanguage());
    }
    try {
      $objects = $queryBuilder->getQuery()->execute();
    } catch (\Doctrine\Orm\NoResultException $e) {
      $objects = array();
    }
    return $objects;
  }

  /**
   * @return array
   */
  public function getObjects(): array
  {
    $this->initObjects();
    return $this->objects;
  }

  /**
   * @param bool $withTranslate
   *
   * @return array
   */
  public function getObjectsByEntity(bool $withTranslate = false): array
  {
    $this->initObjects();
    if($withTranslate)
    {
      return $this->objectsByEntityWithTranslate;
    }
    return $this->objectsByEntity;
  }

  /**
   * @param EntityInterface|EntityContentBlockInterface $object
   */
  public function initComponentByObject(EntityInterface $object)
  {
    $this->debug->stopWatchStart("content_block_container.init_component_by_object", $this->debugContainer);
    if(AustralTools::usedImplements(get_class($object), "Austral\EntityTranslateBundle\Entity\Interfaces\EntityTranslateMasterInterface"))
    {
      $object = $object->getTranslateCurrent();
    }
    if($object && $object->getId())
    {
      $componentsByContainerName = array();
      $components = $this->entityManager->getRepository("App\Entity\Austral\ContentBlockBundle\Component")->selectComponentsByObjectIdAndClassname($object->getId(), $object->getClassname());
      /** @var Component $component */
      foreach($components as $key => $component)
      {
        $componentsByContainerName[$component->getObjectContainerName()][$key] = $component;
      }
      if($componentsByContainerName)
      {
        $object->setComponents($componentsByContainerName);
      }
    }
    $this->debug->stopWatchStop("content_block_container.init_component_by_object");
  }

}