<?php
/*
 * This file is part of the Austral ContentBlock Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ContentBlockBundle\Field;

use Austral\FormBundle\Field\CollectionEmbedField;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Austral Field ContentBlock.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class ContentBlockField extends CollectionEmbedField
{

  /**
   * @param string $fieldname
   * @param array $options
   *
   * @return $this
   */
  public static function create(string $fieldname = "master", array $options = array()): ContentBlockField
  {
    return new self($fieldname, $options);
  }

  /**
   * ContentBlockField constructor.
   */
  public function __construct(string $fieldname = "master", array $options = array())
  {
    if(!array_key_exists("button", $options))
    {
      $options["button"]  = "button.new.block";
    }
    if(array_key_exists("sortable", $options))
    {
      $options["sortable"] = array_merge(array(
        "editable"  =>  true
      ), $options["sortable"]);
    }
    else
    {
      $options["sortable"] = array(
        "editable"  =>  true
      );
    }
    parent::__construct($fieldname, $options);
  }

  /**
   * @param OptionsResolver $resolver
   */
  protected function configureOptions(OptionsResolver $resolver)
  {
    parent::configureOptions($resolver);
    $resolver->setDefaults(array(
      "hydrate_auto"  =>  array()
    ));
    $resolver->setAllowedTypes('hydrate_auto', array("array", "null"));
  }

  /**
   * @return array|null
   */
  public function getHydrateAuto()
  {
    return $this->options['hydrate_auto'];
  }


}