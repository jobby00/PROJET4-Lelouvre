<?php

namespace JD\LelouvreBundle\Services;

use JD\LelouvreBundle\Entity\Reservation;
use JD\LelouvreBundle\Entity\Billets;
use JD\LelouvreBundle\Services;
use JD\LelouvreBundle\Repository;
use Symfony\Component\Config\Definition\Exception\Exception;
use DateTime;
use Symfony\Bundle\TwigBundle;
use Symfony\Component\HttpFoundation\Session;

class  OutilsReser
{
    private  $em;
    private  $outilsBillets;
    private  $heureLimiteDemiJournee = 14;
    private  $nbBilletsMaxParJour = 1000;

    public function __construct
    (   
        \Doctrine\ORM\EntityManager $em,
        \JD\LelouvreBundle\Services\OutilsBillets $outilsBillets
    )
    {
        $this->em = $em;
        $this->outilsBillets = $outilsBillets;
    }

    /**
     * Récupère la réservation en cours via son code
     * ou crée une nouvelle réservation si il n'y en a pas
     * et si la création d'une nouvelle réservation est autorisée par le controlleur
     *
     *
     * @param $resaCode
     * @param boolean $nouvelleResaAcceptee
     * @return Reservation
     */
    public function initResa($resaCode, $nouvelleResaAcceptee)
    {

        $resa = null;

        if ($resaCode !== null )
        {
            $resa = $this->em->getRepository('JDLelouvreBundle:Reservation')->findOneBy(array(
                'resaCode' => $resaCode
            ));
        }
        // si le controlleur permet la création d'une nouvelle réservation
        if ($resa === null && $nouvelleResaAcceptee)
        {
            $resa = new Reservation();
        }
        //si le controlleur ne permet pas la création d'une nouvelle réservation (2e et 3e étape)
        //on retourne un null
        elseif($resa === null)
        {
            return null;
        }
        return $resa;
    }

    /**
     * @param Reservation $resa
     * @return bool
     */
    public function verifDate(Reservation $resa)
    {
        $dateResa = $resa->getDateresa();
        $dateUsuelle = new DateTime("now", new \DateTimeZone('Europe/Paris'));
        $dateResaForma = $dateResa->format('dm');
        $dateResaWeeck = $dateResa->format('w');
        dump($dateResa);
        if(
            $dateResaForma == "0105"
            || $dateResaForma == "2512"
            || $dateResaWeeck == '0'
            || $dateResaWeeck == '2'
        )
        {
            dump('test1');
            //$this->session->getFlashBag()->add('erreurJour', "Nous sommes désolé le musée n'est pas ouvert à cette date.");
            return false;
        }elseif (
            !$resa->getDemijournee()
            && $dateResa->format('dmY') == $dateUsuelle->format('dmY')
            && $dateUsuelle->format('H') >= $this->heureLimiteDemiJournee)
        {
            dump('test2');
            //$this->session->getFlashBag()->add('erreurJournee', 'Nous sommes désolé vous ne pouvez plus sélectionner une réservation journée pour le jour même après 14h!');
            return false;
        }else
        {
            dump('test3');
            return true;
        }
    }

    /**
     * @param Reservation $resa
     * @return bool
     */
    public function verifNbPlaces(Reservation $resa)
    {
       $billetDispo =  true;

        $nbBilleReserves = $this->em
            ->getRepository('JDLelouvreBundle:Reservation')
            ->findByDateresa($resa->getDateresa());
        $sombillets = 0;
        foreach ($nbBilleReserves as $nbBilleReserve)
        {
            $sombillets += $nbBilleReserve->getNbBillets();
        }
        $nbilletDisponible = $this->nbBilletsMaxParJour - $sombillets;
        if($nbilletDisponible < 1)
        {
            dump('test4');
            //$this->session->getFlashBag()->add('erreurDateDispo', "Nous sommes désolé, il n'y a plus de billet disponible à la date demandée!");
            $billetDispo = false;
        }elseif ($nbilletDisponible < $resa->getNbBillets())
        {
            dump('test5');
            //$this->session->getFlashBag()->add('erreurDateDispo', "Nous sommes désolé, il reste seulement ".$nbilletDisponible." billet(s) disponibles à la date demandée!");
            $billetDispo = false;
        }
        return $billetDispo;
    }

    /**
     * @param Reservation $resa
     * @return bool
     */
    public function validResa(Reservation $resa)
    {
        $reservationValide = true;

        if(!$this->verifDate($resa) || !$this->verifNbPlaces($resa))
        {
            dump('test6');
            $reservationValide = false;
            return $reservationValide;
        }
        // si la réservation est valide
        // on persiste la réservation , afin d'être à jour au niveau des disponibilités
        // et de la récupérer à l'étape suivante
        // en cas d'échec on enregistre un message d'erreur

        try
        {
            $this->em->persist($resa);
            $this->em->flush();
            $reservationValide = true;
        }
        catch (Exception $e)
        {
            //$this->session->getFlashBag()->add('erreurInterne', "Une erreur interne s'est produite, merci de réessayer.");
            $reservationValide = false;
        }
        return $reservationValide;
    }

    /**
     * @param $billets
     * @param $reservation
     */
    public function prixTotal($billets, $reservation){
        $total = $reservation->getPrix();
        foreach ($billets as  $billet){
            $total = $total + $billet->getPrix();
        }
        $reservation->setPrixtotal($total);
        $this->em->persist($reservation);
        $this->em->flush();
    }

}


