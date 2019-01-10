<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{

    /**
     * @Route("/", name="index")
     */
    public function index()
    {
        if (null === $this->container->get('session')->get('user')) {
            return $this->redirect('/login');
        }

        return $this->render('index/index.html.twig', [
                'user' => $this->container->get('session')->get('user')
        ]);
    }
}
