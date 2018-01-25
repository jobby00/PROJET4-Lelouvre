<?php

namespace JD\LelouvreBundle\Repository;

use Doctrine\ORM\EntityRepository;

class ReservationRepository extends EntityRepository
{
    public function reservesBillet($nom, $prenom, $pays, $dateNaissance, $email, $dateresa)
    {
    }
}