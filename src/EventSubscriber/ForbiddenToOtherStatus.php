<?php

namespace Drupal\iqual\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Converts 403 entity page error responses to another status code.
 */
class ForbiddenToOtherStatus extends HttpExceptionSubscriberBase {

  /**
   * The status code to return (defaults to 403).
   *
   * @var int
   */
  protected $statusCode = 403;

  /**
   * Constructs a ForbiddenToOtherStatus object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
    protected AccountProxyInterface $account,
    protected LanguageManagerInterface $languageManager,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected RouteMatchInterface $routeMatch,
    ConfigFactoryInterface $config_factory
    ) {
    $this->statusCode = $config_factory->get('iqual.settings')
      ->get('entity_unpublished_status');
  }

  /**
   * {@inheritdoc}
   */
  protected static function getPriority() {
    return 1000;
  }

  /**
   * {@inheritdoc}
   */
  protected function getHandledFormats() {
    return ['html'];
  }

  /**
   * Gets the current page main entity if publishable.
   *
   * @return \Drupal\Core\Entity\EntityPublishedInterface
   *   Current page main entity if publishable, NULL otherwise.
   */
  protected function getEntity() : ?EntityPublishedInterface {
    $entity = &drupal_static(__FUNCTION__, NULL);
    if (!empty($entity)) {
      return $entity;
    }
    $types = array_keys($this->entityTypeManager->getDefinitions());
    $params = $this->routeMatch->getParameters()->all();
    foreach ($types as $type) {
      if (!empty($params[$type]) && $params[$type] instanceof EntityPublishedInterface) {
        return $params[$type];
      }
    }
    return NULL;
  }

  /**
   * Handles all 4xx errors for all serialization failures.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
   *   The event to process.
   */
  public function on403(ExceptionEvent $event) {
    if ($this->shouldApply()) {
      $entity = $this->getEntity();
      if ($this->statusRequired($entity)) {
        $event->setThrowable(new HttpException($this->statusCode));
      }
    }
  }

  /**
   * Return a status code for untranslated entites or non-existing translations.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event.
   *
   * @throws Drupal\iqual\EventSubscriber\NotFoundHttpException
   *   Thrown when access is denied.
   */
  public function onKernelRequestCheckTranslationExists(RequestEvent $event) {
    // Prevent infinite loop by ignoring sub requests.
    if (!$event->isMainRequest()) {
      return;
    }
    if ($this->shouldApply()) {
      $entity = $this->getEntity();
      if ($this->statusRequired($entity)) {
        throw new HttpException($this->statusCode);
      }
    }
  }

  /**
   * Check if an entity should return the status code.
   *
   * @param \Drupal\Core\Entity\EntityPublishedInterface $entity
   *   The entity being accessed.
   *
   * @return bool
   *   True when the status code should be returned, false otherwise.
   */
  public function statusRequired(EntityPublishedInterface $entity = NULL) {
    if (!$entity) {
      return FALSE;
    }
    if ($entity instanceof ContentEntityInterface) {
      // Get the entity in the current language.
      $language = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT);
      if ($entity->hasTranslation($language->getId())) {
        $entity = $entity->getTranslation($language->getId());
        if (!$entity->isPublished()) {
          return TRUE;
        }
      }
      else {
        // If the entity doesn't have a translation, return status code.
        return TRUE;
      }
    }
    else {
      if (!$entity->isPublished()) {
        return TRUE;
      }
    }
  }

  /**
   * Check whether a special status should be sent..
   *
   * @return bool
   *   True if status code should be applied, false otherwise.
   */
  public function shouldApply() : bool {
    // Check if status code response is enabled.
    if ($this->statusCode == 403) {
      return FALSE;
    }
    // Only apply to anonymous user.
    if (!$this->account->isAnonymous()) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = parent::getSubscribedEvents();
    $events[KernelEvents::REQUEST][] = ['onKernelRequestCheckTranslationExists', -100];
    return $events;
  }

}
