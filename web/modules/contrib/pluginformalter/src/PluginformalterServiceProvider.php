<?php

namespace Drupal\pluginformalter;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Defines a service profiler for the pluginformalter module.
 */
class PluginformalterServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $modules = $container->getParameter('container.modules');

    // Add ViewsDataCollector only if Views module is enabled.
    if (isset($modules['webprofiler'])) {
      $container->register('pluginformalter.webprofiler.forms', 'Drupal\pluginformalter\DataCollector\FormAltersDataCollector')
        ->addArgument(new Reference(('pluginformalter.webprofiler.forms.inner')))
        ->addArgument(new Reference(('form_builder')))
        ->addArgument(new Reference(('plugin.manager.form_alter')))
        ->setPublic(FALSE)
        ->setDecoratedService('webprofiler.forms');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $modules = $container->getParameter('container.modules');

    // Alter webprofiler.forms service only if webprofiler module is enabled.
    if (isset($modules['webprofiler'])) {
      $collector = $container->getDefinition('webprofiler.forms');
      $tag = $collector->getTag('data_collector')[0];

      $tag['template'] = '@pluginformalter/Collector/forms.html.twig';
      $collector->clearTag('data_collector')
        ->addTag('data_collector', $tag);
    }
  }

}
