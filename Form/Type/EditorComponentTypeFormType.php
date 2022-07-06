<?php
/*
 * This file is part of the Austral ContentBlock Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ContentBlockBundle\Form\Type;

use App\Entity\Austral\ContentBlockBundle\EditorComponentType;
use Austral\FormBundle\Form\Type\FormType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Austral ContentBlock Form Type.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class EditorComponentTypeFormType extends FormType
{

  /**
   * @param OptionsResolver $resolver
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    parent::configureOptions($resolver);
    $resolver->setDefaults([
      'data_class' => EditorComponentType::class,
    ]);
  }

}