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
use Symfony\Component\HttpFoundation\JsonResponse;


class UserController extends AbstractController
{
    public function get_ip() {
            // IP si internet partagé
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }
            // IP derrière un proxy
        elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
            // Sinon : IP normale
        else {
            return (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '');
        }
    }

        /**
         * @Route("/api/connexion", name="api_connexion")
         */
        public function api_connexion(Request $request,Session $session)
        {
          $email = $request->query->get('email');
          $mdp = $request->query->get('pass');

          $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['mail' => $email]);
          $res = false;

          //on part du principe qu'on est en https donc mdp pas en clair !!
          if(password_verify($mdp,$user->getMdp()))
          {
              $res = true;
              $user->setIp(UserController::get_ip());

              $entityManager = $this->getDoctrine()->getManager();
              $entityManager->flush();
          }

          $response = new JsonResponse();

          $json = stripslashes(json_encode($res));
          $response = JsonResponse::fromJsonString($json);

          return $response;
      }

      /**
       * @Route("/verifier/connexion", name="verifier_connexion")
       */
      public function verifier_connexion(Request $request, Session $session)
      {
        $session->set('isValid', 'false');
        
        $email = $request->request->get('email');
        $mdp = $request->request->get('pass');
        $res = "false";
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['mail' => $email]);


        $session->set('qui', 'nope');
        $session->set('typeCompte', 'nope');

        if(password_verify($mdp,$user->getMdp()))
        {
            $res = "true";

            $session->set('isValid', 'true');
            $session->set('qui', $user->getId());
            $session->set('typeCompte', 'user');

            return $this->redirectToRoute('index');
        }

        return new Response('User : '.$res);
    }

     /**
       * @Route("/inscription", name="new")
       */

     public function new(Request $request)
     {
        return $this->render('inscription.html.twig', [
        ]);
    }

     /**
       * @Route("/new/user", name="new_user")
       */

     public function new_user(Request $request)
     {
        if ($request->request->get('email') && $request->request->get('pass1') && $request->request->get('pass2') && $request->request->get('nom') && $request->request->get('prenom')) {

            $entityManager = $this->getDoctrine()->getManager();
            $user = new User();
            $user->setNom($request->request->get('nom'));
            $user->setPrenom($request->request->get('prenom'));
            $user->setMail($request->request->get('email'));
            $user->setMdp(password_hash($request->request->get('pass1'), PASSWORD_BCRYPT));

            $dossierRacine = new Folder();
            $dossierRacine->setCreator($user->getId());
            $dossierRacine->setLastModificator($user->getId());
            $dossierRacine->setName("Racine");
            $dossierRacine->setParent(null);
            $dossierRacine->setPath("/pathTest");

            // tell Doctrine you want to (eventually) save the Product (no queries yet)
            $entityManager->persist($user);
            $entityManager->persist($dossierRacine);

            // actually executes the queries (i.e. the INSERT query)
            $entityManager->flush();

            // return new Response('Nouveau User avec lid : '.$user->getId());
            return $this->redirectToRoute('index');

        }
        else 
        {
            return $this->redirectToRoute('new');

            // return $this->render('bas_pas_Co.html.twig', [
            // ]);
        }
    }
}