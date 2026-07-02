<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CollaboratorClockingDTO
{
    public ?\DateTimeInterface $date = null;

    #[Assert\Count(min: 1)]
    public array $projects = []; // [{project, duration}, ...]
}
