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

use App\Entity\Austral\ContentBlockBundle\Component;
use App\Entity\Austral\ContentBlockBundle\ComponentValue;
use App\Entity\Austral\ContentBlockBundle\ComponentValues;
use App\Entity\Austral\ContentBlockBundle\EditorComponentType;
use Austral\AdminBundle\Module\Modules;
use Austral\ContentBlockBundle\Configuration\ContentBlockConfiguration;
use Austral\ContentBlockBundle\Entity\EditorComponent;
use Austral\ContentBlockBundle\Entity\Interfaces\ComponentInterface;
use Austral\ContentBlockBundle\Entity\Interfaces\ComponentValueInterface;
use Austral\ContentBlockBundle\Entity\Interfaces\ComponentValuesInterface;
use Austral\ContentBlockBundle\Entity\Interfaces\EditorComponentInterface;
use Austral\ContentBlockBundle\Entity\Interfaces\EditorComponentTypeInterface;
use Austral\EntityBundle\Entity\Interfaces\ComponentsInterface;
use Austral\ContentBlockBundle\Entity\Interfaces\LibraryInterface;
use Austral\ContentBlockBundle\Entity\Traits\EntityComponentsTrait;
use Austral\ContentBlockBundle\EntityManager\ComponentEntityManager;
use Austral\ContentBlockBundle\EntityManager\EditorComponentEntityManager;
use Austral\ContentBlockBundle\EntityManager\LibraryEntityManager;
use Austral\ContentBlockBundle\Field\ContentBlockField;

use Austral\ContentBlockBundle\Form\Type\ComponentFormType;
use Austral\ContentBlockBundle\Model\Editor\Layout;
use Austral\ContentBlockBundle\Model\Editor\Restriction;
use Austral\ContentBlockBundle\Model\Editor\Theme;

use Austral\EntityBundle\Mapping\EntityMapping;
use Austral\EntityBundle\Mapping\Mapping;
use Austral\EntityFileBundle\File\Mapping\FieldFileMapping;
use Austral\FormBundle\Mapper\Base\MapperElementInterface;
use Austral\ToolsBundle\AustralTools;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Constraints as Constraints;
use Austral\FormBundle\Field as Field;

use Austral\ContentBlockBundle\Form\Type\ComponentValueFormType;
use Austral\EntityBundle\Entity\EntityInterface;
use Austral\EntityFileBundle\Configuration\UploadsConfiguration;
use Austral\EntityFileBundle\Exception\FormUploadException;
use Austral\EntityFileBundle\File\Link\Generator;
use Austral\EntityFileBundle\File\Upload\FileUploader;

use Austral\FormBundle\Event\FormEvent;

use Austral\FormBundle\Event\FormFieldEvent;

use Austral\FormBundle\Mapper\FormMapper;
use Austral\FormBundle\Mapper\GroupFields;

