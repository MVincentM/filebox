<?php
// src/Controller/UserController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Package;
use App\Entity\User;
use App\Entity\Association;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\RedirectResponse;


class UserController extends AbstractController
{
      /**
       * @Route("verifier/connexion", name="verifier_connexion")
       */
  public function verifier_connexion(Request $request)
    {
        $session = new Session();
        $session->start();
        $session->set('isValid', 'false');
        
        $email = $request->request->get('email');
        $mdp = $request->request->get('pass');
        $res = "false";

        $session->set('qui', 'nope');
        $session->set('typeCompte', 'nope');

        if($this->getDoctrine()->getRepository(User::class)->findOneBy(['mail' => $email,'mdp' => $mdp]))
        {
            $res = "true";

            $session->set('isValid', 'true');
            $session->set('qui', $this->getDoctrine()->getRepository(User::class)->findOneBy(['mail' => $email,'mdp' => $mdp])->getId());
            $session->set('typeCompte', 'user');

            return $this->redirectToRoute('index');
        }

      return new Response('User : '.$res);
    }

     /**
       * @Route("/new", name="new")
       */

    public function new(Request $request)
    {
        return $this->render('new_compte.html.twig', [
        ]);
    }

     /**
       * @Route("/new/user", name="new_user")
       */

    public function new_user(Request $request)
    {
        if ($request->request->get('email') && $request->request->get('pass1') && $request->request->get('pass2') && $request->request->get('nom') && $request->request->get('prenom') && $request->request->get('telephone')) {
            
            $entityManager = $this->getDoctrine()->getManager();
            $user = new User();
            $user->setNom($request->request->get('nom'));
            $user->setPrenom($request->request->get('prenom'));
            $user->setMail($request->request->get('email'));
            $user->setMdp($request->request->get('pass1'));
            $user->setNumero($request->request->get('telephone'));

            // tell Doctrine you want to (eventually) save the Product (no queries yet)
            $entityManager->persist($user);

            // actually executes the queries (i.e. the INSERT query)
            $entityManager->flush();

            return new Response('Nouveau User avec lid : '.$user->getId());
        }
        else 
        {
            return $this->render('bas_pas_Co.html.twig', [
            ]);
        }
    }
}