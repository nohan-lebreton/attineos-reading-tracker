<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;

final class CurrentUserProvider
{
    public const DEMO_EMAIL = 'demo@example.com';

    private ?User $user = null;

    public function __construct(private readonly UserRepository $users)
    {
    }

    public function get(): User
    {
        return $this->user ??= $this->users->findOneBy(['email' => self::DEMO_EMAIL])
            ?? throw new \LogicException('Utilisateur de démo absent : lancer "php bin/console app:create-demo-user".');
    }
}
