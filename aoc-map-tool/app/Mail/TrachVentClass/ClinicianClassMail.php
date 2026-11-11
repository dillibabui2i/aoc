<?php

namespace App\Mail\TrachVentClass;

use App\DTO\TrachVentClass\ClassEmailDetailsDTO;
use App\Enums\TrachVentClassClinicianEmailEnum;
use Illuminate\Mail\Mailable;

class ClinicianClassMail extends Mailable
{
    public const FROM_ADDRESS = 'noreply@angelsofcareapp.com';
    public const FROM_NAME = 'Angels of Care';

    public function __construct(
        public readonly TrachVentClassClinicianEmailEnum $type,
        public readonly string $clinicianName,
        public readonly ClassEmailDetailsDTO $class,
        public readonly ?ClassEmailDetailsDTO $previousClass = null,
    ) {
    }

    public function build(): self
    {
        return $this->from(self::FROM_ADDRESS, self::FROM_NAME)
            ->subject(sprintf(
                'Angels of Care: %s for %s Class on %s at %s',
                $this->type->subjectStatus(),
                $this->class->classType,
                $this->class->shortDate,
                $this->class->startTime,
            ))
            ->view('emailtemplate.trachventclass.clinician-class');
    }
}
