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

use Austral\AdminBundle\Admin\Admin;
use Austral\AdminBundle\Admin\AdminModuleInterface;
use Austral\AdminBundle\Admin\Event\FormAdminEvent;
use Austral\AdminBundle\Admin\Event\ListAdminEvent;

use Austral\ContentBlockBundle\Entity\Interfaces\LibraryInterface;
use Austral\ContentBlockBundle\Form\Austral\LibraryForm;

use Austral\EntityBundle\Entity\EntityInterface;

use Austral\ListBundle\Column as Column;
use Austral\ListBundle\DataHydrate\DataHydrateORM;

use Doctrine\ORM\QueryBuilder;

/**
 * Library Admin.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class LibraryAdmin extends Admin implements AdminModuleInterface
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
   * @param ListAdminEvent $listAdminEvent
   */
  public function configureListMapper(ListAdminEvent $listAdminEvent)
  {
    $listAdminEvent->getListMapper()
      ->buildDataHydrate(function(DataHydrateORM $dataHydrate) {
        $dataHydrate->addQueryBuilderClosure(function(QueryBuilder $queryBuilder) {
          $queryBuilder->where("root.isNavigationMenu = :isNavigationMenu")
            ->setParameter("isNavigationMenu", false);
        });
      })
      ->addColumn(new Column\Value("name"))
      ->addColumn(new Column\SwitchValue("isEnabled", null, 0, 1,
          $listAdminEvent->getCurrentModule()->generateUrl("change"),
          $listAdminEvent->getCurrentModule()->isGranted("edit")
        )
      );

  }

  /**
   * @param FormAdminEvent $formAdminEvent
   *
   * @throws \Exception
   */
  public function configureFormMapper(FormAdminEvent $formAdminEvent)
  {
    $librairyForm = new LibraryForm($this->container, $formAdminEvent->getFormMapper());
    $librairyForm->form();
  }

  /**
   * @param FormAdminEvent $formAdminEvent
   *
   * @throws \Exception
   */
  protected function formUpdateBefore(FormAdminEvent $formAdminEvent)
  {
    /** @var LibraryInterface|EntityInterface $object */
    $object = $formAdminEvent->getFormMapper()->getObject();
    if(!$object->getKeyname()) {
      $object->setKeyname($object->getName());
    }
    $object->setIsNavigationMenu(false);
    $object->setAccessibleInContent(true);
  }
}