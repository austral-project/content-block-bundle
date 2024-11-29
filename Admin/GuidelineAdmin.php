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

use App\Entity\Austral\ContentBlockBundle\Component;
use App\Entity\Austral\ContentBlockBundle\EditorComponent;
use Austral\AdminBundle\Admin\Admin;
use Austral\AdminBundle\Admin\AdminModuleInterface;
use Austral\AdminBundle\Admin\Event\ActionAdminEvent;
use Austral\AdminBundle\Admin\Event\FormAdminEvent;
use Austral\AdminBundle\Admin\Event\ListAdminEvent;
use Austral\ContentBlockBundle\Entity\Guideline;
use Austral\ContentBlockBundle\Entity\Interfaces\GuidelineInterface;
use Austral\ContentBlockBundle\Field\ContentBlockField;
use Austral\EntityBundle\ORM\AustralQueryBuilder;
use Austral\FormBundle\Mapper\GroupFields;
use Austral\FormBundle\Field as Field;
use Austral\FormBundle\Mapper\Fieldset;
use Austral\FormBundle\Mapper\FormMapper;

use Austral\ContentBlockBundle\Entity\Interfaces\LibraryInterface;

use Austral\EntityBundle\Entity\EntityInterface;

use Austral\ListBundle\Column as Column;
use Austral\ListBundle\Column\Action;
use Austral\ListBundle\DataHydrate\DataHydrateORM;

use Austral\NotifyBundle\Notification\Push;
use Doctrine\ORM\QueryBuilder;

