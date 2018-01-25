<?php

namespace JD\LelouvreBundle\Services;

use JD\LelouvreBundle\Entity\Billets;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Session\Session;
use JD\LelouvreBundle\Entity\Reservation;

class OutilsBillets
{
    //ces valeurs sont stockées dans les paramètres et récupérées via le constructeur
    private $ageMaxGratuit  = 4;
    private $ageMaxEnfant   = 12;
    private $ageMinSenior   = 60;
    private $tarifEnfant    = 8;
    private $tarifSenior    = 12;
    private $tarifNormal    = 16;
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em =$em;

    }

    public function validBillets($billets, $resa)
    {
        $em = $this->em;
        try
        {
            $resa->addBillet($billets);
            $em->persist($resa);
            $em->persist($billets);
            $em->flush();
            $billets = true;
        }
        catch (Exception $e)
        {
            $this->session->getFlashBag()->add('erreurInterne', "Une erreur interne s'est produite, merci de réessayer.");
            $billets = false;
        }
        return $billets;
    }

    /**
     * retourne l'age en fonction de la date de naissance en datetime
     *
     * @param datetime $dateNaissance
     * @return int $age
     */
    public function calculAge($dateNaissance){

        $age = idate('Y') - $dateNaissance->format('Y');

        return $age;
    }

    /**
     * retourne le tarif du billet en fonction de la date de naissance
     *
     *
     * @param Billet $billet
     * @return boolean
     * @internal param $dateNaissance
     */
    public function calculPrix($billets){

        $dateNaissance = $billets->getDateNaissance();

        $age = $this->calculAge($dateNaissance);

        if ( $age <= $this->ageMaxGratuit ){
            $prix = 0;
        }
        elseif ( $age <= $this->ageMaxEnfant )
        {
            $prix = $this->tarifEnfant = 8;
        }
        elseif( $age >= $this->ageMinSenior)
        {
            $prix = $this->tarifSenior = 12;
        }
        elseif ( $billets->getTarifReduit() )
        {
            $prix = $this->tarifReduit = 10;
        }
        else
        {
            $prix = $this->tarifNormal = 16;
        }
        $billets->setPrix($prix);
        return $billets;
    }
    
    
}