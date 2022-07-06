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

use Austral\ToolsBundle\AustralTools;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

use Exception;

/**
 * Austral ContentBlock Extension.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class AustralContentBlockExtension extends Extension
{
  /**
   * {@inheritdoc}
   * @throws Exception
   */
  public function load(array $configs, ContainerBuilder $container)
  {
    $configuration = new Configuration();
    $configs[0]["type_values"] = array_replace_recursive($configuration->blockTypeDefault(), AustralTools::getValueByKey($configs[0], "type_values", array()));
    $configs[0]["editor_component"] = array_replace_recursive($configuration->getEditorComponentCategories(), AustralTools::getValueByKey($configs[0], "editor_component", array()));
    $config = $this->processConfiguration($configuration, $configs);

    $container->setParameter('austral_content_block', $config);

    $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
    $loader->load('parameters.yaml');
    $loader->load('services.yaml');
    $this->loadConfigToAustralBundle($container, $loader);
  }

  /**
   * @param ContainerBuilder $container
   * @param YamlFileLoader $loader
   *
   * @throws Exception
   */
  protected function loadConfigToAustralBundle(ContainerBuilder $container, YamlFileLoader $loader)
  {
    $bundlesConfigPath = $container->getParameter("kernel.project_dir")."/config/bundles.php";
    if(file_exists($bundlesConfigPath))
    {
      $contents = require $bundlesConfigPath;
      if(array_key_exists("Austral\AdminBundle\AustralAdminBundle", $contents))
      {
        $loader->load('austral_admin.yaml');
      }
      if(array_key_exists("Austral\FormBundle\AustralFormBundle", $contents))
      {
        $loader->load('austral_form.yaml');
      }
      if(array_key_exists("Austral\EntityFileBundle\AustralEntityFileBundle", $contents))
      {
        $loader->load('austral_entity_file.yaml');
      }
    }
  }

  /**
   * @return string
   */
  public function getNamespace()
  {
    return 'https://austral.dev/schema/dic/austral_content_block';
  }

}
