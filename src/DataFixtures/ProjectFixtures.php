<?php

namespace App\DataFixtures;

use App\Entity\Project;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ProjectFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $nbProjects = 15;

        for ($i = 1; $i <= $nbProjects; $i++) {
            $project = new Project();
            $project->setName("Chantier {$i}");
            $project->setAddress($faker->streetAddress());
            
            $dateStart = $faker->dateTimeBetween('-2 years', 'today');
            $project->setDateStart($dateStart);

            // 70% de chance que le projet soit terminé
            if (random_int(0, 100) > 30) {
                $dateEnd = (clone $dateStart)->modify('+' . random_int(10, 180) . ' days');
                $project->setDateEnd($dateEnd);
            }

            $manager->persist($project);
        }

        $manager->flush();
    }
}
