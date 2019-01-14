<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use App\Entity\User;
use App\Entity\Log;

/**
 * --- note on psr ---
 * Basically it'd base on psr-2 with custom psr formatter in netbeans, 
 * still nb seems to mess up few things as multi line arguments etc.
 * It migth be there will be some uncatched
 * 
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
    const FAIL = 0;
    const OK = 1;

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
        $this->log($request->getContent(), __METHOD__);

        try {
            $request = $this->unserializeRequest($raw);
            $this->authenticate($request);
            $user = $this->buildUserFromUserData($request);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();
        } catch (UniqueConstraintViolationException $exm) {
            return $this->getResponse(
                    [
                    'detail' => "Użytkownik istnieje",
                    ], self::HTTP_INTERNAL_ERROR
            );
        } catch (Exception $ex) {
            return $this->getResponse(
                    [
                    'detail' => $ex->getMessage(),
                    ], self::HTTP_INTERNAL_ERROR
            );
        }

        return $this->getResponse([$user]);
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
                    'detail' => $ex->getMessage(),
                    ], self::HTTP_INTERNAL_ERROR
            );
        }

        return $this->getResponse($users);
    }

    /**
     * @Route("/read/{id}", name="read_id")
     */
    public function readOne(string $id, Request $raw): Response
    {
        try {
            $request = $this->unserializeRequest($raw);
            $this->authenticate($request);
            $user = $this->getDoctrine()->getRepository(User::class)->find($id);
        } catch (Exception $ex) {
            return $this->getResponse(
                    ['detail' => $ex->getMessage(),], self::HTTP_INTERNAL_ERROR
            );
        }

        return $this->getResponse([$user]);
    }

    /**
     * @Route("/delete/{id}", name="delete_id")
     */
    public function delete(string $id, Request $raw): Response
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
                    ['detail' => $ex->getMessage(),], self::HTTP_INTERNAL_ERROR
            );
        }

        return $this->getResponse(['deleted' => $id]);
    }

    /**
     * @Route("/update/{id}", name="update_id")
     */
    public function update(string $id, Request $raw): Response
    {
        try {
            $request = $this->unserializeRequest($raw);
            $this->authenticate($request);
            $entityManager = $this->getDoctrine()->getManager();
            $user = $this->getDoctrine()->getRepository(User::class)->find($id);
            $entityManager->flush();
        } catch (UniqueConstraintViolationException $uex) {
            return $this->getResponse(
                    ['details' => $uex->getMessage()], self::HTTP_INTERNAL_ERROR
            );
        } catch (Exception $ex) {
            return $this->getResponse(
                    ['details' => $ex->getMessage()], self::HTTP_INTERNAL_ERROR
            );
        }

        return $this->getResponse([$user]);
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
        $ok= ( $status === self::HTTP_OK ) ? self::OK : self::FAIL;
        
        $response = JsonResponse::fromJsonString(
            json_encode(
                ['payload' => $payload, 'success' => $ok],
                JSON_UNESCAPED_UNICODE
            ), 
            $status,
            self::CONTENT_TYPE
        );

        $this->log($response->getContent(), __METHOD__);        
        return $response;
    }
    
    /**
     * Authenticate user based on existence in db,
     * simple way but not best, yet should be enought for test purposes
     * @param array $request
     * @throws \Exception
     */
    private function authenticate(array $request = null): void
    {
        if (null === $request['auth'] || null === $request) {
            throw new \Exception(
            "brak danych logowania", self::HTTP_UNAUTHORIZED
            );
        }

        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy([
            'password' => sha1($request['auth']['password']),
            'login' => $request['auth']['login']
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
     * @return array|null
     * @throws \Exception
     */
    private function unserializeRequest(
        Request $raw,
        array $allowedMethods = ['POST', 'GET']        
    ): ?array {
        $this->log(json_encode($raw), __METHOD__);

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
    private function buildUserFromUserData(array $userData, int $id = null): User
    {
        if (null === $id) {
            $user = new User();
        } else {
            $user = $this->getDoctrine()->getRepository(User::class)->find($id);
        }

        if (isset($userData['payload']['name'])) {
            $user->setName($userData['payload']['name']);
        }
        if (isset($userData['payload']['login'])) {
            $user->setLogin($userData['payload']['login']);
        }
        if (isset($userData['payload']['password'])) {
            $user->setPassword(sha1($userData['payload']['password']));
        }

        return $user;
    }

    /**
     * simple db based logger. serve purpose for this particular use.
     * i'd go ELK in non-test case, with di wrapper to not break single resp.
     */
    private function log(string $mesage, string $method): void
    {
        $log = (new Log())
            ->setMessage($mesage)
            ->setTime(new \DateTime())
            ->setMethod($method);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($log);
        $entityManager->flush();
    }
}
