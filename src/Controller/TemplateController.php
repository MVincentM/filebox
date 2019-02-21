<?php
// src/Controller/TemplateController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Package;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\JsonResponse;

use App\Entity\File;
use App\Entity\Folder;
use App\Entity\Template;
use App\Entity\User;

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
       * @Route("/get/templates/{id}", name="get_templates")
       */
     public function getTemplates($id)
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
      $children = $this->getDoctrine()->getRepository(Template::class)->findBy(['parent' => $id]);
      foreach($children as $child)
      {
        $json[] = $child->toJSON();
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