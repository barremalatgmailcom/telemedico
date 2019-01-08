<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class ApiController extends AbstractController
{

    /**
     * @Route("/api", name="create")
     */
    public function index(): Response
    {
        $methodsAvailable = get_class_methods(self::class);
        return $this->formatOutput(['ok' => 1, 'collection' => $methodsAvailable]);
    }

    /**
     * @Route("/api/create", name="create")
     */
    public function create(): Response
    {
        if (!$this->isAuthorized()) {
            throw new Exception("not authorized");
        }

        return $this->getResponse(['ok' => 1]);
    }

    /**
     * @Route("/api/read", name="read")
     */
    public function read(): Response
    {
        if (!$this->isAuthorized()) {
            throw new Exception("not authorized");
        }

        return $this->getResponse(['ok' => 1]);
    }

    /**
     * @Route("/api/delete", name="delete")
     */
    public function delete(): Response
    {
        if (!$this->isAuthorized()) {
            throw new Exception("not authorized");
        }

        return $this->getResponse(['ok' => 1]);
    }

    /**
     * @Route("/api/update", name="update")
     */
    public function update(): Response
    {
        if (!$this->isAuthorized()) {
            throw new Exception("not authorized");
        }

        return $this->getResponse(['ok' => 1]);
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
            throw new Exception("response array should be associative array");
        }

        return new JsonResponse($responseData);
    }
}