use \Exception;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Austral FormListener Listener.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class FormListener
{

  /**
   * @var ContainerInterface
   */
  protected ContainerInterface $container;

  /**
   * @var Request|null
   */
  protected ?Request $request;

  /**
   * @var FileUploader
   */
  protected FileUploader $fileUploader;

  /**
   * @var UploadsConfiguration
   */
  protected UploadsConfiguration $uploadsConfiguration;

  /**
   * @var ContentBlockConfiguration
   */
  protected ContentBlockConfiguration $contentBlockConfiguration;

  /**
   * @var Generator
   */
  protected Generator $fileLinkGenerator;

  /**
   * @var Mapping
   */
  protected Mapping $mapping;

  /**
   * @var array
   */
  protected array $libraries;

  /**
   * FormListener constructor.
   *
   * @param ContainerInterface $container
   * @param RequestStack $requestStack
   * @param ContentBlockConfiguration $contentBlockConfiguration
   * @param FileUploader $fileUploader
   * @param UploadsConfiguration $uploadsConfiguration
   * @param Generator $fileLinkGenerator
   * @param Mapping $mapping
   */
  public function __construct(ContainerInterface $container,
    RequestStack $requestStack,
    ContentBlockConfiguration $contentBlockConfiguration,
    FileUploader $fileUploader,
    UploadsConfiguration $uploadsConfiguration,
    Generator $fileLinkGenerator,
    Mapping $mapping
  )
  {
    $this->container = $container;
    $this->request = $requestStack->getCurrentRequest();
    $this->fileUploader = $fileUploader;
    $this->uploadsConfiguration = $uploadsConfiguration;
    $this->fileLinkGenerator = $fileLinkGenerator;
    $this->contentBlockConfiguration = $contentBlockConfiguration;
    $this->mapping = $mapping;
  }

  /**
   * @param FormEvent $formEvent
   *
   * @throws Exception
   */
  public function validate(FormEvent $formEvent)
  {
    $this->loopLibraryDetected($formEvent);
    try {
      if($formEvent->getFormMapper()->getObject() instanceof ComponentsInterface)
      {
        /** @var EntityComponentsTrait $object */
        $object = $formEvent->getFormMapper()->getObject();
        foreach($formEvent->getForm() as $child)
        {
          if($child->getConfig()->getOption("entry_type") === ComponentFormType::class)
          {
            $this->validateUploadFiles($child, $object->getComponents());
          }
        }
      }
    } catch(Exception $e) {
      $formEvent->getFormMapper()->setFormStatus("exception");
      throw new FormUploadException($e->getMessage());
    }

  }

  /**
   * @param FormEvent $formEvent
   *
   * @return void
   * @throws ContainerExceptionInterface
   * @throws NotFoundExceptionInterface
   */
  protected function loopLibraryDetected(FormEvent $formEvent)
  {
    if($formEvent->getFormMapper()->getObject() instanceof LibraryInterface)
    {
      /** @var LibraryInterface|EntityComponentsTrait $libraryCurrent */
      $libraryCurrent = $formEvent->getFormMapper()->getObject();

      $this->libraries = $this->container->get('austral.entity_manager.library')->selectAllIndexBy();

      if($componentLoop = $this->infiniteLoopDetected($libraryCurrent, $libraryCurrent))
      {
        $formEvent->getForm()
          ->get("components_library-{$componentLoop->getLibrary()->getKeyname()}")
          ->get($componentLoop->getId())
          ->addError(new FormError("errors.component.loopDetected"));
        $formEvent->getFormMapper()->setFormStatus("error");
      }
    }
  }

  /**
   * @param LibraryInterface|EntityComponentsTrait $libraryCurrent
   * @param LibraryInterface|EntityComponentsTrait $library
   * @param $componentObjectCurrent
   *
   * @return mixed
   * @throws ContainerExceptionInterface
   * @throws NotFoundExceptionInterface
   * @throws Exception
   */
  protected function infiniteLoopDetected(LibraryInterface $libraryCurrent, LibraryInterface $library, $componentObjectCurrent = null)
  {
    $loop = false;
    foreach($library->getComponents() as $componentObjects)
    {
      foreach ($componentObjects as $componentObject)
      {
        if($componentObject->getComponentType() == "library") {
    
          /** @var LibraryInterface|EntityComponentsTrait $libraryComponent */
          $libraryComponent = $componentObject->getLibrary();
          if($libraryCurrent->getId() == $libraryComponent->getId())
          {
            $loop = $componentObjectCurrent;
          }
    
          /** @var LibraryInterface|EntityComponentsTrait $librarySelect */
          if($librarySelect = AustralTools::getValueByKey($this->libraries, $libraryComponent->getKeyname()))
          {
            if(!$librarySelect->getComponents())
            {
              $this->container->get("austral.content_block.content_block_container")->initComponentByObject($librarySelect);
              $this->libraries[$libraryComponent->getKeyname()] = $librarySelect;
            }
            if(!$loop)
            {
              $loop = $this->infiniteLoopDetected($libraryCurrent, $librarySelect, $componentObjectCurrent ?? $componentObject);
            }
          }
        }
      }
    }
    return $loop;
  }

  /**
   * @param Form $form
   * @param array $objects
   *
   * @throws ReflectionException
   */
  protected function validateUploadFiles(Form $form, array $objects)
  {
    foreach($form as $child)
    {
      if(!$child->getData() instanceof ComponentValueInterface)
      {
        if($child->count())
        {
          $this->validateUploadFiles($child, $objects);
        }
      }
      else
      {
        /** @var ComponentValue $componentValue */
        $componentValue = $child->getData();
        $editorComponentType = $componentValue->getEditorComponentType();
        if(($editorComponentType->getType() === "image" || $editorComponentType->getType() === "file") && $editorComponentType->getParameterByKey("isRequired"))
        {
          $this->fileUploader->validateRequiredFiles($child, $componentValue);
        }
      }
    }
  }

  /**
   * @param EditorComponentTypeInterface $editorComponentType
   * @param string $fieldname
   *
   * @return void
   */
  protected function addFieldFileMapping(EditorComponentTypeInterface $editorComponentType, string $fieldname)
  {
    $baseEntityMapping = $this->mapping->getEntityMapping(ComponentValue::class);
    $fieldFileMapping = clone $baseEntityMapping->getFieldsMappingByFieldname(FieldFileMapping::class, $fieldname);
    if(!$entityMapping = $this->mapping->getEntityMapping(ComponentValue::class."_".$editorComponentType->getEditorComponent()->getKeyname()))
    {
      $entityMapping = new EntityMapping(ComponentValue::class, $baseEntityMapping->slugger);
    }
    $entityMapping->addFieldMapping($fieldname, $fieldFileMapping);
    $this->mapping->addEntityMapping(ComponentValue::class."_".$editorComponentType->getEditorComponent()->getKeyname(), $entityMapping);
  }

  /**
   * @param string $path
   * @param string $entitySlugger
   *
   * @return string
   */
  protected function replacePath(string $path, string $entitySlugger): string
  {
    preg_match_all("|%(\S+)%|iuU", $path, $matches, PREG_SET_ORDER);
    foreach($matches as $match)
    {
      $path = str_replace($match[0], $this->container->getParameter($match[1]), $path);
    }
    return str_replace("@entity_slugger_case@", $entitySlugger, $path);
  }

  /**
   * @param FormEvent $formEvent
   *
   * @throws ReflectionException
   */
  public function uploads(FormEvent $formEvent)
  {
    try {
      if($formEvent->getFormMapper()->getObject() instanceof ComponentsInterface)
      {
        /** @var EntityComponentsTrait $object */
        $object = $formEvent->getFormMapper()->getObject();
        $this->uploadFileByObjects($formEvent, $object->getComponents());
      }
    } catch(Exception $e) {
      $formEvent->getFormMapper()->setFormStatus("exception");
      throw new FormUploadException($e->getMessage());
    }
  }

  /**
   * @param FormEvent $formEvent
   * @param array $objects
   *
   * @throws ReflectionException
   * @throws Exception
   */
  protected function uploadFileByObjects(FormEvent $formEvent, array $objects)
  {
    foreach($objects as $object)
    {
      if($object instanceof ComponentInterface)
      {
        $this->uploadFileByObjects($formEvent, $object->getComponentValues()->toArray());
      }
      elseif($object instanceof ComponentValuesInterface)
      {
        $this->uploadFileByObjects($formEvent, $object->getChildren()->toArray());
      }
      elseif($object instanceof ComponentValueInterface)
      {
        if($children = $object->getChildren()->toArray())
        {
          $this->uploadFileByObjects($formEvent, $children);
        }
        else
        {
          $editorComponentType = $object->getEditorComponentType();
          if(($editorComponentType->getType() === "image" || $editorComponentType->getType() === "file" || $editorComponentType->getCanHasLink()))
          {
            $fieldFileMappingName = $editorComponentType->getEditorComponent()->getKeyname() ?
              "{$object->getClassnameForMapping()}_{$editorComponentType->getEditorComponent()->getKeyname()}" :
              $object->getClassnameForMapping();


            $fieldFileMapping = $this->mapping->getFieldsMappingByFieldname($fieldFileMappingName, FieldFileMapping::class, $editorComponentType->getCanHasLink() ? "file" : $editorComponentType->getType());
            foreach ($object->getUploadFiles() as $uploadedFile)
            {
              if($uploadedFile instanceof UploadedFile)
              {
                $this->fileUploader->uploadFile($fieldFileMapping, $object, $uploadedFile);
              }
            }
            if(($deleteFieldnames = $object->getDeleteFiles()) && !$editorComponentType->getParameterByKey("isRequired"))
            {
              foreach($deleteFieldnames as $bool)
              {
                if($bool)
                {
                  $this->fileUploader->deleteFileByFieldname($fieldFileMapping, $object)->deleteThumbnails($fieldFileMapping, $object);
                }
              }
            }
          }
        }
      }
      elseif(is_array($object))
      {
        $this->uploadFileByObjects($formEvent, $object);
      }
    }
  }

  /**
   * @param FormFieldEvent $formFieldEvent
   *
   * @return void
   * @throws Exception
   * @throws ContainerExceptionInterface
   * @throws NotFoundExceptionInterface
   * @throws QueryException
   * @throws ReflectionException
   */
  public function fieldConfiguration(FormFieldEvent $formFieldEvent)
  {
    /** @var EntityComponentsTrait|EntityInterface $object */
    $object = $formFieldEvent->getFormMapper()->getObject();
    $field = $formFieldEvent->getField();
    if($field instanceof ContentBlockField and $object instanceof ComponentsInterface)
    {
      $collectionFormsChildren = array();

      /** @var EditorComponentEntityManager $blockEditorsManager */
      $editorComponentManager = $this->container->get("austral.entity_manager.editor_component");
      $editorComponents = $editorComponentManager->selectAllEnabled("name");


      if(($hydrates = $field->getHydrateAuto()))
      {
        if($formFieldEvent->getFormMapper()->getRequestMethod() !== "POST")
        {
          $componentsExist = array_values($object->getComponentsByContainerName($field->getFieldname()));
          foreach ($hydrates as $hydrateKey => $hydrateKeyname)
          {
            /** @var Component $componentExist */
            foreach($componentsExist as $componentkey => $componentExist)
            {
              if($hydrateKeyname == $componentExist->getEditorComponent()->getKeyname())
              {
                $hydrates[$hydrateKey] = $componentExist;
                unset($componentsExist[$componentkey]);
                break;
              }
            }
          }

          $componentManager = $this->container->get("austral.entity_manager.component");
          $position = 1;
          foreach($hydrates as $hydrate)
          {
            if($hydrate instanceof Component)
            {
              $hydrate->setPosition($position);
            }
            elseif(array_key_exists($hydrate, $editorComponents))
            {
              /** @var EditorComponent $editorComponent */
              $editorComponent = $editorComponents[$hydrate];

              $component = new Component();
              $component->setPosition($position);
              $component->setObjectClassname($object->getClassname());
              $component->setEditorComponent($editorComponent);
              if($object->getId())
              {
                $component->setObjectId($object->getId());
              }
              $this->generateAutoComponent($componentManager, $editorComponent->getEditorComponentTypes(), $component);
              $componentManager->update($component, false);
              $object->addComponents($field->getFieldname(), $component);
            }
            $position++;
          }
        }
      }

      $componentFormMapperDefault = new FormMapper();
      $formFieldEvent->getFormMapper()->addSubFormMapper("{$field->getFieldname()}", $componentFormMapperDefault);
      $component = new Component();
      if($object->getId())
      {
        $component->setObjectId($object->getId());
      }
      $component->setObjectClassname($object->getClassname());
      $component->setId("__name__");
      $componentFormMapperDefault->setObject($component);
      $contentBlockTypeChoices = array();

      /** @var ComponentFormType $componentFormType */
      $componentFormType = $this->container->get('austral.content_block.component_form_type')->setFormMapper($formFieldEvent->getFormMapper());

      if($editorComponents)
      {
        /** @var EditorComponentInterface $editorComponent */
        foreach ($editorComponents as $editorComponent)
        {
          if($this->restrictionByObject($editorComponent, $object, $field))
          {
            $collectionFormFieldname = "{$field->getFieldname()}_{$editorComponent->getKeyname()}";

            $contentBlockTypeChoices[$collectionFormFieldname] = array(
              "entitled"    =>  $editorComponent->getName(),
              "category"    =>  $editorComponent->getCategory(),
              "image"       =>  $this->fileLinkGenerator->image($editorComponent, "image") ?? $this->contentBlockConfiguration->get('editor_component.image_default')
            );

            $componentFormMapper = new FormMapper();
            $componentFormMapperDefault->addSubFormMapper($collectionFormFieldname, $componentFormMapper);

            /** @var ComponentInterface|EntityInterface $componentByEditor */
            $componentByEditor = clone $component;
            $componentByEditor->setEditorComponent($editorComponent);
            $componentFormMapper->setObject($componentByEditor);

            $componentFormMapper->add(Field\SymfonyField::create("id", HiddenType::class, array('entitled'=>false)));
            $componentFormMapper->add(Field\SymfonyField::create("position", HiddenType::class, array('entitled'=>false, 'attr'=>array('data-collection-sortabled'=>""))));
            $this->editorComponentParameters($editorComponent, $componentFormMapper, $componentByEditor);


            $hasComponentChildren = false;
            $hasComponentInputFile = false;
            $componentValueCollectionForms = array();
            /** @var EditorComponentTypeInterface $editorComponentType */
            foreach($editorComponent->getEditorComponentTypesWithChild() as $editorComponentType)
            {
              $componentValueCollectionForms[] = $this->buildComponentValueForm($formFieldEvent, $collectionFormFieldname, $componentFormMapper, $componentByEditor, $editorComponentType, $hasComponentChildren, $hasComponentInputFile);
            }
            $group = $componentFormMapper->addGroup("component-values-{$editorComponent->getId()}")
              ->setDirection(GroupFields::DIRECTION_COLUMN)
              ->setAttr(array("class"=>"component-values ".($hasComponentChildren ? "component-values-with-children" : "")));
            $group->adds($componentValueCollectionForms);

            $collectionFormsChildren[$collectionFormFieldname] = Field\CollectionEmbedField::create($collectionFormFieldname, array(
                "title"               =>  $editorComponent->getName(),
                "entitled"            =>  false,
                "button"              =>  "button.new.contentBlock.typeValueChild",
                "formMapper"          =>  $componentFormMapper,
                "allow"               =>  array(
                  "child"               =>  false,
                  "add"                 =>  true,
                  "delete"              =>  true
                ),
                "attr"                =>  array(
                  'class'             =>  $editorComponent->getIsContainer() ? "component-container" : ( $hasComponentInputFile ? " component-file-children" : "")
                ),
                "prototype"           =>  array(
                  "data"                =>  $componentByEditor
                ),
                "master_children"     =>  true,
                "error_bubbling"      =>  false,
                "entry"               =>  array(
                  "type"                =>  get_class($componentFormType),
                  "attr"                =>  array(
                    "formMapperKey"       =>  $collectionFormFieldname
                  )
                ),
                "sortable"            =>  array(
                  "value"               =>  function(ComponentInterface $object) {
                    return ($object->getPosition() < 10 ? "0{$object->getPosition()}" : $object->getPosition());
                  },
                  "editable"            =>  true
                ),
                "getter"              =>  function(ComponentsInterface $object) use ($editorComponent, $field){
                  $components = array();
                  /** @var Component $component */
                  foreach ($object->getComponentsByContainerName($field->getFieldname()) as $component)
                  {
                    if($component->getEditorComponent())
                    {
                      if($component->getEditorComponent()->getId() === $editorComponent->getId())
                      {
                        $components[$component->getId()] = $component;
                      }
                    }
                  }
                  return $components;
                },
                "setter"              =>  function(ComponentsInterface $object, $components) use ($editorComponent, $field){
                  $componentsExist = $object->getComponentsByContainerName($field->getFieldname());
                  /** @var Component $component */
                  foreach($components as $component)
                  {
                    if(array_key_exists($component->getId(), $componentsExist))
                    {
                      unset($componentsExist[$component->getId()]);
                    }
                    $component->setEditorComponent($editorComponent);
                    $object->addComponents($field->getFieldname(), $component);
                  }
                  foreach($componentsExist as $component)
                  {
                    if($component->getEditorComponent())
                    {
                      if($component->getEditorComponent()->getId() === $editorComponent->getId())
                      {
                        $object->removeComponents($field->getFieldname(), $component);
                      }
                    }
                  }
                }
              )
            );
          }
        }
      }

      /** @var LibraryEntityManager $libraryManager */
      $libraryManager = $this->container->get("austral.entity_manager.library");
      $filterByDomain = null;
      if($formFieldEvent->getFormMapper()->getModule())
      {
        $filterByDomain = $formFieldEvent->getFormMapper()->getModule()->getFilterDomainId();
      }

      $libraries = $libraryManager->selectAccessibleInContent(function(QueryBuilder $queryBuilder) use($filterByDomain) {
        if($filterByDomain)
        {
          $queryBuilder->andWhere("root.domainId = :domainId")
          ->setParameter("domainId", $filterByDomain);
        }
      });

      if($libraries)
      {
        /** @var Modules $modules */
        $modules = $this->container->get('austral.admin.modules');
        /** @var LibraryInterface $library */
        foreach ($libraries as $library)
        {
          if($library->getIsEnabled() && $this->restrictionByObject($library, $object, $field))
          {

            $collectionFormFieldname = "components_library-{$library->getKeyname()}";

            $contentBlockTypeChoices[$collectionFormFieldname] = array(
              "entitled"    =>  $library->getName(),
              "category"    =>  "library",
              "image"       =>  $this->fileLinkGenerator->image($library, "image") ?? $this->contentBlockConfiguration->get('editor_component.image_default')
            );

            $componentFormMapper = new FormMapper();
            $componentFormMapperDefault->addSubFormMapper($collectionFormFieldname, $componentFormMapper);

            /** @var ComponentInterface|EntityInterface $componentByEditor */
            $componentByEditor = clone $component;
            $componentByEditor->setLibrary($library);
            $componentFormMapper->setObject($componentByEditor);

            $componentFormMapper->add(Field\SymfonyField::create("id", HiddenType::class, array('entitled' => false)));
            $componentFormMapper->add(Field\SymfonyField::create("position", HiddenType::class, array('entitled' => false, 'attr'=>array('data-collection-sortabled'=>""))));
            $componentFormMapper->addGroup("")
              ->add(Field\TemplateField::create("button", "@AustralContentBlock/Admin/Library/button.html.twig", array(), array(
              "link"  =>  $modules->getModuleByKey("library")->generateUrl("edit", array("id"=>$library->getId()))
            )));

            $collectionFormsChildren[$collectionFormFieldname] = Field\CollectionEmbedField::create($collectionFormFieldname, array(
                "title"               =>  $library->getName(),
                "entitled"            =>  false,
                "button"              =>  "button.new.contentBlock.typeValueChild",
                "formMapper"          =>  $componentFormMapper,
                "master_children"     =>  true,
                "allow"               =>  array(
                  "child"               =>  false,
                  "add"                 =>  true,
                  "delete"              =>  true,
                ),
                "prototype"           =>  array(
                  "data"                =>  $componentByEditor
                ),
                "entry"               =>  array(
                  "type"                =>  get_class($componentFormType),
                  "attr"                =>  array(
                    "formMapperKey"       =>  $collectionFormFieldname
                  ),
                ),
                "sortable"            =>  array(
                  "value"               =>  function(ComponentInterface $object) {
                    $position = ($object->getPosition() < 10 ? "0{$object->getPosition()}" : $object->getPosition());
                    return $position;
                  },
                  "editable"            =>  true
                ),
                "getter"              =>  function(ComponentsInterface $object) use ($library, $field){
                  $components = array();
                  /** @var Component $component */
                  foreach ($object->getComponentsByContainerName($field->getFieldname()) as $component)
                  {
                    if($component->getLibrary())
                    {
                      if($component->getLibrary()->getId() === $library->getId())
                      {
                        $components[$component->getId()] = $component;
                      }
                    }
                  }
                  return $components;
                },
                "setter"              =>  function(ComponentsInterface $object, $components) use ($library, $field){
                  $componentsExist = $object->getComponentsByContainerName($field->getFieldname());
                  /** @var Component $component */
                  foreach($components as $component)
                  {
                    if(array_key_exists($component->getId(), $componentsExist))
                    {
                      unset($componentsExist[$component->getId()]);
                    }
                    $component->setLibrary($library);
                    $object->addComponents($field->getFieldname(), $component);
                  }
                  foreach($componentsExist as $component)
                  {
                    if($component->getLibrary())
                    {
                      if($component->getLibrary()->getId() === $library->getId())
                      {
                        $object->removeComponents($field->getFieldname(), $component);
                      }
                    }
                  }
                },
              )
            );
          }
        }
      }

      if($collectionFormsChildren)
      {
        $options = $field->getOptions();
        $options['collections']["choices"] = $contentBlockTypeChoices;
        $options['allow']["child"] = true;
        $options['entitled'] = false;
        $options['mapped'] = false;
        $options['between_insert'] = true;
        $options['sortable']['value'] = function($object) {
          return ($object->getPosition() < 10 ? "0{$object->getPosition()}" : $object->getPosition());
        };
        //$options['sortable']['editable'] = true;
        $field->setOptions($options);
        $field->addCollectionsForms($collectionFormsChildren);
      }
      else
      {
        $field->setUsedGeneratedForm(false);
      }
    }

  }


  /**
   * @param ComponentEntityManager $componentManager
   * @param Collection $editorComponentTypes
   * @param Component $component
   *
   * @return $this
   */
  protected function generateAutoComponent(ComponentEntityManager $componentManager, Collection $editorComponentTypes, Component $component): FormListener
  {
    /** @var EditorComponentType $editorComponentType */
    foreach ($editorComponentTypes as $editorComponentType)
    {
      $componentValue = new ComponentValue();
      $componentValue->setComponent($component);
      $componentValue->setEditorComponentType($editorComponentType);
      $component->addComponentValues($componentValue);
      $componentManager->update($componentValue, false);
    }
    return $this;
  }



  /**
   * @param EditorComponent $editorComponent
   * @param FormMapper $componentFormMapper
   * @param Component $componentByEditor
   *
   * @throws ReflectionException
   */
  protected function editorComponentParameters(EditorComponent $editorComponent, FormMapper $componentFormMapper, Component $componentByEditor)
  {
    $themes = $editorComponent->getThemes();
    $options = $editorComponent->getOptions();
    if($themes || $options)
    {
      $groupParamatersId = "group-parameters-{$componentByEditor->getId()}";
      $group = $componentFormMapper->addGroup("content-parameters", null);
      $group->add(
        Field\TemplateField::create(
          "buttonParameters",
          "@AustralContentBlock/Admin/Component/button-parameters.html.twig",
          array("group"=> array('class'=>"content-button-parameters"))
        )
      );
      $group = $group->addGroup("parameters")
        ->setAttr(array('id'=>$groupParamatersId, "data-toggle"=>null));
      if($themes)
      {
        $selectThemes = array();
        /** @var Theme $theme */
        foreach($themes as $theme)
        {
          $selectThemes[$theme->getTitle()] = $theme->getId();
        }
        $group->add(Field\SelectField::create("themeId", $selectThemes, array(
              "container"   =>  array(
                "class"       =>  "side-by-side"
              ),
              "group"       =>  array(
                'size'  => GroupFields::SIZE_COL_6
              ),
              "fieldOptions"  =>  array(
                "choice_translation_domain"  =>  false,
              )
            )
          )
        );
      }
      if($options)
      {
        $selectOptions = array();
        /** @var Theme $theme */
        foreach($options as $option)
        {
          $selectOptions[$option->getTitle()] = $option->getId();
        }
        $group->add(Field\SelectField::create("optionId", $selectOptions, array(
              "container"   =>  array(
                "class"       =>  "side-by-side"
              ),
              "group"       =>  array(
                'size'  => GroupFields::SIZE_COL_6
              ),
              "fieldOptions"  =>  array(
                "choice_translation_domain"  =>  false,
              )
            )
          )
        );
      }
    }

    if($layouts = $editorComponent->getLayouts())
    {
      $selectLayouts = array();
      /** @var Layout $layout */
      foreach($layouts as $layout)
      {
        if(!$componentByEditor->getLayoutId())
        {
          $componentByEditor->setLayoutId($layout->getId());
        }
        $selectLayouts[$layout->getTitle()] = $layout->getId();
      }
      $componentFormMapper->addGroup("layout", null)
        ->setAttr(array('class'=>"background-white"))
        ->add(Field\ChoiceField::create("layoutId", $selectLayouts, array(
              "entitled"    =>  false,
              "container"   =>  array(
                "class"       =>  "side-by-side"
              ),
              "choice_style"  =>  "light",
              "required"      =>  true,
              "group"       =>  array(
                'size'  => GroupFields::SIZE_COL_12
              ),
              "fieldOptions"  =>  array(
                "choice_translation_domain"  =>  false,
              )
            ),
          )->addConstraint(new Constraints\NotNull())
        );
    }
  }


  /**
   * @param FormFieldEvent $formFieldEvent
   * @param string $collectionFormFieldname
   * @param FormMapper $componentFormMapper
   * @param $componentOrComponentValue
   * @param EditorComponentTypeInterface $editorComponentType
   * @param bool $hasComponentChildren
   * @param bool $hasComponentInputFile
   *
   * @return Field\FormTypeField
   * @throws ContainerExceptionInterface
   * @throws NotFoundExceptionInterface
   * @throws ReflectionException
   */
  protected function buildComponentValueForm(
      FormFieldEvent $formFieldEvent,
      string $collectionFormFieldname,
      FormMapper $componentFormMapper,
      $componentOrComponentValue,
      EditorComponentTypeInterface $editorComponentType,
      bool &$hasComponentChildren = false,
      bool &$hasComponentInputFile = false
    ): Field\FormTypeField
  {
    $collectionFormFieldname = "{$collectionFormFieldname}_{$editorComponentType->getKeyname()}";

    $componentValueFormMapper = new FormMapper();

    /** @var ComponentValueFormType $componentValueFormType */
    $componentValueFormType = $this->container->get('austral.content_block.component_value_form_type')->setFormMapper($formFieldEvent->getFormMapper());
    $componentFormMapper->addSubFormMapper($collectionFormFieldname, $componentValueFormMapper);

    /** @var ComponentInterface $componentByEditor */
    $componentValue = new ComponentValue();
    $componentValue->setEditorComponentType($editorComponentType);

    if($componentOrComponentValue instanceof Component)
    {
      $componentOrComponentValue->addComponentValues($componentValue);
    }
    elseif($componentOrComponentValue instanceof ComponentValues)
    {
      $componentOrComponentValue->addChildren($componentValue);
    }
    $componentValueFormMapper->setObject($componentValue);


    if($editorComponentType->getType() == "list" || $editorComponentType->getType() == "group")
    {
      $hasComponentChildren = true;
      $componentValuesListFormMapper = new FormMapper();
      $componentValueFormMapper->addSubFormMapper("{$collectionFormFieldname}_children", $componentValuesListFormMapper);

      $componentValues = new ComponentValues();
      $componentValuesListFormMapper->setObject($componentValues);
      $componentValuesFormType = $this->container->get('austral.content_block.component_values_form_type')->setFormMapper($formFieldEvent->getFormMapper());

      $hasComponentInputFileChild = false;
      $group = $componentValuesListFormMapper->addGroup(
        "component-values-block",
        )->setStyle($editorComponentType->getType() != "list" ? GroupFields::STYLE_WHITE : GroupFields::STYLE_NONE)
        ->setDirection($editorComponentType->getType() == "list" ? GroupFields::DIRECTION_ROW : $editorComponentType->getBlockDirection());
      /** @var EditorComponentTypeInterface $editorComponentTypeChildren */
      foreach($editorComponentType->getChildren() as $editorComponentTypeChildren)
      {
        $componentValueCollectionForm = $this->buildComponentValueForm(
          $formFieldEvent,
          "{$collectionFormFieldname}_children",
          $componentValuesListFormMapper,
          $componentValues,
          $editorComponentTypeChildren,
          $hasComponentChildren,
          $hasComponentInputFileChild)
        ;
        $group->add($componentValueCollectionForm);
      }
      if($editorComponentType->getType() == "list")
      {
        $componentValuesListFormMapper->add(Field\SymfonyField::create("position", HiddenType::class, array("entitled"=>false, "attr"=>array("data-collection-sortabled"=>""))));
        $componentValueFormMapper->add(Field\SymfonyField::create("position", HiddenType::class, array("entitled"=>false, "attr"=>array("data-collection-sortabled"=>""))));
        $componentValueFormMapper->add(Field\CollectionEmbedField::create("{$collectionFormFieldname}_children", array(
            "button"              =>  "button.new.contentBlock.typeValueChild",
            "formMapper"          =>  $componentValueFormMapper,
            "entitled"            =>  false,
            "allow"               =>  array(
              "child"               =>  false,
              "add"                 =>  true,
              "delete"              =>  true
            ),
            "view_position"       =>  true,
            "master_children"     =>  false,
            "between_insert"      =>  true,
            "attr"                =>  array(
              "class"               =>  "direction-{$editorComponentType->getBlockDirection()} block-type-{$editorComponentType->getType()}".( $hasComponentInputFileChild ? " component-file-children" : ""),
            ),
            "prototype"           =>  array(
              "data"                =>  $componentValues,
              "name"                =>  "__{$collectionFormFieldname}Id__",
            ),
            "entry"               =>  array(
              "type"                =>  get_class($componentValuesFormType),
              "attr"                =>  array(
                "formMapperKey"       =>  $collectionFormFieldname."_children"
              ),
            ),
            "sortable"            =>  array(
              "value"               =>  function($object) {
                $position = ($object->getPosition() < 10 ? "0{$object->getPosition()}" : $object->getPosition());
                return $position."-".AustralTools::random(4);
              },
              "editable"            =>  true
            ),
            "getter"              =>  function(ComponentValue $componentValue){
              return $componentValue->getChildren();
            },
            "setter"              =>  function(ComponentValue $componentValue, $componentValues){
              /** @var ComponentValues $oneComponentValues */
              foreach($componentValues as $oneComponentValues)
              {
                $componentValue->addChildren($oneComponentValues);
              }
            }
          )
        ));
      }
      else
      {
        $hasComponentInputFile = $hasComponentInputFileChild;
        $componentValueFormMapper->add(Field\FormTypeField::create("{$collectionFormFieldname}_children", $componentValuesFormType,  array(
          "entitled"            =>  false,
          "attr"                =>  array(
            "formMapperKey"       =>  "{$collectionFormFieldname}_children"
          ),
          "getter"              =>  function(ComponentValue $componentValue) use ($editorComponentType) {
            if(!$children = $componentValue->getChildren()->toArray())
            {
              $child = new ComponentValues();
              foreach ($editorComponentType->getChildren() as $editorComponentTypeChildren)
              {
                $childValue = new ComponentValue();
                $childValue->setParent($child);
                $child->addChildren($childValue);
                $childValue->setEditorComponentType($editorComponentTypeChildren);
              }
            }
            else
            {
              $child = AustralTools::first($children);
            }
            return $child;
          },
          "setter"              =>  function(ComponentValue $componentValue, $componentValues){
              $componentValue->addChildren($componentValues);
          },
        )));
      }
    }
    else
    {
      $componentValueFormMapper->addFieldsMapping($editorComponentType->getKeyname());

      $contraints = array();
      if($editorComponentType->getParameterByKey("isRequired", false))
      {
        $contraints[] = new Constraints\NotNull();
      }
      if($editorComponentType->getType() == "title" ||
        $editorComponentType->getType() == "button" ||
        ($editorComponentType->getType() == "text" && $editorComponentType->getParameterByKey("type") != "date"))
      {
        $contraints[] = new Constraints\Length(array(
            "max" => 255,
            "maxMessage" => "errors.length.max"
          )
        );
      }

      $group = $componentValueFormMapper->addGroup('content-fields' )
        ->setStyle(GroupFields::STYLE_WHITE)
        ->setDirection(GroupFields::DIRECTION_COLUMN)
        ->setAttr(array("class"=>"content-{$editorComponentType->getType()}"));
      if($editorComponentType->getType() == "title")
      {
        $choicesTags = array();
        $tagsList = $this->contentBlockConfiguration->getConfig('title_tag_values');
        foreach($editorComponentType->getParameterByKey("tags", array()) as $tag)
        {
          $choicesTags[array_search($tag, $tagsList)] = $tag;
        }
        if($choicesTags)
        {
          $defaultValue = array_key_exists("h2", $choicesTags) ? $choicesTags['h2'] : AustralTools::first($choicesTags);
          $group->add(Field\ChoiceField::create("tag", $choicesTags, array(
                "entitled"      =>  false,
                "choice_style"  =>  "light",
                "required"      =>  true,
                "getter"        =>  function(ComponentValue $componentValue) use($defaultValue){
                  return $componentValue->getOptionsByKey("tags", $defaultValue);
                },
                "setter"        =>  function(ComponentValue $componentValue, $value) {
                  return $componentValue->setOptionsByKey("tags", $value);
                },
                "fieldOptions"  =>  array(
                  "translation_domain"  =>  false,
                  "choice_translation_domain"  =>  false,
                )
              )
            )->addConstraint(new Constraints\NotNull())
          );
        }
        $group->add(Field\TextField::create("content", array(
              "entitled"    =>  false,
              "placeholder" =>  $editorComponentType->getEntitled()
            )
          )->setConstraints($contraints)
        );
      }
      elseif($editorComponentType->getType() == "choice")
      {
        $choices = array();
        foreach($editorComponentType->getParameterByKey("choices", array()) as $tag)
        {
          $choices[$tag] = $tag;
        }
        if($choices)
        {
          $group->add(Field\SelectField::create("choices",
              $choices,
              array(
                "entitled"    =>  $editorComponentType->getEntitled(),
                "container"   =>  array(
                  "class" =>  "animate"
                ),
                "required"  =>  true,
                "getter"    =>  function(ComponentValue $componentValue){
                  return $componentValue->getOptionsByKey("choice", null);
                },
                "setter"    =>  function(ComponentValue $componentValue, $value) {
                  return $componentValue->setOptionsByKey("choice", $value);
                },
                "group"       =>  array(
                  'size'  => GroupFields::SIZE_COL_12
                ),
                "fieldOptions"  =>  array(
                  "translation_domain"  =>  false,
                  "choice_translation_domain"  =>  false,
                )
              )
            )->addConstraint(new Constraints\NotNull())
          );
        }
      }
      elseif($editorComponentType->getType() == "text")
      {
        $typeField = $editorComponentType->getParameterByKey("type");
        if($typeField !== "date")
        {
          $contraints[] = new Constraints\Length(array(
              "max" => 255,
              "maxMessage" => "errors.length.max"
            )
          );
        }
        if($typeField == "integer")
        {
          $contraints[] = new Constraints\Type("integer", "errors.type.int");
          $group->add(Field\IntegerField::create("content", array(
                "entitled"        =>  $editorComponentType->getEntitled(),
                "container"       =>  array(
                  "class"           =>  "animate"
                ),
                "fieldOptions"  =>  array(
                  "translation_domain"  =>  false,
                )
              )
            )->setConstraints($contraints)
          );
        }
        elseif($typeField == "number")
        {
          $contraints[] = new Constraints\Type("float", "errors.type.float");
          $group->add(Field\NumberField::create("content", array(
                "entitled"        =>  $editorComponentType->getEntitled(),
                "container"       =>  array(
                  "class"           =>  "animate"
                ),
                "fieldOptions"  =>  array(
                  "translation_domain"  =>  false,
                )
              )
            )->setConstraints($contraints)
          );
        }
        elseif($typeField == "date")
        {
          $group->add(Field\DatePicker::create("date", array(
                "entitled"        =>  $editorComponentType->getEntitled(),
                "container"       =>  array(
                  "class"           =>  "animate"
                ),
                "fieldOptions"  =>  array(
                  "translation_domain"  =>  false,
                )
              )
            )->setConstraints($contraints)
          );
        }
        else
        {
          $group->add(Field\TextField::create("content", array(
                "entitled"        =>  $editorComponentType->getEntitled(),
                "container"       =>  array(
                  "class"           =>  "animate"
                ),
                "fieldOptions"  =>  array(
                  "translation_domain"  =>  false,
                )
              )
            )->setConstraints($contraints)
          );
        }
      }
      elseif($editorComponentType->getType() == "textarea")
      {
        if($editorComponentType->getParameterByKey("isWysiwyg"))
        {
          $group->add(Field\WysiwygField::create("content", array(
                "entitled"         =>  false,
                "placeholder"      =>  $editorComponentType->getEntitled(),
                "fieldOptions"  =>  array(
                  "translation_domain"  =>  false,
                )
              )
            )->setConstraints($contraints)
          );
        }
        else
        {
          $group->add(Field\TextareaField::create("content", Field\TextareaField::SIZE_MIDDLE, array(
                "entitled"        =>  $editorComponentType->getEntitled(),
                "container"       =>  array(
                  "class"           =>  "animate"
                ),
                "fieldOptions"  =>  array(
                  "translation_domain"  =>  false,
                )
              )
            )->setConstraints($contraints)
          );
        }
      }
      elseif($editorComponentType->getType() == "movie")
      {
        if($editorComponentType->getParameterByKey("isIframe"))
        {
          $group->add(Field\TextareaField::create("content", Field\TextareaField::SIZE_MIDDLE, array(
                "entitled"        =>  $editorComponentType->getEntitled(),
                "container"       =>  array(
                  "class"           =>  "animate"
                ),
                "fieldOptions"  =>  array(
                  "translation_domain"  =>  false,
                )
              )
            )->setConstraints($contraints)
          );
        }
        else
        {
          $group->add(Field\TextField::create("content", array(
                "entitled"        =>  $editorComponentType->getEntitled(),
                "container"       =>  array(
                  "class"           =>  "animate"
                ),
                "fieldOptions"  =>  array(
                  "translation_domain"  =>  false,
                )
              )
            )->setConstraints($contraints)
          );
        }
      }
      elseif($editorComponentType->getType() == "image")
      {
        $this->addFieldFileMapping($editorComponentType, $editorComponentType->getType());
        $group->add(Field\UploadField::create("image", array(
              "entitled"          =>  $editorComponentType->getParameterByKey("viewEntitled", false) ? $editorComponentType->getEntitled() : false,
              "blockSize"         =>  Field\UploadField::LIGHT,
              "placeholder"       =>  $editorComponentType->getEntitled(),
            )
          )->setConstraints($contraints)
        );
        $hasComponentInputFile = true;

        $componentValueFormMapper->addPopin("popup-component-image-editor", "image", array(
            "button"          =>  array(
              "entitled"        =>  "actions.picture.edit",
              "picto"           =>  "",
              "class"           =>  "button-action"
            ),
            "popin"           =>  array(
              "id"              =>  "upload",
              "template"        =>  "uploadEditor",
            )
          )
        )->add(Field\TextField::create("reelname", array(
              "entitled"        =>  "fields.reelname.entitled",
              "getter"          =>  function(ComponentValue $componentValue){
                return $componentValue->getOptionsByKey("reelname", null);
              },
              "setter"          =>  function(ComponentValue $componentValue, $value) {
                return $componentValue->setOptionsByKey("reelname", $value);
              }
            )
          )
        )->add(Field\TextField::create("alt", array(
              "entitled"        =>  "fields.alt.entitled",
              "getter"          =>  function(ComponentValue $componentValue){
                return $componentValue->getOptionsByKey("alt", null);
              },
              "setter"          =>  function(ComponentValue $componentValue, $value) {
                return $componentValue->setOptionsByKey("alt", $value);
              }
            )
          )
        )
        ->end();
      }
      elseif($editorComponentType->getType() == "file")
      {
        $this->addFieldFileMapping($editorComponentType, $editorComponentType->getType());
        $group->add(Field\UploadField::create("file", array(
              "entitled"          =>  false,
              "blockSize"         =>  Field\UploadField::LIGHT,
              "placeholder"       =>  $editorComponentType->getEntitled(),
            )
          )->setConstraints($contraints)
        );
        $componentValueFormMapper->addPopin("popup-component-file-editor", "file", array(
              "button"          =>  array(
                "entitled"        =>  "actions.file.edit",
                "picto"           =>  "",
                "class"           =>  "button-action"
              ),
              "popin"           =>  array(
                "id"              =>  "upload",
                "template"        =>  "uploadEditor",
              )
            )
          )->add(Field\TextField::create("fileReelname", array(
                "entitled"           => "fields.reelname.entitled",
                "getter"    =>  function(ComponentValue $componentValue){
                  return $componentValue->getOptionsByKey("fileReelname", null);
                },
                "setter"    =>  function(ComponentValue $componentValue, $value) {
                  return $componentValue->setOptionsByKey("fileReelname", $value);
                }
              )
            )
          )
        ->end();
      }
      elseif($editorComponentType->getType() == "container")
      {
        $group->add(Field\TemplateField::create("container", "@AustralContentBlock/Admin/Component/container.html.twig"));
      }
      elseif($editorComponentType->getType() == "button")
      {
        $group->add(Field\TextField::create("content", array(
              "entitled"        =>  false,
              "placeholder"     =>  $editorComponentType->getEntitled(),
              "fieldOptions"  =>  array(
                "translation_domain"  =>  false,
              )
            )
          )->setConstraints($contraints)
        );
      }
    }

    if($editorComponentType->getCanHasLink())
    {
      $this->addFieldFileMapping($editorComponentType, "file");
      $this->addLink($group, "content");
    }


    return Field\FormTypeField::create($collectionFormFieldname, $componentValueFormType, array(
      'entitled'    =>  false,
      "attr"        =>  array(
        "formMapperKey"       =>  $collectionFormFieldname
      ),
      "getter"              =>  function($componentOrComponentValue) use ($editorComponentType){
        if($componentOrComponentValue instanceof Component)
        {
          return $componentOrComponentValue->getComponentValuesByEditorComponentType($editorComponentType);
        }
        elseif($componentOrComponentValue instanceof ComponentValues)
        {
          return $componentOrComponentValue->getChildrenByEditorComponentType($editorComponentType);
        }
        return null;
      },
      "setter"              =>  function($componentOrComponentValue, $componentValue) use ($editorComponentType){
        if($componentValue)
        {
          $componentValue->setEditorComponentType($editorComponentType);
          if($componentOrComponentValue instanceof Component)
          {
            $componentOrComponentValue->addComponentValues($componentValue);
          }
          elseif($componentOrComponentValue instanceof ComponentValues)
          {
            $componentOrComponentValue->addChildren($componentValue);
          }
        }
      },
    ));
  }

  /**
   * @param MapperElementInterface $mapperElement
   * @param null $fieldname
   *
   * @throws Exception
   */
  protected function addLink(MapperElementInterface $mapperElement, $fieldname = null)
  {
    $popin = $mapperElement->addPopin("popup-component-link-editor", $fieldname, array(
      "button"  =>  array(
        "entitled"      =>  "",
        "picto"         =>  "austral-picto-link",
        "class"         =>  "button-picto",
        "data"          =>  array(
          "data-check-value"  =>  json_encode(array(
            "*[data-popin-update-input='field-link-choice']",
            "*[data-popin-update-input='field-link-url']",
            "*[data-popin-update-input='field-link-email']",
            "*[data-popin-update-input='field-link-phone']",
            "*[data-popin-update-input='field-link-file']",
          ))
        )
      ),
      "popin"  =>  array(
        "id"            =>  "master",
        "class"         =>  "little",
        "template"      =>  "linkEditor",
      )
    ));
    $popin->add(Field\SymfonyField::create("linkType", TextType::class, array(
      "entitled"    =>  false,
      "attr"        =>  array(
        "autocomplete"            => "off",
        "data-popin-update-input" => "field-link-type"
      )
    )));
    $popin->add(Field\SymfonyField::create("linkEntityKey", TextType::class, array(
      "entitled"    =>  false,
      "attr"        =>  array(
        "autocomplete"            => "off",
        "data-popin-update-input" => "field-link-choice",
        "data-popin-update-value" => "field-link-choice-name",
      )
    )));
    $popin->add(Field\SymfonyField::create("linkUrl", TextType::class, array(
      "entitled"    =>  false,
      "attr"        =>  array(
        "autocomplete"            => "off",
        "data-popin-update-input" => "field-link-url"
      )
    )));
    $popin->add(Field\SymfonyField::create("linkEmail", TextType::class, array(
      "entitled"    =>  false,
      "attr"        =>  array(
        "autocomplete"            => "off",
        "data-popin-update-input" => "field-link-email"
      )
    )));
    $popin->add(Field\SymfonyField::create("linkPhone", TextType::class, array(
      "entitled"    =>  false,
      "attr"        =>  array(
        "autocomplete"            => "off",
        "data-popin-update-input" => "field-link-phone"
      )
    )));
    $popin->add(Field\SymfonyField::create("target", TextType::class, array(
      "entitled"    =>  false,
      "attr"        =>  array(
        "autocomplete"            => "off",
        "data-popin-update-input" => "field-target-blank"
      ),
      "getter"      =>  function(ComponentValue $componentValue){
        return $componentValue->getOptionsByKey("target") == "_blank";
      },
      "setter"      =>  function(ComponentValue $componentValue, $value) {
        return $componentValue->setOptionsByKey("target", $value == true ? "_blank" : null);
      }
    )));
    $popin->add(Field\SymfonyField::create("anchor", TextType::class, array(
      "entitled"    =>  false,
      "attr"        =>  array(
        "autocomplete"            => "off",
        "data-popin-update-input" => "field-anchor"
      ),
      "getter"      =>  function(ComponentValue $componentValue){
        return $componentValue->getOptionsByKey("anchor");
      },
      "setter"      =>  function(ComponentValue $componentValue, $value) {
        return $componentValue->setOptionsByKey("anchor", $value);
      }
    )));
    $popin->add(Field\UploadField::create("file", array(
      "entitled"    =>  "fields.componentLink.file.entitled",
      "blockSize"   =>  Field\UploadField::LIGHT,
      "attr"        =>  array(
        "data-popin-update-input" => "field-link-file"
      )
    )));
    $mapperElement->addPopin("popup-component-link-editor", "file", array(
          "button"    =>  array(
            "entitled"  =>  "actions.file.edit",
            "picto"     =>  "",
            "class"     =>  "button-action"
          ),
          "popin"     =>  array(
            "id"        =>  "upload",
            "template"  =>  "uploadEditor",
          )
        )
      )->add(Field\TextField::create("fileReelname", array(
          "entitled"  =>  "fields.reelname.entitled",
          "getter"    =>  function(ComponentValue $componentValue){
            return $componentValue->getOptionsByKey("fileReelname");
          },
          "setter"    =>  function(ComponentValue $componentValue, $value) {
            return $componentValue->setOptionsByKey("fileReelname", $value);
          }
        )
      )
    )
    ->end();
  }

  /**
   * @param EditorComponentInterface|LibraryInterface $editorComponentOrLibrary
   * @param EntityInterface $object
   * @param Field\Base\FieldInterface $field
   *
   * @return bool
   */
  protected function restrictionByObject($editorComponentOrLibrary, EntityInterface $object, Field\Base\FieldInterface $field): bool
  {
    if(!$restrictions = $editorComponentOrLibrary->getRestrictions())
    {
      return true;
    }

    $restrictionsByKey = array();
    /** @var Restriction $restriction */
    foreach($restrictions as $restriction)
    {
      if($restriction->getCondition() === "include")
      {
        $restrictionsByKey["all"] = false;
        $restrictionsByKey["{$restriction->getValue()}_{$restriction->getContainerName()}"] = true;
      }
      else
      {
        if(!array_key_exists("all", $restrictionsByKey))
        {
          $restrictionsByKey["all"] = true;
        }
        $restrictionsByKey["{$restriction->getValue()}_{$restriction->getContainerName()}"] = false;
      }
    }

    if(array_key_exists("{$object->getClassname()}:{$object->getId()}_{$field->getFieldname()}", $restrictionsByKey))
    {
      $isInclude = $restrictionsByKey["{$object->getClassname()}:{$object->getId()}_{$field->getFieldname()}"];
    }
    elseif(array_key_exists("{$object->getClassname()}:{$object->getId()}_all", $restrictionsByKey))
    {
      $isInclude = $restrictionsByKey["{$object->getClassname()}:{$object->getId()}_all"];
    }
    elseif(array_key_exists("{$object->getClassname()}:all_all", $restrictionsByKey))
    {
      $isInclude = $restrictionsByKey["{$object->getClassname()}:all_all"];
    }
    elseif(array_key_exists("{$object->getClassname()}:all_{$field->getFieldname()}", $restrictionsByKey))
    {
      $isInclude = $restrictionsByKey["{$object->getClassname()}:all_{$field->getFieldname()}"];
    }
    else
    {
      $isInclude = $restrictionsByKey["all"];
    }
    return $isInclude;
  }



}