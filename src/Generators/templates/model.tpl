<?php declare(strict_types=1);

namespace {{namespace}};

use Lalaz\Data\Model;

class {{name}}Model extends Model
{
    protected function validates(): array
    {
        return [
            'fieldName' => [self::VALIDATE_REQUIRED]
        ];
    }
}
