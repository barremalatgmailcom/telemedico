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

        dump($request->get('login'));
        dump($request->get('password'));
        dump($request->get('name'));
        die(__METHOD__);
        try {
            $this->checkParameters(['login', 'password', 'name'], $request);
        } catch (Exception $ex) {
            $this->getResponse(['success' => 0, 'message' => $ex->getMessage()]);
        }
        $user = $this->retrieveUser($login, $password);
        $isAuthenticated = $this->isAuthenticable($user);
        die(__METHOD__);
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
    
    private function checkParameters(array $paramList, Request $request): void
    {
        foreach($paramList as $param){
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

    private function isAuthenticable(User $user): bool
    {
        return (null !== $user);
    }
}
