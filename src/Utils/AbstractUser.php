<?php
declare(strict_types=1);
namespace App\Utils;

abstract class AbstractUser
{
    
    private $authorized;    

    public function getAuthorized(): bool
    {
        return $this->authorized;
    }

    public function setAuthorized($authorized): AbstractUser
    {
        $this->authorized = $authorized;
        return $this;
    }


    
}
