<?php

namespace Drupal\iqual\EventSubscriber;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\node\NodeInterface;
use Drupal\Core\Language\LanguageInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscribe to kernel requests to check for untranslated nodes.
 */
class UntranslatedEntitySubscriber implements EventSubscriberInterface {

  /**
   * Return 404 for non-existing translations.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *
   * @throws Drupal\iqual\EventSubscriber\NotFoundHttpException
   *   Thrown when #used_fields is malformed.
   */
  public function onKernelRequestCheckTranslationExists(GetResponseEvent $event) {
    if ($event->getRequestType() === HttpKernelInterface::MASTER_REQUEST) {
      $node = \Drupal::routeMatch()->getParameter('node');
      if ($node instanceof NodeInterface) {
        if (\Drupal::service('router.admin_context')->isAdminRoute()) {
          return;
        }
        $language = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT);
        if (!$node->hasTranslation($language->getId())) {
          throw new NotFoundHttpException();
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onKernelRequestCheckTranslationExists'];
    return $events;
  }

}
