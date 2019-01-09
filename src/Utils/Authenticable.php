<?php

interface Authenticable
{
    public function Authenticate(User $user): bool;
}
