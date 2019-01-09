<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class LoginController extends AbstractController
{

    /**
     * May not use builtin authenticationUtils so make it manually
     * 
     * @Route("/login", name="login")
     */
    public function login(Request $request): Response
    {
        $user = new User();

        $form = $this->createFormBuilder($user)
            ->add('login', TextType::class)
            ->add('password', PasswordType::class)
            ->add('zaloguj', SubmitType::class, array('label' => 'Create Task'))
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();
        }

        return $this->render('login/login.html.twig', array(
                'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/signup", name="signup")
     */
    public function signup(Request $request): Response
    {
        $user = new User();

        $form = $this->createFormBuilder($user)
            ->add('login', TextType::class, ['label' => 'podaj login'])
            ->add('password', PasswordType::class, ['label' => 'podaj hasło'])
            ->add('repeat', PasswordType::class, ['label' => 'powtórz hasło','mapped'=>false])
            ->add('name', TextType::class, ['label' => 'podaj imię'])
            ->add('zapisz', SubmitType::class)
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();
        }

        return $this->render('login/login.html.twig', array(
                'form' => $form->createView(),
        ));
    }
}
