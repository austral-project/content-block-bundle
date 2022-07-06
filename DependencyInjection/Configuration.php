<?php
/*
 * This file is part of the Austral ContentBlock Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ContentBlockBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Austral Website Configuration.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class Configuration implements ConfigurationInterface
{
  /**
   * {@inheritdoc}
   */
  public function getConfigTreeBuilder(): TreeBuilder
  {
    $treeBuilder = new TreeBuilder('austral_content_block');

    $rootNode = $treeBuilder->getRootNode();
    $rootNode->children()
      ->arrayNode("type_values")
        ->arrayPrototype()
          ->children()
            ->scalarNode('entitled')->isRequired()->end()
            ->scalarNode('picto')->isRequired()->end()
            ->booleanNode('allow_child')->isRequired()->end()
            ->booleanNode('can_has_link')->isRequired()->end()
          ->end()
        ->end()
        ->defaultValue($this->blockTypeDefault())
      ->end()

      ->arrayNode("editor_component")
        ->children()
          ->scalarNode('image_default')->isRequired()->end()
          ->arrayNode("categories")
            ->scalarPrototype()->end()
          ->end()
        ->end()
      ->end()

      ->arrayNode("title_tag_values")
        ->scalarPrototype()->end()
        ->defaultValue($this->titleTagsDefault())
      ->end()

      ->arrayNode("text_type_values")
        ->scalarPrototype()->end()
        ->defaultValue($this->textValuesDefault())
      ->end()

    ->end();

    return $treeBuilder;
  }

  /**
   * @return array
   */
  public function blockTypeDefault(): array
  {
    return array(
      "group"           =>  array(
        "entitled"        => "choices.collections.blockType.group",
        "picto"           => "austral-picto-layout",
        "allow_child"     => true,
        "can_has_link"    => false,
      ),
      "list"            =>  array(
        "entitled"        => "choices.collections.blockType.list",
        "picto"           => "austral-picto-list-ul",
        "allow_child"     => true,
        "can_has_link"    => false,
      ),
      "title"           =>  array(
        "entitled"        => "choices.collections.blockType.title",
        "picto"           => "austral-picto-text",
        "allow_child"     => false,
        "can_has_link"    => true,
      ),
      "text"           =>  array(
        "entitled"        => "choices.collections.blockType.text",
        "picto"           => "austral-picto-text",
        "allow_child"     => false,
        "can_has_link"    => true,
      ),
      "textarea"            =>  array(
        "entitled"        => "choices.collections.blockType.textarea",
        "picto"           => "austral-picto-text",
        "allow_child"     => false,
        "can_has_link"    => false,
      ),
      "image"           =>  array(
        "entitled"        => "choices.collections.blockType.image",
        "picto"           => "austral-picto-image",
        "allow_child"     => false,
        "can_has_link"    => true,
      ),
      "file"            =>  array(
        "entitled"        => "choices.collections.blockType.file",
        "picto"           => "austral-picto-file-text",
        "allow_child"     => false,
        "can_has_link"    => true,
      ),
      "movie"           =>  array(
        "entitled"        => "choices.collections.blockType.movie",
        "picto"           => "austral-picto-movie",
        "allow_child"     => false,
        "can_has_link"    => false,
      ),
      "choice"           =>  array(
        "entitled"        => "choices.collections.blockType.choice",
        "picto"           => "austral-picto-text",
        "allow_child"     => false,
        "can_has_link"    => false,
      ),
      "button"          =>  array(
        "entitled"        => "choices.collections.blockType.button",
        "picto"           => "austral-picto-paper-clip",
        "allow_child"     => false,
        "can_has_link"    => false,
      )
    );
  }

  /**
   * @return array
   */
  public function titleTagsDefault(): array
  {
    return array(
      "H2"    =>  "h2",
      "H3"    =>  "h3",
      "H4"    =>  "h4",
      "H5"    =>  "h5",
      "H6"    =>  "h6",
      "span"  =>  "span"
    );
  }

  /**
   * @return array
   */
  public function getEditorComponentCategories(): array
  {
    return array(
      "image_default" =>  "",
      "categories"  =>  array(
        "default",
        "custom"
      )
    );
  }

  /**
   * @return array
   */
  public function textValuesDefault(): array
  {
    return array(
      "choices.text.type.string"      =>  "string",
      "choices.text.type.integer"     =>  "integer",
      "choices.text.type.number"      =>  "number",
      "choices.text.type.date"        =>  "date"
    );
  }
}
