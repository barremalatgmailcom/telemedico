<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use App\Entity\User;

/**
 * @Route("/api", name="api_")
 */
class ApiController extends AbstractController
{

    const HTTP_NOT_FOUND = 404;
    const HTTP_INTERNAL_ERROR = 500;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_OK = 200;
    const HTTP_METHOD_NOT_ALLOWED = 405;

    /**
     * Just render documentation
     * @Route("/", name="collection")
     */
    public function index(): Response
    {
        return $this->render('api/index.html.twig');
    }

    /**
     * @Route("/create", name="create")
     */
    public function create(Request $raw): Response
    {
        try {
            $requestData = $this->unserializeRequest($raw);
            $this->authenticate($requestData);
            $user = $this->buildUserFromUserData($requestData);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();
        } catch (Exception $ex) {

            return $this->getResponse(
                    [
                        'status' => $ex->getCode(),
                        'detail' => $ex->getMessage(),
                    ]
            );
        }

        return $this->getResponse(['status' => HTTP_OK, 'user' => $user]);
    }

    /**
     * @Route("/read", name="read")
     */
    public function read(Request $raw): Response
    {
        try {
            $this->authenticate($request);
            $users = $this->getDoctrine()->getRepository(User::class)->findAll();
        } catch (Exception $ex) {

            return $this->getResponse(
                    [
                        'status' => $ex->getCode(),
                        'detail' => $ex->getMessage(),
                    ]
            );
        }

        return $this->getResponse(['status' => HTTP_OK, 'users' => $users]);
    }

    /**
     * @Route("/read/{id}", name="read_id")
     */
    public function readOne(string $id): Response
    {
        try {
            $this->authenticate($request);
            $user = $this->getDoctrine()->getRepository(User::class)->find($id);
        } catch (Exception $ex) {

            return $this->getResponse(
                    [
                        'status' => $ex->getCode(),
                        'detail' => $ex->getMessage(),
                    ]
            );
        }

        return $this->getResponse(['status' => HTTP_OK, $id, 'user' => $user]);
    }

    /**
     * @Route("/delete/{id}", name="delete_id")
     */
    public function delete(string $id, Request $request): Response
    {
        try {
            $this->authenticate($request);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove(
                $this->getDoctrine()->getRepository(User::class)->find($id)
            );
            $entityManager->flush();
        } catch (Exception $ex) {

            return $this->getResponse(
                    [
                        'status' => $ex->getCode(),
                        'detail' => $ex->getMessage(),
                    ]
            );
        }

        return $this->getResponse(['status' => HTTP_OK, 'method' => __METHOD__]);
    }

    /**
     * @Route("/update/{id}", name="update_id")
     */
    public function update(string $id, Request $request): Response
    {

        try {
            $this->authenticate($request);
            $entityManager = $this->getDoctrine()->getManager();
            $user = $this->getDoctrine()->getRepository(User::class)->find($id);
            $entityManager->flush();
        } catch (Exception $ex) {

            return $this->getResponse(
                    [
                        'status' => $ex->getCode(),
                        'detail' => $ex->getMessage(),
                    ]
            );
        }

        return $this->getResponse(['status' => HTTP_OK, 'method' => __METHOD__]);
    }

    /**
     * check if is associative array due to rest _get XSSI
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
        if (!$this->isAssoc($responseData)) {
            throw new \Exception(
            "response array should be associative", self::HTTP_INTERNAL_ERROR
            );
        }

        return new JsonResponse($responseData);
    }

    public function authenticate(Request $raw): void
    {
        $auth = $raw->get('auth', null);
        if (null === $auth) {
            throw new \Exception(
            "brak danych logowania", self::HTTP_UNAUTHORIZED
            );
        }

        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy([
            'password' => sha1($auth['password']),
            'login' => $auth['login']
        ]);

        if (null === $user) {
            throw new \Exception(
            "Nieprawidłowy login lub hasło", self::HTTP_UNAUTHORIZED
            );
        }
    }

    public function unserializeRequest(
        Request $raw,
        array $allowedMethods = ['POST', 'GET']
    ): array {
        if (in_array($raw->getMethod(), $allowedMethods)) {
            throw new \Exception(
            "Nieprawidłowy login lub hasło", self::HTTP_METHOD_NOT_ALLOWED
            );
        }

        return json_decode($raw->getContent(), true);
    }

    public function buildUserFromUserData(array $userData, int $id = null): User
    {
        if (null === $id) {
            $user = new User();
        } else {
            $user = $this->getDoctrine()->getRepository(User::class)->find($id);
        }

        if (null !== $userData['name']) {
            $user->setName($userData['name']);
        }
        if (null !== $userData['login']) {
            $user->setLogin($userData['login']);
        }
        if (null !== $userData['password']) {
            $user->setPassword($userData['password']);
        }

        return $user;
    }
}
