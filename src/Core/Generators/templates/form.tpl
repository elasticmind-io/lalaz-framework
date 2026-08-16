<?php declare(strict_types=1);

namespace {{namespace}};

use Lalaz\Validation\Validatable;

class {{name}}Form extends Validatable
{
    protected function validates(): array
    {
        return [
            'fieldName' => [self::VALIDATE_REQUIRED]
        ];
    }
}
