<?php

namespace Drupal\pf_email\Plugin\PushFrameworkChannel;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\push_framework\ChannelBase;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the push framework channel.
 *
 * @ChannelPlugin(
 *   id = "email",
 *   label = @Translation("Email"),
 *   description = @Translation("Provides the email channel plugin.")
 * )
 */
class Email extends ChannelBase {

  /**
   * The mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected MailManagerInterface $mailManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->mailManager = $container->get('plugin.manager.mail');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigName(): string {
    return 'pf_email.settings';
  }

  /**
   * {@inheritdoc}
   */
  public function applicable(UserInterface $user): bool {
    return $this->active;
  }

  /**
   * {@inheritdoc}
   */
  public function send(UserInterface $user, ContentEntityInterface $entity, array $content, int $attempt): string {
    $language = $user->getPreferredLangcode();
    if (!isset($content[$language])) {
      $language = array_keys($content)[0];
    }
    $message = $this->mailManager->mail('pf_email', 'notification', $user->getEmail(), $user->getPreferredLangcode(), [
      'subject' => $content[$language]['subject'],
      'body' => $content[$language]['body'],
      'is html' => $content[$language]['is html'],
    ]);

    return $message['result'] ? self::RESULT_STATUS_SUCCESS : self::RESULT_STATUS_FAILED;
  }

}
