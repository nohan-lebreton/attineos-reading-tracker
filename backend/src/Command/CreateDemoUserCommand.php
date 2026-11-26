<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\CurrentUserProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-demo-user', description: "Crée l'utilisateur de démonstration s'il n'existe pas")]
final class CreateDemoUserCommand extends Command
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (null !== $this->users->findOneBy(['email' => CurrentUserProvider::DEMO_EMAIL])) {
            $io->note('Utilisateur de démo déjà présent.');

            return Command::SUCCESS;
        }

        $user = (new User())->setEmail(CurrentUserProvider::DEMO_EMAIL);
        $user->setPassword($this->hasher->hashPassword($user, bin2hex(random_bytes(16))));

        $this->em->persist($user);
        $this->em->flush();

        $io->success(sprintf('Utilisateur %s créé.', CurrentUserProvider::DEMO_EMAIL));

        return Command::SUCCESS;
    }
}
