<?php
/*
 * This file is part of the Austral ContentBlock Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ContentBlockBundle\Admin;

use App\Entity\Austral\ContentBlockBundle\EditorComponentType;
use Austral\AdminBundle\Admin\Admin;
use Austral\AdminBundle\Admin\AdminModuleInterface;
use Austral\AdminBundle\Admin\Event\FormAdminEvent;
use Austral\AdminBundle\Admin\Event\ListAdminEvent;
use Austral\AdminBundle\Module\Modules;

use Austral\ContentBlockBundle\Annotation\ObjectContentBlock;
use Austral\ContentBlockBundle\Configuration\ContentBlockConfiguration;
use Austral\ContentBlockBundle\Entity\EditorComponent;
use Austral\ContentBlockBundle\Entity\Interfaces\EditorComponentInterface;
use Austral\ContentBlockBundle\Entity\Interfaces\EditorComponentTypeInterface;
use Austral\ContentBlockBundle\EntityManager\ComponentEntityManager;
use Austral\ContentBlockBundle\Form\Type\EditorComponentTypeFormType;
use Austral\ContentBlockBundle\Form\Type\LayoutFormType;
use Austral\ContentBlockBundle\Form\Type\OptionFormType;
use Austral\ContentBlockBundle\Form\Type\RestrictionFormType;
use Austral\ContentBlockBundle\Form\Type\ThemeFormType;
use Austral\ContentBlockBundle\Mapping\ObjectContentBlockMapping;
use Austral\ContentBlockBundle\Model\Editor\Layout;
use Austral\ContentBlockBundle\Model\Editor\Option;
use Austral\ContentBlockBundle\Model\Editor\Restriction;
use Austral\ContentBlockBundle\Model\Editor\Theme;
use Austral\ContentBlockBundle\Services\ContentBlockContainer;

use Austral\EntityBundle\Entity\EntityInterface;

use Austral\EntityBundle\Mapping\EntityMapping;
use Austral\FormBundle\Mapper\GroupFields;
use Austral\FormBundle\Field as Field;
use Austral\FormBundle\Mapper\Fieldset;
use Austral\FormBundle\Mapper\FormMapper;

use Austral\ListBundle\Column as Column;
use Austral\ListBundle\DataHydrate\DataHydrateORM;

use Austral\ToolsBundle\AustralTools;
use Doctrine\ORM\QueryBuilder;
use Exception;
use ReflectionException;
use Symfony\Component\Validator\Constraints as Constraints;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use function Symfony\Component\String\u;

/**
 * EditorComponent Admin.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class EditorComponentAdmin extends Admin implements AdminModuleInterface
{

  /**
   * @var bool
   */
  protected bool $graphicItemBundleEnabled = false;

  /**
   * @return array
   */
  public function getEvents() : array
  {
    return array(
      FormAdminEvent::EVENT_UPDATE_BEFORE =>  "formUpdateBefore"
    );
  }

  /**
   * @param ListAdminEvent $listAdminEvent
   */
  public function configureListMapper(ListAdminEvent $listAdminEvent)
  {

    $contentBlockContainer = $this->container->get('austral.content_block.content_block_container')->getObjectsByEntity();
    $contentBlockContainerSelect = array();
    foreach($contentBlockContainer as $entityName => $objects)
    {
      if($entityName === "Library")
      {
        $contentBlockContainerSelect["{$entityName}Navigation:all"] = $this->translator->trans("choices.restriction.all", array('%element%'=>"{$entityName}Navigation"),$listAdminEvent->getListMapper()->getTranslateDomain());
      }
      $contentBlockContainerSelect["{$entityName}:all"] = $this->translator->trans("choices.restriction.all", array('%element%'=>$entityName),$listAdminEvent->getListMapper()->getTranslateDomain());
      foreach($objects as $object)
      {
        if($entityName === "Library")
        {
          if($object->getIsNavigationMenu())
          {
            $contentBlockContainerSelect["{$entityName}Navigation:{$object->getId()}"] = $object->__toString();
          }
          else
          {
            $contentBlockContainerSelect["{$entityName}:{$object->getId()}"] = $object->__toString();
          }
        }
        else
        {
          $contentBlockContainerSelect["{$entityName}:{$object->getId()}"] = $object->__toString();
        }
      }
    }

    $listAdminEvent->getTemplateParameters()->addParameters("listRestrictions", $contentBlockContainerSelect);
    $listAdminEvent->getListMapper()
      ->getSection("default")
        ->buildDataHydrate(function(DataHydrateORM $dataHydrate) {
          $dataHydrate->addQueryBuilderPaginatorClosure(function(QueryBuilder $queryBuilder) {
            return $queryBuilder->orderBy("root.position", "ASC");
          });
        })
        ->addColumn(new Column\Value("name"))
        ->addColumn(new Column\Value("keyname"))
        ->addColumn(new Column\Value("category"))
        ->addColumn(new Column\Template("restriction","fields.restrictions.entitled", "@AustralContentBlock/Admin/EditorComponent/restrictions.html.twig"))
        ->addColumn(new Column\SwitchValue("isEnabled", null, 0, 1,
            $listAdminEvent->getCurrentModule()->generateUrl("change"),
            $listAdminEvent->getCurrentModule()->isGranted("edit")
          )
        )
    ->end();
  }

  /**
   * @param FormAdminEvent $formAdminEvent
   *
   * @throws Exception
   */
  public function configureFormMapper(FormAdminEvent $formAdminEvent)
  {
    $categories = array();
    foreach($this->container->get('austral.content_block.config')->get("editor_component.categories") as $value)
    {
      $categories["choices.editor_component.category.{$value}"] = $value;
    }
    $formAdminEvent->getFormMapper()
      ->addFieldset("fieldset.right")
        ->setPositionName(Fieldset::POSITION_RIGHT)
        ->add(Field\SelectField::create("category",
            $categories,
            array("required"=>true)
          )
        )
        ->add(Field\ChoiceField::create("isEnabled", array(
          "choices.status.no"         =>  false,
          "choices.status.yes"        =>  true,
        )))
        ->add(Field\ChoiceField::create("isContainer", array(
          "choices.status.no"         =>  false,
          "choices.status.yes"        =>  true,
        )))
        ->add(Field\ChoiceField::create("isGuidelineView", array(
          "choices.status.no"         =>  false,
          "choices.status.yes"        =>  true,
        )))
      ->end()
      ->addFieldset("fieldset.dev.config")
        ->setIsView($this->container->get("security.authorization_checker")->isGranted("ROLE_ROOT"))
        ->add(Field\TextField::create("keyname"))
      ->end()
      ->addFieldset("fieldset.generalInformation")
        ->add(Field\TextField::create("name"))
        ->add(Field\TextField::create("templatePath"))
        ->addGroup("imagePicto")
          ->add(Field\UploadField::create("image"))
        ->end()
        ->addPopin("popup-editor-image", "image", array(
            "button"  =>  array(
              "entitled"            =>  "actions.picture.edit",
              "picto"               =>  "",
              "class"               =>  "button-action"
            ),
            "popin"  =>  array(
              "id"            =>  "upload",
              "template"      =>  "uploadEditor",
            )
          )
        )
        ->end()
      ->end()

      ->addFieldset("fieldset.editorComponent.themes")
        ->add($this->createCollectionTheme($formAdminEvent))
      ->end()

      ->addFieldset("fieldset.editorComponent.options")
        ->add($this->createCollectionOption($formAdminEvent))
      ->end()

      ->addFieldset("fieldset.editorComponent.layouts")
        ->add(Field\SwitchField::create("layoutViewChoice", array(
          "attr"        =>  array(
            "data-view-by-choices-parent" =>  ".form-container .central-container",
            "data-view-by-choices-children" =>  ".layout-view-choice",
            'data-view-by-choices' =>  json_encode(array(
              0       =>  "layout-view-choice-hide",
              1       =>  "layout-view-choice-view",
            ))
          )

        )))
        ->add($this->createCollectionLayout($formAdminEvent))
      ->end()

      ->addFieldset("fieldset.editorComponent.restrictions")
        ->add($this->createCollectionRestrictions($formAdminEvent))
      ->end()
    ;

    if($this->container->get("kernel")->getBundle("AustralGraphicItemsBundle"))
    {
      $formAdminEvent->getFormMapper()->getFieldset("fieldset.generalInformation")
        ->addGroup("imagePicto")
          ->add(\Austral\GraphicItemsBundle\Field\GraphicItemField::create("graphicItem"))
        ->end();
    }


    if(!$formAdminEvent->getFormMapper()->getObject()->getIsContainer())
    {
      $formAdminEvent->getFormMapper()
        ->addFieldset("fieldset.contentBlockTypeEditor")
          ->add($this->createCollectionEditorComponentTypeForms($formAdminEvent))
        ->end();
    }

    /** @var ComponentEntityManager $componentManager */
    $componentManager = $this->container->get('austral.entity_manager.component');
    /** @var EditorComponentInterface $object */
    $object = $formAdminEvent->getFormMapper()->getObject();
    $allComponentsUsed = $componentManager->selectArrayComponentsByEditorComponent($object);

    /** @var ContentBlockContainer $contentBlockContainer */
    $contentBlockContainer = $this->container->get('austral.content_block.content_block_container');

    $objectsByEntity = $contentBlockContainer->getObjectsByEntity(true);

    /** @var Modules $australModules */
    $australModules = $this->container->get('austral.admin.modules');
    $componentsUsed = array();
    foreach($allComponentsUsed as $component)
    {
      if(!array_key_exists($component['objectLiaison'], $componentsUsed))
      {
        $objectClassname = $component['objectClassname'];
        if(array_key_exists($objectClassname, $objectsByEntity))
        {
          $objects = $objectsByEntity[$objectClassname];
          if(array_key_exists($component['objectId'], $objects))
          {
            $objectId = strpos($objectClassname, "Translate") !== false ? $objects[$component['objectId']]->getMaster()->getId(): $objects[$component['objectId']]->getId();
            $objectClassname = str_replace("Translate", "", $objectClassname);
            $componentsUsed[$component['objectLiaison']] = array(
              "objectClassname"     =>  $objectClassname,
              "objectId"            =>  $objectId,
              "module"              =>  ""//$australModules->getModuleByEntityClassname($objectClassname)[0]
            );
          }
        }
      }
    }
    $formAdminEvent->getFormMapper()
      ->addFieldset("fieldset.editorComponent.usedBy")
        ->setIsView($this->container->get("security.authorization_checker")->isGranted("ROLE_ROOT"))
        ->add(Field\TemplateField::create("usedBy", "@AustralContentBlock/Admin/EditorComponent/used-by.html.twig", array(), array(
          'componentsUsed'  =>  $componentsUsed
        )))
      ->end();


  }

  /**
   * @param FormAdminEvent $formAdminEvent
   *
   * @return Field\CollectionEmbedField
   * @throws ReflectionException|Exception
   */
  protected function createCollectionTheme(FormAdminEvent $formAdminEvent): Field\CollectionEmbedField
  {
    $themeFormMapper = new FormMapper();
    $theme = new Theme();
    $themeFormMapper->setObject($theme)
      ->addGroup("generalInformations")
        ->add(Field\TextField::create("title", array(
              "container"  =>  array(
                "class" =>  "animate"
              ),
              "group"       =>  array(
                'size'  => GroupFields::SIZE_COL_8
              )
            )
          )->setConstraints(array(
            new Constraints\NotNull(),
            new Constraints\Length(array(
                  "max" => 255,
                  "maxMessage" => "errors.length.max"
                )
              )
            )
          )
        )
        ->add(Field\TextField::create("keyname", array(
              "container"  =>  array(
                "class" =>  "animate"
              ),
              "group"       =>  array(
                'size'  => GroupFields::SIZE_COL_4
              )
            )
          )->setConstraints(array(
              new Constraints\Length(array(
                  "max" => 255,
                  "maxMessage" => "errors.length.max"
                )
              )
            )
          )
        )
      ->end()
      ->add(Field\SymfonyField::create("position", HiddenType::class, array("entitled"=>false, "attr"=>array("data-collection-sortabled"=>""))));

    $formAdminEvent->getFormMapper()->addSubFormMapper("themes", $themeFormMapper);

    /** @var ThemeFormType $themeFormType */
    $themeFormType = $this->container->get('austral.content_block.theme_form_type')->setFormMapper($themeFormMapper);
    return Field\CollectionEmbedField::create("themes", array(
        "entitled"            =>  false,
        "button"              =>  "button.new.theme",
        "collections"         =>  array(
          "objects"             =>  "themes"
        ),
        "allow"               =>  array(
          "child"               =>  false,
          "add"                 =>  true,
          "delete"              =>  true,
        ),
        "entry"               =>  array(
          "type"                =>  get_class($themeFormType)
        ),
        "prototype"           =>  array(
          "data"                =>  $theme
        ),
        "sortable"            =>  array(
          "value"               =>  "position",
          "editable"            =>  true
        ),
      )
    );
  }

  /**
   * @param FormAdminEvent $formAdminEvent
   *
   * @return Field\CollectionEmbedField
   * @throws ReflectionException|Exception
   */
  protected function createCollectionOption(FormAdminEvent $formAdminEvent): Field\CollectionEmbedField
  {
    $optionFormMapper = new FormMapper();
    $option = new Option();
    $optionFormMapper->setObject($option)
      ->addGroup("generalInformations")
        ->add(Field\TextField::create("title", array(
              "container"  =>  array(
                "class" =>  "animate"
              ),
              "group"       =>  array(
                'size'  => GroupFields::SIZE_COL_8
              )
            )
          )->setConstraints(array(
              new Constraints\NotNull(),
              new Constraints\Length(array(
                  "max" => 255,
                  "maxMessage" => "errors.length.max"
                )
              )
            )
          )
        )
        ->add(Field\TextField::create("keyname", array(
              "container"  =>  array(
                "class" =>  "animate"
              ),
              "group"       =>  array(
                'size'  => GroupFields::SIZE_COL_4
              )
            )
          )->addConstraint(new Constraints\Length(array(
                "max" => 255,
                "maxMessage" => "errors.length.max"
              )
            )
          ),
        )
      ->end()
      ->add(Field\SymfonyField::create("position", HiddenType::class, array("entitled"=>false, "attr"=>array("data-collection-sortabled"=>""))));

    $formAdminEvent->getFormMapper()->addSubFormMapper("options", $optionFormMapper);
    /** @var OptionFormType $optionFormType */
    $optionFormType = $this->container->get('austral.content_block.option_form_type')->setFormMapper($optionFormMapper);
    return Field\CollectionEmbedField::create("options", array(
        "entitled"            =>  false,
        "button"              =>  "button.new.option",
        "collections"         =>  array(
          "objects"             =>  "options"
        ),
        "allow"               =>  array(
          "child"               =>  false,
          "add"                 =>  true,
          "delete"              =>  true,
        ),
        "entry"               =>  array(
          "type"                =>  get_class($optionFormType)
        ),
        "prototype"           =>  array(
          "data"                =>  $option
        ),
        "sortable"            =>  array(
          "value"               =>  "position",
          "editable"            =>  true
        ),
      )
    );
  }

  /**
   * @param FormAdminEvent $formAdminEvent
   *
   * @return Field\CollectionEmbedField
   * @throws ReflectionException|Exception
   */
  protected function createCollectionLayout(FormAdminEvent $formAdminEvent): Field\CollectionEmbedField
  {
    $layoutFormMapper = new FormMapper();
    $layout = new Layout();
    $layoutFormMapper->setObject($layout)
      ->addGroup("generalInformations")
        ->add(Field\TextField::create("title", array(
              "container"  =>  array(
                "class"     =>  "animate"
              ),
              "group"       =>  array(
                'size'  => GroupFields::SIZE_COL_8
              )
            )
          )->setConstraints(array(
              new Constraints\NotNull(),
              new Constraints\Length(array(
                  "max" => 255,
                  "maxMessage" => "errors.length.max"
                )
              )
            )
          ),
        )
        ->add(
          (Field\TextField::create("keyname", array(
            "container"  =>  array(
              "class"     =>  "animate"
            ),
            "group"       =>  array(
              'size'  => GroupFields::SIZE_COL_4
            )
          )))->addConstraint(new Constraints\Length(array(
                "max" => 255,
                "maxMessage" => "errors.length.max"
              )
            )
          ),
        )
      ->end()
      ->add(Field\SymfonyField::create("position", HiddenType::class, array("entitled"=>false, "attr"=>array("data-collection-sortabled"=>""))));

    $formAdminEvent->getFormMapper()->addSubFormMapper("layouts", $layoutFormMapper);
    /** @var LayoutFormType $layoutFormType */
    $layoutFormType = $this->container->get('austral.content_block.layout_form_type')->setFormMapper($layoutFormMapper);
    return Field\CollectionEmbedField::create("layouts", array(
        "entitled"            =>  false,
        "button"              =>  "button.new.layout",
        "collections"         =>  array(
          "objects"             =>  "layouts"
        ),
        "allow"               =>  array(
          "child"               =>  false,
          "add"                 =>  true,
          "delete"              =>  true,
        ),
        "entry"               =>  array(
          "type"                =>  get_class($layoutFormType)
        ),
        "prototype"           =>  array(
          "data"                =>  $layout
        ),
        "sortable"            =>  array(
          "value"               =>  "position",
          "editable"            =>  true
        ),
      )
    );
  }

  /**
   * @param FormAdminEvent $formAdminEvent
   *
   * @return Field\CollectionEmbedField
   * @throws ReflectionException
   * @throws Exception
   */
  protected function createCollectionRestrictions(FormAdminEvent $formAdminEvent): Field\CollectionEmbedField
  {
    $contentBlockContainer = $this->container->get('austral.content_block.content_block_container');

    $contentBlockContainerSelect = array();
    $contentBlockContainerData = array();
    foreach($contentBlockContainer->getObjectsByEntity() as $entityName => $objects)
    {
      if($entityName === "Library")
      {
        $contentBlockContainerData["{$entityName}Navigation"] = "element-view-{$entityName}";
        $contentBlockContainerSelect["{$entityName}Navigation"] = array(
          $this->translator->trans("choices.restriction.all", array('%element%'=>"{$entityName}Navigation"),$formAdminEvent->getFormMapper()->getTranslateDomain())    =>  "{$entityName}Navigation:all"
        );
      }

      $contentBlockContainerData["{$entityName}"] = "element-view-{$entityName}";
      $contentBlockContainerSelect[$entityName] = array(
        $this->translator->trans("choices.restriction.all", array('%element%'=>$entityName),$formAdminEvent->getFormMapper()->getTranslateDomain())    =>  "{$entityName}:all"
      );
      foreach($objects as $object)
      {
        if($entityName === "Library")
        {
          if($object->getIsNavigationMenu())
          {
            $contentBlockContainerSelect["{$entityName}Navigation"][$object->__toString()] = "{$entityName}Navigation:{$object->getId()}";
          }
          else
          {
            $contentBlockContainerSelect[$entityName][$object->__toString()] = "{$entityName}:{$object->getId()}";
          }
        }
        else
        {
          $contentBlockContainerSelect[$entityName][$object->__toString()] = "{$entityName}:{$object->getId()}";
        }
      }
    }

    $configContainerByEntity = $this->container->get('austral.content_block.config')->getConfig('container_by_entity');
    $containerNameByEntities = array();
    foreach($contentBlockContainer->getEntitiesWithReelName() as $entityName => $classname)
    {
      $containerNameByEntities[$entityName] = $this->container->get('austral.entity_manager.component')->selectArrayComponentsContainerNameByClassname($classname);
      if(array_key_exists($entityName, $configContainerByEntity)) {
        $containerNameByEntities[$entityName] = array_merge($containerNameByEntities[$entityName], $configContainerByEntity[$entityName]);
      }

      if($entityName === "Library")
      {
        $containerNameByEntities["{$entityName}Navigation"] = $containerNameByEntities[$entityName];
        if(array_key_exists($entityName, $configContainerByEntity)) {
          $containerNameByEntities["{$entityName}Navigation"] = array_merge($containerNameByEntities["{$entityName}Navigation"], $configContainerByEntity[$entityName]);
        }
      }

    }

    $restrictionFormMapper = new FormMapper();
    $restriction = new Restriction();
    $restriction->setCondition("exclude");
    $restrictionFormMapper->setObject($restriction)
      ->addGroup("generalInformations")
        ->add(Field\SelectField::create("value",
            $contentBlockContainerSelect,
            array(
              "entitled"  =>  false,
              "group"       =>  array(
                'size'  => GroupFields::SIZE_COL_5
              ),
              "attr"        =>  array(
                "data-view-by-choices-parent"   =>  ".collection-embed-element",
                'data-view-by-choices-regex'    =>  "(\w+):.*",
                'data-view-by-choices'      =>  json_encode($contentBlockContainerData)
              )
            )
          )->addConstraint(new Constraints\NotNull())
        )
        ->add(Field\ChoiceField::create("condition", array(
            "choices.restriction.exclude"     =>  array(
              "value"   =>  "exclude",
            ),
            "choices.restriction.include"         =>  array(
              "value"   =>  "include",
              "styles"  =>  array(
                "--element-choice-current-background:var(--color-blue-20)",
                "--element-choice-current-color:var(--color-blue-100)",
                "--element-choice-hover-color:var(--color-blue-100)"
              )
            ),
          ), array(
            "entitled"      =>  false,
            "choice_style"  =>  "full",
            "group"       =>  array(
              'size'  => GroupFields::SIZE_COL_4
            )
          )
        )->addConstraint(new Constraints\NotNull()))
      ->end()
      ->add(Field\SymfonyField::create("position", HiddenType::class, array("entitled" => false, "attr"=>array("data-collection-sortabled"=>""))));

    $group = $restrictionFormMapper->addGroup("generalInformations")
      ->addGroup("containerName")
      ->setSize(GroupFields::SIZE_COL_3)
      ->setDirection(GroupFields::DIRECTION_COLUMN);
    foreach($containerNameByEntities as $entityName => $containerNames)
    {
      $containerNamesSelect = array($this->translator->trans("choices.restriction.containerName.all", array('%element%'=>$entityName),$formAdminEvent->getFormMapper()->getTranslateDomain())    =>  "all");
      foreach($containerNames as $containerName)
      {
        $containerNamesSelect[$containerName] = $containerName;
      }
      $group->add(Field\SelectField::create("containerName{$entityName}",
            $containerNamesSelect,
            array(
              "entitled"  =>  false,
              "getter"    =>  function(Restriction $object) {
                return $object->getContainerName();
              },
              "setter"    =>  function(Restriction $object, $value) use($entityName) {
                if(strpos($object->getValue(), $entityName) !== false)
                {
                  $object->setContainerName($value);
                }
              },
              "container" =>  array('class'=>"view-element-by-choices element-view-{$entityName}")
            )
          )->addConstraint(new Constraints\NotNull())
        )

      ->end();
    }

    $formAdminEvent->getFormMapper()->addSubFormMapper("restrictions", $restrictionFormMapper);
    /** @var RestrictionFormType $restrictionFormType */
    $restrictionFormType = $this->container->get('austral.content_block.restriction_form_type')->setFormMapper($restrictionFormMapper);
    return Field\CollectionEmbedField::create("restrictions", array(
        "entitled"            =>  false,
        "button"              =>  "button.new.restriction",
        "collections"         =>  array(
          "objects"             =>  "restrictions"
        ),
        "allow"               =>  array(
          "child"               =>  false,
          "add"                 =>  true,
          "delete"              =>  true,
        ),
        "entry"               =>  array(
          "type"                =>  get_class($restrictionFormType)
        ),
        "prototype"           =>  array(
          "data"                =>  $restriction
        ),
        "sortable"            =>  array(
          "value"               =>  "position",
          "editable"            =>  true
        ),
      )
    );
  }

  /**
   * @param FormAdminEvent $formAdminEvent
   *
   * @return Field\CollectionEmbedField|null
   * @throws ReflectionException
   */
  protected function createCollectionEditorComponentTypeForms(FormAdminEvent $formAdminEvent): ?Field\CollectionEmbedField
  {
    /** @var EditorComponentInterface $editorComponent */
    $editorComponent = $formAdminEvent->getFormMapper()->getObject();

    /** @var ContentBlockConfiguration $contentBlockConfig */
    $contentBlockConfiguration = $this->container->get('austral.content_block.config');
    $typeValues = $contentBlockConfiguration->getConfig("type_values", array());

    $formMapperDefault = new FormMapper();
    $formAdminEvent->getFormMapper()->addSubFormMapper("editorComponentTypes", $formMapperDefault);

    $editorComponentTypeDefault = new EditorComponentType();
    $editorComponentTypeDefault->setEditorComponent($editorComponent);
    $formMapperDefault->setObject($editorComponentTypeDefault);

    /** @var EditorComponentTypeFormType $typeValueFormType */
    $editorComponentTypeFormType = $this->container->get('austral.content_block.editor_component_type_form_type');
    $editorComponentTypeFormType->setFormMapper($formMapperDefault);

    $collectionsChoices = array();
    $collectionFormsChildren = array();

    $this->graphicItemBundleEnabled = (bool) $this->container->get("kernel")->getBundle("AustralGraphicItemsBundle");
    if($this->graphicItemBundleEnabled)
    {
      $typeValues["graphicItem"] = array(
        "entitled"      => "choices.collections.blockType.graphicItem",
        "picto"         => "austral-picto-cog",
        "allow_child"   => false,
        "can_has_link"  => false
      );
    }

    foreach($typeValues as $choiceKey => $choice)
    {
      $collectionFormFieldname = "editorComponentTypes_{$choiceKey}";
      $collectionsChoices[$collectionFormFieldname] = $choice;

      $formMapper = new FormMapper();

      $editorComponentTypeByChoice = clone $editorComponentTypeDefault;
      $editorComponentTypeByChoice->setId('__name__');
      $editorComponentTypeByChoice->setType($choiceKey);
      $editorComponentTypeByChoice->setEntitled($choiceKey);
      $editorComponentTypeByChoice->setParentId("__parentId__");
      $formMapper->setObject($editorComponentTypeByChoice);

      $this->generateEditorComponentTypeForm($choiceKey, $formMapper, $choice);

      $formMapperDefault->addSubFormMapper($collectionFormFieldname, $formMapper);
      $editorComponentTypeFormType->addFormMappers($choiceKey, $formMapper);
      $collectionFormsChildren[$collectionFormFieldname] = Field\CollectionEmbedField::create($collectionFormFieldname, array(
          "entitled"            =>  false,
          "title"               =>  "choices.collections.blockType.{$choiceKey}",
          "button"              =>  "button.new.contentBlock.typeValueChild",
          "master_children"     =>  true,

          "allow"               =>  array(
            "child"         =>  $choice["allow_child"],
            "add"           =>  true,
            "delete"        =>  true,
          ),

          "prototype"           =>  array(
            "data"                =>  $editorComponentTypeByChoice
          ),
          "entry"               =>  array(
            "type"                =>  get_class($editorComponentTypeFormType),
            "attr"                =>  array(
              "formMapperKey"       =>  $collectionFormFieldname
            ),
          ),
          "sortable"            =>  array(
            "value"               =>  "position",
            "editable"            =>  true
          ),
          "getter"              =>  function(EditorComponentInterface $editorComponent) use ($choiceKey){
            return $editorComponent->getEditorComponentTypesByTypes($choiceKey);
          },
          "setter"              =>  function(EditorComponentInterface $editorComponent, $editorComponentTypes) use ($choiceKey){
            $editorComponentTypesIds = array();
            if(count($editorComponentTypes) > 0)
            {
              /** @var EditorComponentTypeInterface $editorComponentType */
              foreach($editorComponentTypes as $editorComponentType)
              {
                if(!$editorComponentType->getKeyname()) {
                  $editorComponentType->setKeyname($editorComponentType->getEntitled());
                }
                $editorComponentType->setType($choiceKey);
                $editorComponent->addEditorComponentType($editorComponentType);
                $editorComponentTypesIds[$editorComponentType->getId()] = $editorComponentType->getId();
              }
            }
            if($editorComponentTypesCurrent = $editorComponent->getEditorComponentTypesByTypes($choiceKey))
            {
              /** @var EditorComponentTypeInterface $editorComponentType */
              foreach($editorComponentTypesCurrent as $editorComponentType)
              {
                if(!array_key_exists($editorComponentType->getId(), $editorComponentTypesIds))
                {
                  $editorComponent->removeEditorComponentType($editorComponentType);
                }
              }
            }
          },
        )
      );
    }

    if(count($collectionFormsChildren) > 0)
    {
      $collectionForms = Field\CollectionEmbedField::create("editorComponentTypes", array(
        "entitled"            =>  false,
        "button"              =>  "button.new.contentBlock.typeValue",
        "container"           =>  array(
          "class"               =>  "child-color"
        ),
        "collections"         =>  array(
          "choices"             =>  $collectionsChoices
        ),
        "allow"               =>  array(
          "child"               =>  true
        ),
        "sortable"            =>  array(
          "value"               =>  "position",
          "editable"            =>  true
        ),
        "between_insert"      =>  true,
      ));

      $collectionForms->addCollectionsForms($collectionFormsChildren);
      return $collectionForms;
    }
    return null;
  }


  /**
   * @param string $choiceKey
   * @param FormMapper $formMapper
   * @param array $choiceParameters
   *
   * @throws ReflectionException
   * @throws Exception
   */
  protected function generateEditorComponentTypeForm(string $choiceKey, FormMapper $formMapper, array $choiceParameters)
  {
    $formMapper->add(Field\SymfonyField::create("parentId", HiddenType::class, array("entitled"=>false)))
      ->add(Field\SymfonyField::create("id", HiddenType::class, array("entitled"=>false)))
      ->add(Field\SymfonyField::create("position", HiddenType::class, array("entitled"=>false, "attr"=>array("data-collection-sortabled"=>""))));


    $formMapper->addGroup("layoutViewChoice")
        ->setAttr(array(
          "class" =>  "layout-view-choice layout-view-choice-view"
        ))
        ->add(Field\SelectField::create("layoutChoiceViewKeyname", array(), array(
              "entitled"    =>  "fields.layoutChoiceViewKeyname.entitled",
              "multiple"    =>  true,
              "container"   =>  array(
                "class"       =>  "animate"
              ),
              "getter"      =>  function(EditorComponentTypeInterface $object){
                $choices = array();
                foreach($object->getParameterByKey("layoutChoiceViewKeyname", array()) as $choice)
                {
                  $choices[$choice] = $choice;
                }
                return $choices;
              },
              "setter"      =>  function(EditorComponentTypeInterface $object, $values) {
                $choices = array();
                foreach($values as $value)
                {
                  $choices[$value] = $value;
                }
                return $object->setParameterByKey("layoutChoiceViewKeyname", $choices);
              },
              "select-options"   =>  array(
                "tag"              =>  true,
                "searchEnabled"    =>  false
              )
            )
          )
        )
      ->end();

    if($choiceKey !== "choice" && $choiceKey !== "graphicItem" && $choiceKey !== "object" && $choiceKey !== "switch" && $choiceKey !== "separate")
    {
      $formMapper->addGroup("classCss")
        ->add(Field\SelectField::create("classCss", array(), array(
              "entitled"    =>  "fields.cssClass.entitled",
              "multiple"    =>  true,
              "container"   =>  array(
                "class"       =>  "animate"
              ),
              "getter"      =>  function(EditorComponentTypeInterface $object){
                $choices = array();
                foreach($object->getParameterByKey("classCss", array()) as $choice)
                {
                  $choices[$choice] = $choice;
                }
                return $choices;
              },
              "setter"      =>  function(EditorComponentTypeInterface $object, $values) {
                $choices = array();
                foreach($values as $value)
                {
                  $choices[$value] = $value;
                }
                return $object->setParameterByKey("classCss", $choices);
              },
              "select-options"   =>  array(
                "tag"              =>  true,
                "searchEnabled"    =>  false
              )
            )
          )
        )
      ->end();
    }
    if($choiceKey !== "separate")
    {
      $formMapper->addGroup("generalInformations")
        ->add(Field\TextField::create("entitled", array(
                "container"  =>  array(
                  "class" =>  "animate"
                ),
                "group"       =>  array(
                  'size'  => GroupFields::SIZE_COL_8
                )
              )
            )->setConstraints(array(
              new Constraints\NotNull(),
              new Constraints\Length(array(
                  "max" => 255,
                  "maxMessage" => "errors.length.max"
                )
              )
            )
          ),
        )->add(Field\TextField::create("keyname", array(
              "container"  =>  array(
                "class" =>  "animate"
              ),
              "group"       =>  array(
                'size'  => GroupFields::SIZE_COL_4
              )
            )
          )->addConstraint(
              new Constraints\Length(array(
                "max" => 255,
                "maxMessage" => "errors.length.max"
              )
            )
          ),
        )
      ->end();
    }
    else
    {
      $formMapper->getObject()
        ->setEntitled("Separate")
        ->setKeyname('separate-'.AustralTools::random(4));
      $formMapper->addGroup("generalInformations")
        ->add(Field\SymfonyField::create("entitled", HiddenType::class))
        ->add(Field\SymfonyField::create("keyname", HiddenType::class))
        ->end();
    }
    if($choiceKey === "choice")
    {
      $formMapper->addGroup("choices")
        ->add(Field\SelectField::create("choices", array(), array(
              "entitled"          =>  "fields.addChoice.entitled",
              "multiple"          =>  true,
              "select-options"    =>  array(
                "tag"               =>  true,
                "searchEnabled"     =>  false
              ),
              "container"         =>  array(
                "class"             =>  "animate"
              ),
              'required' => true,
              "getter"    =>  function(EditorComponentTypeInterface $editorComponentType) {
                $values = $editorComponentType->getParameterByKey("choices", array());
                $finaleValues = array();
                foreach($values as $key => $value)
                {
                  $finaleValues[$value] = $key;
                }
                return $finaleValues;
              },
              "setter"    =>  function(EditorComponentTypeInterface $editorComponentType, $values) {
                $finaleValues = array();
                foreach($values as $value)
                {
                  $finaleValues[u($value)->snake()->toString()] = $value;
                }
                return $editorComponentType->setParameterByKey("choices", $finaleValues);
              },
              "group"       =>  array(
                'size'  => GroupFields::SIZE_COL_12
              )
            )
          )
        )
      ->end();
      $group = $formMapper->addGroup("configuration");
      $group->add(Field\SwitchField::create("isRequired", array(
            'required'    =>  true,
            "container"   =>  array(
              "class"       =>  "side-by-side"
            ),
            "getter"      =>  function(EditorComponentTypeInterface $editorComponentType) {
              return $editorComponentType->getParameterByKey("isRequired", false);
            },
            "setter"      =>  function(EditorComponentTypeInterface $editorComponentType, $value) {
              return $editorComponentType->setParameterByKey("isRequired", $value);
            },
            "group"       =>  array(
              'size'  => GroupFields::SIZE_COL_12
            )
          )
        )
      );
    }
    elseif($choiceKey === "object" || $choiceKey === "objects")
    {
      $mapping = $this->container->get("austral.entity.mapping");
      $objectsContentBlockChoice = array();

      /** @var EntityMapping $entityMapping */
      foreach($mapping->getEntitiesMapping() as $entityMapping)
      {
        /** @var ObjectContentBlockMapping $objectContentBlock */
        if($objectContentBlock = $entityMapping->getEntityClassMapping(ObjectContentBlockMapping::class))
        {
          $objectsContentBlockChoice[$objectContentBlock->getName()] = $entityMapping->entityClass;
        }
      }
      $formMapper->addGroup("choices")
          ->add(Field\SelectField::create("choices", $objectsContentBlockChoice, array(
              "entitled"          =>  "fields.addChoice.entitled",
              "multiple"          =>  false,
              "select-options"    =>  array(
                "tag"               =>  false,
                "searchEnabled"     =>  false
              ),
              "container"         =>  array(
                "class"             =>  "animate"
              ),
              'required' => true,
              "getter"    =>  function(EditorComponentTypeInterface $editorComponentType) {
                return $editorComponentType->getParameterByKey("entityClass", null);
              },
              "setter"    =>  function(EditorComponentTypeInterface $editorComponentType, $value) {
                return $editorComponentType->setParameterByKey("entityClass", $value);
              },
              "group"       =>  array(
                'size'  => GroupFields::SIZE_COL_12
              )
            )
          )
        )
      ->end();
      $group = $formMapper->addGroup("configuration");
      $group->add(Field\SwitchField::create("isRequired", array(
            'required'    =>  true,
            "container"   =>  array(
              "class"       =>  "side-by-side"
            ),
            "getter"      =>  function(EditorComponentTypeInterface $editorComponentType) {
              return $editorComponentType->getParameterByKey("isRequired", false);
            },
            "setter"      =>  function(EditorComponentTypeInterface $editorComponentType, $value) {
              return $editorComponentType->setParameterByKey("isRequired", $value);
            },
            "group"       =>  array(
              'size'  => GroupFields::SIZE_COL_12
            )
          )
        )
      );
    }
    elseif($choiceKey !== "separate")
    {
      if($choiceKey !== "group" && $choiceKey !== "list")
      {
        if($choiceKey == "title")
        {
          /** @var ContentBlockConfiguration $contentBlockConfig */
          $contentBlockConfiguration = $this->container->get('austral.content_block.config');
          $choiceTags = $contentBlockConfiguration->getConfig("title_tag_values", array());

          $formMapper->addGroup("choice-tags")
            ->add(Field\ChoiceField::create("tags", $choiceTags, array(
                  "entitled"        =>  false,
                  "choices_styles"  =>  array(
                    "--element-choice-current-background:var(color-main-20)",
                    "--element-choice-current-color:var(--color-main-100)",
                    "--element-choice-hover-color:var(--color-main-100)"
                  ),
                  "choice_style"    =>  "inline",
                  "multiple"        =>  true,
                  'required'        =>  true,
                  "getter"          =>  function(EditorComponentTypeInterface $editorComponentType) use ($choiceTags){
                    return $editorComponentType->getParameterByKey("tags", $choiceTags);
                  },
                  "setter"          =>  function(EditorComponentTypeInterface $editorComponentType, $value) {
                    return $editorComponentType->setParameterByKey("tags", $value);
                  },
                  "group"       =>  array(
                    'size'  => GroupFields::SIZE_COL_12
                  )
                )
              )
            )
          ->end();
        }
        elseif($choiceKey == "text")
        {
          /** @var ContentBlockConfiguration $contentBlockConfig */
          $contentBlockConfiguration = $this->container->get('austral.content_block.config');
          $choiceTags = $contentBlockConfiguration->getConfig("text_type_values", array());
          $formMapper->addGroup("choice-type")
            ->add(Field\SelectField::create("type", $choiceTags, array(
                "container"   =>  array(
                  "class"       =>  "animate"
                ),
                'required'    =>  true,
                "getter"      =>  function(EditorComponentTypeInterface $editorComponentType) {
                  return $editorComponentType->getParameterByKey("type", null);
                },
                "setter"      =>  function(EditorComponentTypeInterface $editorComponentType, $value) {
                  return $editorComponentType->setParameterByKey("type", $value);
                },
                "group"       =>  array(
                  'size'  => GroupFields::SIZE_COL_12
                )
              )
            )
          )
          ->add(Field\TextField::create("defaultValue", array(
                "container"   =>  array(
                  "class"       =>  "animate"
                ),
                'required'    =>  false,
                "getter"      =>  function(EditorComponentTypeInterface $editorComponentType) {
                  return $editorComponentType->getParameterByKey("defaultValue", null);
                },
                "setter"      =>  function(EditorComponentTypeInterface $editorComponentType, $value) {
                  return $editorComponentType->setParameterByKey("defaultValue", $value);
                },
                "group"       =>  array(
                  'size'  => GroupFields::SIZE_COL_12
                )
              )
            )
          )
          ->end();
        }
        elseif($choiceKey == "switch")
        {
          $formMapper->addGroup("default-value")
          ->add(Field\SwitchField::create("defaultEnabled", array(
                'required'    =>  true,
                "container"     =>  array(
                  "class"         =>  "side-by-side"
                ),
                "getter"      =>  function(EditorComponentTypeInterface $editorComponentType) {
                  return $editorComponentType->getParameterByKey("defaultEnabled", null);
                },
                "setter"      =>  function(EditorComponentTypeInterface $editorComponentType, $value) {
                  return $editorComponentType->setParameterByKey("defaultEnabled", $value);
                },
                "group"       =>  array(
                  'size'  => GroupFields::SIZE_COL_6
                )
              )
            )
          )
          ->end();
        }

        $colGroup = GroupFields::SIZE_COL_6;
        if($choiceKey == "image")
        {
          $colGroup = GroupFields::SIZE_COL_4;
        }

        if($choiceKey !== "switch")
        {
          $group = $formMapper->addGroup("configuration");
          $group->add(Field\SwitchField::create("isRequired", array(
                'required'      =>  true,
                "container"     =>  array(
                  "class"         =>  "side-by-side"
                ),
                "getter"        =>  function(EditorComponentTypeInterface $editorComponentType) {
                  return $editorComponentType->getParameterByKey("isRequired", false);
                },
                "setter"        =>  function(EditorComponentTypeInterface $editorComponentType, $value) {
                  return $editorComponentType->setParameterByKey("isRequired", $value);
                },
                "group"       =>  array(
                  'size'  => $colGroup
                )
              )
            )
          );
        }

        if($this->graphicItemBundleEnabled && $choiceKey === "button")
        {
          $group->add(Field\SwitchField::create("hasPicto", array(
                'required'      =>  true,
                "container"     =>  array(
                  "class"         =>  "side-by-side"
                ),
                "getter"        =>  function(EditorComponentTypeInterface $editorComponentType) {
                  return $editorComponentType->getParameterByKey("hasPicto", false);
                },
                "setter"        =>  function(EditorComponentTypeInterface $editorComponentType, $value) {
                  return $editorComponentType->setParameterByKey("hasPicto", $value);
                },
                "group"       =>  array(
                  'size'  => $colGroup
                )
              )
            )
          );
        }

        if($choiceParameters["can_has_link"])
        {
          $group->add(Field\SwitchField::create("canHasLink",array(
                'required'      =>  true,
                "container"     =>  array(
                  "class"         =>  "side-by-side"
                ),
                "group"       =>  array(
                  'size'  => $colGroup
                )
              )
            )
          );
        }
        elseif($choiceKey == "textarea")
        {
          $group->add(Field\SwitchField::create("isWysiwyg", array(
                'required'      => true,
                "container"     =>  array(
                  "class"         =>  "side-by-side"
                ),
                "getter"        =>  function(EditorComponentTypeInterface $editorComponentType) {
                  return $editorComponentType->getParameterByKey("isWysiwyg", true);
                },
                "setter"        =>  function(EditorComponentTypeInterface $editorComponentType, $value) {
                  return $editorComponentType->setParameterByKey("isWysiwyg", $value);
                },
                "group"       =>  array(
                  'size'  => $colGroup
                )
              )
            )
          );
        }
        elseif($choiceKey == "movie")
        {
          $group->add(Field\SwitchField::create("isIframe", array(
                'required'        => true,
                "container"       =>  array(
                  "class"           =>  "side-by-side"
                ),
                "getter"          =>  function(EditorComponentTypeInterface $editorComponentType) {
                  return $editorComponentType->getParameterByKey("isIframe", true);
                },
                "setter"          =>  function(EditorComponentTypeInterface $editorComponentType, $value) {
                  return $editorComponentType->setParameterByKey("isIframe", $value);
                },
                "group"       =>  array(
                  'size'  => $colGroup
                )
              )
            )
          );
        }
        if($choiceKey === "image" || $choiceKey === "file")
        {
            $group->add(Field\SwitchField::create("viewEntitled", array(
                'required'      =>  true,
                "container"     =>  array(
                  "class"         =>  "side-by-side"
                ),
                "getter"        =>  function(EditorComponentTypeInterface $editorComponentType) {
                  return $editorComponentType->getParameterByKey("viewEntitled", false);
                },
                "setter"        =>  function(EditorComponentTypeInterface $editorComponentType, $value) {
                  return $editorComponentType->setParameterByKey("viewEntitled", $value);
                },
                "group"       =>  array(
                  'size'  => $colGroup
                )
              )
            )
          );
        }
      }
      else
      {
        if(!$formMapper->getObject()->getBlockDirection())
        {
          $formMapper->getObject()->setBlockDirection(GroupFields::DIRECTION_ROW);
        }
        $group = $formMapper->addGroup("configuration");
        $group->add(Field\ChoiceField::create("blockDirection", array(
            "choices.blockDirection.column"     =>  array(
              "value"   =>  GroupFields::DIRECTION_COLUMN,
              "styles"  =>  array(
                "--element-choice-current-background:var(--color-blue-20)",
                "--element-choice-current-color:var(--color-blue-100)",
                "--element-choice-hover-color:var(--color-blue-100)"
              )
            ),
            "choices.blockDirection.row"         =>  array(
              "value"   =>  GroupFields::DIRECTION_ROW,
              "styles"  =>  array(
                "--element-choice-current-background:var(--color-blue-20)",
                "--element-choice-current-color:var(--color-blue-100)",
                "--element-choice-hover-color:var(--color-blue-100)"
              )
            ),
          ), array(
              "choice_style"  =>  "full",
              "entitled" => false,
              "group"       =>  array(
                'size'  => GroupFields::SIZE_COL_8
              )
            )
          )->setConstraints(array(new Constraints\NotNull()))
        );
        $group->add(Field\SwitchField::create("viewEntitled", array(
              'required'      =>  true,
              "container"     =>  array(
                "class"         =>  "side-by-side"
              ),
              "getter"        =>  function(EditorComponentTypeInterface $editorComponentType) {
                return $editorComponentType->getParameterByKey("viewEntitled", false);
              },
              "setter"        =>  function(EditorComponentTypeInterface $editorComponentType, $value) {
                return $editorComponentType->setParameterByKey("viewEntitled", $value);
              },
              "group"       =>  array(
                'size'  => GroupFields::SIZE_COL_4
              )
            )
          )
        );

        if($choiceKey === "list")
        {
          $formMapper->addGroup("minMax")
            ->add(Field\IntegerField::create("listElementMin", array(
                  "container"  =>  array(
                    "class" =>  "animate"
                  ),
                  "group"       =>  array(
                    'size'  => GroupFields::SIZE_COL_8
                  ),
                  "getter"        =>  function(EditorComponentTypeInterface $editorComponentType) {
                    return $editorComponentType->getParameterByKey("min", null);
                  },
                  "setter"        =>  function(EditorComponentTypeInterface $editorComponentType, $value) {
                    return $editorComponentType->setParameterByKey("min", $value);
                  },
                )
              ),
            )->add(Field\IntegerField::create("listElementMax", array(
                  "container"  =>  array(
                    "class" =>  "animate"
                  ),
                  "group"       =>  array(
                    'size'  => GroupFields::SIZE_COL_8
                  ),
                  "getter"        =>  function(EditorComponentTypeInterface $editorComponentType) {
                    return $editorComponentType->getParameterByKey("max", null);
                  },
                  "setter"        =>  function(EditorComponentTypeInterface $editorComponentType, $value) {
                    return $editorComponentType->setParameterByKey("max", $value);
                  },
                )
              ),
            )
          ->end();
        }


      }
    }
  }

  /**
   * @param FormAdminEvent $formAdminEvent
   *
   * @throws Exception
   */
  protected function formUpdateBefore(FormAdminEvent $formAdminEvent)
  {
    /** @var EditorComponent|EntityInterface $object */
    $object = $formAdminEvent->getFormMapper()->getObject();
    if(!$object->getKeyname()) {
      $object->setKeyname($object->getName());
    }

    if(!$object->getPosition())
    {
      $object->setPosition($this->container->get('austral.entity_manager.editor_component')->countAll()+1);
    }

  }
}