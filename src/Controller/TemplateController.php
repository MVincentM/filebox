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
  public function verifyAuthKey($authkey)
  {
    $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['authkey' => $authkey]);
    $res = -1;
    if($authkey != null)
    { 
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
    return $res;
  }
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
    $verif = $this->verifyAuthKey($authkey);

    if($verif > -1) $res = $verif;

    $response = new JsonResponse();

    $json = stripslashes(json_encode($res."b"));
    $response = JsonResponse::fromJsonString($json);

    return $response;  
  }
     /**
       * @Route("/get/templates/{id}", name="get_templates")
       */
     public function getTemplates($id, Session $session)
     {
      $template =  $this->getDoctrine()->getRepository(Template::class)->findOneById($id);
      $json = "error";
      if($session->get("isValid") == "true" && $template->getCreator() == $this->getDoctrine()->getRepository(User::class)->findOneById(intval($session->get("qui")))->getId() && ctype_digit($id))
      {
        $json = array();
        $children = $this->getDoctrine()->getRepository(Template::class)->findBy(['parent' => $id, 'creator' => $session->get('qui')]);
        foreach($children as $child)
        {
          $jsonTemp = $child->toJSON(false); 
          $jsonTemp["creator"] = $this->getDoctrine()->getRepository(User::class)->findOneBy(['id' => intval($jsonTemp["creator"])])->getUserName();
          $jsonTemp["lastUpdator"] = $this->getDoctrine()->getRepository(User::class)->findOneBy(['id' => intval($jsonTemp["lastUpdator"])])->getUserName();
          $json[] = $jsonTemp;
        }
      }
      $response = new JsonResponse();

      // echo var_dump($json);
      $json = stripslashes(json_encode($json));

      // return new Response($json);
      // return $this->json($json);
      $response = JsonResponse::fromJsonString($json);

      return $response;
    }
     /**
       * @Route("/api/get/templates/{id}", name="api_get_templates")
       */
     public function getTemplatesAPI($id, Request $request)
     {
      $template =  $this->getDoctrine()->getRepository(Template::class)->findOneById($id);
      $authkey = $request->query->get("authkey");
      $verif = $this->verifyAuthKey($authkey);
      $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['authkey' => $authkey]);
      $json = "error";

      if($verif > -1 && ctype_digit($id))
      {
        $json = array();

        $children = $this->getDoctrine()->getRepository(Template::class)->findBy(['creator' => $user->getId()]);
        foreach($children as $child)
        {
          $jsonTemp = $child->toJSON(true); 
          $jsonTemp["creator"] = $this->getDoctrine()->getRepository(User::class)->findOneBy(['id' => intval($jsonTemp["creator"])])->getUserName();
          $jsonTemp["lastUpdator"] = $this->getDoctrine()->getRepository(User::class)->findOneBy(['id' => intval($jsonTemp["lastUpdator"])])->getUserName();
          $json[] = $jsonTemp;
        }
      }
      $response = new JsonResponse();

      // echo var_dump($json);
      $json = stripslashes(json_encode($json));

      // return new Response($json);
      // return $this->json($json);
      $response = JsonResponse::fromJsonString($json);

      return $response;
    }
     /**
       * @Route("/api/insert/template", name="api_insert_templates")
       */
     public function insertTemplateAPI(Request $request)
     {
      $authkey = $request->query->get("authkey");
      $type = $request->query->get("type");
      $nameFile = $request->query->get("nameFile");
      $dateModif = $request->query->get("dateLastUpdate");
      $path = $request->query->get("path");


      $verif = $this->verifyAuthKey($authkey);
      $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['authkey' => $authkey]);
      $json = "error";

      if($verif > -1)
      {
        $newTemplate;
        if($type == "file") $newTemplate = new File();
        else if($type == "folder") $newTemplate = new Template();
        $newTemplate->setCreator($user->getId());
        $newTemplate->setLastModificator($user->getId());
        $newTemplate->setName($nameFile);
        $newTemplate->setParent($this->getDoctrine()->getRepository(Template::class)->findOneBy(['creator' => $user->getId(), 'parent' => NULL])->getId());
        $newTemplate->setPath($path);
        $date = new DateTime();
        $date->setTimestamp($dateModif);
        $newTemplate->setLastUpdate($date);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($newTemplate);
        $entityManager->flush();

        $json = $newTemplate->getId();
      }
      $response = new JsonResponse();

      // echo var_dump($json);
      $json = stripslashes(json_encode($json));

      // return new Response($json);
      // return $this->json($json);
      $response = JsonResponse::fromJsonString($json);

      return $response;
    }
     /**
       * @Route("/api/update/template", name="api_update_templates")
       */
     public function updateTemplateAPI(Request $request)
     {
      $authkey = $request->query->get("authkey");
      $dateModif = $request->query->get("dateLastUpdate");
      $path = $request->query->get("path");
      $name = $request->query->get("nameFile");

      $verif = $this->verifyAuthKey($authkey);
      $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['authkey' => $authkey]);
      $json = "error";

      if($verif > -1)
      {
        $date = new DateTime();
        $date->setTimestamp($dateModif);
        $template = $this->getDoctrine()->getRepository(Template::class)->findOneBy(['name' => $name, 'path' => $path]);
        $template->setLastUpdate($date);

        $entityManager = $this->getDoctrine()->getManager();
        // $entityManager->remove($template);
        $entityManager->flush();

        $json = $template->getId();
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