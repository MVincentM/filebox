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

use App\Entity\File;
use App\Entity\Folder;
use App\Entity\Template;
use \Datetime;
use \DateInterval;

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

    public function genererChaineAleatoire($longueur)
    {
     $caracteres = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
     $longueurMax = strlen($caracteres);
     $chaineAleatoire = '';
     for ($i = 0; $i < $longueur; $i++)
     {
     $chaineAleatoire .= $caracteres[rand(0, $longueurMax - 1)];
     }
     return $chaineAleatoire;
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
              $user->setIp(hash("md5",UserController::get_ip()));
              $date = new \Datetime();
              $user->setDateKey($date->add(new \DateInterval('P1D')));
              $user->setAuthkey(UserController::genererChaineAleatoire(20));
              $res = "f".$user->getAuthkey();
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

        if($user)
        {            
            if(password_verify($mdp,$user->getMdp()))
            {
                $res = "true";

                $session->set('isValid', 'true');
                $session->set('qui', $user->getId());
                $session->set('typeCompte', 'user');
                $session->set('racine',$this->getDoctrine()->getRepository(Template::class)->findOneBy(['creator' => $user->getId(), 'parent' => NULL])->getId());
                return $this->redirectToRoute('index');
            }
            else return $this->redirectToRoute('login');
        }
        else return $this->redirectToRoute('login');

        // return new Response('User : '.$res);
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
            $entityManager->persist($user);
            $entityManager->flush();

            $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['mail' => $request->request->get('email')]);
            $dossierRacine = new Folder();
            $dossierRacine->setCreator($user->getId());
            $dossierRacine->setLastModificator($user->getId());
            $dossierRacine->setName("Racine");
            $dossierRacine->setPath("/pathTest");
            $dossierRacine->setLastUpdate(new \Datetime());

            $entityManager->persist($dossierRacine);
            $entityManager->flush();

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