<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormError;

class LoginController extends AbstractController
{

    /**
     * May not use builtin authenticationUtils so make it manually
     * @Route("/login", name="login")
     * @param Request $request
     * @param SessionInterface $session
     * @return Response
     */
    public function login(Request $request, SessionInterface $session): Response
    {
        $user = new User();

        $form = $this->createFormBuilder($user)
            ->add('login', TextType::class)
            ->add('password', PasswordType::class)
            ->add('submit', SubmitType::class, ['label' => 'Zaloguj', 'attr' => ['class' => 'btn btn-primary']])
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $formData = $form->getData();

            /*
             * Simplest way to authorize is searching user in db 
             * it issn't best way to do so still no overhead with 
             * doctrine checking password, then loading missing data
             * to user object.
             */

            $user = $this->getDoctrine()->getRepository(User::class)->findOneBy([
                'password' => sha1($formData->getPassword()),
                'login' => $formData->getLogin()
            ]);

            if (null !== $user) {
                $user->setLogged(new \DateTime());
                $session->set('user', $user);
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($user);
                $entityManager->flush();
                return $this->redirect('/');
            } else {
                $form->get('password')->addError(
                    new FormError('Hasło lub login niepoprawne. Czy masz już konto?')
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
            ->add('submit', SubmitType::class, ['label' => 'Załóż', 'attr' => ['class' => 'btn btn-primary']])
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();
            if ($form->get('repeat') === $form->get('password')) {
                $form->get('repeat')->addError(
                    new FormError('Hasła wprowadzone nie są takie same')
                );
            } else {
                $user->setPassword(sha1($user->getPassword()));
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($user);
                $entityManager->flush();
                return $this->redirect('/');
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
        $this->container->get('session')->set('user', null);
        return $this->redirect('/login');
    }
}