/**
 * Guideline Admin.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class GuidelineAdmin extends Admin implements AdminModuleInterface
{

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
   * @param ActionAdminEvent $actionAdminEvent
   *
   * @throws \ReflectionException
   */
  public function view(ActionAdminEvent $actionAdminEvent)
  {
    $guidelines = $this->container->get('austral.entity_manager.guideline')->selectAllIndexBy("id", null, function(AustralQueryBuilder $australQueryBuilder){
      $australQueryBuilder->leftJoin("root.editorComponent", "editorComponent")->addSelect("editorComponent");
    });

    $guidelinesByCateg = array();
    foreach($this->container->get('austral.content_block.config')->get("guideline.categories") as $value)
    {
      $guidelinesByCateg[$value] = array();
    }

    $guidelinesParameters = $actionAdminEvent->getAdminHandler()->getSession()->get("guidelines-parameters", array());
    /** @var Guideline $guideline */
    foreach($guidelines as $guideline)
    {
      if(!array_key_exists($guideline->getCategory(), $guidelinesByCateg))
      {
        $guidelinesByCateg[$guideline->getCategory()] = array();
      }
      if($guideline->getEditorComponent()->getThemes() || $guideline->getEditorComponent()->getLayouts() || $guideline->getEditorComponent()->getOptions())
      {
        $guidelinesByCateg[$guideline->getCategory()][] = $guideline;
        if(!array_key_exists($guideline->getId(), $guidelinesParameters))
        {
          $guidelinesParameters[$guideline->getId()] = array();
        }

        if($guideline->getEditorComponent()->getThemes() && (!array_key_exists("theme", $guidelinesParameters[$guideline->getId()]) || !$guidelinesParameters[$guideline->getId()]["theme"]))
        {
          $guidelinesParameters[$guideline->getId()]["theme"] = "";
          foreach($guideline->getEditorComponent()->getThemes() as $theme)
          {
            if($theme->getKeyname() === "default")
            {
              $guidelinesParameters[$guideline->getId()]["theme"] = $theme->getId();
            }
          }
        }
        if($guideline->getEditorComponent()->getLayouts() && (!array_key_exists("layout", $guidelinesParameters[$guideline->getId()]) || !$guidelinesParameters[$guideline->getId()]["layout"]))
        {
          $guidelinesParameters[$guideline->getId()]["layout"] = "";
          foreach($guideline->getEditorComponent()->getLayouts() as $layout)
          {
            if($layout->getKeyname() === "default")
            {
              $guidelinesParameters[$guideline->getId()]["layout"] = $layout->getId();
            }
          }
        }
        if($guideline->getEditorComponent()->getOptions() && (!array_key_exists("option", $guidelinesParameters[$guideline->getId()]) || !$guidelinesParameters[$guideline->getId()]["option"]))
        {
          $guidelinesParameters[$guideline->getId()]["option"] = "";
          foreach($guideline->getEditorComponent()->getOptions() as $option)
          {
            if($option->getKeyname() === "default")
            {
              $guidelinesParameters[$guideline->getId()]["option"] = $option->getId();
            }
          }
        }
      }
    }
    foreach($guidelinesByCateg as $key => $value)
    {
      if(!$value)
      {
        unset($guidelinesByCateg[$key]);
      }
    }

    if($actionAdminEvent->getAdminHandler()->getRequest()->getMethod() === "POST")
    {
      $reloadElements = array();
      $guidelineParameters = $actionAdminEvent->getAdminHandler()->getRequest()->get("guideline");
      foreach($guidelineParameters as $key => $values)
      {
        $reloadElements[] = "#guideline-{$key}";
        $guidelinesParameters[$key] = $values;
      }
      $actionAdminEvent->getAdminHandler()->getSession()->set("guidelines-parameters", $guidelinesParameters);
      if($this->container->has('austral.notify.push'))
      {
        /** @var Push $push */
        $push = $this->container->get('austral.notify.push');
        $push->add(Push::TYPE_MERCURE, array('topics'=>array("guidelines"), "values" => array(
          'type'            =>  "refresh",
          "url"             =>  "current",
          "reloadElements"  =>  $reloadElements,
        )))->push(true, false);
      }
    }

    $actionAdminEvent->getAdminHandler()->getTemplateParameters()
      ->addParameters("guidelinesByCateg", $guidelinesByCateg)
      ->addParameters("guidelinesParameters", $guidelinesParameters)
      ->addParameters("guidelineSizes",$this->container->get('austral.content_block.config')->getConfig("guideline")["sizes"])
      ->setPath("@AustralContentBlock/Admin/Guideline/view.html.twig");
  }

  /**
   * @param ListAdminEvent $listAdminEvent
   */
  public function configureListMapper(ListAdminEvent $listAdminEvent)
  {
    $actions = new Action("guideline-view", "actions.guidelineComposants",
      $this->module->generateUrl("view", array("id" => "list")),
      null,
      array(
        "attr"                =>  array(
          "target"    =>  "_blank",
        ),
        "translateParameters" => array(
          "module_name"     =>  $this->module->translateSingular(),
          "module_gender"   =>  $this->module->translateGenre()
        )
      )
    );
    $listAdminEvent->getListMapper()
      ->addAction($actions, 4)
      ->buildDataHydrate(function(DataHydrateORM $dataHydrate) {
        $dataHydrate->addQueryBuilderPaginatorClosure(function(QueryBuilder $queryBuilder) {
          return $queryBuilder->orderBy("root.position", "ASC");
        });
      })
      ->addColumn(new Column\Value("name"))
      ->addColumn(new Column\Value("editorComponent"))
      ->addColumn(new Column\Value("category"));

  }

  /**
   * @param FormAdminEvent $formAdminEvent
   *
   * @throws \Exception
   */
  public function configureFormMapper(FormAdminEvent $formAdminEvent)
  {
    $categories = array();
    foreach($this->container->get('austral.content_block.config')->get("guideline.categories") as $value)
    {
      $categories["choices.editor_component.guidelineCategory.{$value}"] = $value;
    }

    $formAdminEvent->getFormMapper()->addFieldset("fieldset.dev.config")
        ->setCollapse(true)
        ->setIsView($this->container->get("security.authorization_checker")->isGranted("ROLE_ROOT"))
        ->add(Field\TextField::create("keyname", array('required' => false)))
      ->end()
      ->addFieldset("fieldset.generalInformation")
        ->add(Field\TextField::create("name", array('required' => false)))
        ->add(Field\EntityField::create("editorComponent", EditorComponent::class, array(
          'required'  =>  true,
          "fieldOptions"  =>  array(
            "attr"  =>  array(
              "data-value-null"  =>  true
            )
          )
        )))
        ->add(Field\SelectField::create("category", $categories, array("required"=>true)));

    /** @var GuidelineInterface $guideline */
    $guideline = $formAdminEvent->getFormMapper()->getObject();
    if($guideline->getEditorComponent()?->getKeyname())
    {
      $formAdminEvent->getFormMapper()->addFieldset("fieldset.guidelineValue")
        ->add(ContentBlockField::create("master", array(
          "hydrate_auto"  =>array(
            $guideline->getEditorComponent()->getKeyname()
          ),
          "sortable"  =>  array(
            "editable"    =>  false
          ),
          "allow"     =>  array(
            "child"                 =>  false,
            "add"                   =>  false,
            "delete"                =>  false,
          )
        )))
        ->end();
    }

  }

  /**
   * @param FormAdminEvent $formAdminEvent
   *
   * @throws \Exception
   */
  protected function formUpdateBefore(FormAdminEvent $formAdminEvent)
  {
    /** @var \App\Entity\Austral\ContentBlockBundle\Guideline $object */
    $object = $formAdminEvent->getFormMapper()->getObject();

    if(!$object->getName()) {
      $object->setName($object->getEditorComponent()->getName());
    }

    if(!$object->getKeyname()) {
      $object->setKeyname($object->getName());
    }
    if(!$object->getPosition())
    {
      $object->setPosition($formAdminEvent->getCurrentModule()->getEntityManager()->countAll()+1);
    }
    $componentEntityManager = $this->container->get('austral.entity_manager.component');
    /** @var Component $component */
    foreach($object->getComponentsByContainerName("master") as $component)
    {
      if($component->getEditorComponent()->getId() !== $object->getEditorComponent()->getId()) {
        $componentEntityManager->delete($component, false);
        $object->removeComponents("master", $component);
      }
    }

    if($this->container->has('austral.notify.push'))
    {
      $reloadElements = array();
      $reloadElements[] = "#guideline-{$object->getId()}";
      /** @var Push $push */
      $push = $this->container->get('austral.notify.push');
      $push->add(Push::TYPE_MERCURE, array('topics'=>array("guidelines"), "values" => array(
        'type'            =>  "refresh",
        "url"             =>  "current",
        "reloadElements"  =>  $reloadElements,
      )))->push(true, false);
    }

  }
}