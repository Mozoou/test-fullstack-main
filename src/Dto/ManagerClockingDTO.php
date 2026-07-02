<?php

namespace App\Dto;

use App\Entity\Project;
use Symfony\Component\Validator\Constraints as Assert;

class ManagerClockingDTO
{
    public ?\DateTimeInterface $date = null;

    #[Assert\NotNull]
    public ?Project $project = null;

    #[Assert\Count(min: 1)]
    public array $collaborators = []; // [{user, duration}, ...]
}
