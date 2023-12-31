<?php

namespace Drupal\media_mpx;

use Drupal\media_mpx\Entity\UserInterface;
use Lullabot\Mpx\Client;
use Lullabot\Mpx\Service\IdentityManagement\UserSession;
use Lullabot\Mpx\TokenCachePool;
use Symfony\Component\Lock\BlockingStoreInterface;

/**
 * Factory used to create mpx user sessions.
 */
class UserSessionFactory {

  /**
   * The underlying mpx API client.
   *
   * @var \Lullabot\Mpx\Client
   */
  private $client;

  /**
   * The lock store used to prevent sign-in stampedes.
   *
   * @var \Symfony\Component\Lock\BlockingStoreInterface
   */
  private $store;

  /**
   * The cache of authentication tokens.
   *
   * @var \Lullabot\Mpx\TokenCachePool
   */
  private $tokenCachePool;

  /**
   * Construct a new UserSessionFactory.
   *
   * @param \Lullabot\Mpx\Client $client
   *   The underlying mpx API client.
   * @param \Symfony\Component\Lock\BlockingStoreInterface $store
   *   The lock store used to prevent sign-in stampedes.
   * @param \Lullabot\Mpx\TokenCachePool $tokenCachePool
   *   The cache of authentication tokens.
   */
  public function __construct(Client $client, BlockingStoreInterface $store, TokenCachePool $tokenCachePool) {
    $this->client = $client;
    $this->store = $store;
    $this->tokenCachePool = $tokenCachePool;
  }

  /**
   * Create a session for a user.
   *
   * @param \Drupal\media_mpx\Entity\UserInterface $user
   *   The user to create the session for.
   *
   * @return \Lullabot\Mpx\Service\IdentityManagement\UserSession
   *   The new user session.
   */
  public function fromUser(UserInterface $user): UserSession {
    return new UserSession($user, $this->client, $this->store, $this->tokenCachePool);
  }

}
