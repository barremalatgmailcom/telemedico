<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\Table(name="user", 
 *    uniqueConstraints={
 *        @ORM\UniqueConstraint(name="login_unique", 
 *            columns={"login"})
 *    }
 * )
 */
class User implements \JsonSerializable
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=55, nullable=true)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=60)
     */
    private $login;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $password;

    /**
     * @ORM\Column(type="time", nullable=true)
     */
    private $logged;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function setLogin(string $login): self
    {
        $this->login = $login;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getLogged(): ?\DateTimeInterface
    {
        return $this->logged;
    }

    public function setLogged(\DateTimeInterface $logged): self
    {
        $this->logged = $logged;

        return $this;
    }
    
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
