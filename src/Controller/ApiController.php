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
    const CONTENT_TYPE = ["content-type" => "application/json"];

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
            $request = $this->unserializeRequest($raw);
            $this->authenticate($request);
            $user = $this->buildUserFromUserData($request);
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

        return $this->getResponse(['user' => $user]);
    }

    /**
     * @Route("/read", name="read")
     */
    public function read(Request $raw): Response
    {
        try {
            $request = $this->unserializeRequest($raw);
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

        return $this->getResponse(['users' => $users]);
    }

    /**
     * @Route("/read/{id}", name="read_id")
     */
    public function readOne(string $id): Response
    {
        try {
            $request = $this->unserializeRequest($raw);
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

        return $this->getResponse([$id, 'user' => $user]);
    }

    /**
     * @Route("/delete/{id}", name="delete_id")
     */
    public function delete(string $id, Request $request): Response
    {
        try {
            $request = $this->unserializeRequest($raw);
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

        return $this->getResponse(['method' => __METHOD__]);
    }

    /**
     * @Route("/update/{id}", name="update_id")
     */
    public function update(string $id, Request $request): Response
    {
        try {
            $request = $this->unserializeRequest($raw);
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

        return $this->getResponse(['method' => __METHOD__]);
    }

    /**
     * Format response data array to response json,
     * object of type inherited from Response
     * @param array $payload
     * @param int $status
     * @return JsonResponse
     */
    private function getResponse(array $payload, int $status = self::HTTP_OK): JsonResponse
    {
        return JsonResponse::fromJsonString(
            json_encode([
                'status'    => $status,
                'payload'   => $payload
            ]),
            $status,
            self::CONTENT_TYPE
        );
    }

    /**
     * Authenticate user based on existence in db,
     * simple way but not best, yet should be enought for test purposes
     * @param array $request
     * @throws \Exception
     */
    public function authenticate(array $request): void
    {
        if (null === $request['auth']) {
            throw new \Exception(
            "brak danych logowania", self::HTTP_UNAUTHORIZED
            );
        }

        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy([
            'password'  => sha1($request['auth']['password']),
            'login'     => $request['auth']['login']
        ]);

        if (null === $user) {
            throw new \Exception(
            "Nieprawidłowy login lub hasło", self::HTTP_UNAUTHORIZED
            );
        }
    }

    /**
     * Fetches array from raw request json body to associative array
     * @param Request $raw
     * @param array $allowedMethods
     * @return array
     * @throws \Exception
     */
    public function unserializeRequest(
    Request $raw, array $allowedMethods = ['POST', 'GET']
    ): array
    {
        if (!in_array($raw->getMethod(), $allowedMethods)) {
            throw new \Exception(
            "Nieprawidłowe wywołanie {$raw->getMethod()}", self::HTTP_METHOD_NOT_ALLOWED
            );
        }

        return json_decode($raw->getContent(), true);
    }

    /**
     * for test purposes it is here, normally it would be factory method
     * to decouple user and avoid loose contract made by array type in parameter
     * @param array $userData
     * @param int $id
     * @return User
     */
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
