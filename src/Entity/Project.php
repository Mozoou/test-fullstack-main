<?php

declare(strict_types = 1);

namespace App\Entity;

use App\Repository\ProjectRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
class Project
{

    #[ORM\Column(length: 255)]
    private ?string            $address   = null;
    #[ORM\OneToMany(targetEntity: Clocking::class, mappedBy: 'clockingProject', orphanRemoval: true)]
    private Collection         $clockings;
    #[Assert\GreaterThan(propertyPath: 'dateStart')]
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?DateTimeInterface $dateEnd   = null;
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $dateStart = null;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int               $id        = null;
    #[ORM\Column(length: 255)]
    private ?string            $name      = null;

    public function __construct()
    {
        $this->clockings = new ArrayCollection();
    }

    public function addClocking(Clocking $clocking) : static
    {
        if(!$this->clockings->contains($clocking)) {
            $this->clockings->add($clocking);
            $clocking->setClockingProject($this);
        }

        return $this;
    }

    public function getAddress() : ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address) : void
    {
        $this->address = $address;
    }

    /**
     * @return Collection<int, Clocking>
     */
    public function getClockings() : Collection
    {
        return $this->clockings;
    }

    public function getDateEnd() : ?DateTimeInterface
    {
        return $this->dateEnd;
    }

    public function setDateEnd(?DateTimeInterface $dateEnd) : void
    {
        $this->dateEnd = $dateEnd;
    }

    public function getDateStart() : ?DateTimeInterface
    {
        return $this->dateStart;
    }

    public function setDateStart(?DateTimeInterface $dateStart) : void
    {
        $this->dateStart = $dateStart;
    }

    public function getId() : ?int
    {
        return $this->id;
    }

    public function getName() : ?string
    {
        return $this->name;
    }

    public function setName(string $name) : static
    {
        $this->name = $name;

        return $this;
    }

    public function removeClocking(Clocking $clocking) : static
    {
        if($this->clockings->removeElement($clocking)) {
            // set the owning side to null (unless already changed)
            if($clocking->getClockingProject() === $this) {
                $clocking->setClockingProject(null);
            }
        }

        return $this;
    }
}
