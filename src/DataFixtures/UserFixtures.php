<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public const MANAGER_USER_REFERENCE = 'manager-user';

    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $password = 'password';

        // Chef de projet
        $manager_user = new User();
        $manager_user->setFirstName('Sophie');
        $manager_user->setLastName('Martin');
        $manager_user->setMatricule('MGR001');
        $manager_user->setEmail('manager@example.com');
        $manager_user->setRoles(['ROLE_MANAGER']);
        $manager_user->setPassword($this->hasher->hashPassword($manager_user, $password));
        $manager->persist($manager_user);

        // 20 Collaborateurs
        for ($i = 1; $i <= 20; $i++) {
            $user = new User();
            $firstName = $faker->firstName();
            $lastName = $faker->lastName();

            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $user->setMatricule(sprintf('COL%03d', $i));
            $user->setEmail(strtolower($firstName . '.' . $lastName . '@example.com'));
            $user->setRoles(['ROLE_USER']);
            $user->setPassword($this->hasher->hashPassword($user, $password));

            $manager->persist($user);

            $this->addReference('collaborateur-' . $i, $user);
        }

        $manager->flush();

        $this->addReference(self::MANAGER_USER_REFERENCE, $manager_user);
    }
}
