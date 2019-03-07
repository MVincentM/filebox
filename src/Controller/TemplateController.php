<?php
// src/Controller/TemplateController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Package;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\File;
use App\Entity\Folder;
use App\Entity\Template;
use App\Entity\User;
use \Datetime;

class TemplateController extends AbstractController
{
   /**
     * @Route("/fichiers", name="view_templates")
     */
   public function viewTemplate()
   {

    return $this->render('file-explorer.html.twig', 
      array(
      ));
  }

  /**
    * @Route("/api/get/racine", name="get_racine")
    */
  public function getRacine(Session $session, Request $request)
  {
    $authkey = $request->query->get("authkey");
    $res = "error";
    if($authkey != null)
    { 
      $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['authkey' => $authkey]);
      if($user != null)
      {
        $dateNom = new \Datetime();
        $dateKey = $user->getDateKey();
        $interval = $dateNom->diff($dateKey);

        if(intval($interval->format('%R%a') >= 0))
        {
          $ipSaved = $user->getIp();
          $ipNom = hash("md5",UserController::get_ip());

          if($ipNom == $ipSaved)
          {
            $res = $this->getDoctrine()->getRepository(Template::class)->findOneBy(['creator' => $user->getId(), 'parent' => NULL])->getId();
          }
        }
      }
    }

    $response = new JsonResponse();

    $json = stripslashes(json_encode($res));
    $response = JsonResponse::fromJsonString($json);

    return $response;  
  }
     /**
       * @Route("/get/templates/{id}", name="get_templates")
       */
     public function getTemplates($id, Session $session)
     {
      // for($i=0;$i<10;$i++)
      // {
      //   $template = new Folder();
      //   $template->setCreator(0);
      //   $template->setLastModificator(0);
      //   $template->setName("Folder ".$i);
      //   $template->setParent(0);
      //   $template->setPath("/pathTest");

      //   $entityManager = $this->getDoctrine()->getManager();
      //   $entityManager->persist($template);
      //   $entityManager->flush();
      // }
      $json = array();
      $children = $this->getDoctrine()->getRepository(Template::class)->findBy(['parent' => $id, 'creator' => $session->get('qui')]);
      foreach($children as $child)
      {
        $jsonTemp = $child->toJSON(); 
        $jsonTemp["creator"] = $this->getDoctrine()->getRepository(User::class)->findOneBy(['id' => intval($jsonTemp["creator"])])->getUserName();
        $jsonTemp["lastUpdator"] = $this->getDoctrine()->getRepository(User::class)->findOneBy(['id' => intval($jsonTemp["lastUpdator"])])->getUserName();
        $json[] = $jsonTemp;
      }

      $response = new JsonResponse();

      // echo var_dump($json);
      $json = stripslashes(json_encode($json));
      // return new Response($json);
      // return $this->json($json);
      $response = JsonResponse::fromJsonString($json);

      return $response;
    }
  }