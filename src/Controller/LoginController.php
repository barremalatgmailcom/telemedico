<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
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
            ->add('submit', SubmitType::class, ['label' => 'Zaloguj', 'attr'=>['class'=>'btn btn-primary']])
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();

            $isLogged = $this->getDoctrine()
                ->getRepository(User::class)
                ->isLoginPasswordOk($user);

            if ($isLogged) {
                
            } else {
                $form->get('repeat')->addError(
                    new FormError('Hasła wprowadzone nie są takie same')
                );
            }
        }

        return $this->render('login/login.html.twig', [
                'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/signup", name="signup")
     */
    public function signup(Request $request): Response
    {
        $form = $this->createFormBuilder(new User())
            ->add('login', TextType::class, ['label' => 'podaj login', 'required' => true])
            ->add('password', PasswordType::class, ['label' => 'podaj hasło', 'required' => true])
            ->add('repeat', PasswordType::class, ['label' => 'powtórz hasło', 'mapped' => false, 'required' => true])
            ->add('name', TextType::class, ['label' => 'podaj imię', 'required' => true])
            ->add('submit', SubmitType::class, ['label' => 'Zaloguj', 'attr'=>['class'=>'btn btn-primary']])
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();

            if ($form->get('repeat') === $form->get('password')) {
                $form->get('repeat')->addError(
                    new FormError('Hasła wprowadzone nie są takie same')
                );
            } else {
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($user);
                $entityManager->flush();
                return $this->redirect('/login');
            }
        }

        return $this->render('login/signup.html.twig', [
                'form' => $form->createView(),
        ]);
    }

    /**
     * 
     * @Route("/logout", name="logout")
     */
    public function logout(Request $request): Response
    {
        return $this->redirect('/login');
    }
}
