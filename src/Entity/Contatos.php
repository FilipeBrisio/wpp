<?php

namespace App\Entity;

use App\Repository\ContatosRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContatosRepository::class)]
class Contatos
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $numeros = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumeros(): ?string
    {
        return $this->numeros;
    }

    public function setNumeros(?string $numeros): static
    {
        $this->numeros = $numeros;

        return $this;
    }
}
