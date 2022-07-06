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
    parent::__construct($fieldname, array("button"=>"button.new.block"));
    $this->options['sortable']['editable'] = true;
  }

}