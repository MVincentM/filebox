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
    		return $this->render('filebox.html.twig', [          
        	]);
    	}

        return $this->render('presentation.html.twig', [      
        ]);
    }
	   /**
       * @Route("/login", name="login")
       */
	public function login()
    {
        return $this->render('login.html.twig', [
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