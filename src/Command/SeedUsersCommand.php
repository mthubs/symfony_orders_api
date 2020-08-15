<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class SeedUsersCommand extends Command
{
    private $em;
    private $passwordEncoder;
    private $userRepository;

    public function __construct(EntityManagerInterface $em, UserPasswordEncoderInterface $passwordEncoder, UserRepository $userRepository)
    {
        parent::__construct();
        $this->em = $em;
        $this->passwordEncoder = $passwordEncoder;
        $this->userRepository = $userRepository;
    }

    protected static $defaultName = 'seed:users';

    protected function configure()
    {
        $this
            ->setDescription('Seeds 3 user companies to the database')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->userRepository->findAll()) {
            $companies = [
                [
                    'username' => 'company_a',
                    'email' => 'company_a@email.com',
                    'password' => 'test1234'
                ],
                [
                    'username' => 'company_b',
                    'email' => 'company_b@email.com',
                    'password' => 'test1234'
                ],
                [
                    'username' => 'company_c',
                    'email' => 'company_c@email.com',
                    'password' => 'test1234'
                ],
            ];

            foreach ($companies as $company) {
                $user = new User();
                $user->setUsername($company['username']);
                $user->setEmail($company['email']);
                $user->setPassword(
                    $this->passwordEncoder->encodePassword($user, $company['password'])
                );

                $this->em->persist($user);
                $this->em->flush();
            }
            $io->success('User table successfully seeded');

            return 0;
        }

        $io->success('User table already seeded');

        return 0;
    }
}
