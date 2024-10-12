<?php declare(strict_types=1);

use Lalaz\Data\Presentable;

class UserPresenterStub
{
    use Presentable;

    public $name;
    public $email;

    public function __construct($name, $email)
    {
        $this->name = $name;
        $this->email = $email;
    }
}
