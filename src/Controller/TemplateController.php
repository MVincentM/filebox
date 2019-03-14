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
use App\Entity\Share;
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

        $share = $this->getDoctrine()->getRepository(Share::class)->findBy(['idUser' => $session->get("qui")]);
        foreach($share as $val)
        {
          $temp = $this->getDoctrine()->getRepository(Template::class)->findOneById($val->getIdTemplate());
          $jsonTemp = $temp->toJSON(false); 
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
       * @Route("/add/folder", name="api_add_folder")
       */
     public function addFolder(Session $session, Request $request)
     {
      $json = "error";
      $nameFolder = $request->query->get("nameFolder");
      $id = intval($request->query->get("id"));

      $user = $this->getDoctrine()->getRepository(User::class)->findOneById(intval($session->get("qui")));
      $template = $this->getDoctrine()->getRepository(Template::class)->findOneById($id);

      if($session->get("isValid") == "true" && $template != null && $template->getCreator() == $user->getId())
      {
        if($this->getDoctrine()->getRepository(Template::class)->findOneBy(['name' => $nameFolder, 'creator' => $user->getId(), 'parent' => $template->getId()]) == null) 
        {          
          $folder = new Folder();
          $folder->setCreator($user->getId());
          $folder->setLastModificator($user->getId());
          $folder->setName($nameFolder);
          $templateParent = $this->getDoctrine()->getRepository(Template::class)->findOneById($id);
          $folder->setParent($id);
          $folder->setPath($templateParent->getPath());
          $date = new DateTime();
          $folder->setLastUpdate($date);

          $entityManager = $this->getDoctrine()->getManager();
          $entityManager->persist($folder);
          $entityManager->flush();
          $json = $folder->getId();
        }
        else $json = "already";
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
       * @Route("/rename/template", name="rename_template")
       */
     public function renameTemplate(Session $session, Request $request)
     {
      $json = "error";
      $nameTemplate = $request->query->get("nameTemplate");
      $id = intval($request->query->get("id"));

      $user = $this->getDoctrine()->getRepository(User::class)->findOneById(intval($session->get("qui")));
      $template = $this->getDoctrine()->getRepository(Template::class)->findOneById($id);

      if($session->get("isValid") == "true" && $template != null && $template->getCreator() == $user->getId())
      {
        if($this->getDoctrine()->getRepository(Template::class)->findOneBy(['name' => $nameTemplate, 'creator' => $user->getId(), 'parent' => $template->getId()]) == null) 
        {  
          $template->setName($nameTemplate);

          $entityManager = $this->getDoctrine()->getManager();
          $entityManager->flush();
          $json = $template->getId();
        }
        else $json = "already";
        
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
       * @Route("/delete/template/{id}", name="delete_template")
       */
     public function deleteTemplate($id, Session $session, Request $request)
     {
      $json = "error";

      $user = $this->getDoctrine()->getRepository(User::class)->findOneById(intval($session->get("qui")));
      $template = $this->getDoctrine()->getRepository(Template::class)->findOneById($id);

      if($session->get("isValid") == "true" && $template != null && $template->getCreator() == $user->getId())
      {
        $children = $this->getDoctrine()->getRepository(Template::class)->findBy(['parent' => $id]);
        $entityManager = $this->getDoctrine()->getManager();

        if($children != null)
        { 
          foreach($children as $templ)
          {
            $entityManager->remove($templ);
          }

        }
        $entityManager->remove($template);
        $entityManager->flush();
        $json = "done";
        
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
       * @Route("/add/files/in/{id}", name="add_files")
       */
     public function addFiles($id, Session $session, Request $request)
     {
      $json = "error";
      $template = $this->getDoctrine()->getRepository(Template::class)->findOneById($id);
      $user = $this->getDoctrine()->getRepository(User::class)->findOneById(intval($session->get("qui")));
      $fileName = $_FILES["file"]["name"];

      if($session->get("isValid") == "true" && $this->getDoctrine()->getRepository(Template::class)->findOneBy(['name' => $fileName, 'creator' => $user->getId(), 'parent' => $template->getId()]) == null)
      {
        $newTemplate = new File();
        $newTemplate->setCreator($user->getId());
        $newTemplate->setLastModificator($user->getId());
        $newTemplate->setName($fileName);
        $newTemplate->setParent($id);
        $newTemplate->setPath($template->getPath());
        $date = new DateTime();
        $newTemplate->setLastUpdate($date);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($newTemplate);
        $entityManager->flush();
        $idF = $newTemplate->getId();
        $json = "bddok";
        
      }
      if($json == "bddok")
      {
        $fileName = $idF."";
        $json = "uploadFailed";
        $repertoireDestination = "serveurTemplates/";
        if (is_uploaded_file($_FILES["file"]["tmp_name"])) {
          if (move_uploaded_file($_FILES["file"]["tmp_name"],$repertoireDestination.$fileName.$_FILES["file"]["type"])) {
            echo "Le fichier temporaire ".$_FILES["file"]["tmp_name"].
            " a ete deplace vers ".$repertoireDestination.$fileName;
            $json = "done";
                        // chmod($repertoireDestination.$fileName, 0644);
                          // header("Location: http:/localhost:8000");
          } else {
            echo "Le déplacement du fichier temporaire a échoué".
            " verifiez lexistence du repertoire ".$repertoireDestination;
          }          
        } else {
          echo "Le fichier na pas ete uploade (trop gros ?)";
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
        else if($type == "folder") $newTemplate = new Folder();
        $newTemplate->setCreator($user->getId());
        $newTemplate->setLastModificator($user->getId());
        $newTemplate->setName($nameFile);
        $newTemplate->setParent($this->getDoctrine()->getRepository(Template::class)->findOneBy(['creator' => $user->getId(), 'parent' => $path, "discr" => 'folder'])->getId());
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
     /**
       * @Route("/api/get/id/template", name="api_getid_template")
       */
     public function getIdTemplateAPI(Request $request)
     {
      $authkey = $request->query->get("authkey");
      $path = $request->query->get("path");
      $name = $request->query->get("nameFile");

      $verif = $this->verifyAuthKey($authkey);
      $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['authkey' => $authkey]);
      $json = "error";

      if($verif > -1)
      {
        $template = $this->getDoctrine()->getRepository(Template::class)->findOneBy(['name' => $name, 'path' => $path]);
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
    /**
      * @Route("/download/template/{id}", name="download_template")
      */
    public function downloadTemplate(Request $request, $id)
    {
      $json = "error";
      $fileId = $request->request->get("fileId");
      $file = $this->getDoctrine()->getRepository(Template::class)->findOneById($id);
      $fileShared = $this->getDoctrine()->getRepository(Share::class)->findOneBy(['idTemplate' => $id]);
      if($file != null)
      {
        $json = "ok";

        if (file_exists("/serveurTemplates/" . basename($file->getName())) && $file->getCreator() == intval($session->get("qui")) )
        {
          $size = filesize("php/files/" . basename($file));
          header("Content-Type: application/force-download; name=\"" . basename($file) . "\"");
          header("Content-Transfer-Encoding: binary");
          header("Content-Length: $size");
          header("Content-Disposition: attachment; filename=\"" . basename($file) . "\"");
          header("Expires: 0");
          header("Cache-Control: no-cache, must-revalidate");
          header("Pragma: no-cache");
          readfile("php/files/" . basename($file));
          exit();
        }
      }
      else if($fileShared != null)
      {
        $json = "ok";
        if (file_exists("/serveurTemplates/" . basename($fileShared->getName())) && $fileShared->getIdUser() == intval($session->get("qui")))
        {
          $size = filesize("php/files/" . basename($fileShared));
          header("Content-Type: application/force-download; name=\"" . basename($fileShared) . "\"");
          header("Content-Transfer-Encoding: binary");
          header("Content-Length: $size");
          header("Content-Disposition: attachment; filename=\"" . basename($fileShared) . "\"");
          header("Expires: 0");
          header("Cache-Control: no-cache, must-revalidate");
          header("Pragma: no-cache");
          readfile("php/files/" . basename($fileShared));
          exit();
        }
      }
      $response = new JsonResponse();
      $json = stripslashes(json_encode($json));
      $response = JsonResponse::fromJsonString($json);
      return $response;
    }
  }