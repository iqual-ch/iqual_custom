<?php

namespace Drupal\iqual\EventSubscriber;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\node\NodeInterface;
use Drupal\Core\Language\LanguageInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Language\LanguageManager;

/**
 * Subscribe to kernel requests to check for untranslated nodes.
 */
class UntranslatedEntitySubscriber implements EventSubscriberInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * The admin context.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * UntranslatedEntitySubscriber constructor.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   The current route match.
   * @param \Drupal\Core\Routing\AdminContext $adminContext
   *   The admin context.
   * @param \Drupal\Core\Language\LanguageManager $languageManager
   *   The language manager.
   */
  public function __construct(CurrentRouteMatch $currentRouteMatch, AdminContext $adminContext, LanguageManager $languageManager) {
    $this->currentRouteMatch = $currentRouteMatch;
    $this->adminContext = $adminContext;
    $this->languageManager = $languageManager;
  }

  /**
   * Return 404 for non-existing translations.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event.
   *
   * @throws Drupal\iqual\EventSubscriber\NotFoundHttpException
   *   Thrown when #used_fields is malformed.
   */
  public function onKernelRequestCheckTranslationExists(RequestEvent $event) {
    if ($event->getRequestType() === HttpKernelInterface::MASTER_REQUEST) {
      $node = $this->currentRouteMatch->getParameter('node');
      if ($node instanceof NodeInterface) {
        if ($this->adminContext->isAdminRoute()) {
          return;
        }
        $language = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT);
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
    $events = [];
    $events[KernelEvents::REQUEST][] = ['onKernelRequestCheckTranslationExists'];
    return $events;
  }

}
