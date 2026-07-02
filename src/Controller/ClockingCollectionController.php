<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\CollaboratorClockingDTO;
use App\Dto\ManagerClockingDTO;
use App\Entity\Clocking;
use App\Form\CollaboratorClockingType;
use App\Form\ManagerClockingType;
use App\Repository\ClockingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/clockings')]
class ClockingCollectionController extends
AbstractController
{

    #[Route('/clockings/create', name: 'app_Clocking_create')]
    public function create(): Response
    {
        // Page d'accueil qui redirige selon le rôle
        if ($this->isGranted('ROLE_MANAGER')) {
            return $this->redirectToRoute('app_Clocking_create_manager');
        }
        return $this->redirectToRoute('app_Clocking_create_collaborator');
    }

    #[Route('/clockings/create/collaborator', name: 'app_Clocking_create_collaborator')]
    #[IsGranted('ROLE_USER')]
    public function createCollaborator(Request $request, EntityManagerInterface $em): Response
    {
        $formData = new CollaboratorClockingDTO();
        $form = $this->createForm(CollaboratorClockingType::class, $formData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // N Clockings avec projets différents
            foreach ($formData->projects as $proj) {
                $clocking = new Clocking();
                $clocking->setClockingUser($this->getUser());
                $clocking->setClockingProject($proj['project']);
                $clocking->setDate($formData->date);
                $clocking->setDuration($proj['duration']);
                $em->persist($clocking);
            }

            $em->flush();
            $this->addFlash('success', 'Pointage créé !');
            return $this->redirectToRoute('app_Clocking_list');
        }

        return $this->render('app/Clocking/create_collaborator.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/clockings/create/manager', name: 'app_Clocking_create_manager')]
    #[IsGranted('ROLE_MANAGER')]
    public function createManager(Request $request, EntityManagerInterface $em): Response
    {
        $formData = new ManagerClockingDTO();
        $form = $this->createForm(ManagerClockingType::class, $formData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // N Clockings avec durations différentes
            foreach ($formData->collaborators as $collab) {
                $clocking = new Clocking();
                $clocking->setClockingUser($collab['user']);
                $clocking->setClockingProject($formData->project);
                $clocking->setDate($formData->date);
                $clocking->setDuration($collab['duration']);
                $em->persist($clocking);
            }

            $em->flush();
            $this->addFlash('success', 'Pointage(s) créé(s) !');
            return $this->redirectToRoute('app_Clocking_list');
        }

        return $this->render('app/Clocking/create_manager.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param \App\Repository\ClockingRepository $clockingRepository
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    #[Route('/', name: 'app_Clocking_list', methods: ['GET'])]
    public function listClockings(ClockingRepository $clockingRepository): Response
    {
        $clockings = $clockingRepository->findAll();

        return $this->render('app/Clocking/list.html.twig', [
            'clockings' => $clockings,
        ]);
    }
}
