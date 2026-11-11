<?php

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

class TrachVentClassRegistrationException extends Exception
{
    public static function classNotFound(): self
    {
        return new self('Class not found.', Response::HTTP_NOT_FOUND);
    }

    public static function registrationNotFound(): self
    {
        return new self('This clinician is no longer registered for this class. Please reopen the class details.', Response::HTTP_CONFLICT);
    }

    public static function targetClassNotFound(): self
    {
        return new self('The selected class is no longer available.', Response::HTTP_CONFLICT);
    }

    public static function targetClassFull(): self
    {
        return new self('The selected class is now full. Please choose another class.', Response::HTTP_CONFLICT);
    }

    public static function alreadyRegisteredInTarget(): self
    {
        return new self('This clinician is already registered for the selected class.', Response::HTTP_CONFLICT);
    }
}
