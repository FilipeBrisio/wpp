<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Contatos;

class NumberProvider
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getNumbers(): array
    {
        $contatosRepository = $this->entityManager->getRepository(Contatos::class);
        
        $contatos = $contatosRepository->findAll();

        $numbers = [];
        foreach ($contatos as $contato) {
            if ($contato->getNumeros() !== null) {
                $numbers[] = $contato->getNumeros();
            }
        }

        return $numbers;
    }
}
