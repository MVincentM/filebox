<?php
// src/Controller/DefaultController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Package;
use Symfony\Component\HttpFoundation\Session\Session;

class DefaultController extends AbstractController
{
	   /**
       * @Route("", name="index")
       */
     public function index(Session $session)
     {
       if($session->get('isValid') == "true")
       {
        return $this->render('file-explorer.html.twig', [  
          "racine" => $session->get('racine')      
        ]);
      }

      // return $this->render('presentation.html.twig', [      
      // ]);
      return $this->redirectToRoute('login');
    }
      
	   /**
       * @Route("/login", name="login")
       */
    public function login()
    {
      return $this->render('connexion.html.twig', [
      ]);
    }
	   /**
       * @Route("/deconnexion", name="deconnexion")
       */
    public function deconnexion(Session $session)
    {
      $session->set('isValid', 'false');
      $session->set('qui', 'nope');

      return $this->redirectToRoute('index');
    }
  }