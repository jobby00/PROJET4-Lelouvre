<?php

namespace JD\LelouvreBundle\Controller;

use JD\LelouvreBundle\Entity\Billets;
use JD\LelouvreBundle\Entity\Reservation;
use JD\LelouvreBundle\Form\BilletsType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use JD\LelouvreBundle\Form\ReservationType;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Doctrine\ORM\Mapping as ORM;
use JD\LelouvreBundle\Services;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;


class ReservationController extends Controller
{
    public function indexAction()
    {
        return $this->render('JDLelouvreBundle:Reservation:index.html.twig');
    }

    /**
     * action pour l'initialisation de la réservation : choix date , type de bilelt, nb billets
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function initialiserReservationAction(Request $request, $resaCode)
    {
        $session = new Session();
        //récupération du service outilsresa
        $outilsReser = $this->get('service_container')
            ->get('jd_reservation.outilsreser');
        $outilsBillets = $this->get('service_container')
            ->get('jd_reservation.outilsbillets');
        // récupération d'une éventuelle réservation en cours
        // si pas de réservation en cours, création d'une nouvelle réservation
        $resa = $outilsReser->initResa($resaCode, true);

        // création du formulaire associé à cette réservation + requête
        $form = $this->createForm(ReservationType::class, $resa);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $session->set('resa', $resa);
            $prixResa = $outilsBillets->calculPrix($resa);

            if ($outilsReser->validResa($resa))
            {
                $nb = $resa->getNbBillets();
                if($nb > 1)
                {
                    return $this->redirectToRoute('jd_reservation_completer',
                        [
                            'id' => $resa->getId()
                        ]
                    );
                }
                elseif($nb >= 1)
                {
                    //après validation, transfert vers l'étape suivante avec les paramètres de la résa
                    return $this->redirectToRoute('jd_reservation_panier', array(
                        'id' => $resa->getId()
                    ));
                }
            }
        }
        // pas de soumission ou erreur, génération de la vue avec le formulaire
        return $this->render('JDLelouvreBundle:Reservation:initialiserReser.html.twig', array(
            'form'          => $form->createView()
        ));
    }

    /**
     * action pour la secondé étape : on complète chaque billet
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function completerReservationAction(Request $request, SessionInterface $session, Reservation $resa)
    {
        if($resa->getNbBillets() > 1)
        {
            $billets = new Billets();
            // intialisation  des billets
            $outilsBillets = $this->get('service_container')
                ->get('jd_reservation.outilsbillets');


            // création du formulaire associé à cette réservation + requête
            $form = $this->createForm(BilletsType::class, $billets);
            $form->handleRequest($request);

            // action lors de la soumission du formulaire
            if ($form->isSubmitted() && $form->isValid())
            {
                $session->set('resa', $resa);
                $billets->setReservation($resa);
                $billets = $outilsBillets->calculPrix($billets);

                if ($outilsBillets->validBillets($billets, $resa))
                {
                    $totalBill = 0;
                    foreach ($resa->getBillets() as $billet)
                    {
                        $totalBill += 1;
                    }
                    if ($resa->getNbBillets() != ($totalBill +1 )) {
                        //après validation, transfert vers l'étape suivante avec les paramètres de la résa
                        return $this->redirectToRoute('jd_reservation_completer', array(
                            'id'    => $resa->getId()
                        ));
                    } else {
                        return $this->redirectToRoute('jd_reservation_panier',
                            [
                                'id' => $resa->getId()
                            ]
                        );
                    }
                }
            }

            $outilsReser = $this->get('service_container')
                ->get('jd_reservation.outilsreser');
            $allBilletsId = $this
                ->getDoctrine()
                ->getManager()
                ->getRepository(Billets::class)
                ->findByReservation($resa);

            $outilsReser->prixTotal($allBilletsId, $resa);
            $billetResa = $resa;
            $resa->getBillets();
            dump($resa);
            return $this->render('JDLelouvreBundle:Reservation:completerReser.html.twig',
                [
                    'resa'          => $resa,
                    'prixtotal'     => $resa->getPrixTotal(),
                    'billetResa'    => [$billetResa],
                    'billets'       => $billets,
                    'form'          => $form->createView()
                ]
            );
        }else
        {
            return $this->redirectToRoute('jd_reservation_panier',
                [
                    'id'        => $resa->getId()
                ]
            );
        }
    }

    /**
     * @param Reservation $resa
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function panierReservationAction(Reservation $resa, Request $request )
    {
        $outilsReser = $this->get('service_container')
            ->get('jd_reservation.outilsreser');
        $outilsReser->prixTotal($resa->getBillets(), $resa);
        dump($resa);
        return $this->render('JDLelouvreBundle:Reservation:panier.html.twig',
            [
                'prixtotal'         => $resa->getPrixTotal(),
                'billetResa'        => $resa,
            ]);
    }

    /**
     * @param Billets $billets
     * @param Request $request
     * @param SessionInterface $session
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public  function modifierAction(Billets $billets, Request $request, SessionInterface $session)
    {
        $resa = $session->get('resa');
        if(null === $resa)
        {
            throw new NotFoundHttpException("Le billet".$resa->getResaCode()." n'existe pas" );
        }

        $form = $this->createForm(BilletsType::class, $billets);
        $form->handleRequest($request);
        dump($billets);
        if ($form->isSubmitted() && $form->isValid())
        {
            $outilsBillets = $this->get('service_container')
                ->get('jd_reservation.outilsbillets');
            $em = $this->getDoctrine()
                ->getManager();
            $em->flush();
            if($resa->getTarifReduit() === true)
            {
                $prix = $outilsBillets->calculPrix($billets);
                $billets->setPrix($prix);
            $request->getSession()->getFlashBag()->add('notice', 'Votre billet a été bien modifiée');

            return $this->redirectToRoute('jd_reservation_panier',
                [
                    'prix' => $prix,
                    'id' =>$resa->getId()
                ]);
            }
        }

        return $this->render('JDLelouvreBundle:Reservation/Modif:edit.html.twig',
            [
                'resa'      => $resa,
                'form'      => $form->createView()
            ]);
    }

    /**
     * @param Reservation $reservation
     * @param Request $request
     * @param SessionInterface $session
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public  function modifieReservationAction(Reservation $reservation, Request $request, SessionInterface $session)
    {
        $outilsReser = $this->get('service_container')
            ->get('jd_reservation.outilsreser');
        $outilsBillets = $this->get('service_container')
                         ->get('jd_reservation.outilsbillets');

        $resa = $session->get('resa');

        if(null === $resa)
        {
            throw new NotFoundHttpException("Le billet".$resa->getResaCode()." n'existe pas" );
        }

            $form = $this->createForm(ReservationType::class, $reservation);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid())
            {
                $em = $this->getDoctrine()
                            ->getManager();
                $em->flush();
                if($resa->getNbBillets() > 1)
                {
                    $request->getSession()->getFlashBag()->add('notice', 'Votre Reservation a été bien modifiée');
                    return $this->redirectToRoute('jd_reservation_panier',
                        [
                            'id'    => $resa->getId()
                        ]);
                }else
                {
                    $request->getSession()->getFlashBag()->add('notice', 'Votre Reservation a été bien modifiée');
                    return $this->redirectToRoute('jd_reservation_completer',
                        [
                            'id'    => $resa->getId()
                        ]);
                }
            }
        return $this->render('JDLelouvreBundle:Reservation/Modif:editResa.html.twig',
            [
                'resa'      => $resa,
                'form'      => $form->createView()
            ]);
    }

    /**
     * @param Request $request
     * @param SessionInterface $session
     * @param Reservation $reservation
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deletAction(Request $request, SessionInterface $session, Reservation $reservation)
    {
        $resa = $session->get('resa');

        $em = $this
            ->getDoctrine()
            ->getManager();

        if(null === $resa)
        {
            throw  new NotFoundHttpException("Se billet n°: " .$resa->getResaCode(). " n'exite pas ");
        }

        $nb = $reservation->getNbBillets();
        $reservation->setNbBillets($nb - 1);
        $nb = $reservation->getNbBillets();

        if($nb > 1)
        {
            $em->persist($reservation);
            $em->flush();
            $request->getSession()->getFlashBag()->add('info', "Le nombre de billet a bien été modifié." .$reservation->getNbBillets());
            return $this->redirectToRoute('jd_reservation_completer',
                [
                    'id'        => $reservation->getId()
                ]
            );
        }
        elseif($nb == 1)
        {
            $em->persist($reservation);
            $em->flush();
            return $this->redirectToRoute('jd_reservation_panier',
                [
                    'id' =>$resa->getId()
                ]);
        }



    }

    /**
     * @param Request $request
     * @param SessionInterface $session
     * @param Reservation $reservation
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function addAction(Request $request, SessionInterface $session, Reservation $reservation)
    {
        $resa = $session->get('resa');

        $em = $this
            ->getDoctrine()
            ->getManager();

        if(null === $resa)
        {
            throw  new NotFoundHttpException("Se billet n°: " .$resa->getResaCode(). " n'exite pas ");
        }
        $nb = $reservation->getNbBillets();

        $reservation->setNbBillets($nb + 1);
        $em->persist($reservation);
        $em->flush();
        $request->getSession()->getFlashBag()->add('info', "Vous avez ajouté un billet aux nombre de billets ".$reservation->getNbBillets());
        return $this->redirectToRoute('jd_reservation_completer',
            [
                'id'        => $reservation->getId()
            ]
        );
    }

    public function checkoutReservationAction(SessionInterface $session, Request $request)
    {
        \Stripe\Stripe::setApiKey("sk_test_CGUR0LzqpU5EUhIPfAdqatvm");
        $resa = $session->get('resa');

        // Get the credit card details submitted by the form
        dump($_POST);
        $token = $_POST['stripeToken'];


        // Create a charge: this will charge the user's card
        try {
            $charge = \Stripe\Charge::create(array(
                "amount" => 1000, // Amount in cents
                "currency" => "eur",
                "source" => $token,
                "description" => "Paiement Stripe - OpenClassrooms Exemple"
            ));
            $this->addFlash("success","Bravo ça marche !");
            dump('non la page n\'est bon');
            return $this->redirectToRoute("jd_reservation_success");
        } catch(\Stripe\Error\Card $e) {

            $this->addFlash("error","Snif ça marche pas :(");
            dump('oui la page est bon');
            return $this->redirectToRoute("jd_reservation_panier,
                        [
                            'id' => $resa->getId()
                        ]");
            // The card has been declined
        }
    }
    public  function  facturationAction(Request $request)
    {
        if($request->isXMLHttpRequest())
        {
            $datas = json_decode($request->getContent(), true);
            $token = $datas[0];
            return $this->redirectToRoute('jd_reservation_success');
        }

        return $this->redirectToRoute('jd_reservation_index');
    }

    public function successAction(SessionInterface $session)
    {

        $outilsReser = $this
            ->get('service_container')
            ->get('jd_reservation.outilsreser');

        $resa = $session->get('resa');
        dump($resa);
        $html = $this->renderView('JDLelouvreBundle:Reservation/Mailer:bodyPdf.html.twig', array(
            'resa' => $resa,
        ));
/*
        $snappy = $this->get('knp_snappy.pdf');

        $pdf = new Response(
            $snappy->getOutputFromHtml($html,
                array('orientation'         => 'portrait',
                    'enable-javascript'     => true,
                    'javascript-delay'      => 0,
                    'no-background'         => false,
                    'lowquality'            => false,
                    'page-size'             => 'A4',
                    'encoding'              => 'utf-8',
                    'images'                => true,
                    'cookie'                => array(),
                    'dpi'                   => 300,
                    'image-dpi'             => 300,
                    'enable-external-links' => true,
                    'enable-internal-links' => true,
                    'header-spacing'        => 5,
                    'footer-spacing'        => 5,
                )),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename=Billets'),
            ]
        );

        $attachment = new \Swift_Attachment($pdf, 'billets.pdf', 'application/pdf');
        */
        $message = (new \Swift_Message('Musée du Louvre'))
            ->setContentType("text/html")
            ->setSubject('Confirmation de commande')
            ->setFrom('jobby00@gmail.com')
            ->setTo($resa->getEmail())
            ->setBody( $this->renderView( 'JDLelouvreBundle:Reservation/Mailer:mailer.html.twig', ['resa' => $resa], 'text/html' ));
            //->attach($attachment);
        $this->get('mailer')->send($message);
        return $this->render('JDLelouvreBundle:Reservation:facturation.html.twig',
            [
                'resa'              => $resa
            ]);
    }
}
