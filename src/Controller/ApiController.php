<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class ApiController extends AbstractController
{

    /**
     * @Route("/api/", name="index")
     */
    public function index(Request $request): Response
    {


        return $this->render('api/index.html.twig');
    }

    /**
     * @Route("/api/create", name="create")
     */
    public function create(Request $request): Response
    {
        try {
            $data = $this->translateParams(
                ['login', 'password', 'name',], $request
            );
        } catch (Exception $ex) {
            $this->getResponse(['success' => 0, 'message' => $ex->getMessage()]);
        }

        $user = $this->retrieveUser($data['login'], $data['password']);
        try {
            $isAuthenticated = $this->isAuthenticable($user);
        } catch (Exception $ex) {
            return $this->getResponse(['success' => 1, 'user' => $user]);
        }

//        $user->setName($data['name']);
//
//        $entityManager = $this->getDoctrine()->getManager();
//        $entityManager->persist($user);
//        $entityManager->flush();

        return $this->getResponse(['success' => 1, 'user' => $user]);
    }

    /**
     * @Route("/api/read", name="read")
     */
    public function read(Request $request): Response
    {

        return $this->getResponse(['success' => 1]);
    }

    /**
     * @Route("/api/delete/{id}", name="delete")
     */
    public function delete(Request $request): Response
    {

        return $this->getResponse(['success' => 1]);
    }

    /**
     * @Route("/api/update", name="update")
     */
    public function update(Request $request): Response
    {


        return $this->getResponse(['success' => 1]);
    }

    private function isAuthorized(): bool
    {
        return true;
    }

    /**
     * check if is associative array due to rest get XSSI
     * @param array $array
     * @return boolean
     */
    private function isAssoc(array $array): bool
    {
        if (array() === $array) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    private function getResponse(array $responseData): JsonResponse
    {
        if ($this->isAssoc($responseData)) {
            throw new \Exception("response array should be associative");
        }

        return new JsonResponse($responseData);
    }

    /**
     * Simple array translation, in larger scale i should go with adapter
     * 
     * @param array $paramList
     * @param Request $request
     */
    private function translateParams(array $paramList, Request $request): void
    {
        $translation = [];
        foreach ($paramList as $param => $translation) {
            $request->get($param);
        }
    }

    private function retrieveUser($login, $password): ?User
    {
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy([
            'password' => sha1($password),
            'login' => $login
        ]);

        return $user;
    }

    /**
     * Simple authentication with userdata
     */
    private function isAuthenticable(User $user): bool
    {
        return (null !== $user);
    }
}
