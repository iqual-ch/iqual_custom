<?php

namespace Drupal\Tests\iqual\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\iqual\EventSubscriber\UntranslatedEntitySubscriber;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Drupal\Core\Routing\ResettableStackedRouteMatchInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\node\NodeInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @coversDefaultClass \Drupal\iqual\EventSubscriber\UntranslatedEntitySubscriber
 * @group iqual_custom
 */
class UntranslatedEntitySubscriberTest extends UnitTestCase {

  /**
   * @var \Drupal\Core\Routing\ResettableStackedRouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $routerAdminContext;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock current_route_match.
    $this->currentRouteMatch = $this->prophesize(ResettableStackedRouteMatchInterface::class);

    // Mock router_admin_context.
    $this->routerAdminContext = $this->prophesize(AdminContext::class);

    // Mock language_manager.
    $this->languageManager = $this->prophesize(LanguageManagerInterface::class);
    $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->willReturn(new Language(['id' => 'de']));

    $container = new ContainerBuilder();
    $container->set('current_route_match', $this->currentRouteMatch->reveal());
    $container->set('router.admin_context', $this->routerAdminContext->reveal());
    $container->set('language_manager', $this->languageManager->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * Tests UntranslatedEntitySubscriber onKernelRequestCheckTranslationExists() method.
   *
   * @covers ::onKernelRequestCheckTranslationExists
   */
  public function testOnKernelRequestCheckTranslationExists() {
    $untranslatedEntitySubscriber = new UntranslatedEntitySubscriber();
    $event = $this->prophesize(RequestEvent::class);
    $node = $this->prophesize(NodeInterface::class);

    // Break the first condition.
    $event->getRequestType()->willReturn(HttpKernelInterface::SUB_REQUEST);
    $return = $untranslatedEntitySubscriber->onKernelRequestCheckTranslationExists($event->reveal());
    $this->assertEquals($return, NULL);
    // Fix the first condition
    $event->getRequestType()->willReturn(HttpKernelInterface::MASTER_REQUEST);

    // Break the second condition.
    $this->currentRouteMatch->getParameter('node')->willReturn(NULL);
    $return = $untranslatedEntitySubscriber->onKernelRequestCheckTranslationExists($event->reveal());
    $this->assertEquals($return, NULL);
    // Fix the second condition.
    $this->currentRouteMatch->getParameter('node')->willReturn($node->reveal());

    // Break the third condition.
    $this->routerAdminContext->isAdminRoute()->willReturn(TRUE);
    $return = $untranslatedEntitySubscriber->onKernelRequestCheckTranslationExists($event->reveal());
    $this->assertEquals($return, NULL);
    // Fix the third condition
    $this->routerAdminContext->isAdminRoute()->willReturn(FALSE);

    // Break the fourth condition.
    $node->hasTranslation('de')->willReturn(TRUE);
    $return = $untranslatedEntitySubscriber->onKernelRequestCheckTranslationExists($event->reveal());
    $this->assertEquals($return, NULL);
    // Fix the fourth condition.
    $node->hasTranslation('de')->willReturn(FALSE);

    // Test the final outcome.
    $this->expectException(NotFoundHttpException::class);
    $untranslatedEntitySubscriber->onKernelRequestCheckTranslationExists($event->reveal());
  }

}
