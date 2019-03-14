<?php
// src/Controller/DefaultController.php
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
use App\Entity\Share;
use \Datetime;

class ShareController extends AbstractController
{
	   /**
       * @Route("/give/access/{id}", name="give_access")
       */
     public function giveAccess(Session $session, $id, Request $request)
     {
      $json = "error";
      $emailUser = $request->query->get("emailUser");
      $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['mail' => $emailUser]);
      $template = $this->getDoctrine()->getRepository(Template::class)->findOneById($id);
      if($template != null && $user != null && $user->getId() != intval($session->get("qui")))
      {        
        if($session->get("isValid") == "true" && $template->getCreator() == intval($session->get("qui")))
        {
          $newShare = new Share();
          $newShare->setIdTemplate($id);
          $newShare->setIdUser($user->getId());
          $entityManager = $this->getDoctrine()->getManager();
          $entityManager->persist($newShare);
          $entityManager->flush();

          $json = "done";
        }
      }
      $response = new JsonResponse();
      $json = stripslashes(json_encode($json));
      $response = JsonResponse::fromJsonString($json);

      return $response;
    }
     /**
       * @Route("/cancel/access/{id}", name="cancel_access")
       */
     public function cancelAccess(Session $session, $id, Request $request)
     {
      $json = "error";
      $idUser = $request->query->get("idUser");
      // $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['email' => $email]);
      $template = $this->getDoctrine()->getRepository(Template::class)->findOneById($id);
      $share = $this->getDoctrine()->getRepository(Template::class)->findOneBy(['idUser' => $idUser, 'idTemplate' => $id]);
      if($template != null && $hare != null)
      {        
        if($session->get("isValid") == "true" && $template->getCreator() == intval($session->get("qui")))
        {
          $entityManager = $this->getDoctrine()->getManager();
          $entityManager->remove($share);
          $entityManager->flush();

          $json = "done";
        }
      }
      $response = new JsonResponse();
      $json = stripslashes(json_encode($json));
      $response = JsonResponse::fromJsonString($json);

      return $response;
    }

   /**
     * @Route("/who/access/{id}", name="who_access")
     */
   public function whoAccess(Session $session, $id)
   {
    $json = "nonExistant";
    $template = $this->getDoctrine()->getRepository(Template::class)->findOneById($id);
    if($template != null)
    {        
      if($session->get("isValid") == "true" && $template->getCreator() == intval($session->get("qui")))
      {
        $access = $this->getDoctrine()->getRepository(Share::class)->findBy(['idTemplate' => $id]);
        if($access != null)
        {
          $json = array();
          foreach($access as $val)
          {
            $json["users"][] = $this->getDoctrine()->getRepository(User::class)->findOneById($val->getId())->getUserShare();
          }
        }
        else $json["users"] = null;
      }
    }
    $response = new JsonResponse();
    $json = stripslashes(json_encode($json));
    $response = JsonResponse::fromJsonString($json);

    return $response;
  }


}