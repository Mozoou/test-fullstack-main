<?php

declare(strict_types = 1);

namespace App\Controller;

use App\Entity\Clocking;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/clockings/{id}', requirements: ['id' => '\d+'])]
class ClockingItemController extends
    AbstractController
{

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \App\Entity\Clocking                 $clocking
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    #[Route('/', name: 'app_Clocking_delete', methods: ['GET'])]
    public function deleteClocking(
        EntityManagerInterface $entityManager,
        Clocking               $clocking,
    ) : Response {
        $entityManager->remove($clocking);
        $entityManager->flush();

        return $this->redirectToRoute('app_Clocking_list');
    }
}
