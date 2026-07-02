<?php

namespace App\DataFixtures;

use App\Entity\Clocking;
use App\Entity\Project;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ClockingFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $nbClockings = 200; // Augmenté pour avoir plus de données

        // Récupérer les utilisateurs et projets
        $users = $manager->getRepository(User::class)->findAll();
        $projects = $manager->getRepository(Project::class)->findAll();

        if (empty($users) || empty($projects)) {
            return;
        }

        for ($i = 0; $i < $nbClockings; $i++) {
            $clocking = new Clocking();

            $clocking->setClockingUser($faker->randomElement($users));
            $clocking->setClockingProject($faker->randomElement($projects));
            $clocking->setDate($faker->dateTimeBetween('-1 year', 'today'));
            $clocking->setDuration(random_int(1, 10));

            $manager->persist($clocking);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            ProjectFixtures::class,
        ];
    }
}
